package com.smartforum.ui;

import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;
import com.smartforum.model.Post;
import com.smartforum.model.Topic;
import com.smartforum.sync.OfflineSyncManager;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.Desktop;
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
    private static final Color SECONDARY  = new Color(0x76, 0x4B, 0xA2);
    private static final Color PENDING_BG = new Color(0xFF, 0xF3, 0xCD);
    private static final Color PENDING_FG = new Color(0x85, 0x64, 0x04);
    private static final Color BEST_BG    = new Color(0xD4, 0xED, 0xDA);
    private static final Color BORDER_C   = new Color(0xE1, 0xE4, 0xE8);
    private static final Color BG_BODY    = new Color(0xF0, 0xF2, 0xF5);

    private final LocalCacheDatabase cache;
    private final AuthUser           user;
    private final OfflineSyncManager syncManager;

    private Topic           currentTopic;
    private JButton         pinBtn, lockBtn, exportBtn, shareBtn;
    private JLabel          metaLbl;
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
        setBackground(BG_BODY);

        // ── conv-header: white bar with title + meta + action buttons ────────────
        topicHeader.setFont(new Font("Segoe UI", Font.BOLD, 17));
        topicHeader.setForeground(new Color(0x2D, 0x37, 0x48));
        topicHeader.setBorder(new EmptyBorder(0, 0, 2, 0));

        JLabel metaLbl = new JLabel(" ");
        metaLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        metaLbl.setForeground(new Color(0x71, 0x80, 0x96));
        this.metaLbl = metaLbl;

        JPanel titleBlock = new JPanel();
        titleBlock.setLayout(new BoxLayout(titleBlock, BoxLayout.Y_AXIS));
        titleBlock.setOpaque(false);
        titleBlock.add(topicHeader);
        titleBlock.add(metaLbl);

        // 📄 Export PDF button
        JButton exportBtn = new JButton("📄 Export PDF") {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), 0, SECONDARY));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 8, 8);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        exportBtn.setFont(new Font("Segoe UI", Font.BOLD, 12));
        exportBtn.setForeground(Color.WHITE);
        exportBtn.setOpaque(false);
        exportBtn.setContentAreaFilled(false);
        exportBtn.setBorderPainted(false);
        exportBtn.setFocusPainted(false);
        exportBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        exportBtn.setPreferredSize(new Dimension(120, 32));
        exportBtn.addActionListener(e -> {
            if (currentTopic != null) new ExportWindow(syncManager.getApi()).setVisible(true);
        });
        this.exportBtn = exportBtn;

        // 🌐 Share Discussion button
        JButton shareBtn = new JButton("🌐 Share") {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, new Color(0x25, 0xD3, 0x66), getWidth(), 0, new Color(0x12, 0x8C, 0x7E)));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 8, 8);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        shareBtn.setFont(new Font("Segoe UI", Font.BOLD, 12));
        shareBtn.setForeground(Color.WHITE);
        shareBtn.setOpaque(false);
        shareBtn.setContentAreaFilled(false);
        shareBtn.setBorderPainted(false);
        shareBtn.setFocusPainted(false);
        shareBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        shareBtn.setPreferredSize(new Dimension(90, 32));
        shareBtn.addActionListener(e -> showShareDialog());
        this.shareBtn = shareBtn;

        // Pin / Lock buttons (lecturer/admin)
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
        this.pinBtn  = pinBtn;
        this.lockBtn = lockBtn;

        JPanel headerBtns = new JPanel(new FlowLayout(FlowLayout.RIGHT, 6, 0));
        headerBtns.setOpaque(false);
        headerBtns.add(exportBtn);
        headerBtns.add(shareBtn);
        headerBtns.add(pinBtn);
        headerBtns.add(lockBtn);

        JPanel headerPanel = new JPanel(new BorderLayout(12, 0));
        headerPanel.setBackground(Color.WHITE);
        headerPanel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
            new EmptyBorder(14, 18, 14, 14)));
        headerPanel.add(titleBlock,  BorderLayout.CENTER);
        headerPanel.add(headerBtns, BorderLayout.EAST);
        exportBtn.setVisible(false);
        shareBtn.setVisible(false);

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
        topicHeader.setText((topic.locked ? "🔒 " : "") + topic.title);
        if (metaLbl != null) metaLbl.setText("Started by " + topic.authorName);
        composeBox.setEnabled(!topic.locked);
        sendBtn.setEnabled(!topic.locked);
        if (topic.locked) statusLbl.setText("🔒 This topic is locked.");
        else statusLbl.setText(" ");
        boolean canModerate = user.isLecturer() || user.isAdmin();
        if (pinBtn   != null) { pinBtn.setVisible(canModerate);  pinBtn.setText(topic.pinned  ? "📌 Unpin" : "📌 Pin"); }
        if (lockBtn  != null) { lockBtn.setVisible(canModerate); lockBtn.setText(topic.locked ? "🔓 Unlock" : "🔒 Lock"); }
        if (exportBtn != null) exportBtn.setVisible(true);
        if (shareBtn  != null) shareBtn.setVisible(true);
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

            // 🔒 Locked banner — mirrors the locked div in topics.blade.php
            if (currentTopic.locked) {
                JLabel lockedBanner = new JLabel("🔒 This topic is locked.", SwingConstants.CENTER);
                lockedBanner.setFont(new Font("Segoe UI", Font.BOLD, 13));
                lockedBanner.setForeground(new Color(0x85, 0x64, 0x04));
                lockedBanner.setOpaque(true);
                lockedBanner.setBackground(new Color(0xFF, 0xF3, 0xCD));
                lockedBanner.setBorder(new EmptyBorder(10, 16, 10, 16));
                lockedBanner.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
                postsPanel.add(lockedBanner);
                postsPanel.add(Box.createVerticalStrut(8));
            }

            if (posts.isEmpty()) {
                JLabel empty = new JLabel("💬 No messages yet. Be the first to post!", SwingConstants.CENTER);
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
        String sql = "SELECT id, topic_id, user_id, author_name, body, is_best_answer, upvotes, downvotes " +
                     "FROM cached_posts WHERE topic_id = ? ORDER BY id ASC";
        try (Connection conn = cache.connect();
             PreparedStatement ps = conn.prepareStatement(sql)) {
            ps.setInt(1, topicId);
            ResultSet rs = ps.executeQuery();
            while (rs.next()) {
                result.add(new Post(
                    rs.getInt("id"), rs.getInt("topic_id"), rs.getInt("user_id"),
                    rs.getString("author_name"), rs.getString("body"),
                    rs.getInt("is_best_answer") == 1,
                    rs.getInt("upvotes"), rs.getInt("downvotes"), false));
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
            BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
                BorderFactory.createMatteBorder(0, 4, 0, 0, new Color(0xE2, 0xE8, 0xF0))),
            new EmptyBorder(12, 14, 10, 14)));
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));

        // Avatar
        String initial = post.authorName.isEmpty() ? "?" : String.valueOf(post.authorName.charAt(0)).toUpperCase();
        JLabel avatarLbl = new JLabel(initial, SwingConstants.CENTER) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), SECONDARY));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 9, 9);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        avatarLbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        avatarLbl.setForeground(Color.WHITE);
        avatarLbl.setOpaque(false);
        avatarLbl.setPreferredSize(new Dimension(32, 32));
        avatarLbl.setMinimumSize(new Dimension(32, 32));
        avatarLbl.setMaximumSize(new Dimension(32, 32));

        // Author + badges row
        JPanel authorRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        authorRow.setOpaque(false);
        authorRow.add(avatarLbl);
        JLabel authorLbl = new JLabel(post.authorName);
        authorLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        authorLbl.setForeground(new Color(0x4A, 0x55, 0x68));
        authorRow.add(authorLbl);
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

        // Body
        JTextArea bodyArea = new JTextArea(post.body);
        bodyArea.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        bodyArea.setLineWrap(true);
        bodyArea.setWrapStyleWord(true);
        bodyArea.setEditable(false);
        bodyArea.setOpaque(false);
        bodyArea.setBorder(new EmptyBorder(4, 0, 4, 0));

        // Actions row — ↩ Reply always shown; ✏ Edit / 🗑 Delete for own/admin
        JPanel actions = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        actions.setOpaque(false);

        if (!post.syncPending) {
            JButton replyBtn = smallButton("↩ Reply", PRIMARY);
            replyBtn.addActionListener(e -> showReplyDialog(post));
            actions.add(replyBtn);

            if (post.userId == user.getUserId() || user.getRole().equals("admin")) {
                JButton editBtn = smallButton("✏ Edit", new Color(0x38, 0xA1, 0x69));
                editBtn.addActionListener(e -> showEditDialog(post));
                JButton deleteBtn = smallButton("🗑 Delete", new Color(0xE5, 0x3E, 0x3E));
                deleteBtn.addActionListener(e -> {
                    int ok = JOptionPane.showConfirmDialog(this,
                        "Delete this post?", "Confirm", JOptionPane.YES_NO_OPTION);
                    if (ok == JOptionPane.YES_OPTION) {
                        new SwingWorker<Void, Void>() {
                            @Override protected Void doInBackground() throws Exception {
                                syncManager.deletePost(post.id); return null;
                            }
                            @Override protected void done() { refreshPosts(); }
                        }.execute();
                    }
                });
                actions.add(editBtn);
                actions.add(deleteBtn);
            }
        }

        card.add(authorRow, BorderLayout.NORTH);
        card.add(bodyArea,  BorderLayout.CENTER);
        card.add(actions,   BorderLayout.SOUTH);
        return card;
    }

    // ── Reply dialog ──────────────────────────────────────────────────────

    private void showReplyDialog(Post post) {
        JDialog dialog = new JDialog((Frame) SwingUtilities.getWindowAncestor(this),
            "Reply to " + post.authorName, true);
        dialog.setSize(440, 180);
        dialog.setLocationRelativeTo(this);

        JPanel panel = new JPanel(new BorderLayout(8, 8));
        panel.setBorder(new EmptyBorder(14, 16, 14, 16));
        panel.setBackground(Color.WHITE);

        JTextField replyField = new JTextField();
        replyField.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        replyField.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 1),
            new EmptyBorder(6, 10, 6, 10)));

        JButton sendReply = new JButton("Send");
        sendReply.setBackground(PRIMARY);
        sendReply.setForeground(Color.WHITE);
        sendReply.setBorderPainted(false);
        sendReply.setFocusPainted(false);
        sendReply.addActionListener(e -> {
            String body = replyField.getText().trim();
            if (body.isEmpty()) return;
            new SwingWorker<Void, Void>() {
                @Override protected Void doInBackground() throws Exception {
                    syncManager.getApi().post("/posts/" + post.id + "/answer",
                        java.util.Map.of("body", body));
                    return null;
                }
                @Override protected void done() { dialog.dispose(); refreshPosts(); }
            }.execute();
        });
        dialog.getRootPane().setDefaultButton(sendReply);

        panel.add(new JLabel("Write a reply to " + post.authorName + ":"), BorderLayout.NORTH);
        panel.add(replyField, BorderLayout.CENTER);
        panel.add(sendReply,  BorderLayout.EAST);
        dialog.setContentPane(panel);
        dialog.setVisible(true);
    }

    // ── Share dialog — mirrors shareModal in topics.blade.php ─────────────

    private void showShareDialog() {
        if (currentTopic == null) return;
        String[] platforms = {"𝕏  Twitter / X", "💼  LinkedIn", "📘  Facebook", "💬  WhatsApp"};
        String choice = (String) JOptionPane.showInputDialog(
            this, "Choose a platform to share the discussion:",
            "🌐 Share Discussion", JOptionPane.PLAIN_MESSAGE,
            null, platforms, platforms[0]);
        if (choice == null) return;
        String url = "http://localhost:8000/topics/" + currentTopic.id;
        String encoded = java.net.URLEncoder.encode(
            "📚 \"" + currentTopic.title + "\" — join the discussion on Discussion Hub\n" + url,
            java.nio.charset.StandardCharsets.UTF_8);
        String shareUrl;
        if      (choice.contains("Twitter"))  shareUrl = "https://twitter.com/intent/tweet?text=" + encoded;
        else if (choice.contains("LinkedIn")) shareUrl = "https://www.linkedin.com/sharing/share-offsite/?url=" + java.net.URLEncoder.encode(url, java.nio.charset.StandardCharsets.UTF_8);
        else if (choice.contains("Facebook")) shareUrl = "https://www.facebook.com/sharer/sharer.php?u=" + java.net.URLEncoder.encode(url, java.nio.charset.StandardCharsets.UTF_8);
        else                                  shareUrl = "https://wa.me/?text=" + encoded;
        try {
            Desktop.getDesktop().browse(new java.net.URI(shareUrl));
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Open this URL:\n" + shareUrl);
        }
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
