package com.smartforum.model;

public class Topic {
    public final int     id;
    public final int     groupId;
    public final int     userId;
    public final String  title;
    public final String  slug;
    public final String  body;
    public final String  authorName;
    public final boolean pinned;
    public final boolean locked;
    public final int     views;
    public final int     postsCount;

    public Topic(int id, int groupId, int userId, String title, String slug, String body,
                 String authorName, boolean pinned, boolean locked, int views, int postsCount) {
        this.id         = id;
        this.groupId    = groupId;
        this.userId     = userId;
        this.title      = title;
        this.slug       = slug;
        this.body       = body;
        this.authorName = authorName;
        this.pinned     = pinned;
        this.locked     = locked;
        this.views      = views;
        this.postsCount = postsCount;
    }

    @Override public String toString() {
        return (pinned ? "📌 " : "") + title;
    }
}
