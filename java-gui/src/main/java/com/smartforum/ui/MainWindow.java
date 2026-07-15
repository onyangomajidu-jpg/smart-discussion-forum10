package com.smartforum.ui;

import com.smartforum.api.ApiClient;
import com.smartforum.auth.AuthService;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;
import com.smartforum.sync.ForumWebSocketListener;
import com.smartforum.sync.OfflineSyncManager;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;

/**
 * Main application window (SDD §3.1 — Days 8-10).
 *
 * Layout:
 *   NORTH  — top bar (title, user info, connection badge, logout)
 *   CENTER — JSplitPane: TopicListPanel (left) | ConversationPanel (right)
 *
 * Background thread polls api.isOnline() every 10 s; on reconnect it
 * calls synchronizeOfflineData() and refreshes both panels.
 */
public class MainWindow extends JFrame {

    private static final Color PRIMARY = new Color(0x66, 0x7E, 0xEA);

    private final AuthUser               user;
    private final AuthService            authService;
    private final ApiClient              api;
    private final LocalCacheDatabase     cache;
    private final OfflineSyncManager     syncManager;
    private final ForumWebSocketListener wsListener;

    private final JLabel connectionBadge = new JLabel();
    private boolean      wasOnline       = false;

    private final ScheduledExecutorService reconnectPoller =
        Executors.newSingleThreadScheduledExecutor(r -> {
            Thread t = new Thread(r, "reconnect-poller");
            t.setDaemon(true);
            return t;
        });

    public MainWindow(AuthUser user, AuthService authService,
                      ApiClient api, LocalCacheDatabase cache) {
        this.user        = user;
        this.authService = authService;
        this.api         = api;
        this.cache       = cache;
        this.syncManager = new OfflineSyncManager(api, cache);

        // ── Panels ────────────────────────────────────────────────────────
        ConversationPanel conversationPanel =
            new ConversationPanel(cache, user, syncManager);

        // ── WebSocket — must be created before TopicListPanel lambda ──────
        wsListener = new ForumWebSocketListener(conversationPanel);
        wsListener.connect();

        TopicListPanel topicListPanel = new TopicListPanel(cache, topic -> {
            conversationPanel.loadTopic(topic);
            wsListener.subscribeTopic(topic.id);
        });

        // Refresh both panels after sync
        syncManager.setSyncListener(() -> {
            topicListPanel.refresh();
            conversationPanel.refreshPosts();
            conversationPanel.setStatus("✅ Sync complete");
        });

        // ── Layout ────────────────────────────────────────────────────────
        setTitle("Smart Discussion Forum — " + user.getName());
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setSize(1100, 700);
        setLocationRelativeTo(null);

        JSplitPane split = new JSplitPane(
            JSplitPane.HORIZONTAL_SPLIT, topicListPanel, conversationPanel);
        split.setDividerLocation(260);
        split.setDividerSize(4);
        split.setBorder(null);

        getContentPane().setLayout(new BorderLayout());
        getContentPane().add(buildTopBar(), BorderLayout.NORTH);
        getContentPane().add(split,         BorderLayout.CENTER);

        // ── Reconnect poller ──────────────────────────────────────────────
        reconnectPoller.scheduleAtFixedRate(this::checkConnectivity,
            5, 10, TimeUnit.SECONDS);

        addWindowListener(new java.awt.event.WindowAdapter() {
            @Override public void windowClosing(java.awt.event.WindowEvent e) {
                wsListener.disconnect();
                reconnectPoller.shutdownNow();
            }
        });
    }

    // ── Top bar ───────────────────────────────────────────────────────────

    private JPanel buildTopBar() {
        JPanel bar = new JPanel(new BorderLayout());
        bar.setBackground(PRIMARY);
        bar.setBorder(new EmptyBorder(10, 20, 10, 20));

        JLabel title = new JLabel("🎓 Smart Discussion Forum");
        title.setFont(new Font("Segoe UI", Font.BOLD, 18));
        title.setForeground(Color.WHITE);

        connectionBadge.setFont(new Font("Segoe UI", Font.BOLD, 12));
        connectionBadge.setForeground(Color.WHITE);
        updateBadge(api.isOnline());

        JLabel userInfo = new JLabel(user.getName() + "  [" + user.getRole().toUpperCase() + "]");
        userInfo.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        userInfo.setForeground(Color.WHITE);

        JButton logoutBtn = new JButton("Logout");
        logoutBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        logoutBtn.setForeground(PRIMARY);
        logoutBtn.setBackground(Color.WHITE);
        logoutBtn.setBorderPainted(false);
        logoutBtn.setFocusPainted(false);
        logoutBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        logoutBtn.addActionListener(e -> {
            wsListener.disconnect();
            reconnectPoller.shutdownNow();
            authService.logout();
            dispose();
            new LoginWindow(authService, api, cache).setVisible(true);
        });

        JPanel right = new JPanel(new FlowLayout(FlowLayout.RIGHT, 12, 0));
        right.setOpaque(false);
        right.add(connectionBadge);
        right.add(userInfo);
        right.add(logoutBtn);

        bar.add(title, BorderLayout.WEST);
        bar.add(right, BorderLayout.EAST);
        return bar;
    }

    // ── Reconnect logic ───────────────────────────────────────────────────

    private void checkConnectivity() {
        boolean online = api.isOnline();
        SwingUtilities.invokeLater(() -> updateBadge(online));

<<<<<<< HEAD
        if (online && !wasOnline) {
            System.out.println("[MainWindow] Reconnected — running synchronizeOfflineData()");
            syncManager.synchronizeOfflineData();
            wsListener.connect();
        }
        wasOnline = online;
    }

    private void updateBadge(boolean online) {
        connectionBadge.setText(online ? "🟢 Online" : "🔴 Offline");
=======
        // ── Main section ──
        sidebar.add(sidebarSection("Main"));
        navDashboard = sidebarItem("🏠", "Dashboard", true, () -> showView("dashboard"));
        sidebar.add(navDashboard);
        sidebar.add(sidebarItem("💬", "Topics",        false, null));
        sidebar.add(sidebarItem("📄", "Export & Share", false, () -> new ExportWindow(api).setVisible(true)));
        if ("member".equals(user.getRole())) {
            sidebar.add(sidebarItem("🎯", "Quizzes",   false, null));
        }
        sidebar.add(sidebarItem("🔔", "Notifications", false, null));

        navStatistics = sidebarItem("📊", "Statistics", false, () -> showView("statistics"));
        sidebar.add(navStatistics);

        // ── Role-specific sections ──
        if ("lecturer".equals(user.getRole())) {
            sidebar.add(sidebarSection("Lecturer"));
            sidebar.add(sidebarItem("📊", "Lecturer Panel",  false, null));
            sidebar.add(sidebarItem("📝", "Manage Quizzes",  false, null));
        }
        if ("admin".equals(user.getRole())) {
            sidebar.add(sidebarSection("Admin"));
            sidebar.add(sidebarItem("⚙️", "Admin Panel", false, null));
        }

        sidebar.add(Box.createVerticalGlue());

        // ── User footer ──
        JPanel footer = new JPanel(new BorderLayout(10, 0));
        footer.setBackground(SURFACE);
        footer.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(1, 0, 0, 0, BORDER_C),
            new EmptyBorder(14, 16, 14, 16)
        ));
        footer.setMaximumSize(new Dimension(Integer.MAX_VALUE, 64));

        JLabel avatar = new JLabel(String.valueOf(user.getName().charAt(0)).toUpperCase()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), SECONDARY));
                g2.fillOval(0, 0, getWidth(), getHeight());
                g2.dispose();
                super.paintComponent(g);
            }
        };
        avatar.setPreferredSize(new Dimension(36, 36));
        avatar.setHorizontalAlignment(SwingConstants.CENTER);
        avatar.setFont(new Font("Segoe UI", Font.BOLD, 14));
        avatar.setForeground(Color.WHITE);
        avatar.setOpaque(false);

        JPanel info = new JPanel();
        info.setLayout(new BoxLayout(info, BoxLayout.Y_AXIS));
        info.setOpaque(false);
        JLabel nameL = new JLabel(user.getName());
        nameL.setFont(new Font("Segoe UI", Font.BOLD, 13));
        nameL.setForeground(TEXT);
        JLabel roleL = new JLabel(capitalize(user.getRole()));
        roleL.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        roleL.setForeground(MUTED);
        info.add(nameL);
        info.add(roleL);

        footer.add(avatar, BorderLayout.WEST);
        footer.add(info,   BorderLayout.CENTER);
        sidebar.add(footer);

        return sidebar;
    }

    private JLabel sidebarSection(String label) {
        JLabel lbl = new JLabel(label.toUpperCase());
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 10));
        lbl.setForeground(MUTED);
        lbl.setBorder(new EmptyBorder(18, 24, 4, 16));
        lbl.setAlignmentX(LEFT_ALIGNMENT);
        lbl.setMaximumSize(new Dimension(Integer.MAX_VALUE, 30));
        return lbl;
    }

    private JPanel sidebarItem(String icon, String label, boolean active, Runnable onClick) {
        JPanel item = new JPanel(new FlowLayout(FlowLayout.LEFT, 10, 0));
        item.setBackground(active ? SIDEBAR_ACTIVE_BG : SURFACE);
        item.setBorder(new EmptyBorder(2, 8, 2, 8));
        item.setMaximumSize(new Dimension(Integer.MAX_VALUE, 38));
        item.setAlignmentX(LEFT_ALIGNMENT);
        item.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));

        JLabel ico = new JLabel(icon);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 15));

        JLabel txt = new JLabel(label);
        txt.setFont(new Font("Segoe UI", Font.BOLD, 13));
        txt.setForeground(active ? PRIMARY : MUTED);

        item.add(ico);
        item.add(txt);

        item.addMouseListener(new MouseAdapter() {
            @Override public void mouseEntered(MouseEvent e) {
                if (!active) { item.setBackground(BG); txt.setForeground(TEXT); }
            }
            @Override public void mouseExited(MouseEvent e) {
                if (!active) { item.setBackground(SURFACE); txt.setForeground(MUTED); }
            }
            @Override public void mouseClicked(MouseEvent e) {
                if (onClick != null) onClick.run();
            }
        });

        return item;
    }

    // ── Main content area ─────────────────────────────────────────────────

    private JScrollPane buildContent() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(28, 28, 48, 28));

        JLabel title = new JLabel("Dashboard");
        title.setFont(new Font("Segoe UI", Font.BOLD, 22));
        title.setForeground(TEXT);
        title.setAlignmentX(LEFT_ALIGNMENT);

        JLabel sub = new JLabel("Welcome back, " + user.getName() + "! Here's your activity overview.");
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        sub.setForeground(MUTED);
        sub.setAlignmentX(LEFT_ALIGNMENT);

        body.add(title);
        body.add(Box.createVerticalStrut(4));
        body.add(sub);
        body.add(Box.createVerticalStrut(22));
        body.add(buildStatsRow());
        body.add(Box.createVerticalStrut(20));
        body.add(buildPanelGrid());

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        return scroll;
    }

    // ── Stats row ─────────────────────────────────────────────────────────

    private JPanel buildStatsRow() {
        JPanel row = new JPanel(new GridLayout(1, 4, 16, 0));
        row.setBackground(BG);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));

        lblTopics   = new JLabel("—");
        lblPosts    = new JLabel("—");
        lblAttempts = new JLabel("—");
        lblAvgScore = new JLabel("—");

        row.add(statCard("💬", lblTopics,   "Topics Joined",  PRIMARY));
        row.add(statCard("📝", lblPosts,    "Posts Made",     SECONDARY));
        row.add(statCard("🎯", lblAttempts, "Quiz Attempts",  SUCCESS));
        row.add(statCard("⭐", lblAvgScore, "Avg Quiz Score", WARNING));
        return row;
    }

    private JPanel statCard(String icon, JLabel valLabel, String caption, Color accent) {
        JPanel card = new JPanel(new BorderLayout(0, 4));
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C),
            new EmptyBorder(16, 18, 16, 18)
        ));
        JLabel ico = new JLabel(icon);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 20));
        valLabel.setFont(new Font("Segoe UI", Font.BOLD, 28));
        valLabel.setForeground(accent);
        JLabel lbl = new JLabel(caption);
        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        lbl.setForeground(MUTED);
        JPanel top = new JPanel(new FlowLayout(FlowLayout.LEFT, 0, 0));
        top.setOpaque(false);
        top.add(ico);
        card.add(top,      BorderLayout.NORTH);
        card.add(valLabel, BorderLayout.CENTER);
        card.add(lbl,      BorderLayout.SOUTH);
        return card;
    }

    // ── 2×2 Panel grid ────────────────────────────────────────────────────

    private JPanel buildPanelGrid() {
        JPanel grid = new JPanel(new GridLayout(2, 2, 18, 18));
        grid.setBackground(BG);
        grid.setAlignmentX(LEFT_ALIGNMENT);
        grid.setMaximumSize(new Dimension(Integer.MAX_VALUE, 600));
        grid.add(buildTopicsPanel());
        grid.add(buildQuizPanel());
        grid.add(buildStatsPanel());
        grid.add(buildAccountPanel());
        return grid;
    }

    // Panel 1 — Topic Participation
    private JPanel buildTopicsPanel() {
        topicsListPanel = new JPanel();
        topicsListPanel.setLayout(new BoxLayout(topicsListPanel, BoxLayout.Y_AXIS));
        topicsListPanel.setBackground(SURFACE);
        topicsListPanel.add(emptyState("💬", "No topic participation yet."));
        return panel("💬  Topic Participation", PRIMARY, topicsListPanel);
    }

    // Panel 2 — Quiz Attempts
    private JPanel buildQuizPanel() {
        quizListPanel = new JPanel();
        quizListPanel.setLayout(new BoxLayout(quizListPanel, BoxLayout.Y_AXIS));
        quizListPanel.setBackground(SURFACE);
        quizListPanel.add(emptyState("📋", "No quiz attempts yet."));
        return panel("🎯  Quiz Attempts", SUCCESS, quizListPanel);
    }

    // Panel 3 — Statistics Review
    private JPanel buildStatsPanel() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(SURFACE);

        barEng  = progressBar(PRIMARY);
        barComp = progressBar(SUCCESS);
        barAvg  = progressBar(WARNING);
        lblEngPct  = new JLabel("0%");
        lblCompPct = new JLabel("0%");
        lblAvgPct  = new JLabel("N/A");

        body.add(progressRow("Forum Engagement",  barEng,  lblEngPct));
        body.add(Box.createVerticalStrut(14));
        body.add(progressRow("Quiz Completion",   barComp, lblCompPct));
        body.add(Box.createVerticalStrut(14));
        body.add(progressRow("Average Score",     barAvg,  lblAvgPct));
        return panel("📊  Statistics Review", WARNING, body);
    }

    private JProgressBar progressBar(Color color) {
        JProgressBar pb = new JProgressBar(0, 100);
        pb.setForeground(color);
        pb.setBackground(BORDER_C);
        pb.setBorderPainted(false);
        pb.setPreferredSize(new Dimension(0, 8));
        return pb;
    }

    private JPanel progressRow(String label, JProgressBar bar, JLabel pctLabel) {
        JPanel row = new JPanel(new BorderLayout(0, 5));
        row.setBackground(SURFACE);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 42));
        JPanel top = new JPanel(new BorderLayout());
        top.setOpaque(false);
        JLabel lbl = new JLabel(label);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        lbl.setForeground(TEXT);
        pctLabel.setFont(new Font("Segoe UI", Font.BOLD, 12));
        pctLabel.setForeground(MUTED);
        top.add(lbl,      BorderLayout.WEST);
        top.add(pctLabel, BorderLayout.EAST);
        row.add(top, BorderLayout.NORTH);
        row.add(bar, BorderLayout.SOUTH);
        return row;
    }

    // Panel 4 — Account Management
    private JPanel buildAccountPanel() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(SURFACE);
        body.add(infoRow("👤  Full Name", user.getName()));
        body.add(infoRow("✉️  Email",     user.getEmail()));
        body.add(infoRow("🏷️  Role",      capitalize(user.getRole())));
        body.add(Box.createVerticalStrut(16));

        JButton signOutBtn = new JButton("Sign Out");
        signOutBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        signOutBtn.setForeground(Color.WHITE);
        signOutBtn.setBackground(new Color(0xEF, 0x44, 0x44));
        signOutBtn.setBorderPainted(false);
        signOutBtn.setFocusPainted(false);
        signOutBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        signOutBtn.setAlignmentX(LEFT_ALIGNMENT);
        signOutBtn.addActionListener(e -> doLogout());
        body.add(signOutBtn);
        return panel("⚙️  Account Management", SECONDARY, body);
    }

    private JPanel infoRow(String label, String value) {
        JPanel row = new JPanel(new BorderLayout(10, 0));
        row.setBackground(SURFACE);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));
        row.setBorder(BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C));
        JLabel lbl = new JLabel(label);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        lbl.setForeground(MUTED);
        lbl.setPreferredSize(new Dimension(130, 0));
        JLabel val = new JLabel(value);
        val.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        val.setForeground(TEXT);
        row.add(lbl, BorderLayout.WEST);
        row.add(val, BorderLayout.CENTER);
        return row;
    }

    // ── Shared panel builder ──────────────────────────────────────────────

    private JPanel panel(String title, Color accent, JPanel content) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createLineBorder(BORDER_C));

        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(new Color(0xFA, 0xFB, 0xFF));
        header.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 4, 1, 0, accent),
            new EmptyBorder(12, 16, 12, 16)
        ));
        JLabel titleLbl = new JLabel(title);
        titleLbl.setFont(new Font("Segoe UI", Font.BOLD, 14));
        titleLbl.setForeground(TEXT);
        header.add(titleLbl, BorderLayout.WEST);

        JScrollPane scroll = new JScrollPane(content,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(new EmptyBorder(12, 16, 12, 16));
        scroll.getViewport().setBackground(SURFACE);

        card.add(header, BorderLayout.NORTH);
        card.add(scroll,  BorderLayout.CENTER);
        return card;
    }

    private JLabel emptyState(String icon, String msg) {
        JLabel lbl = new JLabel("<html><center>" + icon + "<br><br>" + msg + "</center></html>",
            SwingConstants.CENTER);
        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        lbl.setForeground(MUTED);
        lbl.setAlignmentX(CENTER_ALIGNMENT);
        lbl.setPreferredSize(new Dimension(0, 80));
        return lbl;
    }

    // ── API data load ─────────────────────────────────────────────────────

    private void loadDashboardData() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return new ObjectMapper().readTree(api.get("/dashboard")).get("stats");
            }
            @Override protected void done() {
                try {
                    JsonNode stats = get();
                    if (stats != null) applyStats(stats);
                } catch (Exception e) {
                    System.err.println("[MainWindow] Dashboard load failed: " + e.getMessage());
                }
            }
        }.execute();
    }

    public void refreshDashboard() { loadDashboardData(); }

    private void applyStats(JsonNode s) {
        int topics   = s.path("topicsParticipated").asInt(0);
        int posts    = s.path("totalPosts").asInt(0);
        int attempts = s.path("quizAttempts").asInt(0);
        int avail    = s.path("availableQuizzes").asInt(0);
        double avg   = s.path("avgScore").asDouble(-1);

        lblTopics.setText(String.valueOf(topics));
        lblPosts.setText(String.valueOf(posts));
        lblAttempts.setText(String.valueOf(attempts));
        lblAvgScore.setText(avg >= 0 ? Math.round(avg) + "%" : "—");

        int engPct  = Math.min(posts * 5, 100);
        int total   = attempts + avail;
        int compPct = total > 0 ? (int) Math.round(attempts * 100.0 / total) : 0;
        int avgPct  = avg >= 0 ? (int) Math.round(avg) : 0;

        barEng.setValue(engPct);   lblEngPct.setText(engPct + "%");
        barComp.setValue(compPct); lblCompPct.setText(compPct + "%");
        barAvg.setValue(avgPct);   lblAvgPct.setText(avg >= 0 ? avgPct + "%" : "N/A");

        topicsListPanel.removeAll();
        JsonNode recentTopics = s.path("recentTopics");
        if (recentTopics.isEmpty()) {
            topicsListPanel.add(emptyState("💬", "No topic participation yet."));
        } else {
            for (JsonNode t : recentTopics)
                topicsListPanel.add(listRow("●", t.path("title").asText("—"), PRIMARY));
        }

        quizListPanel.removeAll();
        JsonNode recentAttempts = s.path("recentAttempts");
        if (recentAttempts.isEmpty()) {
            quizListPanel.add(emptyState("📋", "No quiz attempts yet."));
        } else {
            for (JsonNode a : recentAttempts) {
                double sc = a.path("score").asDouble(-1);
                String score = sc >= 0 ? Math.round(sc) + "%" : "—";
                quizListPanel.add(listRow("✓", a.path("title").asText("—") + "  [" + score + "]", SUCCESS));
            }
        }

        topicsListPanel.revalidate();
        quizListPanel.revalidate();
        repaint();
    }

    private JPanel listRow(String bullet, String text, Color bulletColor) {
        JPanel row = new JPanel(new BorderLayout(8, 0));
        row.setBackground(SURFACE);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 34));
        row.setBorder(BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C));
        JLabel dot = new JLabel(bullet);
        dot.setFont(new Font("Segoe UI", Font.BOLD, 10));
        dot.setForeground(bulletColor);
        JLabel lbl = new JLabel(text);
        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        lbl.setForeground(TEXT);
        row.add(dot, BorderLayout.WEST);
        row.add(lbl, BorderLayout.CENTER);
        return row;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private void doLogout() {
        authService.logout();
        dispose();
        new LoginWindow(authService, cache).setVisible(true);
    }

    private JButton navButton(String text) {
        JButton btn = new JButton(text);
        btn.setFont(new Font("Segoe UI", Font.BOLD, 12));
        btn.setForeground(Color.WHITE);
        btn.setBackground(new Color(255, 255, 255, 40));
        btn.setBorder(BorderFactory.createLineBorder(new Color(255, 255, 255, 100), 1));
        btn.setFocusPainted(false);
        btn.setOpaque(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return btn;
    }

    private static String capitalize(String s) {
        if (s == null || s.isEmpty()) return s;
        return Character.toUpperCase(s.charAt(0)) + s.substring(1);
>>>>>>> origin
    }
}
