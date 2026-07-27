package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.cache.LocalCacheDatabase;
import org.jfree.chart.ChartFactory;
import org.jfree.chart.ChartPanel;
import org.jfree.chart.JFreeChart;
import org.jfree.chart.plot.PlotOrientation;
import org.jfree.chart.plot.XYPlot;
import org.jfree.chart.renderer.xy.XYLineAndShapeRenderer;
import org.jfree.data.category.DefaultCategoryDataset;
import org.jfree.data.general.DefaultPieDataset;
import org.jfree.data.xy.XYSeries;
import org.jfree.data.xy.XYSeriesCollection;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

public class StatisticsPanel extends JPanel {

    // Values now come from Theme (single source of truth shared with every
    // other panel) instead of being re-declared per-file. BLUE and DARK are
    // distinct chart accents (not part of Laravel's root palette).
    private static final Color PRIMARY  = Theme.PRIMARY;
    private static final Color PURPLE   = Theme.SECONDARY;
    private static final Color GREEN    = Theme.SUCCESS;
    private static final Color AMBER    = Theme.WARNING;
    private static final Color BLUE     = new Color(0x1D, 0x4E, 0xD8);
    private static final Color DANGER   = Theme.DANGER;
    private static final Color DARK     = new Color(0x1E, 0x1B, 0x4B);
    private static final Color BG       = Theme.BG;
    private static final Color SURFACE  = Theme.SURFACE;
    private static final Color BORDER_C = Theme.BORDER;
    private static final Color TEXT     = Theme.TEXT;
    private static final Color MUTED    = Theme.MUTED;

    private final ApiClient          api;
    private final LocalCacheDatabase cache;
    private final ObjectMapper       mapper = new ObjectMapper();

    // KPI labels
    private JLabel lblQuizzesTaken, lblAvgScore, lblCompletionRate, lblTopicsJoined;

    // Progress bars
    private JProgressBar barCompletion, barAvgScore, barBestScore, barEngagement;
    private JLabel       lblCompPct, lblAvgPct, lblBestPct, lblEngPct;

    // Quick stats
    private JLabel lblQsBestScore, lblQsLowestScore, lblQsTotalPosts,
                   lblQsTopicsJoined, lblQsSubjects, lblQsTotalAttempts;

    // Charts
    private JPanel barChartHolder, pieChartHolder;

    private JLabel statusLbl;

    public StatisticsPanel(ApiClient api, LocalCacheDatabase cache) {
        this.api   = api;
        this.cache = cache;
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

        // ── Hero ──────────────────────────────────────────────────────────
        JPanel hero = new JPanel(new BorderLayout());
        hero.setBackground(PRIMARY);
        hero.setBorder(new EmptyBorder(28, 32, 28, 32));
        hero.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));
        hero.setAlignmentX(LEFT_ALIGNMENT);

        JPanel heroLeft = new JPanel();
        heroLeft.setOpaque(false);
        heroLeft.setLayout(new BoxLayout(heroLeft, BoxLayout.Y_AXIS));
        JLabel tag = new JLabel("STATISTICS SCREEN");
        tag.setFont(new Font("Segoe UI", Font.BOLD, 10));
        tag.setForeground(new Color(180, 180, 255));
        JLabel heroTitle = new JLabel("Analytics Dashboard");
        heroTitle.setFont(new Font("Segoe UI", Font.BOLD, 22));
        heroTitle.setForeground(Color.WHITE);
        JLabel heroSub = new JLabel("Your performance overview — " + java.time.LocalDate.now());
        heroSub.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        heroSub.setForeground(new Color(200, 200, 255));
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
        headerRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));
        headerRow.add(statusLbl, BorderLayout.WEST);
        headerRow.add(refreshBtn, BorderLayout.EAST);

        // ── KPI cards ─────────────────────────────────────────────────────
        JPanel kpiRow = new JPanel(new GridLayout(1, 4, 14, 0));
        kpiRow.setBackground(BG);
        kpiRow.setAlignmentX(LEFT_ALIGNMENT);
        kpiRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));

        lblQuizzesTaken    = new JLabel("—");
        lblAvgScore        = new JLabel("—");
        lblCompletionRate  = new JLabel("—");
        lblTopicsJoined    = new JLabel("—");

        kpiRow.add(kpiCard("🧮", lblQuizzesTaken,   "Total Quizzes Taken",  PURPLE));
        kpiRow.add(kpiCard("📊", lblAvgScore,        "Average Quiz Score",   BLUE));
        kpiRow.add(kpiCard("✅", lblCompletionRate,  "Completion Rate",      GREEN));
        kpiRow.add(kpiCard("💬", lblTopicsJoined,    "Topics Joined",        AMBER));

        // ── Charts row ────────────────────────────────────────────────────
        JPanel chartsRow = new JPanel(new GridLayout(1, 2, 16, 0));
        chartsRow.setBackground(BG);
        chartsRow.setAlignmentX(LEFT_ALIGNMENT);
        chartsRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 320));

        barChartHolder = new JPanel(new BorderLayout());
        barChartHolder.setBackground(SURFACE);

        pieChartHolder = new JPanel(new BorderLayout());
        pieChartHolder.setBackground(SURFACE);

        chartsRow.add(wrapChart(barChartHolder, "📈 Weekly Performance Trend", PRIMARY));
        chartsRow.add(wrapChart(pieChartHolder, "🥧 Subject Allocation",        PURPLE));

        // ── Bottom row: Progress + Quick Stats ────────────────────────────
        JPanel bottomRow = new JPanel(new GridLayout(1, 2, 16, 0));
        bottomRow.setBackground(BG);
        bottomRow.setAlignmentX(LEFT_ALIGNMENT);
        bottomRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 260));

        bottomRow.add(buildProgressPanel());
        bottomRow.add(buildQuickStatsPanel());

        body.add(hero);
        body.add(Box.createVerticalStrut(16));
        body.add(headerRow);
        body.add(Box.createVerticalStrut(16));
        body.add(kpiRow);
        body.add(Box.createVerticalStrut(20));
        body.add(chartsRow);
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

    private JPanel kpiCard(String icon, JLabel valLbl, String caption, Color accent) {
        // Mirrors .kpi-card + .kpi-icon in analytics/index.blade.php
        JPanel card = new JPanel(new BorderLayout(12, 0));
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, accent),
            BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER_C),
                new EmptyBorder(16, 18, 16, 18))));

        // Gradient icon box
        Color iconBg = new Color(
            Math.min(accent.getRed()   + 160, 255),
            Math.min(accent.getGreen() + 160, 255),
            Math.min(accent.getBlue()  + 160, 255));
        JLabel ico = new JLabel(icon, SwingConstants.CENTER) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(iconBg);
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 14, 14);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 20));
        ico.setForeground(accent);
        ico.setOpaque(false);
        ico.setPreferredSize(new Dimension(48, 48));
        ico.setMinimumSize(new Dimension(48, 48));
        ico.setMaximumSize(new Dimension(48, 48));

        JPanel textPanel = new JPanel();
        textPanel.setOpaque(false);
        textPanel.setLayout(new BoxLayout(textPanel, BoxLayout.Y_AXIS));
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 28));
        valLbl.setForeground(accent);
        JLabel lbl = new JLabel(caption.toUpperCase());
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 10));
        lbl.setForeground(MUTED);
        textPanel.add(valLbl);
        textPanel.add(lbl);

        card.add(ico,       BorderLayout.WEST);
        card.add(textPanel, BorderLayout.CENTER);
        return card;
    }

    private JPanel wrapChart(JPanel holder, String title, Color accent) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, accent),
            BorderFactory.createLineBorder(BORDER_C)));
        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(new Color(0xFA, 0xFB, 0xFF));
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel(title);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(TEXT);
        header.add(lbl, BorderLayout.WEST);
        card.add(header, BorderLayout.NORTH);
        card.add(holder, BorderLayout.CENTER);
        return card;
    }

    private JPanel buildProgressPanel() {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, PRIMARY),
            BorderFactory.createLineBorder(BORDER_C)));

        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(PRIMARY);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel("📊 Progress Summary");
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        header.add(lbl, BorderLayout.WEST);

        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(SURFACE);
        body.setBorder(new EmptyBorder(16, 16, 16, 16));

        barCompletion = new JProgressBar(0, 100);
        barAvgScore   = new JProgressBar(0, 100);
        barBestScore  = new JProgressBar(0, 100);
        barEngagement = new JProgressBar(0, 100);
        lblCompPct = new JLabel("0%");
        lblAvgPct  = new JLabel("0%");
        lblBestPct = new JLabel("0%");
        lblEngPct  = new JLabel("0 posts");

        body.add(progressRow("Quiz Completion Rate", barCompletion, lblCompPct, GREEN));
        body.add(Box.createVerticalStrut(12));
        body.add(progressRow("Average Score",        barAvgScore,   lblAvgPct,  PRIMARY));
        body.add(Box.createVerticalStrut(12));
        body.add(progressRow("Best Score",           barBestScore,  lblBestPct, AMBER));
        body.add(Box.createVerticalStrut(12));
        body.add(progressRow("Forum Engagement",     barEngagement, lblEngPct,  BLUE));

        card.add(header, BorderLayout.NORTH);
        card.add(body,   BorderLayout.CENTER);
        return card;
    }

    private JPanel progressRow(String label, JProgressBar bar, JLabel pctLbl, Color color) {
        JPanel row = new JPanel(new BorderLayout(0, 4));
        row.setBackground(SURFACE);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 46));

        JPanel labelRow = new JPanel(new BorderLayout());
        labelRow.setBackground(SURFACE);
        JLabel nameLbl = new JLabel(label);
        nameLbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        nameLbl.setForeground(TEXT);
        pctLbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        pctLbl.setForeground(MUTED);
        labelRow.add(nameLbl, BorderLayout.WEST);
        labelRow.add(pctLbl,  BorderLayout.EAST);

        bar.setForeground(color);
        bar.setBackground(BORDER_C);
        bar.setBorderPainted(false);
        bar.setPreferredSize(new Dimension(0, 10));

        row.add(labelRow, BorderLayout.NORTH);
        row.add(bar,      BorderLayout.CENTER);
        return row;
    }

    private JPanel buildQuickStatsPanel() {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, PURPLE),
            BorderFactory.createLineBorder(BORDER_C)));

        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(PURPLE);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel("⚡ Quick Stats");
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        header.add(lbl, BorderLayout.WEST);

        lblQsTotalAttempts = new JLabel("—");
        lblQsBestScore     = new JLabel("—");
        lblQsLowestScore   = new JLabel("—");
        lblQsTotalPosts    = new JLabel("—");
        lblQsTopicsJoined  = new JLabel("—");
        lblQsSubjects      = new JLabel("—");

        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(SURFACE);
        body.setBorder(new EmptyBorder(8, 16, 8, 16));
        body.add(qsRow("🧮", PRIMARY, "Total Quizzes Taken", lblQsTotalAttempts));
        body.add(qsRow("⬆",  GREEN,   "Best Score",          lblQsBestScore));
        body.add(qsRow("⬇",  DANGER,  "Lowest Score",        lblQsLowestScore));
        body.add(qsRow("✏",  PURPLE,  "Total Posts",         lblQsTotalPosts));
        body.add(qsRow("💬", BLUE,    "Topics Joined",       lblQsTopicsJoined));
        body.add(qsRow("📚", AMBER,   "Subjects Covered",    lblQsSubjects));

        card.add(header, BorderLayout.NORTH);
        card.add(body,   BorderLayout.CENTER);
        return card;
    }

    private JPanel qsRow(String icon, Color iconColor, String label, JLabel valLbl) {
        // Mirrors .qs-row with .qs-label icon in analytics/index.blade.php
        JPanel row = new JPanel(new BorderLayout(8, 0));
        row.setBackground(SURFACE);
        row.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, new Color(0xF1, 0xF5, 0xF9)),
            new EmptyBorder(9, 0, 9, 0)));
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 38));
        JLabel ico = new JLabel(icon);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 13));
        ico.setForeground(iconColor);
        ico.setPreferredSize(new Dimension(18, 18));
        JLabel k = new JLabel(label);
        k.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        k.setForeground(MUTED);
        JPanel labelPanel = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        labelPanel.setOpaque(false);
        labelPanel.add(ico);
        labelPanel.add(k);
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        valLbl.setForeground(TEXT);
        row.add(labelPanel, BorderLayout.WEST);
        row.add(valLbl,     BorderLayout.EAST);
        return row;
    }

    // ── Export / Download Report bar — mirrors .export-bar in analytics/index.blade.php ──
    private JPanel buildExportBar() {
        JPanel bar = new JPanel(new BorderLayout(16, 0));
        bar.setBackground(SURFACE);
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
        title.setForeground(TEXT);
        JLabel sub = new JLabel("Export your analytics data in your preferred format");
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        sub.setForeground(MUTED);
        textPanel.add(title);
        textPanel.add(Box.createVerticalStrut(3));
        textPanel.add(sub);

        JPanel btns = new JPanel(new FlowLayout(FlowLayout.RIGHT, 8, 0));
        btns.setOpaque(false);

        JButton pdfBtn = makeExportBtn("📄 Export PDF",
            new Color(0xEF,0x44,0x44), new Color(0xDC,0x26,0x26));
        pdfBtn.addActionListener(e -> openExport("pdf"));

        JButton csvBtn = makeExportBtn("📊 Export CSV",
            new Color(0x10,0xB9,0x81), new Color(0x05,0x96,0x69));
        csvBtn.addActionListener(e -> openExport("csv"));

        JButton jsonBtn = makeExportBtn("📋 Export JSON",
            new Color(0xF5,0x9E,0x0B), new Color(0xD9,0x77,0x06));
        jsonBtn.addActionListener(e -> openExport("json"));

        btns.add(pdfBtn); btns.add(csvBtn); btns.add(jsonBtn);
        bar.add(textPanel, BorderLayout.WEST);
        bar.add(btns,      BorderLayout.EAST);
        return bar;
    }

    private JButton makeExportBtn(String text, Color c1, Color c2) {
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
        return btn;
    }

    private void openExport(String format) {
        try {
            String url = ApiClient.BASE_URL.replace("/api", "") +
                "/reports/export?format=" + format + "&type=user";
            java.awt.Desktop.getDesktop().browse(java.net.URI.create(url));
        } catch (Exception ex) {
            JOptionPane.showMessageDialog(this, "Could not open browser: " + ex.getMessage());
        }
    }

    // ── Data loading ──────────────────────────────────────────────────────

    public void loadData() {
        statusLbl.setText("Loading…");
        statusLbl.setForeground(MUTED);
        new SwingWorker<JsonNode, Void>() {
            boolean fromCache = false;

            @Override protected JsonNode doInBackground() throws Exception {
                if (api.isOnline()) {
                    String json = api.get("/statistics");
                    JsonNode node = mapper.readTree(json).path("stats");
                    cache.saveStatistics(mapper.writeValueAsString(node));
                    return node;
                }
                String cached = cache.loadStatistics();
                if (cached != null) { fromCache = true; return mapper.readTree(cached); }
                return null;
            }

            @Override protected void done() {
                try {
                    JsonNode s = get();
                    if (s == null) {
                        statusLbl.setText("✗ No data available (offline, no cache)");
                        statusLbl.setForeground(DANGER);
                        return;
                    }
                    applyStats(s);
                    statusLbl.setText(fromCache
                        ? "⚠ Offline — showing cached data"
                        : "✓ Live data · last refreshed just now");
                    statusLbl.setForeground(fromCache ? AMBER : GREEN);
                } catch (Exception e) {
                    statusLbl.setText("✗ Error: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
    }

    private void applyStats(JsonNode s) {
        // API returns flat structure: topicsParticipated, totalPosts, quizAttempts,
        // availableQuizzes, avgScore, postsPerDay[], scoreDistribution{}
        int    totalAttempts  = s.path("quizAttempts").asInt(0);
        double avgScore       = s.path("avgScore").asDouble(0);
        int    availableQuizzes = s.path("availableQuizzes").asInt(0);
        int    topicsJoined   = s.path("topicsParticipated").asInt(0);
        int    totalPosts     = s.path("totalPosts").asInt(0);
        int    total          = totalAttempts + availableQuizzes;
        int    completionRate = total > 0 ? (int) Math.round(totalAttempts * 100.0 / total) : 0;

        // KPI cards
        lblQuizzesTaken.setText(String.valueOf(totalAttempts));
        lblAvgScore.setText(Math.round(avgScore) + "%");
        lblCompletionRate.setText(completionRate + "%");
        lblTopicsJoined.setText(String.valueOf(topicsJoined));

        // Progress bars
        barCompletion.setValue(completionRate);  lblCompPct.setText(completionRate + "%");
        barAvgScore.setValue((int) Math.round(avgScore)); lblAvgPct.setText(Math.round(avgScore) + "%");
        int engPct = Math.min(totalPosts * 5, 100);
        barEngagement.setValue(engPct);          lblEngPct.setText(totalPosts + " posts");

        // Best/lowest from real API fields
        double bestScore  = s.path("bestScore").asDouble(0);
        double minScore   = s.path("minScore").asDouble(0);
        String bestStr    = totalAttempts > 0 ? Math.round(bestScore) + "%" : "—";
        String lowestStr  = totalAttempts > 0 ? Math.round(minScore)  + "%" : "—";

        barBestScore.setValue((int) Math.round(bestScore));
        lblBestPct.setText(bestStr);

        // Quick stats
        lblQsTotalAttempts.setText(String.valueOf(totalAttempts));
        lblQsBestScore.setText(bestStr);
        lblQsLowestScore.setText(lowestStr);
        lblQsTotalPosts.setText(String.valueOf(totalPosts));
        lblQsTopicsJoined.setText(String.valueOf(topicsJoined));
        JsonNode dist = s.path("scoreDistribution");
        int subjectCount = (dist != null && dist.isObject()) ? dist.size() : 0;
        lblQsSubjects.setText(String.valueOf(subjectCount));

        renderBarChart(s.path("weekly_performance").isMissingNode() ? s.path("postsPerDay") : s.path("weekly_performance"));
        renderPieChart(s.path("subject_allocation").isMissingNode() ? s.path("scoreDistribution") : s.path("subject_allocation"), totalAttempts, availableQuizzes);
    }

    private void renderBarChart(JsonNode data) {
        XYSeries series = new XYSeries("Avg Score");
        if (data != null && data.isArray() && data.size() > 0) {
            int i = 0;
            for (JsonNode w : data)
                series.add(i++, w.path("avg_score").asDouble(w.path("value").asDouble(0)));
        } else {
            for (int i = 0; i < 7; i++) series.add(i, 0);
        }
        XYSeriesCollection dataset = new XYSeriesCollection(series);
        JFreeChart chart = ChartFactory.createXYLineChart(
            "Weekly Performance Trend", "Day", "Avg Score (%)",
            dataset, PlotOrientation.VERTICAL, false, true, false);
        chart.setBackgroundPaint(SURFACE);
        chart.getPlot().setBackgroundPaint(SURFACE);
        XYPlot plot = chart.getXYPlot();
        XYLineAndShapeRenderer renderer = new XYLineAndShapeRenderer(true, true);
        renderer.setSeriesPaint(0, PRIMARY);
        renderer.setSeriesStroke(0, new java.awt.BasicStroke(2.5f));
        plot.setRenderer(renderer);
        barChartHolder.removeAll();
        ChartPanel cp = new ChartPanel(chart);
        cp.setPreferredSize(new Dimension(360, 240));
        cp.setMinimumDrawWidth(100);
        cp.setMinimumDrawHeight(100);
        barChartHolder.add(cp, BorderLayout.CENTER);
        barChartHolder.revalidate();
        barChartHolder.repaint();
    }

    private void renderPieChart(JsonNode subjectData, int attempts, int available) {
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        if (subjectData != null && !subjectData.isMissingNode()) {
            if (subjectData.isArray()) {
                for (JsonNode sa : subjectData) {
                    int val = sa.path("attempts").asInt(0);
                    if (val > 0) dataset.setValue(sa.path("subject").asText("Unknown"), val);
                }
            } else if (subjectData.isObject()) {
                subjectData.fields().forEachRemaining(e -> {
                    int val = e.getValue().asInt(0);
                    if (val > 0) dataset.setValue(e.getKey() + "%", val);
                });
            }
        }
        if (dataset.getItemCount() == 0) {
            if (attempts > 0) dataset.setValue("Attempted", attempts);
            if (available > 0) dataset.setValue("Available", available);
            if (dataset.getItemCount() == 0) dataset.setValue("No data yet", 1);
        }
        JFreeChart chart = ChartFactory.createPieChart(
            "Subject Allocation", dataset, true, true, false);
        chart.setBackgroundPaint(SURFACE);
        chart.getPlot().setBackgroundPaint(SURFACE);
        pieChartHolder.removeAll();
        ChartPanel cp = new ChartPanel(chart);
        cp.setPreferredSize(new Dimension(360, 240));
        cp.setMinimumDrawWidth(100);
        cp.setMinimumDrawHeight(100);
        pieChartHolder.add(cp, BorderLayout.CENTER);
        pieChartHolder.revalidate();
        pieChartHolder.repaint();
    }
}
