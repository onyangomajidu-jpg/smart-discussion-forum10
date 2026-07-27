package com.smartforum.ui;

import javax.swing.*;
import javax.swing.border.Border;
import javax.swing.border.EmptyBorder;
import java.awt.*;

/**
 * Single source of truth for colors, gradients, fonts, spacing and corner
 * radius used across every Swing panel.
 * <p>
 * Values are copied 1:1 from the Laravel master layout's {@code :root} CSS
 * variables in {@code resources/views/layouts/app.blade.php}, so any panel
 * built from these constants automatically matches the website's palette.
 * Do not hand-pick colors in individual panels — add a token here instead.
 */
public final class Theme {

    private Theme() { }

    // ── Brand / gradient ────────────────────────────────────────────────
    // --primary: #6366f1;  --primary-dark: #4f46e5;  --secondary: #8b5cf6;
    public static final Color PRIMARY      = new Color(0x63, 0x66, 0xF1);
    public static final Color PRIMARY_DARK = new Color(0x4F, 0x46, 0xE5);
    public static final Color SECONDARY    = new Color(0x8B, 0x5C, 0xF6);

    // --grad-warm: #f59e0b -> #ef4444   --grad-green: #10b981 -> #059669
    public static final Color WARM_START  = new Color(0xF5, 0x9E, 0x0B);
    public static final Color WARM_END    = new Color(0xEF, 0x44, 0x44);
    public static final Color GREEN_START = new Color(0x10, 0xB9, 0x81);
    public static final Color GREEN_END   = new Color(0x05, 0x96, 0x69);

    // --bg / --surface / --border / --text / --muted
    public static final Color BG      = new Color(0xF1, 0xF5, 0xF9);
    public static final Color SURFACE = Color.WHITE;
    public static final Color BORDER  = new Color(0xE2, 0xE8, 0xF0);
    public static final Color TEXT    = new Color(0x0F, 0x17, 0x2A);
    public static final Color MUTED   = new Color(0x64, 0x74, 0x8B);

    // --success / --danger / --warning / --info
    public static final Color SUCCESS = new Color(0x10, 0xB9, 0x81);
    public static final Color DANGER  = new Color(0xEF, 0x44, 0x44);
    public static final Color WARNING = new Color(0xF5, 0x9E, 0x0B);
    public static final Color INFO    = new Color(0x3B, 0x82, 0xF6);

    // Soft tint backgrounds used behind badges / alerts / notif icons
    public static final Color SUCCESS_BG = new Color(0xEC, 0xFD, 0xF5);
    public static final Color SUCCESS_TX = new Color(0x06, 0x5F, 0x46);
    public static final Color DANGER_BG  = new Color(0xFE, 0xF2, 0xF2);
    public static final Color DANGER_TX  = new Color(0x99, 0x1B, 0x1B);
    public static final Color WARNING_BG = new Color(0xFF, 0xFB, 0xEB);
    public static final Color WARNING_TX = new Color(0x92, 0x40, 0x0E);
    public static final Color INFO_BG    = new Color(0xEF, 0xF6, 0xFF);
    public static final Color INFO_TX    = new Color(0x1E, 0x40, 0xAF);

    // Sidebar footer / active-link tint (rgba(99,102,241,.10 / .07))
    public static final Color SIDEBAR_ACTIVE_BG = new Color(0xEE, 0xEE, 0xFD);
    public static final Color HOVER_BG          = new Color(0xF8, 0xFA, 0xFC);
    public static final Color CARD_HEADER_BG    = new Color(0xFA, 0xFB, 0xFF);
    public static final Color TABLE_HEAD_BG     = new Color(0xF8, 0xFA, 0xFC);
    public static final Color ROW_HOVER_BG      = new Color(0xFA, 0xFB, 0xFF);

    // ── Radius / spacing (px) ───────────────────────────────────────────
    public static final int RADIUS    = 14;
    public static final int RADIUS_SM = 9;
    public static final int SPACING_XS = 6;
    public static final int SPACING_SM = 10;
    public static final int SPACING_MD = 18;
    public static final int SPACING_LG = 24;
    public static final int SPACING_XL = 32;

    // ── Fonts (Inter isn't bundled with the JRE, so Segoe UI is the closest
    //    widely-available match on Windows; falls back gracefully elsewhere) ──
    private static final String FONT_FAMILY = "Segoe UI";
    private static final String EMOJI_FAMILY = "Segoe UI Emoji";

    public static Font fontRegular(int size)  { return new Font(FONT_FAMILY, Font.PLAIN, size); }
    public static Font fontMedium(int size)   { return new Font(FONT_FAMILY, Font.PLAIN, size); }
    public static Font fontSemibold(int size) { return new Font(FONT_FAMILY, Font.BOLD, size); }
    public static Font fontBold(int size)     { return new Font(FONT_FAMILY, Font.BOLD, size); }
    public static Font fontExtrabold(int size){ return new Font(FONT_FAMILY, Font.BOLD, size); }
    public static Font fontEmoji(int size)    { return new Font(EMOJI_FAMILY, Font.PLAIN, size); }

    public static final Font H1        = fontExtrabold(24);
    public static final Font H2        = fontBold(15);
    public static final Font BODY      = fontRegular(13);
    public static final Font BODY_SM   = fontRegular(12);
    public static final Font LABEL     = fontSemibold(11);

    // ── Gradient helpers ────────────────────────────────────────────────
    public static GradientPaint brandGradient(int w, int h) {
        return new GradientPaint(0, 0, PRIMARY, w, h, SECONDARY);
    }

    public static GradientPaint warmGradient(int w, int h) {
        return new GradientPaint(0, 0, WARM_START, w, h, WARM_END);
    }

    public static GradientPaint greenGradient(int w, int h) {
        return new GradientPaint(0, 0, GREEN_START, w, h, GREEN_END);
    }

    // ── Common borders ──────────────────────────────────────────────────
    public static Border cardPadding()   { return new EmptyBorder(SPACING_LG, SPACING_LG, SPACING_LG, SPACING_LG); }
    public static Border pagePadding()   { return new EmptyBorder(SPACING_XL, SPACING_XL, SPACING_XL, SPACING_XL); }

    /** Rounded 1px border in the Laravel --border color, radius-matched. */
    public static Border roundedLine(Color color, int thickness) {
        return BorderFactory.createLineBorder(color, thickness, true);
    }

    // ── Small style helpers ─────────────────────────────────────────────

    /** Styles a JButton as the Laravel .btn-primary pill (solid gradient look via flat bg fallback). */
    public static void stylePrimaryButton(AbstractButton b) {
        b.setFont(fontSemibold(13));
        b.setForeground(Color.WHITE);
        b.setBackground(PRIMARY);
        b.setOpaque(true);
        b.setBorderPainted(false);
        b.setFocusPainted(false);
        b.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        b.setBorder(new EmptyBorder(10, 20, 10, 20));
    }

    public static void styleSecondaryButton(AbstractButton b) {
        b.setFont(fontSemibold(13));
        b.setForeground(new Color(0x47, 0x55, 0x69));
        b.setBackground(new Color(0xF1, 0xF5, 0xF9));
        b.setOpaque(true);
        b.setBorderPainted(true);
        b.setBorder(BorderFactory.createCompoundBorder(
            roundedLine(BORDER, 1), new EmptyBorder(8, 18, 8, 18)));
        b.setFocusPainted(false);
        b.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
    }

    public static void styleDangerButton(AbstractButton b) {
        b.setFont(fontSemibold(13));
        b.setForeground(Color.WHITE);
        b.setBackground(DANGER);
        b.setOpaque(true);
        b.setBorderPainted(false);
        b.setFocusPainted(false);
        b.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        b.setBorder(new EmptyBorder(10, 20, 10, 20));
    }

    public static void styleSuccessButton(AbstractButton b) {
        b.setFont(fontSemibold(13));
        b.setForeground(Color.WHITE);
        b.setBackground(SUCCESS);
        b.setOpaque(true);
        b.setBorderPainted(false);
        b.setFocusPainted(false);
        b.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        b.setBorder(new EmptyBorder(10, 20, 10, 20));
    }
}
