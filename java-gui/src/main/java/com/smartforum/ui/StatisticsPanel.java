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

/**
 * Statistics Panel (SDD Days 17-18).
 *
 * Fetches data from GET /api/dashboard, renders:
 *   - Bar chart  : Topics Participated, Posts Made, Quiz Attempts
 *   - Pie chart  : Quiz Attempts vs Available Quizzes
 *   - Stat cards : Avg Score, Total Posts, Topics Joined
 *
 * Falls back to the last-cached statistics_cache row when offline.
 */
public class StatisticsPanel extends JPanel {

    private static final Color PRIMARY   = new Color(0x63, 0x66, 0xF1);
    private static final Color SECONDARY = new Color(0x8B, 0x5C, 0xF6);
    private static final Color SUCCESS   = new Color(0x10, 0xB9, 0x81);
    private static final Color WARNING   = new Color(0xF5, 0x9E, 0x0B);
    private static final Color BG        = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE   = Color.WHITE;
    private static final Color BORDER_C  = new Color(0xE2, 0xE8, 0xF0);
    private static final Color TEXT      = new Color(0x0F, 0x17, 0x2A);
    private static final Color MUTED     = new Color(0x64, 0x74, 0x8B);

    private final ApiClient          api;
    private final LocalCacheDatabase cache;
    private final ObjectMapper       mapper = new ObjectMapper();

    // Stat card labels
    private JLabel lblAvgScore, lblTotalPosts, lblTopics, lblAttempts;
    private JLabel lblStatus;

    // Chart containers (replaced on each refresh)
    private JPanel barChartHolder;
    private JPanel pieChartHolder;

    public StatisticsPanel(ApiClient api, LocalCacheDatabase cache) {
        this.api   = api;
        this.cache = cache;
        setBackground(BG);
        setLayout(new BorderLayout());
        buildUI();
        loadData();
    }

    // ── UI skeleton ───────────────────────────────────────────────────────

    private void buildUI() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(28, 28, 48, 28));

        // Header
        JLabel title = new JLabel("Statistics & Reports");
        title.setFont(new Font("Segoe UI", Font.BOLD, 22));
        title.setForeground(TEXT);
        title.setAlignmentX(LEFT_ALIGNMENT);

        lblStatus = new JLabel("Loading…");
        lblStatus.setFont(new Font("Segoe UI", Font.ITALIC, 12));
        lblStatus.setForeground(MUTED);
        lblStatus.setAlignmentX(LEFT_ALIGNMENT);

        // Refresh button
        JButton btnRefresh = new JButton("⟳  Refresh");
        btnRefresh.setFont(new Font("Segoe UI", Font.BOLD, 12));
        btnRefresh.setForeground(Color.WHITE);
        btnRefresh.setBackground(PRIMARY);
        btnRefresh.setBorderPainted(false);
        btnRefresh.setFocusPainted(false);
        btnRefresh.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        btnRefresh.setAlignmentX(LEFT_ALIGNMENT);
        btnRefresh.addActionListener(e -> loadData());

        JPanel headerRow = new JPanel(new BorderLayout());
        headerRow.setBackground(BG);
        headerRow.setAlignmentX(LEFT_ALIGNMENT);
        headerRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
        headerRow.add(title,      BorderLayout.WEST);
        headerRow.add(btnRefresh, BorderLayout.EAST);

        body.add(headerRow);
        body.add(Box.createVerticalStrut(4));
        body.add(lblStatus);
        body.add(Box.createVerticalStrut(20));
        body.add(buildStatCards());
        body.add(Box.createVerticalStrut(20));
        body.add(buildChartRow());

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    // ── Stat cards row ────────────────────────────────────────────────────

    private JPanel buildStatCards() {
        JPanel row = new JPanel(new GridLayout(1, 4, 16, 0));
        row.setBackground(BG);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));

        lblAvgScore  = new JLabel("—");
        lblTotalPosts = new JLabel("—");
        lblTopics    = new JLabel("—");
        lblAttempts  = new JLabel("—");

        row.add(statCard("⭐", lblAvgScore,   "Avg Quiz Score",      WARNING));
        row.add(statCard("📝", lblTotalPosts, "Total Posts",         SECONDARY));
        row.add(statCard("💬", lblTopics,     "Topics Participated", PRIMARY));
        row.add(statCard("🎯", lblAttempts,   "Quiz Attempts",       SUCCESS));
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

    // ── Chart row ─────────────────────────────────────────────────────────

    private JPanel buildChartRow() {
        JPanel row = new JPanel(new GridLayout(1, 2, 18, 0));
        row.setBackground(BG);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setPreferredSize(new Dimension(800, 360));
        row.setMinimumSize(new Dimension(400, 320));
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 360));

        barChartHolder = new JPanel(new BorderLayout());
        barChartHolder.setBackground(SURFACE);
        barChartHolder.setBorder(BorderFactory.createLineBorder(BORDER_C));
        barChartHolder.setPreferredSize(new Dimension(380, 300));

        pieChartHolder = new JPanel(new BorderLayout());
        pieChartHolder.setBackground(SURFACE);
        pieChartHolder.setBorder(BorderFactory.createLineBorder(BORDER_C));
        pieChartHolder.setPreferredSize(new Dimension(380, 300));

        row.add(wrapChart(barChartHolder, "📊  Activity Overview",  PRIMARY));
        row.add(wrapChart(pieChartHolder, "🥧  Quiz Allocation",    SUCCESS));
        return row;
    }

    private JPanel wrapChart(JPanel chartHolder, String title, Color accent) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createLineBorder(BORDER_C));

        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(new Color(0xFA, 0xFB, 0xFF));
        header.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 4, 1, 0, accent),
            new EmptyBorder(10, 16, 10, 16)
        ));
        JLabel lbl = new JLabel(title);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 14));
        lbl.setForeground(TEXT);
        header.add(lbl, BorderLayout.WEST);

        card.add(header,      BorderLayout.NORTH);
        card.add(chartHolder, BorderLayout.CENTER);
        return card;
    }

    // ── Data loading ──────────────────────────────────────────────────────

    public void loadData() {
        lblStatus.setText("Fetching statistics…");
        new SwingWorker<JsonNode, Void>() {
            boolean fromCache = false;

            @Override
            protected JsonNode doInBackground() throws Exception {
                if (api.isOnline()) {
                    String json = api.get("/statistics");
                    JsonNode statsNode = mapper.readTree(json).path("stats");
                    cache.saveStatistics(mapper.writeValueAsString(statsNode));
                    return statsNode;
                }
                // Offline fallback
                String cached = cache.loadStatistics();
                if (cached != null) {
                    fromCache = true;
                    return mapper.readTree(cached);
                }
                return null;
            }

            @Override
            protected void done() {
                try {
                    JsonNode stats = get();
                    if (stats != null) {
                        applyStats(stats);
                        lblStatus.setText(fromCache
                            ? "⚠  Offline — showing cached statistics"
                            : "✓  Live data  ·  last refreshed just now");
                        lblStatus.setForeground(fromCache ? WARNING : SUCCESS);
                    } else {
                        lblStatus.setText("✗  No data available (offline, no cache)");
                        lblStatus.setForeground(new Color(0xEF, 0x44, 0x44));
                    }
                } catch (Exception e) {
                    lblStatus.setText("✗  Error: " + e.getMessage());
                    lblStatus.setForeground(new Color(0xEF, 0x44, 0x44));
                }
            }
        }.execute();
    }

    // ── Apply stats to UI ─────────────────────────────────────────────────

    private void applyStats(JsonNode s) {
        int    topics   = s.path("topicsParticipated").asInt(0);
        int    posts    = s.path("totalPosts").asInt(0);
        int    attempts = s.path("quizAttempts").asInt(0);
        int    avail    = s.path("availableQuizzes").asInt(0);
        double avg      = s.path("avgScore").asDouble(-1);

        lblTopics.setText(String.valueOf(topics));
        lblTotalPosts.setText(String.valueOf(posts));
        lblAttempts.setText(String.valueOf(attempts));
        lblAvgScore.setText(avg >= 0 ? Math.round(avg) + "%" : "—");

        renderBarChart(s.path("postsPerDay"));
        renderPieChart(s.path("scoreDistribution"), attempts, avail);
    }

    // ── Bar chart — posts per day (last 7 days) ───────────────────────────

    private void renderBarChart(JsonNode postsPerDay) {
        DefaultCategoryDataset dataset = new DefaultCategoryDataset();
        String[] days = {"Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"};
        if (postsPerDay != null && postsPerDay.isArray() && postsPerDay.size() > 0) {
            for (JsonNode day : postsPerDay)
                dataset.addValue(day.path("value").asInt(0), "Posts", day.path("label").asText());
        } else {
            // Always render 7 days even with zero data so chart is visible
            for (String d : days)
                dataset.addValue(0, "Posts", d);
        }

        JFreeChart chart = ChartFactory.createBarChart(
            "Posts Per Day (Last 7 Days)", "Day", "Posts",
            dataset, PlotOrientation.VERTICAL, false, true, false
        );
        chart.setBackgroundPaint(SURFACE);
        chart.getPlot().setBackgroundPaint(SURFACE);

        barChartHolder.removeAll();
        ChartPanel cp = new ChartPanel(chart);
        cp.setPreferredSize(new Dimension(380, 300));
        cp.setMinimumDrawWidth(100);
        cp.setMinimumDrawHeight(100);
        cp.setBackground(SURFACE);
        barChartHolder.add(cp, BorderLayout.CENTER);
        barChartHolder.revalidate();
        barChartHolder.repaint();
        SwingUtilities.invokeLater(() -> {
            barChartHolder.revalidate();
            barChartHolder.repaint();
        });
    }

    // ── Pie chart — score distribution (or attempted vs available) ────────

    private void renderPieChart(JsonNode scoreDist, int attempts, int available) {
        DefaultPieDataset<String> dataset = new DefaultPieDataset<>();

        boolean hasScoreDist = scoreDist != null && scoreDist.isObject();
        if (hasScoreDist) {
            // Only add buckets with value > 0 to avoid JFreeChart zero-value exception
            scoreDist.fields().forEachRemaining(e -> {
                int val = e.getValue().asInt(0);
                if (val > 0) dataset.setValue(e.getKey(), val);
            });
        }

        // If score dist is empty, fall back to attempted vs available
        if (dataset.getItemCount() == 0) {
            int att  = Math.max(attempts,  0);
            int avl  = Math.max(available, 0);
            if (att > 0 || avl > 0) {
                if (att > 0) dataset.setValue("Attempted", att);
                if (avl > 0) dataset.setValue("Available",  avl);
            } else {
                // Truly no data — show a placeholder so chart still renders
                dataset.setValue("No attempts yet", 1);
            }
        }

        JFreeChart chart = ChartFactory.createPieChart(
            "Quiz Score Distribution", dataset, true, true, false
        );
        chart.setBackgroundPaint(SURFACE);
        chart.getPlot().setBackgroundPaint(SURFACE);

        pieChartHolder.removeAll();
        ChartPanel cp = new ChartPanel(chart);
        cp.setPreferredSize(new Dimension(380, 300));
        cp.setMinimumDrawWidth(100);
        cp.setMinimumDrawHeight(100);
        cp.setBackground(SURFACE);
        pieChartHolder.add(cp, BorderLayout.CENTER);
        pieChartHolder.revalidate();
        pieChartHolder.repaint();
        SwingUtilities.invokeLater(() -> {
            pieChartHolder.revalidate();
            pieChartHolder.repaint();
        });
    }
}
