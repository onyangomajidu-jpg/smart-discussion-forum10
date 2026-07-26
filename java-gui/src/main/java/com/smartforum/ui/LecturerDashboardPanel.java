package com.smartforum.ui;

import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.*;
import java.net.URL;

/**
 * Mirrors lecturer/dashboard.blade.php exactly.
 * Hero: indigo→purple gradient, "📋 Lecturer Portal" tag (fa-chalkboard-user),
 * forum.png logo loaded from server, welcome + subtitle.
 * Row 1: 📋 My Quizzes | ➕ Create Quiz | 📊 Analytics
 * Row 2: 👥 Manage Groups | 💬 Topic Discussions
 */
public class LecturerDashboardPanel extends JPanel {

    private static final Color C1      = new Color(0x63, 0x66, 0xF1); // indigo  (#6366f1)
    private static final Color C2      = new Color(0x8B, 0x5C, 0xF6); // purple  (#8b5cf6)
    private static final Color BG      = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE = Color.WHITE;
    private static final Color BORDER  = new Color(0xE2, 0xE8, 0xF0);
    private static final Color TEXT    = new Color(0x0F, 0x17, 0x2A);
    private static final Color MUTED   = new Color(0x64, 0x74, 0x8B);

    private final AuthUser    user;
    private final JTabbedPane tabs;

    public LecturerDashboardPanel(ApiClient api, AuthUser user, JTabbedPane tabs) {
        this.user = user;
        this.tabs = tabs;
        setBackground(BG);
        setLayout(new BorderLayout());
        buildUI();
    }

    private void buildUI() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(24, 24, 40, 24));

        body.add(buildHero());
        body.add(Box.createVerticalStrut(24));
        body.add(buildRow1());
        body.add(Box.createVerticalStrut(16));
        body.add(buildRow2());

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    // ── Hero — mirrors the indigo→purple gradient banner in lecturer/dashboard.blade.php ──
    private JPanel buildHero() {
        JPanel hero = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                // mirrors: background:linear-gradient(135deg,#6366f1,#8b5cf6)
                g2.setPaint(new GradientPaint(0, 0, C1, getWidth(), getHeight(), C2));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 16, 16);
                // decorative circle — mirrors position:absolute;top:-60px;right:-60px circle
                g2.setColor(new Color(255, 255, 255, 18));
                g2.fillOval(getWidth() - 130, -60, 200, 200);
                g2.dispose();
            }
        };
        hero.setOpaque(false);
        hero.setBorder(new EmptyBorder(28, 32, 28, 32));
        hero.setMaximumSize(new Dimension(Integer.MAX_VALUE, 130));
        hero.setAlignmentX(LEFT_ALIGNMENT);

        // Left: tag + welcome + subtitle
        JPanel left = new JPanel();
        left.setOpaque(false);
        left.setLayout(new BoxLayout(left, BoxLayout.Y_AXIS));

        // mirrors: fa-chalkboard-user + "Lecturer Portal" uppercase tag
        JLabel tag = new JLabel("\uD83D\uDCCB LECTURER PORTAL"); // 📋
        tag.setFont(new Font("Segoe UI Emoji", Font.BOLD, 11));
        tag.setForeground(new Color(200, 200, 255, 180));

        JLabel welcome = new JLabel("Welcome back, " + user.getName() + " \uD83D\uDC4B"); // 👋
        welcome.setFont(new Font("Segoe UI", Font.BOLD, 24));
        welcome.setForeground(Color.WHITE);

        JLabel sub = new JLabel("Manage your quizzes, track student progress, and view results.");
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        sub.setForeground(new Color(200, 200, 255, 200));

        left.add(tag);
        left.add(Box.createVerticalStrut(6));
        left.add(welcome);
        left.add(Box.createVerticalStrut(4));
        left.add(sub);

        // Right: forum.png logo — mirrors <img src="{{ asset('images/forum.png') }}"> in navbar
        JLabel logoLbl = new JLabel("\uD83D\uDCAC", SwingConstants.CENTER); // 💬 fallback
        logoLbl.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 40));
        new SwingWorker<ImageIcon, Void>() {
            @Override protected ImageIcon doInBackground() throws Exception {
                URL url = new URL(ApiClient.BASE_URL.replace("/api", "") + "/images/forum.png");
                Image img = new ImageIcon(url).getImage().getScaledInstance(56, 56, Image.SCALE_SMOOTH);
                return new ImageIcon(img);
            }
            @Override protected void done() {
                try { logoLbl.setIcon(get()); logoLbl.setText(null); } catch (Exception ignored) {}
            }
        }.execute();

        hero.add(left,    BorderLayout.WEST);
        hero.add(logoLbl, BorderLayout.EAST);
        return hero;
    }

    // ── Row 1: 📋 My Quizzes | ➕ Create Quiz | 📊 Analytics ─────────────
    private JPanel buildRow1() {
        JPanel row = new JPanel(new GridLayout(1, 3, 16, 0));
        row.setBackground(BG);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 140));
        // mirrors lecturer/dashboard.blade.php row-1 cards exactly
        row.add(navCard("\uD83D\uDCCB", "My Quizzes",  "View and manage all your quizzes",   "\uD83D\uDCCB  My Quizzes"));
        row.add(navCard("\u2795",       "Create Quiz", "Build a new assessment",             "\uD83D\uDCCB  My Quizzes"));
        row.add(navCard("\uD83D\uDCCA", "Analytics",   "Evaluation roster & compliance",     "\uD83D\uDCCA  Analytics"));
        return row;
    }

    // ── Row 2: 👥 Manage Groups | 💬 Topic Discussions ───────────────────
    private JPanel buildRow2() {
        JPanel row = new JPanel(new GridLayout(1, 2, 16, 0));
        row.setBackground(BG);
        row.setAlignmentX(LEFT_ALIGNMENT);
        row.setMaximumSize(new Dimension(Integer.MAX_VALUE, 140));
        row.add(navCard("\uD83D\uDC65", "Manage Groups",     "Create and manage class groups",             "\uD83D\uDC65  Groups"));
        row.add(navCard("\uD83D\uDCAC", "Topic Discussions", "Create topics, chat & manage participation", "\uD83D\uDCAC  Forum"));
        return row;
    }

    // ── Nav card — mirrors white .card with 36px emoji + hover lift shadow ─
    private JPanel navCard(String icon, String title, String sub, String targetTab) {
        JPanel card = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(getBackground());
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 14, 14);
                g2.dispose();
            }
        };
        card.setBackground(SURFACE);
        card.setOpaque(false);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER),
            new EmptyBorder(20, 20, 20, 20)));
        card.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));

        JPanel center = new JPanel();
        center.setOpaque(false);
        center.setLayout(new BoxLayout(center, BoxLayout.Y_AXIS));

        // font-size:36px emoji — exact mirror of Laravel card emoji
        JLabel ico = new JLabel(icon, SwingConstants.CENTER);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 36));
        ico.setAlignmentX(CENTER_ALIGNMENT);

        JLabel titleLbl = new JLabel(title, SwingConstants.CENTER);
        titleLbl.setFont(new Font("Segoe UI", Font.BOLD, 15));
        titleLbl.setForeground(TEXT);
        titleLbl.setAlignmentX(CENTER_ALIGNMENT);

        JLabel subLbl = new JLabel("<html><center>" + sub + "</center></html>", SwingConstants.CENTER);
        subLbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        subLbl.setForeground(MUTED);
        subLbl.setAlignmentX(CENTER_ALIGNMENT);

        center.add(ico);
        center.add(Box.createVerticalStrut(8));
        center.add(titleLbl);
        center.add(Box.createVerticalStrut(4));
        center.add(subLbl);
        card.add(center, BorderLayout.CENTER);

        // hover: translateY(-3px) + indigo shadow — mirrors onmouseover in blade
        card.addMouseListener(new MouseAdapter() {
            @Override public void mouseClicked(MouseEvent e) {
                if (tabs == null) return;
                for (int i = 0; i < tabs.getTabCount(); i++) {
                    if (tabs.getTitleAt(i).trim().equals(targetTab.trim())) {
                        tabs.setSelectedIndex(i);
                        return;
                    }
                }
            }
            @Override public void mouseEntered(MouseEvent e) {
                card.setBackground(new Color(0xF0, 0xF0, 0xFF));
                card.repaint();
            }
            @Override public void mouseExited(MouseEvent e) {
                card.setBackground(SURFACE);
                card.repaint();
            }
        });
        return card;
    }
}
