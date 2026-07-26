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
import java.net.URL;

public class LoginWindow extends JFrame {

    private static final Color PRIMARY   = new Color(0x66, 0x7E, 0xEA);
    private static final Color SECONDARY = new Color(0x76, 0x4B, 0xA2);
    private static final Color BORDER_C  = new Color(0xE1, 0xE4, 0xE8);
    private static final Color TEXT_MUTE = new Color(0x6C, 0x75, 0x7D);

    private final AuthService        authService;
    private final ApiClient          api;
    private final LocalCacheDatabase cache;

    private JTextField     emailField;
    private JPasswordField passwordField;
    private JCheckBox      rememberMe;
    private JButton        loginButton;
    private JLabel         statusLabel;

    public LoginWindow(AuthService authService, ApiClient api, LocalCacheDatabase cache) {
        this.authService = authService;
        this.api         = api;
        this.cache       = cache;
        buildUI();
    }

    private void buildUI() {
        setTitle("Discussion Hub — Login");
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setResizable(false);

        // favicon
        new SwingWorker<Image, Void>() {
            @Override protected Image doInBackground() throws Exception {
                return new ImageIcon(new URL(
                    ApiClient.BASE_URL.replace("/api", "") + "/images/forum-favicon.png")).getImage();
            }
            @Override protected void done() {
                try { setIconImage(get()); } catch (Exception ignored) {}
            }
        }.execute();

        // gradient background
        JPanel root = new JPanel(new GridBagLayout()) {
            @Override protected void paintComponent(Graphics g) {
                super.paintComponent(g);
                Graphics2D g2 = (Graphics2D) g;
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), SECONDARY));
                g2.fillRect(0, 0, getWidth(), getHeight());
            }
        };
        root.setBorder(new EmptyBorder(30, 30, 30, 30));

        root.add(buildCard());
        setContentPane(root);
        setSize(500, 680);
        setLocationRelativeTo(null);
    }

    private JPanel buildCard() {
        JPanel card = new JPanel();
        card.setLayout(new BoxLayout(card, BoxLayout.Y_AXIS));
        card.setBackground(Color.WHITE);
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 1),
            new EmptyBorder(40, 40, 36, 40)
        ));

<<<<<<< Updated upstream
        JLabel logo = new JLabel("🎓 Discussion Hub", SwingConstants.CENTER);
        logo.setFont(new Font("Segoe UI", Font.BOLD, 22));
        logo.setForeground(PRIMARY);
        logo.setAlignmentX(CENTER_ALIGNMENT);

        JLabel sub = new JLabel("Welcome back! Please login to your account.", SwingConstants.CENTER);
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 13));
=======
        // ── Logo ──────────────────────────────────────────────────────────
        JLabel logoImg = new JLabel("💬", SwingConstants.CENTER);
        logoImg.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 48));
        logoImg.setAlignmentX(CENTER_ALIGNMENT);
        new SwingWorker<ImageIcon, Void>() {
            @Override protected ImageIcon doInBackground() throws Exception {
                URL url = new URL(ApiClient.BASE_URL.replace("/api", "") + "/images/forum.png");
                Image img = new ImageIcon(url).getImage().getScaledInstance(64, 64, Image.SCALE_SMOOTH);
                return new ImageIcon(img);
            }
            @Override protected void done() {
                try { logoImg.setIcon(get()); logoImg.setText(null); } catch (Exception ignored) {}
            }
        }.execute();

        JLabel title = new JLabel("Discussion Hub", SwingConstants.CENTER);
        title.setFont(new Font("Segoe UI", Font.BOLD, 22));
        title.setForeground(PRIMARY);
        title.setAlignmentX(CENTER_ALIGNMENT);

        JLabel sub = new JLabel("Welcome back! Please login to your account", SwingConstants.CENTER);
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 14));
>>>>>>> Stashed changes
        sub.setForeground(TEXT_MUTE);
        sub.setAlignmentX(CENTER_ALIGNMENT);

        // ── Alert box (hidden until error) ────────────────────────────────
        statusLabel = new JLabel(" ", SwingConstants.CENTER);
        statusLabel.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        statusLabel.setForeground(new Color(0x72, 0x1C, 0x24));
        statusLabel.setOpaque(true);
        statusLabel.setBackground(new Color(0xF8, 0xD7, 0xDA));
        statusLabel.setBorder(new EmptyBorder(10, 12, 10, 12));
        statusLabel.setAlignmentX(CENTER_ALIGNMENT);
        statusLabel.setMaximumSize(new Dimension(Integer.MAX_VALUE, 60));
        statusLabel.setVisible(false);

        // ── Email ─────────────────────────────────────────────────────────
        JLabel emailLbl = fieldLabel("Email Address");
        emailField = new JTextField();
        styleField(emailField);

        // ── Password + show/hide ──────────────────────────────────────────
        JLabel passLbl = fieldLabel("Password");
        passwordField = new JPasswordField();
        styleField(passwordField);

        JButton eyeBtn = new JButton("👁");
        eyeBtn.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 13));
        eyeBtn.setForeground(TEXT_MUTE);
        eyeBtn.setBorderPainted(false);
        eyeBtn.setContentAreaFilled(false);
        eyeBtn.setFocusPainted(false);
        eyeBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        eyeBtn.setMargin(new Insets(0, 4, 0, 4));
        eyeBtn.addActionListener(e -> {
            boolean hidden = passwordField.getEchoChar() != 0;
            passwordField.setEchoChar(hidden ? (char) 0 : '\u2022');
            eyeBtn.setForeground(hidden ? PRIMARY : TEXT_MUTE);
        });

        JPanel passRow = new JPanel(new BorderLayout(4, 0));
        passRow.setBackground(Color.WHITE);
        passRow.setMaximumSize(new Dimension(Integer.MAX_VALUE, 42));
        passRow.setAlignmentX(LEFT_ALIGNMENT);
        passRow.add(passwordField, BorderLayout.CENTER);
        passRow.add(eyeBtn, BorderLayout.EAST);

        // ── Remember me ───────────────────────────────────────────────────
        rememberMe = new JCheckBox("Remember me");
        rememberMe.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        rememberMe.setForeground(TEXT_MUTE);
        rememberMe.setBackground(Color.WHITE);
        rememberMe.setAlignmentX(LEFT_ALIGNMENT);

        // ── Login button (gradient) ───────────────────────────────────────
        loginButton = new JButton("Login") {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), 0, SECONDARY));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 8, 8);
                g2.dispose();
                super.paintComponent(g);
            }
        };
        loginButton.setFont(new Font("Segoe UI", Font.BOLD, 16));
        loginButton.setForeground(Color.WHITE);
        loginButton.setOpaque(false);
        loginButton.setContentAreaFilled(false);
        loginButton.setBorderPainted(false);
        loginButton.setFocusPainted(false);
        loginButton.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        loginButton.setMaximumSize(new Dimension(Integer.MAX_VALUE, 48));
        loginButton.setPreferredSize(new Dimension(340, 48));
        loginButton.setAlignmentX(LEFT_ALIGNMENT);
        loginButton.addActionListener(e -> attemptLogin());
        getRootPane().setDefaultButton(loginButton);

        // ── Forgot password ───────────────────────────────────────────────
        JButton forgotLink = linkButton("Forgot your password?");
        forgotLink.addActionListener(e ->
            JOptionPane.showMessageDialog(this,
                "Visit: http://localhost:8000/forgot-password",
                "Reset Password", JOptionPane.INFORMATION_MESSAGE));

        // ── Divider ───────────────────────────────────────────────────────
        JSeparator divider = new JSeparator();
        divider.setForeground(BORDER_C);
        divider.setMaximumSize(new Dimension(Integer.MAX_VALUE, 1));
        divider.setAlignmentX(LEFT_ALIGNMENT);

        // ── Register row ──────────────────────────────────────────────────
        JButton registerLink = linkButton("Register here");
        registerLink.addActionListener(e ->
            JOptionPane.showMessageDialog(this,
                "Visit: http://localhost:8000/register",
                "Register", JOptionPane.INFORMATION_MESSAGE));
        JPanel registerRow = new JPanel(new FlowLayout(FlowLayout.CENTER, 4, 0));
        registerRow.setBackground(Color.WHITE);
        registerRow.setAlignmentX(CENTER_ALIGNMENT);
        JLabel noAccount = new JLabel("Don't have an account?");
        noAccount.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        noAccount.setForeground(TEXT_MUTE);
        registerRow.add(noAccount);
        registerRow.add(registerLink);

        // ── Assemble ──────────────────────────────────────────────────────
        card.add(logoImg);
        card.add(Box.createVerticalStrut(14));
        card.add(title);
        card.add(Box.createVerticalStrut(6));
        card.add(sub);
        card.add(Box.createVerticalStrut(20));
        card.add(statusLabel);
        card.add(Box.createVerticalStrut(4));
        card.add(emailLbl);
        card.add(Box.createVerticalStrut(8));
        card.add(emailField);
        card.add(Box.createVerticalStrut(20));
        card.add(passLbl);
        card.add(Box.createVerticalStrut(8));
        card.add(passRow);
        card.add(Box.createVerticalStrut(20));
        card.add(rememberMe);
        card.add(Box.createVerticalStrut(20));
        card.add(loginButton);
        card.add(Box.createVerticalStrut(15));
        card.add(forgotLink);
        card.add(Box.createVerticalStrut(20));
        card.add(divider);
        card.add(Box.createVerticalStrut(20));
        card.add(registerRow);

        return card;
    }

    private void attemptLogin() {
        String email    = emailField.getText().trim();
        String password = new String(passwordField.getPassword());

        if (email.isEmpty() || password.isEmpty()) {
            showError("Email and password are required.");
            return;
        }

        loginButton.setEnabled(false);
        loginButton.setText("Logging in…");
        statusLabel.setVisible(false);

        new SwingWorker<AuthUser, Void>() {
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
                    if (cause instanceof AuthException)       showError(cause.getMessage());
                    else if (cause instanceof IOException)    showError("Cannot reach server. " + cause.getMessage());
                    else                                      showError("Unexpected error: " + cause.getMessage());
                } catch (InterruptedException ex) {
                    Thread.currentThread().interrupt();
                }
            }
        }.execute();
    }

    private void onLoginSuccess(AuthUser user) {
        dispose();
        new MainWindow(user, authService, api, cache).setVisible(true);
    }

    private void showError(String msg) {
        statusLabel.setText("<html><center>" + msg + "</center></html>");
        statusLabel.setVisible(true);
        pack();
    }

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
            new EmptyBorder(10, 12, 10, 12)
        ));
        field.setMaximumSize(new Dimension(Integer.MAX_VALUE, 42));
        field.setPreferredSize(new Dimension(340, 42));
        field.setAlignmentX(LEFT_ALIGNMENT);
    }

    private JButton linkButton(String text) {
        JButton btn = new JButton("<html><u>" + text + "</u></html>");
        btn.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        btn.setForeground(PRIMARY);
        btn.setBorderPainted(false);
        btn.setContentAreaFilled(false);
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        btn.setAlignmentX(CENTER_ALIGNMENT);
        return btn;
    }
}
