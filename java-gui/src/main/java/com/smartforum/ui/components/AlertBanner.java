package com.smartforum.ui.components;

import com.smartforum.ui.Theme;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

/**
 * Mirrors Laravel's {@code .alert} banner (success / danger / info / warning
 * variants), used for session flash messages and inline form errors.
 */
public class AlertBanner extends JPanel {

    public enum Kind { SUCCESS, DANGER, WARNING, INFO }

    public AlertBanner(String message, Kind kind) {
        super(new BorderLayout(10, 0));
        Color bg; Color fg; String icon;
        switch (kind) {
            case SUCCESS -> { bg = Theme.SUCCESS_BG; fg = Theme.SUCCESS_TX; icon = "\u2705"; }
            case DANGER  -> { bg = Theme.DANGER_BG;  fg = Theme.DANGER_TX;  icon = "\u274C"; }
            case WARNING -> { bg = Theme.WARNING_BG; fg = Theme.WARNING_TX; icon = "\u26A0\uFE0F"; }
            default      -> { bg = Theme.INFO_BG;    fg = Theme.INFO_TX;    icon = "\u2139\uFE0F"; }
        }
        setBackground(bg);
        setOpaque(true);
        setBorder(new EmptyBorder(13, 18, 13, 18));

        JLabel iconLbl = new JLabel(icon);
        iconLbl.setFont(Theme.fontEmoji(14));

        JLabel textLbl = new JLabel("<html><body style='width:520px'>" + escape(message) + "</body></html>");
        textLbl.setFont(Theme.fontSemibold(13));
        textLbl.setForeground(fg);

        add(iconLbl, BorderLayout.WEST);
        add(textLbl, BorderLayout.CENTER);
    }

    private static String escape(String s) {
        return s == null ? "" : s.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }

    @Override
    protected void paintComponent(Graphics g) {
        Graphics2D g2 = (Graphics2D) g.create();
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g2.setColor(getBackground());
        g2.fillRoundRect(0, 0, getWidth() - 1, getHeight() - 1, Theme.RADIUS_SM, Theme.RADIUS_SM);
        g2.dispose();
        super.paintComponent(g);
    }
}
