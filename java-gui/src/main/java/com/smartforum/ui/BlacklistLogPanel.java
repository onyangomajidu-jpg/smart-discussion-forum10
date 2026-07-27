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

public class BlacklistLogPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x4F, 0x46, 0xE5);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);

    private final ApiClient    api;
    private final ObjectMapper mapper = new ObjectMapper();

    private JComboBox<UserItem> cbUser;
    private JTextField          tfReason;
    private JSpinner            spDays;
    private DefaultTableModel   tableModel;
    private JLabel              statusLbl;

    public BlacklistLogPanel(ApiClient api, AuthUser user) {
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

        JLabel breadcrumb = new JLabel("Admin / Blacklist Log");
        breadcrumb.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        breadcrumb.setForeground(MUTED);
        breadcrumb.setAlignmentX(LEFT_ALIGNMENT);

        JLabel pageTitle = new JLabel("🚫  Blacklist Log");
        pageTitle.setFont(new Font("Segoe UI", Font.BOLD, 20));
        pageTitle.setForeground(TEXT);
        pageTitle.setAlignmentX(LEFT_ALIGNMENT);

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
        headerRow.add(statusLbl,  BorderLayout.WEST);
        headerRow.add(refreshBtn, BorderLayout.EAST);

        body.add(breadcrumb);
        body.add(Box.createVerticalStrut(4));
        body.add(pageTitle);
        body.add(Box.createVerticalStrut(12));
        body.add(headerRow);
        body.add(Box.createVerticalStrut(16));
        body.add(buildBlacklistUserCard());
        body.add(Box.createVerticalStrut(16));
        body.add(buildBlacklistTableCard());

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    // Blacklist User form: User (select), Reason, Days
    private JPanel buildBlacklistUserCard() {
        JPanel formCard = card("➕ Blacklist User", DANGER);
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(14, 14, 14, 14));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        cbUser   = new JComboBox<>();
        cbUser.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfReason = new JTextField();
        tfReason.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfReason.setToolTipText("Reason for ban");
        spDays   = new JSpinner(new SpinnerNumberModel(30, 1, 365, 1));
        spDays.setFont(new Font("Segoe UI", Font.PLAIN, 13));

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(fieldLabel("User"), gc);
        gc.gridx = 1; gc.weightx = 1;                form.add(cbUser, gc);
        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0; form.add(fieldLabel("Reason"), gc);
        gc.gridx = 1; gc.weightx = 1;                form.add(tfReason, gc);
        gc.gridx = 0; gc.gridy = 2; gc.weightx = 0; form.add(fieldLabel("Days"), gc);
        gc.gridx = 1; gc.weightx = 1;                form.add(spDays, gc);
        gc.gridx = 1; gc.gridy = 3;
        JButton banBtn = new JButton("🚫 Blacklist");
        styleBtn(banBtn, DANGER);
        banBtn.addActionListener(e -> blacklistUser());
        form.add(banBtn, gc);

        formCard.add(form, BorderLayout.CENTER);
        formCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 220));
        return formCard;
    }

    // All Blacklist Entries table: #, User, Reason, Banned By, Banned At, Expires At, Status + Lift Ban
    private JPanel buildBlacklistTableCard() {
        JPanel tableCard = card("📋 All Blacklist Entries", DANGER);
        tableModel = new DefaultTableModel(
            new String[]{"#", "User", "Reason", "Banned By", "Banned At", "Expires At", "Status"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable table = new JTable(tableModel);
        table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        table.setRowHeight(28);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
        table.setGridColor(BORDER_C);
        table.getColumnModel().getColumn(0).setMaxWidth(50);

        JButton liftBtn = new JButton("🔓 Lift Ban");
        styleBtn(liftBtn, GREEN);
        liftBtn.addActionListener(e -> {
            int row = table.getSelectedRow();
            if (row < 0) { showStatus("Select a blacklist entry first.", DANGER); return; }
            int id = (int) tableModel.getValueAt(row, 0);
            if (JOptionPane.showConfirmDialog(this, "Remove this ban?", "Confirm",
                    JOptionPane.YES_NO_OPTION) == JOptionPane.YES_OPTION)
                liftBan(id);
        });

        JPanel actions = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 8));
        actions.setBackground(SURFACE);
        actions.add(liftBtn);

        JPanel tableBody = new JPanel(new BorderLayout());
        tableBody.setBackground(SURFACE);
        tableBody.add(new JScrollPane(table), BorderLayout.CENTER);
        tableBody.add(actions, BorderLayout.SOUTH);
        tableCard.add(tableBody, BorderLayout.CENTER);
        tableCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 500));
        return tableCard;
    }

    // ── Data loading ──────────────────────────────────────────────────────

    public void loadAll() {
        loadUsers();
        loadBlacklists();
    }

    private void loadUsers() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/admin/users"));
            }
            @Override protected void done() {
                try {
                    cbUser.removeAllItems();
                    for (JsonNode u : get())
                        cbUser.addItem(new UserItem(u.path("id").asInt(), u.path("name").asText()));
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
                    tableModel.setRowCount(0);
                    for (JsonNode b : get()) {
                        String bannedAt  = b.path("created_at").asText("—");
                        String expiresAt = b.path("expires_at").asText("");
                        boolean isPermanent = b.path("expires_at").isNull() || expiresAt.isBlank();
                        boolean isActive = isPermanent ||
                            java.time.Instant.parse(expiresAt).isAfter(java.time.Instant.now());
                        tableModel.addRow(new Object[]{
                            b.path("id").asInt(),
                            b.path("user_name").asText("—"),
                            b.path("reason").asText(),
                            b.path("banned_by").asText("—"),
                            bannedAt.length()  >= 10 ? bannedAt.substring(0, 10)  : bannedAt,
                            isPermanent ? "Permanent" : (expiresAt.length() >= 10 ? expiresAt.substring(0, 10) : expiresAt),
                            isActive ? "Active" : "Expired"
                        });
                    }
                    showStatus("Loaded " + tableModel.getRowCount() + " entries.", MUTED);
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    // ── Actions ───────────────────────────────────────────────────────────

    private void blacklistUser() {
        UserItem u = (UserItem) cbUser.getSelectedItem();
        String reason = tfReason.getText().trim();
        if (u == null || reason.isEmpty()) { showStatus("Select user and enter reason.", DANGER); return; }
        Map<String, Object> body = new HashMap<>();
        body.put("user_id", u.id);
        body.put("reason", reason);
        body.put("days", (int) spDays.getValue());
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.post("/admin/blacklists", body); return null;
            }
            @Override protected void done() {
                try { get(); tfReason.setText(""); loadBlacklists(); showStatus("User blacklisted.", GREEN); }
                catch (Exception e) { showStatus("Failed to blacklist user.", DANGER); }
            }
        }.execute();
    }

    private void liftBan(int id) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.delete("/admin/blacklists/" + id); return null;
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
        final int    id;
        final String name;
        UserItem(int id, String name) { this.id = id; this.name = name; }
        @Override public String toString() { return name; }
    }
}
