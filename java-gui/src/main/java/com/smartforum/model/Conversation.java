package com.smartforum.model;

/**
 * A row in the Direct Messages sidebar — either an existing conversation
 * (with a preview + unread count, mirrors messages.blade.php's
 * {@code $conversations}) or a "start new conversation" search result
 * (mirrors {@code $searchResults}), distinguished by {@link #isSearchResult}.
 */
public class Conversation {
    public final int     userId;
    public final String  name;
    public final String  roleOrPreview; // role (search result) or last-message preview (conversation)
    public final String  timeText;      // human "3m" style, empty for search results
    public final int     unread;
    public final boolean isSearchResult;

    public Conversation(int userId, String name, String roleOrPreview,
                        String timeText, int unread, boolean isSearchResult) {
        this.userId         = userId;
        this.name           = name;
        this.roleOrPreview  = roleOrPreview;
        this.timeText       = timeText;
        this.unread         = unread;
        this.isSearchResult = isSearchResult;
    }
}
