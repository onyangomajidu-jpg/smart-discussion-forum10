package com.smartforum.model;

/** Mirrors the Laravel Reply model (replies table). */
public class Reply {
    public final int    id;
    public final int    postId;
    public final int    userId;
    public final String authorName;
    public final String body;
    public final String createdAt;

    public Reply(int id, int postId, int userId, String authorName, String body, String createdAt) {
        this.id         = id;
        this.postId     = postId;
        this.userId     = userId;
        this.authorName = authorName;
        this.body       = body;
        this.createdAt  = createdAt;
    }
}
