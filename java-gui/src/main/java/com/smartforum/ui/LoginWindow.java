package com.smartforum.ui;

import com.smartforum.api.ApiClient;
import com.smartforum.auth.AuthException;
import com.smartforum.auth.AuthService;
import com.smartforum.cache.LocalCacheDatabase;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import javax.imageio.ImageIO;
import java.io.IOException;
import java.net.URL;

public class LoginWindow extends JFrame {

    private static final Color PRIMARY   = new Color(0x66, 0x7E, 0xEA);
    private static final Color SECONDARY = new Color(0x76, 0x4B, 0xA2);
    private static final Color TEXT_MUTE = new Color(0x6C, 0x75, 0x7D);
    private static final Color BORDER_C  = new Color(0xE1, 0xE4, 0xE8);

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
<<<<<<< HEAD
        setTitle("Discussion Hub-Login");
=======
        setTitle("Discussion Hub — Login");
>>>>>>> 78dbd56889ed3049ff9bbcfcc353133ff82eacd0
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setResizable(true);

        new SwingWorker<Image, Void>() {
            @Override protected Image doInBackground() throws Exception {
                return new ImageIcon(new URL(
                    ApiClient.BASE_URL.replace("/api", "") + "/images/forum-favicon.png")).getImage();
            }
            @Override protected void done() {
                try { setIconImage(get()); } catch (Exception ignored) {}
            }
        }.execute();

        // Full gradient background — matches Laravel body gradient 135deg #667eea -> #764ba2
        JPanel root = new JPanel(new GridBagLayout()) {
            @Override protected void paintComponent(Graphics g) {
                super.paintComponent(g);
                Graphics2D g2 = (Graphics2D) g;
                g2.setPaint(new GradientPaint(0, 0, PRIMARY, getWidth(), getHeight(), SECONDARY));
                g2.fillRect(0, 0, getWidth(), getHeight());
            }
        };
        root.setBorder(new EmptyBorder(20, 20, 20, 20));

        root.add(buildCard());
        setContentPane(root);
        setSize(520, 720);
        setMinimumSize(new Dimension(480, 680));
        setLocationRelativeTo(null);
    }

    private JPanel buildCard() {
        // Single card panel, GridBagLayout, padding 50px 40px — matches .login-card
        JPanel card = new JPanel(new GridBagLayout());
        card.setBackground(Color.WHITE);
        card.setPreferredSize(new Dimension(420, 660));
        card.setBorder(new EmptyBorder(50, 40, 40, 40));

        GridBagConstraints gbc = new GridBagConstraints();
        gbc.gridx   = 0;
        gbc.fill    = GridBagConstraints.HORIZONTAL;
        gbc.weightx = 1.0;

        // ── Logo — matches .logo img (64x64, centered) ────────────────────
        JLabel logoImg = new JLabel("💬", SwingConstants.CENTER);
        logoImg.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 48));
        new SwingWorker<ImageIcon, Void>() {
            @Override protected ImageIcon doInBackground() throws Exception {
                URL url = new URL(ApiClient.BASE_URL.replace("/api", "") + "/images/forum.png");
                Image img = ImageIO.read(url).getScaledInstance(64, 64, Image.SCALE_SMOOTH);
                return new ImageIcon(img);
            }
            @Override protected void done() {
                try { logoImg.setIcon(get()); logoImg.setText(null); } catch (Exception ignored) {}
            }
        }.execute();

        // ── Title — matches .logo h1 ──────────────────────────────────────
        JLabel title = new JLabel("Discussion Hub", SwingConstants.CENTER);
        title.setFont(new Font("Segoe UI", Font.BOLD, 22));
        title.setForeground(PRIMARY);

        // ── Subtitle — matches .logo p ────────────────────────────────────
        JLabel sub = new JLabel("Welcome back! Please login to your account", SwingConstants.CENTER);
        sub.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        sub.setForeground(TEXT_MUTE);

        // ── Alert — matches .alert-danger ─────────────────────────────────
        statusLabel = new JLabel(" ", SwingConstants.CENTER);
        statusLabel.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        statusLabel.setForeground(new Color(0x72, 0x1C, 0x24));
        statusLabel.setOpaque(true);
        statusLabel.setBackground(new Color(0xF8, 0xD7, 0xDA));
        statusLabel.setBorder(new EmptyBorder(10, 12, 10, 12));
        statusLabel.setVisible(false);

        // ── Email — matches .form-group ───────────────────────────────────
        JLabel emailLbl = fieldLabel("Email Address");
        emailField = new JTextField();
        styleField(emailField);

        // ── Password + eye toggle — matches .password-wrapper ─────────────
        JLabel passLbl = fieldLabel("Password");
        passwordField = new JPasswordField();
        passwordField.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        passwordField.setOpaque(false);
        passwordField.setBorder(new EmptyBorder(10, 12, 10, 8));

        JButton eyeBtn = new JButton("👁");
        eyeBtn.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 13));
        eyeBtn.setForeground(TEXT_MUTE);
        eyeBtn.setBorderPainted(false);
        eyeBtn.setContentAreaFilled(false);
        eyeBtn.setFocusPainted(false);
        eyeBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        eyeBtn.setMargin(new Insets(0, 4, 0, 6));
        eyeBtn.addActionListener(e -> {
            boolean hidden = passwordField.getEchoChar() != 0;
            passwordField.setEchoChar(hidden ? (char) 0 : '\u2022');
            eyeBtn.setForeground(hidden ? PRIMARY : TEXT_MUTE);
        });

        // Wrapper draws the border; field + eye button sit inside it
        JPanel passRow = new JPanel(new BorderLayout());
        passRow.setBackground(Color.WHITE);
        passRow.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 2),
            new EmptyBorder(0, 0, 0, 0)
        ));
        passRow.setPreferredSize(new Dimension(340, 44));
        passRow.add(passwordField, BorderLayout.CENTER);
        passRow.add(eyeBtn, BorderLayout.EAST);

        // ── Remember me — matches .checkbox-group ─────────────────────────
        rememberMe = new JCheckBox("Remember me");
        rememberMe.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        rememberMe.setForeground(TEXT_MUTE);
        rememberMe.setBackground(Color.WHITE);

        // ── Login button full-width — matches .btn-login ──────────────────
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
        loginButton.setPreferredSize(new Dimension(340, 48));
        loginButton.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        loginButton.addActionListener(e -> attemptLogin());
        getRootPane().setDefaultButton(loginButton);

        // ── Forgot password — matches .links a ────────────────────────────
        JButton forgotLink = linkButton("Forgot your password?");
        forgotLink.addActionListener(e ->
            JOptionPane.showMessageDialog(this,
                "Visit: http://localhost:8000/forgot-password",
                "Reset Password", JOptionPane.INFORMATION_MESSAGE));

        // ── Divider — matches .divider ────────────────────────────────────
        JSeparator divider = new JSeparator();
        divider.setForeground(BORDER_C);

        // ── Register row — matches bottom .links ──────────────────────────
        JButton registerLink = linkButton("Register here");
        registerLink.addActionListener(e ->
            JOptionPane.showMessageDialog(this,
                "Visit: http://localhost:8000/register",
                "Register", JOptionPane.INFORMATION_MESSAGE));
        JPanel registerRow = new JPanel(new FlowLayout(FlowLayout.CENTER, 4, 0));
        registerRow.setBackground(Color.WHITE);
        JLabel noAccount = new JLabel("Don't have an account?");
        noAccount.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        noAccount.setForeground(TEXT_MUTE);
        registerRow.add(noAccount);
        registerRow.add(registerLink);

        // ── Assemble — insets mirror Laravel margin-bottom values ─────────
        int row = 0;
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 14, 0); card.add(logoImg, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 6, 0);  card.add(title, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 35, 0); card.add(sub, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 20, 0); card.add(statusLabel, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 8, 0);  card.add(emailLbl, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 20, 0); card.add(emailField, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 8, 0);  card.add(passLbl, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 20, 0); card.add(passRow, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 20, 0); card.add(rememberMe, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 15, 0); card.add(loginButton, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 20, 0); card.add(forgotLink, gbc);
        gbc.gridy = row++; gbc.insets = new Insets(0, 0, 20, 0); card.add(divider, gbc);
        gbc.gridy = row++;  gbc.insets = new Insets(0, 0, 0, 0); card.add(registerRow, gbc);

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
        return lbl;
    }

    private void styleField(JTextField field) {
        field.setFont(new Font("Segoe UI", Font.PLAIN, 14));
        field.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createLineBorder(BORDER_C, 2),
            new EmptyBorder(10, 12, 10, 12)
        ));
        field.setPreferredSize(new Dimension(340, 44));
    }

    private JButton linkButton(String text) {
        JButton btn = new JButton("<html><u>" + text + "</u></html>");
        btn.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        btn.setForeground(PRIMARY);
        btn.setBorderPainted(false);
        btn.setContentAreaFilled(false);
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return btn;
    }
}
