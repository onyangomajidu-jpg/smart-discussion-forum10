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
    private String lastFetchIso     = "1970-01-01T00:00:00.000Z";
    private final Set<Integer> shownIds = new LinkedHashSet<>();

    private final JLabel    threadHeader  = new JLabel("  💬 Select a conversation");
    private final JLabel    threadMeta    = new JLabel(" ");
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
    private boolean        recording = false;

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
            if (c != null) openThread(c.userId, c.name);
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

        JPanel titleBlock = new JPanel();
        titleBlock.setLayout(new BoxLayout(titleBlock, BoxLayout.Y_AXIS));
        titleBlock.setOpaque(false);
        titleBlock.add(threadHeader);
        titleBlock.add(threadMeta);

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
        headerPanel.add(titleBlock,  BorderLayout.CENTER);
        headerPanel.add(clearChatBtn, BorderLayout.EAST);

        // ── Message list ─────────────────────────────────────────────────
        messagesPanel.setLayout(new BoxLayout(messagesPanel, BoxLayout.Y_AXIS));
        messagesPanel.setBackground(BG_BODY);
        messagesPanel.setBorder(new EmptyBorder(8, 0, 8, 0));

        JLabel emptyState = new JLabel(
            "<html><body style='width:260px;text-align:center'>Select a conversation on the left, "
            + "or search for someone to start a new one.</body></html>", SwingConstants.CENTER);
        emptyState.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        emptyState.setForeground(Theme.MUTED);
        emptyState.setAlignmentX(Component.CENTER_ALIGNMENT);
        emptyState.setBorder(new EmptyBorder(40, 20, 0, 20));
        messagesPanel.add(emptyState);

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
        topInputArea.add(statusLbl, BorderLayout.NORTH);
        topInputArea.add(replyBar,  BorderLayout.CENTER);
        topInputArea.add(attachRow, BorderLayout.SOUTH);
        bottom.add(topInputArea, BorderLayout.NORTH);
        bottom.add(composeRow,   BorderLayout.CENTER);

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
        java.util.regex.Pattern rowStart = java.util.regex.Pattern.compile(
            "class=\"topic-item[^\"]*\"[^>]*onclick=\"window\\.location='/messages/(\\d+)'\"");
        java.util.regex.Matcher m = rowStart.matcher(html);
        List<int[]> starts = new ArrayList<>();
        while (m.find()) {
            starts.add(new int[]{m.start(), Integer.parseInt(m.group(1))});
        }
        for (int i = 0; i < starts.size(); i++) {
            int begin = starts.get(i)[0];
            int end   = (i + 1 < starts.size()) ? starts.get(i + 1)[0] : Math.min(html.length(), begin + 2000);
            String chunk  = html.substring(begin, end);
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

    private void openThread(int otherId, String otherName) {
        currentOtherId   = otherId;
        currentOtherName = otherName;
        lastFetchIso      = clearTimestamps.getOrDefault(otherId, "1970-01-01T00:00:00.000Z");
        shownIds.clear();
        messagesPanel.removeAll();
        messagesPanel.revalidate();
        messagesPanel.repaint();
        threadHeader.setText("  " + otherName);
        threadMeta.setText(" ");
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

    private JPanel buildMessageCard(PrivateMessage msg) {
        boolean mine = msg.senderId == user.getUserId();
        JPanel card = new JPanel(new BorderLayout(0, 6));
        card.setBackground(mine ? new Color(0xEE, 0xF2, 0xFF) : Color.WHITE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
                BorderFactory.createMatteBorder(0, 4, 0, 0, mine ? PRIMARY : new Color(0xE2, 0xE8, 0xF0))),
            new EmptyBorder(12, 14, 10, 14)));
        card.setAlignmentX(Component.LEFT_ALIGNMENT);
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));

        String authorName = mine ? "You" : currentOtherName;
        String initial = authorName.isEmpty() ? "?" : String.valueOf(authorName.charAt(0)).toUpperCase();
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

        JPanel authorRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        authorRow.setOpaque(false);
        authorRow.add(avatarLbl);
        JLabel authorLbl = new JLabel(authorName);
        authorLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        authorLbl.setForeground(new Color(0x4A, 0x55, 0x68));
        authorRow.add(authorLbl);
        JLabel timeLbl = new JLabel(formatTime(msg.createdAt));
        timeLbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        timeLbl.setForeground(Theme.MUTED);
        authorRow.add(timeLbl);

        JPanel bodyBox = new JPanel();
        bodyBox.setLayout(new BoxLayout(bodyBox, BoxLayout.Y_AXIS));
        bodyBox.setOpaque(false);
        bodyBox.setAlignmentX(Component.LEFT_ALIGNMENT);

        if (msg.replyToId != null) {
            JLabel quote = new JLabel("<html><body style='width:340px'>↩ "
                + (msg.replyToSenderId == user.getUserId() ? "You" : currentOtherName) + ": "
                + escapeHtml(msg.replyToBody != null ? truncate(msg.replyToBody, 80) : "Attachment")
                + "</body></html>");
            quote.setFont(new Font("Segoe UI", Font.ITALIC, 11));
            quote.setForeground(Theme.MUTED);
            quote.setAlignmentX(Component.LEFT_ALIGNMENT);
            quote.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 3, 0, 0, PRIMARY),
                new EmptyBorder(2, 8, 2, 4)));
            bodyBox.add(quote);
            bodyBox.add(Box.createVerticalStrut(4));
        }

        if (msg.deleted) {
            JLabel del = new JLabel("🚫 This message was deleted");
            del.setFont(new Font("Segoe UI", Font.ITALIC, 13));
            del.setForeground(Theme.MUTED);
            bodyBox.add(del);
        } else if (msg.imagePath != null) {
            String url = storageBase + msg.imagePath;
            JLabel imgLbl = new JLabel("🖼 Photo");
            imgLbl.setFont(new Font("Segoe UI", Font.ITALIC, 13));
            imgLbl.setForeground(PRIMARY);
            imgLbl.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            imgLbl.addMouseListener(new MouseAdapter() {
                @Override public void mouseClicked(MouseEvent e) {
                    try { Desktop.getDesktop().browse(new java.net.URI(url)); } catch (Exception ignored) {}
                }
            });
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
            bodyBox.add(imgLbl);
            if (msg.body != null && !msg.body.isEmpty()) {
                JLabel caption = new JLabel(msg.body);
                caption.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                bodyBox.add(caption);
            }
        } else if (msg.audioPath != null) {
            String url = storageBase + msg.audioPath;
            JPanel audioBubble = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 0));
            audioBubble.setOpaque(false);
            JLabel waveLbl = new JLabel("🎤 Voice message");
            waveLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
            waveLbl.setForeground(new Color(0x38, 0xA1, 0x69));
            JButton playBtn = new JButton("▶ Play");
            playBtn.setFont(new Font("Segoe UI", Font.BOLD, 11));
            playBtn.setForeground(Color.WHITE);
            playBtn.setBackground(new Color(0x38, 0xA1, 0x69));
            playBtn.setBorderPainted(false);
            playBtn.setFocusPainted(false);
            playBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            playBtn.addActionListener(e -> {
                try { Desktop.getDesktop().browse(new java.net.URI(url)); } catch (Exception ignored) {}
            });
            audioBubble.add(waveLbl);
            audioBubble.add(playBtn);
            bodyBox.add(audioBubble);
        } else if (msg.filePath != null) {
            String url  = storageBase + msg.filePath;
            String name = msg.fileName != null ? msg.fileName : "file";
            JButton dlBtn = new JButton("📎 " + name + "  ⬇ Download");
            dlBtn.setFont(new Font("Segoe UI", Font.PLAIN, 13));
            dlBtn.setForeground(PRIMARY);
            dlBtn.setBackground(new Color(0xEE, 0xF2, 0xFF));
            dlBtn.setBorderPainted(false);
            dlBtn.setFocusPainted(false);
            dlBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            dlBtn.addActionListener(e -> {
                try { Desktop.getDesktop().browse(new java.net.URI(url)); } catch (Exception ignored) {}
            });
            bodyBox.add(dlBtn);
        } else {
            JTextArea bodyArea = new JTextArea(msg.body != null ? msg.body : "");
            bodyArea.setFont(new Font("Segoe UI", Font.PLAIN, 14));
            bodyArea.setLineWrap(true);
            bodyArea.setWrapStyleWord(true);
            bodyArea.setEditable(false);
            bodyArea.setOpaque(false);
            bodyArea.setBorder(new EmptyBorder(4, 0, 4, 0));
            bodyBox.add(bodyArea);
        }

        JPanel actions = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        actions.setOpaque(false);
        if (!msg.deleted) {
            JButton replyBtn = smallButton("↩ Reply", PRIMARY);
            replyBtn.addActionListener(e -> setReply(msg.id, mine ? "You" : currentOtherName,
                msg.body != null ? msg.body : "Attachment"));
            actions.add(replyBtn);
            if (mine) {
                JButton editBtn = smallButton("✏ Edit", new Color(0x38, 0xA1, 0x69));
                editBtn.addActionListener(e -> showEditDialog(msg));
                JButton deleteBtn = smallButton("🗑 Delete", new Color(0xE5, 0x3E, 0x3E));
                deleteBtn.addActionListener(e -> deleteMessage(msg));
                actions.add(editBtn);
                actions.add(deleteBtn);
            }
        }

        card.add(authorRow, BorderLayout.NORTH);
        card.add(bodyBox,   BorderLayout.CENTER);
        card.add(actions,   BorderLayout.SOUTH);
        return card;
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
        private final JLabel avatar = new JLabel("", SwingConstants.CENTER);
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
            avatar.setText(c.name.isEmpty() ? "?" : String.valueOf(c.name.charAt(0)).toUpperCase());
            avatar.setIcon(null);
            avatar.setOpaque(true);
            avatar.setBackground(PRIMARY);
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
