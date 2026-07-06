package com.smartforum.ui;

import com.smartforum.auth.AuthException;
import com.smartforum.auth.AuthService;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.*;
import java.io.IOException;

/**
 * Swing login window (SDD §3.1 — Desktop Auth).
 *
 * Layout mirrors the web login.blade.php:
 *   LEFT  — logo, email field, password field, remember-me, Login button,
 *            forgot-password link, register link
 *   RIGHT — Forum Rules panel
 *
 * Brand colours: #667eea (primary) / #764ba2 (secondary)
 */
public class LoginWindow extends JFrame {

    // ── Brand colours (match web CSS) ────────────────────────────────────
    private static final Color PRIMARY   = new Color(0x66, 0x7E, 0xEA);
    private static final Color SECONDARY = new Color(0x76, 0x4B, 0xA2);
    private static final Color BG_PANEL  = new Color(0xF8, 0xF9, 0xFA);
    private static final Color BORDER_C  = new Color(0xE1, 0xE4, 0xE8);
    private static final Color TEXT_MUTE = new Color(0x6C, 0x75, 0x7D);
    private static final Color ERROR_C   = new Color(0xDC, 0x35, 0x45);

    private final AuthService authService;

    // Form fields
    private JTextField     emailField;
    private JPasswordField passwordField;
    private JCheckBox      rememberMe;
    private JButton        loginButton;
    private JLabel         statusLabel;

    public LoginWindow(AuthService authService) {
        this.authService = authService;
        buildUI();
    }

    // ── UI construction ───────────────────────────────────────────────────

    private void buildUI() {
        setTitle("Smart Discussion Forum — Login");
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setResizable(false);

        // Gradient background panel
        JPanel root = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g;
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), SECONDARY));
                g2.fillRect(0, 0, getWidth(), getHeight());
            }
        };
        root.setBorder(new EmptyBorder(30, 30, 30, 30));

        // White card
        JPanel card = new JPanel(new GridLayout(1, 2));
        card.setBackground(Color.WHITE);
        card.setBorder(BorderFactory.createLineBorder(BORDER_C));
        card.setPreferredSize(new Dimension(900, 560));

        card.add(buildFormPanel());
        card.add(buildRulesPanel());

        root.add(card, BorderLayout.CENTER);
        setContentPane(root);
        pack();
        setLocationRelativeTo(null);
    }

    // ── Left: login form ──────────────────────────────────────────────────

    private JPanel buildFormPanel() {
        JPanel panel = new JPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBackground(Color.WHITE);
        panel.setBorder(new EmptyBorder(40, 40, 40, 40));

        // Logo
        JLabel logo = new JLabel("🎓 Smart Discussion Forum", SwingConstants.CENTER);
        logo.setFont(new Font("Segoe UI", Font.BOLD, 22));
        logo.setForeground(PRIMARY);
        logo.setAlignmentX(CENTER_ALIGNMENT);

        JLabel sub = new JLabel("Welcome back! Please login to your account", SwingConstants.CENTER);
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        sub.setForeground(TEXT_MUTE);
        sub.setAlignmentX(CENTER_ALIGNMENT);

        // Status / error label
        statusLabel = new JLabel(" ", SwingConstants.CENTER);
        statusLabel.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        statusLabel.setForeground(ERROR_C);
        statusLabel.setAlignmentX(CENTER_ALIGNMENT);

        // Email
        JLabel emailLbl = fieldLabel("Email Address");
        emailField = new JTextField(20);
        styleField(emailField);

        // Password
        JLabel passLbl = fieldLabel("Password");
        passwordField = new JPasswordField(20);
        styleField(passwordField);

        // Remember me
        rememberMe = new JCheckBox("Remember me");
        rememberMe.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        rememberMe.setForeground(TEXT_MUTE);
        rememberMe.setBackground(Color.WHITE);
        rememberMe.setAlignmentX(LEFT_ALIGNMENT);

        // Login button
        loginButton = new JButton("Login");
        loginButton.setFont(new Font("Segoe UI", Font.BOLD, 15));
        loginButton.setForeground(Color.WHITE);
        loginButton.setBackground(PRIMARY);
        loginButton.setOpaque(true);
        loginButton.setBorderPainted(false);
        loginButton.setFocusPainted(false);
        loginButton.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        loginButton.setMaximumSize(new Dimension(Integer.MAX_VALUE, 44));
        loginButton.setAlignmentX(LEFT_ALIGNMENT);
        loginButton.addActionListener(e -> attemptLogin());

        // Allow Enter key to submit
        getRootPane().setDefaultButton(loginButton);

        // Forgot password link
        JButton forgotLink = linkButton("Forgot your password?");
        forgotLink.addActionListener(e ->
            JOptionPane.showMessageDialog(this,
                "Visit: http://localhost:8000/forgot-password",
                "Reset Password", JOptionPane.INFORMATION_MESSAGE));

        // Register link
        JButton registerLink = linkButton("Register here");
        registerLink.addActionListener(e ->
            JOptionPane.showMessageDialog(this,
                "Visit: http://localhost:8000/register",
                "Register", JOptionPane.INFORMATION_MESSAGE));

        JPanel registerRow = new JPanel(new FlowLayout(FlowLayout.CENTER, 4, 0));
        registerRow.setBackground(Color.WHITE);
        registerRow.setAlignmentX(CENTER_ALIGNMENT);
        JLabel noAccount = new JLabel("Don't have an account?");
        noAccount.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        noAccount.setForeground(TEXT_MUTE);
        registerRow.add(noAccount);
        registerRow.add(registerLink);

        // Assemble
        panel.add(logo);
        panel.add(Box.createVerticalStrut(4));
        panel.add(sub);
        panel.add(Box.createVerticalStrut(16));
        panel.add(statusLabel);
        panel.add(Box.createVerticalStrut(8));
        panel.add(emailLbl);
        panel.add(Box.createVerticalStrut(4));
        panel.add(emailField);
        panel.add(Box.createVerticalStrut(14));
        panel.add(passLbl);
        panel.add(Box.createVerticalStrut(4));
        panel.add(passwordField);
        panel.add(Box.createVerticalStrut(12));
        panel.add(rememberMe);
        panel.add(Box.createVerticalStrut(16));
        panel.add(loginButton);
        panel.add(Box.createVerticalStrut(12));
        panel.add(forgotLink);
        panel.add(Box.createVerticalStrut(16));
        panel.add(new JSeparator());
        panel.add(Box.createVerticalStrut(12));
        panel.add(registerRow);

        return panel;
    }

    // ── Right: forum rules ────────────────────────────────────────────────

    private JPanel buildRulesPanel() {
        JPanel panel = new JPanel(new BorderLayout());
        panel.setBackground(BG_PANEL);
        panel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(0, 1, 0, 0, BORDER_C),
            new EmptyBorder(40, 30, 40, 30)
        ));

        JLabel title = new JLabel("📋 Forum Rules");
        title.setFont(new Font("Segoe UI", Font.BOLD, 20));
        title.setForeground(new Color(0x33, 0x33, 0x33));
        title.setBorder(BorderFactory.createMatteBorder(0, 0, 2, 0, PRIMARY));

        String[] rules = {
            "Be respectful and courteous to all members",
            "No harassment, hate speech, or discrimination",
            "Stay on topic and contribute meaningfully",
            "No spam, advertising, or self-promotion",
            "Respect intellectual property and cite sources",
            "Use appropriate language — keep content PG-13",
            "No sharing of personal information",
            "Report inappropriate content to moderators",
            "Follow academic integrity guidelines",
            "One account per person only",
            "Moderators' decisions are final",
            "Have fun and help build a great community!"
        };

        JPanel list = new JPanel();
        list.setLayout(new BoxLayout(list, BoxLayout.Y_AXIS));
        list.setBackground(BG_PANEL);
        for (String rule : rules) {
            JLabel item = new JLabel("<html><font color='#667eea'>✓</font>&nbsp;" + rule + "</html>");
            item.setFont(new Font("Segoe UI", Font.PLAIN, 13));
            item.setForeground(new Color(0x49, 0x50, 0x57));
            item.setBorder(new EmptyBorder(6, 0, 6, 0));
            list.add(item);
        }

        JScrollPane scroll = new JScrollPane(list,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.setBackground(BG_PANEL);

        panel.add(title,  BorderLayout.NORTH);
        panel.add(Box.createVerticalStrut(16), BorderLayout.CENTER);
        panel.add(scroll, BorderLayout.SOUTH);

        // Fix layout so scroll fills remaining space
        panel.setLayout(new BorderLayout(0, 12));
        panel.add(title,  BorderLayout.NORTH);
        panel.add(scroll, BorderLayout.CENTER);

        return panel;
    }

    // ── Login logic ───────────────────────────────────────────────────────

    private void attemptLogin() {
        String email    = emailField.getText().trim();
        String password = new String(passwordField.getPassword());

        if (email.isEmpty() || password.isEmpty()) {
            showError("Email and password are required.");
            return;
        }

        loginButton.setEnabled(false);
        loginButton.setText("Logging in…");
        statusLabel.setText(" ");

        // Run on background thread to keep UI responsive
        SwingWorker<AuthUser, Void> worker = new SwingWorker<>() {
            @Override protected AuthUser doInBackground() throws Exception {
                return authService.login(email, password);
            }

            @Override protected void done() {
                loginButton.setEnabled(true);
                loginButton.setText("Login");
                try {
                    AuthUser user = get();
                    onLoginSuccess(user);
                } catch (java.util.concurrent.ExecutionException ex) {
                    Throwable cause = ex.getCause();
                    if (cause instanceof AuthException) {
                        showError(cause.getMessage());
                    } else if (cause instanceof IOException) {
                        showError("Cannot reach server. " + cause.getMessage());
                    } else {
                        showError("Unexpected error: " + cause.getMessage());
                    }
                } catch (InterruptedException ex) {
                    Thread.currentThread().interrupt();
                }
            }
        };
        worker.execute();
    }

    private void onLoginSuccess(AuthUser user) {
        dispose();
        MainWindow mainWindow = new MainWindow(user, authService);
        mainWindow.setVisible(true);
    }

    private void showError(String msg) {
        statusLabel.setText("<html><center>" + msg + "</center></html>");
        statusLabel.setForeground(ERROR_C);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private JLabel fieldLabel(String text) {
        JLabel lbl = new JLabel(text);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(new Color(0x33, 0x33, 0x33));
        lbl.setAlignmentX(LEFT_ALIGNMENT);
        return lbl;
    }

    private void styleField(JTextField field) {
        field.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        field.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 2),
            new EmptyBorder(8, 10, 8, 10)
        ));
        field.setMaximumSize(new Dimension(Integer.MAX_VALUE, 42));
        field.setAlignmentX(LEFT_ALIGNMENT);
    }

    private JButton linkButton(String text) {
        JButton btn = new JButton("<html><u>" + text + "</u></html>");
        btn.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        btn.setForeground(PRIMARY);
        btn.setBorderPainted(false);
        btn.setContentAreaFilled(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        btn.setAlignmentX(CENTER_ALIGNMENT);
        return btn;
    }
}
