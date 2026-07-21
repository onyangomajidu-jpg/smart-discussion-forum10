package com.smartforum.ui;

import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;
import com.smartforum.model.Post;
import com.smartforum.model.Topic;
import com.smartforum.sync.OfflineSyncManager;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.*;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;

/**
 * Right panel — conversation view with posts, compose box, typing indicator,
 * syndicate option, edit/delete post, spam filter, Enter key to send.
 */
public class ConversationPanel extends JPanel {

    private static final Color PRIMARY    = new Color(0x66, 0x7E, 0xEA);
    private static final Color PENDING_BG = new Color(0xFF, 0xF3, 0xCD);
    private static final Color PENDING_FG = new Color(0x85, 0x64, 0x04);
    private static final Color BEST_BG    = new Color(0xD4, 0xED, 0xDA);
    private static final Color BORDER_C   = new Color(0xE1, 0xE4, 0xE8);

    private final LocalCacheDatabase cache;
    private final AuthUser           user;
    private final OfflineSyncManager syncManager;

    private Topic           currentTopic;
    private JButton         pinBtn, lockBtn;
    private final JLabel    topicHeader  = new JLabel("  💬 Select a topic");
    private final JPanel    postsPanel   = new JPanel();
    private final JTextArea composeBox   = new JTextArea(3, 40);
    private final JButton   sendBtn      = new JButton("Send");
    private final JLabel    statusLbl    = new JLabel(" ");
    private final JLabel    typingLbl    = new JLabel(" ");
    private final JCheckBox syndicateBox = new JCheckBox("Syndicate to other groups");
    private Timer           typingTimer;

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
        setBackground(new Color(0xF0, 0xF2, 0xF5));

        // Header
        topicHeader.setFont(new Font("Segoe UI", Font.BOLD, 16));
        topicHeader.setForeground(Color.WHITE);
        topicHeader.setOpaque(true);
        topicHeader.setBackground(PRIMARY);
        topicHeader.setBorder(new EmptyBorder(14, 16, 14, 16));

        // Topic action buttons (lecturer/admin only — set visible in loadTopic)
        JButton pinBtn  = new JButton("📌 Pin");
        JButton lockBtn = new JButton("🔒 Lock");
        styleActionBtn(pinBtn,  new Color(0xF5, 0x9E, 0x0B));
        styleActionBtn(lockBtn, new Color(0xEF, 0x44, 0x44));
        pinBtn.setVisible(false);
        lockBtn.setVisible(false);
        pinBtn.addActionListener(e -> {
            if (currentTopic == null) return;
            new SwingWorker<Void, Void>() {
                @Override protected Void doInBackground() throws Exception {
                    syncManager.getApi().post("/topics/" + currentTopic.id + "/pin", java.util.Map.of());
                    return null;
                }
                @Override protected void done() {
                    try { get(); syncManager.synchronizeOfflineData(); } catch (Exception ignored) {}
                }
            }.execute();
        });
        lockBtn.addActionListener(e -> {
            if (currentTopic == null) return;
            new SwingWorker<Void, Void>() {
                @Override protected Void doInBackground() throws Exception {
                    syncManager.getApi().post("/topics/" + currentTopic.id + "/lock", java.util.Map.of());
                    return null;
                }
                @Override protected void done() {
                    try { get(); syncManager.synchronizeOfflineData(); } catch (Exception ignored) {}
                }
            }.execute();
        });

        JPanel headerPanel = new JPanel(new BorderLayout());
        headerPanel.setBackground(PRIMARY);
        JPanel headerBtns = new JPanel(new FlowLayout(FlowLayout.RIGHT, 6, 8));
        headerBtns.setOpaque(false);
        headerBtns.add(pinBtn);
        headerBtns.add(lockBtn);
        headerPanel.add(topicHeader, BorderLayout.CENTER);
        headerPanel.add(headerBtns, BorderLayout.EAST);
        this.pinBtn  = pinBtn;
        this.lockBtn = lockBtn;

        // Posts area
        postsPanel.setLayout(new BoxLayout(postsPanel, BoxLayout.Y_AXIS));
        postsPanel.setBackground(new Color(0xF0, 0xF2, 0xF5));
        postsPanel.setBorder(new EmptyBorder(12, 12, 12, 12));
        JScrollPane scroll = new JScrollPane(postsPanel);
        scroll.setBorder(null);
        scroll.getVerticalScrollBar().setUnitIncrement(16);
        scroll.setBackground(new Color(0xF0, 0xF2, 0xF5));

        // Typing indicator
        typingLbl.setFont(new Font("Segoe UI", Font.ITALIC, 12));
        typingLbl.setForeground(new Color(0x71, 0x80, 0x96));
        typingLbl.setBorder(new EmptyBorder(2, 16, 2, 16));

        // Compose area
        composeBox.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        composeBox.setLineWrap(true);
        composeBox.setWrapStyleWord(true);
        composeBox.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 1),
            new EmptyBorder(8, 10, 8, 10)));

        // Enter = send, Shift+Enter = new line
        composeBox.addKeyListener(new KeyAdapter() {
            @Override public void keyPressed(KeyEvent e) {
                if (e.getKeyCode() == KeyEvent.VK_ENTER && !e.isShiftDown()) {
                    e.consume();
                    handleSend();
                }
            }
            @Override public void keyReleased(KeyEvent e) {
                if (e.getKeyCode() != KeyEvent.VK_ENTER) showTyping();
            }
        });

        sendBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        sendBtn.setBackground(PRIMARY);
        sendBtn.setForeground(Color.WHITE);
        sendBtn.setBorderPainted(false);
        sendBtn.setFocusPainted(false);
        sendBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        sendBtn.addActionListener(e -> handleSend());

        syndicateBox.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        syndicateBox.setForeground(new Color(0x71, 0x80, 0x96));
        syndicateBox.setBackground(Color.WHITE);

        statusLbl.setFont(new Font("Segoe UI", Font.ITALIC, 12));
        statusLbl.setForeground(PENDING_FG);
        statusLbl.setBorder(new EmptyBorder(4, 12, 0, 12));

        JPanel composeRow = new JPanel(new BorderLayout(8, 0));
        composeRow.setBackground(Color.WHITE);
        composeRow.setBorder(new EmptyBorder(8, 12, 4, 12));
        composeRow.add(new JScrollPane(composeBox), BorderLayout.CENTER);
        composeRow.add(sendBtn, BorderLayout.EAST);

        JPanel syndicateRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 12, 2));
        syndicateRow.setBackground(Color.WHITE);
        syndicateRow.add(syndicateBox);

        JPanel bottom = new JPanel(new BorderLayout());
        bottom.setBackground(Color.WHITE);
        bottom.setBorder(BorderFactory.createMatteBorder(1, 0, 0, 0, BORDER_C));
        bottom.add(statusLbl,    BorderLayout.NORTH);
        bottom.add(composeRow,   BorderLayout.CENTER);
        bottom.add(syndicateRow, BorderLayout.SOUTH);

        add(headerPanel, BorderLayout.NORTH);
        add(scroll,      BorderLayout.CENTER);
        add(buildSouthPanel(bottom), BorderLayout.SOUTH);
    }

    private JPanel buildSouthPanel(JPanel bottom) {
        JPanel south = new JPanel(new BorderLayout());
        south.add(typingLbl, BorderLayout.NORTH);
        south.add(bottom,    BorderLayout.CENTER);
        return south;
    }

    // ── Typing indicator ──────────────────────────────────────────────────

    private void showTyping() {
        typingLbl.setText(user.getName() + " is typing...");
        if (typingTimer != null) typingTimer.stop();
        typingTimer = new Timer(2000, e -> typingLbl.setText(" "));
        typingTimer.setRepeats(false);
        typingTimer.start();
    }

    public void showRemoteTyping(String name) {
        SwingUtilities.invokeLater(() -> {
            typingLbl.setText(name + " is typing...");
            if (typingTimer != null) typingTimer.stop();
            typingTimer = new Timer(2000, e -> typingLbl.setText(" "));
            typingTimer.setRepeats(false);
            typingTimer.start();
        });
    }

    // ── Public API ────────────────────────────────────────────────────────

    public void loadTopic(Topic topic) {
        this.currentTopic = topic;
        topicHeader.setText("  " + (topic.locked ? "🔒 " : "💬 ") + topic.title
            + "  —  " + topic.authorName);
        composeBox.setEnabled(!topic.locked);
        sendBtn.setEnabled(!topic.locked);
        if (topic.locked) statusLbl.setText("🔒 This topic is locked.");
        else statusLbl.setText(" ");
        boolean canModerate = user.isLecturer() || user.isAdmin();
        if (pinBtn  != null) { pinBtn.setVisible(canModerate);  pinBtn.setText(topic.pinned  ? "📌 Unpin" : "📌 Pin"); }
        if (lockBtn != null) { lockBtn.setVisible(canModerate); lockBtn.setText(topic.locked ? "🔓 Unlock" : "🔒 Lock"); }
        refreshPosts();
    }

    public void appendPost(Post post) {
        SwingUtilities.invokeLater(() -> {
            postsPanel.add(buildPostCard(post));
            postsPanel.revalidate();
            postsPanel.repaint();
            scrollToBottom();
        });
    }

    public void refreshPosts() {
        if (currentTopic == null) return;
        List<Post> posts = loadPosts(currentTopic.id);
        SwingUtilities.invokeLater(() -> {
            postsPanel.removeAll();
            if (posts.isEmpty()) {
                JLabel empty = new JLabel("No messages yet. Be the first to post!");
                empty.setFont(new Font("Segoe UI", Font.ITALIC, 13));
                empty.setForeground(new Color(0xA0, 0xAE, 0xC0));
                empty.setAlignmentX(CENTER_ALIGNMENT);
                postsPanel.add(Box.createVerticalGlue());
                postsPanel.add(empty);
            } else {
                posts.forEach(p -> postsPanel.add(buildPostCard(p)));
            }
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
        if (currentTopic.locked) {
            JOptionPane.showMessageDialog(this, "This topic is locked.");
            return;
        }
        String text = composeBox.getText().trim();
        if (text.isEmpty()) return;

        if (isSpam(text)) {
            JOptionPane.showMessageDialog(this,
                "Your message was flagged as spam and was not sent.",
                "Spam Detected", JOptionPane.WARNING_MESSAGE);
            return;
        }

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
                    typingLbl.setText(" ");
                    if (!online) setStatus("⏳ Message queued — will sync when online");
                    else setStatus(" ");
                    refreshPosts();
                } catch (Exception ex) {
                    setStatus("Error: " + ex.getMessage());
                }
            }
        }.execute();
    }

    // ── Edit post ─────────────────────────────────────────────────────────

    private void showEditDialog(Post post) {
        JDialog dialog = new JDialog((Frame) SwingUtilities.getWindowAncestor(this),
            "Edit Post", true);
        dialog.setSize(400, 220);
        dialog.setLocationRelativeTo(this);

        JPanel panel = new JPanel(new BorderLayout(8, 8));
        panel.setBorder(new EmptyBorder(16, 16, 16, 16));
        panel.setBackground(Color.WHITE);

        JTextArea editArea = new JTextArea(post.body, 5, 30);
        editArea.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        editArea.setLineWrap(true);
        editArea.setWrapStyleWord(true);

        JButton saveBtn = new JButton("Save");
        saveBtn.setBackground(PRIMARY);
        saveBtn.setForeground(Color.WHITE);
        saveBtn.setBorderPainted(false);
        saveBtn.setFocusPainted(false);
        saveBtn.addActionListener(e -> {
            String newBody = editArea.getText().trim();
            if (newBody.isEmpty()) return;
            if (isSpam(newBody)) {
                JOptionPane.showMessageDialog(dialog, "Content flagged as spam.");
                return;
            }
            new SwingWorker<Void, Void>() {
                @Override protected Void doInBackground() throws Exception {
                    syncManager.editPost(post.id, newBody);
                    return null;
                }
                @Override protected void done() {
                    dialog.dispose();
                    refreshPosts();
                }
            }.execute();
        });

        panel.add(new JScrollPane(editArea), BorderLayout.CENTER);
        panel.add(saveBtn, BorderLayout.SOUTH);
        dialog.setContentPane(panel);
        dialog.setVisible(true);
    }

    // ── Spam filter ───────────────────────────────────────────────────────

    private boolean isSpam(String content) {
        String lower = content.toLowerCase();
        String[] keywords = {"buy now","click here","free money","make money fast",
                             "casino","viagra","crypto giveaway","earn $","limited offer"};
        for (String kw : keywords) if (lower.contains(kw)) return true;
        int urlCount = 0, idx = 0;
        while ((idx = lower.indexOf("http", idx)) != -1) { urlCount++; idx += 4; }
        return urlCount > 3;
    }

    // ── Data loading ──────────────────────────────────────────────────────

    private List<Post> loadPosts(int topicId) {
        List<Post> result = new ArrayList<>();
        String sql = "SELECT id, topic_id, user_id, author_name, body, is_best_answer " +
                     "FROM cached_posts WHERE topic_id = ? ORDER BY id ASC";
        try (Connection conn = cache.connect();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, topicId);
            ResultSet rs = ps.executeQuery();
            while (rs.next()) {
                result.add(new Post(
                    rs.getInt("id"), rs.getInt("topic_id"), rs.getInt("user_id"),
                    rs.getString("author_name"), rs.getString("body"),
                    rs.getInt("is_best_answer") == 1, false));
            }
        } catch (SQLException e) {
            System.err.println("[ConversationPanel] loadPosts: " + e.getMessage());
        }

        String pendingSql = "SELECT id, user_id, body FROM pending_messages " +
                            "WHERE topic_id = ? AND synced = 0 ORDER BY id ASC";
        try (Connection conn = cache.connect();
             PreparedStatement ps = conn.prepareStatement(pendingSql)) {
            ps.setInt(1, topicId);
            ResultSet rs = ps.executeQuery();
            while (rs.next()) {
                result.add(new Post(
                    -rs.getInt("id"), topicId, user.getUserId(),
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
        JPanel card = new JPanel(new BorderLayout(0, 6));
        card.setBackground(post.syncPending ? PENDING_BG
                         : post.bestAnswer  ? BEST_BG
                         : Color.WHITE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
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

        // Action buttons (edit/delete — only for own posts or admin)
        JPanel actions = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        actions.setOpaque(false);

        if (!post.syncPending && (post.userId == user.getUserId() || user.getRole().equals("admin"))) {
            JButton editBtn = smallButton("✏ Edit", new Color(0x38, 0xA1, 0x69));
            editBtn.addActionListener(e -> showEditDialog(post));

            JButton deleteBtn = smallButton("🗑 Delete", new Color(0xE5, 0x3E, 0x3E));
            deleteBtn.addActionListener(e -> {
                int confirm = JOptionPane.showConfirmDialog(this,
                    "Delete this post?", "Confirm", JOptionPane.YES_NO_OPTION);
                if (confirm == JOptionPane.YES_OPTION) {
                    new SwingWorker<Void, Void>() {
                        @Override protected Void doInBackground() throws Exception {
                            syncManager.deletePost(post.id);
                            return null;
                        }
                        @Override protected void done() { refreshPosts(); }
                    }.execute();
                }
            });
            actions.add(editBtn);
            actions.add(deleteBtn);
        }

        card.add(authorRow, BorderLayout.NORTH);
        card.add(bodyArea,  BorderLayout.CENTER);
        if (actions.getComponentCount() > 0) card.add(actions, BorderLayout.SOUTH);
        return card;
    }

    private JButton smallButton(String text, Color color) {
        JButton btn = new JButton(text);
        btn.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        btn.setForeground(color);
        btn.setBackground(Color.WHITE);
        btn.setBorder(BorderFactory.createLineBorder(color, 1));
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return btn;
    }

    private void styleActionBtn(JButton btn, Color bg) {
        btn.setFont(new Font("Segoe UI", Font.BOLD, 11));
        btn.setForeground(Color.WHITE);
        btn.setBackground(bg);
        btn.setBorderPainted(false);
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
    }

    private void scrollToBottom() {
        JScrollPane sp = (JScrollPane) postsPanel.getParent().getParent();
        JScrollBar  sb = sp.getVerticalScrollBar();
        SwingUtilities.invokeLater(() -> sb.setValue(sb.getMaximum()));
    }
}
