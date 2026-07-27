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

public class WarningRegistryPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x4F, 0x46, 0xE5);
    private static final Color AMBER    = new Color(0xF5, 0x9E, 0x0B);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);

    private final ApiClient    api;
    private final ObjectMapper mapper = new ObjectMapper();

    private JComboBox<UserItem> cbUser;
    private JTextField          tfReason;
    private JSpinner            spAutoBanDays;
    private DefaultTableModel   tableModel;
    private JLabel              statusLbl;

    public WarningRegistryPanel(ApiClient api, AuthUser user) {
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

        // Breadcrumb / page title
        JLabel breadcrumb = new JLabel("Admin / Warning Registry");
        breadcrumb.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        breadcrumb.setForeground(MUTED);
        breadcrumb.setAlignmentX(LEFT_ALIGNMENT);

        JLabel pageTitle = new JLabel("⚠️  Warning Registry");
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
        body.add(buildIssueWarningCard());
        body.add(Box.createVerticalStrut(16));
        body.add(buildWarningsTableCard());

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    // Issue Warning form: User (members only), Reason, Auto-ban duration (days)
    private JPanel buildIssueWarningCard() {
        JPanel formCard = card("➕ Issue Warning", AMBER);
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(14, 14, 14, 14));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        cbUser        = new JComboBox<>();
        cbUser.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfReason      = new JTextField();
        tfReason.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfReason.setToolTipText("Reason for warning");
        spAutoBanDays = new JSpinner(new SpinnerNumberModel(30, 1, 365, 1));
        spAutoBanDays.setFont(new Font("Segoe UI", Font.PLAIN, 13));

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(fieldLabel("User"), gc);
        gc.gridx = 1; gc.weightx = 1;                form.add(cbUser, gc);
        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0; form.add(fieldLabel("Reason"), gc);
        gc.gridx = 1; gc.weightx = 1;                form.add(tfReason, gc);
        gc.gridx = 0; gc.gridy = 2; gc.weightx = 0; form.add(fieldLabel("Auto-ban duration (days)"), gc);
        gc.gridx = 1; gc.weightx = 1;                form.add(spAutoBanDays, gc);
        gc.gridx = 1; gc.gridy = 3;
        JButton issueBtn = new JButton("⚠️ Issue Warning");
        styleBtn(issueBtn, AMBER);
        issueBtn.addActionListener(e -> issueWarning());
        form.add(issueBtn, gc);

        formCard.add(form, BorderLayout.CENTER);
        formCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 220));
        return formCard;
    }

    // All Warnings table: #, User, Reason, Issued By, Issued At, Status, Resolved By + Resolve/Delete actions
    private JPanel buildWarningsTableCard() {
        JPanel tableCard = card("📋 All Warnings", AMBER);
        tableModel = new DefaultTableModel(
            new String[]{"#", "User", "Reason", "Issued By", "Issued At", "Status", "Resolved By"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable table = new JTable(tableModel);
        table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        table.setRowHeight(28);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
        table.setGridColor(BORDER_C);
        table.getColumnModel().getColumn(0).setMaxWidth(50);

        JButton resolveBtn = new JButton("✔ Resolve");
        styleBtn(resolveBtn, GREEN);
        resolveBtn.addActionListener(e -> {
            int row = table.getSelectedRow();
            if (row < 0) { showStatus("Select a warning first.", AMBER); return; }
            resolveWarning((int) tableModel.getValueAt(row, 0));
        });

        JButton deleteBtn = new JButton("🗑 Delete");
        styleBtn(deleteBtn, DANGER);
        deleteBtn.addActionListener(e -> {
            int row = table.getSelectedRow();
            if (row < 0) { showStatus("Select a warning first.", AMBER); return; }
            int id = (int) tableModel.getValueAt(row, 0);
            if (JOptionPane.showConfirmDialog(this, "Delete this warning?", "Confirm",
                    JOptionPane.YES_NO_OPTION) == JOptionPane.YES_OPTION)
                deleteWarning(id);
        });

        JPanel actions = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 8));
        actions.setBackground(SURFACE);
        actions.add(resolveBtn);
        actions.add(deleteBtn);

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
        loadWarnings();
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

    private void loadWarnings() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/admin/warnings"));
            }
            @Override protected void done() {
                try {
                    tableModel.setRowCount(0);
                    for (JsonNode w : get()) {
                        String issuedAt = w.path("created_at").asText("—");
                        tableModel.addRow(new Object[]{
                            w.path("id").asInt(),
                            w.path("user_name").asText("—"),
                            w.path("reason").asText(),
                            w.path("issued_by").asText("—"),
                            issuedAt.length() >= 10 ? issuedAt.substring(0, 10) : issuedAt,
                            w.path("resolved_at").isNull() ? "Unresolved" : "Resolved",
                            w.path("resolved_by").asText("—")
                        });
                    }
                    showStatus("Loaded " + tableModel.getRowCount() + " warnings.", MUTED);
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    // ── Actions ───────────────────────────────────────────────────────────

    private void issueWarning() {
        UserItem u = (UserItem) cbUser.getSelectedItem();
        String reason = tfReason.getText().trim();
        if (u == null || reason.isEmpty()) { showStatus("Select user and enter reason.", AMBER); return; }
        Map<String, Object> body = new HashMap<>();
        body.put("user_id", u.id);
        body.put("reason", reason);
        body.put("auto_blacklist_days", (int) spAutoBanDays.getValue());
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.post("/admin/warnings", body); return null;
            }
            @Override protected void done() {
                try { get(); tfReason.setText(""); loadWarnings(); showStatus("Warning issued.", GREEN); }
                catch (Exception e) { showStatus("Failed to issue warning.", DANGER); }
            }
        }.execute();
    }

    private void resolveWarning(int id) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.patch("/admin/warnings/" + id + "/resolve", Map.of()); return null;
            }
            @Override protected void done() {
                try { get(); loadWarnings(); showStatus("Warning resolved.", GREEN); }
                catch (Exception e) { showStatus("Failed to resolve.", DANGER); }
            }
        }.execute();
    }

    private void deleteWarning(int id) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.delete("/admin/warnings/" + id); return null;
            }
            @Override protected void done() {
                try { get(); loadWarnings(); showStatus("Warning deleted.", GREEN); }
                catch (Exception e) { showStatus("Failed to delete.", DANGER); }
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
