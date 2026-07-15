package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.auth.AuthService;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.*;
import java.awt.*;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;

/**
 * Main dashboard window — matches web dashboard layout exactly:
 *   TOP    : gradient navbar (brand + user chip + sign-out)
 *   LEFT   : sidebar (Main nav, role-specific nav, user footer)
 *   CENTER : stats row (4 cards) + 2×2 panel grid
 */
public class MainWindow extends JFrame {

    // ── Brand colours (mirror web CSS vars) ──────────────────────────────
    private static final Color PRIMARY   = new Color(0x63, 0x66, 0xF1);
    private static final Color SECONDARY = new Color(0x8B, 0x5C, 0xF6);
    private static final Color SUCCESS   = new Color(0x10, 0xB9, 0x81);
    private static final Color WARNING   = new Color(0xF5, 0x9E, 0x0B);
    private static final Color BG        = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE   = Color.WHITE;
    private static final Color BORDER_C  = new Color(0xE2, 0xE8, 0xF0);
    private static final Color TEXT      = new Color(0x0F, 0x17, 0x2A);
    private static final Color MUTED     = new Color(0x64, 0x74, 0x8B);
    private static final Color SIDEBAR_ACTIVE_BG = new Color(0xED, 0xE9, 0xFE);

    private static final int SIDEBAR_W = 220;
    private static final int NAV_H     = 64;

    private final AuthUser           user;
    private final AuthService         authService;
    private final ApiClient           api;
    private final LocalCacheDatabase  cache;
    private final ObjectMapper        mapper = new ObjectMapper();

    // Live stat labels
    private JLabel lblTopics, lblPosts, lblAttempts, lblAvgScore;
    private JPanel topicsListPanel, quizListPanel;
    private JLabel lblEngPct, lblCompPct, lblAvgPct;
    private JProgressBar barEng, barComp, barAvg;

    // Content switcher
    private JPanel contentArea;
    private JScrollPane dashboardView;
    private StatisticsPanel statisticsView;

    // Sidebar nav items for toggling active state
    private JPanel navDashboard, navStatistics;

    public MainWindow(AuthUser user, AuthService authService, LocalCacheDatabase cache) {
        this.user        = user;
        this.authService = authService;
        this.cache       = cache;
        this.api         = new ApiClient();
        this.api.setToken(user.getToken());

        setTitle("Smart Discussion Forum — " + user.getName());
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setSize(1200, 760);
        setMinimumSize(new Dimension(960, 620));
        setLocationRelativeTo(null);

        // Root: navbar on top, sidebar left, content center
        JPanel root = new JPanel(new BorderLayout());
        root.setBackground(BG);
        root.add(buildNavBar(),  BorderLayout.NORTH);
        root.add(buildSidebar(), BorderLayout.WEST);

        contentArea = new JPanel(new CardLayout());
        contentArea.setBackground(BG);
        dashboardView   = buildContent();
        statisticsView  = new StatisticsPanel(api, cache);
        contentArea.add(dashboardView,  "dashboard");
        contentArea.add(statisticsView, "statistics");
        root.add(contentArea, BorderLayout.CENTER);
        setContentPane(root);

        loadDashboardData();
    }

    private void showView(String name) {
        ((CardLayout) contentArea.getLayout()).show(contentArea, name);
        boolean isDash = "dashboard".equals(name);
        setNavActive(navDashboard,  isDash);
        setNavActive(navStatistics, !isDash);
        if (!isDash) statisticsView.loadData();
    }

    private void setNavActive(JPanel item, boolean active) {
        if (item == null) return;
        item.setBackground(active ? SIDEBAR_ACTIVE_BG : SURFACE);
        for (Component c : item.getComponents()) {
            if (c instanceof JLabel) {
                JLabel lbl = (JLabel) c;
                if (lbl.getFont().isBold()) {
                    lbl.setForeground(active ? PRIMARY : MUTED);
                }
            }
        }
    }

    // ── Top navbar ────────────────────────────────────────────────────────

    private JPanel buildNavBar() {
        JPanel bar = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), 0, SECONDARY));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
            }
        };
        bar.setPreferredSize(new Dimension(0, NAV_H));
        bar.setBorder(new EmptyBorder(0, 24, 0, 24));
        bar.setOpaque(false);

        JLabel brand = new JLabel("🎓  SmartForum");
        brand.setFont(new Font("Segoe UI", Font.BOLD, 18));
        brand.setForeground(Color.WHITE);

        JPanel right = new JPanel(new FlowLayout(FlowLayout.RIGHT, 12, 0));
        right.setOpaque(false);

        JLabel bell = new JLabel("🔔");
        bell.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 16));
        bell.setForeground(Color.WHITE);
        bell.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));

        JLabel chip = new JLabel(user.getName() + "  ·  " + capitalize(user.getRole()));
        chip.setFont(new Font("Segoe UI", Font.BOLD, 13));
        chip.setForeground(Color.WHITE);
        chip.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(new Color(255, 255, 255, 80), 1, true),
            new EmptyBorder(4, 12, 4, 12)
        ));

        JButton signOut = navButton("Sign Out");
        signOut.addActionListener(e -> doLogout());

        right.add(bell);
        right.add(chip);
        right.add(signOut);

        bar.add(brand, BorderLayout.WEST);
        bar.add(right,  BorderLayout.EAST);
        return bar;
    }

    // ── Left sidebar ──────────────────────────────────────────────────────

    private JPanel buildSidebar() {
        JPanel sidebar = new JPanel();
        sidebar.setLayout(new BoxLayout(sidebar, BoxLayout.Y_AXIS));
        sidebar.setBackground(SURFACE);
        sidebar.setPreferredSize(new Dimension(SIDEBAR_W, 0));
        sidebar.setBorder(BorderFactory.createMatteBorder(0, 0, 0, 1, BORDER_C));

        // ── Main section ──
        sidebar.add(sidebarSection("Main"));
        navDashboard = sidebarItem("🏠", "Dashboard", true);
        navDashboard.addMouseListener(new MouseAdapter() {
            @Override public void mouseClicked(MouseEvent e) { showView("dashboard"); }
        });
        sidebar.add(navDashboard);
        sidebar.add(sidebarItem("💬", "Topics",        false));
        if ("member".equals(user.getRole())) {
            sidebar.add(sidebarItem("🎯", "Quizzes",   false));
        }
        sidebar.add(sidebarItem("🔔", "Notifications", false));

        navStatistics = sidebarItem("📊", "Statistics", false);
        navStatistics.addMouseListener(new MouseAdapter() {
            @Override public void mouseClicked(MouseEvent e) { showView("statistics"); }
        });
        sidebar.add(navStatistics);

        // ── Role-specific sections ──
        if ("lecturer".equals(user.getRole())) {
            sidebar.add(sidebarSection("Lecturer"));
            sidebar.add(sidebarItem("📊", "Lecturer Panel",  false));
            sidebar.add(sidebarItem("📝", "Manage Quizzes",  false));
        }
        if ("admin".equals(user.getRole())) {
            sidebar.add(sidebarSection("Admin"));
            sidebar.add(sidebarItem("⚙️", "Admin Panel", false));
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

    private JPanel sidebarItem(String icon, String label, boolean active) {
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
    }
}
