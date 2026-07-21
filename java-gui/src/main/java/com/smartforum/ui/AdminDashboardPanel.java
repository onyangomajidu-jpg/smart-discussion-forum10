package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.table.DefaultTableModel;
import java.awt.*;

public class AdminDashboardPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x4F, 0x46, 0xE5);
    private static final Color PURPLE   = new Color(0x8B, 0x5C, 0xF6);
    private static final Color AMBER    = new Color(0xF5, 0x9E, 0x0B);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color CYAN     = new Color(0x06, 0xB6, 0xD4);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);

    private final ApiClient    api;
    private final AuthUser     user;
    private final ObjectMapper mapper = new ObjectMapper();
    private JTabbedPane        tabs;

    // Stat cards
    private JLabel lblMembers, lblLecturers, lblQuizzes, lblWarnings, lblBans;

    // Platform summary
    private JLabel lblTotalUsers, lblTotalGroups, lblTotalQuizzes, lblPublished, lblSubmissions;

    // Recent users table
    private DefaultTableModel usersModel;

    private JLabel statusLbl;

    public AdminDashboardPanel(ApiClient api, AuthUser user) {
        this.api  = api;
        this.user = user;
        setBackground(BG);
        setLayout(new BorderLayout());
        buildUI();
        loadData();
    }

    public void setTabs(JTabbedPane tabs) {
        this.tabs = tabs;
    }

    private void buildUI() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(24, 24, 40, 24));

        // Hero
        JPanel hero = new JPanel(new BorderLayout());
        hero.setBackground(new Color(0x1E, 0x1B, 0x4B));
        hero.setBorder(new EmptyBorder(24, 28, 24, 28));
        hero.setAlignmentX(LEFT_ALIGNMENT);
        hero.setMaximumSize(new Dimension(Integer.MAX_VALUE, 120));

        JPanel heroLeft = new JPanel();
        heroLeft.setOpaque(false);
        heroLeft.setLayout(new BoxLayout(heroLeft, BoxLayout.Y_AXIS));
        JLabel portalLbl = new JLabel("Administrator Portal");
        portalLbl.setFont(new Font("Segoe UI", Font.BOLD, 11));
        portalLbl.setForeground(new Color(160, 160, 200));
        JLabel titleLbl = new JLabel("Admin Dashboard");
        titleLbl.setFont(new Font("Segoe UI", Font.BOLD, 22));
        titleLbl.setForeground(Color.WHITE);
        JLabel subLbl = new JLabel("Monitor users, warnings, bans, and platform activity.");
        subLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        subLbl.setForeground(new Color(180, 180, 210));
        heroLeft.add(portalLbl);
        heroLeft.add(Box.createVerticalStrut(4));
        heroLeft.add(titleLbl);
        heroLeft.add(Box.createVerticalStrut(4));
        heroLeft.add(subLbl);

        JPanel heroRight = new JPanel();
        heroRight.setOpaque(false);
        heroRight.setLayout(new BoxLayout(heroRight, BoxLayout.Y_AXIS));
        JLabel loggedAs = new JLabel("Logged in as");
        loggedAs.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        loggedAs.setForeground(new Color(160, 160, 200));
        loggedAs.setAlignmentX(RIGHT_ALIGNMENT);
        JLabel heroUser = new JLabel(user.getName());
        heroUser.setFont(new Font("Segoe UI", Font.BOLD, 15));
        heroUser.setForeground(Color.WHITE);
        heroUser.setAlignmentX(RIGHT_ALIGNMENT);
        JLabel heroDate = new JLabel(java.time.LocalDate.now()
            .format(java.time.format.DateTimeFormatter.ofPattern("EEE, dd MMM yyyy")));
        heroDate.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        heroDate.setForeground(new Color(160, 160, 200));
        heroDate.setAlignmentX(RIGHT_ALIGNMENT);
        heroRight.add(loggedAs);
        heroRight.add(Box.createVerticalStrut(2));
        heroRight.add(heroUser);
        heroRight.add(Box.createVerticalStrut(2));
        heroRight.add(heroDate);

        hero.add(heroLeft,  BorderLayout.WEST);
        hero.add(heroRight, BorderLayout.EAST);

        // Header row with status + refresh
        statusLbl = new JLabel(" ");
        statusLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        statusLbl.setForeground(MUTED);
        statusLbl.setAlignmentX(LEFT_ALIGNMENT);

        JButton refreshBtn = new JButton("⟳ Refresh");
        refreshBtn.setFont(new Font("Segoe UI", Font.BOLD, 12));
        refreshBtn.setForeground(Color.WHITE);
        refreshBtn.setBackground(PRIMARY);
        refreshBtn.setBorderPainted(false);
        refreshBtn.setFocusPainted(false);
        refreshBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        refreshBtn.addActionListener(e -> loadData());

        JPanel headerRow = new JPanel(new BorderLayout());
        headerRow.setBackground(BG);
        headerRow.setAlignmentX(LEFT_ALIGNMENT);
        headerRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
        headerRow.add(statusLbl,  BorderLayout.WEST);
        headerRow.add(refreshBtn, BorderLayout.EAST);

        body.add(hero);
        body.add(Box.createVerticalStrut(16));
        body.add(headerRow);
        body.add(Box.createVerticalStrut(16));
        body.add(buildStatCards());
        body.add(Box.createVerticalStrut(20));
        body.add(buildBottomRow());

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    // ── 5 stat cards: Members, Lecturers, Quizzes, Open Warnings, Active Bans ──

    private JPanel buildStatCards() {
        JPanel row = new JPanel(new GridLayout(1, 5, 12, 0));
        row.setBackground(BG);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));

        lblMembers   = new JLabel("—");
        lblLecturers = new JLabel("—");
        lblQuizzes   = new JLabel("—");
        lblWarnings  = new JLabel("—");
        lblBans      = new JLabel("—");

        row.add(statCard("👥", lblMembers,   "Members",       PURPLE));
        row.add(statCard("🎓", lblLecturers, "Lecturers",     PRIMARY));
        row.add(statCard("📋", lblQuizzes,   "Quizzes",       CYAN));
        row.add(statCard("⚠️", lblWarnings,  "Open Warnings", AMBER));
        row.add(statCard("🚫", lblBans,      "Active Bans",   DANGER));
        return row;
    }

    private JPanel statCard(String icon, JLabel valLabel, String caption, Color accent) {
        JPanel card = new JPanel(new BorderLayout(0, 4));
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 4, 0, 0, accent),
            BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER_C),
                new EmptyBorder(14, 16, 14, 16))));
        JLabel ico = new JLabel(icon);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 18));
        valLabel.setFont(new Font("Segoe UI", Font.BOLD, 26));
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

    // ── Bottom row: Recent Users | Quick Actions | Platform Summary ──

    private JPanel buildBottomRow() {
        JPanel row = new JPanel(new GridLayout(1, 3, 16, 0));
        row.setBackground(BG);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 420));
        row.add(buildRecentUsersCard());
        row.add(buildQuickActionsCard());
        row.add(buildPlatformSummaryCard());
        return row;
    }

    // Recent Users: Name, Email, Role, Joined, Status
    private JPanel buildRecentUsersCard() {
        usersModel = new DefaultTableModel(
            new String[]{"Name", "Email", "Role", "Joined", "Status"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable table = new JTable(usersModel);
        table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        table.setRowHeight(28);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
        table.setGridColor(BORDER_C);
        return sectionCard("👥 Recent Users", PRIMARY, new JScrollPane(table));
    }

    // Quick Actions: Warning Registry, Blacklist Log, All Quizzes
    private JPanel buildQuickActionsCard() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(SURFACE);
        body.setBorder(new EmptyBorder(12, 14, 12, 14));

        body.add(quickActionRow("⚠️ Warning Registry", "View & resolve warnings", AMBER,  "⚠  Warnings"));
        body.add(Box.createVerticalStrut(8));
        body.add(quickActionRow("🚫 Blacklist Log",     "Manage banned users",     DANGER, "🚫  Blacklists"));

        return sectionCard("⚡ Quick Actions", new Color(0x1E, 0x1B, 0x4B), body);
    }

    private JPanel quickActionRow(String label, String sub, Color accent, String targetTab) {
        JPanel row = new JPanel(new BorderLayout(10, 0));
        row.setBackground(SURFACE);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 56));
        row.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C),
            new EmptyBorder(10, 12, 10, 12)));
        row.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));

        JPanel iconBox = new JPanel();
        iconBox.setPreferredSize(new Dimension(36, 36));
        iconBox.setBackground(accent);

        JPanel text = new JPanel();
        text.setOpaque(false);
        text.setLayout(new BoxLayout(text, BoxLayout.Y_AXIS));
        JLabel lbl = new JLabel(label);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(TEXT);
        JLabel sublbl = new JLabel(sub);
        sublbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        sublbl.setForeground(MUTED);
        text.add(lbl);
        text.add(sublbl);

        row.add(iconBox, BorderLayout.WEST);
        row.add(text,    BorderLayout.CENTER);
        row.addMouseListener(new java.awt.event.MouseAdapter() {
            @Override public void mouseClicked(java.awt.event.MouseEvent e) {
                if (tabs == null) return;
                for (int i = 0; i < tabs.getTabCount(); i++) {
                    if (tabs.getTitleAt(i).trim().equals(targetTab.trim())) {
                        tabs.setSelectedIndex(i);
                        return;
                    }
                }
            }
            @Override public void mouseEntered(java.awt.event.MouseEvent e) {
                row.setBackground(new Color(0xF1, 0xF5, 0xF9));
            }
            @Override public void mouseExited(java.awt.event.MouseEvent e) {
                row.setBackground(SURFACE);
            }
        });
        return row;
    }

    // Platform Summary: Total Users, Total Groups, Total Quizzes, Published, Submissions
    private JPanel buildPlatformSummaryCard() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(SURFACE);
        body.setBorder(new EmptyBorder(16, 16, 16, 16));

        lblTotalUsers   = new JLabel("—");
        lblTotalGroups  = new JLabel("—");
        lblTotalQuizzes = new JLabel("—");
        lblPublished    = new JLabel("—");
        lblSubmissions  = new JLabel("—");

        body.add(summaryRow("Total Users",   lblTotalUsers,   PRIMARY));
        body.add(summaryRow("Total Groups",  lblTotalGroups,  PURPLE));
        body.add(summaryRow("Total Quizzes", lblTotalQuizzes, CYAN));
        body.add(summaryRow("Published",     lblPublished,    GREEN));
        body.add(summaryRow("Submissions",   lblSubmissions,  AMBER));

        return sectionCard("📊 Platform Summary", new Color(0x0F, 0x17, 0x2A), body);
    }

    private JPanel summaryRow(String label, JLabel valLbl, Color color) {
        JPanel row = new JPanel(new BorderLayout());
        row.setBackground(SURFACE);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
        row.setBorder(BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C));
        JLabel lbl = new JLabel(label);
        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        lbl.setForeground(MUTED);
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 14));
        valLbl.setForeground(color);
        row.add(lbl,    BorderLayout.WEST);
        row.add(valLbl, BorderLayout.EAST);
        return row;
    }

    private JPanel sectionCard(String title, Color accent, JComponent content) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, accent),
            BorderFactory.createLineBorder(BORDER_C)));
        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(accent);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel(title);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        header.add(lbl, BorderLayout.WEST);
        card.add(header,  BorderLayout.NORTH);
        card.add(content, BorderLayout.CENTER);
        return card;
    }

    // ── Data loading ──────────────────────────────────────────────────────

    public void loadData() {
        statusLbl.setText("Loading…");
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/admin/stats"));
            }
            @Override protected void done() {
                try {
                    applyStats(get());
                    statusLbl.setText("Last refreshed: " + java.time.LocalTime.now().withNano(0));
                    statusLbl.setForeground(MUTED);
                } catch (Exception e) {
                    statusLbl.setText("Failed to load: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
    }

    private void applyStats(JsonNode s) {
        lblMembers.setText(String.valueOf(s.path("members").asInt()));
        lblLecturers.setText(String.valueOf(s.path("lecturers").asInt()));
        lblQuizzes.setText(String.valueOf(s.path("total_quizzes").asInt()));
        lblWarnings.setText(String.valueOf(s.path("open_warnings").asInt()));
        lblBans.setText(String.valueOf(s.path("active_bans").asInt()));

        lblTotalUsers.setText(String.valueOf(s.path("total_users").asInt()));
        lblTotalGroups.setText(String.valueOf(s.path("total_groups").asInt()));
        lblTotalQuizzes.setText(String.valueOf(s.path("total_quizzes").asInt()));
        lblPublished.setText(String.valueOf(s.path("published_quizzes").asInt()));
        lblSubmissions.setText(String.valueOf(s.path("submissions").asInt()));

        usersModel.setRowCount(0);
        for (JsonNode u : s.path("recent_users")) {
            String joined = u.path("created_at").asText("—");
            usersModel.addRow(new Object[]{
                u.path("name").asText(),
                u.path("email").asText(),
                u.path("role").asText(),
                joined.length() >= 10 ? joined.substring(0, 10) : joined,
                u.path("is_active").asBoolean(true) ? "Active" : "Inactive"
            });
        }
    }
}
