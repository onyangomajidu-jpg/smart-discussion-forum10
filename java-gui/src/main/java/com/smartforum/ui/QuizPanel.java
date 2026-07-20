package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.util.*;

public class QuizPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x63, 0x66, 0xF1);
    private static final Color PURPLE   = new Color(0x8B, 0x5C, 0xF6);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color AMBER    = new Color(0xF5, 0x9E, 0x0B);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color CYAN     = new Color(0x06, 0xB6, 0xD4);
    private static final Color DARK     = new Color(0x0F, 0x17, 0x2A);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);

    private final ApiClient    api;
    private final AuthUser     user;
    private final ObjectMapper mapper = new ObjectMapper();

    private JPanel quizListPanel;
    private JLabel statusLbl;

    // store loaded quiz data for actions
    private final java.util.List<JsonNode> quizzes = new ArrayList<>();

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
        hero.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));
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

        // Quiz list
        quizListPanel = new JPanel();
        quizListPanel.setLayout(new BoxLayout(quizListPanel, BoxLayout.Y_AXIS));
        quizListPanel.setBackground(BG);
        quizListPanel.setAlignmentX(LEFT_ALIGNMENT);

        body.add(hero);
        body.add(Box.createVerticalStrut(16));
        body.add(headerRow);
        body.add(Box.createVerticalStrut(16));
        body.add(quizListPanel);

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    public void loadQuizzes() {
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
                } catch (Exception e) {
                    statusLbl.setText("Failed to load: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
    }

    private JPanel buildQuizCard(JsonNode q) {
        boolean attempted = q.path("attempted").asBoolean(false);
        boolean isOpen    = q.path("is_open").asBoolean(false);
        boolean isUpcoming= q.path("is_upcoming").asBoolean(false);
        boolean isClosed  = !isOpen && !isUpcoming && !attempted;

        String state;
        Color accentColor;
        String iconText;
        Color iconBg;
        Color iconFg;

        if (attempted) {
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

        // Icon
        JLabel iconLbl = new JLabel(iconText, SwingConstants.CENTER);
        iconLbl.setFont(new Font("Segoe UI Emoji", Font.BOLD, 18));
        iconLbl.setForeground(iconFg);
        iconLbl.setOpaque(true);
        iconLbl.setBackground(iconBg);
        iconLbl.setPreferredSize(new Dimension(48, 48));
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

        JPanel metaRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 14, 0));
        metaRow.setOpaque(false);
        metaRow.add(metaItem("👥 " + q.path("group_name").asText("—")));
        metaRow.add(metaItem("⏱ " + q.path("duration_minutes").asInt() + " min"));
        metaRow.add(metaItem("❓ " + q.path("questions_count").asInt(0) + " questions"));

        info.add(titleRow);
        info.add(Box.createVerticalStrut(4));
        info.add(metaRow);

        // Action button
        JPanel actionPanel = new JPanel(new FlowLayout(FlowLayout.RIGHT, 0, 0));
        actionPanel.setOpaque(false);
        int quizId = q.path("id").asInt();

        if (attempted) {
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
        return card;
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

    private JLabel metaItem(String text) {
        JLabel l = new JLabel(text);
        l.setFont(new Font("Segoe UI", Font.PLAIN, 12));
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

        JLabel timerLbl = new JLabel(String.format("⏱ %02d:00", duration), SwingConstants.CENTER);
        timerLbl.setFont(new Font("Segoe UI", Font.BOLD, 20));
        timerLbl.setForeground(GREEN);
        timerLbl.setOpaque(true);
        timerLbl.setBackground(DARK);
        timerLbl.setBorder(new EmptyBorder(12, 0, 12, 0));

        JPanel questionsPanel = new JPanel();
        questionsPanel.setLayout(new BoxLayout(questionsPanel, BoxLayout.Y_AXIS));
        questionsPanel.setBackground(BG);
        questionsPanel.setBorder(new EmptyBorder(16, 16, 16, 16));

        Map<Integer, Map<Integer, JRadioButton>> radioMap = new LinkedHashMap<>();

        int qNum = 1;
        for (JsonNode q : qs) {
            int qId = q.path("id").asInt();
            JPanel qCard = new JPanel();
            qCard.setLayout(new BoxLayout(qCard, BoxLayout.Y_AXIS));
            qCard.setBackground(SURFACE);
            qCard.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 4, 0, 0, PRIMARY),
                BorderFactory.createCompoundBorder(
                    BorderFactory.createLineBorder(BORDER_C),
                    new EmptyBorder(14, 16, 14, 16))));
            qCard.setAlignmentX(LEFT_ALIGNMENT);
            qCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));

            JLabel qLbl = new JLabel("<html><b>Q" + qNum + ". " + q.path("question").asText() + "</b></html>");
            qLbl.setFont(new Font("Segoe UI", Font.PLAIN, 14));
            qLbl.setForeground(TEXT);
            qCard.add(qLbl);
            qCard.add(Box.createVerticalStrut(10));

            ButtonGroup bg = new ButtonGroup();
            Map<Integer, JRadioButton> radios = new LinkedHashMap<>();
            int idx = 0;
            for (JsonNode opt : q.path("options")) {
                JRadioButton rb = new JRadioButton(opt.asText());
                rb.setFont(new Font("Segoe UI", Font.PLAIN, 13));
                rb.setBackground(SURFACE);
                bg.add(rb);
                radios.put(idx, rb);
                qCard.add(rb);
                idx++;
            }
            radioMap.put(qId, radios);
            questionsPanel.add(qCard);
            questionsPanel.add(Box.createVerticalStrut(12));
            qNum++;
        }

        JButton submitBtn = new JButton("Submit Quiz");
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
            if (secondsLeft[0] <= 60)  timerLbl.setForeground(DANGER);
            else if (secondsLeft[0] <= 180) timerLbl.setForeground(AMBER);
            if (secondsLeft[0] <= 0) { timer.stop(); submitBtn.doClick(); }
        });
        timer.start();

        dialog.addWindowListener(new java.awt.event.WindowAdapter() {
            @Override public void windowClosing(java.awt.event.WindowEvent ev) { timer.stop(); }
        });

        JPanel bottom = new JPanel(new FlowLayout(FlowLayout.RIGHT, 12, 10));
        bottom.setBackground(SURFACE);
        bottom.setBorder(BorderFactory.createMatteBorder(1, 0, 0, 0, BORDER_C));
        bottom.add(submitBtn);

        main.add(timerLbl, BorderLayout.NORTH);
        main.add(new JScrollPane(questionsPanel), BorderLayout.CENTER);
        main.add(bottom, BorderLayout.SOUTH);
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
        d.setSize(440, 480);
        d.setLocationRelativeTo(this);

        JPanel panel = new JPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBackground(SURFACE);
        panel.setBorder(new EmptyBorder(28, 32, 28, 32));

        // Grade circle
        JLabel gradeLbl = new JLabel(grade, SwingConstants.CENTER);
        gradeLbl.setFont(new Font("Segoe UI", Font.BOLD, 64));
        Color gradeColor = switch (grade) {
            case "A" -> new Color(0x15, 0x57, 0x24);
            case "B" -> new Color(0x0C, 0x54, 0x60);
            case "C" -> new Color(0x85, 0x64, 0x04);
            case "D" -> new Color(0xE6, 0x7E, 0x22);
            default  -> DANGER;
        };
        gradeLbl.setForeground(gradeColor);
        gradeLbl.setAlignmentX(CENTER_ALIGNMENT);

        JLabel pctLbl = new JLabel(pct + "%", SwingConstants.CENTER);
        pctLbl.setFont(new Font("Segoe UI", Font.BOLD, 28));
        pctLbl.setForeground(PRIMARY);
        pctLbl.setAlignmentX(CENTER_ALIGNMENT);

        JLabel statusLbl = new JLabel(pass ? "✅  Passed" : "❌  Failed", SwingConstants.CENTER);
        statusLbl.setFont(new Font("Segoe UI", Font.BOLD, 15));
        statusLbl.setForeground(pass ? GREEN : DANGER);
        statusLbl.setAlignmentX(CENTER_ALIGNMENT);

        JSeparator sep = new JSeparator();
        sep.setMaximumSize(new Dimension(Integer.MAX_VALUE, 1));

        panel.add(gradeLbl);
        panel.add(Box.createVerticalStrut(4));
        panel.add(pctLbl);
        panel.add(Box.createVerticalStrut(6));
        panel.add(statusLbl);
        panel.add(Box.createVerticalStrut(16));
        panel.add(sep);
        panel.add(Box.createVerticalStrut(16));
        panel.add(detailRow("Score",        score + " / " + max));
        panel.add(detailRow("Grade",        grade));
        panel.add(detailRow("Status",       pass ? "Pass" : "Fail"));
        panel.add(detailRow("Submitted At", compAt));
        panel.add(Box.createVerticalStrut(20));

        JButton closeBtn = new JButton("Close");
        styleBtn(closeBtn, PRIMARY);
        closeBtn.setAlignmentX(CENTER_ALIGNMENT);
        closeBtn.addActionListener(e -> d.dispose());
        panel.add(closeBtn);

        d.setContentPane(panel);
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
