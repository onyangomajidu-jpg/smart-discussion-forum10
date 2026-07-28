package com.smartforum.ui;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.smartforum.api.ApiClient;
import com.smartforum.model.AdminProfile;
import com.smartforum.model.AuthUser;
import com.smartforum.model.LecturerProfile;
import com.smartforum.model.MemberProfile;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.util.HashMap;
import java.util.Map;

public class ProfilePanel extends JPanel {

    // Values now come from Theme (single source of truth shared with every
    // other panel) instead of being re-declared per-file.
    private static final Color PRIMARY  = Theme.PRIMARY_DARK;
    private static final Color BG       = Theme.BG;
    private static final Color SURFACE  = Theme.SURFACE;
    private static final Color MUTED    = Theme.MUTED;
    private static final Color TEXT     = Theme.TEXT;
    private static final Color DANGER   = Theme.DANGER;
    private static final Color BORDER_C = Theme.BORDER;

    private final ApiClient api;
    private final AuthUser  user;
    private final ObjectMapper mapper = new ObjectMapper();

    private Image avatarImage; // current photo, painted clipped to circle

    /** Fixed-size circular avatar panel — mirrors .profile-avatar { border-radius:50%; object-fit:cover } */
    private JPanel buildAvatarPanel() {
        return new JPanel() {
            { setPreferredSize(new Dimension(88, 88));
              setMinimumSize(new Dimension(88, 88));
              setMaximumSize(new Dimension(88, 88));
              setOpaque(false); }
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                int w = getWidth(), h = getHeight();
                // shadow
                g2.setColor(new Color(99, 102, 241, 50));
                g2.fillOval(3, 5, w - 2, h - 2);
                // white border ring
                g2.setColor(Color.WHITE);
                g2.fillOval(0, 0, w, h);
                // clip to inner circle (inset 4px = border width)
                java.awt.geom.Ellipse2D clip = new java.awt.geom.Ellipse2D.Float(4, 4, w - 8, h - 8);
                g2.setClip(clip);
                if (avatarImage != null) {
                    // cover: scale to fill then center-crop
                    int iw = avatarImage.getWidth(null), ih = avatarImage.getHeight(null);
                    if (iw > 0 && ih > 0) {
                        int inner = w - 8;
                        double scale = Math.max((double) inner / iw, (double) inner / ih);
                        int sw = (int)(iw * scale), sh = (int)(ih * scale);
                        int ox = 4 + (inner - sw) / 2, oy = 4 + (inner - sh) / 2;
                        g2.drawImage(avatarImage, ox, oy, sw, sh, null);
                    }
                } else {
                    // gradient fill + initial letter
                    g2.setClip(null);
                    g2.setPaint(new GradientPaint(4, 4, PRIMARY, w - 4, h - 4, new Color(0x8B, 0x5C, 0xF6)));
                    g2.fillOval(4, 4, w - 8, h - 8);
                    g2.setColor(Color.WHITE);
                    g2.setFont(new Font("Segoe UI", Font.BOLD, 28));
                    FontMetrics fm = g2.getFontMetrics();
                    String init = user.getName().isEmpty() ? "?" : String.valueOf(user.getName().charAt(0)).toUpperCase();
                    g2.drawString(init, 4 + (w - 8 - fm.stringWidth(init)) / 2, 4 + (h - 8 - fm.getHeight()) / 2 + fm.getAscent());
                }
                g2.dispose();
            }
        };
    }
    private JTextField  tfName;
    private JTextArea   taBio;
    private JPanel      avatarPanel;
    private JLabel      avatarHintLbl;
    private JPasswordField pfCurrent, pfNew, pfConfirm;
    private JLabel statusLbl;

    // Member fields
    private JTextField tfStudentId, tfProgramme, tfYearOfStudy;
    // Lecturer fields
    private JTextField tfStaffId, tfDepartment, tfSpecialisation;

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
        if (user.isMember())   body.add(buildMemberCard());
        if (user.isLecturer()) body.add(buildLecturerCard());
        if (user.isAdmin())    body.add(buildAdminCard());
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
        // Build without card() helper so the gradient banner occupies NORTH
        // and the form body occupies CENTER — mirrors .profile-card structure.
        JPanel card = new JPanel(new BorderLayout());
        card.setBackground(SURFACE);
        card.setAlignmentX(LEFT_ALIGNMENT);
        card.setBorder(BorderFactory.createLineBorder(BORDER_C));

        // Banner strip — mirrors .profile-card-banner { height:72px; background:var(--grad) }
        JPanel banner = new JPanel() {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, new Color(0x66, 0x7E, 0xEA), getWidth(), getHeight(), new Color(0x76, 0x4B, 0xA2)));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
            }
        };
        banner.setPreferredSize(new Dimension(0, 72));
        banner.setOpaque(false);
        card.add(banner, BorderLayout.NORTH);

        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(16, 16, 16, 16));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        // Avatar — fixed-size circle panel, image clipped with object-fit:cover
        avatarPanel = buildAvatarPanel();
        if (user.getAvatar() != null && !user.getAvatar().isEmpty()) {
            new SwingWorker<Image, Void>() {
                @Override protected Image doInBackground() throws Exception {
                    String av = user.getAvatar();
                    String rawUrl = av.startsWith("http") ? av
                        : com.smartforum.api.ApiClient.BASE_URL.replace("/api", "") + "/storage/" + av;
                    java.net.URL url = new java.net.URL(rawUrl);
                    return new ImageIcon(url).getImage();
                }
                @Override protected void done() {
                    try { avatarImage = get(); avatarPanel.repaint(); } catch (Exception ignored) {}
                }
            }.execute();
        }

        gc.gridx = 0; gc.gridy = 0; gc.gridwidth = 2; gc.anchor = GridBagConstraints.CENTER;
        gc.fill = GridBagConstraints.NONE; // prevent horizontal stretch
        form.add(avatarPanel, gc);
        gc.fill = GridBagConstraints.HORIZONTAL;
        gc.gridwidth = 1; gc.anchor = GridBagConstraints.WEST;

        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0;
        form.add(label("Email"), gc);
        gc.gridx = 1; gc.weightx = 1;
        JTextField tfEmail = new JTextField(user.getEmail());
        tfEmail.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        form.add(tfEmail, gc);

        gc.gridx = 0; gc.gridy = 2; gc.weightx = 0;
        form.add(label("Role"), gc);
        gc.gridx = 1; gc.weightx = 1;
        String roleIcon = user.isAdmin() ? "🛡 Admin" : user.isLecturer() ? "📋 Lecturer" : "🎓 Student";
        JLabel roleLbl = new JLabel(roleIcon);
        roleLbl.setFont(new Font("Segoe UI Emoji", Font.BOLD, 13));
        roleLbl.setForeground(PRIMARY);
        form.add(roleLbl, gc);

        // Joined date — mirrors .profile-meta-row Joined in profile/edit.blade.php
        gc.gridx = 0; gc.gridy = 3; gc.weightx = 0;
        form.add(label("Joined"), gc);
        gc.gridx = 1; gc.weightx = 1;
        JLabel joinedLbl = new JLabel("—");
        joinedLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        joinedLbl.setForeground(MUTED);
        form.add(joinedLbl, gc);

        gc.gridx = 0; gc.gridy = 4; gc.weightx = 0;
        form.add(label("Name"), gc);
        gc.gridx = 1; gc.weightx = 1;
        tfName = new JTextField();
        tfName.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        form.add(tfName, gc);

        // "Member for" row — mirrors .profile-meta-row Member for in profile/edit.blade.php
        gc.gridx = 0; gc.gridy = 5; gc.weightx = 0;
        form.add(label("Member for"), gc);
        gc.gridx = 1; gc.weightx = 1;
        JLabel memberForLbl = new JLabel("—");
        memberForLbl.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        memberForLbl.setForeground(MUTED);
        form.add(memberForLbl, gc);

        gc.gridx = 0; gc.gridy = 6; gc.weightx = 0; gc.anchor = GridBagConstraints.NORTHWEST;
        form.add(label("Bio"), gc);
        gc.gridx = 1; gc.weightx = 1; gc.anchor = GridBagConstraints.WEST;
        taBio = new JTextArea(3, 20);
        taBio.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        taBio.setLineWrap(true);
        taBio.setWrapStyleWord(true);
        taBio.setBorder(BorderFactory.createLineBorder(BORDER_C));
        form.add(new JScrollPane(taBio), gc);

        // Avatar upload — mirrors "Change Photo" btn + instant preview in profile/edit.blade.php
        gc.gridx = 0; gc.gridy = 7; gc.weightx = 0; gc.anchor = GridBagConstraints.WEST;
        form.add(label("Avatar"), gc);
        gc.gridx = 1; gc.weightx = 1;
        JButton avatarBtn = new JButton("📷 Change Photo");
        avatarBtn.setFont(new Font("Segoe UI Emoji", Font.PLAIN, 12));
        avatarBtn.setForeground(PRIMARY);
        avatarBtn.setBackground(new Color(0xEE, 0xF2, 0xFF));
        avatarBtn.setBorderPainted(false);
        avatarBtn.setFocusPainted(false);
        avatarBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        avatarBtn.addActionListener(e -> {
            javax.swing.JFileChooser fc = new javax.swing.JFileChooser();
            fc.setFileFilter(new javax.swing.filechooser.FileNameExtensionFilter(
                "Images", "jpg", "jpeg", "png", "gif", "webp"));
            if (fc.showOpenDialog(this) == javax.swing.JFileChooser.APPROVE_OPTION) {
                java.io.File f = fc.getSelectedFile();
                // Instant preview — mirrors the FileReader onload in edit.blade.php
                try {
                    java.awt.image.BufferedImage bi = javax.imageio.ImageIO.read(f);
                    if (bi != null) { avatarImage = bi; avatarPanel.repaint(); }
                } catch (Exception ignored) {}
                // Hint text — mirrors avatarHint ✓ filename — click Save Changes to apply
                avatarHintLbl.setText("✓ " + f.getName() + " — click Save Changes to apply");
                avatarHintLbl.setForeground(new Color(0x10, 0xB9, 0x81));
                // Upload in background
                new SwingWorker<Void, Void>() {
                    @Override protected Void doInBackground() throws Exception {
                        api.uploadAvatar(f, user.getName());
                        return null;
                    }
                    @Override protected void done() {
                        try { get(); showStatus("Avatar updated.", new Color(0x10, 0xB9, 0x81)); }
                        catch (Exception ex) { showStatus("Avatar upload failed: " + ex.getMessage(), DANGER); }
                    }
                }.execute();
            }
        });
        form.add(avatarBtn, gc);

        // Hint label — mirrors #avatarHint in edit.blade.php
        gc.gridx = 1; gc.gridy = 8; gc.weightx = 1;
        avatarHintLbl = new JLabel(" ");
        avatarHintLbl.setFont(new Font("Segoe UI", Font.PLAIN, 11));
        avatarHintLbl.setForeground(MUTED);
        form.add(avatarHintLbl, gc);

        gc.gridx = 1; gc.gridy = 9; gc.weightx = 1;
        JButton saveBtn = primaryButton("Save Changes");
        saveBtn.addActionListener(e -> saveProfile(tfEmail));
        form.add(saveBtn, gc);

        // Populate joined + member-for from API
        new SwingWorker<JsonNode, Void>() {
            @Override protected JsonNode doInBackground() throws Exception {
                return mapper.readTree(api.get("/profile"));
            }
            @Override protected void done() {
                try {
                    String created = get().path("user").path("created_at").asText("");
                    if (!created.isEmpty()) {
                        // Format as "d M Y" — mirrors $user->created_at->format('d M Y')
                        try {
                            java.time.LocalDate d = java.time.LocalDate.parse(created.substring(0, 10));
                            joinedLbl.setText(d.format(java.time.format.DateTimeFormatter.ofPattern("d MMM yyyy")));
                            // "Member for" — mirrors $user->created_at->diffForHumans()
                            long days = java.time.temporal.ChronoUnit.DAYS.between(d, java.time.LocalDate.now());
                            String ago = days < 30 ? days + " day" + (days == 1 ? "" : "s")
                                : days < 365 ? (days / 30) + " month" + (days / 30 == 1 ? "" : "s")
                                : (days / 365) + " year" + (days / 365 == 1 ? "" : "s");
                            memberForLbl.setText(ago + " ago");
                        } catch (Exception ignored) { joinedLbl.setText(created.substring(0, 10)); }
                    }
                } catch (Exception ignored) {}
            }
        }.execute();

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

        // Hint — mirrors "leave blank to keep current" in profile/edit.blade.php
        gc.gridx = 0; gc.gridy = 0; gc.gridwidth = 2; gc.weightx = 1;
        JLabel hint = new JLabel("— leave blank to keep current password");
        hint.setFont(new Font("Segoe UI", Font.ITALIC, 11));
        hint.setForeground(MUTED);
        form.add(hint, gc);
        gc.gridwidth = 1;

        pfCurrent = new JPasswordField();
        pfNew     = new JPasswordField();
        pfConfirm = new JPasswordField();

        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0; form.add(label("Current Password"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(pfCurrent, gc);
        gc.gridx = 0; gc.gridy = 2; gc.weightx = 0; form.add(label("New Password"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(pfNew, gc);
        gc.gridx = 0; gc.gridy = 3; gc.weightx = 0; form.add(label("Confirm Password"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(pfConfirm, gc);

        // Buttons row — Change Password + Cancel (mirrors Save/Cancel row in edit.blade.php)
        gc.gridx = 1; gc.gridy = 4;
        JPanel btnRow = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 0));
        btnRow.setOpaque(false);
        JButton changeBtn = primaryButton("Change Password");
        changeBtn.addActionListener(e -> changePassword());
        JButton cancelBtn = new JButton("Cancel");
        cancelBtn.setFont(new Font("Segoe UI", Font.BOLD, 13));
        cancelBtn.setForeground(new Color(0x47, 0x55, 0x69));
        cancelBtn.setBackground(new Color(0xF1, 0xF5, 0xF9));
        cancelBtn.setBorderPainted(false);
        cancelBtn.setFocusPainted(false);
        cancelBtn.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        cancelBtn.addActionListener(e -> {
            pfCurrent.setText(""); pfNew.setText(""); pfConfirm.setText("");
        });
        btnRow.add(changeBtn);
        btnRow.add(cancelBtn);
        form.add(btnRow, gc);

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
                    taBio.setText(u.path("bio").asText(""));
                    if (user.isMember()) {
                        JsonNode m = u.path("member");
                        tfStudentId.setText(m.path("student_id").asText(""));
                        tfProgramme.setText(m.path("programme").asText(""));
                        tfYearOfStudy.setText(String.valueOf(m.path("year_of_study").asInt(0)));
                    } else if (user.isLecturer()) {
                        JsonNode l = u.path("lecturer");
                        tfStaffId.setText(l.path("staff_id").asText(""));
                        tfDepartment.setText(l.path("department").asText(""));
                        tfSpecialisation.setText(l.path("specialisation").asText(""));
                    }
                } catch (Exception ignored) {}
            }
        }.execute();
    }

    private void saveProfile(JTextField tfEmail) {
        String name = tfName.getText().trim();
        if (name.isEmpty()) { showStatus("Name cannot be empty.", DANGER); return; }
        Map<String, Object> body = new HashMap<>();
        body.put("name", name);
        body.put("email", tfEmail.getText().trim());
        body.put("bio", taBio.getText().trim());
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

    // ── Role-specific cards ───────────────────────────────────────────────

    private JPanel buildMemberCard() {
        JPanel card = card("🎓 Student Profile");
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(16, 16, 16, 16));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        tfStudentId   = new JTextField(); tfStudentId.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfProgramme   = new JTextField(); tfProgramme.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfYearOfStudy = new JTextField(); tfYearOfStudy.setFont(new Font("Segoe UI", Font.PLAIN, 13));

        // Reputation — read-only, populated from cached profile
        int rep = user.getMemberProfile() != null ? user.getMemberProfile().reputation : 0;
        JLabel repLbl = new JLabel(String.valueOf(rep));
        repLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        repLbl.setForeground(PRIMARY);

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(label("Student ID"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfStudentId, gc);
        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0; form.add(label("Programme"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfProgramme, gc);
        gc.gridx = 0; gc.gridy = 2; gc.weightx = 0; form.add(label("Year of Study"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfYearOfStudy, gc);
        gc.gridx = 0; gc.gridy = 3; gc.weightx = 0; form.add(label("Reputation"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(repLbl, gc);

        gc.gridx = 1; gc.gridy = 4;
        JButton saveBtn = primaryButton("Save Student Info");
        saveBtn.addActionListener(e -> saveMemberProfile());
        form.add(saveBtn, gc);

        card.add(form, BorderLayout.CENTER);
        return card;
    }

    private JPanel buildLecturerCard() {
        JPanel card = card("🏫 Lecturer Profile");
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(16, 16, 16, 16));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        tfStaffId        = new JTextField(); tfStaffId.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfDepartment     = new JTextField(); tfDepartment.setFont(new Font("Segoe UI", Font.PLAIN, 13));
        tfSpecialisation = new JTextField(); tfSpecialisation.setFont(new Font("Segoe UI", Font.PLAIN, 13));

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(label("Staff ID"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfStaffId, gc);
        gc.gridx = 0; gc.gridy = 1; gc.weightx = 0; form.add(label("Department"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfDepartment, gc);
        gc.gridx = 0; gc.gridy = 2; gc.weightx = 0; form.add(label("Specialisation"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(tfSpecialisation, gc);

        gc.gridx = 1; gc.gridy = 3;
        JButton saveBtn = primaryButton("Save Lecturer Info");
        saveBtn.addActionListener(e -> saveLecturerProfile());
        form.add(saveBtn, gc);

        card.add(form, BorderLayout.CENTER);
        return card;
    }

    private JPanel buildAdminCard() {
        JPanel card = card("🛡 Admin Profile");
        JPanel form = new JPanel(new GridBagLayout());
        form.setBackground(SURFACE);
        form.setBorder(new EmptyBorder(16, 16, 16, 16));
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 4, 6, 4);
        gc.fill = GridBagConstraints.HORIZONTAL;

        boolean isSuperAdmin = user.getAdminProfile() != null && user.getAdminProfile().superAdmin;
        JLabel superLbl = new JLabel(isSuperAdmin ? "✅ Super Admin" : "Standard Admin");
        superLbl.setFont(new Font("Segoe UI", Font.BOLD, 13));
        superLbl.setForeground(isSuperAdmin ? new Color(0x10, 0xB9, 0x81) : MUTED);

        gc.gridx = 0; gc.gridy = 0; gc.weightx = 0; form.add(label("Admin Level"), gc);
        gc.gridx = 1; gc.weightx = 1; form.add(superLbl, gc);

        card.add(form, BorderLayout.CENTER);
        return card;
    }

    // ── Role-specific save ────────────────────────────────────────────────

    private void saveMemberProfile() {
        Map<String, Object> body = new HashMap<>();
        body.put("name", tfName.getText().trim());
        body.put("bio",  taBio != null ? taBio.getText().trim() : "");
        body.put("student_id",    tfStudentId.getText().trim());
        body.put("programme",     tfProgramme.getText().trim());
        body.put("year_of_study", tfYearOfStudy.getText().trim());
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.put("/profile", body); return null;
            }
            @Override protected void done() {
                try { get(); showStatus("Student info updated.", new Color(0x10, 0xB9, 0x81)); }
                catch (Exception e) { showStatus("Failed to update student info.", DANGER); }
            }
        }.execute();
    }

    private void saveLecturerProfile() {
        Map<String, Object> body = new HashMap<>();
        body.put("name", tfName.getText().trim());
        body.put("bio",  taBio != null ? taBio.getText().trim() : "");
        body.put("staff_id",       tfStaffId.getText().trim());
        body.put("department",     tfDepartment.getText().trim());
        body.put("specialisation", tfSpecialisation.getText().trim());
        new SwingWorker<Void, Void>() {
            @Override protected Void doInBackground() throws Exception {
                api.put("/profile", body); return null;
            }
            @Override protected void done() {
                try { get(); showStatus("Lecturer info updated.", new Color(0x10, 0xB9, 0x81)); }
                catch (Exception e) { showStatus("Failed to update lecturer info.", DANGER); }
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
        card.setMaximumSize(new Dimension(Integer.MAX_VALUE, Integer.MAX_VALUE));
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
