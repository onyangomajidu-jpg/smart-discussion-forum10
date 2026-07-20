package com.smartforum.ui;

import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

public class LecturerDashboardPanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x63, 0x66, 0xF1);
    private static final Color PURPLE   = new Color(0x8B, 0x5C, 0xF6);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);

    private final AuthUser   user;
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

        // Hero
        JPanel hero = new JPanel(new BorderLayout());
        hero.setBackground(PRIMARY);
        hero.setBorder(new EmptyBorder(28, 32, 28, 32));
        hero.setMaximumSize(new Dimension(Integer.MAX_VALUE, 110));
        hero.setAlignmentX(LEFT_ALIGNMENT);
        JPanel heroLeft = new JPanel();
        heroLeft.setOpaque(false);
        heroLeft.setLayout(new BoxLayout(heroLeft, BoxLayout.Y_AXIS));
        JLabel portalLbl = new JLabel("Lecturer Portal");
        portalLbl.setFont(new Font("Segoe UI", Font.BOLD, 11));
        portalLbl.setForeground(new Color(200, 200, 255));
        JLabel welcomeLbl = new JLabel("Welcome back, " + user.getName() + " 👋");
        welcomeLbl.setFont(new Font("Segoe UI", Font.BOLD, 22));
        welcomeLbl.setForeground(Color.WHITE);
        JLabel subLbl = new JLabel("Manage your quizzes, track student progress, and view results.");
        subLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        subLbl.setForeground(new Color(200, 200, 255));
        heroLeft.add(portalLbl);
        heroLeft.add(Box.createVerticalStrut(6));
        heroLeft.add(welcomeLbl);
        heroLeft.add(Box.createVerticalStrut(4));
        heroLeft.add(subLbl);
        hero.add(heroLeft, BorderLayout.WEST);

        // Row 1: My Quizzes, Create Quiz, Analytics
        JPanel row1 = new JPanel(new GridLayout(1, 3, 16, 0));
        row1.setBackground(BG);
        row1.setAlignmentX(LEFT_ALIGNMENT);
        row1.setMaximumSize(new Dimension(Integer.MAX_VALUE, 130));
        row1.add(navCard("📋", "My Quizzes",        "View and manage all your quizzes",       "🎯  Quizzes"));
        row1.add(navCard("➕", "Create Quiz",        "Build a new assessment",                 "🎯  Quizzes"));
        row1.add(navCard("📊", "Analytics",          "Evaluation roster & compliance",         "📊  Analytics"));

        // Row 2: Manage Groups, Topic Discussions
        JPanel row2 = new JPanel(new GridLayout(1, 2, 16, 0));
        row2.setBackground(BG);
        row2.setAlignmentX(LEFT_ALIGNMENT);
        row2.setMaximumSize(new Dimension(Integer.MAX_VALUE, 130));
        row2.add(navCard("👥", "Manage Groups",      "Create and manage class groups",         "👥  Groups"));
        row2.add(navCard("💬", "Topic Discussions",  "Create topics, chat & manage participation", "💬  Forum"));

        body.add(hero);
        body.add(Box.createVerticalStrut(24));
        body.add(row1);
        body.add(Box.createVerticalStrut(16));
        body.add(row2);

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    private JPanel navCard(String icon, String title, String sub, String targetTab) {
        JPanel card = new JPanel(new BorderLayout(0, 8));
        card.setBackground(SURFACE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C),
            new EmptyBorder(20, 20, 20, 20)));
        card.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));

        JLabel ico = new JLabel(icon, SwingConstants.CENTER);
        ico.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 30));
        JLabel titleLbl = new JLabel(title, SwingConstants.CENTER);
        titleLbl.setFont(new Font("Segoe UI", Font.BOLD, 15));
        titleLbl.setForeground(TEXT);
        JLabel subLbl = new JLabel("<html><center>" + sub + "</center></html>", SwingConstants.CENTER);
        subLbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        subLbl.setForeground(MUTED);

        JPanel center = new JPanel();
        center.setOpaque(false);
        center.setLayout(new BoxLayout(center, BoxLayout.Y_AXIS));
        ico.setAlignmentX(CENTER_ALIGNMENT);
        titleLbl.setAlignmentX(CENTER_ALIGNMENT);
        subLbl.setAlignmentX(CENTER_ALIGNMENT);
        center.add(ico);
        center.add(Box.createVerticalStrut(8));
        center.add(titleLbl);
        center.add(Box.createVerticalStrut(4));
        center.add(subLbl);
        card.add(center, BorderLayout.CENTER);

        card.addMouseListener(new java.awt.event.MouseAdapter() {
            @Override public void mouseClicked(java.awt.event.MouseEvent e) {
                if (tabs == null) return;
                for (int i = 0; i < tabs.getTabCount(); i++) {
                    if (tabs.getTitleAt(i).trim().equals(targetTab.trim())) {
                        tabs.setSelectedIndex(i);
                        return;
                    }
                }
            }
            @Override public void mouseEntered(java.awt.event.MouseEvent e) {
                card.setBackground(new Color(0xF0, 0xF0, 0xFF));
            }
            @Override public void mouseExited(java.awt.event.MouseEvent e) {
                card.setBackground(SURFACE);
            }
        });
        return card;
    }
}
