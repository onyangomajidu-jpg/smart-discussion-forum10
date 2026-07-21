package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.table.DefaultTableModel;
import java.awt.*;

/**
 * Mirrors lecturer/analytics.blade.php — Evaluation & Compliance Dashboard.
 * Shows KPI cards, live evaluation roster, compliance tracking, quiz summary.
 */
public class LecturerAnalyticsPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x63, 0x66, 0xF1);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color AMBER    = new Color(0xF5, 0x9E, 0x0B);
    private static final Color PURPLE   = new Color(0x8B, 0x5C, 0xF6);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color DARK     = new Color(0x0F, 0x17, 0x2A);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);

    private final ApiClient    api;
    private final ObjectMapper mapper = new ObjectMapper();

    private JLabel lblTotalQuizzes, lblTotalStudents, lblTotalSubmissions, lblAvgScore;
    private DefaultTableModel rosterModel;
    private JPanel compliancePanel;
    private JLabel lblDraft, lblPublished, lblClosed;
    private JLabel statusLbl;

    public LecturerAnalyticsPanel(ApiClient api) {
        this.api = api;
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
        hero.setBorder(new EmptyBorder(28, 32, 28, 32));
        hero.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));
        hero.setAlignmentX(LEFT_ALIGNMENT);
        JPanel heroLeft = new JPanel();
        heroLeft.setOpaque(false);
        heroLeft.setLayout(new BoxLayout(heroLeft, BoxLayout.Y_AXIS));
        JLabel tag = new JLabel("LECTURER ANALYTICS");
        tag.setFont(new Font("Segoe UI", Font.BOLD, 10));
        tag.setForeground(new Color(150, 150, 200));
        JLabel heroTitle = new JLabel("Evaluation & Compliance Dashboard");
        heroTitle.setFont(new Font("Segoe UI", Font.BOLD, 20));
        heroTitle.setForeground(Color.WHITE);
        JLabel heroSub = new JLabel("Live evaluation roster · Compliance tracking · " + java.time.LocalDate.now());
        heroSub.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        heroSub.setForeground(new Color(160, 160, 200));
        heroLeft.add(tag);
        heroLeft.add(Box.createVerticalStrut(4));
        heroLeft.add(heroTitle);
        heroLeft.add(Box.createVerticalStrut(4));
        heroLeft.add(heroSub);
        hero.add(heroLeft, BorderLayout.WEST);

        // Status + refresh
        statusLbl = new JLabel(" ");
        statusLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        statusLbl.setForeground(MUTED);
        statusLbl.setAlignmentX(LEFT_ALIGNMENT);

        JButton refreshBtn = new JButton("⟳ Refresh");
        styleBtn(refreshBtn, PRIMARY);
        refreshBtn.addActionListener(e -> loadData());

        JPanel headerRow = new JPanel(new BorderLayout());
        headerRow.setBackground(BG);
        headerRow.setAlignmentX(LEFT_ALIGNMENT);
        headerRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));
        headerRow.add(statusLbl, BorderLayout.WEST);
        headerRow.add(refreshBtn, BorderLayout.EAST);

        // KPI row
        JPanel kpiRow = new JPanel(new GridLayout(1, 4, 14, 0));
        kpiRow.setBackground(BG);
        kpiRow.setAlignmentX(LEFT_ALIGNMENT);
        kpiRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 100));

        lblTotalQuizzes     = new JLabel("—");
        lblTotalStudents    = new JLabel("—");
        lblTotalSubmissions = new JLabel("—");
        lblAvgScore         = new JLabel("—");

        kpiRow.add(kpiCard("📋", lblTotalQuizzes,     "Total Quizzes",     PRIMARY));
        kpiRow.add(kpiCard("👥", lblTotalStudents,    "Total Students",    GREEN));
        kpiRow.add(kpiCard("📨", lblTotalSubmissions, "Total Submissions", AMBER));
        kpiRow.add(kpiCard("📊", lblAvgScore,         "Avg Score",         PURPLE));

        // Roster table
        JPanel rosterCard = sectionCard("👥 Live Evaluation Roster", PRIMARY);
        rosterModel = new DefaultTableModel(
            new String[]{"Student", "Email", "Quiz", "Score", "Grade", "Status", "Submitted"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable rosterTable = new JTable(rosterModel);
        rosterTable.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        rosterTable.setRowHeight(28);
        rosterTable.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 11));
        rosterTable.setGridColor(BORDER_C);
        JPanel rosterBody = new JPanel(new BorderLayout());
        rosterBody.setBackground(SURFACE);
        rosterBody.add(new JScrollPane(rosterTable), BorderLayout.CENTER);
        rosterCard.add(rosterBody, BorderLayout.CENTER);
        rosterCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 320));

        // Bottom row: compliance + quiz summary
        JPanel bottomRow = new JPanel(new GridLayout(1, 2, 16, 0));
        bottomRow.setBackground(BG);
        bottomRow.setAlignmentX(LEFT_ALIGNMENT);
        bottomRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 300));

        // Compliance panel
        JPanel complianceCard = sectionCard("🛡 Compliance Tracking Registry", DARK);
        compliancePanel = new JPanel();
        compliancePanel.setLayout(new BoxLayout(compliancePanel, BoxLayout.Y_AXIS));
        compliancePanel.setBackground(SURFACE);
        compliancePanel.setBorder(new EmptyBorder(12, 14, 12, 14));
        JScrollPane compScroll = new JScrollPane(compliancePanel);
        compScroll.setBorder(null);
        complianceCard.add(compScroll, BorderLayout.CENTER);

        // Quiz summary
        JPanel quizSummaryCard = sectionCard("📋 Quiz Summary", AMBER);
        lblDraft     = new JLabel("—");
        lblPublished = new JLabel("—");
        lblClosed    = new JLabel("—");
        JPanel summaryBody = new JPanel();
        summaryBody.setLayout(new BoxLayout(summaryBody, BoxLayout.Y_AXIS));
        summaryBody.setBackground(SURFACE);
        summaryBody.setBorder(new EmptyBorder(12, 14, 12, 14));
        summaryBody.add(summaryRow("✏ Draft Quizzes",     lblDraft,     new Color(0x92, 0x40, 0x0E)));
        summaryBody.add(summaryRow("▶ Published Quizzes", lblPublished, new Color(0x06, 0x5F, 0x46)));
        summaryBody.add(summaryRow("🔒 Closed Quizzes",   lblClosed,    new Color(0x99, 0x1B, 0x1B)));
        quizSummaryCard.add(summaryBody, BorderLayout.CENTER);

        bottomRow.add(complianceCard);
        bottomRow.add(quizSummaryCard);

        body.add(hero);
        body.add(Box.createVerticalStrut(16));
        body.add(headerRow);
        body.add(Box.createVerticalStrut(16));
        body.add(kpiRow);
        body.add(Box.createVerticalStrut(20));
        body.add(rosterCard);
        body.add(Box.createVerticalStrut(20));
        body.add(bottomRow);

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    public void loadData() {
        statusLbl.setText("Loading…");
        statusLbl.setForeground(MUTED);
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/lecturer/analytics"));
            }
            @Override protected void done() {
                try {
                    JsonNode d = get();
                    lblTotalQuizzes.setText(String.valueOf(d.path("total_quizzes").asInt(0)));
                    lblTotalStudents.setText(String.valueOf(d.path("total_students").asInt(0)));
                    lblTotalSubmissions.setText(String.valueOf(d.path("total_submissions").asInt(0)));
                    lblAvgScore.setText(d.path("avg_score").asDouble(0) + "%");
                    lblDraft.setText(String.valueOf(d.path("draft_count").asInt(0)));
                    lblPublished.setText(String.valueOf(d.path("published_count").asInt(0)));
                    lblClosed.setText(String.valueOf(d.path("closed_count").asInt(0)));

                    // Roster
                    rosterModel.setRowCount(0);
                    for (JsonNode r : d.path("roster")) {
                        double pct = r.path("percentage").asDouble(0);
                        rosterModel.addRow(new Object[]{
                            r.path("student_name").asText(),
                            r.path("student_email").asText(),
                            r.path("quiz_title").asText(),
                            r.path("score").asInt(0) + " / " + r.path("max_score").asInt(0),
                            r.path("grade").asText("—"),
                            pct >= 50 ? "✅ Pass" : "❌ Fail",
                            r.path("completed_at").asText("—")
                        });
                    }

                    // Compliance
                    compliancePanel.removeAll();
                    for (JsonNode c : d.path("compliance")) {
                        int rate = c.path("rate").asInt(0);
                        Color fillColor = rate >= 80 ? GREEN : (rate >= 50 ? AMBER : DANGER);
                        JPanel row = new JPanel(new BorderLayout(0, 4));
                        row.setBackground(SURFACE);
                        row.setBorder(new EmptyBorder(8, 0, 8, 0));
                        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 70));
                        JLabel name = new JLabel(c.path("quiz_title").asText());
                        name.setFont(new Font("Segoe UI", Font.BOLD, 12));
                        JLabel meta = new JLabel(c.path("group_size").asInt(0) + " enrolled · " +
                            c.path("submitted").asInt(0) + " submitted · " +
                            c.path("pending").asInt(0) + " pending");
                        meta.setFont(new Font("Segoe UI", Font.PLAIN, 11));
                        meta.setForeground(MUTED);
                        JProgressBar bar = new JProgressBar(0, 100);
                        bar.setValue(rate);
                        bar.setForeground(fillColor);
                        bar.setBackground(BORDER_C);
                        bar.setBorderPainted(false);
                        bar.setPreferredSize(new Dimension(0, 8));
                        JLabel rateLbl = new JLabel(rate + "% compliance");
                        rateLbl.setFont(new Font("Segoe UI", Font.BOLD, 11));
                        rateLbl.setForeground(fillColor);
                        JPanel top = new JPanel(new BorderLayout());
                        top.setOpaque(false);
                        top.add(name, BorderLayout.WEST);
                        top.add(rateLbl, BorderLayout.EAST);
                        row.add(top,  BorderLayout.NORTH);
                        row.add(meta, BorderLayout.CENTER);
                        row.add(bar,  BorderLayout.SOUTH);
                        compliancePanel.add(row);
                        compliancePanel.add(new JSeparator());
                    }
                    if (d.path("compliance").size() == 0) {
                        JLabel empty = new JLabel("No quizzes created yet.");
                        empty.setFont(new Font("Segoe UI", Font.ITALIC, 13));
                        empty.setForeground(MUTED);
                        compliancePanel.add(empty);
                    }
                    compliancePanel.revalidate();
                    compliancePanel.repaint();

                    statusLbl.setText("✓ Live data · last refreshed just now");
                    statusLbl.setForeground(GREEN);
                } catch (Exception e) {
                    statusLbl.setText("✗ Error: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
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

    private JPanel sectionCard(String title, Color accent) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setAlignmentX(LEFT_ALIGNMENT);
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
        card.add(header, BorderLayout.NORTH);
        return card;
    }

    private JPanel summaryRow(String label, JLabel valLbl, Color valColor) {
        JPanel row = new JPanel(new BorderLayout());
        row.setBackground(SURFACE);
        row.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
            new EmptyBorder(12, 0, 12, 0)));
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 44));
        JLabel k = new JLabel(label);
        k.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        k.setForeground(MUTED);
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 20));
        valLbl.setForeground(valColor);
        row.add(k,      BorderLayout.WEST);
        row.add(valLbl, BorderLayout.EAST);
        return row;
    }

    private void styleBtn(JButton btn, Color bg) {
        btn.setFont(new Font("Segoe UI", Font.BOLD, 12));
        btn.setForeground(Color.WHITE);
        btn.setBackground(bg);
        btn.setBorderPainted(false);
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
    }
}
