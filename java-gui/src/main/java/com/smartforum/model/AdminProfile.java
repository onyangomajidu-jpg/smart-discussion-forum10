package com.smartforum.model;

/** Mirrors Laravel Admin model: admins table. */
public class AdminProfile {
    public final boolean superAdmin;

    public AdminProfile(boolean superAdmin) {
        this.superAdmin = superAdmin;
    }
}
