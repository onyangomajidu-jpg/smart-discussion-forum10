package com.smartforum.model;

/**
 * Mirrors the Laravel User model (users table) plus the role-specific
 * profile relation (Member / Lecturer / Admin).
 *
 * Core fields: id, name, email, role, avatar, bio, is_active
 * Role profile: memberProfile | lecturerProfile | adminProfile
 */
public final class AuthUser {

    // ── Core User fields (mirrors users table) ────────────────────────────
    private final int    userId;
    private final String name;
    private final String email;
    private final String role;      // "member" | "lecturer" | "admin"
    private final String token;     // Sanctum bearer token
    private final String avatar;    // nullable
    private final String bio;       // nullable
    private final boolean isActive;

    // ── Role-specific profile (mirrors Member / Lecturer / Admin tables) ──
    private MemberProfile   memberProfile;
    private LecturerProfile lecturerProfile;
    private AdminProfile    adminProfile;

    /** Minimal constructor used during login (profile loaded separately). */
    public AuthUser(int userId, String name, String email, String role, String token) {
        this(userId, name, email, role, token, null, null, true);
    }

    public AuthUser(int userId, String name, String email, String role, String token,
                    String avatar, String bio, boolean isActive) {
        this.userId   = userId;
        this.name     = name;
        this.email    = email;
        this.role     = role;
        this.token    = token;
        this.avatar   = avatar;
        this.bio      = bio;
        this.isActive = isActive;
    }

    // ── Core getters ──────────────────────────────────────────────────────
    public int     getUserId()  { return userId;   }
    public String  getName()    { return name;     }
    public String  getEmail()   { return email;    }
    public String  getRole()    { return role;     }
    public String  getToken()   { return token;    }
    public String  getAvatar()  { return avatar;   }
    public String  getBio()     { return bio;      }
    public boolean isActive()   { return isActive; }

    // ── Role helpers (mirrors User::isMember / isLecturer / isAdmin) ──────
    public boolean isAdmin()    { return "admin".equalsIgnoreCase(role);    }
    public boolean isLecturer() { return "lecturer".equalsIgnoreCase(role); }
    public boolean isMember()   { return "member".equalsIgnoreCase(role);   }

    // ── Role-profile accessors ────────────────────────────────────────────
    public MemberProfile   getMemberProfile()   { return memberProfile;   }
    public LecturerProfile getLecturerProfile() { return lecturerProfile; }
    public AdminProfile    getAdminProfile()    { return adminProfile;    }

    public void setMemberProfile(MemberProfile p)     { this.memberProfile   = p; }
    public void setLecturerProfile(LecturerProfile p) { this.lecturerProfile = p; }
    public void setAdminProfile(AdminProfile p)       { this.adminProfile    = p; }

    @Override
    public String toString() {
        return "AuthUser{id=" + userId + ", name='" + name + "', role='" + role + "'}";
    }
}
