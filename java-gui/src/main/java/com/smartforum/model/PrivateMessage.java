package com.smartforum.model;

/**
 * A single private (1:1) message, mirroring the JSON shape returned by
 * MessageController::poll() — id, sender_id, body, deleted, image_path,
 * audio_path, file_path, file_name, file_size, reply_to{id,sender_id,body},
 * created_at.
 */
public class PrivateMessage {
    public final int     id;
    public final int     senderId;
    public final String  body;        // null when deleted
    public final boolean deleted;
    public final String  imagePath;
    public final String  audioPath;
    public final String  filePath;
    public final String  fileName;
    public final long    fileSize;
    public final Integer replyToId;       // null if not a reply
    public final int     replyToSenderId;
    public final String  replyToBody;
    public final String  createdAt;   // ISO 8601 string, as returned by the server
    public boolean        syncPending = false;

    public PrivateMessage(int id, int senderId, String body, boolean deleted,
                          String imagePath, String audioPath, String filePath,
                          String fileName, long fileSize,
                          Integer replyToId, int replyToSenderId, String replyToBody,
                          String createdAt) {
        this.id              = id;
        this.senderId        = senderId;
        this.body            = body;
        this.deleted         = deleted;
        this.imagePath       = imagePath;
        this.audioPath       = audioPath;
        this.filePath        = filePath;
        this.fileName        = fileName;
        this.fileSize        = fileSize;
        this.replyToId       = replyToId;
        this.replyToSenderId = replyToSenderId;
        this.replyToBody     = replyToBody;
        this.createdAt       = createdAt;
    }

    public boolean hasAttachment() {
        return imagePath != null || audioPath != null || filePath != null;
    }
}
