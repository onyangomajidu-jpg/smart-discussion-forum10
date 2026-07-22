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
            .messages { padding: 12px; gap: 10px; }
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

        .messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 14px; background: #f0f2f5; }

        /* ── Chat bubble styles ── */
        .chat-row { display: flex; align-items: flex-end; gap: 10px; }
        .chat-row.mine { flex-direction: row-reverse; }

        .chat-avatar {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; color: #fff;
            background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 2px 6px rgba(0,0,0,.15);
        }
        .chat-row.mine .chat-avatar { background: linear-gradient(135deg, #10b981, #059669); }
        .chat-row.topic-origin .chat-avatar { background: linear-gradient(135deg, #f59e0b, #d97706); }

        .chat-bubble-wrap { display: flex; flex-direction: column; max-width: 72%; }
        .chat-row.mine .chat-bubble-wrap { align-items: flex-end; }

        .chat-meta { font-size: 11px; color: #94a3b8; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
        .chat-row.mine .chat-meta { flex-direction: row-reverse; }
        .chat-meta .author { font-weight: 700; color: #475569; }
        .chat-row.mine .chat-meta .author { color: #059669; }
        .chat-row.topic-origin .chat-meta .author { color: #d97706; }

        .chat-bubble {
            background: #fff;
            border-radius: 18px 18px 18px 4px;
            padding: 11px 15px;
            font-size: 14px; color: #1e293b; line-height: 1.55;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
            word-break: break-word;
        }
        .chat-row.mine .chat-bubble {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border-radius: 18px 18px 4px 18px;
            box-shadow: 0 2px 10px rgba(102,126,234,.35);
        }
        .chat-row.topic-origin .chat-bubble {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #78350f;
            border-radius: 18px 18px 18px 4px;
            border: 1px solid #fcd34d;
        }

        .chat-actions { display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap; }
        .chat-row.mine .chat-actions { justify-content: flex-end; }
        .btn-sm { padding: 3px 9px; font-size: 11px; border: 1px solid #e2e8f0; border-radius: 20px; cursor: pointer; background: white; font-weight: 600; transition: all .15s; }
        .btn-sm:hover { background: #f1f5f9; }
        .btn-reply { color: #667eea; border-color: #c7d2fe; }
        .btn-edit   { color: #38a169; border-color: #a7f3d0; }
        .btn-delete { color: #e53e3e; border-color: #fecaca; }

        /* Replies as threaded bubbles */
        .replies { margin-top: 8px; padding-left: 44px; display: flex; flex-direction: column; gap: 8px; }
        .reply-bubble {
            background: #f8fafc;
            border-radius: 12px 12px 12px 4px;
            padding: 8px 12px;
            font-size: 13px; color: #374151;
            border-left: 3px solid #c7d2fe;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        .reply-author { font-weight: 700; font-size: 12px; color: #6366f1; margin-bottom: 2px; }
        .reply-time   { font-size: 10px; color: #94a3b8; margin-left: 6px; }

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
        /* Image / file bubbles */
        .img-msg-bubble { margin-top: 4px; border-radius: 14px; overflow: hidden; max-width: 280px; box-shadow: 0 2px 10px rgba(0,0,0,.1); cursor: pointer; }
        .img-msg-bubble img { width: 100%; display: block; }
        .chat-row.mine .img-msg-bubble { border-radius: 14px 14px 4px 14px; }
        .file-msg-bubble { display: flex; align-items: center; gap: 10px; margin-top: 4px; padding: 10px 14px; border-radius: 14px; background: #f8fafc; border: 1.5px solid #e2e8f0; max-width: 280px; }
        .chat-row.mine .file-msg-bubble { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.3); border-radius: 14px 14px 4px 14px; }
        .file-icon { font-size: 26px; flex-shrink: 0; }
        .file-info { flex: 1; min-width: 0; }
        .file-info .fname { font-size: 13px; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .chat-row.mine .file-info .fname { color: #fff; }
        .file-info .fsize { font-size: 11px; color: #94a3b8; }
        .btn-file-dl { width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; flex-shrink: 0; background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        /* Camera modal */
        .cam-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.92); z-index:600; align-items:center; justify-content:center; flex-direction:column; gap:20px; }
        .cam-modal.open { display:flex; }
        .cam-modal video { border-radius:16px; max-width:92vw; max-height:58vh; background:#000; }
        .cam-actions { display:flex; gap:14px; }
        .btn-cam-snap { width:64px; height:64px; border-radius:50%; border:4px solid white; background:white; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 16px rgba(0,0,0,.4); }
        .btn-cam-snap::after { content:''; width:52px; height:52px; border-radius:50%; background:#00a884; display:block; }
        .btn-cam-close { padding:10px 22px; background:rgba(255,255,255,.15); color:#fff; border:1.5px solid rgba(255,255,255,.4); border-radius:24px; font-size:14px; cursor:pointer; }
        /* ── Modern audio bubble ── */
        .audio-msg-bubble {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            border-radius: 20px 20px 20px 6px;
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.08);
            min-width: 240px; max-width: 320px;
            margin-top: 4px;
            border: 1px solid #f1f5f9;
        }
        .chat-row.mine .audio-msg-bubble {
            background: linear-gradient(135deg,#667eea,#764ba2);
            border-radius: 20px 20px 6px 20px;
            box-shadow: 0 4px 16px rgba(102,126,234,.4);
            border: none;
        }
        .audio-play-btn {
            width: 40px; height: 40px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0; transition: all .2s;
            background: linear-gradient(135deg,#667eea,#764ba2); color: #fff;
            box-shadow: 0 3px 10px rgba(102,126,234,.45);
        }
        .chat-row.mine .audio-play-btn {
            background: rgba(255,255,255,.22); color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
            backdrop-filter: blur(4px);
        }
        .audio-play-btn:hover { transform: scale(1.12); }
        .audio-waveform {
            flex: 1; display: flex; align-items: center; gap: 2.5px; height: 32px;
        }
        .audio-waveform span {
            display: inline-block; width: 3px; border-radius: 4px;
            background: #e2e8f0; transition: background .25s;
            transform-origin: center;
        }
        .chat-row.mine .audio-waveform span { background: rgba(255,255,255,.35); }
        .audio-waveform.playing span { background: #667eea; animation: waveAnim .55s ease-in-out infinite alternate; }
        .chat-row.mine .audio-waveform.playing span { background: rgba(255,255,255,.9); }
        .audio-waveform span:nth-child(2n)   { animation-delay: .08s; }
        .audio-waveform span:nth-child(3n)   { animation-delay: .18s; }
        .audio-waveform span:nth-child(4n)   { animation-delay: .12s; }
        .audio-waveform span:nth-child(5n)   { animation-delay: .22s; }
        .audio-waveform span:nth-child(7n)   { animation-delay: .05s; }
        @keyframes waveAnim {
            from { transform: scaleY(.3); opacity: .7; }
            to   { transform: scaleY(1.3); opacity: 1; }
        }
        .audio-duration { font-size: 11px; font-weight: 700; color: #94a3b8; min-width: 34px; text-align: right; font-variant-numeric: tabular-nums; }
        .chat-row.mine .audio-duration { color: rgba(255,255,255,.75); }
        .audio-label { font-size: 10px; font-weight: 600; color: #a0aec0; letter-spacing: .4px; text-transform: uppercase; }
        .chat-row.mine .audio-label { color: rgba(255,255,255,.6); }

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
        <button class="mobile-toggle-btn" id="topicsToggleBtn" type="button" aria-label="Toggle topics list">☰</button>
        <h1>
            <img src="{{ asset('images/forum.png') }}" alt="Discussion Hub" style="height:30px;vertical-align:middle;flex-shrink:0">
            <span class="full-title">Discussion Hub</span>
        </h1>
    </div>
    <div class="navbar-right">
        <button class="notif-btn" onclick="loadNotifications()">
            🔔
            @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="notif-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
            @endif
        </button>
        <span class="nav-username">{{ auth()->user()->name }}</span>
        <a href="{{ route('lecturer.dashboard') }}" class="btn-logout">← <span>Dashboard</span></a>
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
            <div class="sidebar-title">📚 Topics</div>
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
                                <span class="topic-stat">💬 {{ $topic->posts_count }}</span>
                                <span class="topic-stat">👁 {{ $topic->views }}</span>
                            </div>
                            @if($topic->is_pinned || $topic->is_locked)
                                <div class="topic-badges">
                                    @if($topic->is_pinned) <span class="badge badge-pinned">📌 Pinned</span> @endif
                                    @if($topic->is_locked) <span class="badge badge-locked">🔒 Locked</span> @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    @if(auth()->id() === $topic->user_id)
                        <form action="{{ route('lecturer.topics.destroy', $topic) }}" method="POST"
                              onsubmit="return confirm('Delete this topic?')" onclick="event.stopPropagation()">
                            @csrf @method('DELETE')
                            <button type="submit" class="topic-delete-btn">🗑 Delete</button>
                        </form>
                    @endif
                </div>
            @empty
                <div style="padding:40px 20px;text-align:center;color:rgba(255,255,255,0.3);font-size:13px;">
                    <div style="font-size:32px;margin-bottom:8px;">📭</div>
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
                        @if($activeTopic->is_pinned) 📌 @endif
                        @if($activeTopic->is_locked) 🔒 @endif
                        {{ $activeTopic->title }}
                    </h2>
                    <div class="conv-header-meta">
                        Started by {{ $activeTopic->author->name }} · {{ $activeTopic->created_at->diffForHumans() }}
                        · {{ $activeTopic->views }} views
                    </div>
                </div>
                <div class="conv-header-actions">
                    <form action="{{ route('lecturer.topics.pin', $activeTopic) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-action btn-pin">
                            {{ $activeTopic->is_pinned ? '📌 Unpin' : '📌 Pin' }}
                        </button>
                    </form>
                    <form action="{{ route('lecturer.topics.lock', $activeTopic) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn-action btn-lock">
                            {{ $activeTopic->is_locked ? '🔓 Unlock' : '🔒 Lock' }}
                        </button>
                    </form>
                    <button class="btn-action btn-share" onclick="openShareModal({{ $activeTopic->id }})">
                        🌐 Share
                    </button>
                    <button class="mobile-toggle-btn" id="participantsToggleBtn" type="button" aria-label="Toggle participants list" style="background:#ede9fe;color:#4c1d95;">
                        👥
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
                    <div class="chat-avatar">{{ strtoupper(substr($activeTopic->author->name,0,1)) }}</div>
                    <div class="chat-bubble-wrap">
                        <div class="chat-meta">
                            <span class="author">{{ $activeTopic->author->name }}</span>
                            <span>{{ $activeTopic->created_at->diffForHumans() }}</span>
                            <span style="background:#fde68a;color:#92400e;font-size:10px;padding:1px 7px;border-radius:10px;font-weight:700">Topic</span>
                        </div>
                        <div class="chat-bubble">{{ $activeTopic->body }}</div>
                    </div>
                </div>

                @foreach($posts as $post)
                @php $isMe = $post->user_id === auth()->id(); @endphp
                <div class="chat-row {{ $isMe ? 'mine' : '' }}" id="post-{{ $post->id }}">
                    @if(!$isMe)<div class="chat-avatar">{{ strtoupper(substr($post->author->name,0,1)) }}</div>@endif
                    <div class="chat-bubble-wrap">
                        <div class="chat-meta">
                            <span class="author">{{ $isMe ? 'You' : $post->author->name }}</span>
                            <span>{{ $post->created_at->diffForHumans() }}</span>
                            @if(!$isMe)
                                <a href="{{ route('messages.show', $post->user_id) }}" class="btn-sm btn-reply" style="text-decoration:none;" title="Message {{ $post->author->name }} privately">&#9993; Message</a>
                            @endif
                        </div>
                        <div class="chat-bubble" id="post-body-{{ $post->id }}">{{ $post->body }}</div>
                        @if($post->image_path)
                            <div class="img-msg-bubble">
                                <img src="{{ asset('storage/' . $post->image_path) }}" alt="Image" loading="lazy">
                            </div>
                        @endif
                        @if($post->file_path)
                            <div class="file-msg-bubble">
                                <span class="file-icon">&#128196;</span>
                                <div class="file-info">
                                    <div class="fname">{{ $post->file_name ?? 'Document' }}</div>
                                    <div class="fsize">Attachment</div>
                                </div>
                                <a href="{{ asset('storage/' . $post->file_path) }}" download="{{ $post->file_name }}" class="btn-file-dl" title="Download">&#8595;</a>
                            </div>
                        @endif
                        @if($post->audio_path)
                        @php $heights = [8,14,20,28,22,16,26,18,10,24,20,14,22,8,18,26,12,20,30,14]; @endphp
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
                        @endif
                        <div class="chat-actions">
                            <button class="btn-sm btn-reply" onclick="toggleReplyForm({{ $post->id }})">&#8617; Reply</button>
                            @if($post->user_id === auth()->id())
                                <button class="btn-sm btn-edit" onclick="editPost({{ $post->id }}, `{{ addslashes($post->body) }}`)">&#9998; Edit</button>
                                <button class="btn-sm btn-delete" onclick="deletePost({{ $post->id }})">&#128465; Delete</button>
                            @endif
                        </div>
                        <form id="reply-form-{{ $post->id }}" style="display:none;margin-top:8px;" action="{{ route('lecturer.topics.answer', $post->id) }}" method="POST">
                            @csrf
                            <div style="display:flex;gap:8px;">
                                <input type="text" name="body" placeholder="Write a reply..." class="msg-input" style="flex:1;padding:8px 12px;min-width:0">
                                <button type="submit" class="btn-send" style="padding:8px 14px;">Send</button>
                            </div>
                        </form>
                        @if($post->replies->count())
                        <div class="replies">
                            @foreach($post->replies as $reply)
                            <div class="reply-bubble">
                                <div class="reply-author">{{ $reply->author->name }}<span class="reply-time">{{ $reply->created_at->diffForHumans() }}</span></div>
                                <div>{{ $reply->body }}</div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @if($isMe)<div class="chat-avatar" style="background:linear-gradient(135deg,#10b981,#059669)">{{ strtoupper(substr($post->author->name,0,1)) }}</div>@endif
                </div>
                @endforeach
            </div>

            <div class="typing-indicator" id="typingIndicator"></div>

            @if(!$activeTopic->is_locked)
                <div class="input-area">
                    <form action="{{ route('lecturer.topics.participate', $activeTopic->id) }}" method="POST" id="postForm" enctype="multipart/form-data">
                        @csrf
                        <input type="file" id="imgInput" name="image" accept="image/*" style="display:none">
                        <input type="file" id="docInput" name="file" style="display:none">
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
                                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();document.getElementById('postForm').requestSubmit();}"></textarea>
                            <button type="button" class="bar-icon-right" id="micBtn" title="Record audio">
                                <svg id="micIcon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm-1-9c0-.55.45-1 1-1s1 .45 1 1v6c0 .55-.45 1-1 1s-1-.45-1-1V5zm6 6c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/></svg>
                                <svg id="sendIcon" viewBox="0 0 24 24" fill="currentColor" style="display:none"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                            </button>
                        </div>
                        <div class="audio-preview" id="audioPreview">
                            <button type="button" class="btn-discard" id="discardAudio" title="Discard">&#10005;</button>
                            <span class="rec-timer" id="recTimer">0:00</span>
                            <div style="flex:1;display:flex;align-items:center;gap:2px;height:28px">
                                @foreach([10,16,22,28,20,14,24,18,12,26,20,16,22,10,18,24,14,20,28,16] as $h)
                                <span style="display:inline-block;width:3px;border-radius:3px;background:#c7d2fe;height:{{ $h }}px"></span>
                                @endforeach
                            </div>
                            <button type="button" class="btn-send-audio" id="sendAudioBtn" title="Send voice message">&#9658;</button>
                        </div>
                    </form>
                </div>
            @else
                <div style="padding:14px 20px;background:#fff3cd;text-align:center;font-size:14px;color:#856404;">
                    🔒 This topic is locked. Unlock it to allow messages.
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

        <div class="section-label">Active</div>
        @forelse($activeTopic->participants as $participant)
            <div class="participant-item">
                <span class="participant-name">{{ $participant->name }}</span>
                @if($participant->id === $activeTopic->user_id)
                    <span style="font-size:11px;color:#667eea;">creator</span>
                @elseif(auth()->id() === $activeTopic->user_id)
                    <div class="participant-actions">
                        <form action="{{ route('lecturer.topics.removeUser', [$activeTopic, $participant->id]) }}" method="POST"
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


    </aside>
    @endif
</div>

{{-- Create Topic Modal --}}
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <h3>Create New Topic</h3>
        <form action="{{ route('lecturer.topics.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="Topic title...">
            </div>
            <div class="form-group">
                <label>Body</label>
                <textarea name="body" rows="4" required placeholder="Describe your topic..."></textarea>
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
            <textarea id="editBody" rows="4" style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:7px;font-size:14px;font-family:inherit;outline:none;"></textarea>
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
            <button class="share-card" data-platform="whatsapp" onclick="selectSharePlatform(this)">
                <span style="font-size:22px;">💬</span> WhatsApp
            </button>
            <button class="share-card" data-platform="twitter" onclick="selectSharePlatform(this)">
                <span style="font-size:22px;">𝕏</span> Twitter / X
            </button>
            <button class="share-card" data-platform="facebook" onclick="selectSharePlatform(this)">
                <span style="font-size:22px;">📘</span> Facebook
            </button>
            <button class="share-card" data-platform="linkedin" onclick="selectSharePlatform(this)">
                <span style="font-size:22px;">💼</span> LinkedIn
            </button>
        </div>
        <div id="shareStatus"></div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="document.getElementById('shareModal').classList.remove('open')">Cancel</button>
            <button class="btn-submit" id="shareBtn" onclick="submitShare()" disabled style="opacity:0.5;">🚀 Share Now</button>
        </div>
    </div>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let editingPostId = null;
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

    function openShareModal(topicId) {
        selectedPlatform = null;
        document.getElementById('shareStatus').textContent = '';
        document.getElementById('shareBtn').disabled = true;
        document.getElementById('shareBtn').style.opacity = '0.5';
        document.querySelectorAll('.share-card').forEach(c => { c.style.borderColor = '#e2e8f0'; c.style.background = 'white'; });
        document.getElementById('shareModal').classList.add('open');
    }

    function selectSharePlatform(btn) {
        document.querySelectorAll('.share-card').forEach(c => { c.style.borderColor = '#e2e8f0'; c.style.background = 'white'; });
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
        const conversation = buildConversationText();
        const topicUrl  = encodeURIComponent(window.location.href);
        const text      = encodeURIComponent(conversation);
        const shortText = encodeURIComponent('📚 "' + document.querySelector('.conv-header h2').textContent.trim() + '" — join the discussion on Discussion Hub');
        const urls = {
            whatsapp: 'https://wa.me/?text=' + text,
            twitter:  'https://twitter.com/intent/tweet?text=' + shortText + '&url=' + topicUrl,
            facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + topicUrl + '&quote=' + text,
            linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + topicUrl,
        };
        window.open(urls[selectedPlatform], '_blank', 'noopener,noreferrer');
        const statusEl = document.getElementById('shareStatus');
        statusEl.style.color = '#276749';
        statusEl.textContent = '✅ ' + selectedPlatform.charAt(0).toUpperCase() + selectedPlatform.slice(1) + ' opened in a new tab.';
    }

    function toggleReplyForm(postId) {
        const form = document.getElementById('reply-form-' + postId);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
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
                alert(data.error || 'Failed to update post.');
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
            else alert(data.error || 'Failed to delete post.');
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
                const badge = document.querySelector('.notif-badge');
                if (badge) badge.remove();
            });
    }

    @if(isset($activeTopic))
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof window.Echo === 'undefined') return;
        const ch = window.Echo.channel('topic.{{ $activeTopic->id }}');
        ch.listen('.new.post', (e) => {
            if (e.type !== 'post') return;
            const msgs = document.getElementById('messages');
            const div = document.createElement('div');
            div.className = 'post-card';
            div.innerHTML = `<div class="post-header"><span class="post-author">User #${e.userId}</span><span class="post-time">just now</span></div><div class="post-body">${e.body}</div>`;
            msgs.appendChild(div);
            msgs.scrollTop = msgs.scrollHeight;
        });
        ch.listenForWhisper('typing', (e) => {
            const el = document.getElementById('typingIndicator');
            el.innerHTML = `${e.name} is typing <span class="typing-dots"><span></span><span></span><span></span></span>`;
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => el.innerHTML = '', 2000);
        });
    });
    @endif

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
    });

    const msgs = document.getElementById('messages');
    if (msgs) msgs.scrollTop = msgs.scrollHeight;

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
        audio.addEventListener('error', function() { durEl.textContent = 'err'; });
    });

    function toggleAudio(btn) {
        const bubble = btn.closest('.audio-msg-bubble');
        const audio  = bubble.querySelector('audio');
        const wave   = bubble.querySelector('.audio-waveform');
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
        const show = !!val;
        const mi = document.getElementById('micIcon');
        const si = document.getElementById('sendIcon');
        if (mi) mi.style.display = show ? 'none'  : 'block';
        if (si) si.style.display = show ? 'block' : 'none';
    }
    function onMsgInput() { updateSendBtn(); }

    // standalone send handler (fires when micBtn is clicked while text is present)
    document.getElementById('micBtn') && document.getElementById('micBtn').addEventListener('click', function () {
        const val = document.getElementById('postInput') && document.getElementById('postInput').value.trim();
        if (val) { document.getElementById('postForm').requestSubmit(); }
    });

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

        function sendAttachment(formEl) {
            const fd = new FormData(formEl);
            fd.delete('body');
            fetch(formEl.action, { method: 'POST', body: fd })
                .then(r => r.redirected ? window.location.href = r.url : window.location.reload());
        }

        imgBtn.addEventListener('click', () => imgInput.click());
        docBtn.addEventListener('click', () => docInput.click());
        removeBtn.addEventListener('click', clearPreview);

        imgInput.addEventListener('change', function () {
            if (!this.files[0]) return;
            sendAttachment(document.getElementById('postForm'));
        });
        docInput.addEventListener('change', function () {
            if (!this.files[0]) return;
            sendAttachment(document.getElementById('postForm'));
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
                sendAttachment(document.getElementById('postForm'));
            }, 'image/jpeg', 0.92);
        });
    })();

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
                // browser actually supports instead of assuming webm.
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
                    // used, not a hardcoded guess.
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
</body>
</html>
