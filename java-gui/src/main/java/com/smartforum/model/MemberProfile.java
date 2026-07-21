package com.smartforum.model;

/** Mirrors Laravel Member model: members table. */
public class MemberProfile {
    public final String studentId;
    public final String programme;
    public final int    yearOfStudy;
    public final int    reputation;

    public MemberProfile(String studentId, String programme, int yearOfStudy, int reputation) {
        this.studentId   = studentId;
        this.programme   = programme;
        this.yearOfStudy = yearOfStudy;
        this.reputation  = reputation;
    }
}
