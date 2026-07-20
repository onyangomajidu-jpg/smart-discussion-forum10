package com.smartforum.model;

/**
 * Immutable representation of the authenticated user.
 * Mirrors the user object returned by POST /api/login.
 */
public final class AuthUser {

    private final int    userId;
    private final String name;
    private final String email;
    private final String role;   // "member" | "lecturer" | "administrator"
    private final String token;  // Laravel Sanctum / Passport bearer token

    public AuthUser(int userId, String name, String email, String role, String token) {
        this.userId = userId;
        this.name   = name;
        this.email  = email;
        this.role   = role;
        this.token  = token;
    }

    public int    getUserId() { return userId; }
    public String getName()   { return name;   }
    public String getEmail()  { return email;  }
    public String getRole()   { return role;   }
    public String getToken()  { return token;  }

    public boolean isAdmin()    { return "admin".equalsIgnoreCase(role) || "administrator".equalsIgnoreCase(role); }
    public boolean isLecturer() { return "lecturer".equalsIgnoreCase(role);      }
    public boolean isMember()   { return "member".equalsIgnoreCase(role);        }

    @Override
    public String toString() {
        return "AuthUser{id=" + userId + ", name='" + name + "', role='" + role + "'}";
    }
}
