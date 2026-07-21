package com.smartforum.model;

/** Mirrors Laravel Lecturer model: lecturers table. */
public class LecturerProfile {
    public final String staffId;
    public final String department;
    public final String specialisation;

    public LecturerProfile(String staffId, String department, String specialisation) {
        this.staffId        = staffId;
        this.department     = department;
        this.specialisation = specialisation;
    }
}
