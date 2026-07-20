package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AuthUser;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.util.HashMap;
import java.util.Map;

public class ProfilePanel extends JPanel {

    private static final Color PRIMARY  = new Color(0x4F, 0x46, 0xE5);
    private static final Color BG       = new Color(0xF1, 0xF5, 0xF9);
    private static final Color SURFACE  = Color.WHITE;
    private static final Color MUTED    = new Color(0x64, 0x74, 0x8B);
    private static final Color TEXT     = new Color(0x0F, 0x17, 0x2A);
    private static final Color DANGER   = new Color(0xEF, 0x44, 0x44);
    private static final Color BORDER_C = new Color(0xE2, 0xE8, 0xF0);

    private final ApiClient api;
    private final AuthUser  user;
    private final ObjectMapper mapper = new ObjectMapper();

    private JTextField  tfName, tfBio;
    private JPasswordField pfCurrent, pfNew, pfConfirm;
    private JLabel statusLbl;

    public ProfilePanel(ApiClient api, AuthUser user) {
        this.api  = api;
        this.user = user;
        setBackground(BG);
        setLayout(new BorderLayout());
        buildUI();
        loadProfile();
    }

    private void buildUI() {
        JPanel body = new JPanel();
        body.setLayout(new BoxLayout(body, BoxLayout.Y_AXIS));
        body.setBackground(BG);
        body.setBorder(new EmptyBorder(24, 24, 40, 24));

        JLabel title = new JLabel("My Profile");
        title.setFont(new Font("Segoe UI", Font.BOLD, 22));
        title.setForeground(TEXT);
        title.setAlignmentX(LEFT_ALIGNMENT);

        statusLbl = new JLabel(" ");
        statusLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        statusLbl.setForeground(MUTED);
        statusLbl.setAlignmentX(LEFT_ALIGNMENT);

        body.add(title);
        body.add(Box.createVerticalStrut(4));
        body.add(statusLbl);
        body.add(Box.createVerticalStrut(20));
        body.add(buildInfoCard());
        body.add(Box.createVerticalStrut(16));
        body.add(buildPasswordCard());

        JScrollPane scroll = new JScrollPane(body,
            JScrollPane.VERTICAL_SCROLLBAR_AS_NEEDED,
            JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        scroll.setBorder(null);
        scroll.getViewport().setBackground(BG);
        add(scroll, BorderLayout.CENTER);
    }

    private JPanel buildInfoCard() {
        JPanel card = card("👤 Profile Information");
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(16, 16, 16, 16));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        // Read-only fields
        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0;
        form.add(label("Email"), gc);
        gc.gridx = 1; gc.weightx = 1;
        JLabel emailLbl = new JLabel(user.getEmail());
        emailLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        emailLbl.setForeground(MUTED);
        form.add(emailLbl, gc);

        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0;
        form.add(label("Role"), gc);
        gc.gridx = 1; gc.weightx = 1;
        JLabel roleLbl = new JLabel(user.getRole().toUpperCase());
        roleLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        roleLbl.setForeground(PRIMARY);
        form.add(roleLbl, gc);

        gc.gridx = 0; gc.gridy = 2; gc.weightx = 0;
        form.add(label("Name"), gc);
        gc.gridx = 1; gc.weightx = 1;
        tfName = new JTextField();
        tfName.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        form.add(tfName, gc);

        gc.gridx = 0; gc.gridy = 3; gc.weightx = 0;
        form.add(label("Bio"), gc);
        gc.gridx = 1; gc.weightx = 1;
        tfBio = new JTextField();
        tfBio.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        form.add(tfBio, gc);

        gc.gridx = 1; gc.gridy = 4; gc.weightx = 1;
        JButton saveBtn = primaryButton("Save Changes");
        saveBtn.addActionListener(e -> saveProfile());
        form.add(saveBtn, gc);

        card.add(form, BorderLayout.CENTER);
        return card;
    }

    private JPanel buildPasswordCard() {
        JPanel card = card("🔒 Change Password");
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(16, 16, 16, 16));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        pfCurrent = new JPasswordField();
        pfNew     = new JPasswordField();
        pfConfirm = new JPasswordField();

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(label("Current Password"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(pfCurrent, gc);
        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0; form.add(label("New Password"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(pfNew, gc);
        gc.gridx = 0; gc.gridy = 2; gc.weightx = 0; form.add(label("Confirm Password"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(pfConfirm, gc);

        gc.gridx = 1; gc.gridy = 3;
        JButton changeBtn = primaryButton("Change Password");
        changeBtn.addActionListener(e -> changePassword());
        form.add(changeBtn, gc);

        card.add(form, BorderLayout.CENTER);
        return card;
    }

    private void loadProfile() {
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/profile"));
            }
            @Override protected void done() {
                try {
                    JsonNode u = get().path("user");
                    tfName.setText(u.path("name").asText(user.getName()));
                    tfBio.setText(u.path("bio").asText(""));
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    private void saveProfile() {
        String name = tfName.getText().trim();
        if (name.isEmpty()) { showStatus("Name cannot be empty.", DANGER); return; }
        Map<String, Object> body = new HashMap<>();
        body.put("name", name);
        body.put("bio", tfBio.getText().trim());
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.put("/profile", body);
                return null;
            }
            @Override protected void done() {
                try {
                    get();
                    showStatus("Profile updated successfully.", new Color(0x10, 0xB9, 0x81));
                } catch (Exception e) {
                    showStatus("Failed to update profile.", DANGER);
                }
            }
        }.execute();
    }

    private void changePassword() {
        String current = new String(pfCurrent.getPassword());
        String newPw   = new String(pfNew.getPassword());
        String confirm = new String(pfConfirm.getPassword());
        if (newPw.isEmpty()) { showStatus("New password cannot be empty.", DANGER); return; }
        if (!newPw.equals(confirm)) { showStatus("Passwords do not match.", DANGER); return; }
        Map<String, Object> body = new HashMap<>();
        body.put("current_password", current);
        body.put("password", newPw);
        body.put("password_confirmation", confirm);
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.put("/profile", body);
                return null;
            }
            @Override protected void done() {
                try {
                    get();
                    pfCurrent.setText(""); pfNew.setText(""); pfConfirm.setText("");
                    showStatus("Password changed successfully.", new Color(0x10, 0xB9, 0x81));
                } catch (Exception e) {
                    showStatus("Failed to change password.", DANGER);
                }
            }
        }.execute();
    }

    private void showStatus(String msg, Color color) {
        statusLbl.setText(msg);
        statusLbl.setForeground(color);
    }

    private JPanel card(String title) {
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setAlignmentX(LEFT_ALIGNMENT);
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, 400));
        card.setBorder(BorderFactory.createCompoundBorder(
            BorderFactory.createMatteBorder(3, 0, 0, 0, PRIMARY),
            BorderFactory.createLineBorder(BORDER_C)));
        JPanel header = new JPanel(new BorderLayout());
        header.setBackground(PRIMARY);
        header.setBorder(new EmptyBorder(10, 14, 10, 14));
        JLabel lbl = new JLabel(title);
        lbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        lbl.setForeground(Color.WHITE);
        header.add(lbl, BorderLayout.WEST);
        card.add(header, BorderLayout.NORTH);
        return card;
    }

    private JLabel label(String text) {
        JLabel l = new JLabel(text);
        l.setFont(new Font("Segoe UI", Font.BOLD, 12));
        l.setForeground(MUTED);
        return l;
    }

    private JButton primaryButton(String text) {
        JButton btn = new JButton(text);
        btn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        btn.setForeground(Color.WHITE);
        btn.setBackground(PRIMARY);
        btn.setBorderPainted(false);
        btn.setFocusPainted(false);
        btn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return btn;
    }
}
