package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

/**
 * Dashboard panel — mirrors dashboard.blade.php.
 * Shows stat cards (Topics Joined, Posts Made, Quiz Attempts, Avg Score),
 * recent topics, recent quiz attempts, groups, progress bars, and AI recommendations.
 * Visible to all roles; content adapts per role.
 */
public class DashboardPanel extends JPanel {

    private static final Color PRIMARY   = new Color(0x4F, 0x46, 0xE5);
    private static final Color PURPLE    = new Color(0x8B, 0x5C, 0xF6);
    private static final Color CYAN      = new Color(0x06, 0xB6, 0xD4);
    private static final Color AMBER     = new Color(0xF5, 0x9E, 0x0B);
    private static final Color GREEN     = new Color(0x10, 0xB9, 0x81);
    private static final Color DANGER    = new Color(0xEF, 0x44, 0x44);
    private static final Color BG        = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE   = Color.WHITE;
    private static final Color BORDER_C  = new Color(0xE2, 0xE8, 0xF0);
    private static final Color MUTED     = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT      = new Color(0x0F, 0x17, 0x2A);

    private final ApiClient  api;
    private final AuthUser   user;
    private final ObjectMapper mapper = new ObjectMapper();

    // Stat card value labels
    private JLabel lblTopics, lblPosts, lblAttempts, lblAvg;
    // Progress bars
    private JProgressBar barEngagement, barCompletion, barAvgScore;
    private JLabel lblEngPct, lblCompPct, lblAvgPct;
    // Content panels
    private JPanel recentTopicsPanel, recentAttemptsPanel, groupsPanel, aiPanel;
    private JLabel statusLbl;

    public DashboardPanel(ApiClient api, AuthUser user) {
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

        // Header
        JLabel title = new JLabel("Dashboard");
        title.setFont(new Font("Segoe UI", Font.BOLD, 22));
        title.setForeground(TEXT);
        title.setAlignmentX(LEFT_ALIGNMENT);

        statusLbl = new JLabel("Welcome back, " + user.getName() + "!");
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
        headerRow.add(title, BorderLayout.WEST);
        headerRow.add(refreshBtn, BorderLayout.EAST);

        body.add(headerRow);
        body.add(Box.createVerticalStrut(4));
        body.add(statusLbl);
        body.add(Box.createVerticalStrut(20));
        body.add(buildStatCards());
        body.add(Box.createVerticalStrut(20));
        body.add(buildPanelGrid());
        body.add(Box.createVerticalStrut(20));
        body.add(buildAiPanel());

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

        lblTopics   = new JLabel("—");
        lblPosts    = new JLabel("—");
        lblAttempts = new JLabel("—");
        lblAvg      = new JLabel("—");

        row.add(statCard("💬", lblTopics,   "Topics Joined",  PURPLE));
        row.add(statCard("✏",  lblPosts,    "Posts Made",     PRIMARY));
        row.add(statCard("🎯", lblAttempts, "Quiz Attempts",  GREEN));
        row.add(statCard("⭐", lblAvg,      "Avg Quiz Score", AMBER));
        return row;
    }

    private JPanel statCard(String icon, JLabel valLabel, String caption, Color accent) {
        // Mirrors .dash-stat-card with .stat-icon in dashboard.blade.php
        JPanel card = new JPanel(new BorderLayout(0, 4));
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 4, 0, 0, accent),
            BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(new Color(0xC7, 0xD2, 0xFE)),
                new EmptyBorder(16, 18, 16, 18))));
        // .stat-icon — colored icon at top
        JLabel ico = new JLabel(icon, SwingConstants.LEFT);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 20));
        ico.setForeground(accent);
        valLabel.setFont(new Font("Segoe UI", Font.BOLD, 28));
        valLabel.setForeground(accent);
        JLabel lbl = new JLabel(caption.toUpperCase());
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 10));
        lbl.setForeground(MUTED);
        card.add(ico,      BorderLayout.NORTH);
        card.add(valLabel, BorderLayout.CENTER);
        card.add(lbl,      BorderLayout.SOUTH);
        return card;
    }

    // ── 2×2 panel grid ────────────────────────────────────────────────────

    private JPanel buildPanelGrid() {
        JPanel grid = new JPanel(new GridLayout(2, 2, 16, 16));
        grid.setBackground(BG);
        grid.setAlignmentX(LEFT_ALIGNMENT);
        grid.setMaximumSize(new Dimension(Integer.MAX_VALUE, 520));

        // Topic Participation — mirrors .panel-topics with fa-comments icon
        recentTopicsPanel = new JPanel();
        recentTopicsPanel.setLayout(new BoxLayout(recentTopicsPanel, BoxLayout.Y_AXIS));
        recentTopicsPanel.setBackground(SURFACE);
        recentTopicsPanel.add(emptyState("💬", "No topic participation yet."));
        grid.add(panelCard("💬 Topic Participation", new Color(0x63, 0x66, 0xF1), recentTopicsPanel, true));

        // Quiz Attempts — mirrors .panel-quiz with fa-bullseye icon
        recentAttemptsPanel = new JPanel();
        recentAttemptsPanel.setLayout(new BoxLayout(recentAttemptsPanel, BoxLayout.Y_AXIS));
        recentAttemptsPanel.setBackground(SURFACE);
        recentAttemptsPanel.add(emptyState("📋", "No quiz attempts yet."));
        grid.add(panelCard("🎯 Quiz Attempts", CYAN, recentAttemptsPanel, true));

        // My Groups — mirrors .panel-groups with fa-users icon
        groupsPanel = new JPanel();
        groupsPanel.setLayout(new BoxLayout(groupsPanel, BoxLayout.Y_AXIS));
        groupsPanel.setBackground(SURFACE);
        groupsPanel.add(emptyState("👥", "Not assigned to any group yet."));
        grid.add(panelCard("👥 My Groups", PRIMARY, groupsPanel, true));

        // Statistics Review — mirrors .panel-stats with fa-chart-bar icon
        grid.add(buildStatsReviewCard());

        return grid;
    }

    private JPanel panelCard(String title, Color accent, JPanel contentPanel, boolean withViewAll) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, accent),
            BorderFactory.createLineBorder(BORDER_C)));

        // .panel-header with gradient background
        JPanel header = new JPanel(new BorderLayout());
        header.setOpaque(false);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        // Paint gradient matching Laravel panel headers
        JPanel headerWrap = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, accent, getWidth(), getHeight(),
                    accent.darker()));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
            }
        };
        headerWrap.setOpaque(false);
        headerWrap.add(header, BorderLayout.CENTER);

        JLabel lbl = new JLabel(title);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        header.add(lbl, BorderLayout.WEST);

        JScrollPane scroll = new JScrollPane(contentPanel,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);

        card.add(headerWrap, BorderLayout.NORTH);
        card.add(scroll,     BorderLayout.CENTER);
        return card;
    }

    private JPanel buildStatsReviewCard() {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, DANGER),
            BorderFactory.createLineBorder(BORDER_C)));

        // .panel-stats header — gradient amber→red matching dashboard.blade.php
        JPanel header = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setPaint(new GradientPaint(0, 0, AMBER, getWidth(), 0, DANGER));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
            }
        };
        header.setOpaque(false);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel("📊 Statistics Review");
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        header.add(lbl, BorderLayout.WEST);

        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(SURFACE);
        body.setBorder(new EmptyBorder(16, 16, 16, 16));

        barEngagement = new JProgressBar(0, 100);
        barCompletion = new JProgressBar(0, 100);
        barAvgScore   = new JProgressBar(0, 100);
        lblEngPct  = new JLabel("0%");
        lblCompPct = new JLabel("0%");
        lblAvgPct  = new JLabel("N/A");

        body.add(progressRow("Forum Engagement", barEngagement, lblEngPct,  PRIMARY));
        body.add(Box.createVerticalStrut(14));
        body.add(progressRow("Quiz Completion",  barCompletion, lblCompPct, GREEN));
        body.add(Box.createVerticalStrut(14));
        body.add(progressRow("Average Score",    barAvgScore,   lblAvgPct,  AMBER));

        card.add(header, BorderLayout.NORTH);
        card.add(body,   BorderLayout.CENTER);
        return card;
    }

    private JPanel progressRow(String label, JProgressBar bar, JLabel pctLbl, Color color) {
        JPanel row = new JPanel(new BorderLayout(8, 4));
        row.setBackground(SURFACE);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 50));

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
        bar.setBackground(new Color(0xE2, 0xE8, 0xF0));
        bar.setBorderPainted(false);
        bar.setPreferredSize(new Dimension(0, 8));

        row.add(labelRow, BorderLayout.NORTH);
        row.add(bar,      BorderLayout.CENTER);
        return row;
    }

    // ── AI Recommendations panel (full width) ─────────────────────────────

    private JPanel buildAiPanel() {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setAlignmentX(LEFT_ALIGNMENT);
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, 200));
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, new Color(0xDB, 0x27, 0x77)),
            BorderFactory.createLineBorder(BORDER_C)));

        // .panel-ai header — gradient purple→pink matching dashboard.blade.php
        JPanel header = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setPaint(new GradientPaint(0, 0, new Color(0x7C,0x3A,0xED), getWidth(), 0, new Color(0xDB,0x27,0x77)));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
            }
        };
        header.setOpaque(false);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel("🤖 AI Recommended Topics");
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        header.add(lbl, BorderLayout.WEST);

        aiPanel = new JPanel();
        aiPanel.setLayout(new BoxLayout(aiPanel, BoxLayout.Y_AXIS));
        aiPanel.setBackground(SURFACE);
        aiPanel.setBorder(new EmptyBorder(12, 14, 12, 14));
        aiPanel.add(emptyState("🤖", "Generating personalised recommendations…"));

        JScrollPane scroll = new JScrollPane(aiPanel,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);

        card.add(header, BorderLayout.NORTH);
        card.add(scroll,  BorderLayout.CENTER);
        return card;
    }

    // ── Data loading ──────────────────────────────────────────────────────

    public void loadData() {
        statusLbl.setText("Loading…");
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/dashboard")).path("stats");
            }
            @Override protected void done() {
                try {
                    applyStats(get());
                    statusLbl.setText("Welcome back, " + user.getName() + "! Here's your activity overview.");
                    statusLbl.setForeground(MUTED);
                } catch (Exception e) {
                    statusLbl.setText("Could not load dashboard: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();

        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/recommendations")).path("recommendations");
            }
            @Override protected void done() {
                try { applyRecommendations(get()); } catch (Exception ignored) {}
            }
        }.execute();
    }

    private void applyStats(JsonNode s) {
        int    topics   = s.path("topicsParticipated").asInt(0);
        int    posts    = s.path("totalPosts").asInt(0);
        int    attempts = s.path("quizAttempts").asInt(0);
        int    avail    = s.path("availableQuizzes").asInt(0);
        double avg      = s.path("avgScore").asDouble(-1);

        lblTopics.setText(String.valueOf(topics));
        lblPosts.setText(String.valueOf(posts));
        lblAttempts.setText(String.valueOf(attempts));
        lblAvg.setText(avg >= 0 ? Math.round(avg) + "%" : "—");

        int engPct  = Math.min(posts * 5, 100);
        int total   = attempts + avail;
        int compPct = total > 0 ? (int) Math.round(attempts * 100.0 / total) : 0;
        int avgPct  = avg >= 0 ? (int) Math.round(avg) : 0;

        barEngagement.setValue(engPct);  lblEngPct.setText(engPct + "%");
        barCompletion.setValue(compPct); lblCompPct.setText(compPct + "%");
        barAvgScore.setValue(avgPct);    lblAvgPct.setText(avg >= 0 ? avgPct + "%" : "N/A");

        // Recent topics — mirrors .list-row with .list-dot purple ●
        JsonNode rt = s.path("recentTopics");
        recentTopicsPanel.removeAll();
        if (rt.isArray() && rt.size() > 0) {
            for (JsonNode t : rt) {
                JPanel row = new JPanel(new BorderLayout(8, 0));
                row.setBackground(SURFACE);
                row.setBorder(BorderFactory.createCompoundBorder(
                    BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
                    new EmptyBorder(8, 12, 8, 12)));
                row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 38));
                JLabel dot = new JLabel("●");
                dot.setFont(new Font("Segoe UI", Font.BOLD, 10));
                dot.setForeground(PURPLE);
                JLabel title = new JLabel(t.path("title").asText());
                title.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                title.setForeground(TEXT);
                JLabel arrow = new JLabel("›");
                arrow.setFont(new Font("Segoe UI", Font.BOLD, 14));
                arrow.setForeground(new Color(0xC4, 0xB5, 0xFD));
                row.add(dot,   BorderLayout.WEST);
                row.add(title, BorderLayout.CENTER);
                row.add(arrow, BorderLayout.EAST);
                recentTopicsPanel.add(row);
            }
        } else {
            recentTopicsPanel.add(emptyState("💬", "No topic participation yet."));
        }
        recentTopicsPanel.revalidate();
        recentTopicsPanel.repaint();

        // Recent quiz attempts — mirrors .list-row with ✓ cyan dot + .dash-badge score
        JsonNode ra = s.path("recentAttempts");
        recentAttemptsPanel.removeAll();
        if (ra.isArray() && ra.size() > 0) {
            for (JsonNode a : ra) {
                double sc = a.path("score").asDouble(-1);
                String scoreStr = sc >= 0 ? Math.round(sc) + "%" : "—";
                JPanel row = new JPanel(new BorderLayout(8, 0));
                row.setBackground(SURFACE);
                row.setBorder(BorderFactory.createCompoundBorder(
                    BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
                    new EmptyBorder(8, 12, 8, 12)));
                row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 38));
                JLabel dot = new JLabel("✓");
                dot.setFont(new Font("Segoe UI", Font.BOLD, 10));
                dot.setForeground(CYAN);
                JLabel title = new JLabel(a.path("title").asText());
                title.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                title.setForeground(TEXT);
                // .dash-badge
                JLabel badge = new JLabel(scoreStr);
                badge.setFont(new Font("Segoe UI", Font.BOLD, 11));
                badge.setForeground(MUTED);
                badge.setOpaque(true);
                badge.setBackground(new Color(0xF1, 0xF5, 0xF9));
                badge.setBorder(new EmptyBorder(2, 8, 2, 8));
                row.add(dot,   BorderLayout.WEST);
                row.add(title, BorderLayout.CENTER);
                row.add(badge, BorderLayout.EAST);
                recentAttemptsPanel.add(row);
            }
        } else {
            recentAttemptsPanel.add(emptyState("📋", "No quiz attempts yet."));
        }
        recentAttemptsPanel.revalidate();
        recentAttemptsPanel.repaint();

        // Groups — fetch separately
        loadGroups();
    }

    private void loadGroups() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/groups"));
            }
            @Override protected void done() {
                try {
                    JsonNode groups = get();
                    groupsPanel.removeAll();
                    boolean any = false;
                    for (JsonNode g : groups) {
                        if (!g.path("is_member").asBoolean(false)) continue;
                        any = true;
                        JPanel chip = new JPanel(new BorderLayout(8, 0));
                        chip.setBackground(new Color(0xF1, 0xF5, 0xF9));
                        chip.setBorder(new EmptyBorder(8, 12, 8, 12));
                        chip.setMaximumSize(new Dimension(Integer.MAX_VALUE, 50));

                        // Gradient icon circle — mirrors .g-icon in dashboard.blade.php
                        JLabel gIcon = new JLabel("👥", SwingConstants.CENTER) {
                            @Override protected void paintComponent(Graphics g2d) {
                                Graphics2D g2 = (Graphics2D) g2d.create();
                                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                                g2.setPaint(new GradientPaint(0, 0, new Color(0x63,0x66,0xF1), getWidth(), getHeight(), new Color(0x8B,0x5C,0xF6)));
                                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 8, 8);
                                g2.dispose();
                                super.paintComponent(g2d);
                            }
                        };
                        gIcon.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 14));
                        gIcon.setForeground(Color.WHITE);
                        gIcon.setOpaque(false);
                        gIcon.setPreferredSize(new Dimension(34, 34));

                        JLabel name = new JLabel(g.path("name").asText());
                        name.setFont(new Font("Segoe UI", Font.BOLD, 13));
                        name.setForeground(TEXT);
                        JLabel count = new JLabel(g.path("members_count").asInt(0) + " members");
                        count.setFont(new Font("Segoe UI", Font.PLAIN, 11));
                        count.setForeground(MUTED);
                        chip.add(gIcon, BorderLayout.WEST);
                        chip.add(name,  BorderLayout.CENTER);
                        chip.add(count, BorderLayout.EAST);
                        groupsPanel.add(chip);
                        groupsPanel.add(new JSeparator());
                    }
                    if (!any) groupsPanel.add(emptyState("👥", "Not assigned to any group yet."));
                    groupsPanel.revalidate();
                    groupsPanel.repaint();
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    private void applyRecommendations(JsonNode recs) {
        aiPanel.removeAll();
        if (!recs.isArray() || recs.size() == 0) {
            aiPanel.add(emptyState("🤖", "No recommendations yet — start participating in topics!"));
        } else {
            for (JsonNode r : recs) {
                String title = r.path("title").asText();
                int    score = (int) Math.round(r.path("score").asDouble(0) * 100);
                // .list-row with ★ pink dot + .ai-score — mirrors panel-ai in dashboard.blade.php
                JPanel row = new JPanel(new BorderLayout(8, 0));
                row.setBackground(SURFACE);
                row.setBorder(BorderFactory.createCompoundBorder(
                    BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
                    new EmptyBorder(8, 0, 8, 0)));
                row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 38));
                JLabel dot = new JLabel("★");
                dot.setFont(new Font("Segoe UI Emoji", Font.BOLD, 12));
                dot.setForeground(new Color(0xDB, 0x27, 0x77));
                JLabel t = new JLabel(title);
                t.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                t.setForeground(TEXT);
                // .ai-score badge
                JLabel s = new JLabel(score + "% match");
                s.setFont(new Font("Segoe UI", Font.BOLD, 11));
                s.setForeground(new Color(0xDB, 0x27, 0x77));
                JPanel left = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
                left.setOpaque(false);
                left.add(dot); left.add(t);
                row.add(left, BorderLayout.WEST);
                row.add(s,    BorderLayout.EAST);
                aiPanel.add(row);
            }
        }
        aiPanel.revalidate();
        aiPanel.repaint();
    }

    private JPanel emptyState(String icon, String msg) {
        JPanel p = new JPanel(new BorderLayout());
        p.setBackground(SURFACE);
        p.setBorder(new EmptyBorder(24, 12, 24, 12));
        JLabel lbl = new JLabel("<html><center>" + icon + "<br><font color='#64748b'>" + msg + "</font></center></html>",
            SwingConstants.CENTER);
        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        p.add(lbl, BorderLayout.CENTER);
        return p;
    }
}
