package com.smartforum.ui.components;

import com.smartforum.ui.Theme;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;

/**
 * A single left-sidebar navigation row: icon glyph + label, with an optional
 * trailing count badge. Mirrors Laravel's {@code .sidebar-link} /
 * {@code .sidebar-link.active} (left accent border + tinted background when
 * selected, hover tint otherwise).
 */
public class SidebarLink extends JPanel {

    private boolean active = false;
    private final JLabel iconLbl;
    private final JLabel textLbl;
    private final JLabel badgeLbl = new JLabel("", SwingConstants.CENTER) {
        @Override protected void paintComponent(Graphics g) {
            if (isVisible() && getText() != null && !getText().isEmpty()) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(Theme.PRIMARY);
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), getHeight(), getHeight());
                g2.dispose();
            }
            super.paintComponent(g);
        }
    };

    public SidebarLink(String icon, String text) {
        setLayout(new BorderLayout(10, 0));
        setOpaque(false);
        setBorder(new EmptyBorder(10, 12, 10, 12));
        setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
        setAlignmentX(Component.LEFT_ALIGNMENT);
        setToolTipText(text);

        iconLbl = new JLabel(icon, SwingConstants.CENTER);
        iconLbl.setFont(Theme.fontEmoji(14));
        iconLbl.setPreferredSize(new Dimension(20, 18));

        textLbl = new JLabel(text);
        textLbl.setFont(Theme.fontSemibold(13));
        textLbl.setForeground(Theme.MUTED);

        JPanel left = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 0));
        left.setOpaque(false);
        left.add(iconLbl);
        left.add(textLbl);

        badgeLbl.setFont(Theme.fontBold(10));
        badgeLbl.setForeground(Color.WHITE);
        badgeLbl.setOpaque(false);
        badgeLbl.setBorder(new EmptyBorder(2, 8, 2, 8));
        badgeLbl.setVisible(false);

        add(left, BorderLayout.WEST);
        add(badgeLbl, BorderLayout.EAST);

        addMouseListener(new MouseAdapter() {
            @Override public void mouseEntered(MouseEvent e) { if (!active) { setOpaque(true); setBackground(Theme.HOVER_BG); repaint(); } }
            @Override public void mouseExited(MouseEvent e)  { if (!active) { setOpaque(false); repaint(); } }
        });
    }

    /** Collapse to icon-only (no label, no badge) when sidebar is narrow. */
    public void setCollapsed(boolean collapsed) {
        textLbl.setVisible(!collapsed);
        badgeLbl.setVisible(!collapsed && badgeLbl.getText() != null && !badgeLbl.getText().isEmpty());
        setBorder(collapsed
            ? new EmptyBorder(10, 8, 10, 8)
            : new EmptyBorder(10, 12, 10, 12));
        iconLbl.setPreferredSize(collapsed ? new Dimension(28, 18) : new Dimension(20, 18));
    }

    public SidebarLink withBadge(int count) {
        if (count > 0) {
            badgeLbl.setText(String.valueOf(count));
            badgeLbl.setVisible(true);
        }
        return this;
    }

    public void setActive(boolean active) {
        this.active = active;
        textLbl.setForeground(active ? Theme.PRIMARY : Theme.MUTED);
        setOpaque(active);
        setBackground(active ? Theme.SIDEBAR_ACTIVE_BG : null);
        repaint();
    }

    public void onClick(Runnable action) {
        addMouseListener(new MouseAdapter() {
            @Override public void mouseClicked(MouseEvent e) { action.run(); }
        });
    }

    @Override
    protected void paintComponent(Graphics g) {
        if (isOpaque()) {
            Graphics2D g2 = (Graphics2D) g.create();
            g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
            g2.setColor(getBackground());
            g2.fillRoundRect(0, 0, getWidth(), getHeight(), 10, 10);
            if (active) {
                g2.setColor(Theme.PRIMARY);
                g2.fillRoundRect(0, 0, 3, getHeight(), 3, 3);
            }
            g2.dispose();
        }
        super.paintComponent(g);
    }
}
