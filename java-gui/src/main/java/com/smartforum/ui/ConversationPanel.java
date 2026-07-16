package com.smartforum.ui;

import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;
import com.smartforum.model.Post;
import com.smartforum.model.Topic;
import com.smartforum.sync.OfflineSyncManager;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * Right panel — displays posts for the selected topic and a compose box.
 *
 * Offline behaviour:
 *   • Send while offline → storeOfflineData() saves to pending_messages
 *   • Pending messages shown with a "⏳ Sync pending" badge
 *   • On reconnect the OfflineSyncManager calls synchronizeOfflineData()
 */
public class ConversationPanel extends JPanel {

    private static final Color PRIMARY      = new Color(0x66, 0x7E, 0xEA);
    private static final Color PENDING_BG   = new Color(0xFF, 0xF3, 0xCD);
    private static final Color PENDING_FG   = new Color(0x85, 0x64, 0x04);
    private static final Color BEST_BG      = new Color(0xD4, 0xED, 0xDA);

    private final LocalCacheDatabase cache;
    private final AuthUser           user;
    private final OfflineSyncManager syncManager;

    private Topic         currentTopic;
    private final JLabel  topicHeader  = new JLabel("Select a topic");
    private final JPanel  postsPanel   = new JPanel();
    private final JTextArea composeBox = new JTextArea(3, 40);
    private final JButton   sendBtn    = new JButton("Send");
    private final JLabel    statusLbl  = new JLabel(" ");

    public ConversationPanel(LocalCacheDatabase cache, AuthUser user,
                             OfflineSyncManager syncManager) {
        this.cache       = cache;
        this.user        = user;
        this.syncManager = syncManager;
        buildUI();
    }

    // ── UI construction ───────────────────────────────────────────────────

    private void buildUI() {
        setLayout(new BorderLayout(0, 0));
        setBackground(Color.WHITE);

        // Header
        topicHeader.setFont(new Font("Segoe UI", Font.BOLD, 16));
        topicHeader.setForeground(Color.WHITE);
        topicHeader.setOpaque(true);
        topicHeader.setBackground(PRIMARY);
        topicHeader.setBorder(new EmptyBorder(12, 16, 12, 16));

        // Posts area
        postsPanel.setLayout(new BoxLayout(postsPanel, BoxLayout.Y_AXIS));
        postsPanel.setBackground(Color.WHITE);
        JScrollPane scroll = new JScrollPane(postsPanel);
        scroll.setBorder(null);
        scroll.getVerticalScrollBar().setUnitIncrement(16);

        // Compose area
        composeBox.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        composeBox.setLineWrap(true);
        composeBox.setWrapStyleWord(true);
        composeBox.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(new Color(0xE1, 0xE4, 0xE8), 1),
            new EmptyBorder(6, 8, 6, 8)));

        sendBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        sendBtn.setBackground(PRIMARY);
        sendBtn.setForeground(Color.WHITE);
        sendBtn.setBorderPainted(false);
        sendBtn.setFocusPainted(false);
        sendBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        sendBtn.addActionListener(e -> handleSend());

        statusLbl.setFont(new Font("Segoe UI", Font.ITALIC, 12));
        statusLbl.setForeground(PENDING_FG);

        JPanel composeRow = new JPanel(new BorderLayout(8, 0));
        composeRow.setBackground(Color.WHITE);
        composeRow.setBorder(new EmptyBorder(8, 12, 12, 12));
        composeRow.add(new JScrollPane(composeBox), BorderLayout.CENTER);
        composeRow.add(sendBtn, BorderLayout.EAST);

        JPanel bottom = new JPanel(new BorderLayout());
        bottom.setBackground(Color.WHITE);
        bottom.setBorder(BorderFactory.createMatteBorder(1, 0, 0, 0, new Color(0xE1, 0xE4, 0xE8)));
        bottom.add(statusLbl,  BorderLayout.NORTH);
        bottom.add(composeRow, BorderLayout.CENTER);

        add(topicHeader, BorderLayout.NORTH);
        add(scroll,      BorderLayout.CENTER);
        add(bottom,      BorderLayout.SOUTH);
    }

    // ── Public API ────────────────────────────────────────────────────────

    /** Called by TopicListPanel selection and WebSocket new-post events. */
    public void loadTopic(Topic topic) {
        this.currentTopic = topic;
        topicHeader.setText("  " + (topic.locked ? "🔒 " : "💬 ") + topic.title
            + "  —  " + topic.authorName);
        refreshPosts();
    }

    /** Appends a single new post received via WebSocket without full reload. */
    public void appendPost(Post post) {
        SwingUtilities.invokeLater(() -> {
            postsPanel.add(buildPostCard(post));
            postsPanel.revalidate();
            postsPanel.repaint();
            scrollToBottom();
        });
    }

    /** Refreshes the post list (called after sync completes). */
    public void refreshPosts() {
        if (currentTopic == null) return;
        List<Post> posts = loadPosts(currentTopic.id);
        SwingUtilities.invokeLater(() -> {
            postsPanel.removeAll();
            posts.forEach(p -> postsPanel.add(buildPostCard(p)));
            postsPanel.revalidate();
            postsPanel.repaint();
            scrollToBottom();
        });
    }

    public void setStatus(String msg) {
        SwingUtilities.invokeLater(() -> statusLbl.setText(msg));
    }

    // ── Send logic ────────────────────────────────────────────────────────

    private void handleSend() {
        if (currentTopic == null) {
            JOptionPane.showMessageDialog(this, "Select a topic first.");
            return;
        }
        String text = composeBox.getText().trim();
        if (text.isEmpty()) return;

        sendBtn.setEnabled(false);
        new SwingWorker<Boolean, Void>() {
            @Override protected Boolean doInBackground() {
                return syncManager.sendOrQueue(currentTopic.id, user.getUserId(), text);
            }
            @Override protected void done() {
                sendBtn.setEnabled(true);
                try {
                    boolean online = get();
                    composeBox.setText("");
                    if (!online) setStatus("⏳ Message queued — will sync when online");
                    refreshPosts();
                } catch (Exception ex) {
                    setStatus("Error: " + ex.getMessage());
                }
            }
        }.execute();
    }

    // ── Data loading ──────────────────────────────────────────────────────

    private List<Post> loadPosts(int topicId) {
        List<Post> result = new ArrayList<>();

        // Cached (synced) posts
        String sql = "SELECT id, topic_id, author_name, body, is_best_answer " +
                     "FROM cached_posts WHERE topic_id = ? ORDER BY id ASC";
        try (Connection conn = cache.connect();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, topicId);
            ResultSet rs = ps.executeQuery();
            while (rs.next()) {
                result.add(new Post(
                    rs.getInt("id"), rs.getInt("topic_id"),
                    rs.getString("author_name"), rs.getString("body"),
                    rs.getInt("is_best_answer") == 1, false));
            }
        } catch (SQLException e) {
            System.err.println("[ConversationPanel] loadPosts: " + e.getMessage());
        }

        // Pending (offline) messages for this topic
        String pendingSql = "SELECT id, user_id, body FROM pending_messages " +
                            "WHERE topic_id = ? AND synced = 0 ORDER BY id ASC";
        try (Connection conn = cache.connect();
             PreparedStatement ps = conn.prepareStatement(pendingSql)) {
            ps.setInt(1, topicId);
            ResultSet rs = ps.executeQuery();
            while (rs.next()) {
                result.add(new Post(
                    -rs.getInt("id"), topicId,
                    user.getName() + " (you)", rs.getString("body"),
                    false, true));
            }
        } catch (SQLException e) {
            System.err.println("[ConversationPanel] loadPending: " + e.getMessage());
        }

        return result;
    }

    // ── Post card builder ─────────────────────────────────────────────────

    private JPanel buildPostCard(Post post) {
        JPanel card = new JPanel(new BorderLayout(0, 4));
        card.setBackground(post.syncPending ? PENDING_BG
                         : post.bestAnswer  ? BEST_BG
                         : Color.WHITE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, new Color(0xE1, 0xE4, 0xE8)),
            new EmptyBorder(10, 14, 10, 14)));
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));

        // Author row
        JPanel authorRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        authorRow.setOpaque(false);

        JLabel author = new JLabel(post.authorName);
        author.setFont(new Font("Segoe UI", Font.BOLD, 13));
        author.setForeground(PRIMARY);
        authorRow.add(author);

        if (post.bestAnswer) {
            JLabel badge = new JLabel("✅ Best Answer");
            badge.setFont(new Font("Segoe UI", Font.BOLD, 11));
            badge.setForeground(new Color(0x15, 0x52, 0x24));
            authorRow.add(badge);
        }
        if (post.syncPending) {
            JLabel badge = new JLabel("⏳ Sync pending");
            badge.setFont(new Font("Segoe UI", Font.ITALIC, 11));
            badge.setForeground(PENDING_FG);
            authorRow.add(badge);
        }

        JTextArea bodyArea = new JTextArea(post.body);
        bodyArea.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        bodyArea.setLineWrap(true);
        bodyArea.setWrapStyleWord(true);
        bodyArea.setEditable(false);
        bodyArea.setOpaque(false);
        bodyArea.setBorder(null);

        card.add(authorRow, BorderLayout.NORTH);
        card.add(bodyArea,  BorderLayout.CENTER);
        return card;
    }

    private void scrollToBottom() {
        JScrollPane sp = (JScrollPane) postsPanel.getParent().getParent();
        JScrollBar  sb = sp.getVerticalScrollBar();
        SwingUtilities.invokeLater(() -> sb.setValue(sb.getMaximum()));
    }
}
