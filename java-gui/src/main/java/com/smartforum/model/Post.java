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
    public final String  imagePath;
    public final String  audioPath;
    public final String  filePath;
    public final String  fileName;
    public final long    fileSize;

    public Post(int id, int topicId, int userId, String authorName, String body,
                boolean bestAnswer, int upvotes, int downvotes, boolean syncPending,
                String imagePath, String audioPath, String filePath, String fileName, long fileSize) {
        this.id          = id;
        this.topicId     = topicId;
        this.userId      = userId;
        this.authorName  = authorName;
        this.body        = body;
        this.bestAnswer  = bestAnswer;
        this.upvotes     = upvotes;
        this.downvotes   = downvotes;
        this.syncPending = syncPending;
        this.imagePath   = imagePath;
        this.audioPath   = audioPath;
        this.filePath    = filePath;
        this.fileName    = fileName;
        this.fileSize    = fileSize;
    }

    /** Convenience constructor for plain text posts (no attachments). */
    public Post(int id, int topicId, int userId, String authorName, String body,
                boolean bestAnswer, int upvotes, int downvotes, boolean syncPending) {
        this(id, topicId, userId, authorName, body, bestAnswer, upvotes, downvotes, syncPending,
             null, null, null, null, 0);
    }

    /** Convenience constructor for pending offline posts (no votes, no attachments). */
    public Post(int id, int topicId, int userId, String authorName, String body,
                boolean bestAnswer, boolean syncPending) {
        this(id, topicId, userId, authorName, body, bestAnswer, 0, 0, syncPending,
             null, null, null, null, 0);
    }
}
