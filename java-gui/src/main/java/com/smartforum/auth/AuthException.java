package com.smartforum.auth;

/** Thrown when the Laravel API rejects credentials (HTTP 401 / 422). */
public class AuthException extends Exception {
    public AuthException(String message) { super(message); }
}
