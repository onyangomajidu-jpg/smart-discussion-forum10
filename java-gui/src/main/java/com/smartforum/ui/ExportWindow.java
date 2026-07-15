package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.filechooser.FileNameExtensionFilter;
import java.awt.*;
import java.awt.Desktop;
import java.io.*;
import java.nio.file.*;
import java.util.Map;

/**
 * ExportWindow — PDF export & social media sharing panel.
 *
 * Features:
 *  • Browse & select a topic from the API
 *  • Export to PDF via GET /api/topics/{id}/export-pdf  (saves locally + opens)
 *  • Share a post to Twitter / LinkedIn / Facebook via POST /api/posts/{id}/share
 */
public class ExportWindow extends JFrame {

    // ── Brand colours (mirror MainWindow) ────────────────────────────────
    private static final Color PRIMARY   = new Color(0x63, 0x66, 0xF1);
    private static final Color SECONDARY = new Color(0x8B, 0x5C, 0xF6);
    private static final Color SUCCESS   = new Color(0x10, 0xB9, 0x81);
    private static final Color WARNING   = new Color(0xF5, 0x9E, 0x0B);
    private static final Color DANGER    = new Color(0xEF, 0x44, 0x44);
    private static final Color BG        = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE   = Color.WHITE;
    private static final Color BORDER_C  = new Color(0xE2, 0xE8, 0xF0);
    private static final Color TEXT      = new Color(0x0F, 0x17, 0x2A);
    private static final Color MUTED     = new Color(0x64, 0x74, 0x8B);

    private final ApiClient    api;
    private final ObjectMapper mapper = new ObjectMapper();

    // ── PDF section state ─────────────────────────────────────────────────
    private final DefaultListModel<TopicItem> topicModel = new DefaultListModel<>();
    private final JList<TopicItem>            topicList  = new JList<>(topicModel);
    private JLabel  pdfStatusLabel;
    private JButton btnExportPdf;

    // ── Share section state ───────────────────────────────────────────────
    private JTextField  postIdField;
    private JComboBox<String> platformCombo;
    private JLabel      shareStatusLabel;
    private JButton     btnShare;

    public ExportWindow(ApiClient api) {
        this.api = api;

        setTitle("SmartForum — Export & Share");
        setDefaultCloseOperation(DISPOSE_ON_CLOSE);
        setSize(780, 600);
        setMinimumSize(new Dimension(680, 520));
        setLocationRelativeTo(null);
        setResizable(true);

        JPanel root = new JPanel(new BorderLayout());
        root.setBackground(BG);
        root.add(buildHeader(),  BorderLayout.NORTH);
        root.add(buildBody(),    BorderLayout.CENTER);
        setContentPane(root);

        loadTopics();
    }

    // ── Header ────────────────────────────────────────────────────────────

    private JPanel buildHeader() {
        JPanel bar = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), 0, SECONDARY));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
            }
        };
        bar.setPreferredSize(new Dimension(0, 60));
        bar.setBorder(new EmptyBorder(0, 24, 0, 24));
        bar.setOpaque(false);

        JLabel title = new JLabel("📄  Export & Share");
        title.setFont(new Font("Segoe UI", Font.BOLD, 17));
        title.setForeground(Color.WHITE);

        JLabel sub = new JLabel("Export discussions to PDF · Share posts on social media");
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        sub.setForeground(new Color(255, 255, 255, 180));

        JPanel left = new JPanel();
        left.setLayout(new BoxLayout(left, BoxLayout.Y_AXIS));
        left.setOpaque(false);
        left.add(Box.createVerticalGlue());
        left.add(title);
        left.add(sub);
        left.add(Box.createVerticalGlue());

        bar.add(left, BorderLayout.WEST);
        return bar;
    }

    // ── Body: two side-by-side cards ──────────────────────────────────────

    private JPanel buildBody() {
        JPanel body = new JPanel(new GridLayout(1, 2, 18, 0));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(22, 22, 22, 22));
        body.add(buildPdfCard());
        body.add(buildShareCard());
        return body;
    }

    // ── PDF Export Card ───────────────────────────────────────────────────

    private JPanel buildPdfCard() {
        JPanel card = card("📄  Export Discussion to PDF", PRIMARY);

        // Topic list
        topicList.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        topicList.setForeground(TEXT);
        topicList.setBackground(SURFACE);
        topicList.setSelectionBackground(new Color(0xED, 0xE9, 0xFE));
        topicList.setSelectionForeground(PRIMARY);
        topicList.setFixedCellHeight(34);
        topicList.setCellRenderer(new TopicCellRenderer());

        JScrollPane scroll = new JScrollPane(topicList);
        scroll.setBorder(BorderFactory.createLineBorder(BORDER_C));
        scroll.setPreferredSize(new Dimension(0, 260));

        JLabel hint = new JLabel("Select a topic then click Export PDF");
        hint.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        hint.setForeground(MUTED);

        pdfStatusLabel = statusLabel();

        btnExportPdf = actionButton("⬇  Export PDF", PRIMARY);
        btnExportPdf.addActionListener(e -> doExportPdf());

        JButton btnRefresh = ghostButton("↻  Refresh");
        btnRefresh.addActionListener(e -> loadTopics());

        JPanel btnRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 0));
        btnRow.setOpaque(false);
        btnRow.add(btnExportPdf);
        btnRow.add(btnRefresh);

        JPanel content = (JPanel) card.getComponent(1);
        content.add(hint);
        content.add(Box.createVerticalStrut(8));
        content.add(scroll);
        content.add(Box.createVerticalStrut(12));
        content.add(btnRow);
        content.add(Box.createVerticalStrut(8));
        content.add(pdfStatusLabel);

        return card;
    }

    // ── Social Share Card ─────────────────────────────────────────────────

    private JPanel buildShareCard() {
        JPanel card = card("🌐  Share Post to Social Media", SECONDARY);

        JLabel postIdLbl = fieldLabel("Post ID");
        postIdField = new JTextField();
        styleField(postIdField);

        JLabel platformLbl = fieldLabel("Platform");
        platformCombo = new JComboBox<>(new String[]{"twitter", "linkedin", "facebook"});
        platformCombo.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        platformCombo.setBackground(SURFACE);
        platformCombo.setForeground(TEXT);
        platformCombo.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));

        shareStatusLabel = statusLabel();

        btnShare = actionButton("🚀  Share Now", SECONDARY);
        btnShare.addActionListener(e -> doShare());

        // Platform badges
        JPanel badges = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        badges.setOpaque(false);
        badges.add(badge("𝕏 Twitter",   new Color(0x1D, 0xA1, 0xF2)));
        badges.add(badge("in LinkedIn", new Color(0x00, 0x77, 0xB5)));
        badges.add(badge("f Facebook",  new Color(0x18, 0x77, 0xF2)));

        JPanel content = (JPanel) card.getComponent(1);
        content.add(postIdLbl);
        content.add(Box.createVerticalStrut(4));
        content.add(postIdField);
        content.add(Box.createVerticalStrut(14));
        content.add(platformLbl);
        content.add(Box.createVerticalStrut(4));
        content.add(platformCombo);
        content.add(Box.createVerticalStrut(14));
        content.add(badges);
        content.add(Box.createVerticalStrut(16));
        content.add(btnShare);
        content.add(Box.createVerticalStrut(8));
        content.add(shareStatusLabel);

        return card;
    }

    // ── Actions ───────────────────────────────────────────────────────────

    private void doExportPdf() {
        TopicItem selected = topicList.getSelectedValue();
        if (selected == null) {
            setStatus(pdfStatusLabel, "⚠  Please select a topic first.", WARNING);
            return;
        }

        JFileChooser chooser = new JFileChooser();
        chooser.setDialogTitle("Save PDF");
        chooser.setSelectedFile(new File("discussion-" + selected.id + ".pdf"));
        chooser.setFileFilter(new FileNameExtensionFilter("PDF Files (*.pdf)", "pdf"));
        if (chooser.showSaveDialog(this) != JFileChooser.APPROVE_OPTION) return;

        File dest = chooser.getSelectedFile();
        if (!dest.getName().endsWith(".pdf")) dest = new File(dest.getAbsolutePath() + ".pdf");
        final File finalDest = dest;

        btnExportPdf.setEnabled(false);
        setStatus(pdfStatusLabel, "⏳  Generating PDF…", MUTED);

        new SwingWorker<byte[], Void>() {
            @Override protected byte[] doInBackground() throws Exception {
                return api.getBytes("/topics/" + selected.id + "/export-pdf");
            }
            @Override protected void done() {
                btnExportPdf.setEnabled(true);
                try {
                    byte[] bytes = get();
                    Files.write(finalDest.toPath(), bytes);
                    setStatus(pdfStatusLabel,
                        "✅  Saved: " + finalDest.getName() + "  (" + (bytes.length / 1024) + " KB)", SUCCESS);
                    if (Desktop.isDesktopSupported())
                        Desktop.getDesktop().open(finalDest);
                } catch (Exception ex) {
                    setStatus(pdfStatusLabel, "❌  " + ex.getMessage(), DANGER);
                }
            }
        }.execute();
    }

    private void doShare() {
        String postIdText = postIdField.getText().trim();
        if (postIdText.isEmpty()) {
            setStatus(shareStatusLabel, "⚠  Enter a Post ID.", WARNING);
            return;
        }
        int postId;
        try { postId = Integer.parseInt(postIdText); }
        catch (NumberFormatException e) {
            setStatus(shareStatusLabel, "⚠  Post ID must be a number.", WARNING);
            return;
        }

        String platform = (String) platformCombo.getSelectedItem();
        btnShare.setEnabled(false);
        setStatus(shareStatusLabel, "⏳  Sharing to " + platform + "…", MUTED);

        final int fPostId = postId;
        new SwingWorker<String, Void>() {
            @Override protected String doInBackground() throws Exception {
                String json = api.post("/posts/" + fPostId + "/share",
                    Map.of("platform", platform));
                JsonNode node = mapper.readTree(json);
                return node.path("message").asText("Shared successfully.");
            }
            @Override protected void done() {
                btnShare.setEnabled(true);
                try {
                    setStatus(shareStatusLabel, "✅  " + get(), SUCCESS);
                } catch (Exception ex) {
                    setStatus(shareStatusLabel, "❌  " + ex.getMessage(), DANGER);
                }
            }
        }.execute();
    }

    private void loadTopics() {
        topicModel.clear();
        setStatus(pdfStatusLabel, "⏳  Loading topics…", MUTED);
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/topics"));
            }
            @Override protected void done() {
                try {
                    JsonNode data = get();
                    JsonNode topics = data.isArray() ? data : data.path("data");
                    topicModel.clear();
                    for (JsonNode t : topics)
                        topicModel.addElement(new TopicItem(
                            t.path("id").asInt(),
                            t.path("title").asText("Untitled"),
                            t.path("posts_count").asInt(0)
                        ));
                    setStatus(pdfStatusLabel,
                        topicModel.isEmpty() ? "No topics found." : "", MUTED);
                } catch (Exception e) {
                    setStatus(pdfStatusLabel, "❌  Could not load topics: " + e.getMessage(), DANGER);
                }
            }
        }.execute();
    }

    // ── UI helpers ────────────────────────────────────────────────────────

    /** Returns a card panel with a coloured left-border header and a BoxLayout content area. */
    private JPanel card(String title, Color accent) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createLineBorder(BORDER_C));

        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(new Color(0xFA, 0xFB, 0xFF));
        header.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 4, 1, 0, accent),
            new EmptyBorder(12, 16, 12, 16)
        ));
        JLabel lbl = new JLabel(title);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 14));
        lbl.setForeground(TEXT);
        header.add(lbl, BorderLayout.WEST);

        JPanel content = new JPanel();
        content.setLayout(new BoxLayout(content, BoxLayout.Y_AXIS));
        content.setBackground(SURFACE);
        content.setBorder(new EmptyBorder(16, 18, 16, 18));

        card.add(header,  BorderLayout.NORTH);
        card.add(content, BorderLayout.CENTER);
        return card;
    }

    private JButton actionButton(String text, Color bg) {
        JButton btn = new JButton(text);
        btn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        btn.setForeground(Color.WHITE);
        btn.setBackground(bg);
        btn.setBorderPainted(false);
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        btn.setAlignmentX(LEFT_ALIGNMENT);
        return btn;
    }

    private JButton ghostButton(String text) {
        JButton btn = new JButton(text);
        btn.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        btn.setForeground(MUTED);
        btn.setBackground(BG);
        btn.setBorder(BorderFactory.createLineBorder(BORDER_C));
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return btn;
    }

    private JLabel fieldLabel(String text) {
        JLabel lbl = new JLabel(text);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 12));
        lbl.setForeground(MUTED);
        lbl.setAlignmentX(LEFT_ALIGNMENT);
        return lbl;
    }

    private void styleField(JTextField f) {
        f.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        f.setForeground(TEXT);
        f.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C),
            new EmptyBorder(6, 10, 6, 10)
        ));
        f.setMaximumSize(new Dimension(Integer.MAX_VALUE, 36));
    }

    private JLabel statusLabel() {
        JLabel lbl = new JLabel(" ");
        lbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        lbl.setForeground(MUTED);
        lbl.setAlignmentX(LEFT_ALIGNMENT);
        return lbl;
    }

    private void setStatus(JLabel lbl, String msg, Color color) {
        SwingUtilities.invokeLater(() -> { lbl.setText(msg); lbl.setForeground(color); });
    }

    private JLabel badge(String text, Color bg) {
        JLabel lbl = new JLabel(text);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 10));
        lbl.setForeground(Color.WHITE);
        lbl.setBackground(bg);
        lbl.setOpaque(true);
        lbl.setBorder(new EmptyBorder(3, 8, 3, 8));
        return lbl;
    }

    // ── Inner types ───────────────────────────────────────────────────────

    private record TopicItem(int id, String title, int postCount) {
        @Override public String toString() { return title; }
    }

    private class TopicCellRenderer extends DefaultListCellRenderer {
        @Override
        public Component getListCellRendererComponent(
                JList<?> list, Object value, int index, boolean isSelected, boolean cellHasFocus) {
            JPanel row = new JPanel(new BorderLayout(10, 0));
            row.setOpaque(true);
            row.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 0, 1, 0, BORDER_C),
                new EmptyBorder(6, 12, 6, 12)
            ));
            row.setBackground(isSelected ? new Color(0xED, 0xE9, 0xFE) : SURFACE);

            TopicItem item = (TopicItem) value;
            JLabel title = new JLabel(item.title());
            title.setFont(new Font("Segoe UI", Font.PLAIN, 12));
            title.setForeground(isSelected ? PRIMARY : TEXT);

            JLabel count = new JLabel(item.postCount() + " posts");
            count.setFont(new Font("Segoe UI", Font.PLAIN, 10));
            count.setForeground(MUTED);

            row.add(title, BorderLayout.CENTER);
            row.add(count, BorderLayout.EAST);
            return row;
        }
    }
}
