package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.cache.LocalCacheDatabase;
import org.jfree.chart.ChartFactory;
import org.jfree.chart.ChartPanel;
import org.jfree.chart.JFreeChart;
import org.jfree.chart.plot.PlotOrientation;
import org.jfree.data.category.DefaultCategoryDataset;
import org.jfree.data.general.DefaultPieDataset;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

public class StatisticsPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x63, 0x66, 0xF1);
    private static final Color PURPLE   = new Color(0x8B, 0x5C, 0xF6);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color AMBER    = new Color(0xF5, 0x9E, 0x0B);
    private static final Color BLUE     = new Color(0x1D, 0x4E, 0xD8);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color DARK     = new Color(0x1E, 0x1B, 0x4B);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);

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
        chartsRow.add(wrapChart(pieChartHolder, "🥧 Subject Allocation",       PURPLE));

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
                new EmptyBorder(16, 18, 16, 18))));
        JLabel ico = new JLabel(icon);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 20));
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 28));
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
        body.add(qsRow("🧮 Total Quizzes Taken", lblQsTotalAttempts));
        body.add(qsRow("⬆ Best Score",           lblQsBestScore));
        body.add(qsRow("⬇ Lowest Score",         lblQsLowestScore));
        body.add(qsRow("✏ Total Posts",           lblQsTotalPosts));
        body.add(qsRow("💬 Topics Joined",        lblQsTopicsJoined));
        body.add(qsRow("📚 Subjects Covered",     lblQsSubjects));

        card.add(header, BorderLayout.NORTH);
        card.add(body,   BorderLayout.CENTER);
        return card;
    }

    private JPanel qsRow(String label, JLabel valLbl) {
        JPanel row = new JPanel(new BorderLayout());
        row.setBackground(SURFACE);
        row.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, new Color(0xF1, 0xF5, 0xF9)),
            new EmptyBorder(9, 0, 9, 0)));
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 38));
        JLabel k = new JLabel(label);
        k.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        k.setForeground(MUTED);
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        valLbl.setForeground(TEXT);
        row.add(k,      BorderLayout.WEST);
        row.add(valLbl, BorderLayout.EAST);
        return row;
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
        JsonNode quiz  = s.path("quiz");
        JsonNode forum = s.path("forum");

        int    totalAttempts   = quiz.path("total_attempts").asInt(0);
        double avgScore        = quiz.path("average_score").asDouble(0);
        int    completionRate  = quiz.path("completion_rate").asInt(0);
        int    maxScore        = quiz.path("max_score").asInt(0);
        int    minScore        = quiz.path("min_score").asInt(0);
        int    topicsJoined    = forum.path("topics_joined").asInt(0);
        int    totalPosts      = forum.path("total_posts").asInt(0);
        int    subjectCount    = s.path("subject_allocation").size();

        // KPI cards
        lblQuizzesTaken.setText(String.valueOf(totalAttempts));
        lblAvgScore.setText(Math.round(avgScore) + "%");
        lblCompletionRate.setText(completionRate + "%");
        lblTopicsJoined.setText(String.valueOf(topicsJoined));

        // Progress bars
        barCompletion.setValue(completionRate);  lblCompPct.setText(completionRate + "%");
        barAvgScore.setValue((int) Math.round(avgScore)); lblAvgPct.setText(Math.round(avgScore) + "%");
        barBestScore.setValue(maxScore);         lblBestPct.setText(maxScore + "%");
        int engPct = Math.min(totalPosts * 5, 100);
        barEngagement.setValue(engPct);          lblEngPct.setText(totalPosts + " posts");

        // Quick stats
        lblQsTotalAttempts.setText(String.valueOf(totalAttempts));
        lblQsBestScore.setText(maxScore + "%");
        lblQsLowestScore.setText(minScore + "%");
        lblQsTotalPosts.setText(String.valueOf(totalPosts));
        lblQsTopicsJoined.setText(String.valueOf(topicsJoined));
        lblQsSubjects.setText(String.valueOf(subjectCount));

        // Charts
        renderBarChart(s.path("weekly_performance"));
        renderPieChart(s.path("subject_allocation"), totalAttempts,
            s.path("quiz").path("available_quizzes").asInt(0));
    }

    private void renderBarChart(JsonNode weekly) {
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        if (weekly != null && weekly.isArray() && weekly.size() > 0) {
            for (JsonNode w : weekly)
                dataset.addValue(w.path("avg_score").asDouble(0), "Avg Score",
                    w.path("date").asText());
        } else {
            String[] days = {"Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"};
            for (String d : days) dataset.addValue(0, "Avg Score", d);
        }
        JFreeChart chart = ChartFactory.createBarChart(
            "Weekly Performance Trend", "Day", "Score %",
            dataset, PlotOrientation.VERTICAL, false, true, false);
        chart.setBackgroundPaint(SURFACE);
        chart.getPlot().setBackgroundPaint(SURFACE);
        barChartHolder.removeAll();
        ChartPanel cp = new ChartPanel(chart);
        cp.setPreferredSize(new Dimension(360, 240));
        cp.setMinimumDrawWidth(100);
        cp.setMinimumDrawHeight(100);
        barChartHolder.add(cp, BorderLayout.CENTER);
        barChartHolder.revalidate();
        barChartHolder.repaint();
    }

    private void renderPieChart(JsonNode subjects, int attempts, int available) {
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();
        if (subjects != null && subjects.isArray() && subjects.size() > 0) {
            for (JsonNode sa : subjects) {
                int val = sa.path("attempts").asInt(0);
                if (val > 0) dataset.setValue(sa.path("subject").asText("Unknown"), val);
            }
        }
        if (dataset.getItemCount() == 0) {
            if (attempts > 0) dataset.setValue("Attempted", attempts);
            if (available > 0) dataset.setValue("Available", available);
            if (dataset.getItemCount() == 0) dataset.setValue("No data yet", 1);
        }
        JFreeChart chart = ChartFactory.createPieChart(
            "Quiz Subject Allocation", dataset, true, true, false);
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
