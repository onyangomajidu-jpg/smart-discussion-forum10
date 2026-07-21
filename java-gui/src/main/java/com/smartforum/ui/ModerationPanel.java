package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.table.DefaultTableModel;
import java.awt.*;
import java.util.HashMap;
import java.util.Map;

public class ModerationPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x4F, 0x46, 0xE5);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color AMBER    = new Color(0xF5, 0x9E, 0x0B);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color PURPLE   = new Color(0x8B, 0x5C, 0xF6);
    private static final Color BLUE     = new Color(0x1D, 0x4E, 0xD8);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);

    private final ApiClient api;
    private final ObjectMapper mapper = new ObjectMapper();

    // Warnings
    private JComboBox<UserItem> cbWarnUser;
    private JTextField tfWarnReason;
    private DefaultTableModel warningsModel;

    // Blacklists
    private JComboBox<UserItem> cbBanUser;
    private JTextField tfBanReason;
    private JSpinner spDays;
    private DefaultTableModel blacklistModel;

    private JLabel statusLbl;

    public ModerationPanel(ApiClient api, AuthUser user) {
        this.api = api;
        if (!user.isAdmin()) {
            setLayout(new BorderLayout());
            add(new JLabel("Access denied.", SwingConstants.CENTER), BorderLayout.CENTER);
            return;
        }
        setBackground(BG);
        setLayout(new BorderLayout());
        buildUI();
        loadAll();
    }

    private void buildUI() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(24, 24, 40, 24));

        JLabel title = new JLabel("Moderation");
        title.setFont(new Font("Segoe UI", Font.BOLD, 22));
        title.setForeground(TEXT);
        title.setAlignmentX(LEFT_ALIGNMENT);

        statusLbl = new JLabel(" ");
        statusLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        statusLbl.setForeground(MUTED);
        statusLbl.setAlignmentX(LEFT_ALIGNMENT);

        JButton refreshBtn = new JButton("⟳ Refresh");
        styleBtn(refreshBtn, PRIMARY);
        refreshBtn.addActionListener(e -> loadAll());

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
        body.add(buildAdminStatsSection());
        body.add(Box.createVerticalStrut(20));
        body.add(buildWarningsSection());
        body.add(Box.createVerticalStrut(20));
        body.add(buildBlacklistSection());

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    // ── Admin Dashboard Stats ─────────────────────────────────────────────

    private JLabel lblMembers, lblLecturers, lblQuizzes, lblOpenWarnings, lblActiveBans;
    private DefaultTableModel recentUsersModel;

    private JPanel buildAdminStatsSection() {
        JPanel section = new JPanel();
        section.setLayout(new BoxLayout(section, BoxLayout.Y_AXIS));
        section.setBackground(BG);
        section.setAlignmentX(LEFT_ALIGNMENT);

        // KPI row
        JPanel kpiRow = new JPanel(new GridLayout(1, 5, 12, 0));
        kpiRow.setBackground(BG);
        kpiRow.setAlignmentX(LEFT_ALIGNMENT);
        kpiRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 90));

        lblMembers      = new JLabel("—");
        lblLecturers    = new JLabel("—");
        lblQuizzes      = new JLabel("—");
        lblOpenWarnings = new JLabel("—");
        lblActiveBans   = new JLabel("—");

        kpiRow.add(kpiCard("👥", lblMembers,      "Members",       PRIMARY));
        kpiRow.add(kpiCard("🎓", lblLecturers,    "Lecturers",     PURPLE));
        kpiRow.add(kpiCard("📋", lblQuizzes,      "Quizzes",       BLUE));
        kpiRow.add(kpiCard("⚠",  lblOpenWarnings, "Open Warnings", AMBER));
        kpiRow.add(kpiCard("🚫", lblActiveBans,   "Active Bans",   DANGER));

        // Recent users table
        JPanel tableCard = card("👤 Recent Users", PRIMARY);
        recentUsersModel = new DefaultTableModel(
            new String[]{"Name", "Email", "Role", "Joined"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable table = new JTable(recentUsersModel);
        table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        table.setRowHeight(28);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
        table.setGridColor(BORDER_C);
        JPanel tableBody = new JPanel(new BorderLayout());
        tableBody.setBackground(SURFACE);
        tableBody.add(new JScrollPane(table), BorderLayout.CENTER);
        tableCard.add(tableBody, BorderLayout.CENTER);
        tableCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 280));

        section.add(kpiRow);
        section.add(Box.createVerticalStrut(16));
        section.add(tableCard);
        return section;
    }

    private JPanel kpiCard(String icon, JLabel valLbl, String caption, Color accent) {
        JPanel card = new JPanel(new BorderLayout(0, 4));
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, accent),
            BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDER_C),
                new EmptyBorder(12, 14, 12, 14))));
        JLabel ico = new JLabel(icon);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 18));
        valLbl.setFont(new Font("Segoe UI", Font.BOLD, 24));
        valLbl.setForeground(accent);
        JLabel lbl = new JLabel(caption);
        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        lbl.setForeground(MUTED);
        JPanel top = new JPanel(new FlowLayout(FlowLayout.LEFT, 0, 0));
        top.setOpaque(false);
        top.add(ico);
        card.add(top,    BorderLayout.NORTH);
        card.add(valLbl, BorderLayout.CENTER);
        card.add(lbl,    BorderLayout.SOUTH);
        return card;
    }

    private void loadAdminStats() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/admin/dashboard"));
            }
            @Override protected void done() {
                try {
                    JsonNode d = get();
                    lblMembers.setText(String.valueOf(d.path("members").asInt(0)));
                    lblLecturers.setText(String.valueOf(d.path("lecturers").asInt(0)));
                    lblQuizzes.setText(String.valueOf(d.path("quizzes").asInt(0)));
                    lblOpenWarnings.setText(String.valueOf(d.path("open_warnings").asInt(0)));
                    lblActiveBans.setText(String.valueOf(d.path("active_bans").asInt(0)));
                    recentUsersModel.setRowCount(0);
                    for (JsonNode u : d.path("recent_users")) {
                        recentUsersModel.addRow(new Object[]{
                            u.path("name").asText(),
                            u.path("email").asText(),
                            u.path("role").asText(),
                            u.path("created_at").asText("—")
                        });
                    }
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    // ── Warnings ──────────────────────────────────────────────────────────

    private JPanel buildWarningsSection() {
        JPanel section = new JPanel();
        section.setLayout(new BoxLayout(section, BoxLayout.Y_AXIS));
        section.setBackground(BG);
        section.setAlignmentX(LEFT_ALIGNMENT);

        // Issue warning form
        JPanel formCard = card("⚠️ Issue Warning", AMBER);
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(14, 14, 14, 14));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        cbWarnUser = new JComboBox<>();
        cbWarnUser.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfWarnReason = new JTextField();
        tfWarnReason.setFont(new Font("Segoe UI", Font.PLAIN, 13));

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(fieldLabel("User"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(cbWarnUser, gc);
        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0; form.add(fieldLabel("Reason"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfWarnReason, gc);
        gc.gridx = 1; gc.gridy = 2;
        JButton issueBtn = new JButton("Issue Warning");
        styleBtn(issueBtn, AMBER);
        issueBtn.addActionListener(e -> issueWarning());
        form.add(issueBtn, gc);
        formCard.add(form, BorderLayout.CENTER);

        // Warnings table
        JPanel tableCard = card("📋 Warnings", AMBER);
        warningsModel = new DefaultTableModel(
            new String[]{"ID", "User", "Reason", "Resolved"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable table = new JTable(warningsModel);
        table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        table.setRowHeight(28);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
        JButton resolveBtn = new JButton("Resolve Selected");
        styleBtn(resolveBtn, GREEN);
        resolveBtn.addActionListener(e -> {
            int row = table.getSelectedRow();
            if (row < 0) return;
            int id = (int) warningsModel.getValueAt(row, 0);
            resolveWarning(id);
        });
        JButton delWarnBtn = new JButton("Delete Selected");
        styleBtn(delWarnBtn, DANGER);
        delWarnBtn.addActionListener(e -> {
            int row = table.getSelectedRow();
            if (row < 0) return;
            int id = (int) warningsModel.getValueAt(row, 0);
            deleteWarning(id);
        });
        JPanel tableActions = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 8));
        tableActions.setBackground(SURFACE);
        tableActions.add(resolveBtn);
        tableActions.add(delWarnBtn);
        JPanel tableBody = new JPanel(new BorderLayout());
        tableBody.setBackground(SURFACE);
        tableBody.add(new JScrollPane(table), BorderLayout.CENTER);
        tableBody.add(tableActions, BorderLayout.SOUTH);
        tableCard.add(tableBody, BorderLayout.CENTER);

        section.add(formCard);
        section.add(Box.createVerticalStrut(12));
        section.add(tableCard);
        return section;
    }

    // ── Blacklists ────────────────────────────────────────────────────────

    private JPanel buildBlacklistSection() {
        JPanel section = new JPanel();
        section.setLayout(new BoxLayout(section, BoxLayout.Y_AXIS));
        section.setBackground(BG);
        section.setAlignmentX(LEFT_ALIGNMENT);

        JPanel formCard = card("🚫 Blacklist User", DANGER);
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(14, 14, 14, 14));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        cbBanUser   = new JComboBox<>();
        cbBanUser.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfBanReason = new JTextField();
        tfBanReason.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        spDays = new JSpinner(new SpinnerNumberModel(7, 1, 365, 1));
        spDays.setFont(new Font("Segoe UI", Font.PLAIN, 13));

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(fieldLabel("User"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(cbBanUser, gc);
        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0; form.add(fieldLabel("Reason"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfBanReason, gc);
        gc.gridx = 0; gc.gridy = 2; gc.weightx = 0; form.add(fieldLabel("Days"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(spDays, gc);
        gc.gridx = 1; gc.gridy = 3;
        JButton banBtn = new JButton("Blacklist User");
        styleBtn(banBtn, DANGER);
        banBtn.addActionListener(e -> blacklistUser());
        form.add(banBtn, gc);
        formCard.add(form, BorderLayout.CENTER);

        JPanel tableCard = card("📋 Blacklisted Users", DANGER);
        blacklistModel = new DefaultTableModel(
            new String[]{"ID", "User", "Reason", "Expires"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable table = new JTable(blacklistModel);
        table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        table.setRowHeight(28);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
        JButton liftBtn = new JButton("Lift Ban");
        styleBtn(liftBtn, GREEN);
        liftBtn.addActionListener(e -> {
            int row = table.getSelectedRow();
            if (row < 0) return;
            int id = (int) blacklistModel.getValueAt(row, 0);
            liftBan(id);
        });
        JPanel tableActions = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 8));
        tableActions.setBackground(SURFACE);
        tableActions.add(liftBtn);
        JPanel tableBody = new JPanel(new BorderLayout());
        tableBody.setBackground(SURFACE);
        tableBody.add(new JScrollPane(table), BorderLayout.CENTER);
        tableBody.add(tableActions, BorderLayout.SOUTH);
        tableCard.add(tableBody, BorderLayout.CENTER);

        section.add(formCard);
        section.add(Box.createVerticalStrut(12));
        section.add(tableCard);
        return section;
    }

    // ── Data loading ──────────────────────────────────────────────────────

    private void loadAll() {
        loadAdminStats();
        loadUsers();
        loadWarnings();
        loadBlacklists();
    }

    private void loadUsers() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/admin/users"));
            }
            @Override protected void done() {
                try {
                    JsonNode users = get();
                    cbWarnUser.removeAllItems();
                    cbBanUser.removeAllItems();
                    for (JsonNode u : users) {
                        UserItem item = new UserItem(u.path("id").asInt(), u.path("name").asText());
                        cbWarnUser.addItem(item);
                        cbBanUser.addItem(item);
                    }
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    private void loadWarnings() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/admin/warnings"));
            }
            @Override protected void done() {
                try {
                    warningsModel.setRowCount(0);
                    for (JsonNode w : get()) {
                        warningsModel.addRow(new Object[]{
                            w.path("id").asInt(),
                            w.path("user_name").asText(),
                            w.path("reason").asText(),
                            w.path("resolved_at").isNull() ? "No" : "Yes"
                        });
                    }
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    private void loadBlacklists() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/admin/blacklists"));
            }
            @Override protected void done() {
                try {
                    blacklistModel.setRowCount(0);
                    for (JsonNode b : get()) {
                        blacklistModel.addRow(new Object[]{
                            b.path("id").asInt(),
                            b.path("user_name").asText(),
                            b.path("reason").asText(),
                            b.path("expires_at").asText("—")
                        });
                    }
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    // ── Actions ───────────────────────────────────────────────────────────

    private void issueWarning() {
        UserItem u = (UserItem) cbWarnUser.getSelectedItem();
        String reason = tfWarnReason.getText().trim();
        if (u == null || reason.isEmpty()) { showStatus("Select user and enter reason.", AMBER); return; }
        Map<String, Object> body = new HashMap<>();
        body.put("user_id", u.id);
        body.put("reason", reason);
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.post("/admin/warnings", body);
                return null;
            }
            @Override protected void done() {
                try { get(); tfWarnReason.setText(""); loadWarnings(); showStatus("Warning issued.", GREEN); }
                catch (Exception e) { showStatus("Failed to issue warning.", DANGER); }
            }
        }.execute();
    }

    private void resolveWarning(int id) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.patch("/admin/warnings/" + id + "/resolve", Map.of());
                return null;
            }
            @Override protected void done() {
                try { get(); loadWarnings(); }
                catch (Exception e) { showStatus("Failed to resolve.", DANGER); }
            }
        }.execute();
    }

    private void deleteWarning(int id) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.delete("/admin/warnings/" + id);
                return null;
            }
            @Override protected void done() {
                try { get(); loadWarnings(); }
                catch (Exception e) { showStatus("Failed to delete.", DANGER); }
            }
        }.execute();
    }

    private void blacklistUser() {
        UserItem u = (UserItem) cbBanUser.getSelectedItem();
        String reason = tfBanReason.getText().trim();
        int days = (int) spDays.getValue();
        if (u == null || reason.isEmpty()) { showStatus("Select user and enter reason.", DANGER); return; }
        Map<String, Object> body = new HashMap<>();
        body.put("user_id", u.id);
        body.put("reason", reason);
        body.put("days", days);
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.post("/admin/blacklists", body);
                return null;
            }
            @Override protected void done() {
                try { get(); tfBanReason.setText(""); loadBlacklists(); showStatus("User blacklisted.", GREEN); }
                catch (Exception e) { showStatus("Failed to blacklist user.", DANGER); }
            }
        }.execute();
    }

    private void liftBan(int id) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.delete("/admin/blacklists/" + id);
                return null;
            }
            @Override protected void done() {
                try { get(); loadBlacklists(); showStatus("Ban lifted.", GREEN); }
                catch (Exception e) { showStatus("Failed to lift ban.", DANGER); }
            }
        }.execute();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private void showStatus(String msg, Color color) {
        statusLbl.setText(msg);
        statusLbl.setForeground(color);
    }

    private JPanel card(String title, Color accent) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setAlignmentX(LEFT_ALIGNMENT);
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, 600));
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

    private JLabel fieldLabel(String text) {
        JLabel l = new JLabel(text);
        l.setFont(new Font("Segoe UI", Font.BOLD, 12));
        l.setForeground(MUTED);
        return l;
    }

    private void styleBtn(JButton btn, Color bg) {
        btn.setFont(new Font("Segoe UI", Font.BOLD, 12));
        btn.setForeground(Color.WHITE);
        btn.setBackground(bg);
        btn.setBorderPainted(false);
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
    }

    private static class UserItem {
        final int id;
        final String name;
        UserItem(int id, String name) { this.id = id; this.name = name; }
        @Override public String toString() { return name; }
    }
}
