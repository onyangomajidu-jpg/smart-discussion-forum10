package com.smartforum.ui.components;

import com.smartforum.ui.Theme;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

/**
 * Mirrors Laravel's {@code .stat-card}: a rounded white tile with a thin
 * gradient top accent, an icon glyph, a large gradient-colored value, and a
 * small uppercase label underneath. Used in {@code .stats-grid} rows across
 * every dashboard (admin / lecturer / student).
 */
public class StatCard extends JPanel {

    public StatCard(String icon, String value, String label) {
        setLayout(new BoxLayout(this, BoxLayout.Y_AXIS));
        setOpaque(false);
        setBorder(new EmptyBorder(20, 14, 18, 14));
        setAlignmentX(Component.CENTER_ALIGNMENT);

        JLabel iconLbl = new JLabel(icon, SwingConstants.CENTER);
        iconLbl.setFont(Theme.fontEmoji(22));
        iconLbl.setAlignmentX(Component.CENTER_ALIGNMENT);

        JLabel valueLbl = new JLabel(value, SwingConstants.CENTER);
        valueLbl.setFont(Theme.fontExtrabold(28));
        valueLbl.setForeground(Theme.PRIMARY);
        valueLbl.setAlignmentX(Component.CENTER_ALIGNMENT);

        JLabel labelLbl = new JLabel(label.toUpperCase(), SwingConstants.CENTER);
        labelLbl.setFont(Theme.LABEL);
        labelLbl.setForeground(Theme.MUTED);
        labelLbl.setAlignmentX(Component.CENTER_ALIGNMENT);

        add(iconLbl);
        add(Box.createVerticalStrut(6));
        add(valueLbl);
        add(Box.createVerticalStrut(5));
        add(labelLbl);
    }

    /** Convenience factory for building a full stats-grid row (mirrors .stats-grid). */
    public static JPanel grid(int columns, StatCard... cards) {
        JPanel grid = new JPanel(new GridLayout(1, columns, 16, 0));
        grid.setOpaque(false);
        for (StatCard c : cards) grid.add(c);
        return grid;
    }

    @Override
    protected void paintComponent(Graphics g) {
        Graphics2D g2 = (Graphics2D) g.create();
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g2.setColor(Theme.SURFACE);
        g2.fillRoundRect(0, 0, getWidth() - 1, getHeight() - 1, Theme.RADIUS, Theme.RADIUS);
        g2.setColor(Theme.BORDER);
        g2.drawRoundRect(0, 0, getWidth() - 1, getHeight() - 1, Theme.RADIUS, Theme.RADIUS);
        // top gradient accent strip, 3px, matching .stat-card::before
        Shape oldClip = g2.getClip();
        g2.clipRect(0, 0, getWidth(), 5);
        g2.setPaint(Theme.brandGradient(getWidth(), 0));
        g2.fillRoundRect(0, 0, getWidth() - 1, 6, Theme.RADIUS, Theme.RADIUS);
        g2.setClip(oldClip);
        g2.dispose();
        super.paintComponent(g);
    }
}
