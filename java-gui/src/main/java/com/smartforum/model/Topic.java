package com.smartforum.model;

public class Topic {
    public final int    id;
    public final int    groupId;
    public final String title;
    public final String body;
    public final String authorName;
    public final boolean pinned;
    public final boolean locked;

    public Topic(int id, int groupId, String title, String body,
                 String authorName, boolean pinned, boolean locked) {
        this.id         = id;
        this.groupId    = groupId;
        this.title      = title;
        this.body       = body;
        this.authorName = authorName;
        this.pinned     = pinned;
        this.locked     = locked;
    }

    @Override public String toString() {
        return (pinned ? "📌 " : "") + title;
    }
}
