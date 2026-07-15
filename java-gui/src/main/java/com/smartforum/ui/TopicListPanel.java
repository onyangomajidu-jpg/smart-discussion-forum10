package com.smartforum.ui;

import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.Topic;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.sql.*;
import java.util.ArrayList;
import java.util.List;
import java.util.function.Consumer;

/**
 * Left panel — scrollable list of cached topics.
 * Calls {@code onSelect} whenever the user clicks a topic.
 */
public class TopicListPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x66, 0x7E, 0xEA);
    private static final Color BG       = new Color(0xF8, 0xF9, 0xFA);
    private static final Color SEL_BG   = new Color(0xE8, 0xEC, 0xFD);

    private final LocalCacheDatabase   cache;
    private final Consumer<Topic>      onSelect;
    private final DefaultListModel<Topic> model = new DefaultListModel<>();
    private final JList<Topic>         list  = new JList<>(model);

    public TopicListPanel(LocalCacheDatabase cache, Consumer<Topic> onSelect) {
        this.cache    = cache;
        this.onSelect = onSelect;
        setLayout(new BorderLayout());
        setPreferredSize(new Dimension(260, 0));
        setBackground(BG);

        // ── Header ────────────────────────────────────────────────────────
        JLabel header = new JLabel("  💬 Topics");
        header.setFont(new Font("Segoe UI", Font.BOLD, 15));
        header.setForeground(Color.WHITE);
        header.setOpaque(true);
        header.setBackground(PRIMARY);
        header.setBorder(new EmptyBorder(10, 12, 10, 12));

        // ── List ──────────────────────────────────────────────────────────
        list.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        list.setBackground(BG);
        list.setSelectionBackground(SEL_BG);
        list.setSelectionForeground(PRIMARY);
        list.setFixedCellHeight(44);
        list.setCellRenderer(new TopicCellRenderer());
        list.addListSelectionListener(e -> {
            if (!e.getValueIsAdjusting() && list.getSelectedValue() != null)
                onSelect.accept(list.getSelectedValue());
        });

        JScrollPane scroll = new JScrollPane(list);
        scroll.setBorder(null);

        add(header, BorderLayout.NORTH);
        add(scroll,  BorderLayout.CENTER);

        refresh();
    }

    /** Reloads topics from SQLite and repaints the list. */
    public void refresh() {
        List<Topic> topics = loadTopics();
        SwingUtilities.invokeLater(() -> {
            model.clear();
            topics.forEach(model::addElement);
        });
    }

    private List<Topic> loadTopics() {
        List<Topic> result = new ArrayList<>();
        String sql = "SELECT id, group_id, title, body, author_name, is_pinned, is_locked " +
                     "FROM cached_topics ORDER BY is_pinned DESC, id DESC";
        try (Connection conn = cache.connect();
             Statement  st   = conn.createStatement();
             ResultSet  rs   = st.executeQuery(sql)) {
            while (rs.next()) {
                result.add(new Topic(
                    rs.getInt("id"),
                    rs.getInt("group_id"),
                    rs.getString("title"),
                    rs.getString("body"),
                    rs.getString("author_name"),
                    rs.getInt("is_pinned") == 1,
                    rs.getInt("is_locked") == 1
                ));
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
                lbl.setText("<html><b>" + escHtml(t.toString()) + "</b>"
                    + "<br><font color='#6c757d' size='-2'>by " + escHtml(t.authorName) + "</font></html>");
            }
            lbl.setBorder(new EmptyBorder(6, 12, 6, 12));
            return lbl;
        }

        private String escHtml(String s) {
            return s.replace("&","&amp;").replace("<","&lt;").replace(">","&gt;");
        }
    }
}
