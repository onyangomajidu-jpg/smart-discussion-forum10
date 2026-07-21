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
        System.getProperty("api.baseUrl", "http://localhost:8000/api");

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

    /** GET request — returns response body as String. */
    public String get(String endpoint) throws IOException {
        Request request = new Request.Builder()
            .url(BASE_URL + endpoint)
            .header("Accept", "application/json")
            .header("Authorization", bearerToken != null ? "Bearer " + bearerToken : "")
            .build();
        try (Response response = http.newCall(request).execute()) {
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code());
            return response.body() != null ? response.body().string() : "";
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
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code());
            return response.body() != null ? response.body().string() : "";
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
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code());
            return response.body() != null ? response.body().string() : "";
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
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code());
            return response.body() != null ? response.body().string() : "";
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
            if (!response.isSuccessful()) throw new IOException("HTTP " + response.code());
            return response.body() != null ? response.body().string() : "";
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
