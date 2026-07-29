package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.api.WebSessionClient;
import com.smartforum.model.AuthUser;
import com.smartforum.model.Conversation;
import com.smartforum.model.PrivateMessage;

import javax.sound.sampled.*;
import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.*;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.concurrent.ExecutionException;

/**
 * Direct Messages tab — mirrors messages.blade.php (the "Messages" link in
 * the Laravel sidebar): a conversation list on the left (whose search box
 * doubles as "start a new conversation"), and a 1:1 thread on the right
 * with text, image, file and voice messages, replies, edit/delete, and a
 * local clear-chat — the same feature set as the Topics view, scoped to a
 * private conversation instead of a group discussion.
 *
 * Laravel only exposes this feature as session-authenticated "web" routes
 * (routes/web.php's private-messaging group) — there is no Sanctum-token
 * /api equivalent — and two of those routes (index/show) render full HTML
 * pages rather than JSON. So this panel reaches the server two ways:
 *   - /messages/{id}/poll returns real JSON — used for the thread itself.
 *   - /messages and /messages?search=... only return rendered HTML — this
 *     panel parses the small, fixed list-item markup out of that HTML to
 *     build the conversation list / search results.
 * Both go through {@link WebSessionClient}'s cookie+CSRF session rather
 * than the token-based ApiClient, since that is what those routes accept.
 */
public class MessagesPanel extends JPanel {

    private static final Color PRIMARY   = Theme.PRIMARY;
    private static final Color SECONDARY = Theme.SECONDARY;
    private static final Color BORDER_C  = Theme.BORDER;
    private static final Color BG_BODY   = new Color(0xF0, 0xF2, 0xF5);

    private final AuthUser         user;
    private final WebSessionClient webSession;
    private final ObjectMapper     mapper = new ObjectMapper();
    private final String           storageBase;

    // ── Conversation list ───────────────────────────────────────────────
    private final DefaultListModel<Conversation> convModel = new DefaultListModel<>();
    private final JList<Conversation>            convList  = new JList<>(convModel);
    private final JTextField                     searchField = new JTextField();
    private Timer                                 searchDebounce;

    // ── Thread state ─────────────────────────────────────────────────────
    private int    currentOtherId   = -1;
    private String currentOtherName = "";
    private String currentOtherRole = "";
    private String lastFetchIso     = "1970-01-01T00:00:00.000Z";
    private final Set<Integer> shownIds = new LinkedHashSet<>();

    private final JLabel    threadHeader  = new JLabel("  💬 Select a conversation");
    private final JLabel    threadMeta    = new JLabel(" ");
    private final JLabel    threadAvatar  = new JLabel("", SwingConstants.CENTER) {
        @Override protected void paintComponent(Graphics g) {
            if (getText().isEmpty()) return;
            Graphics2D g2 = (Graphics2D) g.create();
            g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
            g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), SECONDARY));
            g2.fillOval(0, 0, getWidth(), getHeight());
            g2.dispose();
            super.paintComponent(g);
        }
    };
    private final JPanel    messagesPanel = new JPanel();
    private final JTextArea composeBox    = new JTextArea(3, 40);
    private final JButton   sendBtn       = new JButton("Send");
    private final JLabel    statusLbl     = new JLabel(" ");
    private JButton         clearChatBtn;

    // ── Reply bar (mirrors the reply-bar in messages.blade.php) ────────────
    private int    replyToId = -1;
    private JPanel replyBar;
    private JLabel replyBarAuthor;
    private JLabel replyBarBody;

    // ── Clear-chat watermark (device-local, per conversation) ──────────────
    private final Map<Integer, String> clearTimestamps = new java.util.HashMap<>();

    // ── Voice recording ─────────────────────────────────────────────────
    private TargetDataLine micLine;
    private Thread         recordThread;
    private File           pendingAudio;
    private boolean        recording  = false;
    private Timer          recTimer;
    private int            recSeconds = 0;
    private JLabel         recTimerLbl;
    private JPanel         audioPreviewBar;

    // ── Pending attachment (image or file chosen before send) ─────────────
    private File   pendingAttachment;
    private String pendingAttachmentType; // "image" | "file"
    private JLabel attachPreviewLbl;

    private final Timer pollTimer;

    public MessagesPanel(AuthUser user, WebSessionClient webSession) {
        this.user        = user;
        this.webSession   = webSession;
        this.storageBase  = ApiClient.BASE_URL.replace("/api", "") + "/storage/";
        buildUI();
        refreshConversations();
        pollTimer = new Timer(3000, e -> { if (currentOtherId > 0) pollThread(); });
        pollTimer.start();
    }

    // ── UI construction ───────────────────────────────────────────────────

    private void buildUI() {
        setLayout(new BorderLayout(0, 0));
        setBackground(BG_BODY);

        JSplitPane split = new JSplitPane(
            JSplitPane.HORIZONTAL_SPLIT, buildSidebar(), buildThreadArea());
        split.setDividerLocation(280);
        split.setDividerSize(4);
        split.setBorder(null);
        add(split, BorderLayout.CENTER);
        showNoSelectionState();
    }

    private void showNoSelectionState() {
        messagesPanel.removeAll();
        JLabel lbl = new JLabel(
            "<html><body style='width:260px;text-align:center'>✉️<br><br>Select a conversation on the left,<br>or search for someone to start a private chat.</body></html>",
            SwingConstants.CENTER);
        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        lbl.setForeground(Theme.MUTED);
        lbl.setAlignmentX(Component.CENTER_ALIGNMENT);
        lbl.setBorder(new EmptyBorder(60, 20, 0, 20));
        messagesPanel.add(lbl);
        messagesPanel.revalidate();
        messagesPanel.repaint();
    }

    private JComponent buildSidebar() {
        JPanel sidebar = new JPanel(new BorderLayout(0, 0));
        sidebar.setBackground(Color.WHITE);
        sidebar.setPreferredSize(new Dimension(280, 0));
        sidebar.setMinimumSize(new Dimension(220, 0));

        JLabel title = new JLabel("💬 Direct Messages");
        title.setFont(new Font("Segoe UI", Font.BOLD, 16));
        title.setForeground(new Color(0x2D, 0x37, 0x48));
        title.setAlignmentX(Component.LEFT_ALIGNMENT);
        title.setBorder(new EmptyBorder(0, 0, 10, 0));

        searchField.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        searchField.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 1),
            new EmptyBorder(6, 8, 6, 8)));
        searchField.setToolTipText("Search people to message…");
        searchField.setAlignmentX(Component.LEFT_ALIGNMENT);
        searchField.setMaximumSize(new Dimension(Integer.MAX_VALUE, 30));
        searchField.getDocument().addDocumentListener(new javax.swing.event.DocumentListener() {
            @Override public void insertUpdate(javax.swing.event.DocumentEvent e) { onSearchChanged(); }
            @Override public void removeUpdate(javax.swing.event.DocumentEvent e) { onSearchChanged(); }
            @Override public void changedUpdate(javax.swing.event.DocumentEvent e) { onSearchChanged(); }
        });

        JPanel top = new JPanel();
        top.setLayout(new BoxLayout(top, BoxLayout.Y_AXIS));
        top.setBackground(Color.WHITE);
        top.setBorder(new EmptyBorder(14, 14, 10, 14));
        top.add(title);
        top.add(searchField);

        convList.setCellRenderer(new ConversationCellRenderer());
        convList.setBackground(Color.WHITE);
        convList.setSelectionBackground(new Color(0xEE, 0xF2, 0xFF));
        convList.setFixedCellHeight(58);
        convList.setBorder(new EmptyBorder(4, 4, 4, 4));
        convList.addListSelectionListener(e -> {
            if (e.getValueIsAdjusting()) return;
            Conversation c = convList.getSelectedValue();
            if (c != null) openThread(c.userId, c.name, c.isSearchResult ? c.roleOrPreview : "");
        });

        JScrollPane scroll = new JScrollPane(convList);
        scroll.setBorder(null);
        scroll.getVerticalScrollBar().setUnitIncrement(16);

        sidebar.add(top,    BorderLayout.NORTH);
        sidebar.add(scroll, BorderLayout.CENTER);
        return sidebar;
    }

    private JComponent buildThreadArea() {
        JPanel panel = new JPanel(new BorderLayout(0, 0));
        panel.setBackground(BG_BODY);

        // ── Header ───────────────────────────────────────────────────────
        threadHeader.setFont(new Font("Segoe UI", Font.BOLD, 17));
        threadHeader.setForeground(new Color(0x2D, 0x37, 0x48));
        threadHeader.setBorder(new EmptyBorder(0, 0, 2, 0));
        threadMeta.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        threadMeta.setForeground(new Color(0x71, 0x80, 0x96));

        threadAvatar.setFont(new Font("Segoe UI", Font.BOLD, 15));
        threadAvatar.setForeground(Color.WHITE);
        threadAvatar.setOpaque(false);
        threadAvatar.setPreferredSize(new Dimension(38, 38));
        threadAvatar.setMinimumSize(new Dimension(38, 38));
        threadAvatar.setMaximumSize(new Dimension(38, 38));
        threadAvatar.setHorizontalAlignment(SwingConstants.CENTER);

        JPanel titleBlock = new JPanel();
        titleBlock.setLayout(new BoxLayout(titleBlock, BoxLayout.Y_AXIS));
        titleBlock.setOpaque(false);
        titleBlock.add(threadHeader);
        titleBlock.add(threadMeta);

        JPanel headerLeft = new JPanel(new BorderLayout(10, 0));
        headerLeft.setOpaque(false);
        headerLeft.add(threadAvatar, BorderLayout.WEST);
        headerLeft.add(titleBlock,   BorderLayout.CENTER);

        clearChatBtn = new JButton("🧹 Clear Chat");
        clearChatBtn.setFont(new Font("Segoe UI", Font.BOLD, 11));
        clearChatBtn.setForeground(Color.WHITE);
        clearChatBtn.setBackground(new Color(0x94, 0xA3, 0xB8));
        clearChatBtn.setBorderPainted(false);
        clearChatBtn.setFocusPainted(false);
        clearChatBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        clearChatBtn.setVisible(false);
        clearChatBtn.addActionListener(e -> clearChat());

        JPanel headerPanel = new JPanel(new BorderLayout(10, 0));
        headerPanel.setBackground(Color.WHITE);
        headerPanel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
            new EmptyBorder(12, 16, 12, 16)));
        headerPanel.add(headerLeft,   BorderLayout.CENTER);
        headerPanel.add(clearChatBtn, BorderLayout.EAST);

        // ── Message list ─────────────────────────────────────────────────
        messagesPanel.setLayout(new BoxLayout(messagesPanel, BoxLayout.Y_AXIS));
        messagesPanel.setBackground(BG_BODY);
        messagesPanel.setBorder(new EmptyBorder(12, 0, 12, 0));

        JScrollPane scroll = new JScrollPane(messagesPanel);
        scroll.setBorder(null);
        scroll.getVerticalScrollBar().setUnitIncrement(16);

        // ── Compose area ─────────────────────────────────────────────────
        composeBox.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        composeBox.setLineWrap(true);
        composeBox.setWrapStyleWord(true);
        composeBox.setBorder(new EmptyBorder(8, 10, 8, 10));
        composeBox.addKeyListener(new KeyAdapter() {
            @Override public void keyPressed(KeyEvent e) {
                if (e.getKeyCode() == KeyEvent.VK_ENTER && !e.isShiftDown()) {
                    e.consume();
                    handleSend();
                }
            }
        });

        sendBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        sendBtn.setBackground(PRIMARY);
        sendBtn.setForeground(Color.WHITE);
        sendBtn.setBorderPainted(false);
        sendBtn.setFocusPainted(false);
        sendBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        sendBtn.addActionListener(e -> handleSend());

        statusLbl.setFont(new Font("Segoe UI", Font.ITALIC, 12));
        statusLbl.setForeground(new Color(0x85, 0x64, 0x04));
        statusLbl.setBorder(new EmptyBorder(4, 12, 0, 12));

        // Reply bar
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

        // Attachment toolbar (image | file | camera)
        JButton imgBtn  = attachBtn("🖼", "Image");
        JButton fileBtn = attachBtn("📎", "File");
        JButton camBtn  = attachBtn("📸", "Camera");
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

        JButton micBtn = new JButton("🎤");
        micBtn.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 16));
        micBtn.setBackground(Color.WHITE);
        micBtn.setBorderPainted(false);
        micBtn.setFocusPainted(false);
        micBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        micBtn.setToolTipText("Record a voice message");
        micBtn.addActionListener(e -> toggleRecording(micBtn));

        // ── Audio preview bar (mirrors .audio-preview in messages.blade.php) ──
        recTimerLbl = new JLabel("0:00");
        recTimerLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        recTimerLbl.setForeground(new Color(0xEF, 0x44, 0x44));
        JButton discardBtn = new JButton("✕");
        discardBtn.setFont(new Font("Segoe UI", Font.BOLD, 11));
        discardBtn.setForeground(new Color(0xDC, 0x26, 0x26));
        discardBtn.setBackground(new Color(0xFE, 0xE2, 0xE2));
        discardBtn.setBorderPainted(false);
        discardBtn.setFocusPainted(false);
        discardBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        discardBtn.setToolTipText("Discard recording");
        discardBtn.addActionListener(e -> discardRecording(micBtn));
        int[] recBarHeights = {10,16,22,28,20,14,24,18,12,26,20,16,22,10,18,24,14,20,28,16};
        JPanel recWavePanel = new JPanel(new FlowLayout(FlowLayout.LEFT, 2, 0));
        recWavePanel.setOpaque(false);
        for (int bh : recBarHeights) {
            final int barH = bh;
            JPanel bar = new JPanel() {
                @Override public Dimension getPreferredSize() { return new Dimension(3, barH); }
                @Override protected void paintComponent(Graphics g) {
                    Graphics2D g2 = (Graphics2D) g.create();
                    g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                    g2.setColor(new Color(0xC7, 0xD2, 0xFE));
                    g2.fillRoundRect(0, 0, getWidth(), getHeight(), 3, 3);
                    g2.dispose();
                }
            };
            bar.setOpaque(false);
            recWavePanel.add(bar);
        }
        JButton sendAudioBtn = new JButton("▶");
        sendAudioBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        sendAudioBtn.setForeground(Color.WHITE);
        sendAudioBtn.setBackground(new Color(0x00, 0xA8, 0x84));
        sendAudioBtn.setBorderPainted(false);
        sendAudioBtn.setFocusPainted(false);
        sendAudioBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        sendAudioBtn.setToolTipText("Send voice message");
        sendAudioBtn.addActionListener(e -> stopRecording(micBtn));
        audioPreviewBar = new JPanel(new BorderLayout(8, 0));
        audioPreviewBar.setBackground(Color.WHITE);
        audioPreviewBar.setBorder(new EmptyBorder(6, 12, 6, 12));
        audioPreviewBar.add(discardBtn,   BorderLayout.WEST);
        audioPreviewBar.add(recTimerLbl,  BorderLayout.EAST);
        audioPreviewBar.add(recWavePanel, BorderLayout.CENTER);
        audioPreviewBar.add(sendAudioBtn, BorderLayout.EAST);
        // Re-layout: discard | timer | wave | send
        audioPreviewBar.removeAll();
        JPanel recLeft = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        recLeft.setOpaque(false);
        recLeft.add(discardBtn);
        recLeft.add(recTimerLbl);
        audioPreviewBar.add(recLeft,      BorderLayout.WEST);
        audioPreviewBar.add(recWavePanel, BorderLayout.CENTER);
        audioPreviewBar.add(sendAudioBtn, BorderLayout.EAST);
        audioPreviewBar.setVisible(false);

        JPanel composeRow = new JPanel(new BorderLayout(8, 0));
        composeRow.setBackground(Color.WHITE);
        composeRow.setBorder(new EmptyBorder(4, 12, 4, 12));
        JPanel composeCenter = new JPanel(new BorderLayout(4, 0));
        composeCenter.setOpaque(false);
        composeCenter.add(micBtn, BorderLayout.WEST);
        composeCenter.add(new JScrollPane(composeBox), BorderLayout.CENTER);
        composeRow.add(composeCenter, BorderLayout.CENTER);
        composeRow.add(sendBtn, BorderLayout.EAST);

        JPanel bottom = new JPanel(new BorderLayout());
        bottom.setBackground(Color.WHITE);
        bottom.setBorder(BorderFactory.createMatteBorder(1, 0, 0, 0, BORDER_C));
        JPanel topInputArea = new JPanel(new BorderLayout(0, 4));
        topInputArea.setOpaque(false);
        topInputArea.add(statusLbl,       BorderLayout.NORTH);
        topInputArea.add(replyBar,        BorderLayout.CENTER);
        topInputArea.add(attachRow,       BorderLayout.SOUTH);
        bottom.add(topInputArea,    BorderLayout.NORTH);
        bottom.add(audioPreviewBar, BorderLayout.CENTER);
        bottom.add(composeRow,      BorderLayout.SOUTH);

        panel.add(headerPanel, BorderLayout.NORTH);
        panel.add(scroll,      BorderLayout.CENTER);
        panel.add(bottom,      BorderLayout.SOUTH);
        return panel;
    }

    private JButton attachBtn(String icon, String tip) {
        JButton btn = new JButton(icon);
        btn.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 16));
        btn.setBackground(Color.WHITE);
        btn.setBorderPainted(false);
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        btn.setToolTipText(tip);
        return btn;
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

    // ── Conversation list ───────────────────────────────────────────────

    private void onSearchChanged() {
        if (searchDebounce != null) searchDebounce.stop();
        searchDebounce = new Timer(300, e -> refreshConversations());
        searchDebounce.setRepeats(false);
        searchDebounce.start();
    }

    private void refreshConversations() {
        String q = searchField.getText().trim();
        new SwingWorker<List<Conversation>, Void>() {
            @Override protected List<Conversation> doInBackground() throws Exception {
                if (webSession == null || !webSession.isAuthenticated()) {
                    throw new IOException("Direct messages need a browser-style session — try logging out and back in.");
                }
                String path = q.isEmpty() ? "/messages"
                    : "/messages?search=" + java.net.URLEncoder.encode(q, "UTF-8");
                String html = webSession.get(path);
                return parseConversationList(html, !q.isEmpty());
            }
            @Override protected void done() {
                try {
                    List<Conversation> list = get();
                    Conversation selected = convList.getSelectedValue();
                    convModel.clear();
                    for (Conversation c : list) convModel.addElement(c);
                    setStatus(list.isEmpty()
                        ? (q.isEmpty() ? "No conversations yet — search for someone to start one." : "No matching people found.")
                        : " ");
                } catch (Exception ex) {
                    setStatus("⚠ " + rootMessage(ex));
                }
            }
        }.execute();
    }

    /**
     * Parses the fixed {@code .topic-item} row markup shared by the
     * conversation list and "start new conversation" search results in
     * messages.blade.php — there is no JSON endpoint for either, since
     * MessageController::index() renders a full HTML page.
     */
    private List<Conversation> parseConversationList(String html, boolean isSearch) {
        List<Conversation> result = new ArrayList<>();

        // The blade renders both the search-results block (@if filled('search'))
        // and the conversations block (@else) in the same page. Slice to the
        // correct one using the unique sentinel text each block contains.
        String section;
        if (isSearch) {
            // Search block starts after the results-count div and ends at the
            // @else block opener, which always contains the conversations list.
            // The @else block is identified by the "topics-count" span that
            // shows "N conversation(s)" — but that word is too common.
            // Instead, split on the literal @else boundary: the blade emits
            // the conversations list inside a second <div class="topic-list">
            // that follows the search block's own <div class="topic-list">.
            int firstList  = html.indexOf("class=\"topic-list\"");
            int secondList = firstList >= 0 ? html.indexOf("class=\"topic-list\"", firstList + 1) : -1;
            int start = firstList >= 0 ? firstList : 0;
            int end   = secondList >= 0 ? secondList : html.length();
            section = html.substring(start, end);
        } else {
            // Conversations block is the second <div class="topic-list"> in the page.
            int firstList  = html.indexOf("class=\"topic-list\"");
            int secondList = firstList >= 0 ? html.indexOf("class=\"topic-list\"", firstList + 1) : -1;
            int start = secondList >= 0 ? secondList : (firstList >= 0 ? firstList : 0);
            section = html.substring(start);
        }

        java.util.regex.Pattern rowStart = java.util.regex.Pattern.compile(
            "onclick=\"window\\.location='[^']*/messages/(\\d+)'\"");
        java.util.regex.Matcher m = rowStart.matcher(section);
        List<int[]> starts = new ArrayList<>();
        while (m.find()) {
            starts.add(new int[]{m.start(), Integer.parseInt(m.group(1))});
        }
        for (int i = 0; i < starts.size(); i++) {
            int begin = starts.get(i)[0];
            int end   = (i + 1 < starts.size()) ? starts.get(i + 1)[0] : Math.min(section.length(), begin + 2000);
            String chunk  = section.substring(begin, end);
            int    userId = starts.get(i)[1];
            String name   = unescapeHtml(extractTag(chunk, "<h4>", "</h4>"));
            String sub    = unescapeHtml(extractTag(chunk, "class=\"topic-author\">", "</div>"));
            String time   = unescapeHtml(extractTag(chunk, "class=\"conv-time\">", "</span>"));
            String unreadStr = extractTag(chunk, "class=\"unread-pill\">", "</span>");
            int unread = 0;
            if (unreadStr != null) {
                try { unread = Integer.parseInt(unreadStr.trim()); } catch (Exception ignored) {}
            }
            result.add(new Conversation(userId, name != null ? name : ("User #" + userId),
                sub != null ? sub : "", time != null ? time : "", unread, isSearch));
        }
        return result;
    }

    private String extractTag(String chunk, String startMarker, String endMarker) {
        int s = chunk.indexOf(startMarker);
        if (s < 0) return null;
        s += startMarker.length();
        int e = chunk.indexOf(endMarker, s);
        if (e < 0) return null;
        return chunk.substring(s, e).trim();
    }

    private String unescapeHtml(String s) {
        if (s == null) return null;
        return s.replace("&amp;", "&").replace("&lt;", "<").replace("&gt;", ">")
                .replace("&quot;", "\"").replace("&#039;", "'").replace("&apos;", "'").trim();
    }

    // ── Thread ────────────────────────────────────────────────────────────

    private void openThread(int otherId, String otherName, String otherRole) {
        stopActiveAudio();
        currentOtherId   = otherId;
        currentOtherName = otherName;
        currentOtherRole = otherRole;
        lastFetchIso      = clearTimestamps.getOrDefault(otherId, "1970-01-01T00:00:00.000Z");
        shownIds.clear();
        messagesPanel.removeAll();
        JLabel sayHi = new JLabel(
            "<html><body style='width:260px;text-align:center'>\uD83D\uDC4B<br><br>Say hi to "
            + escapeHtml(otherName) + " \u2014 this is the start of your private conversation.</body></html>",
            SwingConstants.CENTER);
        sayHi.setName("sayHi");
        sayHi.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        sayHi.setForeground(Theme.MUTED);
        sayHi.setAlignmentX(Component.CENTER_ALIGNMENT);
        sayHi.setBorder(new EmptyBorder(60, 20, 0, 20));
        messagesPanel.add(sayHi);
        messagesPanel.revalidate();
        messagesPanel.repaint();
        threadHeader.setText("  " + otherName);
        String meta = otherRole.isEmpty() ? "Private Message" : otherRole + " · Private Message";
        threadMeta.setText(" " + meta);
        threadAvatar.setText(otherName.isEmpty() ? "?" : String.valueOf(otherName.charAt(0)).toUpperCase());
        clearChatBtn.setVisible(true);
        cancelReply();
        pollThread();
    }

    private void pollThread() {
        if (currentOtherId <= 0) return;
        final int otherId = currentOtherId;
        final String since = lastFetchIso;
        new SwingWorker<List<PrivateMessage>, Void>() {
            String fetchedAt = since;
            @Override protected List<PrivateMessage> doInBackground() throws Exception {
                if (webSession == null || !webSession.isAuthenticated()) {
                    throw new IOException("Web session unavailable");
                }
                String json = webSession.get("/messages/" + otherId + "/poll?since=" +
                    java.net.URLEncoder.encode(since, "UTF-8"));
                JsonNode root = mapper.readTree(json);
                if (root.hasNonNull("fetched_at")) fetchedAt = root.path("fetched_at").asText(since);
                return parsePollMessages(root);
            }
            @Override protected void done() {
                if (otherId != currentOtherId) return; // switched conversations meanwhile
                try {
                    List<PrivateMessage> msgs = get();
                    boolean any = false;
                    for (PrivateMessage msg : msgs) {
                        if (shownIds.add(msg.id)) {
                            // Remove the "Say hi" placeholder on first real message
                            for (int i = messagesPanel.getComponentCount() - 1; i >= 0; i--) {
                                java.awt.Component c = messagesPanel.getComponent(i);
                                if ("sayHi".equals(c.getName())) { messagesPanel.remove(i); break; }
                            }
                            messagesPanel.add(buildMessageCard(msg));
                            any = true;
                        }
                    }
                    if (any) {
                        messagesPanel.revalidate();
                        messagesPanel.repaint();
                        scrollToBottom();
                    }
                    lastFetchIso = fetchedAt;
                } catch (Exception ex) {
                    setStatus("⚠ " + rootMessage(ex));
                }
            }
        }.execute();
    }

    private List<PrivateMessage> parsePollMessages(JsonNode root) {
        List<PrivateMessage> result = new ArrayList<>();
        JsonNode arr = root.path("messages");
        if (arr.isArray()) {
            for (JsonNode n : arr) {
                JsonNode rt = n.path("reply_to");
                Integer replyToId     = null;
                int     replyToSender = 0;
                String  replyToBody   = null;
                if (!rt.isMissingNode() && !rt.isNull()) {
                    replyToId     = rt.path("id").asInt();
                    replyToSender = rt.path("sender_id").asInt();
                    replyToBody   = rt.hasNonNull("body") ? rt.path("body").asText(null) : null;
                }
                result.add(new PrivateMessage(
                    n.path("id").asInt(),
                    n.path("sender_id").asInt(),
                    n.hasNonNull("body") ? n.path("body").asText(null) : null,
                    n.path("deleted").asBoolean(false),
                    n.hasNonNull("image_path") ? n.path("image_path").asText(null) : null,
                    n.hasNonNull("audio_path") ? n.path("audio_path").asText(null) : null,
                    n.hasNonNull("file_path")  ? n.path("file_path").asText(null)  : null,
                    n.hasNonNull("file_name")  ? n.path("file_name").asText(null)  : null,
                    n.path("file_size").asLong(0),
                    replyToId, replyToSender, replyToBody,
                    n.path("created_at").asText(null)));
            }
        }
        return result;
    }

    // ── WhatsApp-style bubble colours (mirrors messages.blade.php) ──────
    private static final Color BUBBLE_MINE   = new Color(0xD9, 0xFD, 0xD3); // #d9fdd3
    private static final Color BUBBLE_THEIRS = Color.WHITE;
    private static final Color BUBBLE_AUDIO  = new Color(0xF0, 0xF2, 0xF5);
    private static final Color FILE_BG_MINE  = new Color(0xD9, 0xFD, 0xD3);
    private static final Color FILE_BG       = Color.WHITE;

    private JPanel buildMessageCard(PrivateMessage msg) {
        boolean mine = msg.senderId == user.getUserId();

        // Outer row — aligns bubble left (theirs) or right (mine)
        JPanel row = new JPanel();
        row.setLayout(new BoxLayout(row, BoxLayout.X_AXIS));
        row.setOpaque(false);
        row.setAlignmentX(Component.LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));
        row.setBorder(new EmptyBorder(3, 10, 3, 10));

        JPanel bubble = buildBubble(msg, mine);

        if (mine) {
            row.add(Box.createHorizontalGlue());
            row.add(bubble);
        } else {
            row.add(bubble);
            row.add(Box.createHorizontalGlue());
        }
        return row;
    }

    private JPanel buildBubble(PrivateMessage msg, boolean mine) {
        Color bgColor = mine ? BUBBLE_MINE : BUBBLE_THEIRS;
        int arc = 14;

        JPanel bubble = new JPanel(new BorderLayout(0, 4)) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(bgColor);
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), arc, arc);
                g2.dispose();
            }
        };
        bubble.setOpaque(false);
        bubble.setBorder(new EmptyBorder(8, 12, 6, 12));
        bubble.setMaximumSize(new Dimension(380, Integer.MAX_VALUE));

        JPanel bodyBox = new JPanel();
        bodyBox.setLayout(new BoxLayout(bodyBox, BoxLayout.Y_AXIS));
        bodyBox.setOpaque(false);

        // Reply quote
        if (msg.replyToId != null) {
            String rpName = msg.replyToSenderId == user.getUserId() ? "You" : currentOtherName;
            String rpBody = msg.replyToBody != null ? truncate(msg.replyToBody, 60) : "📎 Attachment";
            JPanel quote = new JPanel(new BorderLayout());
            quote.setOpaque(true);
            quote.setBackground(mine ? new Color(0xBB, 0xF7, 0xD0) : new Color(0xF1, 0xF0, 0xFF));
            quote.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 3, 0, 0, PRIMARY),
                new EmptyBorder(3, 8, 3, 8)));
            JLabel rpAuthorLbl = new JLabel(rpName);
            rpAuthorLbl.setFont(new Font("Segoe UI", Font.BOLD, 11));
            rpAuthorLbl.setForeground(PRIMARY);
            JLabel rpBodyLbl = new JLabel(rpBody);
            rpBodyLbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
            rpBodyLbl.setForeground(new Color(0x4A, 0x55, 0x68));
            JPanel qt = new JPanel(); qt.setLayout(new BoxLayout(qt, BoxLayout.Y_AXIS)); qt.setOpaque(false);
            qt.add(rpAuthorLbl); qt.add(rpBodyLbl);
            quote.add(qt, BorderLayout.CENTER);
            bodyBox.add(quote);
            bodyBox.add(Box.createVerticalStrut(4));
        }

        // Content
        if (msg.deleted) {
            JLabel del = new JLabel("🚫 This message was deleted");
            del.setFont(new Font("Segoe UI", Font.ITALIC, 13));
            del.setForeground(new Color(0x94, 0xA3, 0xB8));
            bodyBox.add(del);
        } else if (msg.imagePath != null) {
            String url = resolveUrl(msg.imagePath);
            JLabel imgLbl = new JLabel("🖼 Loading…");
            imgLbl.setFont(new Font("Segoe UI", Font.ITALIC, 13));
            imgLbl.setForeground(PRIMARY);
            imgLbl.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            imgLbl.setMaximumSize(new Dimension(260, 200));
            imgLbl.addMouseListener(new MouseAdapter() {
                @Override public void mouseClicked(MouseEvent e) {
                    try { Desktop.getDesktop().browse(new java.net.URI(url)); } catch (Exception ignored) {}
                }
            });
            new SwingWorker<ImageIcon, Void>() {
                @Override protected ImageIcon doInBackground() throws Exception {
                    Image img = new ImageIcon(new java.net.URL(url)).getImage()
                        .getScaledInstance(240, -1, Image.SCALE_SMOOTH);
                    return new ImageIcon(img);
                }
                @Override protected void done() {
                    try { imgLbl.setIcon(get()); imgLbl.setText(null);
                        imgLbl.getParent().revalidate(); } catch (Exception ignored) {}
                }
            }.execute();
            bodyBox.add(imgLbl);
            if (msg.body != null && !msg.body.isEmpty()) {
                JLabel caption = new JLabel("<html><body style='width:220px'>" + escapeHtml(msg.body) + "</body></html>");
                caption.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                bodyBox.add(caption);
            }
        } else if (msg.audioPath != null) {
            String url = resolveUrl(msg.audioPath);
            // Play button — mirrors .audio-play-btn
            JButton playBtn = new JButton("\u25B6");
            playBtn.setFont(new Font("Segoe UI", Font.BOLD, 14));
            playBtn.setForeground(Color.WHITE);
            playBtn.setBackground(new Color(0x66, 0x7E, 0xEA));
            playBtn.setBorderPainted(false);
            playBtn.setFocusPainted(false);
            playBtn.setPreferredSize(new Dimension(38, 38));
            playBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            playBtn.addActionListener(e -> playAudio(url, playBtn));
            // Waveform bars — mirrors .audio-waveform 20 bars
            int[] barHeights = {8,14,20,28,22,16,26,18,10,24,20,14,22,8,18,26,12,20,30,14};
            JPanel wavePanel = new JPanel(new FlowLayout(FlowLayout.LEFT, 2, 0));
            wavePanel.setOpaque(false);
            Color barColor = mine ? new Color(0x86,0xEF,0xAC) : new Color(0xCB,0xD5,0xE1);
            for (int bh : barHeights) {
                final int barH = bh;
                JPanel bar = new JPanel() {
                    @Override public Dimension getPreferredSize() { return new Dimension(3, barH); }
                    @Override protected void paintComponent(Graphics g) {
                        Graphics2D g2 = (Graphics2D) g.create();
                        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                        g2.setColor(barColor);
                        g2.fillRoundRect(0, 0, getWidth(), getHeight(), 3, 3);
                        g2.dispose();
                    }
                };
                bar.setOpaque(false);
                wavePanel.add(bar);
            }
            // Duration label — mirrors .audio-duration
            JLabel durLbl = new JLabel("0:00");
            durLbl.setFont(new Font("Segoe UI", Font.BOLD, 11));
            durLbl.setForeground(new Color(0x64, 0x74, 0x8B));
            JPanel waveRow = new JPanel(new BorderLayout(4, 0));
            waveRow.setOpaque(false);
            waveRow.add(wavePanel, BorderLayout.CENTER);
            waveRow.add(durLbl,   BorderLayout.EAST);
            JPanel audioBubble = new JPanel(new BorderLayout(8, 0));
            audioBubble.setOpaque(false);
            audioBubble.setMaximumSize(new Dimension(300, 54));
            audioBubble.add(playBtn,  BorderLayout.WEST);
            audioBubble.add(waveRow,  BorderLayout.CENTER);
            bodyBox.add(audioBubble);
        } else if (msg.filePath != null) {
            String url  = resolveUrl(msg.filePath);
            String name = msg.fileName != null ? msg.fileName : "file";
            String ext  = name.contains(".") ? name.substring(name.lastIndexOf('.') + 1).toUpperCase() : "FILE";
            String size = msg.fileSize >= 1_048_576
                ? String.format("%.1f MB", msg.fileSize / 1_048_576.0)
                : msg.fileSize > 0 ? (msg.fileSize / 1024) + " KB" : ext;
            JPanel fileBubble = new JPanel(new BorderLayout(10, 0)) {
                @Override protected void paintComponent(Graphics g) {
                    Graphics2D g2 = (Graphics2D) g.create();
                    g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                    g2.setColor(mine ? FILE_BG_MINE : FILE_BG);
                    g2.fillRoundRect(0, 0, getWidth(), getHeight(), 10, 10);
                    g2.setColor(new Color(0xE2, 0xE8, 0xF0));
                    g2.drawRoundRect(0, 0, getWidth() - 1, getHeight() - 1, 10, 10);
                    g2.dispose();
                }
            };
            fileBubble.setOpaque(false);
            fileBubble.setBorder(new EmptyBorder(8, 10, 8, 10));
            fileBubble.setMaximumSize(new Dimension(280, 60));
            JLabel iconLbl = new JLabel(fileEmoji(ext));
            iconLbl.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 22));
            JPanel info = new JPanel(); info.setLayout(new BoxLayout(info, BoxLayout.Y_AXIS)); info.setOpaque(false);
            JLabel nameLbl = new JLabel(truncate(name, 28));
            nameLbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
            nameLbl.setForeground(new Color(0x1E, 0x29, 0x3B));
            JLabel metaLbl = new JLabel(ext + "  ·  " + size);
            metaLbl.setFont(new Font("Segoe UI", Font.PLAIN, 10));
            metaLbl.setForeground(new Color(0x94, 0xA3, 0xB8));
            info.add(nameLbl); info.add(metaLbl);
            JButton dlBtn = new JButton("⬇");
            dlBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
            dlBtn.setForeground(Color.WHITE);
            dlBtn.setBackground(PRIMARY);
            dlBtn.setBorderPainted(false);
            dlBtn.setFocusPainted(false);
            dlBtn.setPreferredSize(new Dimension(30, 30));
            dlBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            dlBtn.addActionListener(e -> {
                try { Desktop.getDesktop().browse(new java.net.URI(url)); } catch (Exception ignored) {}
            });
            fileBubble.add(iconLbl, BorderLayout.WEST);
            fileBubble.add(info,    BorderLayout.CENTER);
            fileBubble.add(dlBtn,   BorderLayout.EAST);
            bodyBox.add(fileBubble);
        } else {
            JLabel bodyLbl = new JLabel(
                "<html><body style='width:260px'>" + escapeHtml(msg.body != null ? msg.body : "") + "</body></html>");
            bodyLbl.setFont(new Font("Segoe UI", Font.PLAIN, 14));
            bodyLbl.setForeground(new Color(0x1E, 0x29, 0x3B));
            bodyBox.add(bodyLbl);
        }

        // Timestamp (bottom-right, WhatsApp style)
        JLabel timeLbl = new JLabel(formatTime(msg.createdAt));
        timeLbl.setFont(new Font("Segoe UI", Font.PLAIN, 10));
        timeLbl.setForeground(new Color(0x94, 0xA3, 0xB8));
        JPanel timeRow = new JPanel(new FlowLayout(FlowLayout.RIGHT, 0, 0));
        timeRow.setOpaque(false);
        timeRow.add(timeLbl);

        // Actions (reply / edit / delete)
        JPanel actions = new JPanel(new FlowLayout(mine ? FlowLayout.RIGHT : FlowLayout.LEFT, 4, 0));
        actions.setOpaque(false);
        if (!msg.deleted) {
            JButton replyBtn = smallButton("↩", PRIMARY);
            replyBtn.setToolTipText("Reply");
            replyBtn.addActionListener(e -> setReply(msg.id, mine ? "You" : currentOtherName,
                msg.body != null ? msg.body : "Attachment"));
            actions.add(replyBtn);
            if (mine) {
                if (msg.body != null) {
                    JButton editBtn = smallButton("✏", new Color(0x38, 0xA1, 0x69));
                    editBtn.setToolTipText("Edit");
                    editBtn.addActionListener(e -> showEditDialog(msg));
                    actions.add(editBtn);
                }
                JButton deleteBtn = smallButton("🗑", new Color(0xE5, 0x3E, 0x3E));
                deleteBtn.setToolTipText("Delete");
                deleteBtn.addActionListener(e -> deleteMessage(msg));
                actions.add(deleteBtn);
            }
        }

        bubble.add(bodyBox,  BorderLayout.CENTER);
        bubble.add(timeRow,  BorderLayout.SOUTH);

        // Wrap bubble + actions in a column
        JPanel col = new JPanel();
        col.setLayout(new BoxLayout(col, BoxLayout.Y_AXIS));
        col.setOpaque(false);
        col.add(bubble);
        col.add(actions);
        return col;
    }

    // ── Active audio playback (one at a time, mirrors web's toggleAudio) ──
    private SourceDataLine activeLine   = null;
    private boolean        paused       = false;
    private JButton        activePlayBtn = null;

    private void playAudio(String url, JButton playBtn) {
        // If this button is already playing, pause/resume
        if (playBtn == activePlayBtn && activeLine != null) {
            if (paused) {
                activeLine.start();
                paused = false;
                playBtn.setText("⏸");
            } else {
                activeLine.stop();
                paused = true;
                playBtn.setText("▶");
            }
            return;
        }
        // Stop any other playing line first
        stopActiveAudio();
        activePlayBtn = playBtn;
        playBtn.setEnabled(false);
        playBtn.setText("⏳");
        new SwingWorker<File, Void>() {
            @Override protected File doInBackground() throws Exception {
                String ext = url.contains(".") ? url.substring(url.lastIndexOf('.')) : ".wav";
                File tmp = File.createTempFile("dm_play_", ext);
                tmp.deleteOnExit();
                try {
                    java.net.URL audioUrl = new java.net.URL(url);
                    try (java.io.InputStream in = audioUrl.openStream();
                         java.io.FileOutputStream out = new java.io.FileOutputStream(tmp)) {
                        byte[] buf = new byte[8192]; int n;
                        while ((n = in.read(buf)) != -1) out.write(buf, 0, n);
                    }
                } catch (Exception ex) {
                    byte[] bytes = webSession.getBytes(url.replace(webSession.rootUrl, ""));
                    try (java.io.FileOutputStream out = new java.io.FileOutputStream(tmp)) {
                        out.write(bytes);
                    }
                }
                return tmp;
            }
            @Override protected void done() {
                playBtn.setEnabled(true);
                try {
                    File tmp = get();
                    String name = tmp.getName().toLowerCase();
                    if (name.endsWith(".wav")) {
                        playWav(tmp, playBtn);
                    } else {
                        Desktop.getDesktop().open(tmp);
                        playBtn.setText("▶");
                        activePlayBtn = null;
                    }
                } catch (Exception ex) {
                    playBtn.setText("▶");
                    activePlayBtn = null;
                    setStatus("⚠ Could not play audio: " + ex.getMessage());
                }
            }
        }.execute();
    }

    private void stopActiveAudio() {
        if (activeLine != null) {
            activeLine.stop();
            activeLine.close();
            activeLine = null;
        }
        paused = false;
        if (activePlayBtn != null) {
            JButton btn = activePlayBtn;
            SwingUtilities.invokeLater(() -> btn.setText("▶"));
            activePlayBtn = null;
        }
    }

    private void playWav(File file, JButton playBtn) {
        new Thread(() -> {
            try (AudioInputStream ais = AudioSystem.getAudioInputStream(file)) {
                AudioFormat fmt = ais.getFormat();
                DataLine.Info info = new DataLine.Info(SourceDataLine.class, fmt);
                if (!AudioSystem.isLineSupported(info)) {
                    SwingUtilities.invokeLater(() -> {
                        try { Desktop.getDesktop().open(file); } catch (Exception ignored) {}
                        playBtn.setText("▶"); activePlayBtn = null;
                    });
                    return;
                }
                SourceDataLine line = (SourceDataLine) AudioSystem.getLine(info);
                line.open(fmt);
                line.start();
                activeLine = line;
                paused = false;
                SwingUtilities.invokeLater(() -> playBtn.setText("⏸"));
                byte[] buf = new byte[4096]; int n;
                while ((n = ais.read(buf)) != -1) {
                    // Block here while paused
                    while (paused && activeLine == line) {
                        Thread.sleep(50);
                    }
                    if (activeLine != line) break; // stopped externally
                    line.write(buf, 0, n);
                }
                line.drain();
                line.close();
            } catch (Exception ex) {
                SwingUtilities.invokeLater(() -> setStatus("⚠ Playback error: " + ex.getMessage()));
            } finally {
                SwingUtilities.invokeLater(() -> {
                    playBtn.setText("▶");
                    if (activePlayBtn == playBtn) { activeLine = null; activePlayBtn = null; paused = false; }
                });
            }
        }, "dm-audio-play").start();
    }

    private String resolveUrl(String path) {
        if (path == null) return "";
        if (path.startsWith("http://") || path.startsWith("https://")) return path;
        return storageBase + path;
    }

    private String fileEmoji(String ext) {
        return switch (ext.toLowerCase()) {
            case "pdf"              -> "📕";
            case "doc", "docx"      -> "📘";
            case "xls", "xlsx", "csv" -> "📗";
            case "ppt", "pptx"      -> "📙";
            case "zip", "rar", "7z" -> "🗜";
            case "mp3", "wav", "ogg" -> "🎵";
            case "mp4", "mov", "avi" -> "🎬";
            default                 -> "📄";
        };
    }

    // ── Sending ──────────────────────────────────────────────────────────

    private void handleSend() {
        if (currentOtherId <= 0) return;
        if (pendingAttachment != null) {
            sendAttachment(pendingAttachment, pendingAttachmentType);
            return;
        }
        String text = composeBox.getText().trim();
        if (text.isEmpty()) return;
        sendText(text);
    }

    private void sendText(String text) {
        sendBtn.setEnabled(false);
        final int otherId = currentOtherId;
        final int replyId = replyToId;
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                if (webSession == null || !webSession.isAuthenticated()) {
                    throw new IOException("Web session unavailable");
                }
                Map<String, String> fields = new java.util.HashMap<>();
                fields.put("body", text);
                if (replyId > 0) fields.put("reply_to_id", String.valueOf(replyId));
                webSession.postMultipart("/messages/" + otherId, fields, null);
                return null;
            }
            @Override protected void done() {
                sendBtn.setEnabled(true);
                try {
                    get();
                    composeBox.setText("");
                } catch (Exception ex) {
                    setStatus("⚠ Could not send: " + rootMessage(ex));
                }
                cancelReply();
                pollThread();
                refreshConversations();
            }
        }.execute();
    }

    private void sendAttachment(File file, String type) {
        sendBtn.setEnabled(false);
        final int otherId = currentOtherId;
        final int replyId = replyToId;
        final String caption = composeBox.getText().trim();
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                if (webSession == null || !webSession.isAuthenticated()) {
                    throw new IOException("Web session unavailable");
                }
                String mime = type.equals("image") ? "image/" + guessImageExt(file) : "application/octet-stream";
                Map<String, String> fields = new java.util.HashMap<>();
                if (!caption.isEmpty()) fields.put("body", caption);
                if (replyId > 0) fields.put("reply_to_id", String.valueOf(replyId));
                Map<String, WebSessionClient.FilePart> files = new java.util.HashMap<>();
                files.put(type, new WebSessionClient.FilePart(file, mime));
                webSession.postMultipart("/messages/" + otherId, fields, files);
                return null;
            }
            @Override protected void done() {
                sendBtn.setEnabled(true);
                try {
                    get();
                } catch (Exception ex) {
                    setStatus("⚠ Could not send attachment: " + rootMessage(ex));
                }
                composeBox.setText("");
                clearAttachment();
                cancelReply();
                pollThread();
                refreshConversations();
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

    // ── Attachments (image / file / camera) ─────────────────────────────

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
            File tmp = File.createTempFile("dm_camera_", ".png");
            javax.imageio.ImageIO.write(img, "png", tmp);
            pendingAttachment     = tmp;
            pendingAttachmentType = "image";
            attachPreviewLbl.setText("📸 photo.png");
        } catch (Throwable ex) {
            // No webcam present, or the (optional, best-effort) native
            // driver behind webcam-capture couldn't load on this machine —
            // fall back to a screen capture, exactly like the Topics view.
            try {
                Robot robot = new Robot();
                Rectangle screen = new Rectangle(Toolkit.getDefaultToolkit().getScreenSize());
                BufferedImage img = robot.createScreenCapture(screen);
                File tmp = File.createTempFile("dm_camera_", ".png");
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

    // ── Voice recording ──────────────────────────────────────────────────

    private void toggleRecording(JButton micBtn) {
        if (currentOtherId <= 0) {
            JOptionPane.showMessageDialog(this, "Open a conversation first.");
            return;
        }
        if (!recording) startRecording(micBtn); else stopRecording(micBtn);
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
            pendingAudio = File.createTempFile("dm_voice_", ".wav");
            File audioFile = pendingAudio;
            recordThread = new Thread(() -> {
                try (AudioInputStream ais = new AudioInputStream(micLine)) {
                    AudioSystem.write(ais, AudioFileFormat.Type.WAVE, audioFile);
                } catch (Exception ignored) {}
            }, "dm-voice-recorder");
            recordThread.start();
            // Show audio-preview bar with live timer (mirrors .audio-preview + rec-timer)
            recSeconds = 0;
            recTimerLbl.setText("0:00");
            audioPreviewBar.setVisible(true);
            recTimer = new Timer(1000, ev -> {
                recSeconds++;
                recTimerLbl.setText(recSeconds / 60 + ":" + String.format("%02d", recSeconds % 60));
            });
            recTimer.start();
            setStatus("🔴 Recording…");
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Could not start recording: " + ex.getMessage());
        }
    }

    private void discardRecording(JButton micBtn) {
        if (recTimer != null) { recTimer.stop(); recTimer = null; }
        audioPreviewBar.setVisible(false);
        recTimerLbl.setText("0:00");
        recording = false;
        micBtn.setText("🎤");
        micBtn.setForeground(null);
        if (micLine != null) { micLine.stop(); micLine.close(); micLine = null; }
        pendingAudio = null;
        setStatus(" ");
    }

    private void stopRecording(JButton micBtn) {
        if (recTimer != null) { recTimer.stop(); recTimer = null; }
        audioPreviewBar.setVisible(false);
        recTimerLbl.setText("0:00");
        recording = false;
        micBtn.setText("🎤");
        micBtn.setForeground(null);
        if (micLine != null) { micLine.stop(); micLine.close(); }
        setStatus(" ");
        if (pendingAudio != null && currentOtherId > 0) {
            File audio = pendingAudio;
            pendingAudio = null;
            final int otherId = currentOtherId;
            final int replyId = replyToId;
            new SwingWorker<Void, Void>() {
                @Override protected Void doInBackground() throws Exception {
                    if (webSession == null || !webSession.isAuthenticated()) {
                        throw new IOException("Web session unavailable");
                    }
                    Map<String, String> fields = new java.util.HashMap<>();
                    if (replyId > 0) fields.put("reply_to_id", String.valueOf(replyId));
                    Map<String, WebSessionClient.FilePart> files = new java.util.HashMap<>();
                    files.put("audio", new WebSessionClient.FilePart(audio, "audio/wav"));
                    webSession.postMultipart("/messages/" + otherId, fields, files);
                    return null;
                }
                @Override protected void done() {
                    try {
                        get();
                    } catch (Exception ex) {
                        setStatus("⚠ Could not send voice message: " + rootMessage(ex));
                    }
                    cancelReply();
                    pollThread();
                    refreshConversations();
                }
            }.execute();
        }
    }

    // ── Edit / delete ────────────────────────────────────────────────────

    private void showEditDialog(PrivateMessage msg) {
        String newBody = (String) JOptionPane.showInputDialog(
            this, "Edit message:", "✏ Edit Message", JOptionPane.PLAIN_MESSAGE, null, null, msg.body);
        if (newBody == null || newBody.trim().isEmpty()) return;
        final String trimmed = newBody.trim();
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                if (webSession == null || !webSession.isAuthenticated()) {
                    throw new IOException("Web session unavailable");
                }
                webSession.putJson("/messages/" + msg.id + "/edit", Map.of("body", trimmed));
                return null;
            }
            @Override protected void done() {
                try {
                    get();
                } catch (Exception ex) {
                    setStatus("⚠ Could not edit message: " + rootMessage(ex));
                }
                reloadThread();
            }
        }.execute();
    }

    private void deleteMessage(PrivateMessage msg) {
        int ok = JOptionPane.showConfirmDialog(this, "Delete this message?", "Confirm", JOptionPane.YES_NO_OPTION);
        if (ok != JOptionPane.YES_OPTION) return;
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                if (webSession == null || !webSession.isAuthenticated()) {
                    throw new IOException("Web session unavailable");
                }
                webSession.delete("/messages/" + msg.id);
                return null;
            }
            @Override protected void done() {
                try {
                    get();
                } catch (Exception ex) {
                    setStatus("⚠ Could not delete message: " + rootMessage(ex));
                }
                reloadThread();
            }
        }.execute();
    }

    /** Re-fetches the whole visible window of the thread — used after an edit/delete so the change shows immediately. */
    private void reloadThread() {
        if (currentOtherId <= 0) return;
        shownIds.clear();
        messagesPanel.removeAll();
        messagesPanel.revalidate();
        messagesPanel.repaint();
        lastFetchIso = clearTimestamps.getOrDefault(currentOtherId, "1970-01-01T00:00:00.000Z");
        pollThread();
    }

    // ── Clear chat (device-local, mirrors clearDmChat in messages.blade.php) ──

    private void clearChat() {
        if (currentOtherId <= 0) return;
        int ok = JOptionPane.showConfirmDialog(this,
            "Clear this chat on this device? " + currentOtherName + " will still see the full history.",
            "Clear Chat", JOptionPane.YES_NO_OPTION);
        if (ok != JOptionPane.YES_OPTION) return;
        String now = nowIso();
        clearTimestamps.put(currentOtherId, now);
        lastFetchIso = now;
        shownIds.clear();
        messagesPanel.removeAll();
        messagesPanel.revalidate();
        messagesPanel.repaint();
    }

    // ── Reply bar helpers ─────────────────────────────────────────────────

    private void setReply(int msgId, String author, String body) {
        replyToId = msgId;
        replyBarAuthor.setText("↩ " + author);
        replyBarBody.setText(body.length() > 60 ? body.substring(0, 60) + "…" : body);
        replyBar.setVisible(true);
        composeBox.requestFocusInWindow();
    }

    private void cancelReply() {
        replyToId = -1;
        replyBar.setVisible(false);
    }

    // ── Small helpers ────────────────────────────────────────────────────

    public void setStatus(String text) { statusLbl.setText(text); }

    private void scrollToBottom() {
        Container p1 = messagesPanel.getParent();
        if (!(p1 instanceof JViewport)) return;
        Container p2 = p1.getParent();
        if (!(p2 instanceof JScrollPane)) return;
        JScrollBar sb = ((JScrollPane) p2).getVerticalScrollBar();
        SwingUtilities.invokeLater(() -> sb.setValue(sb.getMaximum()));
    }

    private String formatTime(String iso) {
        if (iso == null) return "";
        try {
            String normalized = iso.matches(".*[Zz]$|.*[+-]\\d{2}:?\\d{2}$") ? iso : iso + "Z";
            java.time.Instant inst = java.time.Instant.parse(normalized);
            return java.time.format.DateTimeFormatter.ofPattern("HH:mm")
                .withZone(java.time.ZoneId.systemDefault()).format(inst);
        } catch (Exception e) {
            return iso.length() >= 16 ? iso.substring(11, 16) : iso;
        }
    }

    private String nowIso() {
        return java.time.format.DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss.SSS'Z'")
            .withZone(java.time.ZoneOffset.UTC).format(java.time.Instant.now());
    }

    private String truncate(String s, int max) {
        return s.length() > max ? s.substring(0, max) + "…" : s;
    }

    private String escapeHtml(String s) {
        return s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }

    private String rootMessage(Exception ex) {
        Throwable t = ex;
        if (ex instanceof ExecutionException && ex.getCause() != null) t = ex.getCause();
        String msg = t.getMessage();
        return msg != null ? msg : t.getClass().getSimpleName();
    }

    /** Renders a {@link Conversation} row: avatar + name + preview/role + time + unread pill. */
    private class ConversationCellRenderer extends JPanel implements ListCellRenderer<Conversation> {
        private final JLabel avatar = new JLabel("", SwingConstants.CENTER) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), SECONDARY));
                g2.fillOval(0, 0, getWidth(), getHeight());
                g2.dispose();
                super.paintComponent(g);
            }
        };
        private final JLabel nameLbl = new JLabel();
        private final JLabel subLbl  = new JLabel();
        private final JLabel timeLbl = new JLabel();
        private final JLabel unreadLbl = new JLabel();

        ConversationCellRenderer() {
            setLayout(new BorderLayout(10, 0));
            setBorder(new EmptyBorder(8, 10, 8, 10));

            avatar.setOpaque(false);
            avatar.setFont(new Font("Segoe UI", Font.BOLD, 13));
            avatar.setForeground(Color.WHITE);
            avatar.setPreferredSize(new Dimension(36, 36));
            avatar.setMinimumSize(new Dimension(36, 36));
            avatar.setMaximumSize(new Dimension(36, 36));

            nameLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
            nameLbl.setForeground(new Color(0x2D, 0x37, 0x48));
            subLbl.setFont(new Font("Segoe UI", Font.PLAIN, 12));
            subLbl.setForeground(Theme.MUTED);

            JPanel textCol = new JPanel();
            textCol.setOpaque(false);
            textCol.setLayout(new BoxLayout(textCol, BoxLayout.Y_AXIS));
            textCol.add(nameLbl);
            textCol.add(subLbl);

            timeLbl.setFont(new Font("Segoe UI", Font.PLAIN, 10));
            timeLbl.setForeground(Theme.MUTED);
            unreadLbl.setFont(new Font("Segoe UI", Font.BOLD, 10));
            unreadLbl.setForeground(Color.WHITE);
            unreadLbl.setOpaque(true);
            unreadLbl.setBackground(PRIMARY);
            unreadLbl.setHorizontalAlignment(SwingConstants.CENTER);
            unreadLbl.setBorder(new EmptyBorder(1, 6, 1, 6));

            JPanel rightCol = new JPanel();
            rightCol.setOpaque(false);
            rightCol.setLayout(new BoxLayout(rightCol, BoxLayout.Y_AXIS));
            rightCol.add(timeLbl);
            rightCol.add(Box.createVerticalStrut(4));
            rightCol.add(unreadLbl);

            add(avatar,   BorderLayout.WEST);
            add(textCol,  BorderLayout.CENTER);
            add(rightCol, BorderLayout.EAST);
        }

        @Override
        public Component getListCellRendererComponent(JList<? extends Conversation> list, Conversation c,
                                                       int index, boolean isSelected, boolean cellHasFocus) {
            setBackground(isSelected ? new Color(0xEE, 0xF2, 0xFF) : Color.WHITE);
            final String initial = c.name.isEmpty() ? "?" : String.valueOf(c.name.charAt(0)).toUpperCase();
            // Replace flat avatar with gradient-painted label
            avatar.setText(initial);
            avatar.setIcon(null);
            avatar.setOpaque(false);
            // repaint will use paintComponent below
            nameLbl.setText(c.name);
            subLbl.setText(c.roleOrPreview);
            timeLbl.setText(c.timeText != null ? c.timeText : "");
            unreadLbl.setVisible(!c.isSearchResult && c.unread > 0);
            unreadLbl.setText(String.valueOf(c.unread));
            return this;
        }

        @Override protected void paintComponent(Graphics g) {
            g.setColor(getBackground());
            g.fillRect(0, 0, getWidth(), getHeight());
            super.paintComponent(g);
        }
    }
}
