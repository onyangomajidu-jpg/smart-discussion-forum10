package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.auth.AuthService;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;
import com.smartforum.sync.ForumWebSocketListener;
import com.smartforum.sync.OfflineSyncManager;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.Component;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;

public class MainWindow extends JFrame {

    private static final Color PRIMARY = new Color(0x66, 0x7E, 0xEA);

    private final AuthUser               user;
    private final AuthService            authService;
    private final ApiClient              api;
    private final LocalCacheDatabase     cache;
    private final OfflineSyncManager     syncManager;
    private final ForumWebSocketListener wsListener;

    private final JLabel         connectionBadge = new JLabel();
    private final ObjectMapper    mapper          = new ObjectMapper();
    private boolean               wasOnline       = false;
    private TopicListPanel        topicListPanel;

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

        // ── WebSocket ─────────────────────────────────────────────────────
        wsListener = new ForumWebSocketListener(conversationPanel);
        wsListener.connect();

        TopicListPanel topicListPanel = new TopicListPanel(cache, user, syncManager, topic -> {
            conversationPanel.loadTopic(topic);
            wsListener.subscribeTopic(topic.id);
        });
        this.topicListPanel = topicListPanel;

        // ── Extra panels ──────────────────────────────────────────────────
        StatisticsPanel  statisticsPanel  = new StatisticsPanel(api, cache);
        DashboardPanel   dashboardPanel   = new DashboardPanel(api, user);
        GroupsPanel      groupsPanel      = new GroupsPanel(api, user);
        ProfilePanel     profilePanel     = new ProfilePanel(api, user);
        QuizPanel        quizPanel        = new QuizPanel(api, user);
        LecturerAnalyticsPanel lecturerAnalyticsPanel =
            (user.isLecturer() || user.isAdmin()) ? new LecturerAnalyticsPanel(api) : null;

        // ── Sync listener ─────────────────────────────────────────────────
        syncManager.setSyncListener(() -> {
            topicListPanel.refresh();
            conversationPanel.refreshPosts();
            conversationPanel.setStatus("✅ Sync complete");
            statisticsPanel.loadData();
            dashboardPanel.loadData();
        });

        // ── Layout ────────────────────────────────────────────────────────
        setTitle("Smart Discussion Forum — " + user.getName());
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setSize(1200, 720);
        setLocationRelativeTo(null);

        JSplitPane split = new JSplitPane(
            JSplitPane.HORIZONTAL_SPLIT, topicListPanel, conversationPanel);
        split.setDividerLocation(280);
        split.setDividerSize(4);
        split.setBorder(null);

        JTabbedPane tabs = new JTabbedPane();
        if (user.isAdmin()) {
            AdminDashboardPanel adminDashboardPanel = new AdminDashboardPanel(api, user);
            WarningRegistryPanel warningRegistryPanel = new WarningRegistryPanel(api, user);
            BlacklistLogPanel    blacklistLogPanel    = new BlacklistLogPanel(api, user);
            tabs.addTab("🛡  Admin Dashboard", adminDashboardPanel);
            tabs.addTab("⚠  Warnings",         warningRegistryPanel);
            tabs.addTab("🚫  Blacklists",       blacklistLogPanel);
            tabs.addTab("👤  Profile",           profilePanel);
            adminDashboardPanel.setTabs(tabs);
            syncManager.setSyncListener(() -> adminDashboardPanel.loadData());
            tabs.addChangeListener(e -> {
                Component sel = tabs.getSelectedComponent();
                if (sel == adminDashboardPanel)   adminDashboardPanel.loadData();
                else if (sel == warningRegistryPanel) warningRegistryPanel.loadAll();
                else if (sel == blacklistLogPanel)    blacklistLogPanel.loadAll();
            });
        } else if (user.isLecturer()) {
            LecturerAnalyticsPanel analyticsPanel = new LecturerAnalyticsPanel(api, user);
            LecturerGroupsPanel    groupsLecPanel = new LecturerGroupsPanel(api, user);
            tabs.addTab("🏠  Dashboard",  null); // placeholder, set after tabs built
            tabs.addTab("🎯  Quizzes",    quizPanel);
            tabs.addTab("📊  Analytics", analyticsPanel);
            tabs.addTab("📈  Statistics", statisticsPanel);
            tabs.addTab("💬  Forum",      split);
            tabs.addTab("👥  Groups",     groupsLecPanel);
            tabs.addTab("👤  Profile",    profilePanel);
            LecturerDashboardPanel lecDashboard = new LecturerDashboardPanel(api, user, tabs);
            tabs.setComponentAt(0, lecDashboard);
            syncManager.setSyncListener(() -> {
                topicListPanel.refresh();
                conversationPanel.refreshPosts();
                conversationPanel.setStatus("✅ Sync complete");
                analyticsPanel.loadData();
                statisticsPanel.loadData();
            });
            tabs.addChangeListener(e -> {
                Component sel = tabs.getSelectedComponent();
                if (sel == analyticsPanel) analyticsPanel.loadData();
                else if (sel == statisticsPanel) statisticsPanel.loadData();
                else if (sel == groupsLecPanel) groupsLecPanel.loadGroups();
                else if (sel == quizPanel) quizPanel.loadQuizzes();
            });
        } else {
            tabs.addTab("🏠  Dashboard",  dashboardPanel);
            tabs.addTab("💬  Forum",      split);
            tabs.addTab("🎯  Quizzes",    quizPanel);
            tabs.addTab("📊  Statistics", statisticsPanel);
            tabs.addTab("👥  Groups",     groupsPanel);
            tabs.addTab("👤  Profile",    profilePanel);
            syncManager.setSyncListener(() -> {
                topicListPanel.refresh();
                conversationPanel.refreshPosts();
                conversationPanel.setStatus("✅ Sync complete");
                statisticsPanel.loadData();
                dashboardPanel.loadData();
            });
            tabs.addChangeListener(e -> {
                Component sel = tabs.getSelectedComponent();
                if (sel == statisticsPanel) statisticsPanel.loadData();
                else if (sel == dashboardPanel) dashboardPanel.loadData();
                else if (sel == groupsPanel) groupsPanel.loadGroups();
                else if (sel == quizPanel) quizPanel.loadQuizzes();
            });
        }

        getContentPane().setLayout(new BorderLayout());
        getContentPane().add(buildTopBar(), BorderLayout.NORTH);
        getContentPane().add(tabs,          BorderLayout.CENTER);

        // ── Reconnect poller ──────────────────────────────────────────────
        reconnectPoller.scheduleAtFixedRate(this::checkConnectivity,
            5, 10, TimeUnit.SECONDS);

        // ── Notification poller (every 30s) ───────────────────────────────
        reconnectPoller.scheduleAtFixedRate(this::checkModerationNotifications,
            30, 30, TimeUnit.SECONDS);

        // ── Initial sync on startup ───────────────────────────────────────
        new Thread(() -> {
            syncManager.synchronizeOfflineData();
            SwingUtilities.invokeLater(topicListPanel::refresh);
            checkModerationNotifications();
        }, "startup-sync").start();

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

        // Notifications bell
        JButton notifBtn = new JButton("🔔");
        notifBtn.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        notifBtn.setForeground(Color.WHITE);
        notifBtn.setContentAreaFilled(false);
        notifBtn.setOpaque(false);
        notifBtn.setBorderPainted(false);
        notifBtn.setFocusPainted(false);
        notifBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        notifBtn.setToolTipText("Notifications");
        notifBtn.addActionListener(e -> showNotifications());

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
        right.add(notifBtn);
        right.add(userInfo);
        right.add(logoutBtn);

        bar.add(title, BorderLayout.WEST);
        bar.add(right, BorderLayout.EAST);
        return bar;
    }

    // ── Notifications ─────────────────────────────────────────────────────

    private void showNotifications() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/notifications"));
            }
            @Override protected void done() {
                try {
                    JsonNode list = get();
                    if (!list.isArray() || list.size() == 0) {
                        JOptionPane.showMessageDialog(MainWindow.this,
                            "No notifications.", "Notifications",
                            JOptionPane.INFORMATION_MESSAGE);
                        return;
                    }
                    StringBuilder sb = new StringBuilder();
                    for (JsonNode n : list) {
                        JsonNode data = n.path("data");
                        String type    = data.path("type").asText("");
                        String message = data.path("message").asText(n.path("type").asText());
                        String readAt  = n.path("read_at").asText("");
                        String icon    = type.equals("warning") ? "⚠️" : type.equals("blacklist") ? "🚫" : "🔔";
                        String status  = readAt.isBlank() ? " [NEW]" : "";
                        sb.append(icon).append(status).append(" ").append(message).append("\n");
                    }
                    JTextArea area = new JTextArea(sb.toString(), 10, 40);
                    area.setEditable(false);
                    area.setLineWrap(true);
                    area.setWrapStyleWord(true);
                    area.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                    JOptionPane.showMessageDialog(MainWindow.this,
                        new JScrollPane(area), "Notifications",
                        JOptionPane.INFORMATION_MESSAGE);
                } catch (Exception ex) {
                    JOptionPane.showMessageDialog(MainWindow.this,
                        "Could not load notifications.", "Error",
                        JOptionPane.ERROR_MESSAGE);
                }
            }
        }.execute();
    }

    /** Called once on startup and every 30s — pops up a dialog for unread moderation notifications. */
    private void checkModerationNotifications() {
        try {
            JsonNode list = mapper.readTree(api.get("/notifications"));
            if (!list.isArray() || list.size() == 0) return;
            StringBuilder sb = new StringBuilder();
            for (JsonNode n : list) {
                JsonNode data = n.path("data");
                String type    = data.path("type").asText("");
                String message = data.path("message").asText("");
                String icon = switch (type) {
                    case "warning"   -> "⚠️ WARNING";
                    case "blacklist" -> "🚫 SUSPENDED";
                    case "pinned"    -> "📌 PINNED TOPIC";
                    default          -> "🔔";
                };
                if (!message.isEmpty()) sb.append(icon).append(": ").append(message).append("\n");
            }
            if (sb.length() > 0) {
                final String msg = sb.toString();
                SwingUtilities.invokeLater(() -> {
                    JOptionPane.showMessageDialog(MainWindow.this,
                        msg, "🔔 Notifications", JOptionPane.INFORMATION_MESSAGE);
                    refresh();
                });
            }
        } catch (Exception ignored) {}
    }

    private void refresh() {
        if (topicListPanel != null) SwingUtilities.invokeLater(topicListPanel::refresh);
    }

    // ── Reconnect logic ───────────────────────────────────────────────────

    private void checkConnectivity() {
        boolean online = api.isOnline();
        SwingUtilities.invokeLater(() -> updateBadge(online));

        if (online && !wasOnline) {
            System.out.println("[MainWindow] Reconnected — running synchronizeOfflineData()");
            syncManager.synchronizeOfflineData();
            wsListener.connect();
        }
        wasOnline = online;
    }

    private void updateBadge(boolean online) {
        connectionBadge.setText(online ? "🟢 Online" : "🔴 Offline");
    }
}
