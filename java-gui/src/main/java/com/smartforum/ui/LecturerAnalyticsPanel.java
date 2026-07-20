package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.table.DefaultTableModel;
import java.awt.*;

public class LecturerAnalyticsPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x63, 0x66, 0xF1);
    private static final Color PURPLE   = new Color(0x8B, 0x5C, 0xF6);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color AMBER    = new Color(0xF5, 0x9E, 0x0B);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);
    private static final Color DARK     = new Color(0x1E, 0x1B, 0x4B);

    private final ApiClient    api;
    private final AuthUser     user;
    private final ObjectMapper mapper = new ObjectMapper();

    // KPI labels
    private JLabel lblTotalQuizzes, lblTotalStudents, lblTotalSubmissions, lblAvgScore;

    // Quiz summary labels
    private JLabel lblDraft, lblPublished, lblClosed;

    // Tables
    private DefaultTableModel rosterModel;
    private DefaultTableModel complianceModel;

    private JLabel statusLbl;

    public LecturerAnalyticsPanel(ApiClient api, AuthUser user) {
        this.api  = api;
        this.user = user;
        setBackground(BG);
        setLayout(new BorderLayout());
        buildUI();
        loadData();
    }

    private void buildUI() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(24, 24, 40, 24));

        // Hero
        JPanel hero = new JPanel(new BorderLayout());
        hero.setBackground(DARK);
        hero.setBorder(new EmptyBorder(24, 28, 24, 28));
        hero.setMaximumSize(new Dimension(Integer.MAX_VALUE, 100));
        hero.setAlignmentX(LEFT_ALIGNMENT);
        JPanel heroLeft = new JPanel();
        heroLeft.setOpaque(false);
        heroLeft.setLayout(new BoxLayout(heroLeft, BoxLayout.Y_AXIS));
        JLabel tag = new JLabel("Lecturer Analytics");
        tag.setFont(new Font("Segoe UI", Font.BOLD, 11));
        tag.setForeground(new Color(160, 160, 200));
        JLabel heroTitle = new JLabel("Evaluation & Compliance Dashboard");
        heroTitle.setFont(new Font("Segoe UI", Font.BOLD, 20));
        heroTitle.setForeground(Color.WHITE);
        JLabel heroSub = new JLabel("Live evaluation roster · Compliance tracking registry");
        heroSub.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        heroSub.setForeground(new Color(160, 160, 200));
        heroLeft.add(tag);
        heroLeft.add(Box.createVerticalStrut(4));
        heroLeft.add(heroTitle);
        heroLeft.add(Box.createVerticalStrut(4));
        heroLeft.add(heroSub);
        JLabel heroUser = new JLabel(user.getName());
        heroUser.setFont(new Font("Segoe UI", Font.BOLD, 14));
        heroUser.setForeground(Color.WHITE);
        hero.add(heroLeft,  BorderLayout.WEST);
        hero.add(heroUser,  BorderLayout.EAST);

        // Status + refresh
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

        // KPI cards
        JPanel kpiRow = new JPanel(new GridLayout(1, 4, 14, 0));
        kpiRow.setBackground(BG);
        kpiRow.setAlignmentX(LEFT_ALIGNMENT);
        kpiRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));
        lblTotalQuizzes    = new JLabel("—");
        lblTotalStudents   = new JLabel("—");
        lblTotalSubmissions= new JLabel("—");
        lblAvgScore        = new JLabel("—");
        kpiRow.add(kpiCard("📋", lblTotalQuizzes,     "Total Quizzes",    PRIMARY));
        kpiRow.add(kpiCard("👥", lblTotalStudents,    "Total Students",   GREEN));
        kpiRow.add(kpiCard("📨", lblTotalSubmissions, "Total Submissions",AMBER));
        kpiRow.add(kpiCard("📊", lblAvgScore,         "Avg Score",        PURPLE));

        // Main content: roster left, right panel
        JPanel mainRow = new JPanel(new GridLayout(1, 2, 16, 0));
        mainRow.setBackground(BG);
        mainRow.setAlignmentX(LEFT_ALIGNMENT);
        mainRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 500));
        mainRow.add(buildRosterCard());
        mainRow.add(buildRightPanel());

        body.add(hero);
        body.add(Box.createVerticalStrut(16));
        body.add(headerRow);
        body.add(Box.createVerticalStrut(16));
        body.add(kpiRow);
        body.add(Box.createVerticalStrut(20));
        body.add(mainRow);

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    private JPanel kpiCard(String icon, JLabel valLbl, String caption, Color accent) {
        JPanel card = new JPanel(new BorderLayout(0, 4));
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, accent),
            BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER_C),
                new EmptyBorder(14, 16, 14, 16))));
        JLabel ico = new JLabel(icon);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 18));
        ico.setForeground(accent);
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 26));
        valLbl.setForeground(accent);
        JLabel lbl = new JLabel(caption.toUpperCase());
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 10));
        lbl.setForeground(MUTED);
        JPanel top = new JPanel(new FlowLayout(FlowLayout.LEFT, 0, 0));
        top.setOpaque(false);
        top.add(ico);
        card.add(top,    BorderLayout.NORTH);
        card.add(valLbl, BorderLayout.CENTER);
        card.add(lbl,    BorderLayout.SOUTH);
        return card;
    }

    private JPanel buildRosterCard() {
        rosterModel = new DefaultTableModel(
            new String[]{"Student", "Email", "Quiz", "Score", "Grade", "Status", "Submitted"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable table = new JTable(rosterModel);
        table.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        table.setRowHeight(28);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 11));
        table.setGridColor(BORDER_C);

        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, PRIMARY),
            BorderFactory.createLineBorder(BORDER_C)));
        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(PRIMARY);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel("👥 Live Evaluation Roster");
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        header.add(lbl, BorderLayout.WEST);
        JLabel liveBadge = new JLabel("● Live");
        liveBadge.setFont(new Font("Segoe UI", Font.BOLD, 11));
        liveBadge.setForeground(new Color(0x4A, 0xDE, 0x80));
        header.add(liveBadge, BorderLayout.EAST);
        card.add(header, BorderLayout.NORTH);
        card.add(new JScrollPane(table), BorderLayout.CENTER);
        return card;
    }

    private JPanel buildRightPanel() {
        JPanel panel = new JPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBackground(BG);

        // Compliance tracking
        complianceModel = new DefaultTableModel(
            new String[]{"Quiz", "Status", "Enrolled", "Submitted", "Pending", "Rate"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable compTable = new JTable(complianceModel);
        compTable.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        compTable.setRowHeight(26);
        compTable.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 11));
        compTable.setGridColor(BORDER_C);

        JPanel compCard = sectionCard("🛡 Compliance Tracking Registry", DARK, new JScrollPane(compTable));
        compCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 220));
        compCard.setAlignmentX(LEFT_ALIGNMENT);

        // Quiz summary
        lblDraft     = new JLabel("—");
        lblPublished = new JLabel("—");
        lblClosed    = new JLabel("—");
        JPanel summaryBody = new JPanel();
        summaryBody.setLayout(new BoxLayout(summaryBody, BoxLayout.Y_AXIS));
        summaryBody.setBackground(SURFACE);
        summaryBody.setBorder(new EmptyBorder(8, 14, 8, 14));
        summaryBody.add(quizSummaryRow("✏ Draft Quizzes",     "Not yet published", lblDraft,     new Color(0xFE, 0xF3, 0xC7), new Color(0x92, 0x40, 0x0E)));
        summaryBody.add(quizSummaryRow("▶ Published Quizzes", "Active & available", lblPublished, new Color(0xD1, 0xFA, 0xE5), new Color(0x06, 0x5F, 0x46)));
        summaryBody.add(quizSummaryRow("🔒 Closed Quizzes",   "Past deadline",      lblClosed,    new Color(0xFE, 0xE2, 0xE2), new Color(0x99, 0x1B, 0x1B)));
        JPanel summaryCard = sectionCard("📋 Quiz Summary", AMBER, summaryBody);
        summaryCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 200));
        summaryCard.setAlignmentX(LEFT_ALIGNMENT);

        panel.add(compCard);
        panel.add(Box.createVerticalStrut(14));
        panel.add(summaryCard);
        return panel;
    }

    private JPanel quizSummaryRow(String label, String sub, JLabel valLbl, Color bg, Color fg) {
        JPanel row = new JPanel(new BorderLayout(10, 0));
        row.setBackground(SURFACE);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 52));
        row.setBorder(BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C));
        JPanel iconBox = new JPanel();
        iconBox.setPreferredSize(new Dimension(36, 36));
        iconBox.setBackground(bg);
        JPanel textPanel = new JPanel();
        textPanel.setOpaque(false);
        textPanel.setLayout(new BoxLayout(textPanel, BoxLayout.Y_AXIS));
        JLabel lbl = new JLabel(label);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        lbl.setForeground(TEXT);
        JLabel sublbl = new JLabel(sub);
        sublbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        sublbl.setForeground(MUTED);
        textPanel.add(lbl);
        textPanel.add(sublbl);
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 18));
        valLbl.setForeground(fg);
        row.add(iconBox,   BorderLayout.WEST);
        row.add(textPanel, BorderLayout.CENTER);
        row.add(valLbl,    BorderLayout.EAST);
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

    public void loadData() {
        statusLbl.setText("Loading…");
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/lecturer/analytics"));
            }
            @Override protected void done() {
                try {
                    applyData(get());
                    statusLbl.setText("Last refreshed: " + java.time.LocalTime.now().withNano(0));
                    statusLbl.setForeground(MUTED);
                } catch (Exception e) {
                    statusLbl.setText("Failed to load: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
    }

    private void applyData(JsonNode d) {
        lblTotalQuizzes.setText(String.valueOf(d.path("total_quizzes").asInt()));
        lblTotalStudents.setText(String.valueOf(d.path("total_students").asInt()));
        lblTotalSubmissions.setText(String.valueOf(d.path("total_submissions").asInt()));
        lblAvgScore.setText(d.path("avg_score").asDouble() + "%");
        lblDraft.setText(String.valueOf(d.path("draft_count").asInt()));
        lblPublished.setText(String.valueOf(d.path("published_count").asInt()));
        lblClosed.setText(String.valueOf(d.path("closed_count").asInt()));

        rosterModel.setRowCount(0);
        for (JsonNode r : d.path("roster")) {
            double pct = r.path("percentage").asDouble();
            rosterModel.addRow(new Object[]{
                r.path("student_name").asText(),
                r.path("student_email").asText(),
                r.path("quiz_title").asText(),
                r.path("score").asInt() + " / " + r.path("max_score").asInt(),
                r.path("grade").asText(),
                pct >= 50 ? "Pass" : "Fail",
                r.path("completed_at").asText("—")
            });
        }

        complianceModel.setRowCount(0);
        for (JsonNode c : d.path("compliance")) {
            complianceModel.addRow(new Object[]{
                c.path("quiz_title").asText(),
                c.path("status").asText(),
                c.path("group_size").asInt(),
                c.path("submitted").asInt(),
                c.path("pending").asInt(),
                c.path("rate").asInt() + "%"
            });
        }
    }
}
