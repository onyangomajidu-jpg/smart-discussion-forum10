package com.smartforum.api;

import com.fasterxml.jackson.databind.ObjectMapper;
import okhttp3.*;

import java.io.IOException;
import java.util.Map;
import java.util.concurrent.TimeUnit;

/**
 * Thin HTTP wrapper around the Laravel backend API.
 * All requests are sent to APP_URL (default http://localhost:8000/api).
 */
public class ApiClient {

    public static final String BASE_URL =
        System.getProperty("api.baseUrl", "http://discussionhub.onrender.com/api");

    private static final MediaType JSON = MediaType.get("application/json; charset=utf-8");

    private final OkHttpClient http;
    private final ObjectMapper mapper;
    private String bearerToken;

    public ApiClient() {
        this.http = new OkHttpClient.Builder()
            .connectTimeout(10, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .build();
        this.mapper = new ObjectMapper();
    }

    public void setToken(String token) {
        this.bearerToken = token;
    }

    private static String bodyOrEmpty(Response r) throws IOException {
        return r.body() != null ? r.body().string() : "";
    }

    /** GET request — returns response body as String. */
    public String get(String endpoint) throws IOException {
        Request request = new Request.Builder()
            .url(BASE_URL + endpoint)
            .header("Accept", "application/json")
            .header("Authorization", bearerToken != null ? "Bearer " + bearerToken : "")
            .build();
        try (Response response = http.newCall(request).execute()) {
            String body = bodyOrEmpty(response);
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code() + ": " + body);
            return body;
        }
    }

    /** POST request with a JSON body map. */
    public String post(String endpoint, Map<String, Object> body) throws IOException {
        String json = mapper.writeValueAsString(body);
        Request request = new Request.Builder()
            .url(BASE_URL + endpoint)
            .header("Accept", "application/json")
            .header("Authorization", bearerToken != null ? "Bearer " + bearerToken : "")
            .post(RequestBody.create(json, JSON))
            .build();
        try (Response response = http.newCall(request).execute()) {
            String rb = bodyOrEmpty(response);
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code() + ": " + rb);
            return rb;
        }
    }

    /** PUT request with a JSON body map. */
    public String put(String endpoint, Map<String, Object> body) throws IOException {
        String json = mapper.writeValueAsString(body);
        Request request = new Request.Builder()
            .url(BASE_URL + endpoint)
            .header("Accept", "application/json")
            .header("Authorization", bearerToken != null ? "Bearer " + bearerToken : "")
            .put(RequestBody.create(json, JSON))
            .build();
        try (Response response = http.newCall(request).execute()) {
            String rb = bodyOrEmpty(response);
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code() + ": " + rb);
            return rb;
        }
    }

    /** PATCH request with a JSON body map. */
    public String patch(String endpoint, Map<String, Object> body) throws IOException {
        String json = mapper.writeValueAsString(body);
        Request request = new Request.Builder()
            .url(BASE_URL + endpoint)
            .header("Accept", "application/json")
            .header("Authorization", bearerToken != null ? "Bearer " + bearerToken : "")
            .patch(RequestBody.create(json, JSON))
            .build();
        try (Response response = http.newCall(request).execute()) {
            String rb = bodyOrEmpty(response);
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code() + ": " + rb);
            return rb;
        }
    }

    /** DELETE request. */
    public String delete(String endpoint) throws IOException {
        Request request = new Request.Builder()
            .url(BASE_URL + endpoint)
            .header("Accept", "application/json")
            .header("Authorization", bearerToken != null ? "Bearer " + bearerToken : "")
            .delete()
            .build();
        try (Response response = http.newCall(request).execute()) {
            String rb = bodyOrEmpty(response);
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code() + ": " + rb);
            return rb;
        }
    }

    /** GET request — returns raw bytes (for binary responses like PDF). */
    public byte[] getBytes(String endpoint) throws IOException {
        Request request = new Request.Builder()
            .url(BASE_URL + endpoint)
            .header("Accept", "application/pdf")
            .header("Authorization", bearerToken != null ? "Bearer " + bearerToken : "")
            .build();
        try (Response response = http.newCall(request).execute()) {
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code());
            return response.body() != null ? response.body().bytes() : new byte[0];
        }
    }

    /** Multipart POST to upload an avatar image file — mirrors profile/edit.blade.php avatar upload. */
    public String uploadAvatar(String endpoint, java.io.File file) throws IOException {
        RequestBody body = new MultipartBody.Builder()
            .setType(MultipartBody.FORM)
            .addFormDataPart("avatar", file.getName(),
                RequestBody.create(file, MediaType.parse("image/*")))
            .addFormDataPart("_method", "POST")
            .build();
        Request request = new Request.Builder()
            .url(BASE_URL + endpoint)
            .header("Accept", "application/json")
            .header("Authorization", bearerToken != null ? "Bearer " + bearerToken : "")
            .post(body)
            .build();
        try (Response response = http.newCall(request).execute()) {
            String rb = bodyOrEmpty(response);
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code() + ": " + rb);
            return rb;
        }
    }

    /**
     * Multipart POST for attachments (image, file, audio).
     * fieldName  — form field name expected by the API ("image", "file", "audio")
     * mimeType   — e.g. "image/jpeg", "audio/wav", "application/octet-stream"
     * extraFields — additional string fields (e.g. topic_id, body)
     */
    public String postMultipart(String endpoint, String fieldName, java.io.File file,
                                String mimeType, Map<String, Object> extraFields) throws IOException {
        MultipartBody.Builder mb = new MultipartBody.Builder().setType(MultipartBody.FORM);
        mb.addFormDataPart(fieldName, file.getName(),
            RequestBody.create(file, MediaType.parse(mimeType)));
        for (Map.Entry<String, Object> e : extraFields.entrySet())
            mb.addFormDataPart(e.getKey(), String.valueOf(e.getValue()));
        Request request = new Request.Builder()
            .url(BASE_URL + endpoint)
            .header("Accept", "application/json")
            .header("Authorization", bearerToken != null ? "Bearer " + bearerToken : "")
            .post(mb.build())
            .build();
        try (Response response = http.newCall(request).execute()) {
            String rb = bodyOrEmpty(response);
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code() + ": " + rb);
            return rb;
        }
    }

    /** Returns true if the Laravel server is reachable. */
    public boolean isOnline() {
        try {
            get("/ping");
            return true;
        } catch (IOException e) {
            return false;
        }
    }
}
