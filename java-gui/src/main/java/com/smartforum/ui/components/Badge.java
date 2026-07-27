package com.smartforum.ui.components;

import com.smartforum.ui.Theme;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

/**
 * Pill-shaped status label mirroring Laravel's {@code .badge} + status
 * variants ({@code .badge-open}, {@code .badge-closed}, {@code .badge-A}..
 * {@code .badge-F}, etc). Pass a {@link Kind} to pick the tint.
 */
public class Badge extends JLabel {

    public enum Kind { SUCCESS, DANGER, WARNING, INFO, NEUTRAL }

    private final Color bg;
    private final Color fg;

    public Badge(String text, Kind kind) {
        super(text, SwingConstants.CENTER);
        switch (kind) {
            case SUCCESS -> { bg = Theme.SUCCESS_BG; fg = Theme.SUCCESS_TX; }
            case DANGER  -> { bg = Theme.DANGER_BG;  fg = Theme.DANGER_TX;  }
            case WARNING -> { bg = Theme.WARNING_BG; fg = Theme.WARNING_TX; }
            case INFO    -> { bg = Theme.INFO_BG;    fg = Theme.INFO_TX;    }
            default      -> { bg = new Color(0xF1, 0xF5, 0xF9); fg = new Color(0x47, 0x55, 0x69); }
        }
        setFont(Theme.fontBold(11));
        setForeground(fg);
        setOpaque(false);
        setBorder(new EmptyBorder(4, 12, 4, 12));
    }

    /** Maps common forum status strings (open/closed/draft/published/A-F grades) to a Kind. */
    public static Badge forStatus(String status) {
        if (status == null) return new Badge("—", Kind.NEUTRAL);
        String s = status.trim().toLowerCase();
        return switch (s) {
            case "open", "published", "done", "active", "resolved" -> new Badge(cap(status), Kind.SUCCESS);
            case "closed", "blacklisted", "suspended", "f" -> new Badge(cap(status), Kind.DANGER);
            case "draft", "upcoming", "pending", "warned" -> new Badge(cap(status), Kind.WARNING);
            default -> new Badge(cap(status), Kind.INFO);
        };
    }

    private static String cap(String s) {
        return s.isEmpty() ? s : Character.toUpperCase(s.charAt(0)) + s.substring(1);
    }

    @Override
    protected void paintComponent(Graphics g) {
        Graphics2D g2 = (Graphics2D) g.create();
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g2.setColor(bg);
        g2.fillRoundRect(0, 0, getWidth(), getHeight(), getHeight(), getHeight());
        g2.dispose();
        super.paintComponent(g);
    }
}
