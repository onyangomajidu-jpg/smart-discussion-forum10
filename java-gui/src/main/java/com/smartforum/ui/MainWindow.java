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

        // ── Statistics panel ──────────────────────────────────────────────
        StatisticsPanel statisticsPanel = new StatisticsPanel(api, cache);

        // ── Sync listener (now statisticsPanel is in scope) ───────────────
        syncManager.setSyncListener(() -> {
            topicListPanel.refresh();
            conversationPanel.refreshPosts();
            conversationPanel.setStatus("✅ Sync complete");
            statisticsPanel.loadData();
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

        JTabbedPane tabs = new JTabbedPane();
        tabs.addTab("💬  Forum",      split);
        tabs.addTab("📊  Statistics", statisticsPanel);
        tabs.addChangeListener(e -> {
            if (tabs.getSelectedComponent() == statisticsPanel)
                statisticsPanel.loadData();
        });

        getContentPane().setLayout(new BorderLayout());
        getContentPane().add(buildTopBar(), BorderLayout.NORTH);
        getContentPane().add(tabs,          BorderLayout.CENTER);

        // ── Reconnect poller ──────────────────────────────────────────────
        reconnectPoller.scheduleAtFixedRate(this::checkConnectivity,
            5, 10, TimeUnit.SECONDS);

        // ── Initial sync on startup ───────────────────────────────────────
        new Thread(() -> {
            syncManager.synchronizeOfflineData();
            SwingUtilities.invokeLater(topicListPanel::refresh);
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
