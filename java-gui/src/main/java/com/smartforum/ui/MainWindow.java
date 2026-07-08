package com.smartforum.ui;

import com.smartforum.auth.AuthService;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

/**
 * Main application window shown after successful login (SDD §3.1).
 * Acts as the shell for the discussion forum desktop UI.
 */
public class MainWindow extends JFrame {

    private static final Color PRIMARY = new Color(0x66, 0x7E, 0xEA);

    public MainWindow(AuthUser user, AuthService authService) {
        setTitle("Smart Discussion Forum — " + user.getName());
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setSize(1100, 700);
        setLocationRelativeTo(null);

        // ── Top bar ───────────────────────────────────────────────────────
        JPanel topBar = new JPanel(new BorderLayout());
        topBar.setBackground(PRIMARY);
        topBar.setBorder(new EmptyBorder(10, 20, 10, 20));

        JLabel title = new JLabel("🎓 Smart Discussion Forum");
        title.setFont(new Font("Segoe UI", Font.BOLD, 18));
        title.setForeground(Color.WHITE);

        JLabel userInfo = new JLabel(
            user.getName() + "  [" + user.getRole().toUpperCase() + "]");
        userInfo.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        userInfo.setForeground(Color.WHITE);

        JButton logoutBtn = new JButton("Logout");
        logoutBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        logoutBtn.setForeground(PRIMARY);
        logoutBtn.setBackground(Color.WHITE);
        logoutBtn.setBorderPainted(false);
        logoutBtn.setFocusPainted(false);
        logoutBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        logoutBtn.addActionListener(e -> {
            authService.logout();
            dispose();
            LoginWindow login = new LoginWindow(authService);
            login.setVisible(true);
        });

        JPanel right = new JPanel(new FlowLayout(FlowLayout.RIGHT, 12, 0));
        right.setOpaque(false);
        right.add(userInfo);
        right.add(logoutBtn);

        topBar.add(title, BorderLayout.WEST);
        topBar.add(right,  BorderLayout.EAST);

        // ── Placeholder content ───────────────────────────────────────────
        JLabel placeholder = new JLabel(
            "<html><center><h2>Welcome, " + user.getName() + "!</h2>"
            + "<p>Discussion forum content loads here.</p></center></html>",
            SwingConstants.CENTER);
        placeholder.setFont(new Font("Segoe UI", Font.PLAIN, 16));
        placeholder.setForeground(new Color(0x49, 0x50, 0x57));

        getContentPane().setLayout(new BorderLayout());
        getContentPane().add(topBar,       BorderLayout.NORTH);
        getContentPane().add(placeholder,  BorderLayout.CENTER);
    }
}
