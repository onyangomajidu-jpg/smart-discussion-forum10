package com.smartforum.api;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import okhttp3.*;

import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;

/**
 * Session-cookie HTTP client for the handful of Laravel "web" routes that
 * have no Sanctum-token equivalent under routes/api.php:
 *
 *   - Private messaging (routes/web.php "Private Messaging" group —
 *     /messages, /messages/{id}, /messages/{id}/poll, /messages/{id}/edit)
 *   - Topic attachment uploads (POST /topics/{id}/participate — the only
 *     route that accepts image/audio/file fields; /api/posts only accepts
 *     plain-text {topic_id, body})
 *
 * Laravel's routes/api.php intentionally isn't touched by this client or
 * anywhere else in the app — instead this re-authenticates exactly the way
 * a browser tab would (cookie jar + CSRF token obtained from the same
 * /csrf-token endpoint the login page itself uses), using the credentials
 * the person already typed into the desktop login screen. Everything here
 * talks to plain Laravel "web" routes, so ordinary session + CSRF rules
 * apply: GET needs nothing extra, POST/PUT/DELETE need the X-CSRF-TOKEN
 * header plus the session cookie.
 */
public class WebSessionClient {

    /** Root site URL, e.g. http://localhost:8000 — no /api suffix. */
    public final String rootUrl;

    private final OkHttpClient http;
    private final ObjectMapper mapper = new ObjectMapper();
    private final Map<String, List<Cookie>> cookieStore = new HashMap<>();

    private volatile String  csrfToken;
    private volatile boolean authenticated = false;

    public WebSessionClient(String rootUrl) {
        this.rootUrl = rootUrl.endsWith("/") ? rootUrl.substring(0, rootUrl.length() - 1) : rootUrl;
        this.http = new OkHttpClient.Builder()
            .connectTimeout(10, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .cookieJar(new CookieJar() {
                @Override public void saveFromResponse(HttpUrl url, List<Cookie> cookies) {
                    if (!cookies.isEmpty()) cookieStore.put(url.host(), cookies);
                }
                @Override public List<Cookie> loadForRequest(HttpUrl url) {
                    List<Cookie> saved = cookieStore.get(url.host());
                    return saved != null ? saved : new ArrayList<>();
                }
            })
            .build();
    }

    public boolean isAuthenticated() { return authenticated; }

    /**
     * Logs in with a real browser-style session: fetch a CSRF token +
     * session cookie, POST the same fields the login form posts, then
     * re-fetch the CSRF token (Laravel regenerates the session on login).
     * Safe to call from a background thread. Never throws — returns false
     * on any failure so the caller can degrade gracefully (attachments /
     * private messaging simply stay unavailable, everything else in the
     * app keeps working on the token API as before).
     */
    public synchronized boolean login(String email, String password) {
        try {
            String token = fetchCsrfToken();
            if (token == null) return false;

            RequestBody form = new FormBody.Builder()
                .add("email", email)
                .add("password", password)
                .add("_token", token)
                .build();
            Request req = new Request.Builder()
                .url(rootUrl + "/login")
                .header("X-CSRF-TOKEN", token)
                .header("Accept", "text/html,application/json")
                .post(form)
                .build();
            try (Response resp = http.newCall(req).execute()) {
                if (!resp.isSuccessful()) {
                    authenticated = false;
                    return false;
                }
            }

            // Session (and its CSRF token) is regenerated on successful
            // login — grab the fresh one before doing anything else.
            String fresh = fetchCsrfToken();
            csrfToken     = fresh != null ? fresh : token;
            authenticated = true;
            return true;
        } catch (Exception e) {
            System.err.println("[WebSessionClient] login failed: " + e.getMessage());
            authenticated = false;
            return false;
        }
    }

    public synchronized void logout() {
        authenticated = false;
        csrfToken      = null;
        cookieStore.clear();
    }

    private String fetchCsrfToken() {
        try {
            String body = rawGet("/csrf-token");
            JsonNode node = mapper.readTree(body);
            return node.path("token").asText(null);
        } catch (Exception e) {
            return null;
        }
    }

    private String rawGet(String path) throws IOException {
        Request req = new Request.Builder()
            .url(rootUrl + path)
            .header("Accept", "application/json")
            .header("X-Requested-With", "XMLHttpRequest")
            .build();
        try (Response resp = http.newCall(req).execute()) {
            ResponseBody body = resp.body();
            return body != null ? body.string() : "";
        }
    }

    // ── Public request helpers ──────────────────────────────────────────

    /** GET returning the raw response body (HTML or JSON) as a String. */
    public String get(String path) throws IOException {
        Request req = new Request.Builder()
            .url(rootUrl + path)
            .header("Accept", "application/json, text/html")
            .header("X-Requested-With", "XMLHttpRequest")
            .build();
        try (Response resp = http.newCall(req).execute()) {
            if (!resp.isSuccessful()) throw new IOException("HTTP " + resp.code());
            ResponseBody body = resp.body();
            return body != null ? body.string() : "";
        }
    }

    /** PUT with a JSON body — mirrors messages.update / posts.update. */
    public String putJson(String path, Map<String, Object> fields) throws IOException {
        String json = mapper.writeValueAsString(fields);
        Request req = new Request.Builder()
            .url(rootUrl + path)
            .header("Accept", "application/json")
            .header("Content-Type", "application/json")
            .header("X-CSRF-TOKEN", csrfToken != null ? csrfToken : "")
            .header("X-Requested-With", "XMLHttpRequest")
            .put(RequestBody.create(json, MediaType.get("application/json; charset=utf-8")))
            .build();
        try (Response resp = http.newCall(req).execute()) {
            if (!resp.isSuccessful()) throw new IOException("HTTP " + resp.code());
            ResponseBody body = resp.body();
            return body != null ? body.string() : "";
        }
    }

    /** DELETE — mirrors messages.destroy / posts.destroy. */
    public String delete(String path) throws IOException {
        Request req = new Request.Builder()
            .url(rootUrl + path)
            .header("Accept", "application/json")
            .header("X-CSRF-TOKEN", csrfToken != null ? csrfToken : "")
            .header("X-Requested-With", "XMLHttpRequest")
            .delete()
            .build();
        try (Response resp = http.newCall(req).execute()) {
            if (!resp.isSuccessful()) throw new IOException("HTTP " + resp.code());
            ResponseBody body = resp.body();
            return body != null ? body.string() : "";
        }
    }

    /**
     * Multipart POST — mirrors the form-with-file submits behind
     * topics.participate / messages.store (image / file / audio + body).
     */
    public String postMultipart(String path, Map<String, String> textFields,
                                Map<String, FilePart> fileFields) throws IOException {
        MultipartBody.Builder mb = new MultipartBody.Builder().setType(MultipartBody.FORM);
        mb.addFormDataPart("_token", csrfToken != null ? csrfToken : "");
        if (textFields != null) {
            for (Map.Entry<String, String> e : textFields.entrySet()) {
                if (e.getValue() != null) mb.addFormDataPart(e.getKey(), e.getValue());
            }
        }
        if (fileFields != null) {
            for (Map.Entry<String, FilePart> e : fileFields.entrySet()) {
                FilePart fp = e.getValue();
                mb.addFormDataPart(e.getKey(), fp.file.getName(),
                    RequestBody.create(fp.file, MediaType.parse(fp.mimeType)));
            }
        }
        Request req = new Request.Builder()
            .url(rootUrl + path)
            .header("Accept", "application/json")
            .header("X-CSRF-TOKEN", csrfToken != null ? csrfToken : "")
            .header("X-Requested-With", "XMLHttpRequest")
            .post(mb.build())
            .build();
        try (Response resp = http.newCall(req).execute()) {
            if (!resp.isSuccessful()) throw new IOException("HTTP " + resp.code());
            ResponseBody body = resp.body();
            return body != null ? body.string() : "";
        }
    }

    /** A single file field for {@link #postMultipart}. */
    public static class FilePart {
        public final File   file;
        public final String mimeType;
        public FilePart(File file, String mimeType) {
            this.file     = file;
            this.mimeType = mimeType;
        }
    }
}
