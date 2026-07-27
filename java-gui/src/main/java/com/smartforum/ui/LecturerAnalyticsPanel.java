package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.table.DefaultTableModel;
import java.awt.*;
import java.awt.Image;

/**
 * Mirrors lecturer/analytics.blade.php — Evaluation & Compliance Dashboard.
 * Shows KPI cards, live evaluation roster, compliance tracking, quiz summary.
 */
public class LecturerAnalyticsPanel extends JPanel {

    // Values now come from Theme (single source of truth shared with every
    // other panel) instead of being re-declared per-file. DARK is a distinct
    // near-black accent used for chart labels, not Laravel's --text token.
    private static final Color PRIMARY  = Theme.PRIMARY;
    private static final Color GREEN    = Theme.SUCCESS;
    private static final Color AMBER    = Theme.WARNING;
    private static final Color PURPLE   = Theme.SECONDARY;
    private static final Color DANGER   = Theme.DANGER;
    private static final Color DARK     = Theme.TEXT;
    private static final Color BG       = Theme.BG;
    private static final Color SURFACE  = Theme.SURFACE;
    private static final Color MUTED    = Theme.MUTED;
    private static final Color BORDER_C = Theme.BORDER;

    private final ApiClient    api;
    private final ObjectMapper mapper = new ObjectMapper();

    private JLabel lblTotalQuizzes, lblTotalStudents, lblTotalSubmissions, lblAvgScore;
    private DefaultTableModel rosterModel;
    private JTable rosterTable;
    private JTextField rosterSearchField;
    private JPanel compliancePanel;
    private JLabel lblDraft, lblPublished, lblClosed;
    private JLabel statusLbl;

    public LecturerAnalyticsPanel(ApiClient api) {
        this(api, null);
    }

    public LecturerAnalyticsPanel(ApiClient api, com.smartforum.model.AuthUser user) {
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

        // Hero — mirrors .lec-hero: background:linear-gradient(135deg,#0f172a,#1e1b4b,#312e81)
        JPanel hero = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, new Color(0x0F,0x17,0x2A),
                    getWidth(), getHeight(), new Color(0x31,0x2E,0x81)));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 16, 16);
                // decorative circle — mirrors .lec-hero::before
                g2.setColor(new Color(0x63,0x66,0xF1,38));
                g2.fillOval(getWidth()-130, -60, 220, 220);
                g2.dispose();
            }
        };
        hero.setOpaque(false);
        hero.setBorder(new EmptyBorder(28, 32, 28, 32));
        hero.setMaximumSize(new Dimension(Integer.MAX_VALUE, 120));
        hero.setAlignmentX(LEFT_ALIGNMENT);
        JPanel heroLeft = new JPanel();
        heroLeft.setOpaque(false);
        heroLeft.setLayout(new BoxLayout(heroLeft, BoxLayout.Y_AXIS));
        // mirrors: fa-chart-mixed + "LECTURER ANALYTICS" uppercase tag
        JLabel tag = new JLabel("\uD83D\uDCCA LECTURER ANALYTICS"); // 📊
        tag.setFont(new Font("Segoe UI Emoji", Font.BOLD, 10));
        tag.setForeground(new Color(150, 150, 200, 165));
        JLabel heroTitle = new JLabel("Evaluation & Compliance Dashboard");
        heroTitle.setFont(new Font("Segoe UI", Font.BOLD, 20));
        heroTitle.setForeground(Color.WHITE);
        JLabel heroSub = new JLabel("Live evaluation roster \u00B7 Compliance tracking \u00B7 " + java.time.LocalDate.now());
        heroSub.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        heroSub.setForeground(new Color(160, 160, 200, 190));
        heroLeft.add(tag);
        heroLeft.add(Box.createVerticalStrut(4));
        heroLeft.add(heroTitle);
        heroLeft.add(Box.createVerticalStrut(4));
        heroLeft.add(heroSub);
        // Right: forum.png logo — mirrors asset('images/forum.png') in navbar
        JLabel heroLogo = new JLabel("\uD83D\uDCCA", SwingConstants.CENTER);
        heroLogo.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 48));
        heroLogo.setForeground(new Color(255,255,255,18));
        new SwingWorker<ImageIcon, Void>() {
            @Override protected ImageIcon doInBackground() throws Exception {
                java.net.URL url = new java.net.URL(ApiClient.BASE_URL.replace("/api","") + "/images/forum.png");
                Image img = new ImageIcon(url).getImage().getScaledInstance(52, 52, Image.SCALE_SMOOTH);
                return new ImageIcon(img);
            }
            @Override protected void done() {
                try { heroLogo.setIcon(get()); heroLogo.setText(null); } catch (Exception ignored) {}
            }
        }.execute();
        hero.add(heroLeft,  BorderLayout.WEST);
        hero.add(heroLogo,  BorderLayout.EAST);

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
        kpiRow.add(kpiCard("💯", lblAvgScore,         "Avg Score",         PURPLE));

        // Roster table — mirrors .roster-card with gradient header
        JPanel rosterCard = sectionCard("👥 Live Evaluation Roster", PRIMARY, true);
        rosterModel = new DefaultTableModel(
            new String[]{"Student", "Email", "Quiz", "Score", "Grade", "Status", "Submitted"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        rosterTable = new JTable(rosterModel);
        rosterTable.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        rosterTable.setRowHeight(28);
        rosterTable.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 11));
        rosterTable.setGridColor(BORDER_C);

        // Search bar above roster table
        rosterSearchField = new JTextField();
        rosterSearchField.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        rosterSearchField.putClientProperty("JTextField.placeholderText", "🔍 Search by student name or email…");
        rosterSearchField.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 1),
            new EmptyBorder(6, 10, 6, 10)));
        rosterSearchField.addKeyListener(new java.awt.event.KeyAdapter() {
            @Override public void keyReleased(java.awt.event.KeyEvent e) { filterRoster(); }
        });
        JPanel searchBar = new JPanel(new BorderLayout());
        searchBar.setBackground(new Color(0xFA, 0xFB, 0xFF));
        searchBar.setBorder(new EmptyBorder(8, 10, 8, 10));
        searchBar.add(rosterSearchField, BorderLayout.CENTER);

        JPanel rosterBody = new JPanel(new BorderLayout());
        rosterBody.setBackground(SURFACE);
        rosterBody.add(searchBar, BorderLayout.NORTH);
        rosterBody.add(new JScrollPane(rosterTable), BorderLayout.CENTER);
        rosterCard.add(rosterBody, BorderLayout.CENTER);
        rosterCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 320));

        // Bottom row: compliance + quiz summary
        JPanel bottomRow = new JPanel(new GridLayout(1, 2, 16, 0));
        bottomRow.setBackground(BG);
        bottomRow.setAlignmentX(LEFT_ALIGNMENT);
        bottomRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 300));

        // Compliance panel — mirrors .compliance-card with dark gradient header
        JPanel complianceCard = sectionCard("🛡 Compliance Tracking Registry", DARK, false);
        compliancePanel = new JPanel();
        compliancePanel.setLayout(new BoxLayout(compliancePanel, BoxLayout.Y_AXIS));
        compliancePanel.setBackground(SURFACE);
        compliancePanel.setBorder(new EmptyBorder(12, 14, 12, 14));
        JScrollPane compScroll = new JScrollPane(compliancePanel);
        compScroll.setBorder(null);
        complianceCard.add(compScroll, BorderLayout.CENTER);

        // Quiz summary — mirrors .quiz-summary-card with amber gradient header
        JPanel quizSummaryCard = sectionCard("📋 Quiz Summary", AMBER, false);
        lblDraft     = new JLabel("—");
        lblPublished = new JLabel("—");
        lblClosed    = new JLabel("—");
        JPanel summaryBody = new JPanel();
        summaryBody.setLayout(new BoxLayout(summaryBody, BoxLayout.Y_AXIS));
        summaryBody.setBackground(SURFACE);
        summaryBody.setBorder(new EmptyBorder(12, 14, 12, 14));
        summaryBody.add(summaryRow("✏",  new Color(0xFE,0xF3,0xC7), new Color(0x92,0x40,0x0E), "Draft Quizzes",     "Not yet published",  lblDraft));
        summaryBody.add(summaryRow("▶",  new Color(0xD1,0xFA,0xE5), new Color(0x06,0x5F,0x46), "Published Quizzes", "Active & available", lblPublished));
        summaryBody.add(summaryRow("🔒", new Color(0xFE,0xE2,0xE2), new Color(0x99,0x1B,0x1B), "Closed Quizzes",    "Past deadline",      lblClosed));
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
        body.add(Box.createVerticalStrut(20));
        body.add(buildExportBar());

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
                        JLabel meta = new JLabel("👥 " + c.path("group_size").asInt(0) + " enrolled  ✔ " +
                            c.path("submitted").asInt(0) + " submitted  ⏳ " +
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

    private void filterRoster() {
        String query = rosterSearchField.getText().trim().toLowerCase();
        javax.swing.table.TableRowSorter<DefaultTableModel> sorter =
            new javax.swing.table.TableRowSorter<>(rosterModel);
        rosterTable.setRowSorter(sorter);
        if (query.isEmpty()) {
            sorter.setRowFilter(null);
        } else {
            sorter.setRowFilter(javax.swing.RowFilter.regexFilter(
                "(?i)" + java.util.regex.Pattern.quote(query), 0, 1));
        }
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
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 20));
        ico.setForeground(accent);
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 26));
        valLbl.setForeground(accent);
        JLabel lbl = new JLabel(caption.toUpperCase());
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 10));
        lbl.setForeground(MUTED);
        card.add(ico,    BorderLayout.NORTH);
        card.add(valLbl, BorderLayout.CENTER);
        card.add(lbl,    BorderLayout.SOUTH);
        return card;
    }

    private JPanel sectionCard(String title, Color accent, boolean gradient) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setAlignmentX(LEFT_ALIGNMENT);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, accent),
            BorderFactory.createLineBorder(BORDER_C)));
        JPanel header = gradient ? new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setPaint(new GradientPaint(0, 0, accent, getWidth(), 0, accent.darker()));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
            }
        } : new JPanel(new BorderLayout());
        if (!gradient) header.setBackground(accent);
        else header.setOpaque(false);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel(title);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        header.add(lbl, BorderLayout.WEST);
        card.add(header, BorderLayout.NORTH);
        return card;
    }

    private JPanel summaryRow(String icon, Color iconBg, Color iconFg, String label, String sub, JLabel valLbl) {
        JPanel row = new JPanel(new BorderLayout(12, 0));
        row.setBackground(SURFACE);
        row.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
            new EmptyBorder(12, 0, 12, 0)));
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 56));
        JLabel ico = new JLabel(icon, SwingConstants.CENTER) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(iconBg);
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 10, 10);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 14));
        ico.setForeground(iconFg);
        ico.setOpaque(false);
        ico.setPreferredSize(new Dimension(36, 36));
        JPanel textPanel = new JPanel();
        textPanel.setOpaque(false);
        textPanel.setLayout(new BoxLayout(textPanel, BoxLayout.Y_AXIS));
        JLabel nameLbl = new JLabel(label);
        nameLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        nameLbl.setForeground(DARK);
        JLabel subLbl = new JLabel(sub);
        subLbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        subLbl.setForeground(MUTED);
        textPanel.add(nameLbl);
        textPanel.add(subLbl);
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 20));
        valLbl.setForeground(iconFg);
        row.add(ico,       BorderLayout.WEST);
        row.add(textPanel, BorderLayout.CENTER);
        row.add(valLbl,    BorderLayout.EAST);
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

    // ── Export bar — mirrors .export-bar in lecturer/analytics.blade.php ──
    private JPanel buildExportBar() {
        JPanel bar = new JPanel(new BorderLayout(16, 0));
        bar.setBackground(Color.WHITE);
        bar.setAlignmentX(LEFT_ALIGNMENT);
        bar.setMaximumSize(new Dimension(Integer.MAX_VALUE, 80));
        bar.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C),
            new EmptyBorder(16, 20, 16, 20)));
        JPanel textPanel = new JPanel();
        textPanel.setOpaque(false);
        textPanel.setLayout(new BoxLayout(textPanel, BoxLayout.Y_AXIS));
        JLabel title = new JLabel("📄 Download Report");
        title.setFont(new Font("Segoe UI", Font.BOLD, 14));
        title.setForeground(DARK);
        JLabel sub = new JLabel("Export your analytics data");
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        sub.setForeground(MUTED);
        textPanel.add(title);
        textPanel.add(Box.createVerticalStrut(3));
        textPanel.add(sub);
        JPanel btns = new JPanel(new FlowLayout(FlowLayout.RIGHT, 8, 0));
        btns.setOpaque(false);
        btns.add(makeExportBtn("📄 Export PDF", new Color(0xEF,0x44,0x44), new Color(0xDC,0x26,0x26), "pdf"));
        btns.add(makeExportBtn("📊 Export CSV", new Color(0x10,0xB9,0x81), new Color(0x05,0x96,0x69), "csv"));
        bar.add(textPanel, BorderLayout.WEST);
        bar.add(btns,      BorderLayout.EAST);
        return bar;
    }

    private JButton makeExportBtn(String text, Color c1, Color c2, String format) {
        JButton btn = new JButton(text) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, c1, getWidth(), 0, c2));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 9, 9);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        btn.setFont(new Font("Segoe UI", Font.BOLD, 12));
        btn.setForeground(Color.WHITE);
        btn.setOpaque(false);
        btn.setContentAreaFilled(false);
        btn.setBorderPainted(false);
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        btn.setPreferredSize(new Dimension(120, 36));
        btn.addActionListener(e -> {
            try {
                String url = ApiClient.BASE_URL.replace("/api", "") +
                    "/reports/export?format=" + format + "&type=user";
                java.awt.Desktop.getDesktop().browse(java.net.URI.create(url));
            } catch (Exception ex) {
                JOptionPane.showMessageDialog(LecturerAnalyticsPanel.this,
                    "Could not open browser: " + ex.getMessage());
            }
        });
        return btn;
    }
}
