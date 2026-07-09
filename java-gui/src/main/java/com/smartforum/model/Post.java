package com.smartforum.model;

public class Post {
    public final int     id;
    public final int     topicId;
    public final String  authorName;
    public final String  body;
    public final boolean bestAnswer;
    /** true = saved locally, not yet uploaded to server */
    public final boolean syncPending;

    public Post(int id, int topicId, String authorName, String body,
                boolean bestAnswer, boolean syncPending) {
        this.id          = id;
        this.topicId     = topicId;
        this.authorName  = authorName;
        this.body        = body;
        this.bestAnswer  = bestAnswer;
        this.syncPending = syncPending;
    }
}
