package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.time.Instant;
import java.util.*;

public class QuizPanel extends JPanel {

    // Values now come from Theme (single source of truth shared with every
    // other panel) instead of being re-declared per-file. CYAN is a distinct
    // accent (not part of Laravel's root palette), so it stays local. DARK
    // and TEXT were duplicate declarations of the same value.
    private static final Color PRIMARY  = Theme.PRIMARY;
    private static final Color PURPLE   = Theme.SECONDARY;
    private static final Color GREEN    = Theme.SUCCESS;
    private static final Color AMBER    = Theme.WARNING;
    private static final Color DANGER   = Theme.DANGER;
    private static final Color CYAN     = new Color(0x06, 0xB6, 0xD4);
    private static final Color DARK     = Theme.TEXT;
    private static final Color BG       = Theme.BG;
    private static final Color SURFACE  = Theme.SURFACE;
    private static final Color MUTED    = Theme.MUTED;
    private static final Color TEXT     = Theme.TEXT;
    private static final Color BORDER_C = Theme.BORDER;

    private final ApiClient    api;
    private final AuthUser     user;
    private final ObjectMapper mapper = new ObjectMapper();

    private JPanel quizListPanel;
    private JLabel statusLbl;
    private String activeFilter = "all";

    // store loaded quiz data for actions
    private final java.util.List<JsonNode> quizzes = new ArrayList<>();
    // countdown timers for upcoming quizzes: quizId -> {timer, label}
    private final Map<Integer, javax.swing.Timer> countdownTimers = new HashMap<>();

    public QuizPanel(ApiClient api, AuthUser user) {
        this.api  = api;
        this.user = user;
        setBackground(BG);
        setLayout(new BorderLayout());
        buildUI();
        loadQuizzes();
    }

    private void buildUI() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(24, 24, 40, 24));

        // Hero banner
        JPanel hero = new JPanel(new BorderLayout());
        hero.setBackground(PRIMARY);
        hero.setBorder(new EmptyBorder(28, 32, 28, 32));
        hero.setMaximumSize(new Dimension(Integer.MAX_VALUE, 120));
        hero.setAlignmentX(LEFT_ALIGNMENT);

        JPanel heroLeft = new JPanel();
        heroLeft.setOpaque(false);
        heroLeft.setLayout(new BoxLayout(heroLeft, BoxLayout.Y_AXIS));
        JLabel heroTitle = new JLabel("📝 My Quizzes");
        heroTitle.setFont(new Font("Segoe UI", Font.BOLD, 22));
        heroTitle.setForeground(Color.WHITE);
        JLabel heroSub = new JLabel("Track your assessments, deadlines, and results all in one place");
        heroSub.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        heroSub.setForeground(new Color(200, 200, 255));
        heroLeft.add(heroTitle);
        heroLeft.add(Box.createVerticalStrut(6));
        heroLeft.add(heroSub);
        hero.add(heroLeft, BorderLayout.WEST);

        if (user.isLecturer() || user.isAdmin()) {
            JButton createBtn = new JButton("➕ Create Quiz");
            createBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
            createBtn.setForeground(PRIMARY);
            createBtn.setBackground(Color.WHITE);
            createBtn.setBorderPainted(false);
            createBtn.setFocusPainted(false);
            createBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            createBtn.addActionListener(e -> showCreateQuizDialog());
            JPanel heroRight = new JPanel(new FlowLayout(FlowLayout.RIGHT, 0, 0));
            heroRight.setOpaque(false);
            heroRight.add(createBtn);
            hero.add(heroRight, BorderLayout.EAST);
        }

        // Status + refresh row
        statusLbl = new JLabel(" ");
        statusLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        statusLbl.setForeground(MUTED);
        statusLbl.setAlignmentX(LEFT_ALIGNMENT);

        JButton refreshBtn = new JButton("⟳ Refresh");
        styleBtn(refreshBtn, PRIMARY);
        refreshBtn.addActionListener(e -> loadQuizzes());

        JPanel headerRow = new JPanel(new BorderLayout());
        headerRow.setBackground(BG);
        headerRow.setAlignmentX(LEFT_ALIGNMENT);
        headerRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));
        headerRow.add(statusLbl, BorderLayout.WEST);
        headerRow.add(refreshBtn, BorderLayout.EAST);

        // Filter bar (student only) — mirrors .filter-bar in quiz/student/index.blade.php
        JPanel filterBar = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 0));
        filterBar.setBackground(BG);
        filterBar.setAlignmentX(LEFT_ALIGNMENT);
        filterBar.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));
        if (!(user.isLecturer() || user.isAdmin())) {
            String[][] filters = {{"all","All"},{"open","● Open"},{"upcoming","⏳ Upcoming"},{"done","✓ Completed"},{"closed","🔒 Closed"}};
            for (String[] f : filters) {
                JButton fb = new JButton(f[1]);
                fb.setFont(new Font("Segoe UI", Font.BOLD, 11));
                fb.setFocusPainted(false);
                fb.setBorderPainted(true);
                fb.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
                updateFilterBtn(fb, f[0].equals(activeFilter));
                fb.addActionListener(e -> {
                    activeFilter = f[0];
                    for (Component c : filterBar.getComponents()) {
                        if (c instanceof JButton b) updateFilterBtn(b, b.getText().equals(fb.getText()));
                    }
                    applyFilter();
                });
                filterBar.add(fb);
            }
        }

        // Quiz list
        quizListPanel = new JPanel();
        quizListPanel.setLayout(new BoxLayout(quizListPanel, BoxLayout.Y_AXIS));
        quizListPanel.setBackground(BG);
        quizListPanel.setAlignmentX(LEFT_ALIGNMENT);

        body.add(hero);
        body.add(Box.createVerticalStrut(16));
        body.add(headerRow);
        body.add(Box.createVerticalStrut(8));
        if (!(user.isLecturer() || user.isAdmin())) {
            body.add(filterBar);
            body.add(Box.createVerticalStrut(8));
        }
        body.add(quizListPanel);

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    public void loadQuizzes() {
        stopAllCountdowns();
        statusLbl.setText("Loading…");
        statusLbl.setForeground(MUTED);
        String endpoint = (user.isLecturer() || user.isAdmin()) ? "/lecturer/quizzes" : "/quizzes";
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get(endpoint));
            }
            @Override protected void done() {
                try {
                    JsonNode data = get();
                    quizzes.clear();
                    quizListPanel.removeAll();
                    if (!data.isArray() || data.size() == 0) {
                        quizListPanel.add(emptyState());
                    } else {
                        for (JsonNode q : data) {
                            quizzes.add(q);
                            quizListPanel.add(buildQuizCard(q));
                            quizListPanel.add(Box.createVerticalStrut(12));
                        }
                    }
                    quizListPanel.revalidate();
                    quizListPanel.repaint();
                    statusLbl.setText("Last refreshed: " + java.time.LocalTime.now().withNano(0));
                    applyFilter();
                } catch (Exception e) {
                    statusLbl.setText("Failed to load: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
    }

    private void stopAllCountdowns() {
        countdownTimers.values().forEach(javax.swing.Timer::stop);
        countdownTimers.clear();
    }

    private void applyFilter() {
        for (Component c : quizListPanel.getComponents()) {
            if (c instanceof JPanel p && p.getClientProperty("state") != null) {
                String state = (String) p.getClientProperty("state");
                p.setVisible(activeFilter.equals("all") || activeFilter.equals(state));
            }
        }
        quizListPanel.revalidate();
        quizListPanel.repaint();
    }

    private void updateFilterBtn(JButton btn, boolean active) {
        if (active) {
            btn.setBackground(PRIMARY);
            btn.setForeground(Color.WHITE);
            btn.setBorder(BorderFactory.createEmptyBorder(5, 14, 5, 14));
        } else {
            btn.setBackground(SURFACE);
            btn.setForeground(MUTED);
            btn.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER_C),
                BorderFactory.createEmptyBorder(4, 13, 4, 13)));
        }
    }

    private JPanel buildQuizCard(JsonNode q) {
        boolean attempted = q.path("attempted").asBoolean(false);
        boolean isOpen    = q.path("is_open").asBoolean(false);
        boolean isUpcoming= q.path("is_upcoming").asBoolean(false);
        boolean isClosed  = !isOpen && !isUpcoming && !attempted;
        // Lecturer uses status field: published/draft/closed
        String lecturerStatus = q.path("status").asText("");

        String state;
        Color accentColor;
        String iconText;
        Color iconBg;
        Color iconFg;

        if (user.isLecturer() || user.isAdmin()) {
            // Mirrors .icon-published/.icon-draft/.icon-closed in quiz/lecturer/index.blade.php
            switch (lecturerStatus) {
                case "published" -> {
                    state = "open"; accentColor = GREEN;
                    iconText = "▶"; iconBg = new Color(0xD1,0xFA,0xE5); iconFg = new Color(0x06,0x5F,0x46);
                }
                case "draft" -> {
                    state = "upcoming"; accentColor = AMBER;
                    iconText = "✏"; iconBg = new Color(0xFE,0xF3,0xC7); iconFg = new Color(0x92,0x40,0x0E);
                }
                default -> {
                    state = "closed"; accentColor = DANGER;
                    iconText = "🔒"; iconBg = new Color(0xFE,0xE2,0xE2); iconFg = new Color(0x99,0x1B,0x1B);
                }
            }
        } else if (attempted) {
            state = "done"; accentColor = PURPLE;
            iconText = "✓"; iconBg = new Color(0xED, 0xE9, 0xFE); iconFg = new Color(0x5B, 0x21, 0xB6);
        } else if (isOpen) {
            state = "open"; accentColor = GREEN;
            iconText = "▶"; iconBg = new Color(0xD1, 0xFA, 0xE5); iconFg = new Color(0x06, 0x5F, 0x46);
        } else if (isUpcoming) {
            state = "upcoming"; accentColor = AMBER;
            iconText = "⏳"; iconBg = new Color(0xFE, 0xF3, 0xC7); iconFg = new Color(0x92, 0x40, 0x0E);
        } else {
            state = "closed"; accentColor = DANGER;
            iconText = "🔒"; iconBg = new Color(0xFE, 0xE2, 0xE2); iconFg = new Color(0x99, 0x1B, 0x1B);
        }

        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setAlignmentX(LEFT_ALIGNMENT);
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 6, 0, 0, accentColor),
            BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER_C),
                new EmptyBorder(16, 16, 16, 16))));

        // Icon — mirrors .quiz-icon-wrap in quiz/student/index.blade.php
        JLabel iconLbl = new JLabel(iconText, SwingConstants.CENTER) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(iconBg);
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 14, 14);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        iconLbl.setFont(new Font("Segoe UI Emoji", Font.BOLD, 20));
        iconLbl.setForeground(iconFg);
        iconLbl.setOpaque(false);
        iconLbl.setPreferredSize(new Dimension(52, 52));
        iconLbl.setBorder(BorderFactory.createEmptyBorder(4, 4, 4, 4));

        // Info
        JPanel info = new JPanel();
        info.setOpaque(false);
        info.setLayout(new BoxLayout(info, BoxLayout.Y_AXIS));

        JPanel titleRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        titleRow.setOpaque(false);
        JLabel titleLbl = new JLabel(q.path("title").asText());
        titleLbl.setFont(new Font("Segoe UI", Font.BOLD, 15));
        titleLbl.setForeground(TEXT);
        JLabel badge = buildBadge(state);
        titleRow.add(titleLbl);
        titleRow.add(badge);

        int quizId = q.path("id").asInt();

        JPanel metaRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 14, 0));
        metaRow.setOpaque(false);
        metaRow.add(metaItem("👥", PRIMARY,  q.path("group_name").asText("—")));
        metaRow.add(metaItem("⏱", AMBER,   q.path("duration_minutes").asInt() + " min"));
        metaRow.add(metaItem("❓", PURPLE,  q.path("questions_count").asInt(0) + " questions"));
        if (user.isLecturer() || user.isAdmin()) {
            metaRow.add(metaItem("👥", GREEN, q.path("attempts_count").asInt(0) + " submissions"));
        }
        String unlockDate = q.path("unlock_date").asText("");
        String deadline   = q.path("hard_deadline").asText("");
        if (!unlockDate.isEmpty() && !unlockDate.equals("null"))
            metaRow.add(metaItem("🔓", GREEN,  "Opens " + formatApiDate(unlockDate)));
        if (!deadline.isEmpty() && !deadline.equals("null"))
            metaRow.add(metaItem("🏁", DANGER, "Due " + formatApiDate(deadline)));

        // Countdown label for upcoming quizzes — mirrors .quiz-countdown in student/index.blade.php
        JLabel countdownLbl = new JLabel("");
        countdownLbl.setFont(new Font("Segoe UI", Font.BOLD, 11));
        countdownLbl.setForeground(AMBER);
        if ("upcoming".equals(state) && !unlockDate.isEmpty() && !unlockDate.equals("null")) {
            startCountdown(quizId, unlockDate, countdownLbl, card, q);
        }

        info.add(titleRow);
        info.add(Box.createVerticalStrut(4));
        info.add(metaRow);
        if (countdownLbl.getText() != null && !countdownLbl.getText().isEmpty())
            info.add(countdownLbl);

        // Action button
        JPanel actionPanel = new JPanel(new FlowLayout(FlowLayout.RIGHT, 6, 0));
        actionPanel.setOpaque(false);

        if (user.isLecturer() || user.isAdmin()) {
            String status = q.path("status").asText("draft");
            JButton viewBtn = new JButton("👁 View");
            styleBtn(viewBtn, PURPLE);
            viewBtn.addActionListener(e -> showQuizDetailDialog(quizId, q));
            actionPanel.add(viewBtn);
            if ("draft".equals(status)) {
                JButton editBtn = new JButton("✏ Edit");
                styleBtn(editBtn, AMBER);
                editBtn.addActionListener(e -> showEditQuizDialog(quizId, q));
                actionPanel.add(editBtn);
                JButton publishBtn = new JButton("📤 Publish");
                styleBtn(publishBtn, GREEN);
                publishBtn.addActionListener(e -> publishQuiz(quizId));
                actionPanel.add(publishBtn);
            } else {
                JButton remindBtn = new JButton("🔔 Remind");
                styleBtn(remindBtn, AMBER);
                remindBtn.addActionListener(e -> sendReminder(quizId, q.path("title").asText()));
                actionPanel.add(remindBtn);
            }
            JButton resultsBtn = new JButton("📊 Results");
            styleBtn(resultsBtn, CYAN);
            resultsBtn.addActionListener(e -> showLecturerResults(quizId, q.path("title").asText()));
            actionPanel.add(resultsBtn);
            JButton deleteBtn = new JButton("🗑");
            styleBtn(deleteBtn, DANGER);
            deleteBtn.setToolTipText("Delete Quiz");
            deleteBtn.addActionListener(e -> deleteQuiz(quizId, q.path("title").asText()));
            actionPanel.add(deleteBtn);
        } else if (attempted) {
            JButton resultBtn = new JButton("📋 View Result");
            styleBtn(resultBtn, PURPLE);
            resultBtn.addActionListener(e -> showResult(quizId));
            actionPanel.add(resultBtn);
        } else if (isOpen) {
            JButton startBtn = new JButton("▶ Start Quiz");
            styleBtn(startBtn, GREEN);
            startBtn.addActionListener(e -> takeQuiz(quizId));
            actionPanel.add(startBtn);
        } else {
            JButton unavailBtn = new JButton("🚫 Unavailable");
            styleBtn(unavailBtn, MUTED);
            unavailBtn.setEnabled(false);
            actionPanel.add(unavailBtn);
        }

        JPanel left = new JPanel(new FlowLayout(FlowLayout.LEFT, 12, 0));
        left.setOpaque(false);
        left.add(iconLbl);
        left.add(info);

        card.add(left,        BorderLayout.CENTER);
        card.add(actionPanel, BorderLayout.EAST);
        card.putClientProperty("state", state);
        return card;
    }

    private String formatApiDate(String iso) {
        try {
            // ISO string from API: "2025-08-01T10:00:00.000000Z" or "2025-08-01 10:00:00"
            String s = iso.replace(" ", "T");
            if (!s.endsWith("Z") && !s.contains("+")) s += "Z";
            java.time.ZonedDateTime zdt = java.time.ZonedDateTime.parse(s)
                .withZoneSameInstant(java.time.ZoneId.systemDefault());
            return zdt.format(java.time.format.DateTimeFormatter.ofPattern("d MMM, HH:mm"));
        } catch (Exception e) {
            return iso.length() > 16 ? iso.substring(0, 16) : iso;
        }
    }

    private void startCountdown(int quizId, String unlockIso, JLabel lbl, JPanel card, JsonNode q) {
        long unlockMs;
        try {
            String s = unlockIso.replace(" ", "T");
            if (!s.endsWith("Z") && !s.contains("+")) s += "Z";
            unlockMs = Instant.parse(s).toEpochMilli();
        } catch (Exception e) { return; }

        javax.swing.Timer t = new javax.swing.Timer(1000, null);
        t.addActionListener(ev -> {
            long diff = unlockMs - System.currentTimeMillis();
            if (diff <= 0) {
                t.stop();
                countdownTimers.remove(quizId);
                // Flip card to open state — reload quizzes to get fresh data
                loadQuizzes();
                return;
            }
            long h = diff / 3600000, m = (diff % 3600000) / 60000, s2 = (diff % 60000) / 1000;
            lbl.setText("⏱ Opens in: " + h + "h " + m + "m " + s2 + "s");
            if (lbl.getParent() != null) lbl.getParent().revalidate();
        });
        long diff0 = unlockMs - System.currentTimeMillis();
        if (diff0 > 0) {
            long h = diff0 / 3600000, m = (diff0 % 3600000) / 60000, s2 = (diff0 % 60000) / 1000;
            lbl.setText("⏱ Opens in: " + h + "h " + m + "m " + s2 + "s");
            t.start();
            countdownTimers.put(quizId, t);
        }
    }

    private JLabel buildBadge(String state) {
        JLabel badge = new JLabel();
        badge.setFont(new Font("Segoe UI", Font.BOLD, 11));
        badge.setBorder(new EmptyBorder(2, 8, 2, 8));
        badge.setOpaque(true);
        switch (state) {
            case "done"     -> { badge.setText("✓ Submitted");  badge.setBackground(new Color(0xED,0xE9,0xFE)); badge.setForeground(new Color(0x5B,0x21,0xB6)); }
            case "open"     -> { badge.setText("● Live Now");   badge.setBackground(new Color(0xD1,0xFA,0xE5)); badge.setForeground(new Color(0x06,0x5F,0x46)); }
            case "upcoming" -> { badge.setText("⏳ Upcoming");  badge.setBackground(new Color(0xFE,0xF3,0xC7)); badge.setForeground(new Color(0x92,0x40,0x0E)); }
            default         -> { badge.setText("🔒 Closed");    badge.setBackground(new Color(0xFE,0xE2,0xE2)); badge.setForeground(new Color(0x99,0x1B,0x1B)); }
        }
        return badge;
    }

    private JLabel metaItem(String icon, Color iconColor, String text) {
        // Mirrors .quiz-meta-item with colored fa icon in quiz/student/index.blade.php
        JLabel l = new JLabel(icon + " " + text);
        l.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 12));
        l.setForeground(MUTED);
        return l;
    }

    private JPanel emptyState() {
        JPanel p = new JPanel(new BorderLayout());
        p.setBackground(SURFACE);
        p.setBorder(new EmptyBorder(60, 20, 60, 20));
        p.setAlignmentX(LEFT_ALIGNMENT);
        p.setMaximumSize(new Dimension(Integer.MAX_VALUE, 200));
        JLabel lbl = new JLabel("<html><center><font size='5'>📭</font><br><br>" +
            "<font color='#0f172a'><b>No Quizzes Available</b></font><br>" +
            "<font color='#64748b'>There are no published quizzes in your groups right now.</font></center></html>",
            SwingConstants.CENTER);
        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        p.add(lbl, BorderLayout.CENTER);
        return p;
    }

    // ── Create quiz (lecturer) ─────────────────────────────────────────────

    public void openCreateDialog() { showCreateQuizDialog(); }

    private void showCreateQuizDialog() {
        JDialog dialog = new JDialog((Frame) SwingUtilities.getWindowAncestor(this),
            "Create New Quiz", true);
        dialog.setSize(900, 720);
        dialog.setLocationRelativeTo(this);

        // ── Hero banner — mirrors .create-hero in quiz/lecturer/create.blade.php ──
        JPanel hero = new JPanel(new BorderLayout(16, 0)) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), PURPLE));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 16, 16);
                g2.dispose();
            }
        };
        hero.setOpaque(false);
        hero.setBorder(new EmptyBorder(22, 28, 22, 28));

        // Icon box — mirrors .hero-icon-box
        JLabel iconBox = new JLabel("✏", SwingConstants.CENTER) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(new Color(255, 255, 255, 50));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 14, 14);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        iconBox.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 24));
        iconBox.setForeground(Color.WHITE);
        iconBox.setOpaque(false);
        iconBox.setPreferredSize(new Dimension(56, 56));

        JPanel heroText = new JPanel();
        heroText.setOpaque(false);
        heroText.setLayout(new BoxLayout(heroText, BoxLayout.Y_AXIS));
        JLabel heroTitle = new JLabel("Create New Quiz");
        heroTitle.setFont(new Font("Segoe UI", Font.BOLD, 20));
        heroTitle.setForeground(Color.WHITE);
        JLabel heroSub = new JLabel("Build your assessment with questions, settings, and deadlines");
        heroSub.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        heroSub.setForeground(new Color(220, 220, 255));
        heroText.add(heroTitle);
        heroText.add(Box.createVerticalStrut(4));
        heroText.add(heroSub);

        hero.add(iconBox,   BorderLayout.WEST);
        hero.add(heroText,  BorderLayout.CENTER);

        // ── Form fields ───────────────────────────────────────────────────
        JTextField tfTitle       = new JTextField();
        JTextArea  taDesc        = new JTextArea(3, 20);
        taDesc.setLineWrap(true); taDesc.setWrapStyleWord(true);
        JComboBox<String> cbGroup = new JComboBox<>();
        java.util.List<Integer> groupIds = new ArrayList<>();
        JSpinner spDuration      = new JSpinner(new SpinnerNumberModel(30, 1, 180, 5));
        JTextField tfUnlock      = new JTextField("yyyy-MM-dd HH:mm");
        JTextField tfDeadline    = new JTextField("yyyy-MM-dd HH:mm");
        JCheckBox  chkAutoSubmit = new JCheckBox("Auto-submit on timer expiry", true);

        // Load groups into combo — all groups, mirrors Group::orderBy('name')->get() in QuizController@create
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/groups"));
            }
            @Override protected void done() {
                try {
                    for (JsonNode g : get()) {
                        groupIds.add(g.path("id").asInt());
                        cbGroup.addItem(g.path("name").asText());
                    }
                } catch (Exception ignored) {}
            }
        }.execute();

        // ── Live summary labels (declared early so questions can update them) ──
        JLabel sumQ = new JLabel("0");  sumQ.setFont(new Font("Segoe UI", Font.BOLD, 15)); sumQ.setForeground(PRIMARY);
        JLabel sumM = new JLabel("0");  sumM.setFont(new Font("Segoe UI", Font.BOLD, 15)); sumM.setForeground(PRIMARY);
        JLabel sumD = new JLabel(spDuration.getValue() + " min"); sumD.setFont(new Font("Segoe UI", Font.BOLD, 15)); sumD.setForeground(PRIMARY);
        spDuration.addChangeListener(e -> sumD.setText(spDuration.getValue() + " min"));

        // ── Questions panel ───────────────────────────────────────────────
        JPanel questionsContainer = new JPanel();
        questionsContainer.setLayout(new BoxLayout(questionsContainer, BoxLayout.Y_AXIS));
        questionsContainer.setBackground(BG);
        java.util.List<QuestionRow> questionRows = new ArrayList<>();

        Runnable updateSummary = () -> {
            sumQ.setText(String.valueOf(questionRows.size()));
            int total = questionRows.stream().mapToInt(r2 -> (int) r2.spMarks.getValue()).sum();
            sumM.setText(String.valueOf(total));
        };

        Runnable addQuestion = () -> {
            QuestionRow qr = new QuestionRow(questionRows.size() + 1, questionsContainer, questionRows, updateSummary);
            questionRows.add(qr);
            questionsContainer.add(qr.panel);
            questionsContainer.add(Box.createVerticalStrut(10));
            questionsContainer.revalidate();
            questionsContainer.repaint();
            updateSummary.run();
        };
        addQuestion.run();

        // ── LEFT column ───────────────────────────────────────────────────
        // Quiz Details card
        JPanel detailsCard = createCard("Quiz Details");
        detailsCard.add(formRow("Group *",          cbGroup));
        detailsCard.add(formRow("Title *",           tfTitle));
        detailsCard.add(formRow("Description",       new JScrollPane(taDesc)));
        detailsCard.add(formRow("Unlock Date",       tfUnlock));
        detailsCard.add(formRow("Hard Deadline",     tfDeadline));
        detailsCard.add(formRow("Duration (min) *",  spDuration));

        // Questions card
        JPanel questionsCard = createCard("Questions");
        questionsCard.add(questionsContainer);
        questionsCard.add(Box.createVerticalStrut(8));
        // Dashed "Add New Question" button — mirrors .add-q-btn
        JButton addQBtn = new JButton("＋ Add New Question") {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(new Color(0xEE, 0xF2, 0xFF));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 12, 12);
                float[] dash = {6f, 4f};
                g2.setStroke(new java.awt.BasicStroke(2, java.awt.BasicStroke.CAP_BUTT,
                    java.awt.BasicStroke.JOIN_MITER, 10, dash, 0));
                g2.setColor(new Color(0xC7, 0xD2, 0xFE));
                g2.drawRoundRect(1, 1, getWidth()-2, getHeight()-2, 12, 12);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        addQBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        addQBtn.setForeground(PRIMARY);
        addQBtn.setContentAreaFilled(false);
        addQBtn.setBorderPainted(false);
        addQBtn.setFocusPainted(false);
        addQBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        addQBtn.setMaximumSize(new Dimension(Integer.MAX_VALUE, 44));
        addQBtn.setAlignmentX(LEFT_ALIGNMENT);
        addQBtn.addActionListener(e -> addQuestion.run());
        questionsCard.add(addQBtn);

        JPanel leftCol = new JPanel();
        leftCol.setLayout(new BoxLayout(leftCol, BoxLayout.Y_AXIS));
        leftCol.setBackground(BG);
        leftCol.add(detailsCard);
        leftCol.add(Box.createVerticalStrut(16));
        leftCol.add(questionsCard);

        // ── RIGHT column ──────────────────────────────────────────────────
        // Settings card — mirrors .toggle-card
        JPanel settingsCard = createCard("Quiz Settings");
        settingsCard.add(toggleCard("⏱", "Auto-Submit on Expiry",
            "Answers are automatically submitted when the timer reaches zero.", chkAutoSubmit));
        settingsCard.add(Box.createVerticalStrut(8));
        JCheckBox chkFocus = new JCheckBox();
        settingsCard.add(toggleCard("🔒", "Focus Lock Mode",
            "Students receive a warning if they switch tabs or windows.", chkFocus));

        // Live Summary card — mirrors .summary-row
        JPanel summaryCard = createCard("Live Summary");
        summaryCard.add(summaryRow("❓ Questions",  sumQ));
        summaryCard.add(summaryRow("⭐ Total Marks", sumM));
        summaryCard.add(summaryRow("⏱ Duration",    sumD));

        // Actions card
        JLabel errLbl = new JLabel(" ");
        errLbl.setForeground(DANGER);
        errLbl.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        errLbl.setAlignmentX(LEFT_ALIGNMENT);

        JButton saveBtn   = new JButton("💾 Save as Draft");
        JButton cancelBtn = new JButton("✕ Cancel");
        styleBtn(saveBtn,   PRIMARY);
        styleBtn(cancelBtn, MUTED);
        saveBtn.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
        cancelBtn.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));
        saveBtn.setAlignmentX(LEFT_ALIGNMENT);
        cancelBtn.setAlignmentX(LEFT_ALIGNMENT);
        cancelBtn.addActionListener(e -> dialog.dispose());

        JPanel actionsCard = createCard("Actions");
        actionsCard.add(errLbl);
        actionsCard.add(Box.createVerticalStrut(6));
        actionsCard.add(saveBtn);
        actionsCard.add(Box.createVerticalStrut(8));
        actionsCard.add(cancelBtn);

        JPanel rightCol = new JPanel();
        rightCol.setLayout(new BoxLayout(rightCol, BoxLayout.Y_AXIS));
        rightCol.setBackground(BG);
        rightCol.setPreferredSize(new Dimension(340, 0));
        rightCol.setMinimumSize(new Dimension(340, 0));
        rightCol.setMaximumSize(new Dimension(340, Integer.MAX_VALUE));
        rightCol.add(settingsCard);
        rightCol.add(Box.createVerticalStrut(16));
        rightCol.add(summaryCard);
        rightCol.add(Box.createVerticalStrut(16));
        rightCol.add(actionsCard);

        // ── Two-column grid — mirrors .quiz-create-grid ───────────────────
        JPanel grid = new JPanel(new BorderLayout(20, 0));
        grid.setBackground(BG);
        grid.setBorder(new EmptyBorder(16, 16, 16, 16));
        grid.add(leftCol,  BorderLayout.CENTER);
        grid.add(rightCol, BorderLayout.EAST);

        JScrollPane scrollContent = new JScrollPane(grid,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scrollContent.setBorder(null);
        scrollContent.getViewport().setBackground(BG);

        // Hero sits in a wrapper above the scroll area
        JPanel heroWrapper = new JPanel(new BorderLayout());
        heroWrapper.setBackground(BG);
        heroWrapper.setBorder(new EmptyBorder(16, 16, 0, 16));
        heroWrapper.add(hero, BorderLayout.CENTER);

        dialog.setLayout(new BorderLayout());
        dialog.add(heroWrapper,   BorderLayout.NORTH);
        dialog.add(scrollContent, BorderLayout.CENTER);

        // ── Submit logic ──────────────────────────────────────────────────
        saveBtn.addActionListener(e -> {
            String title = tfTitle.getText().trim();
            if (title.isEmpty()) { errLbl.setText("Title is required."); return; }
            if (groupIds.isEmpty() || cbGroup.getSelectedIndex() < 0) {
                errLbl.setText("Please select a group."); return;
            }
            if (questionRows.isEmpty()) { errLbl.setText("Add at least one question."); return; }
            java.util.List<Map<String, Object>> questions = new ArrayList<>();
            for (QuestionRow qr : questionRows) {
                Map<String, Object> qMap = qr.toMap();
                if (qMap == null) { errLbl.setText("Fill in all question fields."); return; }
                questions.add(qMap);
            }
            Map<String, Object> body = new HashMap<>();
            body.put("title",            title);
            body.put("description",      taDesc.getText().trim());
            body.put("group_id",         groupIds.get(cbGroup.getSelectedIndex()));
            body.put("duration_minutes", spDuration.getValue());
            body.put("auto_submit",      chkAutoSubmit.isSelected());
            body.put("enforce_focus",    chkFocus.isSelected());
            body.put("questions",        questions);
            String unlock   = tfUnlock.getText().trim();
            String deadline = tfDeadline.getText().trim();
            if (!unlock.equals("yyyy-MM-dd HH:mm") && !unlock.isEmpty())
                body.put("unlock_date", unlock.replace(" ", "T") + ":00");
            if (!deadline.equals("yyyy-MM-dd HH:mm") && !deadline.isEmpty())
                body.put("hard_deadline", deadline.replace(" ", "T") + ":00");
            saveBtn.setEnabled(false);
            errLbl.setText("Saving…"); errLbl.setForeground(MUTED);
            new SwingWorker<JsonNode, Void>() {
                @Override protected JsonNode doInBackground() throws Exception {
                    return mapper.readTree(api.post("/lecturer/quizzes", body));
                }
                @Override protected void done() {
                    try {
                        get(); dialog.dispose(); loadQuizzes();
                    } catch (Exception ex) {
                        saveBtn.setEnabled(true);
                        errLbl.setText("Error: " + ex.getMessage());
                        errLbl.setForeground(DANGER);
                        JOptionPane.showMessageDialog(dialog, ex.getMessage(), "Save Error", JOptionPane.ERROR_MESSAGE);
                    }
                }
            }.execute();
        });

        dialog.setVisible(true);
    }

    // ── Edit draft quiz (lecturer) ────────────────────────────────────────

    private void showEditQuizDialog(int quizId, JsonNode existing) {
        // Load full quiz with questions first
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                // Re-use lecturer index data; questions may not be present — fetch detail
                // The API doesn't have a dedicated GET for a single lecturer quiz,
                // so we use the questions already in the card node if present,
                // otherwise fall back to what we have.
                return existing;
            }
            @Override protected void done() {
                try { openEditDialog(quizId, get()); }
                catch (Exception e) {
                    JOptionPane.showMessageDialog(QuizPanel.this, "Could not open editor: " + e.getMessage());
                }
            }
        }.execute();
    }

    private void openEditDialog(int quizId, JsonNode existing) {
        JDialog dialog = new JDialog((Frame) SwingUtilities.getWindowAncestor(this),
            "Edit Quiz — " + existing.path("title").asText(), true);
        dialog.setSize(780, 700);
        dialog.setLocationRelativeTo(this);

        JTextField tfTitle    = new JTextField(existing.path("title").asText());
        JTextArea  taDesc     = new JTextArea(existing.path("description").asText(""), 3, 20);
        taDesc.setLineWrap(true); taDesc.setWrapStyleWord(true);
        JComboBox<String> cbGroup = new JComboBox<>();
        java.util.List<Integer> groupIds = new ArrayList<>();
        int existingGroupId = existing.path("group_id").asInt(-1);
        JSpinner spDuration  = new JSpinner(new SpinnerNumberModel(
            existing.path("duration_minutes").asInt(30), 1, 180, 5));
        JTextField tfUnlock   = new JTextField(existing.path("unlock_date").asText("yyyy-MM-dd HH:mm"));
        JTextField tfDeadline = new JTextField(existing.path("hard_deadline").asText("yyyy-MM-dd HH:mm"));
        JCheckBox  chkAutoSubmit = new JCheckBox("Auto-submit on timer expiry",
            existing.path("auto_submit").asBoolean(true));

        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/groups"));
            }
            @Override protected void done() {
                try {
                    int selectIdx = 0;
                    for (JsonNode g : get()) {
                        int gid = g.path("id").asInt();
                        groupIds.add(gid);
                        cbGroup.addItem(g.path("name").asText());
                        if (gid == existingGroupId) selectIdx = groupIds.size() - 1;
                    }
                    cbGroup.setSelectedIndex(selectIdx);
                } catch (Exception ignored) {}
            }
        }.execute();

        JPanel questionsContainer = new JPanel();
        questionsContainer.setLayout(new BoxLayout(questionsContainer, BoxLayout.Y_AXIS));
        questionsContainer.setBackground(BG);
        java.util.List<QuestionRow> questionRows = new ArrayList<>();

        Runnable addQuestion = () -> {
            QuestionRow row = new QuestionRow(questionRows.size() + 1, questionsContainer, questionRows);
            questionRows.add(row);
            questionsContainer.add(row.panel);
            questionsContainer.add(Box.createVerticalStrut(10));
            questionsContainer.revalidate();
            questionsContainer.repaint();
        };

        // Pre-populate existing questions if available
        JsonNode existingQs = existing.path("questions");
        if (existingQs.isArray() && existingQs.size() > 0) {
            for (JsonNode eq : existingQs) {
                QuestionRow row = new QuestionRow(questionRows.size() + 1, questionsContainer, questionRows);
                row.tfQuestion.setText(eq.path("question").asText());
                row.spMarks.setValue(eq.path("marks").asInt(1));
                int correctIdx = eq.path("correct_option").asInt(0);
                // Clear default 4 options and repopulate
                row.optionFields.clear();
                row.correctRadios.clear();
                row.correctGroup = new ButtonGroup();
                row.optionsPanel.removeAll();
                for (JsonNode opt : eq.path("options")) {
                    row.addOption();
                    row.optionFields.get(row.optionFields.size() - 1).setText(opt.asText());
                }
                if (correctIdx < row.correctRadios.size())
                    row.correctRadios.get(correctIdx).setSelected(true);
                questionRows.add(row);
                questionsContainer.add(row.panel);
                questionsContainer.add(Box.createVerticalStrut(10));
            }
        } else {
            addQuestion.run();
        }

        JButton addQBtn = new JButton("+ Add Question");
        styleBtn(addQBtn, PURPLE);
        addQBtn.addActionListener(e -> addQuestion.run());

        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(20, 20, 10, 20));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        int row = 0;
        gc.gridx = 0; gc.gridy = row; gc.weightx = 0; form.add(formLabel("Title *"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfTitle, gc);
        gc.gridx = 0; gc.gridy = ++row; gc.weightx = 0; form.add(formLabel("Description"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(new JScrollPane(taDesc), gc);
        gc.gridx = 0; gc.gridy = ++row; gc.weightx = 0; form.add(formLabel("Group *"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(cbGroup, gc);
        gc.gridx = 0; gc.gridy = ++row; gc.weightx = 0; form.add(formLabel("Duration (min) *"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(spDuration, gc);
        gc.gridx = 0; gc.gridy = ++row; gc.weightx = 0; form.add(formLabel("Unlock Date"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfUnlock, gc);
        gc.gridx = 0; gc.gridy = ++row; gc.weightx = 0; form.add(formLabel("Hard Deadline"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfDeadline, gc);
        gc.gridx = 1; gc.gridy = ++row; form.add(chkAutoSubmit, gc);

        JPanel qSection = new JPanel(new BorderLayout());
        qSection.setBackground(BG);
        qSection.setBorder(new EmptyBorder(10, 20, 10, 20));
        JLabel qTitle = new JLabel("Questions");
        qTitle.setFont(new Font("Segoe UI", Font.BOLD, 14));
        qTitle.setForeground(TEXT);
        JPanel qHeader = new JPanel(new BorderLayout());
        qHeader.setBackground(BG);
        qHeader.add(qTitle, BorderLayout.WEST);
        qHeader.add(addQBtn, BorderLayout.EAST);
        qSection.add(qHeader, BorderLayout.NORTH);
        qSection.add(new JScrollPane(questionsContainer), BorderLayout.CENTER);

        JLabel errLbl = new JLabel(" ");
        errLbl.setForeground(DANGER);
        errLbl.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        errLbl.setBorder(new EmptyBorder(0, 20, 0, 20));

        JButton saveBtn = new JButton("💾 Save Changes");
        styleBtn(saveBtn, PRIMARY);

        JPanel bottom = new JPanel(new FlowLayout(FlowLayout.RIGHT, 10, 10));
        bottom.setBackground(SURFACE);
        bottom.setBorder(BorderFactory.createMatteBorder(1, 0, 0, 0, BORDER_C));
        bottom.add(saveBtn);

        JPanel content = new JPanel(new BorderLayout());
        content.add(form,     BorderLayout.NORTH);
        content.add(qSection, BorderLayout.CENTER);
        content.add(errLbl,   BorderLayout.SOUTH);

        JScrollPane scrollContent = new JScrollPane(content);
        scrollContent.setBorder(null);
        dialog.setLayout(new BorderLayout());
        dialog.add(scrollContent, BorderLayout.CENTER);
        dialog.add(bottom,        BorderLayout.SOUTH);

        saveBtn.addActionListener(e -> {
            String title = tfTitle.getText().trim();
            if (title.isEmpty()) { errLbl.setText("Title is required."); return; }
            if (groupIds.isEmpty() || cbGroup.getSelectedIndex() < 0) {
                errLbl.setText("Please select a group."); return;
            }
            if (questionRows.isEmpty()) { errLbl.setText("Add at least one question."); return; }
            java.util.List<Map<String, Object>> questions = new ArrayList<>();
            for (QuestionRow qr : questionRows) {
                Map<String, Object> qMap = qr.toMap();
                if (qMap == null) { errLbl.setText("Fill in all question fields."); return; }
                questions.add(qMap);
            }
            Map<String, Object> body = new HashMap<>();
            body.put("title",            title);
            body.put("description",      taDesc.getText().trim());
            body.put("group_id",         groupIds.get(cbGroup.getSelectedIndex()));
            body.put("duration_minutes", spDuration.getValue());
            body.put("auto_submit",      chkAutoSubmit.isSelected());
            body.put("enforce_focus",    false);
            body.put("questions",        questions);
            String unlock   = tfUnlock.getText().trim();
            String deadline = tfDeadline.getText().trim();
            if (!unlock.equals("yyyy-MM-dd HH:mm") && !unlock.isEmpty())
                body.put("unlock_date", unlock.replace(" ", "T") + ":00");
            if (!deadline.equals("yyyy-MM-dd HH:mm") && !deadline.isEmpty())
                body.put("hard_deadline", deadline.replace(" ", "T") + ":00");

            saveBtn.setEnabled(false);
            errLbl.setText("Saving…"); errLbl.setForeground(MUTED);
            new SwingWorker<Void, Void>() {
                @Override protected Void doInBackground() throws Exception {
                    api.put("/lecturer/quizzes/" + quizId, body);
                    return null;
                }
                @Override protected void done() {
                    try {
                        get();
                        dialog.dispose();
                        loadQuizzes();
                    } catch (Exception ex) {
                        saveBtn.setEnabled(true);
                        errLbl.setText("Error: " + ex.getMessage());
                        errLbl.setForeground(DANGER);
                    }
                }
            }.execute();
        });

        dialog.setVisible(true);
    }

    // ── Publish existing draft ────────────────────────────────────────────

    private void publishQuiz(int quizId) {
        int confirm = JOptionPane.showConfirmDialog(this,
            "Publish this quiz? Students will be able to see and attempt it.",
            "Confirm Publish", JOptionPane.YES_NO_OPTION);
        if (confirm != JOptionPane.YES_OPTION) return;
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.post("/lecturer/quizzes/" + quizId + "/publish", Map.of());
                return null;
            }
            @Override protected void done() {
                try {
                    get();
                    loadQuizzes();
                } catch (Exception e) {
                    JOptionPane.showMessageDialog(QuizPanel.this,
                        "Publish failed: " + e.getMessage(), "Error", JOptionPane.ERROR_MESSAGE);
                }
            }
        }.execute();
    }

    // ── Send reminder ─────────────────────────────────────────────────────

    private void sendReminder(int quizId, String quizTitle) {
        int confirm = JOptionPane.showConfirmDialog(this,
            "Send reminder to all group members for \"" + quizTitle + "\"?",
            "Confirm Reminder", JOptionPane.YES_NO_OPTION);
        if (confirm != JOptionPane.YES_OPTION) return;
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.post("/lecturer/quizzes/" + quizId + "/remind", Map.of()));
            }
            @Override protected void done() {
                try {
                    get();
                    JOptionPane.showMessageDialog(QuizPanel.this,
                        "Reminder sent successfully.", "Reminder Sent",
                        JOptionPane.INFORMATION_MESSAGE);
                } catch (Exception e) {
                    JOptionPane.showMessageDialog(QuizPanel.this,
                        "Reminder failed: " + e.getMessage(), "Error",
                        JOptionPane.ERROR_MESSAGE);
                }
            }
        }.execute();
    }

    // ── Delete quiz ───────────────────────────────────────────────────────

    private void deleteQuiz(int quizId, String quizTitle) {
        int confirm = JOptionPane.showConfirmDialog(this,
            "Delete quiz \"" + quizTitle + "\"? This cannot be undone.",
            "Confirm Delete", JOptionPane.YES_NO_OPTION, JOptionPane.WARNING_MESSAGE);
        if (confirm != JOptionPane.YES_OPTION) return;
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.delete("/lecturer/quizzes/" + quizId);
                return null;
            }
            @Override protected void done() {
                try { get(); loadQuizzes(); }
                catch (Exception e) {
                    JOptionPane.showMessageDialog(QuizPanel.this,
                        "Delete failed: " + e.getMessage(), "Error",
                        JOptionPane.ERROR_MESSAGE);
                }
            }
        }.execute();
    }

    // ── Lecturer quiz detail (show.blade.php) ────────────────────────────

    private void showQuizDetailDialog(int quizId, JsonNode q) {
        JDialog d = new JDialog((Frame) SwingUtilities.getWindowAncestor(this),
            q.path("title").asText() + " — Detail", true);
        d.setSize(820, 640);
        d.setLocationRelativeTo(this);

        String status    = q.path("status").asText("draft");
        String groupName = q.path("group_name").asText("—");
        int duration     = q.path("duration_minutes").asInt(0);
        int qCount       = q.path("questions_count").asInt(0);
        int attempts     = q.path("attempts_count").asInt(0);

        // Hero
        JPanel hero = new JPanel(new BorderLayout(16, 0)) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), PURPLE));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 16, 16);
                g2.dispose();
            }
        };
        hero.setOpaque(false);
        hero.setBorder(new EmptyBorder(20, 24, 20, 24));
        JPanel heroLeft = new JPanel();
        heroLeft.setOpaque(false);
        heroLeft.setLayout(new BoxLayout(heroLeft, BoxLayout.Y_AXIS));
        JLabel heroTitle = new JLabel(q.path("title").asText());
        heroTitle.setFont(new Font("Segoe UI", Font.BOLD, 18));
        heroTitle.setForeground(Color.WHITE);
        JPanel heroMeta = new JPanel(new FlowLayout(FlowLayout.LEFT, 14, 0));
        heroMeta.setOpaque(false);
        for (String m : new String[]{"👥 " + groupName, "❓ " + qCount + " questions", "⏱ " + duration + " min", "👥 " + attempts + " submissions"}) {
            JLabel ml = new JLabel(m); ml.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 12)); ml.setForeground(new Color(220,220,255)); heroMeta.add(ml);
        }
        heroLeft.add(heroTitle); heroLeft.add(Box.createVerticalStrut(6)); heroLeft.add(heroMeta);
        JLabel statusBadge = new JLabel(status.toUpperCase());
        statusBadge.setFont(new Font("Segoe UI", Font.BOLD, 12));
        statusBadge.setOpaque(true);
        statusBadge.setBackground("published".equals(status) ? new Color(0xD1,0xFA,0xE5) : "draft".equals(status) ? new Color(0xFE,0xF3,0xC7) : new Color(0xFE,0xE2,0xE2));
        statusBadge.setForeground("published".equals(status) ? new Color(0x06,0x5F,0x46) : "draft".equals(status) ? new Color(0x92,0x40,0x0E) : new Color(0x99,0x1B,0x1B));
        statusBadge.setBorder(new EmptyBorder(5, 14, 5, 14));
        hero.add(heroLeft, BorderLayout.CENTER);
        hero.add(statusBadge, BorderLayout.EAST);

        // Info grid
        JPanel infoGrid = new JPanel(new GridLayout(2, 2, 10, 10));
        infoGrid.setBackground(BG);
        String unlockVal   = q.path("unlock_date").isNull()   || q.path("unlock_date").asText().isEmpty()   ? "Immediate on publish" : formatApiDate(q.path("unlock_date").asText());
        String deadlineVal = q.path("hard_deadline").isNull() || q.path("hard_deadline").asText().isEmpty() ? "No deadline set"      : formatApiDate(q.path("hard_deadline").asText());
        for (String[] item : new String[][]{
                {"🔓 Unlock Date",  unlockVal},
                {"🏁 Hard Deadline", deadlineVal},
                {"🤖 Auto-Submit",   q.path("auto_submit").asBoolean(false)   ? "✅ Enabled"  : "❌ Disabled"},
                {"🔒 Focus Lock",    q.path("enforce_focus").asBoolean(false) ? "🔒 Enforced" : "🔓 Disabled"}}) {
            JPanel cell = new JPanel(); cell.setLayout(new BoxLayout(cell, BoxLayout.Y_AXIS));
            cell.setBackground(new Color(0xF8,0xFA,0xFC));
            cell.setBorder(BorderFactory.createCompoundBorder(BorderFactory.createLineBorder(BORDER_C), new EmptyBorder(10,12,10,12)));
            JLabel k = new JLabel(item[0]); k.setFont(new Font("Segoe UI Emoji", Font.BOLD, 11)); k.setForeground(MUTED);
            JLabel v = new JLabel(item[1]); v.setFont(new Font("Segoe UI", Font.BOLD, 13)); v.setForeground(TEXT);
            cell.add(k); cell.add(Box.createVerticalStrut(4)); cell.add(v);
            infoGrid.add(cell);
        }

        // Questions preview
        JPanel qPreviewPanel = new JPanel();
        qPreviewPanel.setLayout(new BoxLayout(qPreviewPanel, BoxLayout.Y_AXIS));
        qPreviewPanel.setBackground(SURFACE);
        JsonNode questions = q.path("questions");
        if (questions.isArray() && questions.size() > 0) {
            int qi = 1;
            for (JsonNode qn : questions) {
                JPanel qBlock = new JPanel(); qBlock.setLayout(new BoxLayout(qBlock, BoxLayout.Y_AXIS)); qBlock.setBackground(SURFACE);
                qBlock.setBorder(BorderFactory.createMatteBorder(0,0,1,0, new Color(0xF1,0xF5,0xF9)));
                JLabel qText = new JLabel("<html><b style='color:#6366f1'>Q" + qi + ".</b> " + qn.path("question").asText() + "</html>");
                qText.setFont(new Font("Segoe UI", Font.PLAIN, 13)); qText.setBorder(new EmptyBorder(10,0,6,0));
                JPanel optsGrid = new JPanel(new GridLayout(0, 2, 6, 4)); optsGrid.setBackground(SURFACE); optsGrid.setBorder(new EmptyBorder(0,0,10,0));
                int oi = 0; int correct = qn.path("correct_option").asInt(-1);
                String[] letters = {"A","B","C","D","E","F"};
                for (JsonNode opt : qn.path("options")) {
                    boolean isCorrect = oi == correct;
                    JLabel optLbl = new JLabel((oi < letters.length ? letters[oi] : String.valueOf(oi)) + ". " + opt.asText());
                    optLbl.setFont(new Font("Segoe UI", isCorrect ? Font.BOLD : Font.PLAIN, 12));
                    optLbl.setOpaque(true);
                    optLbl.setBackground(isCorrect ? new Color(0xD1,0xFA,0xE5) : new Color(0xF8,0xFA,0xFC));
                    optLbl.setForeground(isCorrect ? new Color(0x06,0x5F,0x46) : MUTED);
                    optLbl.setBorder(BorderFactory.createCompoundBorder(
                        BorderFactory.createLineBorder(isCorrect ? new Color(0x6E,0xE7,0xB7) : BORDER_C),
                        new EmptyBorder(6,10,6,10)));
                    optsGrid.add(optLbl); oi++;
                }
                qBlock.add(qText); qBlock.add(optsGrid);
                qPreviewPanel.add(qBlock); qi++;
            }
        } else {
            JLabel noQ = new JLabel("No questions in list view — open Edit to see them.", SwingConstants.CENTER);
            noQ.setForeground(MUTED); noQ.setFont(new Font("Segoe UI", Font.PLAIN, 13));
            qPreviewPanel.add(noQ);
        }

        // Lifecycle
        JPanel lifecyclePanel = new JPanel(); lifecyclePanel.setLayout(new BoxLayout(lifecyclePanel, BoxLayout.Y_AXIS)); lifecyclePanel.setBackground(SURFACE);
        boolean published = "published".equals(status) || "closed".equals(status);
        for (String[] step : new String[][]{
                {"Draft Created",     "done",    "✅"},
                {"Published",         published ? "done" : "pending",                    published ? "✅" : "⏳"},
                {"Accepting Answers", "published".equals(status) ? "active" : "pending", "published".equals(status) ? "▶" : "⏳"},
                {"Closed",            "closed".equals(status) ? "done" : "pending",      "closed".equals(status) ? "🔒" : "⏳"}}) {
            JPanel row = new JPanel(new BorderLayout(10,0)); row.setBackground(SURFACE);
            row.setBorder(BorderFactory.createMatteBorder(0,0,1,0, new Color(0xF1,0xF5,0xF9)));
            row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 44));
            JLabel icon = new JLabel(step[2], SwingConstants.CENTER);
            icon.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 14)); icon.setOpaque(true); icon.setPreferredSize(new Dimension(32,32));
            icon.setBackground("done".equals(step[1]) ? new Color(0xD1,0xFA,0xE5) : "active".equals(step[1]) ? PRIMARY : new Color(0xF1,0xF5,0xF9));
            icon.setForeground("done".equals(step[1]) ? new Color(0x06,0x5F,0x46) : "active".equals(step[1]) ? Color.WHITE : MUTED);
            JLabel lbl = new JLabel(step[0]); lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
            lbl.setForeground("done".equals(step[1]) ? new Color(0x06,0x5F,0x46) : "active".equals(step[1]) ? PRIMARY : MUTED);
            row.add(icon, BorderLayout.WEST); row.add(lbl, BorderLayout.CENTER);
            lifecyclePanel.add(row); lifecyclePanel.add(Box.createVerticalStrut(4));
        }

        // Layout
        JPanel infoCard = createCard("Quiz Information"); infoCard.add(infoGrid);
        JPanel qCard    = createCard("Questions Preview"); qCard.add(new JScrollPane(qPreviewPanel));
        JPanel leftCol  = new JPanel(); leftCol.setLayout(new BoxLayout(leftCol, BoxLayout.Y_AXIS)); leftCol.setBackground(BG);
        leftCol.add(infoCard); leftCol.add(Box.createVerticalStrut(14)); leftCol.add(qCard);

        JPanel lcCard   = createCard("Lifecycle"); lcCard.add(lifecyclePanel);
        JPanel rightCol = new JPanel(); rightCol.setLayout(new BoxLayout(rightCol, BoxLayout.Y_AXIS)); rightCol.setBackground(BG);
        rightCol.setPreferredSize(new Dimension(220, 0)); rightCol.add(lcCard);

        JPanel grid = new JPanel(new BorderLayout(16, 0)); grid.setBackground(BG); grid.setBorder(new EmptyBorder(14,14,14,14));
        grid.add(leftCol, BorderLayout.CENTER); grid.add(rightCol, BorderLayout.EAST);

        JPanel heroWrapper = new JPanel(new BorderLayout()); heroWrapper.setBackground(BG); heroWrapper.setBorder(new EmptyBorder(14,14,0,14));
        heroWrapper.add(hero, BorderLayout.CENTER);

        JButton closeBtn = new JButton("Close"); styleBtn(closeBtn, PRIMARY); closeBtn.addActionListener(ev -> d.dispose());
        JPanel foot = new JPanel(new FlowLayout(FlowLayout.RIGHT, 12, 8)); foot.setBackground(SURFACE);
        foot.setBorder(BorderFactory.createMatteBorder(1,0,0,0,BORDER_C)); foot.add(closeBtn);

        d.setLayout(new BorderLayout());
        d.add(heroWrapper, BorderLayout.NORTH);
        d.add(new JScrollPane(grid), BorderLayout.CENTER);
        d.add(foot, BorderLayout.SOUTH);
        d.setVisible(true);
    }

    // ── Lecturer results view ─────────────────────────────────────────────

    private void showLecturerResults(int quizId, String quizTitle) {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/lecturer/quizzes/" + quizId + "/results"));
            }
            @Override protected void done() {
                try {
                    JsonNode data    = get();
                    JsonNode results = data.path("results");

                    JDialog d = new JDialog((Frame) SwingUtilities.getWindowAncestor(QuizPanel.this),
                        "Results — " + quizTitle, true);
                    d.setSize(780, 560);
                    d.setLocationRelativeTo(QuizPanel.this);

                    // ── Compute stats — mirrors results.blade.php @php block ──
                    int total = results.size();
                    double sumScore = 0, sumPct = 0; int highest = 0, passCount = 0;
                    for (JsonNode r : results) {
                        double pct = r.path("percentage").asDouble();
                        int sc = r.path("score").asInt();
                        sumScore += sc; sumPct += pct;
                        if (sc > highest) highest = sc;
                        if (pct >= 50) passCount++;
                    }
                    double avgScore = total > 0 ? Math.round(sumScore / total * 10.0) / 10.0 : 0;
                    double avgPct   = total > 0 ? Math.round(sumPct   / total * 10.0) / 10.0 : 0;
                    int    passRate = total > 0 ? (int) Math.round((double) passCount / total * 100) : 0;

                    // ── Stats strip — mirrors .stats-grid in results.blade.php ──
                    JPanel statsStrip = new JPanel(new GridLayout(1, 5, 10, 0));
                    statsStrip.setBackground(BG);
                    statsStrip.setBorder(new EmptyBorder(12, 16, 12, 16));
                    for (String[] s : new String[][]{
                            {"Submissions", String.valueOf(total)},
                            {"Avg Score",   String.valueOf(avgScore)},
                            {"Avg %",       avgPct + "%"},
                            {"Pass Rate",   passRate + "%"},
                            {"Highest",     String.valueOf(highest)}}) {
                        JPanel sc = new JPanel();
                        sc.setLayout(new BoxLayout(sc, BoxLayout.Y_AXIS));
                        sc.setBackground(SURFACE);
                        sc.setBorder(BorderFactory.createCompoundBorder(
                            BorderFactory.createLineBorder(BORDER_C),
                            new EmptyBorder(10, 14, 10, 14)));
                        JLabel val = new JLabel(s[1], SwingConstants.CENTER);
                        val.setFont(new Font("Segoe UI", Font.BOLD, 18));
                        val.setForeground(PRIMARY);
                        val.setAlignmentX(CENTER_ALIGNMENT);
                        JLabel lbl = new JLabel(s[0], SwingConstants.CENTER);
                        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
                        lbl.setForeground(MUTED);
                        lbl.setAlignmentX(CENTER_ALIGNMENT);
                        sc.add(val); sc.add(lbl);
                        statsStrip.add(sc);
                    }

                    // ── Rankings table — mirrors Student Rankings in results.blade.php ──
                    String[] cols = {"Rank", "Student", "Email", "Score", "%", "Grade", "Status", "Submitted"};
                    Object[][] rows = new Object[total][8];
                    int i = 0;
                    for (JsonNode r : results) {
                        double pct = r.path("percentage").asDouble();
                        String rank = i == 0 ? "🥇" : i == 1 ? "🥈" : i == 2 ? "🥉" : String.valueOf(i + 1);
                        rows[i++] = new Object[]{
                            rank,
                            r.path("user_name").asText(),
                            r.path("user_email").asText(),
                            r.path("score").asInt() + " / " + r.path("max_score").asInt(),
                            String.format("%.1f%%", pct),
                            r.path("grade").asText(),
                            pct >= 50 ? "✅ Pass" : "❌ Fail",
                            r.path("completed_at").asText()
                        };
                    }
                    JTable table = new JTable(rows, cols);
                    table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                    table.setRowHeight(28);
                    table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
                    table.setEnabled(false);
                    table.getColumnModel().getColumn(0).setPreferredWidth(40);
                    table.getColumnModel().getColumn(6).setPreferredWidth(70);

                    JPanel panel = new JPanel(new BorderLayout());
                    panel.setBackground(BG);
                    panel.add(statsStrip,              BorderLayout.NORTH);
                    panel.add(new JScrollPane(table),  BorderLayout.CENTER);

                    JButton closeBtn = new JButton("Close");
                    styleBtn(closeBtn, PRIMARY);
                    closeBtn.addActionListener(ev -> d.dispose());
                    JPanel foot = new JPanel(new FlowLayout(FlowLayout.RIGHT, 12, 8));
                    foot.setBackground(SURFACE);
                    foot.setBorder(BorderFactory.createMatteBorder(1, 0, 0, 0, BORDER_C));
                    foot.add(closeBtn);
                    panel.add(foot, BorderLayout.SOUTH);

                    d.setContentPane(panel);
                    d.setVisible(true);
                } catch (Exception e) {
                    JOptionPane.showMessageDialog(QuizPanel.this,
                        "Could not load results: " + e.getMessage());
                }
            }
        }.execute();
    }

    // ── QuestionRow helper ────────────────────────────────────────────────

    private class QuestionRow {
        JPanel panel;
        JTextField tfQuestion;
        JSpinner   spMarks;
        // spCorrect kept for edit-dialog back-compat; not shown in create dialog
        JSpinner   spCorrect = new JSpinner(new SpinnerNumberModel(0, 0, 9, 1));
        java.util.List<JTextField> optionFields = new ArrayList<>();
        java.util.List<JRadioButton> correctRadios = new ArrayList<>();
        ButtonGroup correctGroup = new ButtonGroup();
        JPanel optionsPanel;
        private static final String[] LETTERS = {"A","B","C","D","E","F"};

        QuestionRow(int num, JPanel container, java.util.List<QuestionRow> allRows) {
            this(num, container, allRows, null);
        }

        QuestionRow(int num, JPanel container, java.util.List<QuestionRow> allRows, Runnable onRemove) {
            panel = new JPanel(new BorderLayout());
            panel.setBackground(SURFACE);
            panel.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 4, 0, 0, PURPLE),
                BorderFactory.createCompoundBorder(
                    BorderFactory.createLineBorder(BORDER_C),
                    new EmptyBorder(10, 12, 10, 12))));
            panel.setAlignmentX(LEFT_ALIGNMENT);
            panel.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));

            JPanel top = new JPanel(new BorderLayout());
            top.setOpaque(false);
            JLabel qNumLbl = new JLabel("Q" + num);
            qNumLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
            qNumLbl.setForeground(PURPLE);

            JButton removeBtn = new JButton("✕");
            removeBtn.setFont(new Font("Segoe UI", Font.BOLD, 11));
            removeBtn.setForeground(DANGER);
            removeBtn.setBackground(new Color(0xFE, 0xE2, 0xE2));
            removeBtn.setBorderPainted(false);
            removeBtn.setFocusPainted(false);
            removeBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            removeBtn.addActionListener(e -> {
                allRows.remove(this);
                container.remove(panel);
                container.revalidate();
                container.repaint();
                if (onRemove != null) onRemove.run();
            });
            top.add(qNumLbl,   BorderLayout.WEST);
            top.add(removeBtn, BorderLayout.EAST);

            tfQuestion = new JTextField();
            tfQuestion.setFont(new Font("Segoe UI", Font.PLAIN, 13));
            tfQuestion.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER_C),
                new EmptyBorder(4, 6, 4, 6)));

            // Hint — mirrors .correct-hint in create.blade.php
            JLabel hint = new JLabel("🟣 Click the radio button to mark the correct answer");
            hint.setFont(new Font("Segoe UI", Font.PLAIN, 11));
            hint.setForeground(MUTED);
            hint.setAlignmentX(LEFT_ALIGNMENT);

            optionsPanel = new JPanel();
            optionsPanel.setLayout(new BoxLayout(optionsPanel, BoxLayout.Y_AXIS));
            optionsPanel.setOpaque(false);
            addOption(); addOption(); addOption(); addOption(); // start with A B C D

            JButton addOptBtn = new JButton("+ Option");
            addOptBtn.setFont(new Font("Segoe UI", Font.PLAIN, 11));
            addOptBtn.setForeground(PRIMARY);
            addOptBtn.setBackground(new Color(0xEE, 0xF2, 0xFF));
            addOptBtn.setBorderPainted(false);
            addOptBtn.setFocusPainted(false);
            addOptBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            addOptBtn.addActionListener(e -> { addOption(); optionsPanel.revalidate(); optionsPanel.repaint(); });

            JPanel metaRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 10, 0));
            metaRow.setOpaque(false);
            metaRow.add(new JLabel("Marks:"));
            spMarks = new JSpinner(new SpinnerNumberModel(1, 1, 100, 1));
            spMarks.setPreferredSize(new Dimension(60, 26));
            metaRow.add(spMarks);
            metaRow.add(addOptBtn);

            JPanel body = new JPanel();
            body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
            body.setOpaque(false);
            body.add(Box.createVerticalStrut(6));
            body.add(tfQuestion);
            body.add(Box.createVerticalStrut(8));
            body.add(hint);
            body.add(Box.createVerticalStrut(6));
            body.add(optionsPanel);
            body.add(Box.createVerticalStrut(6));
            body.add(metaRow);

            panel.add(top,  BorderLayout.NORTH);
            panel.add(body, BorderLayout.CENTER);
        }

        void addOption() {
            int idx = optionFields.size();
            String letter = idx < LETTERS.length ? LETTERS[idx] : String.valueOf(idx);

            // Radio button — mirrors input[type=radio] in .option-row
            JRadioButton rb = new JRadioButton();
            rb.setOpaque(false);
            rb.setFocusPainted(false);
            rb.setToolTipText("Mark as correct answer");
            correctGroup.add(rb);
            correctRadios.add(rb);

            // Letter badge — mirrors .option-letter
            JLabel letterLbl = new JLabel(letter, SwingConstants.CENTER);
            letterLbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
            letterLbl.setOpaque(true);
            letterLbl.setBackground(new Color(0xE2, 0xE8, 0xF0));
            letterLbl.setForeground(MUTED);
            letterLbl.setPreferredSize(new Dimension(28, 28));
            letterLbl.setMinimumSize(new Dimension(28, 28));
            letterLbl.setMaximumSize(new Dimension(28, 28));

            // Highlight letter badge when this option is selected as correct
            rb.addActionListener(e -> {
                for (int i = 0; i < correctRadios.size(); i++) {
                    // find the letter label in the same row panel
                    Component rowComp = optionsPanel.getComponent(i * 2); // every other is a strut
                    if (rowComp instanceof JPanel rp) {
                        JLabel ll = (JLabel) rp.getClientProperty("letterLbl");
                        if (ll != null) {
                            boolean sel = correctRadios.get(i).isSelected();
                            ll.setBackground(sel ? PRIMARY : new Color(0xE2, 0xE8, 0xF0));
                            ll.setForeground(sel ? Color.WHITE : MUTED);
                        }
                    }
                }
            });

            JTextField tf = new JTextField();
            tf.setFont(new Font("Segoe UI", Font.PLAIN, 12));
            tf.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER_C, 2),
                new EmptyBorder(6, 10, 6, 10)));
            tf.setAlignmentX(LEFT_ALIGNMENT);
            optionFields.add(tf);

            // Row: [radio][letter][textfield] — mirrors .option-row
            JPanel row = new JPanel(new BorderLayout(6, 0));
            row.setOpaque(false);
            row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 38));
            row.setAlignmentX(LEFT_ALIGNMENT);
            JPanel leftBadge = new JPanel(new FlowLayout(FlowLayout.LEFT, 4, 0));
            leftBadge.setOpaque(false);
            leftBadge.add(rb);
            leftBadge.add(letterLbl);
            row.add(leftBadge, BorderLayout.WEST);
            row.add(tf,        BorderLayout.CENTER);
            row.putClientProperty("letterLbl", letterLbl);
            optionsPanel.add(row);
            optionsPanel.add(Box.createVerticalStrut(6));
        }

        Map<String, Object> toMap() {
            String q = tfQuestion.getText().trim();
            if (q.isEmpty()) return null;
            java.util.List<String> opts = new ArrayList<>();
            for (JTextField tf : optionFields) {
                String o = tf.getText().trim();
                if (o.isEmpty()) return null;
                opts.add(o);
            }
            if (opts.size() < 2) return null;
            int correct = 0;
            for (int i = 0; i < correctRadios.size(); i++) {
                if (correctRadios.get(i).isSelected()) { correct = i; break; }
            }
            Map<String, Object> m = new HashMap<>();
            m.put("question",       q);
            m.put("options",        opts);
            m.put("correct_option", correct);
            m.put("marks",          spMarks.getValue());
            return m;
        }
    }

    private JLabel formLabel(String text) {
        JLabel l = new JLabel(text);
        l.setFont(new Font("Segoe UI", Font.BOLD, 12));
        l.setForeground(MUTED);
        return l;
    }

    // ── Card / form helpers for two-column create dialog ──────────────────

    private JPanel createCard(String title) {
        JPanel card = new JPanel();
        card.setLayout(new BoxLayout(card, BoxLayout.Y_AXIS));
        card.setBackground(SURFACE);
        card.setAlignmentX(LEFT_ALIGNMENT);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C),
            new EmptyBorder(16, 16, 16, 16)));
        JLabel hdr = new JLabel(title);
        hdr.setFont(new Font("Segoe UI", Font.BOLD, 14));
        hdr.setForeground(TEXT);
        hdr.setAlignmentX(LEFT_ALIGNMENT);
        hdr.setBorder(new EmptyBorder(0, 0, 10, 0));
        card.add(hdr);
        return card;
    }

    private JPanel formRow(String label, JComponent field) {
        JPanel row = new JPanel(new BorderLayout(8, 0));
        row.setBackground(SURFACE);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 56));
        row.setBorder(new EmptyBorder(0, 0, 8, 0));
        JLabel lbl = formLabel(label);
        lbl.setPreferredSize(new Dimension(120, 26));
        row.add(lbl,   BorderLayout.WEST);
        row.add(field, BorderLayout.CENTER);
        return row;
    }

    /** Mirrors .toggle-card in create.blade.php */
    private JPanel toggleCard(String icon, String title, String desc, JCheckBox chk) {
        JPanel card = new JPanel(new BorderLayout(12, 0));
        card.setBackground(new Color(0xFA, 0xFB, 0xFF));
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 2),
            new EmptyBorder(12, 14, 12, 14)));
        card.setAlignmentX(LEFT_ALIGNMENT);
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, 80));
        card.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));

        chk.setOpaque(false);
        chk.setSelected(true);

        JLabel iconLbl = new JLabel(icon);
        iconLbl.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 20));

        JPanel info = new JPanel();
        info.setOpaque(false);
        info.setLayout(new BoxLayout(info, BoxLayout.Y_AXIS));
        JLabel titleLbl = new JLabel(title);
        titleLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        titleLbl.setForeground(TEXT);
        JLabel descLbl = new JLabel("<html><p style='width:180px'>" + desc + "</p></html>");
        descLbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        descLbl.setForeground(MUTED);
        info.add(titleLbl);
        info.add(descLbl);

        card.add(chk,     BorderLayout.WEST);
        card.add(iconLbl, BorderLayout.CENTER);
        card.add(info,    BorderLayout.EAST);
        card.addMouseListener(new java.awt.event.MouseAdapter() {
            @Override public void mouseClicked(java.awt.event.MouseEvent e) { chk.setSelected(!chk.isSelected()); }
        });
        return card;
    }

    /** Mirrors .summary-row in create.blade.php */
    private JPanel summaryRow(String label, JLabel valueLabel) {
        JPanel row = new JPanel(new BorderLayout());
        row.setBackground(SURFACE);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
        row.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, new Color(0xF1, 0xF5, 0xF9)),
            new EmptyBorder(8, 0, 8, 0)));
        JLabel lbl = new JLabel(label);
        lbl.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 13));
        lbl.setForeground(MUTED);
        valueLabel.setAlignmentX(RIGHT_ALIGNMENT);
        row.add(lbl,        BorderLayout.WEST);
        row.add(valueLabel, BorderLayout.EAST);
        return row;
    }

    // ── Take quiz ─────────────────────────────────────────────────────────

    private void takeQuiz(int quizId) {
        statusLbl.setText("Loading quiz…");
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/quizzes/" + quizId));
            }
            @Override protected void done() {
                try {
                    JsonNode quiz = get();
                    if (quiz.has("message")) {
                        JOptionPane.showMessageDialog(QuizPanel.this,
                            quiz.path("message").asText(), "Cannot Take Quiz",
                            JOptionPane.WARNING_MESSAGE);
                        statusLbl.setText(" ");
                        return;
                    }
                    showQuizDialog(quiz);
                    statusLbl.setText(" ");
                } catch (Exception e) {
                    statusLbl.setText("Error: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
    }

    private void showQuizDialog(JsonNode quiz) {
        int quizId   = quiz.path("id").asInt();
        String title = quiz.path("title").asText();
        int duration = quiz.path("duration_minutes").asInt(15);
        JsonNode qs  = quiz.path("questions");

        JDialog dialog = new JDialog((Frame) SwingUtilities.getWindowAncestor(this),
            title + " — Quiz", true);
        dialog.setSize(720, 620);
        dialog.setLocationRelativeTo(this);

        JPanel main = new JPanel(new BorderLayout());
        main.setBackground(BG);

        // ── Timer header — mirrors .quiz-header in take.blade.php ─────────
        JPanel quizHeader = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setPaint(new GradientPaint(0, 0, DARK, getWidth(), 0, new Color(0x31,0x2E,0x81)));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
            }
        };
        quizHeader.setOpaque(false);
        quizHeader.setBorder(new EmptyBorder(10, 16, 10, 16));

        JLabel titleLbl = new JLabel("🎓 " + title);
        titleLbl.setFont(new Font("Segoe UI", Font.BOLD, 15));
        titleLbl.setForeground(Color.WHITE);

        // Timer box — mirrors .timer-box
        JLabel timerLbl = new JLabel(String.format("⏱ %02d:00", duration), SwingConstants.CENTER);
        timerLbl.setFont(new Font("Segoe UI", Font.BOLD, 20));
        timerLbl.setForeground(GREEN);
        timerLbl.setOpaque(true);
        timerLbl.setBackground(new Color(255,255,255,30));
        timerLbl.setBorder(new EmptyBorder(8, 18, 8, 18));

        quizHeader.add(titleLbl,  BorderLayout.WEST);
        quizHeader.add(timerLbl,  BorderLayout.EAST);

        // ── Progress strip — mirrors .progress-strip ──────────────────────
        int totalQs = qs.size();
        JProgressBar answerBar = new JProgressBar(0, totalQs);
        answerBar.setForeground(PRIMARY);
        answerBar.setBackground(BORDER_C);
        answerBar.setBorderPainted(false);
        answerBar.setPreferredSize(new Dimension(0, 8));
        JLabel answerVal = new JLabel("0 / " + totalQs);
        answerVal.setFont(new Font("Segoe UI", Font.BOLD, 11));
        answerVal.setForeground(MUTED);

        JProgressBar timeBar = new JProgressBar(0, duration * 60);
        timeBar.setValue(duration * 60);
        timeBar.setForeground(GREEN);
        timeBar.setBackground(BORDER_C);
        timeBar.setBorderPainted(false);
        timeBar.setPreferredSize(new Dimension(0, 8));

        JPanel progressStrip = new JPanel(new GridLayout(1, 2, 16, 0));
        progressStrip.setBackground(SURFACE);
        progressStrip.setBorder(new EmptyBorder(8, 16, 8, 16));
        JPanel ap = new JPanel(new BorderLayout(6, 0)); ap.setBackground(SURFACE);
        JLabel aLbl = new JLabel("✅ Answered"); aLbl.setFont(new Font("Segoe UI", Font.BOLD, 11)); aLbl.setForeground(MUTED);
        ap.add(aLbl, BorderLayout.WEST); ap.add(answerBar, BorderLayout.CENTER); ap.add(answerVal, BorderLayout.EAST);
        JPanel tp = new JPanel(new BorderLayout(6, 0)); tp.setBackground(SURFACE);
        JLabel tLbl = new JLabel("⏱ Time"); tLbl.setFont(new Font("Segoe UI", Font.BOLD, 11)); tLbl.setForeground(MUTED);
        tp.add(tLbl, BorderLayout.WEST); tp.add(timeBar, BorderLayout.CENTER);
        progressStrip.add(ap); progressStrip.add(tp);

        // ── Question navigator dots — mirrors .q-nav ──────────────────────
        JPanel navPanel = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 6));
        navPanel.setBackground(SURFACE);
        navPanel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C),
            new EmptyBorder(8, 12, 8, 12)));
        Map<Integer, JButton> dotMap = new LinkedHashMap<>();
        JLabel navTitle = new JLabel("🗺 Question Navigator");
        navTitle.setFont(new Font("Segoe UI", Font.BOLD, 11));
        navTitle.setForeground(MUTED);
        navPanel.add(navTitle);

        JPanel questionsPanel = new JPanel();
        questionsPanel.setLayout(new BoxLayout(questionsPanel, BoxLayout.Y_AXIS));
        questionsPanel.setBackground(BG);
        questionsPanel.setBorder(new EmptyBorder(8, 16, 16, 16));

        Map<Integer, Map<Integer, JRadioButton>> radioMap = new LinkedHashMap<>();
        Map<Integer, JPanel> cardMap = new LinkedHashMap<>();

        int qNum = 1;
        for (JsonNode q : qs) {
            int qId = q.path("id").asInt();

            // Nav dot — mirrors .q-dot
            JButton dot = new JButton(String.valueOf(qNum));
            dot.setFont(new Font("Segoe UI", Font.BOLD, 11));
            dot.setPreferredSize(new Dimension(34, 34));
            dot.setBackground(new Color(0xF8,0xFA,0xFC));
            dot.setForeground(MUTED);
            dot.setBorderPainted(true);
            dot.setFocusPainted(false);
            dot.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            dotMap.put(qId, dot);
            navPanel.add(dot);

            // Question card — mirrors .question-card
            JPanel qCard = new JPanel();
            qCard.setLayout(new BoxLayout(qCard, BoxLayout.Y_AXIS));
            qCard.setBackground(SURFACE);
            qCard.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER_C, 2),
                new EmptyBorder(16, 18, 16, 18)));
            qCard.setAlignmentX(LEFT_ALIGNMENT);
            qCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));
            cardMap.put(qId, qCard);

            // Badge row — mirrors .q-num-badge
            JLabel numBadge = new JLabel("Q" + qNum + " of " + totalQs);
            numBadge.setFont(new Font("Segoe UI", Font.BOLD, 11));
            numBadge.setForeground(Color.WHITE);
            numBadge.setOpaque(true);
            numBadge.setBackground(PRIMARY);
            numBadge.setBorder(new EmptyBorder(3, 10, 3, 10));

            JLabel qLbl = new JLabel("<html><b>" + q.path("question").asText() + "</b></html>");
            qLbl.setFont(new Font("Segoe UI", Font.PLAIN, 15));
            qLbl.setForeground(TEXT);
            qLbl.setAlignmentX(LEFT_ALIGNMENT);

            int marks = q.path("marks").asInt(1);
            JLabel marksLbl = new JLabel("⭐ " + marks + " mark" + (marks > 1 ? "s" : ""));
            marksLbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
            marksLbl.setForeground(MUTED);
            marksLbl.setAlignmentX(LEFT_ALIGNMENT);

            // "Answered" tag — mirrors .q-answered-tag in take.blade.php
            JLabel answeredTag = new JLabel("✓ Answered");
            answeredTag.setFont(new Font("Segoe UI", Font.BOLD, 11));
            answeredTag.setForeground(new Color(0x06, 0x5F, 0x46));
            answeredTag.setOpaque(true);
            answeredTag.setBackground(new Color(0xD1, 0xFA, 0xE5));
            answeredTag.setBorder(new EmptyBorder(3, 10, 3, 10));
            answeredTag.setVisible(false);

            JPanel badgeRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
            badgeRow.setOpaque(false);
            badgeRow.add(numBadge);
            badgeRow.add(answeredTag);
            badgeRow.setAlignmentX(LEFT_ALIGNMENT);

            qCard.putClientProperty("answeredTag", answeredTag);
            qCard.add(badgeRow);
            qCard.add(Box.createVerticalStrut(10));
            qCard.add(qLbl);
            qCard.add(Box.createVerticalStrut(4));
            qCard.add(marksLbl);
            qCard.add(Box.createVerticalStrut(10));

            ButtonGroup bg = new ButtonGroup();
            Map<Integer, JRadioButton> radios = new LinkedHashMap<>();
            int idx = 0;
            String[] letters = {"A","B","C","D","E","F"};
            for (JsonNode opt : q.path("options")) {
                final int optIdx = idx;
                String letter = idx < letters.length ? letters[idx] : String.valueOf(idx);

                // Letter badge — mirrors .opt-letter
                JLabel letterBadge = new JLabel(letter, SwingConstants.CENTER);
                letterBadge.setFont(new Font("Segoe UI", Font.BOLD, 12));
                letterBadge.setPreferredSize(new Dimension(32, 32));
                letterBadge.setMinimumSize(new Dimension(32, 32));
                letterBadge.setMaximumSize(new Dimension(32, 32));
                letterBadge.setOpaque(true);
                letterBadge.setBackground(new Color(0xF1,0xF5,0xF9));
                letterBadge.setForeground(MUTED);

                JRadioButton rb = new JRadioButton(opt.asText());
                rb.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                rb.setOpaque(false);
                rb.setFocusPainted(false);
                bg.add(rb);
                radios.put(idx, rb);

                // Option row panel — mirrors .option-label
                JPanel optRow = new JPanel(new BorderLayout(10, 0));
                optRow.setBackground(SURFACE);
                optRow.setBorder(BorderFactory.createCompoundBorder(
                    BorderFactory.createLineBorder(BORDER_C, 2),
                    new EmptyBorder(10, 12, 10, 12)));
                optRow.setAlignmentX(LEFT_ALIGNMENT);
                optRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 52));
                optRow.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
                optRow.add(letterBadge, BorderLayout.WEST);
                optRow.add(rb, BorderLayout.CENTER);

                // Click anywhere on the row selects the option — mirrors .option-label click
                java.awt.event.MouseAdapter optClick = new java.awt.event.MouseAdapter() {
                    @Override public void mouseClicked(java.awt.event.MouseEvent e) {
                        rb.setSelected(true);
                        rb.getActionListeners()[0].actionPerformed(null);
                    }
                };
                optRow.addMouseListener(optClick);

                // Highlight selected — mirrors .option-label.selected (purple border + letter bg)
                rb.addActionListener(e -> {
                    // Reset all option rows for this question
                    for (int i = 0; i < qCard.getComponentCount(); i++) {
                        Component c = qCard.getComponent(i);
                        if (c instanceof JPanel op && op.getClientProperty("optRow") != null) {
                            op.setBackground(SURFACE);
                            op.setBorder(BorderFactory.createCompoundBorder(
                                BorderFactory.createLineBorder(BORDER_C, 2),
                                new EmptyBorder(10, 12, 10, 12)));
                            JLabel lbl = (JLabel) op.getClientProperty("letterBadge");
                            if (lbl != null) { lbl.setBackground(new Color(0xF1,0xF5,0xF9)); lbl.setForeground(MUTED); }
                        }
                    }
                    // Highlight this row
                    optRow.setBackground(new Color(0xEE,0xF2,0xFF));
                    optRow.setBorder(BorderFactory.createCompoundBorder(
                        BorderFactory.createLineBorder(PRIMARY, 2),
                        new EmptyBorder(10, 12, 10, 12)));
                    letterBadge.setBackground(PRIMARY);
                    letterBadge.setForeground(Color.WHITE);
                    // Show answered tag + purple card border — mirrors .question-card.answered
                    JLabel tag = (JLabel) qCard.getClientProperty("answeredTag");
                    if (tag != null) tag.setVisible(true);
                    qCard.setBorder(BorderFactory.createCompoundBorder(
                        BorderFactory.createLineBorder(PRIMARY, 2),
                        new EmptyBorder(16, 18, 16, 18)));
                });

                optRow.putClientProperty("optRow", true);
                optRow.putClientProperty("letterBadge", letterBadge);
                qCard.add(optRow);
                qCard.add(Box.createVerticalStrut(8));
                idx++;
            }
            radioMap.put(qId, radios);
            questionsPanel.add(qCard);
            questionsPanel.add(Box.createVerticalStrut(14));
            qNum++;
        }

        // Wire nav dots to scroll to card
        JScrollPane questScroll = new JScrollPane(questionsPanel,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        questScroll.setBorder(null);
        for (Map.Entry<Integer, JButton> e : dotMap.entrySet()) {
            int qId = e.getKey();
            e.getValue().addActionListener(ev -> {
                JPanel card = cardMap.get(qId);
                if (card != null) card.scrollRectToVisible(card.getBounds());
            });
        }

        JButton submitBtn = new JButton("🚀 Submit Quiz");
        styleBtn(submitBtn, PRIMARY);

        int[] secondsLeft = {duration * 60};
        javax.swing.Timer timer = new javax.swing.Timer(1000, null);

        submitBtn.addActionListener(e -> {
            Map<String, Object> answers = new LinkedHashMap<>();
            for (Map.Entry<Integer, Map<Integer, JRadioButton>> entry : radioMap.entrySet()) {
                for (Map.Entry<Integer, JRadioButton> rb : entry.getValue().entrySet()) {
                    if (rb.getValue().isSelected()) {
                        answers.put(String.valueOf(entry.getKey()), rb.getKey());
                        break;
                    }
                }
            }
            if (answers.size() < qs.size()) {
                int confirm = JOptionPane.showConfirmDialog(dialog,
                    "You have unanswered questions. Submit anyway?",
                    "Confirm Submit", JOptionPane.YES_NO_OPTION);
                if (confirm != JOptionPane.YES_OPTION) return;
            }
            timer.stop();
            submitQuiz(quizId, answers, dialog);
        });

        timer.addActionListener(ev -> {
            secondsLeft[0]--;
            int m = secondsLeft[0] / 60, s = secondsLeft[0] % 60;
            timerLbl.setText(String.format("⏱ %02d:%02d", m, s));
            timeBar.setValue(secondsLeft[0]);
            // answered count
            int answeredCount = (int) radioMap.values().stream()
                .filter(rm -> rm.values().stream().anyMatch(JRadioButton::isSelected)).count();
            answerBar.setValue(answeredCount);
            answerVal.setText(answeredCount + " / " + totalQs);
            // colour transitions — mirrors timer-warning / timer-danger
            if (secondsLeft[0] <= 60) {
                timerLbl.setForeground(DANGER);
                timeBar.setForeground(DANGER);
            } else if (secondsLeft[0] <= 180) {
                timerLbl.setForeground(AMBER);
                timeBar.setForeground(AMBER);
            }
            // dot colour — mirrors .q-dot.answered
            for (Map.Entry<Integer, Map<Integer, JRadioButton>> entry : radioMap.entrySet()) {
                boolean ans = entry.getValue().values().stream().anyMatch(JRadioButton::isSelected);
                JButton dot = dotMap.get(entry.getKey());
                if (dot != null && ans) {
                    dot.setBackground(PRIMARY);
                    dot.setForeground(Color.WHITE);
                }
            }
            if (secondsLeft[0] <= 0) { timer.stop(); submitBtn.doClick(); }
        });
        timer.start();

        dialog.addWindowListener(new java.awt.event.WindowAdapter() {
            @Override public void windowClosing(java.awt.event.WindowEvent ev) { timer.stop(); }
        });

        // Submit bar — mirrors .submit-bar
        JLabel submitInfo = new JLabel("0 of " + totalQs + " questions answered");
        submitInfo.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        submitInfo.setForeground(MUTED);

        // Update submitInfo instantly on answer — mirrors onAnswer() in take.blade.php
        Runnable updateSubmitInfo = () -> {
            int c = (int) radioMap.values().stream()
                .filter(rm -> rm.values().stream().anyMatch(JRadioButton::isSelected)).count();
            submitInfo.setText(c + " of " + totalQs + " questions answered");
        };
        radioMap.values().forEach(rm -> rm.values().forEach(rb2 -> rb2.addActionListener(e -> updateSubmitInfo.run())));
        JPanel bottom = new JPanel(new BorderLayout(12, 0));
        bottom.setBackground(SURFACE);
        bottom.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(1, 0, 0, 0, BORDER_C),
            new EmptyBorder(12, 16, 12, 16)));
        bottom.add(submitInfo, BorderLayout.WEST);
        bottom.add(submitBtn,  BorderLayout.EAST);

        JPanel northStack = new JPanel();
        northStack.setLayout(new BoxLayout(northStack, BoxLayout.Y_AXIS));
        northStack.add(quizHeader);
        northStack.add(progressStrip);
        northStack.add(navPanel);

        main.add(northStack,   BorderLayout.NORTH);
        main.add(questScroll,  BorderLayout.CENTER);
        main.add(bottom,       BorderLayout.SOUTH);
        dialog.setContentPane(main);
        dialog.setVisible(true);
    }

    private void submitQuiz(int quizId, Map<String, Object> answers, JDialog dialog) {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.post("/quizzes/" + quizId + "/submit",
                    Map.of("answers", answers)));
            }
            @Override protected void done() {
                try {
                    JsonNode result = get();
                    dialog.dispose();
                    showResultDialog(result);
                    loadQuizzes();
                } catch (Exception e) {
                    JOptionPane.showMessageDialog(dialog,
                        "Submission failed: " + e.getMessage(), "Error",
                        JOptionPane.ERROR_MESSAGE);
                }
            }
        }.execute();
    }

    // ── View result ───────────────────────────────────────────────────────

    private void showResult(int quizId) {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/quizzes/" + quizId + "/result"));
            }
            @Override protected void done() {
                try {
                    JsonNode r = get();
                    if (r.has("message")) {
                        JOptionPane.showMessageDialog(QuizPanel.this, r.path("message").asText());
                        return;
                    }
                    showResultDialog(r);
                } catch (Exception e) {
                    JOptionPane.showMessageDialog(QuizPanel.this,
                        "Could not load result: " + e.getMessage());
                }
            }
        }.execute();
    }

    private void showResultDialog(JsonNode r) {
        int    score  = r.path("score").asInt();
        int    max    = r.path("max_score").asInt();
        double pct    = r.path("percentage").asDouble();
        String grade  = r.path("grade").asText("—");
        String compAt = r.path("completed_at").asText("—");
        boolean pass  = pct >= 50;

        JDialog d = new JDialog((Frame) SwingUtilities.getWindowAncestor(this),
            "Quiz Result", true);
        d.setSize(460, 560);
        d.setLocationRelativeTo(this);

        JPanel panel = new JPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBackground(SURFACE);
        panel.setBorder(new EmptyBorder(28, 32, 28, 32));

        // Score ring — mirrors .score-ring SVG in result.blade.php
        JPanel ring = new JPanel() {
            @Override protected void paintComponent(Graphics g) {
                super.paintComponent(g);
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                int cx = getWidth()/2, cy = getHeight()/2, r2 = 60;
                g2.setStroke(new java.awt.BasicStroke(12, java.awt.BasicStroke.CAP_ROUND, java.awt.BasicStroke.JOIN_ROUND));
                g2.setColor(new Color(0xF0,0xF2,0xFF));
                g2.drawOval(cx-r2, cy-r2, r2*2, r2*2);
                g2.setColor(PRIMARY);
                int angle = (int) Math.round(pct / 100.0 * 360);
                g2.drawArc(cx-r2, cy-r2, r2*2, r2*2, 90, -angle);
                g2.dispose();
            }
        };
        ring.setBackground(SURFACE);
        ring.setPreferredSize(new Dimension(140, 140));
        ring.setMaximumSize(new Dimension(140, 140));
        ring.setAlignmentX(CENTER_ALIGNMENT);

        // Score text overlaid on ring
        JLabel scoreLbl = new JLabel(score + " / " + max, SwingConstants.CENTER);
        scoreLbl.setFont(new Font("Segoe UI", Font.BOLD, 18));
        scoreLbl.setForeground(TEXT);
        scoreLbl.setAlignmentX(CENTER_ALIGNMENT);

        // Grade letter — mirrors .grade-letter
        Color gradeColor = switch (grade) {
            case "A" -> new Color(0x15, 0x57, 0x24);
            case "B" -> new Color(0x0C, 0x54, 0x60);
            case "C" -> new Color(0x85, 0x64, 0x04);
            case "D" -> new Color(0xE6, 0x7E, 0x22);
            default  -> DANGER;
        };
        JLabel gradeLbl = new JLabel(grade, SwingConstants.CENTER);
        gradeLbl.setFont(new Font("Segoe UI", Font.BOLD, 56));
        gradeLbl.setForeground(gradeColor);
        gradeLbl.setAlignmentX(CENTER_ALIGNMENT);

        JLabel pctLbl = new JLabel(pct + "%", SwingConstants.CENTER);
        pctLbl.setFont(new Font("Segoe UI", Font.BOLD, 26));
        pctLbl.setForeground(PRIMARY);
        pctLbl.setAlignmentX(CENTER_ALIGNMENT);

        // Pass/fail banner — mirrors .status-banner
        JLabel statusLbl = new JLabel(pass ? "✅  Passed" : "❌  Failed", SwingConstants.CENTER);
        statusLbl.setFont(new Font("Segoe UI", Font.BOLD, 14));
        statusLbl.setForeground(pass ? new Color(0x15,0x57,0x24) : new Color(0x72,0x1C,0x24));
        statusLbl.setOpaque(true);
        statusLbl.setBackground(pass ? new Color(0xD4,0xED,0xDA) : new Color(0xF8,0xD7,0xDA));
        statusLbl.setBorder(new EmptyBorder(8, 20, 8, 20));
        statusLbl.setAlignmentX(CENTER_ALIGNMENT);

        JSeparator sep = new JSeparator();
        sep.setMaximumSize(new Dimension(Integer.MAX_VALUE, 1));

        panel.add(ring);
        panel.add(Box.createVerticalStrut(4));
        panel.add(scoreLbl);
        panel.add(Box.createVerticalStrut(6));
        panel.add(gradeLbl);
        panel.add(pctLbl);
        panel.add(Box.createVerticalStrut(8));
        panel.add(statusLbl);
        panel.add(Box.createVerticalStrut(14));
        panel.add(sep);
        panel.add(Box.createVerticalStrut(10));
        panel.add(detailRow("Score",        score + " / " + max));
        panel.add(detailRow("Percentage",   pct + "%"));
        panel.add(detailRow("Grade",        grade));
        panel.add(detailRow("Status",       pass ? "✅ Pass" : "❌ Fail"));
        panel.add(detailRow("Submitted At", compAt));
        panel.add(detailRow("Quiz Duration", r.path("duration_minutes").asInt(0) + " minutes"));
        panel.add(Box.createVerticalStrut(12));

        // Grade scale — mirrors .grade-scale in result.blade.php
        JPanel scalePanel = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 4));
        scalePanel.setBackground(new Color(0xF8,0xF9,0xFF));
        scalePanel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C),
            new EmptyBorder(6, 10, 6, 10)));
        scalePanel.setAlignmentX(LEFT_ALIGNMENT);
        scalePanel.setMaximumSize(new Dimension(Integer.MAX_VALUE, 50));
        JLabel scaleTitle = new JLabel("Grade Scale: ");
        scaleTitle.setFont(new Font("Segoe UI", Font.BOLD, 11));
        scaleTitle.setForeground(MUTED);
        scalePanel.add(scaleTitle);
        String[][] scale = {{"A ≥80%","#D4EDDA","#155724"},{"B ≥65%","#D1ECF1","#0C5460"},
                            {"C ≥50%","#FFF3CD","#856404"},{"D ≥40%","#FFE5D0","#E67E22"},{"F <40%","#F8D7DA","#DC3545"}};
        for (String[] gs : scale) {
            JLabel gl = new JLabel(gs[0]);
            gl.setFont(new Font("Segoe UI", Font.BOLD, 11));
            gl.setOpaque(true);
            gl.setBackground(Color.decode(gs[1]));
            gl.setForeground(Color.decode(gs[2]));
            gl.setBorder(new EmptyBorder(2, 8, 2, 8));
            scalePanel.add(gl);
        }
        panel.add(scalePanel);
        panel.add(Box.createVerticalStrut(16));

        JButton closeBtn = new JButton("Close");
        styleBtn(closeBtn, PRIMARY);
        closeBtn.setAlignmentX(CENTER_ALIGNMENT);
        closeBtn.addActionListener(e -> d.dispose());
        panel.add(closeBtn);

        d.setContentPane(new JScrollPane(panel));
        d.setVisible(true);
    }

    private JPanel detailRow(String label, String value) {
        JPanel row = new JPanel(new BorderLayout());
        row.setBackground(SURFACE);
        row.setBorder(new EmptyBorder(8, 0, 8, 0));
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));
        JLabel k = new JLabel(label);
        k.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        k.setForeground(MUTED);
        JLabel v = new JLabel(value);
        v.setFont(new Font("Segoe UI", Font.BOLD, 13));
        v.setForeground(TEXT);
        row.add(k, BorderLayout.WEST);
        row.add(v, BorderLayout.EAST);
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
