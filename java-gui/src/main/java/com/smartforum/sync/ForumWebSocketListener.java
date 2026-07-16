package com.smartforum.sync;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.model.Post;
import com.smartforum.ui.ConversationPanel;
import okhttp3.*;
import okio.ByteString;

/**
 * OkHttp WebSocket client that connects to Laravel Reverb and listens on
 * the {@code topic.{topicId}} channel for {@code new.post} events.
 *
 * On each event the new post is appended to the ConversationPanel live,
 * without a full reload.
 *
 * Protocol: Laravel Echo / Pusher wire format
 *   subscribe  → {"event":"pusher:subscribe","data":{"channel":"topic.N"}}
 *   broadcast  ← {"event":"App\\Events\\NewPost","channel":"topic.N","data":{...}}
 */
public class ForumWebSocketListener extends WebSocketListener {

    private static final String WS_URL =
        System.getProperty("ws.url", "ws://localhost:8080/app/local");

    private final ObjectMapper     mapper = new ObjectMapper();
    private final ConversationPanel panel;

    private WebSocket       socket;
    private int             subscribedTopicId = -1;
    private final OkHttpClient client;

    public ForumWebSocketListener(ConversationPanel panel) {
        this.panel  = panel;
        this.client = new OkHttpClient();
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────

    /** Opens the WebSocket connection. */
    public void connect() {
        Request req = new Request.Builder().url(WS_URL).build();
        socket = client.newWebSocket(req, this);
    }

    /** Subscribes to the given topic channel (unsubscribes from previous). */
    public void subscribeTopic(int topicId) {
        if (socket == null) return;
        if (subscribedTopicId == topicId) return;

        if (subscribedTopicId != -1) unsubscribe(subscribedTopicId);
        subscribedTopicId = topicId;

        String msg = """
            {"event":"pusher:subscribe","data":{"channel":"topic.%d"}}
            """.formatted(topicId).strip();
        socket.send(msg);
        System.out.println("[WS] Subscribed to topic." + topicId);
    }

    public void disconnect() {
        if (socket != null) socket.close(1000, "Client closed");
    }

    // ── WebSocketListener callbacks ───────────────────────────────────────

    @Override
    public void onOpen(WebSocket ws, Response response) {
        System.out.println("[WS] Connected to " + WS_URL);
    }

    @Override
    public void onMessage(WebSocket ws, String text) {
        try {
            JsonNode root    = mapper.readTree(text);
            String   event   = root.path("event").asText();
            String   channel = root.path("channel").asText();

            if (!"new.post".equals(event)) return;
            if (!channel.equals("topic." + subscribedTopicId)) return;

            // data may be a nested JSON string (Pusher wire format)
            JsonNode dataNode = root.path("data");
            JsonNode payload  = dataNode.isTextual()
                ? mapper.readTree(dataNode.asText())
                : dataNode;

            Post post = new Post(
                payload.path("id").asInt(-1),
                payload.path("topic_id").asInt(subscribedTopicId),
                payload.path("author_name").asText("Unknown"),
                payload.path("body").asText(""),
                false, false
            );
            panel.appendPost(post);

        } catch (Exception e) {
            System.err.println("[WS] onMessage parse error: " + e.getMessage());
        }
    }

    @Override
    public void onMessage(WebSocket ws, ByteString bytes) {
        onMessage(ws, bytes.utf8());
    }

    @Override
    public void onFailure(WebSocket ws, Throwable t, Response response) {
        System.err.println("[WS] Connection failure: " + t.getMessage());
        panel.setStatus("⚠ Real-time connection lost — working offline");
    }

    @Override
    public void onClosed(WebSocket ws, int code, String reason) {
        System.out.println("[WS] Closed: " + reason);
    }

    // ── Private ───────────────────────────────────────────────────────────

    private void unsubscribe(int topicId) {
        String msg = """
            {"event":"pusher:unsubscribe","data":{"channel":"topic.%d"}}
            """.formatted(topicId).strip();
        socket.send(msg);
    }
}
