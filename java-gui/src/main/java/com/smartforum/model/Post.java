package com.smartforum.model;

public class Post {
    public final int     id;
    public final int     topicId;
    public final int     userId;
    public final String  authorName;
    public final String  body;
    public final boolean bestAnswer;
    public final int     upvotes;
    public final int     downvotes;
    public final boolean syncPending;

    public Post(int id, int topicId, int userId, String authorName, String body,
                boolean bestAnswer, int upvotes, int downvotes, boolean syncPending) {
        this.id          = id;
        this.topicId     = topicId;
        this.userId      = userId;
        this.authorName  = authorName;
        this.body        = body;
        this.bestAnswer  = bestAnswer;
        this.upvotes     = upvotes;
        this.downvotes   = downvotes;
        this.syncPending = syncPending;
    }

    /** Convenience constructor for pending offline posts (no votes). */
    public Post(int id, int topicId, int userId, String authorName, String body,
                boolean bestAnswer, boolean syncPending) {
        this(id, topicId, userId, authorName, body, bestAnswer, 0, 0, syncPending);
    }
}
