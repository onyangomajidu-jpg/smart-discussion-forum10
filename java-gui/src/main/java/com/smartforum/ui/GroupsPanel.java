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

public class GroupsPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x4F, 0x46, 0xE5);
    private static final Color GREEN    = new Color(0x10, 0xB9, 0x81);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);
    private static final Color PURPLE   = new Color(0x5B, 0x21, 0xB6);

    private final ApiClient    api;
    private final AuthUser     user;
    private final ObjectMapper mapper = new ObjectMapper();

    private DefaultTableModel joinedModel;
    private DefaultTableModel availableModel;
    private JLabel            statusLbl;
    private JLabel            joinedCountLbl;
    private JLabel            availableCountLbl;

    public GroupsPanel(ApiClient api, AuthUser user) {
        this.api  = api;
        this.user = user;
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

        // Page header
        JLabel title = new JLabel("👥 Groups");
        title.setFont(new Font("Segoe UI", Font.BOLD, 22));
        title.setForeground(TEXT);
        title.setAlignmentX(LEFT_ALIGNMENT);

        JLabel subtitle = new JLabel("Join groups to access their topics and quizzes.");
        subtitle.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        subtitle.setForeground(MUTED);
        subtitle.setAlignmentX(LEFT_ALIGNMENT);

        statusLbl = new JLabel(" ");
        statusLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        statusLbl.setForeground(MUTED);
        statusLbl.setAlignmentX(LEFT_ALIGNMENT);

        JButton refreshBtn = new JButton("⟳ Refresh");
        styleBtn(refreshBtn, PRIMARY);
        refreshBtn.addActionListener(e -> loadGroups());

        JPanel headerRow = new JPanel(new BorderLayout());
        headerRow.setBackground(BG);
        headerRow.setAlignmentX(LEFT_ALIGNMENT);
        headerRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
        headerRow.add(title, BorderLayout.WEST);
        headerRow.add(refreshBtn, BorderLayout.EAST);

        body.add(headerRow);
        body.add(Box.createVerticalStrut(2));
        body.add(subtitle);
        body.add(Box.createVerticalStrut(4));
        body.add(statusLbl);
        body.add(Box.createVerticalStrut(20));

        // ── My Groups card ────────────────────────────────────────────────
        joinedCountLbl = new JLabel("0 joined");
        joinedCountLbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        joinedCountLbl.setForeground(MUTED);

        joinedModel = new DefaultTableModel(
            new String[]{"Group", "Description", "Members", ""}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable joinedTable = new JTable(joinedModel);
        styleTable(joinedTable);

        JPanel joinedCard = buildTableCard("👥 My Groups", PRIMARY, joinedCountLbl, joinedTable);
        joinedCard.setAlignmentX(LEFT_ALIGNMENT);
        joinedCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 260));

        body.add(joinedCard);
        body.add(Box.createVerticalStrut(20));

        // ── Available Groups card ─────────────────────────────────────────
        availableCountLbl = new JLabel("0 available");
        availableCountLbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        availableCountLbl.setForeground(MUTED);

        availableModel = new DefaultTableModel(
            new String[]{"Group", "Description", "Members", ""}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable availableTable = new JTable(availableModel);
        styleTable(availableTable);

        // Join button on row selection
        availableTable.getSelectionModel().addListSelectionListener(e -> {
            if (e.getValueIsAdjusting()) return;
            int row = availableTable.getSelectedRow();
            if (row < 0) return;
            String name = (String) availableModel.getValueAt(row, 0);
            int groupId = findGroupId(name, false);
            if (groupId < 0) return;
            int confirm = JOptionPane.showConfirmDialog(this,
                "Join \"" + name + "\"?", "Confirm", JOptionPane.YES_NO_OPTION);
            if (confirm != JOptionPane.YES_OPTION) { availableTable.clearSelection(); return; }
            joinOrLeave(groupId, true);
            availableTable.clearSelection();
        });

        // Leave button on joined row selection
        joinedTable.getSelectionModel().addListSelectionListener(e -> {
            if (e.getValueIsAdjusting()) return;
            int row = joinedTable.getSelectedRow();
            if (row < 0) return;
            String name = (String) joinedModel.getValueAt(row, 0);
            int groupId = findGroupId(name, true);
            if (groupId < 0) return;
            int confirm = JOptionPane.showConfirmDialog(this,
                "Leave \"" + name + "\"?", "Confirm", JOptionPane.YES_NO_OPTION);
            if (confirm != JOptionPane.YES_OPTION) { joinedTable.clearSelection(); return; }
            joinOrLeave(groupId, false);
            joinedTable.clearSelection();
        });

        JPanel availableCard = buildTableCard("🔍 Available Groups", GREEN, availableCountLbl, availableTable);
        availableCard.setAlignmentX(LEFT_ALIGNMENT);
        availableCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 260));

        body.add(availableCard);

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    private JPanel buildTableCard(String title, Color accent, JLabel countLbl, JTable table) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, accent),
            BorderFactory.createLineBorder(BORDER_C)));

        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(accent);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel(title);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        countLbl.setForeground(new Color(220, 220, 255));
        header.add(lbl,      BorderLayout.WEST);
        header.add(countLbl, BorderLayout.EAST);

        JLabel hint = new JLabel("  Click a row to join/leave");
        hint.setFont(new Font("Segoe UI", Font.ITALIC, 11));
        hint.setForeground(MUTED);
        hint.setBorder(new EmptyBorder(4, 14, 4, 14));

        card.add(header,              BorderLayout.NORTH);
        card.add(new JScrollPane(table), BorderLayout.CENTER);
        card.add(hint,                BorderLayout.SOUTH);
        return card;
    }

    // ── Data loading ──────────────────────────────────────────────────────

    // cache for id lookup
    private JsonNode lastGroups = null;

    public void loadGroups() {
        statusLbl.setText("Loading…");
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/groups"));
            }
            @Override protected void done() {
                try {
                    lastGroups = get();
                    joinedModel.setRowCount(0);
                    availableModel.setRowCount(0);
                    int joinedCount = 0, availCount = 0;

                    for (JsonNode g : lastGroups) {
                        String name  = g.path("name").asText();
                        String desc  = g.path("description").asText("—");
                        int members  = g.path("members_count").asInt(0);
                        boolean mine = g.path("is_member").asBoolean(false);

                        if (mine) {
                            joinedModel.addRow(new Object[]{name, desc, members, "← Leave"});
                            joinedCount++;
                        } else {
                            availableModel.addRow(new Object[]{name, desc, members, "Join →"});
                            availCount++;
                        }
                    }

                    joinedCountLbl.setText(joinedCount + " joined");
                    availableCountLbl.setText(availCount + " available");
                    statusLbl.setText(" ");
                } catch (Exception e) {
                    statusLbl.setText("Could not load groups: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
    }

    private int findGroupId(String name, boolean joined) {
        if (lastGroups == null) return -1;
        for (JsonNode g : lastGroups) {
            boolean mine = g.path("is_member").asBoolean(false);
            if (mine == joined && g.path("name").asText().equals(name))
                return g.path("id").asInt(-1);
        }
        return -1;
    }

    private void joinOrLeave(int groupId, boolean join) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                if (join) api.post("/groups/" + groupId + "/join",   Map.of());
                else      api.delete("/groups/" + groupId + "/leave");
                return null;
            }
            @Override protected void done() {
                try { get(); loadGroups(); }
                catch (Exception e) {
                    statusLbl.setText("Action failed: " + e.getMessage());
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
    }

    private void styleTable(JTable table) {
        table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        table.setRowHeight(30);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
        table.setGridColor(BORDER_C);
        table.setSelectionBackground(new Color(0xE0, 0xE7, 0xFF));
        table.setSelectionForeground(TEXT);
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
