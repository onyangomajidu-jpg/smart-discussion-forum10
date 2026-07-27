package com.smartforum.ui;

import com.smartforum.api.WebSessionClient;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;
import com.smartforum.model.Post;
import com.smartforum.model.Topic;
import com.smartforum.sync.OfflineSyncManager;

import javax.sound.sampled.*;
import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.Desktop;
import java.awt.event.*;
import java.awt.image.BufferedImage;
import java.io.*;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

/**
 * Right panel — conversation view with posts, compose box, typing indicator,
 * syndicate option, edit/delete post, spam filter, Enter key to send.
 */
public class ConversationPanel extends JPanel {

    // PRIMARY/SECONDARY were off-brand (#667EEA/#764BA2); now the exact
    // Laravel gradient via Theme. Other tokens here (pending/best-answer
    // tints, chat body background) aren't part of Laravel's root palette,
    // so they stay as local constants.
    private static final Color PRIMARY    = Theme.PRIMARY;
    private static final Color SECONDARY  = Theme.SECONDARY;
    private static final Color PENDING_BG = new Color(0xFF, 0xF3, 0xCD);
    private static final Color PENDING_FG = new Color(0x85, 0x64, 0x04);
    private static final Color BEST_BG    = new Color(0xD4, 0xED, 0xDA);
    private static final Color BORDER_C   = Theme.BORDER;
    private static final Color BG_BODY    = new Color(0xF0, 0xF2, 0xF5);

    private final LocalCacheDatabase cache;
    private final AuthUser           user;
    private final OfflineSyncManager syncManager;

    // Attachment uploads (image/audio/file) only exist as a session-based
    // Laravel "web" route (POST /topics/{id}/participate) — the token API
    // under /api/posts is text-only. This client re-authenticates as a
    // normal browser session with the same credentials so those uploads
    // can actually reach the server (see WebSessionClient's class javadoc).
    // May be null (e.g. in older call sites/tests) or simply not
    // authenticated if the web login failed — both are handled by falling
    // back to the previous "queue as text" behavior.
    private final WebSessionClient webSession;

    private Topic           currentTopic;
    private JButton         pinBtn, lockBtn, exportBtn, shareBtn, participantsBtn, clearChatBtn;
    private JLabel          metaLbl;
    private final JLabel    topicHeader  = new JLabel("  💬 Select a topic");
    private final JPanel    postsPanel   = new JPanel();
    private final JTextArea composeBox   = new JTextArea(3, 40);
    private final JButton   sendBtn      = new JButton("Send");
    private final JLabel    statusLbl    = new JLabel(" ");
    private final JLabel    typingLbl    = new JLabel(" ");
    private final JCheckBox syndicateBox = new JCheckBox("Syndicate to other groups");

    // ── Reply bar (inline quote above compose, mirrors topics.blade.php reply-bar) ──
    private int             replyToPostId   = -1;
    private String          replyToAuthor   = null;
    private String          replyToBody     = null;
    private JPanel          replyBar;
    private JLabel          replyBarAuthor;
    private JLabel          replyBarBody;

    // ── Clear-chat watermark (device-local, mirrors clearTopicChat in blade) ──
    private java.util.Map<Integer, Long> clearTimestamps = new java.util.HashMap<>();
    private Timer           typingTimer;

    // ── Voice recording state ─────────────────────────────────────────────
    private TargetDataLine  micLine;
    private Thread          recordThread;
    private File            pendingAudio;
    private boolean         recording = false;

    // ── Pending attachment (image or file chosen before send) ─────────────
    private File            pendingAttachment;
    private String          pendingAttachmentType; // "image" | "file"
    private JLabel          attachPreviewLbl;

    public ConversationPanel(LocalCacheDatabase cache, AuthUser user,
                             OfflineSyncManager syncManager) {
        this(cache, user, syncManager, null);
    }

    public ConversationPanel(LocalCacheDatabase cache, AuthUser user,
                             OfflineSyncManager syncManager, WebSessionClient webSession) {
        this.cache       = cache;
        this.user        = user;
        this.syncManager = syncManager;
        this.webSession  = webSession;
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

        // 👥 Participants (mirrors the "Participants" side panel in topics.blade.php
        // — remove/restore/block/unblock — only visible to the topic creator or an admin,
        // matching TopicController's authorization check)
        JButton participantsBtn = new JButton("👥 Participants");
        styleActionBtn(participantsBtn, Theme.PRIMARY_DARK);
        participantsBtn.setVisible(false);
        participantsBtn.addActionListener(e -> showParticipantsDialog());
        this.participantsBtn = participantsBtn;

        JButton clearChatBtn = new JButton("🗑 Clear Chat");
        clearChatBtn.setFont(new Font("Segoe UI", Font.BOLD, 11));
        clearChatBtn.setForeground(new Color(0x64, 0x74, 0x8B));
        clearChatBtn.setBackground(new Color(0xF1, 0xF5, 0xF9));
        clearChatBtn.setBorder(BorderFactory.createLineBorder(BORDER_C, 1));
        clearChatBtn.setFocusPainted(false);
        clearChatBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        clearChatBtn.setVisible(false);
        clearChatBtn.addActionListener(e -> clearChat());
        this.clearChatBtn = clearChatBtn;

        JPanel headerBtns = new JPanel(new FlowLayout(FlowLayout.RIGHT, 6, 0));
        headerBtns.setOpaque(false);
        headerBtns.add(exportBtn);
        headerBtns.add(shareBtn);
        headerBtns.add(clearChatBtn);
        headerBtns.add(pinBtn);
        headerBtns.add(lockBtn);
        headerBtns.add(participantsBtn);

        JPanel headerPanel = new JPanel(new BorderLayout(12, 0));
        headerPanel.setBackground(Color.WHITE);
        headerPanel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
            new EmptyBorder(14, 18, 14, 14)));
        headerPanel.add(titleBlock,  BorderLayout.CENTER);
        headerPanel.add(headerBtns, BorderLayout.EAST);
        exportBtn.setVisible(false);
        shareBtn.setVisible(false);
        clearChatBtn.setVisible(false);

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

        // ── Reply bar (hidden until a reply is triggered) ────────────────
        replyBarAuthor = new JLabel();
        replyBarAuthor.setFont(new Font("Segoe UI", Font.BOLD, 12));
        replyBarAuthor.setForeground(PRIMARY);
        replyBarBody = new JLabel();
        replyBarBody.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        replyBarBody.setForeground(new Color(0x4A, 0x55, 0x68));
        JButton cancelReplyBtn = new JButton("✕");
        cancelReplyBtn.setFont(new Font("Segoe UI", Font.BOLD, 11));
        cancelReplyBtn.setForeground(new Color(0xA0, 0xAE, 0xC0));
        cancelReplyBtn.setBackground(Color.WHITE);
        cancelReplyBtn.setBorderPainted(false);
        cancelReplyBtn.setFocusPainted(false);
        cancelReplyBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        cancelReplyBtn.addActionListener(e -> cancelReply());
        replyBar = new JPanel(new BorderLayout(6, 0));
        replyBar.setBackground(new Color(0xE8, 0xE6, 0xFF));
        replyBar.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(new Color(0xC4, 0xB5, 0xFD), 1),
            new EmptyBorder(6, 10, 6, 10)));
        JPanel replyBarText = new JPanel();
        replyBarText.setLayout(new BoxLayout(replyBarText, BoxLayout.Y_AXIS));
        replyBarText.setOpaque(false);
        replyBarText.add(replyBarAuthor);
        replyBarText.add(replyBarBody);
        replyBar.add(replyBarText, BorderLayout.CENTER);
        replyBar.add(cancelReplyBtn, BorderLayout.EAST);
        replyBar.setVisible(false);

        // ── Attachment toolbar (📷 image | 📎 file | 📸 camera) ──────────────
        JButton imgBtn = attachBtn("🖼", "Image");
        JButton fileBtn = attachBtn("📎", "File");
        JButton camBtn = attachBtn("📸", "Camera");
        imgBtn.addActionListener(e -> pickAttachment("image"));
        fileBtn.addActionListener(e -> pickAttachment("file"));
        camBtn.addActionListener(e -> captureCamera());

        attachPreviewLbl = new JLabel();
        attachPreviewLbl.setFont(new Font("Segoe UI", Font.ITALIC, 11));
        attachPreviewLbl.setForeground(new Color(0x38, 0xA1, 0x69));
        attachPreviewLbl.setBorder(new EmptyBorder(0, 6, 0, 0));

        JButton clearAttachBtn = new JButton("✕");
        clearAttachBtn.setFont(new Font("Segoe UI", Font.BOLD, 10));
        clearAttachBtn.setForeground(new Color(0xE5, 0x3E, 0x3E));
        clearAttachBtn.setBackground(Color.WHITE);
        clearAttachBtn.setBorderPainted(false);
        clearAttachBtn.setFocusPainted(false);
        clearAttachBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        clearAttachBtn.addActionListener(e -> clearAttachment());

        JPanel attachRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 2));
        attachRow.setBackground(Color.WHITE);
        attachRow.add(imgBtn);
        attachRow.add(fileBtn);
        attachRow.add(camBtn);
        attachRow.add(attachPreviewLbl);
        attachRow.add(clearAttachBtn);

        // ── Mic button ────────────────────────────────────────────────────
        JButton micBtn = new JButton("🎤");
        micBtn.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 16));
        micBtn.setBackground(Color.WHITE);
        micBtn.setBorderPainted(false);
        micBtn.setFocusPainted(false);
        micBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        micBtn.setToolTipText("Hold to record voice message");
        micBtn.addActionListener(e -> toggleRecording(micBtn));

        JPanel composeRow = new JPanel(new BorderLayout(8, 0));
        composeRow.setBackground(Color.WHITE);
        composeRow.setBorder(new EmptyBorder(4, 12, 4, 12));
        JPanel composeCenter = new JPanel(new BorderLayout(4, 0));
        composeCenter.setOpaque(false);
        composeCenter.add(micBtn, BorderLayout.WEST);
        composeCenter.add(new JScrollPane(composeBox), BorderLayout.CENTER);
        composeRow.add(composeCenter, BorderLayout.CENTER);
        composeRow.add(sendBtn, BorderLayout.EAST);

        JPanel syndicateRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 12, 2));
        syndicateRow.setBackground(Color.WHITE);
        syndicateRow.add(syndicateBox);

        JPanel bottom = new JPanel(new BorderLayout());
        bottom.setBackground(Color.WHITE);
        bottom.setBorder(BorderFactory.createMatteBorder(1, 0, 0, 0, BORDER_C));
        JPanel topInputArea = new JPanel(new BorderLayout(0, 4));
        topInputArea.setOpaque(false);
        topInputArea.add(statusLbl, BorderLayout.NORTH);
        topInputArea.add(replyBar,  BorderLayout.CENTER);
        topInputArea.add(attachRow, BorderLayout.SOUTH);
        bottom.add(topInputArea,  BorderLayout.NORTH);
        bottom.add(composeRow,    BorderLayout.CENTER);
        // syndicateRow moved into a wrapper below
        JPanel bottomWrap = new JPanel(new BorderLayout());
        bottomWrap.setBackground(Color.WHITE);
        bottomWrap.add(bottom,       BorderLayout.CENTER);
        bottomWrap.add(syndicateRow, BorderLayout.SOUTH);

        add(headerPanel, BorderLayout.NORTH);
        add(scroll,      BorderLayout.CENTER);
        add(buildSouthPanel(bottomWrap), BorderLayout.SOUTH);
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
        boolean canManageParticipants = user.isAdmin() || topic.userId == user.getUserId();
        if (participantsBtn != null) participantsBtn.setVisible(canManageParticipants);
        if (exportBtn    != null) exportBtn.setVisible(true);
        if (shareBtn     != null) shareBtn.setVisible(true);
        if (clearChatBtn != null) clearChatBtn.setVisible(true);
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

            // 🔒 Locked banner
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

            // 🚫 Removed-user banner (mirrors $isRemoved in topics.blade.php)
            if (isCurrentUserRemoved()) {
                JPanel banner = new JPanel(new BorderLayout());
                banner.setBackground(new Color(0xFF, 0xF5, 0xF5));
                banner.setBorder(new EmptyBorder(40, 20, 40, 20));
                JLabel lbl = new JLabel(
                    "<html><center><font size='5'>🚫</font><br><br>" +
                    "<b>You have been removed from this discussion.</b><br>" +
                    "<font color='#718096'>You cannot view or post messages until restored by the topic creator.</font>" +
                    "</center></html>", SwingConstants.CENTER);
                lbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                banner.add(lbl, BorderLayout.CENTER);
                postsPanel.add(banner);
                composeBox.setEnabled(false);
                sendBtn.setEnabled(false);
                postsPanel.revalidate();
                postsPanel.repaint();
                return;
            }

            // 💬 Topic origin bubble (mirrors .chat-row.topic-origin)
            if (currentTopic.body != null && !currentTopic.body.isEmpty()) {
                postsPanel.add(buildOriginBubble());
                postsPanel.add(Box.createVerticalStrut(4));
            }

            if (posts.isEmpty()) {
                JLabel empty = new JLabel("💬 No messages yet. Be the first to post!", SwingConstants.CENTER);
                empty.setFont(new Font("Segoe UI", Font.ITALIC, 13));
                empty.setForeground(new Color(0xA0, 0xAE, 0xC0));
                empty.setAlignmentX(CENTER_ALIGNMENT);
                postsPanel.add(Box.createVerticalGlue());
                postsPanel.add(empty);
            } else {
                // Chronological stream: posts + replies interleaved
                Long clearTs = clearTimestamps.get(currentTopic.id);
                for (Post p : posts) {
                    if (clearTs != null && p.id > 0 && p.id <= clearTs) continue;
                    postsPanel.add(buildPostCard(p));
                    postsPanel.add(Box.createVerticalStrut(2));
                    try {
                        for (com.smartforum.model.Reply r : syncManager.getReplies(p.id)) {
                            postsPanel.add(buildReplyCard(r, p));
                            postsPanel.add(Box.createVerticalStrut(2));
                        }
                    } catch (Exception ignored) {}
                }
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

        // ── Send attachment if one is pending ─────────────────────────────
        if (pendingAttachment != null) {
            sendAttachment(pendingAttachment, pendingAttachmentType);
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

        // If replying, POST to /posts/{id}/answer
        if (replyToPostId != -1) {
            final int pid = replyToPostId;
            final String body = text;
            sendBtn.setEnabled(false);
            new SwingWorker<Void, Void>() {
                @Override protected Void doInBackground() throws Exception {
                    syncManager.getApi().post("/posts/" + pid + "/answer",
                        java.util.Map.of("body", body));
                    return null;
                }
                @Override protected void done() {
                    sendBtn.setEnabled(true);
                    composeBox.setText("");
                    cancelReply();
                    refreshPosts();
                }
            }.execute();
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

    // ── Attachment helpers ────────────────────────────────────────────────

    private void pickAttachment(String type) {
        JFileChooser fc = new JFileChooser();
        if (type.equals("image")) {
            fc.setFileFilter(new javax.swing.filechooser.FileNameExtensionFilter(
                "Images", "jpg", "jpeg", "png", "gif", "webp"));
        }
        if (fc.showOpenDialog(this) == JFileChooser.APPROVE_OPTION) {
            pendingAttachment     = fc.getSelectedFile();
            pendingAttachmentType = type;
            attachPreviewLbl.setText("📎 " + pendingAttachment.getName());
        }
    }

    private void captureCamera() {
        try {
            com.github.sarxos.webcam.Webcam webcam = com.github.sarxos.webcam.Webcam.getDefault();
            if (webcam == null) throw new IllegalStateException("No webcam detected");
            if (!webcam.isOpen()) webcam.open();
            BufferedImage img = null;
            for (int i = 0; i < 6 && img == null; i++) {
                img = webcam.getImage();
                if (img == null) Thread.sleep(150);
            }
            webcam.close();
            if (img == null) throw new IllegalStateException("Camera did not return an image");
            File tmp = File.createTempFile("camera_", ".png");
            javax.imageio.ImageIO.write(img, "png", tmp);
            pendingAttachment     = tmp;
            pendingAttachmentType = "image";
            attachPreviewLbl.setText("📸 photo.png");
        } catch (Throwable ex) {
            // No webcam present, or the (optional, best-effort) native
            // driver behind webcam-capture couldn't load on this machine —
            // fall back to a screen capture so the button still works.
            try {
                Robot robot = new Robot();
                Rectangle screen = new Rectangle(Toolkit.getDefaultToolkit().getScreenSize());
                BufferedImage img = robot.createScreenCapture(screen);
                File tmp = File.createTempFile("camera_", ".png");
                javax.imageio.ImageIO.write(img, "png", tmp);
                pendingAttachment     = tmp;
                pendingAttachmentType = "image";
                attachPreviewLbl.setText("📸 screenshot.png (no camera found)");
            } catch (Exception ex2) {
                JOptionPane.showMessageDialog(this, "Camera capture failed: " + ex2.getMessage());
            }
        }
    }

    private void clearAttachment() {
        pendingAttachment     = null;
        pendingAttachmentType = null;
        attachPreviewLbl.setText("");
    }

    private void sendAttachment(File file, String type) {
        sendBtn.setEnabled(false);
        String caption = composeBox.getText().trim();
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                if (webSession == null || !webSession.isAuthenticated()) {
                    throw new IOException("Web session unavailable");
                }
                String mime = type.equals("image")
                    ? "image/" + guessImageExt(file)
                    : "application/octet-stream";
                Map<String, String> fields = new java.util.HashMap<>();
                if (!caption.isEmpty()) fields.put("body", caption);
                if (replyToPostId > 0)  fields.put("reply_to_id", String.valueOf(replyToPostId));
                Map<String, WebSessionClient.FilePart> files = new java.util.HashMap<>();
                files.put(type, new WebSessionClient.FilePart(file, mime));
                webSession.postMultipart("/topics/" + currentTopic.id + "/participate", fields, files);
                return null;
            }
            @Override protected void done() {
                sendBtn.setEnabled(true);
                try {
                    get();
                } catch (Exception ex) {
                    // Fallback: send as text post with filename so the
                    // message isn't silently lost when the web session
                    // couldn't be established.
                    String fallback = "[" + type + ": " + file.getName() + "]";
                    syncManager.sendOrQueue(currentTopic.id, user.getUserId(), fallback);
                }
                composeBox.setText("");
                clearAttachment();
                cancelReply();
                refreshPosts();
            }
        }.execute();
    }

    private String guessImageExt(File f) {
        String n = f.getName().toLowerCase();
        if (n.endsWith(".png"))  return "png";
        if (n.endsWith(".gif"))  return "gif";
        if (n.endsWith(".webp")) return "webp";
        return "jpeg";
    }

    // ── Voice recording ───────────────────────────────────────────────────

    private void toggleRecording(JButton micBtn) {
        if (!recording) {
            startRecording(micBtn);
        } else {
            stopRecording(micBtn);
        }
    }

    private void startRecording(JButton micBtn) {
        try {
            AudioFormat fmt = new AudioFormat(16000, 16, 1, true, false);
            DataLine.Info info = new DataLine.Info(TargetDataLine.class, fmt);
            if (!AudioSystem.isLineSupported(info)) {
                JOptionPane.showMessageDialog(this, "Microphone not available.");
                return;
            }
            micLine = (TargetDataLine) AudioSystem.getLine(info);
            micLine.open(fmt);
            micLine.start();
            recording = true;
            micBtn.setText("⏹");
            micBtn.setForeground(new Color(0xE5, 0x3E, 0x3E));
            pendingAudio = File.createTempFile("voice_", ".wav");
            File audioFile = pendingAudio;
            recordThread = new Thread(() -> {
                try (AudioInputStream ais = new AudioInputStream(micLine)) {
                    AudioSystem.write(ais, AudioFileFormat.Type.WAVE, audioFile);
                } catch (Exception ignored) {}
            }, "voice-recorder");
            recordThread.start();
            setStatus("🔴 Recording… click ⏹ to stop");
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Could not start recording: " + ex.getMessage());
        }
    }

    private void stopRecording(JButton micBtn) {
        recording = false;
        micBtn.setText("🎤");
        micBtn.setForeground(null);
        if (micLine != null) { micLine.stop(); micLine.close(); }
        setStatus(" ");
        if (pendingAudio != null && currentTopic != null) {
            File audio = pendingAudio;
            pendingAudio = null;
            new SwingWorker<Void, Void>() {
                @Override protected Void doInBackground() throws Exception {
                    if (webSession == null || !webSession.isAuthenticated()) {
                        throw new IOException("Web session unavailable");
                    }
                    Map<String, String> fields = new java.util.HashMap<>();
                    if (replyToPostId > 0) fields.put("reply_to_id", String.valueOf(replyToPostId));
                    Map<String, WebSessionClient.FilePart> files = new java.util.HashMap<>();
                    files.put("audio", new WebSessionClient.FilePart(audio, "audio/wav"));
                    webSession.postMultipart("/topics/" + currentTopic.id + "/participate", fields, files);
                    return null;
                }
                @Override protected void done() {
                    try {
                        get();
                    } catch (Exception ex) {
                        // Fallback: send as text marker so nothing is silently lost
                        syncManager.sendOrQueue(currentTopic.id, user.getUserId(), "[voice message]");
                    }
                    cancelReply();
                    refreshPosts();
                }
            }.execute();
        }
    }

    private JButton attachBtn(String icon, String tip) {
        JButton btn = new JButton(icon);
        btn.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 15));
        btn.setBackground(Color.WHITE);
        btn.setBorderPainted(false);
        btn.setFocusPainted(false);
        btn.setToolTipText(tip);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return btn;
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

        // Body — detect special attachment/voice prefixes
        JComponent bodyComp;
        String body = post.body;
        if (body != null && body.startsWith("[image:")) {
            // Image bubble: [image:url]
            String url = body.substring(7, body.length() - 1);
            JLabel imgLbl = new JLabel("🖼 " + url);
            imgLbl.setFont(new Font("Segoe UI", Font.ITALIC, 13));
            imgLbl.setForeground(PRIMARY);
            imgLbl.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            imgLbl.addMouseListener(new MouseAdapter() {
                @Override public void mouseClicked(MouseEvent e) {
                    try { Desktop.getDesktop().browse(new java.net.URI(url)); } catch (Exception ignored) {}
                }
            });
            // Try to load thumbnail
            new SwingWorker<ImageIcon, Void>() {
                @Override protected ImageIcon doInBackground() throws Exception {
                    Image img = new ImageIcon(new java.net.URL(url)).getImage()
                        .getScaledInstance(200, -1, Image.SCALE_SMOOTH);
                    return new ImageIcon(img);
                }
                @Override protected void done() {
                    try { imgLbl.setIcon(get()); imgLbl.setText(null); } catch (Exception ignored) {}
                }
            }.execute();
            bodyComp = imgLbl;
        } else if (body != null && body.startsWith("[file:")) {
            // File bubble: [file:url|name]
            String inner = body.substring(6, body.length() - 1);
            String[] parts = inner.split("\\|", 2);
            String fileUrl  = parts[0];
            String fileName = parts.length > 1 ? parts[1] : fileUrl;
            JButton dlBtn = new JButton("📎 " + fileName + "  ⬇ Download");
            dlBtn.setFont(new Font("Segoe UI", Font.PLAIN, 13));
            dlBtn.setForeground(PRIMARY);
            dlBtn.setBackground(new Color(0xEE, 0xF2, 0xFF));
            dlBtn.setBorderPainted(false);
            dlBtn.setFocusPainted(false);
            dlBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            dlBtn.addActionListener(e -> {
                try { Desktop.getDesktop().browse(new java.net.URI(fileUrl)); } catch (Exception ignored) {}
            });
            bodyComp = dlBtn;
        } else if (body != null && (body.equals("[voice]") || body.equals("[voice message]") || body.startsWith("[audio:"))) {
            // Audio bubble
            String audioUrl = body.startsWith("[audio:") ? body.substring(7, body.length() - 1) : null;
            JPanel audioBubble = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 0));
            audioBubble.setOpaque(false);
            JLabel waveLbl = new JLabel("🎤 ▶ Voice message");
            waveLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
            waveLbl.setForeground(new Color(0x38, 0xA1, 0x69));
            audioBubble.add(waveLbl);
            if (audioUrl != null) {
                JButton playBtn = new JButton("▶ Play");
                playBtn.setFont(new Font("Segoe UI", Font.BOLD, 11));
                playBtn.setForeground(Color.WHITE);
                playBtn.setBackground(new Color(0x38, 0xA1, 0x69));
                playBtn.setBorderPainted(false);
                playBtn.setFocusPainted(false);
                playBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
                playBtn.addActionListener(e -> {
                    try { Desktop.getDesktop().browse(new java.net.URI(audioUrl)); } catch (Exception ignored) {}
                });
                audioBubble.add(playBtn);
            }
            bodyComp = audioBubble;
        } else {
            JTextArea bodyArea = new JTextArea(body);
            bodyArea.setFont(new Font("Segoe UI", Font.PLAIN, 14));
            bodyArea.setLineWrap(true);
            bodyArea.setWrapStyleWord(true);
            bodyArea.setEditable(false);
            bodyArea.setOpaque(false);
            bodyArea.setBorder(new EmptyBorder(4, 0, 4, 0));
            bodyComp = bodyArea;
        }

        // Actions row — ↩ Reply always shown; ✏ Edit / 🗑 Delete for own/admin
        JPanel actions = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        actions.setOpaque(false);

        if (!post.syncPending) {
            JButton replyBtn = smallButton("↩ Reply", PRIMARY);
            replyBtn.addActionListener(e -> setReply(post.id,
                post.userId == user.getUserId() ? "You" : post.authorName,
                post.body != null ? post.body : "Attachment"));
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
        card.add(bodyComp,  BorderLayout.CENTER);
        card.add(actions,   BorderLayout.SOUTH);
        return card;
    }

    // ── Reply bar helpers ─────────────────────────────────────────────────

    private void setReply(int postId, String author, String body) {
        replyToPostId = postId;
        replyBarAuthor.setText("↩ " + author);
        replyBarBody.setText(body.length() > 60 ? body.substring(0, 60) + "…" : body);
        replyBar.setVisible(true);
        composeBox.requestFocusInWindow();
    }

    private void cancelReply() {
        replyToPostId = -1;
        replyToAuthor = null;
        replyToBody   = null;
        replyBar.setVisible(false);
    }

    // ── Clear chat (device-local, mirrors clearTopicChat in topics.blade.php) ──

    public void clearChat() {
        if (currentTopic == null) return;
        int ok = JOptionPane.showConfirmDialog(this,
            "Clear this chat on this device only?\nOther users will not be affected.",
            "Clear Chat", JOptionPane.YES_NO_OPTION);
        if (ok != JOptionPane.YES_OPTION) return;
        clearTimestamps.put(currentTopic.id, (long) Integer.MAX_VALUE);
        refreshPosts();
    }

    // ── Removed-user check ────────────────────────────────────────────────

    private boolean isCurrentUserRemoved() {
        if (currentTopic == null) return false;
        try {
            String json = syncManager.getApi().get("/topics/" + currentTopic.id + "/removed-status");
            return json.contains("\"removed\":true");
        } catch (Exception e) {
            return false;
        }
    }

    // ── Topic origin bubble (mirrors .chat-row.topic-origin) ──────────────

    private JPanel buildOriginBubble() {
        JPanel card = new JPanel(new BorderLayout(0, 4));
        card.setBackground(new Color(0xFE, 0xF9, 0xC3));
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(new Color(0xFC, 0xD3, 0x4D), 1),
            new EmptyBorder(10, 14, 10, 14)));
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));
        JLabel authorLbl = new JLabel(currentTopic.authorName);
        authorLbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        authorLbl.setForeground(new Color(0xD9, 0x77, 0x06));
        JTextArea bodyArea = new JTextArea(currentTopic.body);
        bodyArea.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        bodyArea.setLineWrap(true);
        bodyArea.setWrapStyleWord(true);
        bodyArea.setEditable(false);
        bodyArea.setOpaque(false);
        bodyArea.setBorder(new EmptyBorder(4, 0, 0, 0));
        card.add(authorLbl, BorderLayout.NORTH);
        card.add(bodyArea,  BorderLayout.CENTER);
        return card;
    }

    // ── Reply card (with quote preview, mirrors .reply-quote in blade) ────

    private JPanel buildReplyCard(com.smartforum.model.Reply reply, Post parentPost) {
        boolean isMe = reply.userId == user.getUserId();
        JPanel card = new JPanel(new BorderLayout(0, 4));
        card.setBackground(isMe ? new Color(0xD9, 0xFD, 0xD3) : Color.WHITE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, isMe ? 0 : 4, 1, isMe ? 4 : 0, new Color(0xC4, 0xB5, 0xFD)),
            new EmptyBorder(8, 14, 8, 14)));
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));
        JLabel authorLbl = new JLabel(isMe ? "You" : reply.authorName);
        authorLbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        authorLbl.setForeground(PRIMARY);
        // Quote panel
        JPanel quotePanel = new JPanel(new BorderLayout());
        quotePanel.setBackground(new Color(0xF1, 0xF0, 0xFF));
        quotePanel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 3, 0, 0, PRIMARY),
            new EmptyBorder(4, 8, 4, 8)));
        JLabel quoteAuthor = new JLabel(parentPost.authorName);
        quoteAuthor.setFont(new Font("Segoe UI", Font.BOLD, 11));
        quoteAuthor.setForeground(PRIMARY);
        String qb = parentPost.body != null ? parentPost.body : "Attachment";
        JLabel quoteBody = new JLabel(qb.length() > 70 ? qb.substring(0, 70) + "…" : qb);
        quoteBody.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        quoteBody.setForeground(new Color(0x4A, 0x55, 0x68));
        JPanel qt = new JPanel(); qt.setLayout(new BoxLayout(qt, BoxLayout.Y_AXIS)); qt.setOpaque(false);
        qt.add(quoteAuthor); qt.add(quoteBody);
        quotePanel.add(qt, BorderLayout.CENTER);
        JTextArea bodyArea = new JTextArea(reply.body);
        bodyArea.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        bodyArea.setLineWrap(true); bodyArea.setWrapStyleWord(true);
        bodyArea.setEditable(false); bodyArea.setOpaque(false);
        bodyArea.setBorder(new EmptyBorder(4, 0, 0, 0));
        JPanel top = new JPanel(new BorderLayout(0, 4)); top.setOpaque(false);
        top.add(authorLbl, BorderLayout.NORTH);
        top.add(quotePanel, BorderLayout.CENTER);
        card.add(top, BorderLayout.NORTH);
        card.add(bodyArea, BorderLayout.CENTER);
        return card;
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

    // ── Participants dialog — mirrors the "Participants" side panel in
    // topics.blade.php (block / unblock / remove). Laravel's API doesn't
    // expose a dedicated participants-list endpoint, so the roster here is
    // derived from post authors plus the topic creator — the same people
    // the website's panel would show, just sourced from posts instead of
    // the topic's participant pivot table. Restoring a removed user isn't
    // wired up: the website route for that (topics.unremoveUser) exists
    // only as a web/session route, not under /api, so the Java client has
    // no endpoint to call for it.
    private void showParticipantsDialog() {
        if (currentTopic == null) return;

        java.util.LinkedHashMap<Integer, String> people = new java.util.LinkedHashMap<>();
        people.put(currentTopic.userId, currentTopic.authorName);
        for (Post p : loadPosts(currentTopic.id)) people.putIfAbsent(p.userId, p.authorName);

        JDialog dialog = new JDialog((Frame) SwingUtilities.getWindowAncestor(this),
            "👥 Manage Participants — " + currentTopic.title, true);
        dialog.setSize(460, 420);
        dialog.setLocationRelativeTo(this);

        JPanel list = new JPanel();
        list.setLayout(new BoxLayout(list, BoxLayout.Y_AXIS));
        list.setBackground(Color.WHITE);
        list.setBorder(new EmptyBorder(12, 14, 12, 14));

        for (java.util.Map.Entry<Integer, String> entry : people.entrySet()) {
            int    pid  = entry.getKey();
            String name = entry.getValue();
            boolean isCreator = pid == currentTopic.userId;

            JPanel row = new JPanel(new BorderLayout(10, 0));
            row.setOpaque(false);
            row.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
                new EmptyBorder(8, 4, 8, 4)));
            row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 44));
            row.setAlignmentX(Component.LEFT_ALIGNMENT);

            JLabel nameLbl = new JLabel(name + (isCreator ? "  (Creator)" : ""));
            nameLbl.setFont(new Font("Segoe UI", isCreator ? Font.BOLD : Font.PLAIN, 13));
            row.add(nameLbl, BorderLayout.WEST);

            if (!isCreator) {
                JPanel actions = new JPanel(new FlowLayout(FlowLayout.RIGHT, 6, 0));
                actions.setOpaque(false);
                JButton blockBtn   = smallButton("🚫 Block",   new Color(0xD6, 0x9E, 0x2E));
                JButton unblockBtn = smallButton("✅ Unblock", new Color(0x38, 0xA1, 0x69));
                JButton removeBtn  = smallButton("🗑 Remove",  new Color(0xE5, 0x3E, 0x3E));
                blockBtn.addActionListener(e -> callParticipantAction(
                    "/topics/" + currentTopic.id + "/users/" + pid + "/block", true, dialog));
                unblockBtn.addActionListener(e -> callParticipantAction(
                    "/topics/" + currentTopic.id + "/users/" + pid + "/unblock", true, dialog));
                removeBtn.addActionListener(e -> {
                    int ok = JOptionPane.showConfirmDialog(dialog,
                        "Remove " + name + " from this topic?", "Confirm", JOptionPane.YES_NO_OPTION);
                    if (ok == JOptionPane.YES_OPTION) {
                        callParticipantAction(
                            "/topics/" + currentTopic.id + "/users/" + pid, false, dialog);
                    }
                });
                actions.add(blockBtn);
                actions.add(unblockBtn);
                actions.add(removeBtn);
                row.add(actions, BorderLayout.EAST);
            }
            list.add(row);
        }

        JScrollPane scroll = new JScrollPane(list);
        scroll.setBorder(null);
        scroll.getVerticalScrollBar().setUnitIncrement(16);

        JLabel note = new JLabel(
            "<html><body style='width:400px'>Roster is derived from this discussion's posts "
            + "(no dedicated participants API is exposed), so someone who joined but hasn't "
            + "posted yet won't be listed here.</body></html>");
        note.setFont(new Font("Segoe UI", Font.ITALIC, 11));
        note.setForeground(Theme.MUTED);
        note.setBorder(new EmptyBorder(0, 14, 10, 14));

        JPanel content = new JPanel(new BorderLayout());
        content.setBackground(Color.WHITE);
        content.add(scroll, BorderLayout.CENTER);
        content.add(note,   BorderLayout.SOUTH);

        dialog.setContentPane(content);
        dialog.setVisible(true);
    }

    /** Fires a block/unblock/remove call and refreshes the dialog + post list on success. */
    private void callParticipantAction(String endpoint, boolean isPost, JDialog dialogToRefresh) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                if (isPost) syncManager.getApi().post(endpoint, java.util.Map.of());
                else        syncManager.getApi().delete(endpoint);
                return null;
            }
            @Override protected void done() {
                try {
                    get();
                    dialogToRefresh.dispose();
                    refreshPosts();
                    showParticipantsDialog();
                } catch (Exception ex) {
                    JOptionPane.showMessageDialog(dialogToRefresh,
                        "Action failed: " + ex.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
                }
            }
        }.execute();
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
