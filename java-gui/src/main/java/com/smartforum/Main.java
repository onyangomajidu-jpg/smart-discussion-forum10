package com.smartforum;

import com.smartforum.cache.LocalCacheDatabase;

/**
 * Entry point for the Smart Discussion Forum desktop client.
 * Boots the local SQLite cache then launches the GUI.
 */
public class Main {

    public static void main(String[] args) {
        System.out.println("Smart Discussion Forum – Desktop Client starting...");

        // Initialise local SQLite cache (creates tables if not present)
        LocalCacheDatabase db = LocalCacheDatabase.getInstance();
        db.initialise();

        System.out.println("Local cache ready at: " + LocalCacheDatabase.DB_PATH);
        // TODO: launch JavaFX/Swing GUI here
    }
}
