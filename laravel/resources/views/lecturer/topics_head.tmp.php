<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/forum-favicon.png') }}">
    <title>Topic Participation - Lecturer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; flex-direction: column; height: 100vh; }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0 16px; height: 58px; color: white;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15); flex-shrink: 0;
        }
        .navbar h1 { font-size: 17px; display:flex; align-items:center; gap:8px; }
        .navbar-right { display: flex; align-items: center; gap: 8px; flex-shrink:0; }
        .notif-btn { background: rgba(255,255,255,0.2); border: none; color: white; padding: 6px 10px; border-radius: 6px; cursor: pointer; position: relative; }
        .notif-badge { position: absolute; top: -4px; right: -4px; background: #e53e3e; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 11px; display: flex; align-items: center; justify-content: center; }
        .btn-logout { background: rgba(255,255,255,0.2); padding: 6px 11px; border: 1px solid rgba(255,255,255,.5); border-radius: 6px; color: white; cursor: pointer; text-decoration: none; font-size: 13px; white-space:nowrap; }
        .nav-username { font-size:13px; font-weight:600; max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

        .forum-layout { display: flex; flex: 1; overflow: hidden; }

        .sidebar { width: 300px; background: white; border-right: 1px solid #e2e8f0; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar-header { padding: 18px 16px 14px; border-bottom: 1px solid #e2e8f0; }
        .sidebar-title { font-size: 11px; font-weight: 700; color: #a0aec0; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 12px; }
        .search-bar { width: 100%; padding: 9px 12px 9px 36px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; outline: none; background: #f7fafc; color: #2d3748; }
        .search-bar::placeholder { color: #a0aec0; }
        .search-bar:focus { border-color: #667eea; background: white; }
        .search-wrap { position: relative; margin-bottom: 12px; }
        .search-wrap::before { content: 'ðŸ”'; position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; pointer-events: none; }
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
        .topic-avatar.locked { background: linear-gradient(135deg, #f87171, #ef4444); }
        .topic-avatar.pinned { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
        .topic-content { flex: 1; min-width: 0; }
        .topic-item h4 { font-size: 13px; font-weight: 600; color: #2d3748; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topic-item.active h4 { color: #4c1d95; }
        .topic-author { font-size: 11px; color: #a0aec0; margin-bottom: 5px; }
        .topic-stats { display: flex; gap: 10px; }
        .topic-stat { display: flex; align-items: center; gap: 3px; font-size: 11px; color: #718096; }
        .topic-badges { display: flex; gap: 4px; margin-top: 5px; }
        .badge { font-size: 10px; padding: 2px 7px; border-radius: 20px; font-weight: 700; }
        .badge-locked { background: #fed7d7; color: #9b2c2c; }
        .badge-pinned { background: #fefcbf; color: #744210; }
        .topic-delete-btn { position: absolute; top: 10px; right: 10px; opacity: 0; font-size: 11px; color: #e53e3e; background: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px; padding: 2px 7px; cursor: pointer; transition: opacity 0.15s; }
        .topic-item:hover .topic-delete-btn { opacity: 1; }
        .topics-count { padding: 6px 20px 2px; font-size: 11px; color: #a0aec0; font-weight: 600; }

        .participants-panel { width: 230px; background: white; border-left: 1px solid #e2e8f0; display: flex; flex-direction: column; flex-shrink: 0; overflow-y: auto; }
        .participants-panel h4 { padding: 14px 16px; font-size: 13px; font-weight: 700; color: #4a5568; border-bottom: 1px solid #e2e8f0; margin: 0; }
        .section-label { padding: 8px 14px 4px; font-size: 11px; font-weight: 700; color: #a0aec0; text-transform: uppercase; letter-spacing: .5px; }
        .participant-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; border-bottom: 1px solid #f0f2f5; font-size: 13px; color: #2d3748; gap: 6px; }
        .participant-item.blocked-item { background: #fff5f5; color: #9b2c2c; }
        .participant-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .participant-actions { display: flex; gap: 4px; flex-shrink: 0; }
        .btn-remove-user { font-size: 11px; color: #e53e3e; background: none; border: 1px solid #e53e3e; border-radius: 4px; padding: 2px 6px; cursor: pointer; }
        .btn-block-user { font-size: 11px; color: #d69e2e; background: none; border: 1px solid #d69e2e; border-radius: 4px; padding: 2px 6px; cursor: pointer; }
        .btn-unblock-user { font-size: 11px; color: #38a169; background: none; border: 1px solid #38a169; border-radius: 4px; padding: 2px 6px; cursor: pointer; }

        /* Mobile toggle buttons â€” hidden on desktop */
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
            .navbar h1 span.full-title { display: none; } 
            .nav-username { display: none; }
            .btn-logout span { display: none; }

            .sidebar, .participants-panel {
                position: fixed; top: 0; height: 100vh; z-index: 500;
                transition: transform .25s ease;
            }
            .sidebar { left: 0; transform: translateX(-100%); width: 85%; max-width: 300px; }
            .sidebar.open { transform: translateX(0); }
            .participants-panel { right: 0; transform: translateX(100%); width: 80%; max-width: 260px; }
            .participants-panel.open { transform: translateX(0); }

            .conversation { width: 100%; min-width: 0; }
            .conv-header { flex-direction: column; align-items: flex-start; gap: 8px; padding: 10px 12px; }
            .conv-header h2 { font-size: 15px; }
            .conv-header-actions { flex-wrap: wrap; gap: 6px; }
            .messages { padding: 12px; gap: 4px; }
            .input-area { padding: 10px 12px; }
            .modal { width: 95vw; padding: 20px 16px; }
        }

        .conversation { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .conv-header { padding: 16px 20px; background: white; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .conv-header h2 { font-size: 18px; color: #2d3748; }
        .conv-header-meta { font-size: 13px; color: #718096; }
        .conv-header-actions { display: flex; gap: 8px; }
        .btn-action { padding: 6px 12px; font-size: 12px; border-radius: 6px; cursor: pointer; font-weight: 600; border: none; }
        .btn-lock { background: #fed7d7; color: #9b2c2c; }
        .btn-pin { background: #fefcbf; color: #744210; }
        .btn-del-topic { background: #fed7d7; color: #9b2c2c; }

        .messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 4px; background: #f0f2f5; }

        /* â”€â”€ Chat bubble styles (WhatsApp group) â”€â”€ */
        .chat-row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 0; }
        .chat-row.mine { flex-direction: row-reverse; }

        .chat-avatar {
            width: 26px; height: 26px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 800; color: #fff;
            background: linear-gradient(135deg,#667eea,#764ba2);
            overflow: hidden; align-self: flex-end;
            box-shadow: 0 1px 4px rgba(0,0,0,.18);
            border: 1.5px solid rgba(255,255,255,.7);
            margin-bottom: 2px;
        }
        .chat-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .chat-row.mine .chat-avatar { background: linear-gradient(135deg,#25d366,#128c7e); }
        .chat-row.topic-origin .chat-avatar { background: linear-gradient(135deg,#f59e0b,#d97706); }

        .chat-bubble-wrap { display: flex; flex-direction: column; max-width: 68%; min-width: 0; overflow: hidden; }
        .chat-row.mine .chat-bubble-wrap { align-items: flex-end; }

        .bubble-author {
            font-size: 12.5px; font-weight: 700;
            margin-bottom: 2px; display: block; line-height: 1.3;
        }
        .chat-row.mine .bubble-author { display: none; }
        a.bubble-author { text-decoration: none; cursor: pointer; }
        a.bubble-author:hover { text-decoration: underline; }

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
            padding: 7px 10px;
            font-size: 14px; color: #111b21; line-height: 1.55;
            box-shadow: 0 1px 2px rgba(0,0,0,.13);
            word-break: break-word;
            overflow-wrap: break-word;
            overflow: hidden;
            min-width: 0;
            width: 100%;
        }
        .chat-row.mine .chat-bubble {
            background: #d9fdd3; color: #111b21;
            border-radius: 8px 8px 2px 8px;
        }
        .chat-row.topic-origin .chat-bubble {
            background: #fef9c3; color: #78350f;
            border-radius: 8px 8px 8px 2px;
            border: 1px solid #fcd34d;
        }

        .reply-quote {
            border-left: 3px solid #667eea;
            background: rgba(0,0,0,.06);
            border-radius: 6px; padding: 5px 9px;
            margin-bottom: 5px; font-size: 12px; cursor: pointer;
        }
        .reply-quote .rq-author { font-weight: 700; color: #667eea; margin-bottom: 2px; font-size: 11px; }
        .reply-quote .rq-body   { color: #4a5568; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; box-sizing: border-box; }
        .chat-row.mine .reply-quote { border-color: #6abf8a; background: rgba(0,0,0,.07); }
        .chat-row.mine .reply-quote .rq-author { color: #2d7a4f; }
        .chat-row.mine .reply-quote .rq-body   { color: #374151; }

        .reply-bar {
            display: none; align-items: center; gap: 8px;
            padding: 8px 14px; background: #e8e6ff; border-radius: 12px; margin-bottom: 6px;
            font-size: 13px; color: #4a5568;
        }
        .reply-bar .rb-author { font-weight: 700; color: #667eea; }
        .reply-bar .rb-body { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .btn-cancel-reply { background: none; border: none; color: #a0aec0; cursor: pointer; font-size: 18px; flex-shrink: 0; line-height: 1; }
        .btn-cancel-reply:hover { color: #e53e3e; }

        .chat-actions { display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap; opacity: 0; transition: opacity .15s; pointer-events: none; clear: both; }
        .chat-row.selected .chat-actions { opacity: 1; pointer-events: auto; }
        .chat-row.mine .chat-actions { justify-content: flex-end; }
        .btn-sm { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 14px; border: 1px solid #e2e8f0; border-radius: 50%; cursor: pointer; background: white; transition: all .15s; padding: 0; }
        .btn-sm:hover { background: #f1f5f9; transform: scale(1.1); }
        .btn-reply { color: #667eea; border-color: #c7d2fe; }
        .btn-edit   { color: #38a169; border-color: #a7f3d0; }
        .btn-delete { color: #e53e3e; border-color: #fecaca; }

        .typing-indicator { padding: 6px 20px; font-size: 13px; color: #718096; font-style: italic; min-height: 28px; }
        .typing-dots span { display: inline-block; width: 6px; height: 6px; background: #718096; border-radius: 50%; margin: 0 2px; animation: bounce 1.2s infinite; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

        /* â”€â”€ WhatsApp-style input bar â”€â”€ */
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
        /* â”€â”€ Audio bubble â”€â”€ */
        .audio-msg-bubble {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px;
            border-radius: 8px 8px 8px 2px;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0,0,0,.1);
            min-width: 220px; max-width: 300px;
            border: 1px solid #f1f5f9;
        }
        .syndicate-row { margin-top: 8px; display: flex; align-items: center; gap: 8px; font-size: 13px; color: #718096; }
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
        .chat-row.mine .audio-play-btn { background: #25d366; }
        .audio-play-btn:hover { transform: scale(1.12); }
        .audio-waveform { flex: 1; display: flex; align-items: center; gap: 2.5px; height: 32px; }
        .audio-waveform span {
            display: inline-block; width: 3px; border-radius: 4px;
            background: #c8d8d0; transition: background .25s; transform-origin: center;
        }
        .chat-row.mine .audio-waveform span { background: #8abfa8; }
        .audio-waveform.playing span { background: #00a884; animation: waveAnim .55s ease-in-out infinite alternate; }
        .chat-row.mine .audio-waveform.playing span { background: #25d366; }
        .audio-waveform span:nth-child(2n) { animation-delay: .08s; }
        .audio-waveform span:nth-child(3n) { animation-delay: .18s; }
        .audio-waveform span:nth-child(4n) { animation-delay: .12s; }
        .audio-waveform span:nth-child(5n) { animation-delay: .22s; }
        .audio-waveform span:nth-child(7n) { animation-delay: .05s; }
        @keyframes waveAnim { from { transform: scaleY(.3); opacity: .7; } to { transform: scaleY(1.3); opacity: 1; } }
        .audio-duration { font-size: 11px; font-weight: 700; color: #8696a0; min-width: 34px; text-align: right; font-variant-numeric: tabular-nums; }
        .chat-row.mine .audio-duration { color: #6a9f7a; }
        .audio-label { font-size: 10px; font-weight: 600; color: #a0aec0; letter-spacing: .4px; text-transform: uppercase; }
        .chat-row.mine .audio-label { color: #5a8a6a; }
        .audio-bubble-footer { display: flex; justify-content: flex-end; font-size: 11px; color: #8696a0; margin-top: 2px; }
        .chat-row.mine .audio-bubble-footer { color: #6a9f7a; }

        .empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #a0aec0; }
        .empty-state p { margin-top: 10px; font-size: 15px; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 28px; width: 480px; max-width: 95vw; }
        .modal h3 { margin-bottom: 18px; color: #2d3748; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #4a5568; margin-bottom: 5px; }
        .form-group input, .form-group textarea { width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 7px; font-size: 14px; font-family: inherit; outline: none; }
        .form-group input:focus, .form-group textarea:focus { border-color: #667eea; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
        .btn-cancel { padding: 8px 18px; border: 1px solid #e2e8f0; border-radius: 7px; cursor: pointer; background: white; }
        .btn-submit { padding: 8px 18px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 7px; cursor: pointer; font-weight: 600; }

        .btn-share { background: linear-gradient(135deg,#25d366,#128c7e); color: white; border: none; }
        .share-card { display:flex; align-items:center; gap:10px; padding:12px 14px; border:2px solid #e2e8f0; border-radius:10px; background:white; cursor:pointer; font-size:14px; font-weight:600; color:#2d3748; width:100%; }
        #shareStatus { font-size:13px; min-height:20px; margin-bottom:8px; }
    </style>
</head>
<body>

<nav class="navbar">
    <div style="display:flex;align-items:center;gap:8px;min-width:0">
        <button class="mobile-toggle-btn" id="topicsToggleBtn" type="button" aria-label="Toggle topics list">â˜°</button>
        <h1>
            <img src="{{ asset('images/forum.png') }}" alt="Discussion Hub" style="height:30px;vertical-align:middle;flex-shrink:0">
            <span class="full-title">Discussion Hub</span>
        </h1>
    </div>
    <div class="navbar-right">
        <button class="notif-btn" onclick="loadNotifications()">
            ðŸ””
            @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="notif-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
            @endif
        </button>
        <span class="nav-username">{{ auth()->user()->name }}</span>
        <a href="{{ route('lecturer.dashboard') }}" class="btn-logout">â† <span>Dashboard</span></a>
        <form action="{{ route('logout') }}" method="POST" style="margin:0">
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
            <div class="sidebar-title">ðŸ“š Topics</div>
            <form method="GET" action="{{ route('lecturer.topics.index') }}">
                <div class="search-wrap">
                    <input type="text" name="search" class="search-bar" placeholder="Search topics..."
                        value="{{ request('search') }}" oninput="this.form.submit()">
                </div>
            </form>
            <button class="btn-create" onclick="document.getElementById('createModal').classList.add('open')">
                + New Topic
            </button>
        </div>
        <div class="topics-count">{{ $topics->count() }} topic{{ $topics->count() !== 1 ? 's' : '' }}</div>
        <div class="topic-list">
            @forelse($topics as $topic)
                @php $initials = strtoupper(substr($topic->title, 0, 2)); @endphp
                <div class="topic-item {{ isset($activeTopic) && $activeTopic->id === $topic->id ? 'active' : '' }}"
                     onclick="window.location='{{ route('lecturer.topics.show', $topic) }}'">
                    <div class="topic-item-inner">
                        <div class="topic-avatar {{ $topic->is_locked ? 'locked' : ($topic->is_pinned ? 'pinned' : '') }}">{{ $initials }}</div>
                        <div class="topic-content">
                            <h4>{{ $topic->title }}</h4>
                            <div class="topic-author">by {{ $topic->author->name }}</div>
                            <div class="topic-stats">
                                <span class="topic-stat">ðŸ’¬ {{ $topic->posts_count }}</span>
                                <span class="topic-stat">ðŸ‘ {{ $topic->views }}</span>
                            </div>
                            @if($topic->is_pinned || $topic->is_locked)
                                <div class="topic-badges">
                                    @if($topic->is_pinned) <span class="badge badge-pinned">ðŸ“Œ Pinned</span> @endif
                                    @if($topic->is_locked) <span class="badge badge-locked">ðŸ”’ Locked</span> @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    @if(auth()->id() === $topic->user_id)
                        <form action="{{ route('lecturer.topics.destroy', $topic) }}" method="POST"
                              onsubmit="return confirm('Delete this topic?')" onclick="event.stopPropagation()">
                            @csrf @method('DELETE')
                            <button type="submit" class="topic-delete-btn">ðŸ—‘ Delete</button>
                        </form>
                    @endif
                </div>
            @empty
                <div style="padding:40px 20px;text-align:center;color:rgba(255,255,255,0.3);font-size:13px;">
                    <div style="font-size:32px;margin-bottom:8px;">ðŸ“­</div>
                    No topics yet.
                </div>
            @endforelse
        </div>
    </aside>

    {{-- Conversation Panel --}}
    <main class="conversation">
        @if(isset($activeTopic))
            <div class="conv-header">
                <div>
                    <h2>
                        @if($activeTopic->is_pinned) ðŸ“Œ @endif
                        @if($activeTopic->is_locked) ðŸ”’ @endif
                        {{ $activeTopic->title }}
                    </h2>
                    <div class="conv-header-meta">
                        Started by {{ $activeTopic->author->name }} Â· {{ $activeTopic->created_at->diffForHumans() }}
                        Â· {{ $activeTopic->views }} views
                    </div>
                </div>
                <div class="conv-header-actions">
                    <form action="{{ route('lecturer.topics.pin', $activeTopic) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-action btn-pin">
                            {{ $activeTopic->is_pinned ? 'ðŸ“Œ Unpin' : 'ðŸ“Œ Pin' }}
                        </button>
                    </form>
                    <form action="{{ route('lecturer.topics.lock', $activeTopic) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-action btn-lock">
                            {{ $activeTopic->is_locked ? 'ðŸ”“ Unlock' : 'ðŸ”’ Lock' }}
                        </button>
                    </form>
                    <button class="btn-action btn-share" onclick="openShareModal({{ $activeTopic->id }})">
                        ðŸŒ Share
                    </button>
                    <button onclick="clearTopicChat({{ $activeTopic->id }})" title="Clear chat on this device only" style="padding:6px 12px;background:#f1f5f9;color:#64748b;border:1.5px solid #e2e8f0;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">&#128465; Clear Chat</button>
                    <button class="mobile-toggle-btn" id="participantsToggleBtn" type="button" aria-label="Toggle participants list" style="background:#ede9fe;color:#4c1d95;">
                        ðŸ‘¥
                    </button>
                </div>
            </div>

            <div class="messages" id="messages">
                @if(session('success'))
                    <div style="background:#d1fae5;color:#065f46;padding:10px 14px;border-radius:8px;font-size:13px">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px">{{ $errors->first() }}</div>
                @endif

                {{-- Topic origin bubble --}}
                <div class="chat-row topic-origin">
                    <div class="chat-avatar">
                        @if($activeTopic->author->avatar)
                            <img src="{{ storage_url($activeTopic->author->avatar) }}" alt="">
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

                @foreach($posts as $post)
