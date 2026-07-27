package com.smartforum.ui.components;

import com.smartforum.ui.Theme;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

/**
 * Mirrors Laravel's {@code .card} component: white rounded-corner surface,
 * 1px border, soft shadow-like outline, with an optional header row
 * ({@code .card-header}) and a padded body ({@code .card-body}).
 */
public class Card extends JPanel {

    private final JPanel headerRow = new JPanel(new BorderLayout());
    private final JPanel body      = new JPanel();
    private boolean headerAdded = false;

    public Card() {
        super(new BorderLayout());
        setOpaque(false);
        body.setOpaque(false);
        body.setLayout(new BorderLayout());
        body.setBorder(Theme.cardPadding());
        add(body, BorderLayout.CENTER);

        headerRow.setOpaque(false);
        headerRow.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 0, 1, 0, Theme.BORDER),
            new EmptyBorder(14, 24, 14, 24)));
    }

    /** Adds a title (with optional icon glyph prefix) to the card header. */
    public Card withTitle(String titleText) {
        JLabel title = new JLabel(titleText);
        title.setFont(Theme.H2);
        title.setForeground(Theme.TEXT);
        headerRow.add(title, BorderLayout.WEST);
        installHeader();
        return this;
    }

    /** Adds a trailing action component (e.g. a button) to the header's right side. */
    public Card withHeaderAction(JComponent action) {
        installHeader();
        headerRow.add(action, BorderLayout.EAST);
        return this;
    }

    private void installHeader() {
        if (!headerAdded) {
            add(headerRow, BorderLayout.NORTH);
            headerAdded = true;
        }
    }

    /** The content panel — add your fields/components here. Uses BorderLayout by default. */
    public JPanel body() { return body; }

    public Card setBodyLayout(LayoutManager lm) {
        body.setLayout(lm);
        return this;
    }

    @Override
    protected void paintComponent(Graphics g) {
        Graphics2D g2 = (Graphics2D) g.create();
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g2.setColor(new Color(0x63, 0x66, 0xF1, 18));
        g2.fillRoundRect(1, 3, getWidth() - 2, getHeight() - 2, Theme.RADIUS, Theme.RADIUS);
        g2.setColor(Theme.SURFACE);
        g2.fillRoundRect(0, 0, getWidth() - 1, getHeight() - 4, Theme.RADIUS, Theme.RADIUS);
        g2.setColor(Theme.BORDER);
        g2.setStroke(new BasicStroke(1f));
        g2.drawRoundRect(0, 0, getWidth() - 2, getHeight() - 5, Theme.RADIUS, Theme.RADIUS);
        g2.dispose();
        super.paintComponent(g);
    }
}
