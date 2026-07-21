package com.smartforum.ui;

import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;
import com.smartforum.model.Topic;
import com.smartforum.sync.OfflineSyncManager;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.*;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;
import java.util.function.Consumer;

/**
 * Left panel — search bar, create topic button, scrollable topic list with delete.
 */
public class TopicListPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x66, 0x7E, 0xEA);
    private static final Color SECONDARY= new Color(0x76, 0x4B, 0xA2);
    private static final Color BG       = new Color(0xF8, 0xF9, 0xFA);
    private static final Color SEL_BG   = new Color(0xE8, 0xEC, 0xFD);
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);

    private final LocalCacheDatabase      cache;
    private final AuthUser                user;
    private final OfflineSyncManager      syncManager;
    private final Consumer<Topic>         onSelect;
    private final DefaultListModel<Topic> model    = new DefaultListModel<>();
    private final JList<Topic>            list     = new JList<>(model);
    private final JTextField              searchField = new JTextField();
    private String                        searchQuery = "";

    public TopicListPanel(LocalCacheDatabase cache, AuthUser user,
                          OfflineSyncManager syncManager, Consumer<Topic> onSelect) {
        this.cache       = cache;
        this.user        = user;
        this.syncManager = syncManager;
        this.onSelect    = onSelect;
        setLayout(new BorderLayout());
        setPreferredSize(new Dimension(280, 0));
        setBackground(BG);
        buildUI();
        refresh();
    }

    private void buildUI() {
        // ── Header ────────────────────────────────────────────────────────
        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(PRIMARY);
        header.setBorder(new EmptyBorder(10, 12, 10, 12));
        JLabel title = new JLabel("💬 Topics");
        title.setFont(new Font("Segoe UI", Font.BOLD, 15));
        title.setForeground(Color.WHITE);
        header.add(title, BorderLayout.WEST);

        // ── Search + Create bar ───────────────────────────────────────────
        JPanel topBar = new JPanel(new BorderLayout(6, 6));
        topBar.setBackground(Color.WHITE);
        topBar.setBorder(new EmptyBorder(10, 10, 10, 10));

        searchField.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        searchField.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 1),
            new EmptyBorder(6, 10, 6, 10)));
        searchField.putClientProperty("JTextField.placeholderText", "🔍 Search topics...");
        searchField.addKeyListener(new KeyAdapter() {
            @Override public void keyReleased(KeyEvent e) {
                searchQuery = searchField.getText().trim().toLowerCase();
                refresh();
            }
        });

        JButton createBtn = new JButton("+ Create Topic");
        createBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        createBtn.setForeground(Color.WHITE);
        createBtn.setBackground(PRIMARY);
        createBtn.setBorderPainted(false);
        createBtn.setFocusPainted(false);
        createBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        createBtn.addActionListener(e -> showCreateTopicDialog());

        topBar.add(searchField, BorderLayout.CENTER);
        topBar.add(createBtn,   BorderLayout.SOUTH);

        // ── Topic list ────────────────────────────────────────────────────
        list.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        list.setBackground(BG);
        list.setSelectionBackground(SEL_BG);
        list.setSelectionForeground(PRIMARY);
        list.setFixedCellHeight(56);
        list.setCellRenderer(new TopicCellRenderer());
        list.addListSelectionListener(e -> {
            if (!e.getValueIsAdjusting() && list.getSelectedValue() != null)
                onSelect.accept(list.getSelectedValue());
        });

        // Right-click context menu for delete
        JPopupMenu popup = new JPopupMenu();
        JMenuItem deleteItem = new JMenuItem("🗑 Delete Topic");
        deleteItem.setForeground(new Color(0xE5, 0x3E, 0x3E));
        deleteItem.addActionListener(e -> {
            Topic selected = list.getSelectedValue();
            if (selected == null) return;
            if (selected.userId != user.getUserId() && !user.getRole().equals("admin")) {
                JOptionPane.showMessageDialog(this, "You can only delete your own topics.",
                    "Permission Denied", JOptionPane.ERROR_MESSAGE);
                return;
            }
            int confirm = JOptionPane.showConfirmDialog(this,
                "Delete topic: \"" + selected.title + "\"?", "Confirm Delete",
                JOptionPane.YES_NO_OPTION, JOptionPane.WARNING_MESSAGE);
            if (confirm == JOptionPane.YES_OPTION) deleteTopic(selected);
        });
        popup.add(deleteItem);
        list.setComponentPopupMenu(popup);
        list.addMouseListener(new MouseAdapter() {
            @Override public void mousePressed(MouseEvent e) {
                int idx = list.locationToIndex(e.getPoint());
                if (idx >= 0) list.setSelectedIndex(idx);
            }
        });

        JScrollPane scroll = new JScrollPane(list);
        scroll.setBorder(null);

        JPanel north = new JPanel(new BorderLayout());
        north.add(header, BorderLayout.NORTH);
        north.add(topBar, BorderLayout.CENTER);

        add(north,  BorderLayout.NORTH);
        add(scroll, BorderLayout.CENTER);
    }

    // ── Create topic dialog ───────────────────────────────────────────────

    private void showCreateTopicDialog() {
        JDialog dialog = new JDialog((Frame) SwingUtilities.getWindowAncestor(this),
            "Create New Topic", true);
        dialog.setSize(440, 320);
        dialog.setLocationRelativeTo(this);

        JPanel panel = new JPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBorder(new EmptyBorder(20, 24, 20, 24));
        panel.setBackground(Color.WHITE);

        JLabel titleLbl = new JLabel("Title");
        titleLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        JTextField titleField = new JTextField();
        titleField.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        titleField.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));
        titleField.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(new Color(0xE2, 0xE8, 0xF0), 1),
            new EmptyBorder(6, 10, 6, 10)));

        JLabel bodyLbl = new JLabel("Body");
        bodyLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        JTextArea bodyArea = new JTextArea(4, 30);
        bodyArea.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        bodyArea.setLineWrap(true);
        bodyArea.setWrapStyleWord(true);
        bodyArea.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(new Color(0xE2, 0xE8, 0xF0), 1),
            new EmptyBorder(6, 10, 6, 10)));

        JCheckBox syndicateBox = new JCheckBox("Syndicate to other groups");
        syndicateBox.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        syndicateBox.setBackground(Color.WHITE);

        JButton submitBtn = new JButton("Create Topic");
        submitBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        submitBtn.setForeground(Color.WHITE);
        submitBtn.setBackground(PRIMARY);
        submitBtn.setBorderPainted(false);
        submitBtn.setFocusPainted(false);
        submitBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        submitBtn.setAlignmentX(RIGHT_ALIGNMENT);
        submitBtn.addActionListener(e -> {
            String t = titleField.getText().trim();
            String b = bodyArea.getText().trim();
            if (t.isEmpty() || b.isEmpty()) {
                JOptionPane.showMessageDialog(dialog, "Title and body are required.");
                return;
            }
            if (isSpam(b)) {
                JOptionPane.showMessageDialog(dialog,
                    "Content flagged as spam.", "Spam Detected", JOptionPane.WARNING_MESSAGE);
                return;
            }
            new SwingWorker<Void, Void>() {
                @Override protected Void doInBackground() throws Exception {
                    syncManager.createTopic(t, b, user.getUserId());
                    return null;
                }
                @Override protected void done() {
                    dialog.dispose();
                    refresh();
                }
            }.execute();
        });

        panel.add(titleLbl);
        panel.add(Box.createVerticalStrut(4));
        panel.add(titleField);
        panel.add(Box.createVerticalStrut(12));
        panel.add(bodyLbl);
        panel.add(Box.createVerticalStrut(4));
        panel.add(new JScrollPane(bodyArea));
        panel.add(Box.createVerticalStrut(10));
        panel.add(syndicateBox);
        panel.add(Box.createVerticalStrut(14));
        panel.add(submitBtn);

        dialog.setContentPane(panel);
        dialog.setVisible(true);
    }

    // ── Delete topic ──────────────────────────────────────────────────────

    private void deleteTopic(Topic topic) {
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                syncManager.deleteTopic(topic.id);
                return null;
            }
            @Override protected void done() { refresh(); }
        }.execute();
    }

    // ── Spam filter (mirrors ContentManagementService) ────────────────────

    private boolean isSpam(String content) {
        String lower = content.toLowerCase();
        String[] keywords = {"buy now","click here","free money","make money fast",
                             "casino","viagra","crypto giveaway","earn $","limited offer"};
        for (String kw : keywords) if (lower.contains(kw)) return true;
        int urlCount = 0;
        int idx = 0;
        while ((idx = lower.indexOf("http", idx)) != -1) { urlCount++; idx += 4; }
        return urlCount > 3;
    }

    // ── Refresh ───────────────────────────────────────────────────────────

    public void refresh() {
        List<Topic> topics = loadTopics();
        SwingUtilities.invokeLater(() -> {
            model.clear();
            topics.stream()
                .filter(t -> searchQuery.isEmpty() ||
                             t.title.toLowerCase().contains(searchQuery))
                .forEach(model::addElement);
        });
    }

    private List<Topic> loadTopics() {
        List<Topic> result = new ArrayList<>();
        // Load user's group IDs
        java.util.Set<Integer> userGroupIds = new java.util.HashSet<>();
        try (Connection conn = cache.connect();
             ResultSet rs = conn.createStatement().executeQuery("SELECT group_id FROM user_groups")) {
            while (rs.next()) userGroupIds.add(rs.getInt("group_id"));
        } catch (SQLException ignored) {}

        String sql = "SELECT id, group_id, user_id, title, body, author_name, is_pinned, is_locked " +
                     "FROM cached_topics ORDER BY is_pinned DESC, id DESC";
        try (Connection conn = cache.connect();
             Statement  st   = conn.createStatement();
             ResultSet  rs   = st.executeQuery(sql)) {
            while (rs.next()) {
                int groupId   = rs.getInt("group_id");
                boolean pinned = rs.getInt("is_pinned") == 1 && userGroupIds.contains(groupId);
                result.add(new Topic(
                    rs.getInt("id"), groupId, rs.getInt("user_id"),
                    rs.getString("title"), rs.getString("body"),
                    rs.getString("author_name"),
                    pinned, rs.getInt("is_locked") == 1));
            }
        } catch (SQLException e) {
            System.err.println("[TopicListPanel] load failed: " + e.getMessage());
        }
        return result;
    }

    // ── Cell renderer ─────────────────────────────────────────────────────

    private static class TopicCellRenderer extends DefaultListCellRenderer {
        @Override
        public Component getListCellRendererComponent(
                JList<?> list, Object value, int index,
                boolean isSelected, boolean cellHasFocus) {
            JLabel lbl = (JLabel) super.getListCellRendererComponent(
                    list, value, index, isSelected, cellHasFocus);
            if (value instanceof Topic t) {
                lbl.setText("<html><b>" + (t.isPinned ? "📌 " : "") + esc(t.toString()) + "</b>" +
                    "<br><font color='#6c757d' size='-2'>by " + esc(t.authorName) +
                    "</font></html>");
            }
            lbl.setBorder(new EmptyBorder(8, 12, 8, 12));
            return lbl;
        }
        private String esc(String s) {
            return s.replace("&","&amp;").replace("<","&lt;").replace(">","&gt;");
        }
    }
}
