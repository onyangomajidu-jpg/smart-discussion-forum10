package com.smartforum.model;

public class Post {
    public final int     id;
    public final int     topicId;
    public final int     userId;
    public final String  authorName;
    public final String  body;
    public final boolean bestAnswer;
    public final boolean syncPending;

    public Post(int id, int topicId, int userId, String authorName, String body,
                boolean bestAnswer, boolean syncPending) {
        this.id          = id;
        this.topicId     = topicId;
        this.userId      = userId;
        this.authorName  = authorName;
        this.body        = body;
        this.bestAnswer  = bestAnswer;
        this.syncPending = syncPending;
    }
}
