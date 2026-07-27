package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.event.DocumentEvent;
import javax.swing.event.DocumentListener;
import javax.swing.table.DefaultTableModel;
import java.awt.*;
import java.util.ArrayList;
import java.util.List;

/**
 * Administrator → User Management (mirrors the "Recent Users" / user roster
 * data exposed by Laravel's ModerationController@apiUsers — GET /admin/users).
 * Read-only roster with live search + role filter, matching the Laravel
 * admin dashboard's user table (id, name, email, role).
 */
public class UserManagementPanel extends JPanel {

    // Values now come from Theme (single source of truth shared with every
    // other panel) instead of being re-declared per-file. CYAN is a distinct
    // accent (not part of Laravel's root palette), so it stays local.
    private static final Color PRIMARY  = Theme.PRIMARY_DARK;
    private static final Color PURPLE   = Theme.SECONDARY;
    private static final Color CYAN     = new Color(0x06, 0xB6, 0xD4);
    private static final Color DANGER   = Theme.DANGER;
    private static final Color BG       = Theme.BG;
    private static final Color SURFACE  = Theme.SURFACE;
    private static final Color MUTED    = Theme.MUTED;
    private static final Color TEXT     = Theme.TEXT;
    private static final Color BORDER_C = Theme.BORDER;

    private final ApiClient    api;
    private final ObjectMapper mapper = new ObjectMapper();

    private JTextField        searchField;
    private JComboBox<String> roleFilter;
    private DefaultTableModel tableModel;
    private JLabel            statusLbl;
    private JLabel            countLbl;

    private List<JsonNode> allUsers = new ArrayList<>();

    public UserManagementPanel(ApiClient api, AuthUser user) {
        this.api = api;
        if (!user.isAdmin()) {
            setLayout(new BorderLayout());
            add(new JLabel("Access denied.", SwingConstants.CENTER), BorderLayout.CENTER);
            return;
        }
        setBackground(BG);
        setLayout(new BorderLayout());
        buildUI();
        loadUsers();
    }

    private void buildUI() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(24, 24, 40, 24));

        JLabel breadcrumb = new JLabel("Admin / User Management");
        breadcrumb.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        breadcrumb.setForeground(MUTED);
        breadcrumb.setAlignmentX(LEFT_ALIGNMENT);

        JLabel pageTitle = new JLabel("👥  User Management");
        pageTitle.setFont(new Font("Segoe UI", Font.BOLD, 20));
        pageTitle.setForeground(TEXT);
        pageTitle.setAlignmentX(LEFT_ALIGNMENT);

        statusLbl = new JLabel(" ");
        statusLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        statusLbl.setForeground(MUTED);
        statusLbl.setAlignmentX(LEFT_ALIGNMENT);

        JButton refreshBtn = new JButton("⟳ Refresh");
        styleBtn(refreshBtn, PRIMARY);
        refreshBtn.addActionListener(e -> loadUsers());

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
        body.add(buildFilterCard());
        body.add(Box.createVerticalStrut(16));
        body.add(buildTableCard());

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    private JPanel buildFilterCard() {
        JPanel filterCard = card("🔍 Search & Filter", PRIMARY);
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(14, 14, 14, 14));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        searchField = new JTextField();
        searchField.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        searchField.putClientProperty("JTextField.placeholderText", "🔍 Search by name or email...");
        searchField.getDocument().addDocumentListener(new DocumentListener() {
            @Override public void insertUpdate(DocumentEvent e)  { applyFilter(); }
            @Override public void removeUpdate(DocumentEvent e)  { applyFilter(); }
            @Override public void changedUpdate(DocumentEvent e) { applyFilter(); }
        });

        roleFilter = new JComboBox<>(new String[]{"All Roles", "member", "lecturer", "admin"});
        roleFilter.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        roleFilter.addActionListener(e -> applyFilter());

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(fieldLabel("Search"), gc);
        gc.gridx = 1; gc.weightx = 1;                form.add(searchField, gc);
        gc.gridx = 2; gc.gridy = 0; gc.weightx = 0; form.add(fieldLabel("Role"), gc);
        gc.gridx = 3; gc.weightx = 0.4;              form.add(roleFilter, gc);

        filterCard.add(form, BorderLayout.CENTER);
        filterCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 90));
        return filterCard;
    }

    private JPanel buildTableCard() {
        JPanel tableCard = card("📋 All Users", PURPLE);

        countLbl = new JLabel(" ");
        countLbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        countLbl.setForeground(Color.WHITE);

        tableModel = new DefaultTableModel(
            new String[]{"#", "Name", "Email", "Role"}, 0) {
            @Override public boolean isCellEditable(int r, int c) { return false; }
        };
        JTable table = new JTable(tableModel);
        table.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        table.setRowHeight(30);
        table.getTableHeader().setFont(new Font("Segoe UI", Font.BOLD, 12));
        table.setGridColor(BORDER_C);
        table.getColumnModel().getColumn(0).setMaxWidth(50);
        table.getColumnModel().getColumn(3).setCellRenderer(new javax.swing.table.DefaultTableCellRenderer() {
            @Override public Component getTableCellRendererComponent(
                    JTable tbl, Object value, boolean sel, boolean focus, int row, int col) {
                JLabel lbl = (JLabel) super.getTableCellRendererComponent(tbl, value, sel, focus, row, col);
                String role = String.valueOf(value);
                Color c = role.equals("admin") ? DANGER : role.equals("lecturer") ? CYAN : PURPLE;
                String icon = role.equals("admin") ? "🛡 " : role.equals("lecturer") ? "🎫 " : "🎓 ";
                lbl.setText(icon + role);
                if (!sel) lbl.setForeground(c);
                lbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
                return lbl;
            }
        });

        JPanel tableBody = new JPanel(new BorderLayout());
        tableBody.setBackground(SURFACE);
        tableBody.add(new JScrollPane(table), BorderLayout.CENTER);

        tableCard.add(tableBody, BorderLayout.CENTER);
        tableCard.setMaximumSize(new Dimension(Integer.MAX_VALUE, 520));

        // Slot the row-count label into the card header (east side)
        Component headerComp = tableCard.getComponent(0);
        if (headerComp instanceof JPanel) {
            ((JPanel) headerComp).add(countLbl, BorderLayout.EAST);
        }
        return tableCard;
    }

    // ── Data loading ─────────────────────────────────────────────────────

    public void loadUsers() {
        statusLbl.setText("Loading…");
        new SwingWorker<List<JsonNode>, Void>() {
            @Override protected List<JsonNode> doInBackground() throws Exception {
                List<JsonNode> list = new ArrayList<>();
                mapper.readTree(api.get("/admin/users")).forEach(list::add);
                return list;
            }
            @Override protected void done() {
                try {
                    allUsers = get();
                    applyFilter();
                    statusLbl.setText("Last refreshed: " + java.time.LocalTime.now().withNano(0));
                    statusLbl.setForeground(MUTED);
                } catch (Exception e) {
                    statusLbl.setText("Failed to load users.");
                    statusLbl.setForeground(DANGER);
                }
            }
        }.execute();
    }

    private void applyFilter() {
        String q = searchField == null ? "" : searchField.getText().trim().toLowerCase();
        String role = roleFilter == null ? "All Roles" : (String) roleFilter.getSelectedItem();

        tableModel.setRowCount(0);
        for (JsonNode u : allUsers) {
            String name  = u.path("name").asText();
            String email = u.path("email").asText();
            String r     = u.path("role").asText();

            boolean matchesQuery = q.isEmpty()
                || name.toLowerCase().contains(q)
                || email.toLowerCase().contains(q);
            boolean matchesRole = role == null || role.equals("All Roles") || role.equals(r);

            if (matchesQuery && matchesRole) {
                tableModel.addRow(new Object[]{ u.path("id").asInt(), name, email, r });
            }
        }
        if (countLbl != null) {
            countLbl.setText(tableModel.getRowCount() + " of " + allUsers.size() + " users");
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────

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
}
