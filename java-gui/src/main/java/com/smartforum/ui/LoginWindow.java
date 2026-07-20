package com.smartforum.ui;

import com.smartforum.api.ApiClient;
import com.smartforum.auth.AuthException;
import com.smartforum.auth.AuthService;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
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

    private static final Color PRIMARY   = new Color(0x66, 0x7E, 0xEA);
    private static final Color SECONDARY = new Color(0x76, 0x4B, 0xA2);
    private static final Color BG_PANEL  = new Color(0xF8, 0xF9, 0xFA);
    private static final Color BORDER_C  = new Color(0xE1, 0xE4, 0xE8);
    private static final Color TEXT_MUTE = new Color(0x6C, 0x75, 0x7D);
    private static final Color ERROR_C   = new Color(0xDC, 0x35, 0x45);

    private final AuthService        authService;
private final ApiClient api;
private final LocalCacheDatabase cache;

    // Form fields
    private JTextField     emailField;
    private JPasswordField passwordField;
    private JCheckBox      rememberMe;
    private JButton        loginButton;
    private JLabel         statusLabel;

public LoginWindow(AuthService authService, ApiClient api, LocalCacheDatabase cache) {
    this.authService = authService;
    this.api = api;
    this.cache = cache;
    buildUI();
}

    // ── UI construction ───────────────────────────────────────────────────

    private void buildUI() {
        setTitle("Smart Discussion Forum — Login");
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setResizable(false);

        JPanel root = new JPanel(new GridBagLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g;
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), SECONDARY));
                g2.fillRect(0, 0, getWidth(), getHeight());
            }
        };

        JPanel card = buildFormPanel();
        card.setPreferredSize(new Dimension(420, 520));
        root.add(card);

        setContentPane(root);
        setSize(520, 620);
        setLocationRelativeTo(null);
    }

    // ── Left: login form ──────────────────────────────────────────────────

    private JPanel buildFormPanel() {
        JPanel panel = new JPanel();
        panel.setLayout(new BoxLayout(panel, BoxLayout.Y_AXIS));
        panel.setBackground(Color.WHITE);
        panel.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C),
            new EmptyBorder(50, 40, 40, 40)
        ));
        panel.putClientProperty("arc", 15);

        JLabel logo = new JLabel("🎓 Smart Discussion Forum", SwingConstants.CENTER);
        logo.setFont(new Font("Segoe UI", Font.BOLD, 22));
        logo.setForeground(PRIMARY);
        logo.setAlignmentX(CENTER_ALIGNMENT);

        JLabel sub = new JLabel("Welcome back! Please login to your account", SwingConstants.CENTER);
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        sub.setForeground(TEXT_MUTE);
        sub.setAlignmentX(CENTER_ALIGNMENT);

        statusLabel = new JLabel(" ", SwingConstants.CENTER);
        statusLabel.setFont(new Font("Segoe UI", Font.PLAIN, 12));
        statusLabel.setForeground(ERROR_C);
        statusLabel.setAlignmentX(CENTER_ALIGNMENT);

        JLabel emailLbl = fieldLabel("Email Address");
        emailField = new JTextField(20);
        styleField(emailField);

        JLabel passLbl = fieldLabel("Password");
        passwordField = new JPasswordField(20);
        styleField(passwordField);

        rememberMe = new JCheckBox("Remember me");
        rememberMe.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        rememberMe.setForeground(TEXT_MUTE);
        rememberMe.setBackground(Color.WHITE);
        rememberMe.setAlignmentX(LEFT_ALIGNMENT);

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

        getRootPane().setDefaultButton(loginButton);

        JButton forgotLink = linkButton("Forgot your password?");
        forgotLink.addActionListener(e ->
            JOptionPane.showMessageDialog(this,
                "Visit: http://localhost:8000/forgot-password",
                "Reset Password", JOptionPane.INFORMATION_MESSAGE));

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

        SwingWorker<AuthUser, Void> worker = new SwingWorker<>() {
            @Override protected AuthUser doInBackground() throws Exception {
                return authService.login(email, password);
            }

            @Override protected void done() {
                loginButton.setEnabled(true);
                loginButton.setText("Login");
                try {
                    onLoginSuccess(get());
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
new MainWindow(user, authService, api, cache).setVisible(true);
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
