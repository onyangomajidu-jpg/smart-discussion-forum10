<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/forum-favicon.png') }}">
    <title>Topics - Discussion Hub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; flex-direction: column; height: 100vh; }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 12px 20px; color: white;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15); flex-shrink: 0;
        }
        .navbar h1 { font-size: 20px; }
        .navbar-right { display: flex; align-items: center; gap: 15px; }
        .notif-btn { background: rgba(255,255,255,0.2); border: none; color: white; padding: 6px 12px; border-radius: 6px; cursor: pointer; position: relative; }
        .notif-badge { position: absolute; top: -4px; right: -4px; background: #e53e3e; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: flex; align-items: center; justify-content: center; }
        .btn-logout { background: rgba(255,255,255,0.2); padding: 6px 14px; border: 1px solid white; border-radius: 6px; color: white; cursor: pointer; }

        /* Layout */
        .forum-layout { display: flex; flex: 1; overflow: hidden; }

        /* Sidebar */
        .sidebar {
            width: 300px; background: white; border-right: 1px solid #e2e8f0;
            display: flex; flex-direction: column; flex-shrink: 0;
        }
        .sidebar-header { padding: 18px 16px 14px; border-bottom: 1px solid #e2e8f0; }
        .sidebar-title { font-size: 11px; font-weight: 700; color: #a0aec0; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 12px; }
        .search-bar { width: 100%; padding: 9px 12px 9px 36px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; outline: none; background: #f7fafc; color: #2d3748; }
        .search-bar::placeholder { color: #a0aec0; }
        .search-bar:focus { border-color: #667eea; background: white; }
        .search-wrap { position: relative; margin-bottom: 12px; }
        .search-wrap::before { content: '🔍'; position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; pointer-events: none; }
        .btn-create { width: 100%; padding: 10px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 13px; letter-spacing: 0.3px; transition: opacity 0.2s, transform 0.1s; }
        .btn-create:hover { opacity: 0.9; transform: translateY(-1px); }
        .topic-list { flex: 1; overflow-y: auto; padding: 8px 0; }
        .topic-list::-webkit-scrollbar { width: 4px; }
        .topic-list::-webkit-scrollbar-track { background: transparent; }
        .topic-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
        .topic-item { margin: 4px 10px; border-radius: 12px; padding: 12px 14px; cursor: pointer; transition: background 0.18s; position: relative; }
        .topic-item:hover { background: #f0f0ff; }
        .topic-item.active { background: #ede9fe; box-shadow: inset 0 0 0 1px #c4b5fd; }
        .topic-item-inner { display: flex; gap: 11px; align-items: flex-start; }
        .topic-avatar { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: white; flex-shrink: 0; text-transform: uppercase; }
        .topic-content { flex: 1; min-width: 0; }
        .topic-item h4 { font-size: 13px; font-weight: 600; color: #2d3748; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topic-item.active h4 { color: #4c1d95; }
        .topic-author { font-size: 11px; color: #a0aec0; margin-bottom: 5px; }
        .topic-stats { display: flex; gap: 10px; }
        .topic-stat { display: flex; align-items: center; gap: 3px; font-size: 11px; color: #718096; }
        .topic-delete-btn { position: absolute; top: 10px; right: 10px; opacity: 0; font-size: 11px; color: #e53e3e; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px; padding: 2px 7px; cursor: pointer; transition: opacity 0.15s; }
        .topic-item:hover .topic-delete-btn { opacity: 1; }
        .topics-count { padding: 6px 20px 2px; font-size: 11px; color: #a0aec0; font-weight: 600; }

        /* Participants Panel */
        .participants-panel {
            width: 230px; background: white; border-left: 1px solid #e2e8f0;
            display: flex; flex-direction: column; flex-shrink: 0; overflow-y: auto;
        }
        .participants-panel h4 { padding: 14px 16px; font-size: 13px; font-weight: 700; color: #4a5568; border-bottom: 1px solid #e2e8f0; margin: 0; }
        .section-label { padding: 8px 14px 4px; font-size: 11px; font-weight: 700; color: #a0aec0; text-transform: uppercase; letter-spacing: .5px; }
        .participant-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; border-bottom: 1px solid #f0f2f5; font-size: 13px; color: #2d3748; gap: 6px; }
        .participant-item.blocked-item { background: #fff5f5; color: #9b2c2c; }
        .participant-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .participant-actions { display: flex; gap: 4px; flex-shrink: 0; }
        .btn-remove-user { font-size: 11px; color: #e53e3e; background: none; border: 1px solid #e53e3e; border-radius: 4px; padding: 2px 6px; cursor: pointer; }
        .btn-remove-user:hover { background: #fff5f5; }
        .btn-block-user { font-size: 11px; color: #d69e2e; background: none; border: 1px solid #d69e2e; border-radius: 4px; padding: 2px 6px; cursor: pointer; }
        .btn-block-user:hover { background: #fffff0; }
        .btn-unblock-user { font-size: 11px; color: #38a169; background: none; border: 1px solid #38a169; border-radius: 4px; padding: 2px 6px; cursor: pointer; }
        .btn-unblock-user:hover { background: #f0fff4; }

        /* Mobile toggle buttons — hidden on desktop */
        .mobile-toggle-btn {
            display: none; background: rgba(255,255,255,0.2); border: none; color: white;
            width: 34px; height: 34px; border-radius: 6px; align-items: center; justify-content: center;
            font-size: 15px; cursor: pointer; flex-shrink: 0;
        }
        .panel-backdrop {
            display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45);
            z-index: 499; opacity: 0; transition: opacity .2s;
        }
        .panel-backdrop.show { display: block; opacity: 1; }

        @media (max-width: 768px) {
            .mobile-toggle-btn { display: flex; }

            /* Navbar: prevent overflow */
            .navbar { padding: 8px 10px; gap: 6px; flex-wrap: nowrap; }
            .navbar h1 { font-size: 15px; }
            .navbar h1 span.full-title { display: none; }
            .navbar-right { gap: 6px; }
            .navbar-right > span { display: none; } /* hide username text */
            .btn-logout { padding: 5px 8px; font-size: 12px; }
            .notif-btn { padding: 5px 8px; }

            .sidebar, .participants-panel {
                position: fixed; top: 0; height: 100vh; z-index: 500;
                transition: transform .25s ease;
            }
            .sidebar { left: 0; transform: translateX(-100%); width: 85%; max-width: 300px; }
            .sidebar.open { transform: translateX(0); }
            .participants-panel { right: 0; transform: translateX(100%); width: 80%; max-width: 260px; }
            .participants-panel.open { transform: translateX(0); }

            .conversation { width: 100%; min-width: 0; }
            .conv-header { flex-direction: column; align-items: flex-start; gap: 8px; padding: 12px 14px; }
            .conv-header h2 { font-size: 15px; }
            .conv-header > div:last-child { display: flex; flex-wrap: wrap; gap: 6px; width: 100%; }
            .conv-header > div:last-child a,
            .conv-header > div:last-child button { font-size: 12px; padding: 6px 10px; }

            .messages { padding: 12px; gap: 10px; }
            .input-area { padding: 10px 12px; }
            .msg-input { font-size: 13px; }
            .btn-send { padding: 8px 14px; font-size: 13px; }

            /* Modal full-width on mobile */
            .modal { width: 95vw; padding: 20px 16px; }
        }
        @media (max-width: 480px) {
            .navbar h1 img { height: 26px; }
            .replies { padding-left: 10px; }
        }

        /* Conversation Panel */
        .conversation { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .conv-header {
            padding: 16px 20px; background: white; border-bottom: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .conv-header h2 { font-size: 18px; color: #2d3748; }
        .conv-header-meta { font-size: 13px; color: #718096; }
        .messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }

        /* ── Chat bubble styles (WhatsApp group) ── */
        .chat-row { display: flex; align-items: flex-end; gap: 6px; margin-bottom: 2px; }
        .chat-row.mine { flex-direction: row-reverse; }

        /* Avatar inline with bubble */
        .chat-avatar {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; color: #fff;
            background: linear-gradient(135deg,#667eea,#764ba2);
            overflow: hidden; align-self: flex-end;
        }
        .chat-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .chat-row.mine .chat-avatar { background: linear-gradient(135deg,#25d366,#128c7e); }
        .chat-row.topic-origin .chat-avatar { background: linear-gradient(135deg,#f59e0b,#d97706); }

        .chat-bubble-wrap { display: flex; flex-direction: column; max-width: 68%; }
        .chat-row.mine .chat-bubble-wrap { align-items: flex-end; }

        /* sender name inside bubble */
        .bubble-author {
            font-size: 12.5px; font-weight: 700;
            margin-bottom: 2px; display: block;
            line-height: 1.3;
        }
        .chat-row.mine .bubble-author { display: none; }
        a.bubble-author { text-decoration: none; cursor: pointer; }
        a.bubble-author:hover { text-decoration: underline; }

        /* time stamp inside bubble */
        .bubble-time {
            font-size: 11px; color: #8696a0;
            float: right; margin-left: 10px; margin-top: 4px;
            line-height: 1; white-space: nowrap;
        }
        .chat-row.mine .bubble-time { color: #6a9f7a; }
        .chat-row.topic-origin .bubble-time { color: #a16207; }

        .chat-bubble {
            background: #fff;
            border-radius: 8px 8px 8px 2px;
            padding: 7px 10px 7px 10px;
            font-size: 14px; color: #111b21; line-height: 1.55;
            box-shadow: 0 1px 2px rgba(0,0,0,.13);
            word-break: break-word;
            overflow: hidden;
        }
        .chat-row.mine .chat-bubble {
            background: #d9fdd3;
            color: #111b21;
            border-radius: 8px 8px 2px 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,.13);
        }
        .chat-row.topic-origin .chat-bubble {
            background: #fef9c3;
            color: #78350f;
            border-radius: 8px 8px 8px 2px;
            border: 1px solid #fcd34d;
        }

        .chat-actions { display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap; opacity: 0; transition: opacity .15s; pointer-events: none; clear: both; }
        .chat-row.selected .chat-actions { opacity: 1; pointer-events: auto; }
        .chat-row.mine .chat-actions { justify-content: flex-end; }
        .btn-sm { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 14px; border: 1px solid #e2e8f0; border-radius: 50%; cursor: pointer; background: white; transition: all .15s; padding: 0; }
        .btn-sm:hover { background: #f1f5f9; transform: scale(1.1); }
        .btn-reply { color: #667eea; border-color: #c7d2fe; }
        .btn-edit   { color: #38a169; border-color: #a7f3d0; }
        .btn-delete { color: #e53e3e; border-color: #fecaca; }

        /* ── WhatsApp-style quoted reply preview inside bubble ── */
        .reply-quote {
            border-left: 3px solid #667eea;
            background: rgba(0,0,0,.06);
            border-radius: 6px;
            padding: 5px 9px;
            margin-bottom: 5px;
            font-size: 12px;
            cursor: pointer;
        }
        .reply-quote .rq-author { font-weight: 700; color: #667eea; margin-bottom: 2px; font-size: 11px; }
        .reply-quote .rq-body   { color: #4a5568; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .chat-row.mine .reply-quote { border-color: #6abf8a; background: rgba(0,0,0,.07); }
        .chat-row.mine .reply-quote .rq-author { color: #2d7a4f; }
        .chat-row.mine .reply-quote .rq-body   { color: #374151; }

        /* ── Reply bar above input ── */
        .reply-bar {
            display: none; align-items: center; gap: 8px;
            padding: 8px 14px; background: #e8e6ff; border-radius: 12px; margin-bottom: 6px;
            font-size: 13px; color: #4a5568;
        }
        .reply-bar .rb-author { font-weight: 700; color: #667eea; }
        .reply-bar .rb-body { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .btn-cancel-reply { background: none; border: none; color: #a0aec0; cursor: pointer; font-size: 18px; flex-shrink: 0; line-height: 1; }
        .btn-cancel-reply:hover { color: #e53e3e; }

        /* Typing indicator */
        .typing-indicator { padding: 6px 20px; font-size: 13px; color: #718096; font-style: italic; min-height: 28px; }
        .typing-dots span { display: inline-block; width: 6px; height: 6px; background: #718096; border-radius: 50%; margin: 0 2px; animation: bounce 1.2s infinite; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

        /* ── WhatsApp-style input bar ── */
        .input-area { padding: 10px 14px; background: #f0f2f5; border-top: none; }
        .input-bar {
            display: flex; align-items: flex-end; gap: 8px;
            background: white; border-radius: 26px;
            padding: 6px 6px 6px 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
        .bar-icons-left { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
        .bar-icon {
            width: 38px; height: 38px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            background: none; transition: background .18s; flex-shrink: 0;
            color: #54656f; padding: 0;
        }
        .bar-icon:hover { background: #f0f2f5; }
        .bar-icon svg { width: 24px; height: 24px; display: block; }
        .msg-input {
            flex: 1; border: none; outline: none; resize: none;
            font-family: inherit; font-size: 15px; line-height: 1.4;
            background: transparent; color: #111b21;
            padding: 6px 0; max-height: 120px; overflow-y: auto;
        }
        .msg-input::placeholder { color: #8696a0; }
        .bar-icon-right {
            width: 46px; height: 46px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            background: #00a884; color: white; flex-shrink: 0;
            transition: background .18s, transform .15s;
            box-shadow: 0 2px 8px rgba(0,168,132,.35);
        }
        .bar-icon-right:hover { background: #017a62; transform: scale(1.06); }
        .bar-icon-right.recording { background: #ef4444; animation: micPulse 1s ease-in-out infinite; }
        .bar-icon-right svg { width: 22px; height: 22px; display: block; }
        .attach-preview-bar {
            display: none; align-items: center; gap: 10px; margin-bottom: 8px;
            padding: 8px 12px; background: #f0f2f5; border-radius: 12px;
            font-size: 13px; color: #475569;
        }
        .attach-preview-bar img { max-height: 48px; border-radius: 6px; object-fit: cover; }
        .attach-preview-bar .attach-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .btn-attach-remove { background: none; border: none; color: #8696a0; cursor: pointer; font-size: 18px; flex-shrink: 0; line-height: 1; }
        .btn-attach-remove:hover { color: #ef4444; }
        /* File bubble */
        .file-msg-bubble {
            display: flex; align-items: center; gap: 12px;
            padding: 10px 12px; border-radius: 8px 8px 8px 2px;
            background: #fff; border: 1px solid #e2e8f0;
            max-width: 320px; box-shadow: 0 1px 2px rgba(0,0,0,.1);
            transition: box-shadow .2s;
        }
        .file-msg-bubble:hover { box-shadow: 0 3px 10px rgba(0,0,0,.12); }
        .chat-row.mine .file-msg-bubble {
            background: #d9fdd3; border-color: #b2dfb8;
            border-radius: 8px 8px 2px 8px;
        }
        .file-bubble-footer { display: flex; justify-content: flex-end; margin-top: 3px; }
        .file-bubble-time { font-size: 11px; color: #8696a0; }
        .chat-row.mine .file-bubble-time { color: #6a9f7a; }
        .file-type-icon {
            width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; background: #ede9fe;
        }
        .chat-row.mine .file-type-icon { background: #ede9fe; }
        .file-info { flex: 1; min-width: 0; }
        .file-info .fname {
            font-size: 13px; font-weight: 700; color: #1e293b;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            margin-bottom: 3px;
        }
        .chat-row.mine .file-info .fname { color: #1e293b; }
        .file-info .fmeta { font-size: 11px; color: #94a3b8; display: flex; align-items: center; gap: 6px; }
        .chat-row.mine .file-info .fmeta { color: #718096; }
        .fmeta-dot { width: 3px; height: 3px; border-radius: 50%; background: #cbd5e1; flex-shrink: 0; }
        .chat-row.mine .fmeta-dot { background: #cbd5e1; }
        .btn-file-dl {
            width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer;
            flex-shrink: 0; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg,#667eea,#764ba2); color: #fff;
            font-size: 16px; box-shadow: 0 2px 8px rgba(102,126,234,.4);
            transition: all .2s; text-decoration: none;
        }
        .btn-file-dl:hover { transform: scale(1.12); box-shadow: 0 4px 14px rgba(102,126,234,.55); }
        .chat-row.mine .btn-file-dl { background: linear-gradient(135deg,#667eea,#764ba2); box-shadow: 0 2px 8px rgba(102,126,234,.4); }
        .chat-row.mine .btn-file-dl:hover { box-shadow: 0 4px 14px rgba(102,126,234,.55); }
        /* Image bubble */
        .img-msg-bubble { border-radius: 8px 8px 8px 2px; overflow: hidden; max-width: 280px; box-shadow: 0 1px 4px rgba(0,0,0,.15); cursor: pointer; position: relative; display: inline-block; }
        .img-msg-bubble img { width: 100%; display: block; }
        .chat-row.mine .img-msg-bubble { border-radius: 8px 8px 2px 8px; }
        .img-time-badge {
            position: absolute; bottom: 6px; right: 7px;
            background: rgba(0,0,0,.45); color: #fff;
            font-size: 11px; padding: 2px 6px; border-radius: 10px;
            backdrop-filter: blur(3px); pointer-events: none;
        }
        .btn-img-save {
            position: absolute; bottom: 8px; right: 8px;
            width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer;
            background: rgba(0,0,0,.55); color: #fff; font-size: 15px;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s; text-decoration: none;
            backdrop-filter: blur(4px);
        }
        .img-msg-bubble:hover .btn-img-save { opacity: 1; }
        /* Camera modal */
        .cam-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.92); z-index:600; align-items:center; justify-content:center; flex-direction:column; gap:20px; }
        .cam-modal.open { display:flex; }
        .cam-modal video { border-radius:16px; max-width:92vw; max-height:58vh; background:#000; }
        .cam-actions { display:flex; gap:14px; }
        .btn-cam-snap { width:64px; height:64px; border-radius:50%; border:4px solid white; background:white; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 16px rgba(0,0,0,.4); }
        .btn-cam-snap::after { content:''; width:52px; height:52px; border-radius:50%; background:#00a884; display:block; }
        .btn-cam-close { padding:10px 22px; background:rgba(255,255,255,.15); color:#fff; border:1.5px solid rgba(255,255,255,.4); border-radius:24px; font-size:14px; cursor:pointer; }
        @keyframes micPulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,.5); }
            50%     { box-shadow: 0 0 0 10px rgba(239,68,68,0); }
        }
        .audio-preview {
            display: none; align-items: center; gap: 10px; margin-top: 8px;
            background: white; border-radius: 26px; padding: 8px 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
        .rec-timer { font-size: 13px; font-weight: 700; color: #ef4444; min-width: 38px; font-variant-numeric: tabular-nums; }
        .btn-discard {
            width: 30px; height: 30px; border-radius: 50%; border: none;
            background: #fee2e2; color: #dc2626; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
        }
        .btn-discard:hover { background: #fecaca; }
        .btn-send-audio {
            width: 38px; height: 38px; border-radius: 50%; border: none;
            background: #00a884; color: #fff; cursor: pointer; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; box-shadow: 0 2px 8px rgba(0,168,132,.35);
        }
        .btn-send-audio:hover { background: #017a62; }
        /* ── Modern audio bubble ── */
        .audio-msg-bubble {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px;
            border-radius: 8px 8px 8px 2px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0,0,0,.1);
            min-width: 220px; max-width: 300px;
            border: 1px solid #f1f5f9;
        }
        .chat-row.mine .audio-msg-bubble {
            background: #d9fdd3;
            border-radius: 8px 8px 2px 8px;
            border-color: #b2dfb8;
        }
        .audio-play-btn {
            width: 38px; height: 38px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0; transition: all .2s;
            background: #00a884; color: #fff;
            box-shadow: 0 2px 6px rgba(0,168,132,.35);
        }
        .chat-row.mine .audio-play-btn {
            background: #25d366;
        }
        .audio-play-btn:hover { transform: scale(1.12); }
        .audio-waveform {
            flex: 1; display: flex; align-items: center; gap: 2.5px; height: 32px;
        }
        .audio-waveform span {
            display: inline-block; width: 3px; border-radius: 4px;
            background: #c8d8d0; transition: background .25s;
            transform-origin: center;
        }
        .chat-row.mine .audio-waveform span { background: #8abfa8; }
        .audio-waveform.playing span { background: #00a884; animation: waveAnim .55s ease-in-out infinite alternate; }
        .chat-row.mine .audio-waveform.playing span { background: #25d366; }
        .audio-waveform span:nth-child(2n)   { animation-delay: .08s; }
        .audio-waveform span:nth-child(3n)   { animation-delay: .18s; }
        .audio-waveform span:nth-child(4n)   { animation-delay: .12s; }
        .audio-waveform span:nth-child(5n)   { animation-delay: .22s; }
        .audio-waveform span:nth-child(7n)   { animation-delay: .05s; }
        @keyframes waveAnim {
            from { transform: scaleY(.3); opacity: .7; }
            to   { transform: scaleY(1.3); opacity: 1; }
        }
        .audio-duration { font-size: 11px; font-weight: 700; color: #8696a0; min-width: 34px; text-align: right; font-variant-numeric: tabular-nums; }
        .chat-row.mine .audio-duration { color: #6a9f7a; }
        .audio-label { font-size: 10px; font-weight: 600; color: #a0aec0; letter-spacing: .4px; text-transform: uppercase; }
        .chat-row.mine .audio-label { color: #5a8a6a; }
        .audio-bubble-footer { display: flex; justify-content: flex-end; font-size: 11px; color: #8696a0; margin-top: 2px; }
        .chat-row.mine .audio-bubble-footer { color: #6a9f7a; }
        .syndicate-row { margin-top: 8px; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #718096; }

        /* Empty state */
        .empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #a0aec0; }
        .empty-state p { margin-top: 10px; font-size: 15px; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 28px; width: 480px; max-width: 95vw; }
        .modal h3 { margin-bottom: 18px; color: #2d3748; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 5px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 7px; font-size: 14px; font-family: inherit; outline: none; }
        .form-group input:focus, .form-group textarea:focus { border-color: #667eea; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
        .btn-cancel { padding: 8px 18px; border: 1px solid #e2e8f0; border-radius: 7px; cursor: pointer; background: white; }
        .btn-submit { padding: 8px 18px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 7px; cursor: pointer; font-weight: 600; }

        /* Alerts */
        .alert { padding: 10px 16px; border-radius: 7px; margin-bottom: 12px; font-size: 14px; }
        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }
    </style>
</head>
<body>

{{-- Navbar --}}
<nav class="navbar">
    <div style="display:flex;align-items:center;gap:10px;">
        <button class="mobile-toggle-btn" id="topicsToggleBtn" type="button" aria-label="Toggle topics list">☰</button>
        <h1><img src="{{ asset('images/forum.png') }}" alt="Discussion Hub" style="height:34px;vertical-align:middle;margin-right:8px;"><span class="full-title">Discussion Hub</span></h1>
    </div>
    <div class="navbar-right">
        <button class="notif-btn" id="notifBtn" onclick="loadNotifications()">
            🔔
            @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="notif-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
            @endif
        </button>
        <span class="d-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100px;">{{ auth()->user()->name }}</span>
        <a href="{{ route('dashboard') }}" class="btn-logout" style="text-decoration:none;">&#8592; Dashboard</a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-logout">Logout</button>
        </form>
    </div>
</nav>

<div class="forum-layout">
    <div class="panel-backdrop" id="panelBackdrop"></div>
    {{-- Sidebar --}}
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">📚 Topics</div>
            <form method="GET" action="{{ route('topics.index') }}">
                <div class="search-wrap">
                    <input type="text" name="search" class="search-bar" placeholder="Search topics..."
                        value="{{ request('search') }}" oninput="this.form.submit()">
                </div>
            </form>
            @if(auth()->user()->isMember() || auth()->user()->isLecturer() || auth()->user()->isAdmin())
            <button class="btn-create" onclick="document.getElementById('createModal').classList.add('open')">
                + New Topic
            </button>
            @endif
        </div>
        <div class="topics-count">{{ $topics->count() }} topic{{ $topics->count() !== 1 ? 's' : '' }}</div>
        <div class="topic-list">
            @forelse($topics as $topic)
                @php $initials = strtoupper(substr($topic->title, 0, 2)); @endphp
                <div class="topic-item {{ isset($activeTopic) && $activeTopic->id === $topic->id ? 'active' : '' }}"
                     onclick="window.location='{{ route('topics.show', $topic) }}'">
                    <div class="topic-item-inner">
                        <div class="topic-avatar">{{ $initials }}</div>
                        <div class="topic-content">
                            <h4>{{ ($topic->is_pinned && ($userGroupIds->contains($topic->group_id) || $lecturerIds->contains($topic->user_id))) ? '📌 ' : '' }}{{ $topic->title }}</h4>
                            <div class="topic-author">by {{ $topic->author->name }}</div>
                            <div class="topic-stats">
                                <span class="topic-stat">💬 {{ $topic->posts_count }}</span>
                                <span class="topic-stat">👁 {{ $topic->views }}</span>
                            </div>
                        </div>
                    </div>
                    @if(auth()->id() === $topic->user_id || auth()->user()->isAdmin())
                        <form action="{{ route('topics.destroy', $topic) }}" method="POST"
                              onsubmit="return confirm('Delete this topic?')" onclick="event.stopPropagation()">
                            @csrf @method('DELETE')
                            <button type="submit" class="topic-delete-btn">🗑 Delete</button>
                        </form>
                    @endif
                </div>
            @empty
                <div style="padding:40px 20px;text-align:center;color:#a0aec0;font-size:13px;">
                    <div style="font-size:32px;margin-bottom:8px;">📭</div>
                    No topics yet.
                </div>
            @endforelse
        </div>
    </aside>

    {{-- Conversation Panel --}}
    <main class="conversation" style="flex:1;display:flex;flex-direction:column;overflow:hidden;">
        @if(isset($activeTopic))
            <div class="conv-header">
                <div>
                    <h2>{{ $activeTopic->title }}</h2>
                    <div class="conv-header-meta">
                        Started by {{ $activeTopic->author->name }} · {{ $activeTopic->created_at->diffForHumans() }}
                    </div>
                </div>
                {{-- Export & Share actions --}}
                <div style="display:flex;gap:8px;align-items:center;">
                    <a href="{{ route('topics.export-pdf', $activeTopic->id) }}"
                       style="padding:7px 14px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border-radius:7px;font-size:13px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:5px;">
                        📄 Export PDF
                    </a>
                    <button onclick="openDiscussionShareModal({{ $activeTopic->id }})"
                        style="padding:7px 14px;background:linear-gradient(135deg,#25d366,#128c7e);color:white;border:none;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;">
                        🌐 Share Discussion
                    </button>
                    <button class="mobile-toggle-btn" id="participantsToggleBtn" type="button" aria-label="Toggle participants list" style="background:#ede9fe;color:#4c1d95;">
                        👥
                    </button>
                </div>
            </div>

            <div class="messages" id="messages">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif

                @php $isRemoved = $activeTopic->removedParticipants->contains(auth()->id()); @endphp

                @if($isRemoved)
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#9b2c2c;background:#fff5f5;border-radius:10px;padding:40px;text-align:center;">
                        <span style="font-size:40px;">🚫</span>
                        <p style="margin-top:10px;font-size:15px;font-weight:600;">You have been removed from this discussion.</p>
                        <p style="font-size:13px;color:#718096;margin-top:6px;">You cannot view or post messages until restored by the topic creator.</p>
                    </div>
                @else
                {{-- Topic origin bubble --}}
                <div class="chat-row topic-origin">
                    <div class="chat-avatar">
                        @if($activeTopic->author->avatar)
                            <img src="{{ asset('storage/'.$activeTopic->author->avatar) }}" alt="">
                        @else
                            {{ strtoupper(substr($activeTopic->author->name,0,1)) }}
                        @endif
                    </div>
                    <div class="chat-bubble-wrap">
                        <div class="chat-bubble">
                            <span class="bubble-author" style="color:#d97706">{{ $activeTopic->author->name }}</span>
                            {{ $activeTopic->body }}
                            <span class="bubble-time">{{ $activeTopic->created_at->format('H:i') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Posts --}}
                @foreach($posts as $post)
                @php
                    $isMe = $post->user_id === auth()->id();
                    $palette = ['#e91e8c','#00bcd4','#4caf50','#ff9800','#9c27b0','#f44336','#2196f3','#009688'];
                    $nameColor = $palette[abs(crc32($post->author->name)) % 8];
                    $postTime = $post->created_at->format('H:i');
                @endphp
                <div class="chat-row {{ $isMe ? 'mine' : '' }}" id="post-{{ $post->id }}">
                    @if(!$isMe)
                    <div class="chat-avatar">
                        @if($post->author->avatar)
                            <img src="{{ asset('storage/'.$post->author->avatar) }}" alt="">
                        @else
                            {{ strtoupper(substr($post->author->name,0,1)) }}
                        @endif
                    </div>
                    @endif
                    <div class="chat-bubble-wrap">
                        @if($post->body)
                        <div class="chat-bubble" id="post-body-{{ $post->id }}">
                            @if(!$isMe)<a href="{{ route('messages.show', $post->user_id) }}" class="bubble-author" style="color:{{ $nameColor }}" title="Message {{ $post->author->name }}">{{ $post->author->name }}</a>@endif
                            {{ $post->body }}<span class="bubble-time">{{ $postTime }}</span>
                        </div>
                        @endif
                        @if($post->image_path)
                            <div class="img-msg-bubble">
                                @if(!$post->body && !$isMe)<span class="bubble-author" style="color:{{ $nameColor }};display:block;padding:5px 8px 0;font-size:12.5px;font-weight:700">{{ $post->author->name }}</span>@endif
                                <img src="{{ asset('storage/' . $post->image_path) }}" alt="Image" loading="lazy">
                                <span class="img-time-badge">{{ $postTime }}</span>
                                <a href="{{ asset('storage/' . $post->image_path) }}" download class="btn-img-save" title="Save image">&#8595;</a>
                            </div>
                        @endif
                        @if($post->file_path)
                            @php
                                $ext = strtolower(pathinfo($post->file_name ?? '', PATHINFO_EXTENSION));
                                $fileIcon = match(true) {
                                    in_array($ext,['pdf']) => '📕',
                                    in_array($ext,['doc','docx']) => '📘',
                                    in_array($ext,['xls','xlsx','csv']) => '📗',
                                    in_array($ext,['ppt','pptx']) => '📙',
                                    in_array($ext,['zip','rar','7z']) => '🗜️',
                                    in_array($ext,['mp3','wav','ogg']) => '🎵',
                                    in_array($ext,['mp4','mov','avi']) => '🎬',
                                    default => '📄'
                                };
                                $fileSize = $post->file_size
                                    ? ($post->file_size >= 1048576
                                        ? round($post->file_size/1048576,1).'MB'
                                        : round($post->file_size/1024,0).'KB')
                                    : strtoupper($ext);
                            @endphp
                            @if(!$post->body && !$isMe)<a href="{{ route('messages.show', $post->user_id) }}" class="bubble-author" style="color:{{ $nameColor }};display:block;font-size:12.5px;font-weight:700;text-decoration:none;margin-bottom:3px">{{ $post->author->name }}</a>@endif
                            <div class="file-msg-bubble">
                                <div class="file-type-icon">{{ $fileIcon }}</div>
                                <div class="file-info">
                                    <div class="fname" title="{{ $post->file_name }}">{{ $post->file_name ?? 'Document' }}</div>
                                    <div class="fmeta">
                                        <span>{{ strtoupper($ext) }}</span>
                                        <span class="fmeta-dot"></span>
                                        <span>{{ $fileSize }}</span>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $post->file_path) }}" download="{{ $post->file_name }}" class="btn-file-dl" title="Download">&#8595;</a>
                            </div>
                            <div class="file-bubble-footer"><span class="file-bubble-time">{{ $postTime }}</span></div>
                        @endif
                        @if($post->audio_path)
                        @php $heights = [8,14,20,28,22,16,26,18,10,24,20,14,22,8,18,26,12,20,30,14]; @endphp
                        @if(!$post->body && !$isMe)<span class="bubble-author" style="color:{{ $nameColor }};display:block;font-size:12.5px;font-weight:700;margin-bottom:3px">{{ $post->author->name }}</span>@endif
                        <div class="audio-msg-bubble">
                            <button class="audio-play-btn" onclick="toggleAudio(this)" type="button">&#9654;</button>
                            <div style="flex:1;display:flex;flex-direction:column;gap:3px;min-width:0;">
                                <span class="audio-label">Voice message</span>
                                <div class="audio-waveform">
                                    @foreach($heights as $h)<span style="height:{{ $h }}px"></span>@endforeach
                                </div>
                            </div>
                            <span class="audio-duration">0:00</span>
                            <audio preload="auto" src="{{ asset('storage/' . $post->audio_path) }}" style="display:none"></audio>
                        </div>
                        <div class="audio-bubble-footer">{{ $postTime }}</div>
                        @endif
                        <div class="chat-actions">
                            <button class="btn-sm btn-reply" title="Reply"
                                onclick="setReply({{ $post->id }}, '{{ $isMe ? 'You' : addslashes($post->author->name) }}', '{{ addslashes(Str::limit($post->body ?: 'Attachment', 60)) }}')">&#8617;</button>
                            @if(auth()->id() === $post->user_id || auth()->user()->isAdmin())
                                <button class="btn-sm btn-edit" title="Edit" onclick="editPost({{ $post->id }}, `{{ addslashes($post->body) }}`)">&#9998;</button>
                                <button class="btn-sm btn-delete" title="Delete" onclick="deletePost({{ $post->id }})">&#128465;</button>
                            @endif
                        </div>
                    </div>
                    @if($isMe)
                    <div class="chat-avatar">
                        @if(auth()->user()->avatar)
                            <img src="{{ asset('storage/'.auth()->user()->avatar) }}" alt="">
                        @else
                            {{ strtoupper(substr(auth()->user()->name,0,1)) }}
                        @endif
                    </div>
                    @endif
                </div>
                {{-- Replies as WhatsApp-style bubbles with embedded quote --}}
                @foreach($post->replies as $reply)
                @php
                    $rIsMe = $reply->user_id === auth()->id();
                    $rColor = $palette[abs(crc32($reply->author->name)) % 8];
                @endphp
                <div class="chat-row {{ $rIsMe ? 'mine' : '' }}" id="reply-{{ $reply->id }}">
                    @if(!$rIsMe)
                    <div class="chat-avatar">
                        @if($reply->author->avatar)
                            <img src="{{ asset('storage/'.$reply->author->avatar) }}" alt="">
                        @else
                            {{ strtoupper(substr($reply->author->name,0,1)) }}
                        @endif
                    </div>
                    @endif
                    <div class="chat-bubble-wrap">
                        <div class="chat-bubble">
                            @if(!$rIsMe)<span class="bubble-author" style="color:{{ $rColor }}">{{ $reply->author->name }}</span>@endif
                            <div class="reply-quote" onclick="document.getElementById('post-{{ $post->id }}')?.scrollIntoView({behavior:'smooth',block:'center'})">
                                <div class="rq-author" style="color:{{ $nameColor }}">{{ $post->author->name }}</div>
                                <div class="rq-body">{{ Str::limit($post->body ?: 'Attachment', 80) }}</div>
                            </div>
                            {{ $reply->body }}<span class="bubble-time">{{ $reply->created_at->format('H:i') }}</span>
                        </div>
                    </div>
                    @if($rIsMe)
                    <div class="chat-avatar">
                        @if(auth()->user()->avatar)
                            <img src="{{ asset('storage/'.auth()->user()->avatar) }}" alt="">
                        @else
                            {{ strtoupper(substr(auth()->user()->name,0,1)) }}
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
                @endforeach
                @endif {{-- end $isRemoved check --}}
            </div>

            {{-- Typing indicator --}}
            <div class="typing-indicator" id="typingIndicator"></div>

            {{-- Input area --}}
            @if(!$activeTopic->is_locked && !$isRemoved)
                <div class="input-area">
                    {{-- Hidden reply form — POSTs to /posts/{id}/answer --}}
                    <form id="replyForm" method="POST" action="" style="display:none">
                        @csrf
                        <input type="hidden" name="body" id="replyFormBody">
                    </form>

                    <form action="{{ route('topics.participate', $activeTopic->id) }}" method="POST" id="postForm" enctype="multipart/form-data" data-no-loader>
                        @csrf
                        <input type="file" id="imgInput" name="image" accept="image/*" style="display:none">
                        <input type="file" id="docInput" name="file" style="display:none">
                        <div class="reply-bar" id="replyBar">
                            <div style="flex:1;min-width:0;">
                                <div class="rb-author" id="replyBarAuthor"></div>
                                <div class="rb-body" id="replyBarBody"></div>
                            </div>
                            <button type="button" class="btn-cancel-reply" onclick="cancelReply()">&#10005;</button>
                        </div>
                        <div class="attach-preview-bar" id="attachPreviewBar">
                            <span id="attachPreviewThumb"></span>
                            <span class="attach-name" id="attachPreviewName"></span>
                            <button type="button" class="btn-attach-remove" id="attachRemoveBtn" title="Remove">&#10005;</button>
                        </div>
                        <div class="input-bar">
                            <div class="bar-icons-left">
                                <button type="button" class="bar-icon" id="docBtn" title="Send document">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .83-.67 1.5-1.5 1.5s-1.5-.67-1.5-1.5V6H9v9.5a3 3 0 0 0 6 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/></svg>
                                </button>
                                <button type="button" class="bar-icon" id="camBtn" title="Take photo">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 15.2A3.2 3.2 0 1 0 12 8.8a3.2 3.2 0 0 0 0 6.4zm0-8.4a5.2 5.2 0 1 1 0 10.4A5.2 5.2 0 0 1 12 6.8zM9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9z"/></svg>
                                </button>
                                <button type="button" class="bar-icon" id="imgBtn" title="Send image">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                                </button>
                            </div>
                            <textarea name="body" id="postInput" class="msg-input" rows="1"
                                placeholder="Write a message…"
                                oninput="onMsgInput();handleTyping();"
                                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();submitMessage();}"></textarea>
                            <button type="button" class="bar-icon-right" id="micBtn" title="Record audio">
                                <svg id="micIcon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
                                <svg id="sendIcon" viewBox="0 0 24 24" fill="currentColor" style="display:none"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                            </button>
                        </div>
                        <div class="audio-preview" id="audioPreview">
                            <button type="button" class="btn-discard" id="discardAudio" title="Discard">&#10005;</button>
                            <span class="rec-timer" id="recTimer">0:00</span>
                            <div style="flex:1;display:flex;align-items:center;gap:2px;height:28px">
                                @for($i=0;$i<20;$i++)
                                <span style="display:inline-block;width:3px;border-radius:3px;background:#c7d2fe;height:{{ [10,16,22,28,20,14,24,18,12,26,20,16,22,10,18,24,14,20,28,16][$i] }}px"></span>
                                @endfor
                            </div>
                            <button type="button" class="btn-send-audio" id="sendAudioBtn" title="Send voice message">&#9658;</button>
                        </div>
                        <div class="syndicate-row">
                            <input type="checkbox" name="syndicate" id="syndicate" value="1">
                            <label for="syndicate">Syndicate to other groups</label>
                        </div>
                    </form>
                </div>
            @else
                <div style="padding:14px 20px;background:#fff3cd;text-align:center;font-size:14px;color:#856404;">
                    🔒 This topic is locked.
                </div>
            @endif
        @else
            <div class="empty-state">
                <span style="font-size:48px;">💬</span>
                <p>Select a topic to start the conversation</p>
            </div>
        @endif
    </main>

    {{-- Participants Panel --}}
    @if(isset($activeTopic))
    <aside class="participants-panel">
        <h4>👥 Participants</h4>

        {{-- Active participants --}}
        <div class="section-label">Active</div>
        @forelse($activeTopic->participants as $participant)
            <div class="participant-item">
                <span class="participant-name">{{ $participant->name }}</span>
                @if($participant->id === $activeTopic->user_id)
                    <span style="font-size:11px;color:#667eea;">creator</span>
                @elseif(auth()->id() === $activeTopic->user_id)
                    <div class="participant-actions">
                        <form action="{{ route('topics.removeUser', [$activeTopic, $participant->id]) }}" method="POST"
                              onsubmit="return confirm('Remove {{ addslashes($participant->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-remove-user">Remove</button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div style="padding:10px 14px;font-size:13px;color:#a0aec0;">No active participants.</div>
        @endforelse

        @if(auth()->id() === $activeTopic->user_id)
            <div class="section-label" style="margin-top:8px;">🗑 Removed</div>
            @forelse($activeTopic->removedParticipants as $removed)
                <div class="participant-item" style="background:#fff5f5;color:#9b2c2c;">
                    <span class="participant-name">{{ $removed->name }}</span>
                    <form action="{{ route('topics.unremoveUser', [$activeTopic, $removed->id]) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-unblock-user">Restore</button>
                    </form>
                </div>
            @empty
                <div style="padding:10px 14px;font-size:13px;color:#a0aec0;">No removed users.</div>
            @endforelse
        @endif
    </aside>
    @endif
</div>

{{-- Create Topic Modal --}}
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <h3>Create New Topic</h3>
        <form action="{{ route('topics.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="Topic title...">
            </div>
            <div class="form-group">
                <label>Body</label>
                <textarea name="body" rows="4" required placeholder="Describe your topic..."></textarea>
            </div>
            <div class="syndicate-row" style="margin-bottom:10px;">
                <input type="checkbox" name="syndicate" id="syndicateModal" value="1">
                <label for="syndicateModal">Syndicate to other groups</label>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="document.getElementById('createModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn-submit">Create Topic</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Post Modal --}}
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h3>Edit Post</h3>
        <div class="form-group">
            <label>Content</label>
            <textarea id="editBody" rows="4" class="msg-input" style="width:100%;"></textarea>
        </div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="document.getElementById('editModal').classList.remove('open')">Cancel</button>
            <button class="btn-submit" onclick="submitEdit()">Save</button>
        </div>
    </div>
</div>

{{-- Share Modal --}}
<div class="modal-overlay" id="shareModal">
    <div class="modal" style="width:500px;">
        <h3>🌐 Share Discussion</h3>
        <p style="font-size:13px;color:#718096;margin-bottom:14px;">Choose a platform to share the entire conversation.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
            <button class="share-card" data-platform="twitter" onclick="selectSharePlatform(this)"
                style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid #e2e8f0;border-radius:10px;background:white;cursor:pointer;font-size:14px;font-weight:600;color:#2d3748;">
                <span style="font-size:22px;">𝕏</span> Twitter / X
            </button>
            <button class="share-card" data-platform="linkedin" onclick="selectSharePlatform(this)"
                style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid #e2e8f0;border-radius:10px;background:white;cursor:pointer;font-size:14px;font-weight:600;color:#2d3748;">
                <span style="font-size:22px;">💼</span> LinkedIn
            </button>
            <button class="share-card" data-platform="facebook" onclick="selectSharePlatform(this)"
                style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid #e2e8f0;border-radius:10px;background:white;cursor:pointer;font-size:14px;font-weight:600;color:#2d3748;">
                <span style="font-size:22px;">📘</span> Facebook
            </button>
            <button class="share-card" data-platform="whatsapp" onclick="selectSharePlatform(this)"
                style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:2px solid #e2e8f0;border-radius:10px;background:white;cursor:pointer;font-size:14px;font-weight:600;color:#2d3748;">
                <span style="font-size:22px;">💬</span> WhatsApp
            </button>
        </div>
        <div id="shareStatus" style="font-size:13px;min-height:20px;margin-bottom:8px;"></div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeShareModal()">Cancel</button>
            <button class="btn-submit" id="shareBtn" onclick="submitShare()" disabled style="opacity:0.5;">🚀 Share Now</button>
        </div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let editingPostId = null;
    let sharingTopicId = null;
    let selectedPlatform = null;
    let typingTimer = null;

    // Mobile off-canvas toggling for topics list / participants panel
    (function () {
        var sidebar = document.querySelector('.sidebar');
        var participants = document.querySelector('.participants-panel');
        var backdrop = document.getElementById('panelBackdrop');
        var topicsBtn = document.getElementById('topicsToggleBtn');
        var participantsBtn = document.getElementById('participantsToggleBtn');
        if (!backdrop) return;

        function closeAll() {
            if (sidebar) sidebar.classList.remove('open');
            if (participants) participants.classList.remove('open');
            backdrop.classList.remove('show');
        }
        function openPanel(panel) {
            closeAll();
            if (panel) { panel.classList.add('open'); backdrop.classList.add('show'); }
        }

        if (topicsBtn) topicsBtn.addEventListener('click', function () {
            sidebar && sidebar.classList.contains('open') ? closeAll() : openPanel(sidebar);
        });
        if (participantsBtn) participantsBtn.addEventListener('click', function () {
            participants && participants.classList.contains('open') ? closeAll() : openPanel(participants);
        });
        backdrop.addEventListener('click', closeAll);
    })();

    // ── Open share modal for entire discussion ─────────────────
    function openDiscussionShareModal(topicId) {
        sharingTopicId = topicId;
        resetShareModal();
        document.getElementById('shareModal').classList.add('open');
    }


    function resetShareModal() {
        selectedPlatform = null;
        document.getElementById('shareStatus').textContent = '';
        document.getElementById('shareBtn').disabled = true;
        document.getElementById('shareBtn').style.opacity = '0.5';
        document.querySelectorAll('.share-card').forEach(c => {
            c.style.borderColor = '#e2e8f0';
            c.style.background = 'white';
            c.style.color = '#2d3748';
        });
    }

    function closeShareModal() {
        document.getElementById('shareModal').classList.remove('open');
    }

    function selectSharePlatform(btn) {
        document.querySelectorAll('.share-card').forEach(c => {
            c.style.borderColor = '#e2e8f0';
            c.style.background = 'white';
            c.style.color = '#2d3748';
        });
        btn.style.borderColor = '#667eea';
        btn.style.background = '#f0f0ff';
        selectedPlatform = btn.dataset.platform;
        document.getElementById('shareBtn').disabled = false;
        document.getElementById('shareBtn').style.opacity = '1';
        document.getElementById('shareStatus').textContent = '';
    }

    function buildConversationText() {
        const title = document.querySelector('.conv-header h2').textContent.trim();
        let lines = ['📚 Discussion: "' + title + '"', ''];
        document.querySelectorAll('#messages .chat-row').forEach(row => {
            const author = row.querySelector('.chat-meta .author');
            const time   = row.querySelector('.chat-meta span:not(.author)');
            const body   = row.querySelector('.chat-bubble');
            if (author && body) {
                const prefix = row.classList.contains('topic-origin') ? '[Topic] ' : '';
                lines.push(prefix + '[' + (time ? time.textContent.trim() : '') + '] ' + author.textContent.trim() + ': ' + body.textContent.trim());
                row.querySelectorAll('.reply-bubble').forEach(r => {
                    const ra = r.querySelector('.reply-author');
                    const rb = r.lastChild;
                    if (ra) lines.push('  ↩ ' + ra.textContent.trim() + ': ' + (rb ? rb.textContent.trim() : ''));
                });
                lines.push('');
            }
        });
        lines.push(window.location.href);
        return lines.join('\n');
    }

    function submitShare() {
        if (!selectedPlatform) return;
        const statusEl = document.getElementById('shareStatus');
        const conversation = buildConversationText();
        const topicUrl = encodeURIComponent(window.location.href);
        const text = encodeURIComponent(conversation);
        const twitterText = encodeURIComponent(
            '📚 "' + document.querySelector('.conv-header h2').textContent.trim() + '" — join the discussion on Discussion Hub'
        );
        const shareUrls = {
            whatsapp: 'https://wa.me/?text=' + text,
            twitter:  'https://twitter.com/intent/tweet?text=' + twitterText + '&url=' + topicUrl,
            facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + topicUrl + '&quote=' + text,
            linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + topicUrl,
        };
        window.open(shareUrls[selectedPlatform], '_blank', 'noopener,noreferrer');
        statusEl.style.color = '#276749';
        statusEl.textContent = '✅ ' + selectedPlatform.charAt(0).toUpperCase() + selectedPlatform.slice(1) + ' opened in a new tab.';
    }


    let replyingToPostId = null;

    function setReply(postId, author, body) {
        replyingToPostId = postId;
        document.getElementById('replyBarAuthor').textContent = author;
        document.getElementById('replyBarBody').textContent = body;
        document.getElementById('replyBar').style.display = 'flex';
        document.getElementById('postInput').focus();
        // Update hidden reply form action
        document.getElementById('replyForm').action = '/posts/' + postId + '/answer';
    }
    function cancelReply() {
        replyingToPostId = null;
        document.getElementById('replyBar').style.display = 'none';
        document.getElementById('replyForm').action = '';
    }

    function submitMessage() {
        const val = document.getElementById('postInput').value.trim();
        if (!val) return;
        if (replyingToPostId) {
            document.getElementById('replyFormBody').value = val;
            document.getElementById('replyForm').submit();
        } else {
            document.getElementById('postForm').requestSubmit();
        }
    }

    function editPost(postId, body) {
        editingPostId = postId;
        document.getElementById('editBody').value = body;
        document.getElementById('editModal').classList.add('open');
    }

    function submitEdit() {
        fetch(`/posts/${editingPostId}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ body: document.getElementById('editBody').value })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('post-body-' + editingPostId).textContent = data.post.body;
                document.getElementById('editModal').classList.remove('open');
            } else {
                alert(data.error);
            }
        });
    }

    function deletePost(postId) {
        if (!confirm('Delete this post?')) return;
        fetch(`/posts/${postId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) document.getElementById('post-' + postId).remove();
            else alert(data.error);
        });
    }

    function handleTyping() {
        @if(isset($activeTopic))
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('topic.{{ $activeTopic->id }}').whisper('typing', { name: '{{ auth()->user()->name }}' });
        }
        @endif
    }

    function loadNotifications() {
        fetch('/notifications')
            .then(r => r.json())
            .then(data => {
                const msgs = data.map(n => `• ${n.data.user}: ${n.data.excerpt}`).join('\n');
                alert(msgs || 'No notifications.');
                document.querySelector('.notif-badge') && (document.querySelector('.notif-badge').remove());
            });
    }

    @if(isset($activeTopic))
    document.addEventListener('DOMContentLoaded', () => {
        const palette = ['#e91e8c','#00bcd4','#4caf50','#ff9800','#9c27b0','#f44336','#2196f3','#009688'];
        function nameColor(name) { return palette[Math.abs(name.split('').reduce((a,c)=>a+c.charCodeAt(0),0)) % palette.length]; }
        const myId   = {{ auth()->id() }};
        const myName = @json(auth()->user()->name);
        let lastFetch = new Date().toISOString();

        function buildBubble(post) {
            const isMe = post.user_id === myId;
            const authorName = post.author_name || 'User';
            const color = nameColor(authorName);
            const now = new Date();
            const timeStr = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
            const row = document.createElement('div');
            row.className = 'chat-row' + (isMe ? ' mine' : '');
            row.id = 'post-' + post.id;
            const initial = authorName.charAt(0).toUpperCase();
            const avatarHtml = `<div class="chat-avatar">${initial}</div>`;
            let inner = '';
            if (post.body) {
                const authorTag = isMe ? '' : `<a href="/messages/${post.user_id}" class="bubble-author" style="color:${color}">${escHtml(authorName)}</a>`;
                inner += `<div class="chat-bubble" id="post-body-${post.id}">${authorTag}${escHtml(post.body)}<span class="bubble-time">${timeStr}</span></div>`;
            }
            let actionsHtml = `<div class="chat-actions">
                <button class="btn-sm btn-reply" title="Reply" onclick="setReply(${post.id},'${isMe?'You':authorName.replace(/'/g,"\\'")}','${escHtml(post.body||'Attachment').replace(/'/g,"\\'")}');">&#8617;</button>`;
            if (isMe) actionsHtml += `<button class="btn-sm btn-edit" title="Edit" onclick="editPost(${post.id},\`${(post.body||'').replace(/`/g,'\\`')}\`)">&#9998;</button><button class="btn-sm btn-delete" title="Delete" onclick="deletePost(${post.id})">&#128465;</button>`;
            actionsHtml += '</div>';
            row.innerHTML =
                (!isMe ? avatarHtml : '') +
                `<div class="chat-bubble-wrap">${inner}${actionsHtml}</div>` +
                (isMe ? avatarHtml : '');
            return row;
        }

        function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        function pollPosts() {
            fetch('/api/topics/{{ $activeTopic->id }}/posts?since=' + encodeURIComponent(lastFetch), {credentials:'same-origin'})
                .then(r => r.json())
                .then(posts => {
                    if (!posts.length) return;
                    lastFetch = posts[posts.length - 1].created_at;
                    const container = document.getElementById('messages');
                    const atBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 60;
                    posts.forEach(post => {
                        if (document.getElementById('post-' + post.id)) return;
                        container.appendChild(buildBubble(post));
                    });
                    if (atBottom) container.scrollTop = container.scrollHeight;
                })
                .catch(() => {});
        }

        setInterval(pollPosts, 3000);

        // Typing indicator via Echo whisper (kept if Echo is available)
        if (typeof window.Echo !== 'undefined') {
            window.Echo.channel('topic.{{ $activeTopic->id }}').listenForWhisper('typing', (e) => {
                const el = document.getElementById('typingIndicator');
                el.innerHTML = `${e.name} is typing <span class="typing-dots"><span></span><span></span><span></span></span>`;
                clearTimeout(typingTimer);
                typingTimer = setTimeout(() => el.innerHTML = '', 2000);
            });
        }
    });
    @endif

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
    });

    const msgs = document.getElementById('messages');
    if (msgs) msgs.scrollTop = msgs.scrollHeight;

    // ── Select message row on click to reveal actions ──
    document.getElementById('messages') && document.getElementById('messages').addEventListener('click', function (e) {
        const row = e.target.closest('.chat-row');
        if (!row) { document.querySelectorAll('.chat-row.selected').forEach(r => r.classList.remove('selected')); return; }
        if (e.target.closest('.btn-sm, .audio-play-btn, .btn-file-dl, audio, a')) return;
        const wasSelected = row.classList.contains('selected');
        document.querySelectorAll('.chat-row.selected').forEach(r => r.classList.remove('selected'));
        if (!wasSelected) row.classList.add('selected');
    });

    function fmtTime(s) {
        if (!isFinite(s) || isNaN(s)) return '0:00';
        return Math.floor(s/60)+':'+(Math.floor(s%60)).toString().padStart(2,'0');
    }

    // ── Audio bubble player ──
    document.querySelectorAll('.audio-msg-bubble').forEach(function(bubble) {
        const audio = bubble.querySelector('audio');
        const durEl = bubble.querySelector('.audio-duration');
        let fixingDuration = false;

        function setDurationText(seconds) {
            if (isFinite(seconds)) durEl.textContent = fmtTime(seconds);
        }

        audio.addEventListener('loadedmetadata', function() {
            // Chrome-family browsers often report duration = Infinity for
            // MediaRecorder-produced webm blobs, since no duration is written
            // into the file header while recording. Forcing a seek past the
            // end and back makes the browser recompute the real duration.
            if (audio.duration === Infinity || isNaN(audio.duration)) {
                fixingDuration = true;
                audio.currentTime = 1e101;
                audio.addEventListener('timeupdate', function onFix() {
                    audio.removeEventListener('timeupdate', onFix);
                    audio.currentTime = 0;
                    fixingDuration = false;
                    setDurationText(audio.duration);
                }, { once: true });
            } else {
                setDurationText(audio.duration);
            }
        });
        audio.addEventListener('durationchange', function() {
            if (!fixingDuration) setDurationText(audio.duration);
        });
        audio.addEventListener('timeupdate', function() {
            if (!fixingDuration) durEl.textContent = fmtTime(audio.currentTime);
        });
        audio.addEventListener('ended', function() {
            bubble.querySelector('.audio-play-btn').innerHTML = '&#9654;';
            bubble.querySelector('.audio-waveform').classList.remove('playing');
            setDurationText(audio.duration);
        });
        audio.addEventListener('error', function() {
            durEl.textContent = 'err';
        });
    });

    function toggleAudio(btn) {
        const bubble = btn.closest('.audio-msg-bubble');
        const audio  = bubble.querySelector('audio');
        const wave   = bubble.querySelector('.audio-waveform');
        // stop all others
        document.querySelectorAll('.audio-msg-bubble audio').forEach(function(a) {
            if (a !== audio && !a.paused) {
                a.pause();
                const b = a.closest('.audio-msg-bubble');
                b.querySelector('.audio-play-btn').innerHTML = '&#9654;';
                b.querySelector('.audio-waveform').classList.remove('playing');
            }
        });
        if (audio.paused) {
            audio.play().catch(function(e) { console.warn('Audio play failed:', e); });
            btn.innerHTML = '&#9646;&#9646;';
            wave.classList.add('playing');
        } else {
            audio.pause();
            btn.innerHTML = '&#9654;';
            wave.classList.remove('playing');
        }
    }

    // ── Send/mic toggle ──
    function updateSendBtn() {
        const val = document.getElementById('postInput') && document.getElementById('postInput').value.trim();
        const imgInput = document.getElementById('imgInput');
        const docInput = document.getElementById('docInput');
        const hasAttach = (imgInput && imgInput.files[0]) || (docInput && docInput.files[0]);
        const show = !!(val || hasAttach);
        const mi = document.getElementById('micIcon');
        const si = document.getElementById('sendIcon');
        if (mi) mi.style.display = show ? 'none'  : 'block';
        if (si) si.style.display = show ? 'block' : 'none';
    }
    function onMsgInput() { updateSendBtn(); }

    // standalone send handler
    document.getElementById('micBtn') && document.getElementById('micBtn').addEventListener('click', function () {
        const val = document.getElementById('postInput') && document.getElementById('postInput').value.trim();
        if (val) { submitMessage(); }
    });

    // ── Audio Recorder ──
    (function () {
        const micBtn       = document.getElementById('micBtn');
        const audioPreview = document.getElementById('audioPreview');
        const discardBtn   = document.getElementById('discardAudio');
        const recTimerEl   = document.getElementById('recTimer');
        const sendAudioBtn = document.getElementById('sendAudioBtn');
        const postForm     = document.getElementById('postForm');
        if (!micBtn) return;

        let mediaRecorder, audioChunks = [], recInterval, recSeconds = 0, audioBlob = null, mimeType = '';

        function fmtSecs(s) { return Math.floor(s/60)+':'+(s%60).toString().padStart(2,'0'); }

        micBtn.addEventListener('click', async function () {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioChunks = []; recSeconds = 0;
                recTimerEl.textContent = '0:00';
                // Not every browser supports the same container — Safari/iOS
                // can't record audio/webm at all. Ask for whatever this
                // browser actually supports instead of assuming webm; if we
                // hardcode webm, Safari either throws or silently records
                // something the recipient's browser can't decode later.
                const preferredTypes = [
                    'audio/webm;codecs=opus',
                    'audio/webm',
                    'audio/mp4',
                    'audio/ogg;codecs=opus',
                ];
                const supportedType = preferredTypes.find(t => window.MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(t));
                mediaRecorder = supportedType ? new MediaRecorder(stream, { mimeType: supportedType }) : new MediaRecorder(stream);
                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                mediaRecorder.onstop = function () {
                    stream.getTracks().forEach(t => t.stop());
                    // Tag the Blob with whatever mimeType the recorder actually
                    // used (falls back to webm only if the browser didn't
                    // report one), not a hardcoded guess.
                    audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                    audioPreview.style.display = 'flex';
                    micBtn.classList.remove('recording');
                    micBtn.title = 'Record audio message';
                    clearInterval(recInterval);
                };
                mediaRecorder.start();
                micBtn.classList.add('recording');
                micBtn.title = 'Stop recording';
                recInterval = setInterval(() => { recSeconds++; recTimerEl.textContent = fmtSecs(recSeconds); }, 1000);
            } catch (err) {
                alert('Microphone access denied. Please allow microphone permission.');
            }
        });

        discardBtn.addEventListener('click', function () {
            audioBlob = null;
            audioPreview.style.display = 'none';
            recTimerEl.textContent = '0:00';
        });

        // Send audio independently — no text required
        sendAudioBtn.addEventListener('click', async function () {
            if (!audioBlob) return;
            const fd = new FormData();
            const ext = audioBlob.type.includes('mp4') ? 'mp4'
                : audioBlob.type.includes('ogg') ? 'ogg'
                : 'webm';
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            fd.append('audio', audioBlob, 'voice-message.' + ext);
            fd.append('body', '');
            const res = await fetch(postForm.action, { method: 'POST', body: fd });
            if (res.redirected) { window.location.href = res.url; }
            else { window.location.reload(); }
        });
    })();

    // ── Attachment toolbar ──
    (function () {
        const imgBtn       = document.getElementById('imgBtn');
        const docBtn       = document.getElementById('docBtn');
        const camBtn       = document.getElementById('camBtn');
        const imgInput     = document.getElementById('imgInput');
        const docInput     = document.getElementById('docInput');
        const previewBar   = document.getElementById('attachPreviewBar');
        const previewThumb = document.getElementById('attachPreviewThumb');
        const previewName  = document.getElementById('attachPreviewName');
        const removeBtn    = document.getElementById('attachRemoveBtn');
        if (!imgBtn) return;

        function stageAttachment(file, isImage) {
            if (isImage) {
                const url = URL.createObjectURL(file);
                previewThumb.innerHTML = '<img src="' + url + '" style="max-height:48px;border-radius:6px;">';
            } else {
                previewThumb.textContent = '📄';
            }
            previewName.textContent = file.name;
            previewBar.style.display = 'flex';
            updateSendBtn();
        }

        function clearAttachment() {
            imgInput.value = '';
            docInput.value = '';
            previewThumb.innerHTML = '';
            previewName.textContent = '';
            previewBar.style.display = 'none';
            updateSendBtn();
        }

        removeBtn.addEventListener('click', clearAttachment);

        imgBtn.addEventListener('click', () => imgInput.click());
        docBtn.addEventListener('click', () => docInput.click());

        imgInput.addEventListener('change', function () {
            if (!this.files[0]) return;
            stageAttachment(this.files[0], true);
        });
        docInput.addEventListener('change', function () {
            if (!this.files[0]) return;
            stageAttachment(this.files[0], false);
        });

        // Send staged attachment when send button clicked
        document.getElementById('micBtn').addEventListener('click', function () {
            const hasAttach = imgInput.files[0] || docInput.files[0];
            if (hasAttach) {
                const fd = new FormData(document.getElementById('postForm'));
                fetch(document.getElementById('postForm').action, { method: 'POST', body: fd })
                    .then(r => r.redirected ? window.location.href = r.url : window.location.reload());
            }
        });

        const camModal  = document.getElementById('camModal');
        const camVideo  = document.getElementById('camVideo');
        const camCanvas = document.getElementById('camCanvas');
        const snapBtn   = document.getElementById('camSnapBtn');
        const closeBtn  = document.getElementById('camCloseBtn');
        let camStream   = null;

        camBtn.addEventListener('click', async function () {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                imgInput.setAttribute('capture', 'environment'); imgInput.click(); return;
            }
            try {
                camStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                camVideo.srcObject = camStream;
                camModal.classList.add('open');
            } catch (e) { imgInput.setAttribute('capture', 'environment'); imgInput.click(); }
        });

        function stopCam() {
            if (camStream) { camStream.getTracks().forEach(function(t) { t.stop(); }); camStream = null; }
            camModal.classList.remove('open');
        }
        closeBtn.addEventListener('click', stopCam);

        snapBtn.addEventListener('click', function () {
            camCanvas.width  = camVideo.videoWidth;
            camCanvas.height = camVideo.videoHeight;
            camCanvas.getContext('2d').drawImage(camVideo, 0, 0);
            camCanvas.toBlob(function (blob) {
                var file = new File([blob], 'photo-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                var dt = new DataTransfer();
                dt.items.add(file);
                imgInput.files = dt.files;
                stopCam();
                stageAttachment(file, true);
            }, 'image/jpeg', 0.92);
        });
    })();
</script>

{{-- Camera modal --}}
<div class="cam-modal" id="camModal">
    <video id="camVideo" autoplay playsinline></video>
    <canvas id="camCanvas" style="display:none"></canvas>
    <div class="cam-actions">
        <button class="btn-cam-close" id="camCloseBtn">&#10005; Cancel</button>
        <button class="btn-cam-snap" id="camSnapBtn" title="Capture"></button>
    </div>
</div>
@auth
@if(auth()->user()->isMember())
<style>
.qpop-overlay{position:fixed;inset:0;background:rgba(15,23,42,.92);z-index:99999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(6px);}
.qpop-overlay.active{display:flex!important;}
.qpop-box{background:#fff;border-radius:24px;padding:44px 40px;max-width:460px;width:90%;text-align:center;box-shadow:0 32px 80px rgba(0,0,0,.5);animation:qpopIn .35s cubic-bezier(.34,1.56,.64,1);}
@keyframes qpopIn{from{transform:scale(.7);opacity:0}to{transform:scale(1);opacity:1}}
@keyframes qpopBell{from{transform:rotate(-15deg)}to{transform:rotate(15deg)}}
.qpop-icon{font-size:52px;margin-bottom:14px;}
.qpop-title{font-size:21px;font-weight:900;color:#0f172a;margin-bottom:8px;}
.qpop-name{font-size:16px;font-weight:700;color:#6366f1;background:#ede9fe;border-radius:10px;padding:9px 14px;margin-bottom:12px;}
.qpop-meta{display:flex;justify-content:center;gap:14px;flex-wrap:wrap;font-size:12px;color:#64748b;font-weight:600;margin-bottom:14px;}
.qpop-desc{font-size:13px;color:#64748b;line-height:1.6;margin-bottom:24px;}
.qpop-btn{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;text-decoration:none;border-radius:12px;padding:13px 32px;font-size:15px;font-weight:800;box-shadow:0 8px 24px rgba(99,102,241,.45);transition:all .2s;}
.qpop-btn:hover{opacity:.9;transform:translateY(-2px);}
body.qpop-locked{overflow:hidden;pointer-events:none;}
body.qpop-locked .qpop-overlay{pointer-events:all;}
</style>
<div id="qpop-container"></div>
<script>
(function(){
    function isDismissed(id){return localStorage.getItem('quiz_started_'+id)==='1';}
    window.qpopClose=function(id){
        localStorage.setItem('quiz_started_'+id,'1');
        var el=document.getElementById('qpop_'+id);
        if(el)el.classList.remove('active');
        if(!document.querySelector('.qpop-overlay.active'))document.body.classList.remove('qpop-locked');
    };
    function showPopup(q){
        if(isDismissed(q.id))return;
        if(document.getElementById('qpop_'+q.id))return;
        var deadline=q.hard_deadline?'<span>🏁 Due '+q.hard_deadline+'</span>':'';
        var html='<div id="qpop_'+q.id+'" class="qpop-overlay active">'
            +'<div class="qpop-box">'
            +'<div class="qpop-icon"><i class="fa-solid fa-bell" style="color:#f59e0b;animation:qpopBell .6s ease infinite alternate"></i></div>'
            +'<div class="qpop-title">Quiz is Live Now!</div>'
            +'<div class="qpop-name">'+q.title+'</div>'
            +'<div class="qpop-meta"><span>👥 '+q.group+'</span><span>⏱ '+q.duration+' min</span>'+deadline+'</div>'
            +'<div class="qpop-desc">This quiz is now open. Click Start Quiz to begin.</div>'
            +'<a href="'+q.url+'" class="qpop-btn" onclick="qpopClose('+q.id+')">▶ Start Quiz Now</a>'
            +'</div></div>';
        document.getElementById('qpop-container').insertAdjacentHTML('beforeend',html);
        document.body.classList.add('qpop-locked');
    }
    document.addEventListener('DOMContentLoaded',function(){
        fetch('/quiz/live-check',{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(quizzes){
            quizzes.forEach(function(q){
                if(isDismissed(q.id))return;
                var now=Date.now();
                if(q.unlock_ms===0||now>=q.unlock_ms){showPopup(q);}
                else{setTimeout(function(){showPopup(q);},q.unlock_ms-now);}
            });
        }).catch(function(){});
    });
})();
</script>
@endif
@endauth

<script>
setInterval(function() { fetch('/api/ping', {credentials:'same-origin'}).catch(function(){}); }, 240000);
</script>
</body>
</html>
