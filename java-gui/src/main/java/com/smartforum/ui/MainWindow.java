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
import java.net.URL;
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
        setTitle("Discussion Hub — " + user.getName());
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
            tabs.addTab("🏠  Dashboard",       adminDashboardPanel);
            tabs.addTab("⚠️  Warnings",         warningRegistryPanel);
            tabs.addTab("🚫  Blacklist Log",    blacklistLogPanel);
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
            // Tab titles mirror the Laravel sidebar exactly:
            // fa-house Dashboard | fa-clipboard-list My Quizzes | fa-chart-bar Analytics
            // fa-comments Topic Discussions | fa-people-group Groups | fa-chart-line Statistics | profile
            tabs.addTab("\uD83C\uDFE0  Dashboard",          null); // placeholder, set after tabs built
            tabs.addTab("\uD83D\uDCCB  My Quizzes",         quizPanel);
            tabs.addTab("\uD83D\uDCCA  Analytics",          analyticsPanel);
            tabs.addTab("\uD83D\uDCAC  Forum",              split);
            tabs.addTab("\uD83D\uDC65  Groups",             groupsLecPanel);
            tabs.addTab("\uD83D\uDCC8  Statistics",         statisticsPanel);
            tabs.addTab("\uD83D\uDC64  Profile",            profilePanel);
            LecturerDashboardPanel lecDashboard = new LecturerDashboardPanel(api, user, tabs);
            tabs.setComponentAt(0, lecDashboard);
            syncManager.setSyncListener(() -> {
                topicListPanel.refresh();
                conversationPanel.refreshPosts();
                conversationPanel.setStatus("\u2705 Sync complete");
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
            // Student sidebar mirrors app.blade.php member section exactly:
            // fa-house Dashboard | fa-file-pen My Quizzes | fa-chart-line Analytics
            // fa-people-group Groups  (Forum is embedded inside Dashboard quick-link / separate tab)
            tabs.addTab("\uD83C\uDFE0  Dashboard",  dashboardPanel);
            tabs.addTab("\uD83D\uDCAC  Forum",       split);
            tabs.addTab("\uD83D\uDCDD  My Quizzes", quizPanel);
            tabs.addTab("\uD83D\uDCC8  Analytics",  statisticsPanel);
            tabs.addTab("\uD83D\uDC65  Groups",      groupsPanel);
            tabs.addTab("\uD83D\uDC64  Profile",     profilePanel);
            syncManager.setSyncListener(() -> {
                topicListPanel.refresh();
                conversationPanel.refreshPosts();
                conversationPanel.setStatus("\u2705 Sync complete");
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
        // Load window icon from Laravel server (async)
        new SwingWorker<Image, Void>() {
            @Override protected Image doInBackground() throws Exception {
                return new ImageIcon(new URL(
                    ApiClient.BASE_URL.replace("/api", "") + "/images/forum-favicon.png")).getImage();
            }
            @Override protected void done() {
                try { setIconImage(get()); } catch (Exception ignored) {}
            }
        }.execute();

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
        bar.setBorder(new EmptyBorder(8, 16, 8, 16));

        // Brand: logo image + name (mirrors topnav-brand in app.blade.php)
        JPanel brand = new JPanel(new FlowLayout(FlowLayout.LEFT, 10, 0));
        brand.setOpaque(false);

        JLabel logoLbl = new JLabel("💬");
        logoLbl.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 24));
        new SwingWorker<ImageIcon, Void>() {
            @Override protected ImageIcon doInBackground() throws Exception {
                URL url = new URL(ApiClient.BASE_URL.replace("/api", "") + "/images/forum.png");
                Image img = new ImageIcon(url).getImage().getScaledInstance(36, 36, Image.SCALE_SMOOTH);
                return new ImageIcon(img);
            }
            @Override protected void done() {
                try { logoLbl.setIcon(get()); logoLbl.setText(null); } catch (Exception ignored) {}
            }
        }.execute();

        JPanel namePanel = new JPanel();
        namePanel.setLayout(new BoxLayout(namePanel, BoxLayout.Y_AXIS));
        namePanel.setOpaque(false);
        JLabel nameLabel = new JLabel("Discussion Hub");
        nameLabel.setFont(new Font("Segoe UI", Font.BOLD, 16));
        nameLabel.setForeground(Color.WHITE);
        JLabel subLabel = new JLabel("Assessment Platform");
        subLabel.setFont(new Font("Segoe UI", Font.PLAIN, 10));
        subLabel.setForeground(new Color(255, 255, 255, 180));
        namePanel.add(nameLabel);
        namePanel.add(subLabel);

        brand.add(logoLbl);
        brand.add(namePanel);

        String roleIcon  = user.isAdmin() ? "\uD83D\uDEE1" : user.isLecturer() ? "\uD83D\uDCCB" : "\uD83C\uDF93";
        String roleLabel = user.isAdmin() ? "Admin"        : user.isLecturer() ? "Lecturer"    : "Student";

        connectionBadge.setFont(new Font("Segoe UI", Font.BOLD, 12));
        connectionBadge.setForeground(Color.WHITE);
        updateBadge(api.isOnline());

        // Notifications bell
        JButton notifBtn = new JButton("🔔");
        notifBtn.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 14));
        notifBtn.setForeground(Color.WHITE);
        notifBtn.setContentAreaFilled(false);
        notifBtn.setOpaque(false);
        notifBtn.setBorderPainted(false);
        notifBtn.setFocusPainted(false);
        notifBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        notifBtn.setToolTipText("Notifications");
        notifBtn.addActionListener(e -> showNotifications());

        // User chip: avatar initial + name + role (mirrors topnav-profile)
        JPanel userChip = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        userChip.setOpaque(false);
        userChip.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(new Color(255, 255, 255, 60), 1),
            new EmptyBorder(4, 8, 4, 8)));

        // Avatar: first letter of name, replaced by server image if avatar is set
        JLabel avatar = new JLabel(String.valueOf(user.getName().charAt(0)).toUpperCase(), SwingConstants.CENTER) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(new Color(255, 255, 255, 50));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 9, 9);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        avatar.setFont(new Font("Segoe UI", Font.BOLD, 14));
        avatar.setForeground(Color.WHITE);
        avatar.setOpaque(false);
        avatar.setPreferredSize(new Dimension(34, 34));
        avatar.setBorder(new EmptyBorder(4, 8, 4, 8));
        if (user.getAvatar() != null && !user.getAvatar().isEmpty()) {
            new SwingWorker<ImageIcon, Void>() {
                @Override protected ImageIcon doInBackground() throws Exception {
                    URL url = new URL(ApiClient.BASE_URL.replace("/api", "") + "/storage/" + user.getAvatar());
                    Image img = new ImageIcon(url).getImage().getScaledInstance(34, 34, Image.SCALE_SMOOTH);
                    return new ImageIcon(img);
                }
                @Override protected void done() {
                    try { avatar.setIcon(get()); avatar.setText(null); } catch (Exception ignored) {}
                }
            }.execute();
        }

        JPanel userInfo = new JPanel();
        userInfo.setLayout(new BoxLayout(userInfo, BoxLayout.Y_AXIS));
        userInfo.setOpaque(false);
        JLabel userName = new JLabel(user.getName());
        userName.setFont(new Font("Segoe UI", Font.BOLD, 13));
        userName.setForeground(Color.WHITE);
        JLabel userRole = new JLabel(roleIcon + " " + roleLabel);
        userRole.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 10));
        userRole.setForeground(new Color(255, 255, 255, 180));
        userInfo.add(userName);
        userInfo.add(userRole);

        userChip.add(avatar);
        userChip.add(userInfo);

        // Sign Out button (mirrors topnav-logout-btn)
        JButton logoutBtn = new JButton("🚪 Sign Out");
        logoutBtn.setFont(new Font("Segoe UI Emoji", Font.BOLD, 12));
        logoutBtn.setForeground(Color.WHITE);
        logoutBtn.setBackground(new Color(255, 255, 255, 40));
        logoutBtn.setBorderPainted(true);
        logoutBtn.setBorder(BorderFactory.createLineBorder(new Color(255, 255, 255, 80), 1));
        logoutBtn.setFocusPainted(false);
        logoutBtn.setOpaque(true);
        logoutBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        logoutBtn.addActionListener(e -> {
            wsListener.disconnect();
            reconnectPoller.shutdownNow();
            authService.logout();
            dispose();
            new LoginWindow(authService, api, cache).setVisible(true);
        });

        JPanel right = new JPanel(new FlowLayout(FlowLayout.RIGHT, 10, 0));
        right.setOpaque(false);
        right.add(connectionBadge);
        right.add(notifBtn);
        right.add(userChip);
        right.add(logoutBtn);

        bar.add(brand, BorderLayout.WEST);
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
                        String type    = n.path("type").asText("info");
                        String message = n.path("message").asText("");
                        boolean unread = !n.path("read").asBoolean(true);
                        String icon    = type.equals("warning") ? "⚠️" : type.equals("blacklist") ? "🚫" : "🔔";
                        String status  = unread ? " [NEW]" : "";
                        sb.append(icon).append(status).append(" ").append(message).append("\n");
                    }
                    JTextArea area = new JTextArea(sb.toString().trim(), 10, 44);
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
                String type    = n.path("type").asText("");
                String message = n.path("message").asText("");
                boolean unread = !n.path("read").asBoolean(true);
                if (!unread || message.isEmpty()) continue;
                String icon = switch (type) {
                    case "warning"   -> "⚠️ WARNING";
                    case "blacklist" -> "🚫 SUSPENDED";
                    default          -> "🔔";
                };
                sb.append(icon).append(": ").append(message).append("\n");
            }
            if (sb.length() > 0) {
                final String msg = sb.toString();
                SwingUtilities.invokeLater(() ->
                    JOptionPane.showMessageDialog(MainWindow.this,
                        msg, "🔔 New Notifications", JOptionPane.WARNING_MESSAGE));
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
