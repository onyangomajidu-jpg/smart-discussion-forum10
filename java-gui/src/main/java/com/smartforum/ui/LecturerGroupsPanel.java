package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.table.DefaultTableModel;
import java.awt.*;
import java.util.Map;

public class LecturerGroupsPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x63, 0x66, 0xF1);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);

    private final ApiClient    api;
    private final ObjectMapper mapper = new ObjectMapper();

    private JTextField         tfName, tfDesc;
    private DefaultTableModel  groupsModel;
    private JLabel             statusLbl;

    public LecturerGroupsPanel(ApiClient api, AuthUser user) {
        this.api = api;
        setBackground(BG);
        setLayout(new BorderLayout());
        buildUI();
        loadGroups();
    }

    private void buildUI() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(24, 24, 40, 24));

        // Header
        JLabel title = new JLabel("👥 My Groups");
        title.setFont(new Font("Segoe UI", Font.BOLD, 22));
        title.setForeground(TEXT);
        title.setAlignmentX(LEFT_ALIGNMENT);
        JLabel sub = new JLabel("Create and manage your class groups");
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        sub.setForeground(MUTED);
        sub.setAlignmentX(LEFT_ALIGNMENT);

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
        refreshBtn.addActionListener(e -> loadGroups());

        JPanel headerRow = new JPanel(new BorderLayout());
        headerRow.setBackground(BG);
        headerRow.setAlignmentX(LEFT_ALIGNMENT);
        headerRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
        headerRow.add(title,      BorderLayout.WEST);
        headerRow.add(refreshBtn, BorderLayout.EAST);

        // Create group form card
        JPanel formCard = sectionCard("➕ Create New Group", PRIMARY);
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(14, 14, 14, 14));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        tfName = new JTextField();
        tfName.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfName.putClientProperty("JTextField.placeholderText", "e.g. CS101 - Group A");
        tfDesc = new JTextField();
        tfDesc.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfDesc.putClientProperty("JTextField.placeholderText", "Optional description");

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(fieldLabel("Group Name *"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfName, gc);
        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0; form.add(fieldLabel("Description"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfDesc, gc);
        gc.gridx = 1; gc.gridy = 2;
        JButton createBtn = new JButton("+ Create Group");
        styleBtn(createBtn, GREEN);
        createBtn.addActionListener(e -> createGroup());
        form.add(createBtn, gc);
        formCard.add(form, BorderLayout.CENTER);

        // Groups table card
        JPanel tableCard = sectionCard("📋 Your Groups", PRIMARY);
        groupsModel = new DefaultTableModel(
            new String[]{"ID", "Name", "Description", "Members", "Created"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable table = new JTable(groupsModel);
        table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        table.setRowHeight(30);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
        table.setGridColor(BORDER_C);

        JButton deleteBtn = new JButton("🗑 Delete Selected");
        styleBtn(deleteBtn, DANGER);
        deleteBtn.addActionListener(e -> {
            int row = table.getSelectedRow();
            if (row < 0) { showStatus("Select a group to delete.", DANGER); return; }
            int id = (int) groupsModel.getValueAt(row, 0);
            String name = (String) groupsModel.getValueAt(row, 1);
            int confirm = JOptionPane.showConfirmDialog(this,
                "Delete group '" + name + "'? This cannot be undone.",
                "Confirm Delete", JOptionPane.YES_NO_OPTION);
            if (confirm == JOptionPane.YES_OPTION) deleteGroup(id);
        });

        JPanel tableActions = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 8));
        tableActions.setBackground(SURFACE);
        tableActions.add(deleteBtn);
        JPanel tableBody = new JPanel(new BorderLayout());
        tableBody.setBackground(SURFACE);
        tableBody.add(new JScrollPane(table), BorderLayout.CENTER);
        tableBody.add(tableActions, BorderLayout.SOUTH);
        tableCard.add(tableBody, BorderLayout.CENTER);

        body.add(headerRow);
        body.add(Box.createVerticalStrut(4));
        body.add(sub);
        body.add(Box.createVerticalStrut(4));
        body.add(statusLbl);
        body.add(Box.createVerticalStrut(16));
        body.add(formCard);
        body.add(Box.createVerticalStrut(16));
        body.add(tableCard);

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    public void loadGroups() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/groups"));
            }
            @Override protected void done() {
                try {
                    groupsModel.setRowCount(0);
                    for (JsonNode g : get()) {
                        if (!g.path("is_mine").asBoolean(true)) continue;
                        String created = g.path("created_at").asText("—");
                        groupsModel.addRow(new Object[]{
                            g.path("id").asInt(),
                            g.path("name").asText(),
                            g.path("description").asText("—"),
                            g.path("members_count").asInt(0),
                            created.length() >= 10 ? created.substring(0, 10) : created
                        });
                    }
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    private void createGroup() {
        String name = tfName.getText().trim();
        if (name.isEmpty()) { showStatus("Group name is required.", DANGER); return; }
        Map<String, Object> body = Map.of(
            "name",        name,
            "description", tfDesc.getText().trim()
        );
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.post("/lecturer/groups", body);
                return null;
            }
            @Override protected void done() {
                try {
                    get();
                    tfName.setText(""); tfDesc.setText("");
                    loadGroups();
                    showStatus("Group created.", GREEN);
                } catch (Exception e) {
                    showStatus("Failed to create group: " + e.getMessage(), DANGER);
                }
            }
        }.execute();
    }

    private void deleteGroup(int id) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.delete("/lecturer/groups/" + id);
                return null;
            }
            @Override protected void done() {
                try { get(); loadGroups(); showStatus("Group deleted.", GREEN); }
                catch (Exception e) { showStatus("Failed to delete: " + e.getMessage(), DANGER); }
            }
        }.execute();
    }

    private void showStatus(String msg, Color color) {
        statusLbl.setText(msg);
        statusLbl.setForeground(color);
    }

    private JPanel sectionCard(String title, Color accent) {
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
}
