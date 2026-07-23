<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/forum-favicon.png') }}">
    <title>Messages - Discussion Hub</title>
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

        /* Sidebar (conversation list) */
        .sidebar {
            width: 300px; background: white; border-right: 1px solid #e2e8f0;
            display: flex; flex-direction: column; flex-shrink: 0;
        }
        .sidebar-header { padding: 18px 16px 14px; border-bottom: 1px solid #e2e8f0; }
        .sidebar-title { font-size: 11px; font-weight: 700; color: #a0aec0; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 12px; }
        .search-bar { width: 100%; padding: 9px 12px 9px 36px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; outline: none; background: #f7fafc; color: #2d3748; }
        .search-bar::placeholder { color: #a0aec0; }
        .search-bar:focus { border-color: #667eea; background: white; }
        .search-wrap { position: relative; margin-bottom: 0; }
        .search-wrap::before { content: '🔍'; position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; pointer-events: none; }
        .topic-list { flex: 1; overflow-y: auto; padding: 8px 0; }
        .topic-list::-webkit-scrollbar { width: 4px; }
        .topic-list::-webkit-scrollbar-track { background: transparent; }
        .topic-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }
        .topic-item { margin: 4px 10px; border-radius: 12px; padding: 12px 14px; cursor: pointer; transition: background 0.18s; position: relative; }
        .topic-item:hover { background: #f0f0ff; }
        .topic-item.active { background: #ede9fe; box-shadow: inset 0 0 0 1px #c4b5fd; }
        .topic-item-inner { display: flex; gap: 11px; align-items: flex-start; }
        .topic-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: white; flex-shrink: 0; text-transform: uppercase; }
        .topic-content { flex: 1; min-width: 0; }
        .topic-item h4 { font-size: 13px; font-weight: 600; color: #2d3748; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topic-item.active h4 { color: #4c1d95; }
        .topic-author { font-size: 12px; color: #718096; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topics-count { padding: 6px 20px 2px; font-size: 11px; color: #a0aec0; font-weight: 600; }
        .unread-pill { background: #667eea; color: #fff; font-size: 10px; font-weight: 800; border-radius: 10px; padding: 1px 7px; flex-shrink: 0; }
        .conv-time { font-size: 10px; color: #a0aec0; flex-shrink: 0; }

        /* Mobile toggle button — hidden on desktop */
        .mobile-toggle-btn {
            display: none; background: rgba(255,255,255,0.2); border: none; color: white;
            width: 34px; height: 34px; border-radius: 6px; align-items: center; justify-content: center;
            font-size: 15px; cursor: pointer; flex-shrink: 0;
        }

        @media (max-width: 768px) {
            .mobile-toggle-btn { display: flex; }
            .navbar { padding: 8px 10px; gap: 6px; flex-wrap: nowrap; }
            .navbar h1 { font-size: 15px; }
            .navbar h1 span.full-title { display: none; }
            .navbar-right { gap: 6px; }
            .navbar-right > span { display: none; }
            .btn-logout { padding: 5px 8px; font-size: 12px; }
            .notif-btn { padding: 5px 8px; }
            .sidebar { position: fixed; top: 0; left: 0; height: 100vh; z-index: 500; transition: transform .25s ease; transform: translateX(-100%); width: 85%; max-width: 300px; }
            .sidebar.open { transform: translateX(0); }
            .conversation { width: 100%; min-width: 0; }
            .conv-header { padding: 12px 14px; flex-wrap: wrap; gap: 8px; }
            .btn-back-group { font-size: 12px; padding: 6px 10px; }
            .messages { padding: 12px; gap: 10px; }
            .input-area { padding: 10px 12px; }
            .msg-input { font-size: 13px; }
            .btn-send { padding: 8px 14px; font-size: 13px; }
        }

        .panel-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 499; opacity: 0; transition: opacity .2s; }
        .panel-backdrop.show { display: block; opacity: 1; }

        /* Conversation Panel */
        .conversation { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .conv-header {
            padding: 16px 20px; background: white; border-bottom: 1px solid #e2e8f0;
            display: flex; justify-content: space-between; align-items: center; gap: 10px;
        }
        .conv-header-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .conv-header h2 { font-size: 17px; color: #2d3748; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-header-meta { font-size: 12px; color: #718096; }
        .messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }

        .empty-state { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #a0aec0; padding: 40px; text-align: center; }
        .empty-state span { font-size: 44px; margin-bottom: 12px; display: block; }

        /* ── Chat bubble styles (same as topic discussions) ── */
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
        .chat-bubble-wrap { display: flex; flex-direction: column; max-width: 72%; }
        .chat-row.mine .chat-bubble-wrap { align-items: flex-end; }
        .chat-meta { font-size: 11px; color: #94a3b8; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
        .chat-row.mine .chat-meta { flex-direction: row-reverse; }
        .chat-meta .author { font-weight: 700; color: #475569; }
        .chat-row.mine .chat-meta .author { color: #059669; }
        .chat-bubble {
            background: #fff; border-radius: 18px 18px 18px 4px; padding: 11px 15px;
            font-size: 14px; color: #1e293b; line-height: 1.55;
            box-shadow: 0 1px 4px rgba(0,0,0,.08); word-break: break-word;
        }
        .chat-row.mine .chat-bubble {
            background: linear-gradient(135deg, #667eea, #764ba2); color: #fff;
            border-radius: 18px 18px 4px 18px; box-shadow: 0 2px 10px rgba(102,126,234,.35);
        }

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
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px 20px 6px 20px;
            box-shadow: 0 4px 16px rgba(102,126,234,.4);
            border: none;
        }
        .audio-play-btn {
            width: 40px; height: 40px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0; transition: all .2s;
            background: linear-gradient(135deg, #667eea, #764ba2); color: #fff;
            box-shadow: 0 3px 10px rgba(102,126,234,.45);
        }
        .chat-row.mine .audio-play-btn {
            background: rgba(255,255,255,.22); color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
            backdrop-filter: blur(4px);
        }
        .audio-play-btn:hover { transform: scale(1.12); }
        .audio-waveform { flex: 1; display: flex; align-items: center; gap: 2.5px; height: 32px; }
        .audio-waveform span { display: inline-block; width: 3px; border-radius: 4px; background: #e2e8f0; transition: background .25s; transform-origin: center; }
        .chat-row.mine .audio-waveform span { background: rgba(255,255,255,.35); }
        .audio-waveform.playing span { background: #667eea; animation: waveAnim .55s ease-in-out infinite alternate; }
        .chat-row.mine .audio-waveform.playing span { background: rgba(255,255,255,.9); }
        .audio-waveform span:nth-child(2n)   { animation-delay: .08s; }
        .audio-waveform span:nth-child(3n)   { animation-delay: .18s; }
        .audio-waveform span:nth-child(4n)   { animation-delay: .12s; }
        .audio-waveform span:nth-child(5n)   { animation-delay: .22s; }
        .audio-waveform span:nth-child(7n)   { animation-delay: .05s; }
        @keyframes waveAnim { from { transform: scaleY(.3); opacity: .7; } to { transform: scaleY(1.3); opacity: 1; } }
        .audio-duration { font-size: 11px; font-weight: 700; color: #94a3b8; min-width: 34px; text-align: right; font-variant-numeric: tabular-nums; }
        .chat-row.mine .audio-duration { color: rgba(255,255,255,.75); }
        .audio-label { font-size: 10px; font-weight: 600; color: #a0aec0; letter-spacing: .4px; text-transform: uppercase; }
        .chat-row.mine .audio-label { color: rgba(255,255,255,.6); }

        /* ── WhatsApp-style input bar ── */
        .input-area { padding: 10px 14px; background: #f0f2f5; border-top: none; }
        .input-bar {
            display: flex; align-items: flex-end; gap: 8px;
            background: white; border-radius: 26px;
            padding: 6px 6px 6px 14px;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
        /* left icon group */
        .bar-icons-left { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
        .bar-icon {
            width: 38px; height: 38px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            background: none; transition: background .18s; flex-shrink: 0;
            color: #54656f; font-size: 22px; padding: 0;
        }
        .bar-icon:hover { background: #f0f2f5; }
        .bar-icon svg { width: 24px; height: 24px; display: block; }
        /* textarea */
        .msg-input {
            flex: 1; border: none; outline: none; resize: none;
            font-family: inherit; font-size: 15px; line-height: 1.4;
            background: transparent; color: #111b21;
            padding: 6px 0; max-height: 120px; overflow-y: auto;
        }
        .msg-input::placeholder { color: #8696a0; }
        /* right send/mic button */
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
        /* audio preview bar */
        .audio-preview {
            display: none; align-items: center; gap: 10px; margin-top: 8px;
            background: white; border-radius: 26px;
            padding: 8px 14px;
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
        /* attach preview */
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
            display: flex; align-items: center; gap: 12px; margin-top: 6px;
            padding: 12px 14px; border-radius: 16px;
            background: #fff; border: 1.5px solid #e2e8f0;
            max-width: 320px; box-shadow: 0 2px 8px rgba(0,0,0,.07);
            transition: box-shadow .2s;
        }
        .file-msg-bubble:hover { box-shadow: 0 4px 16px rgba(0,0,0,.12); }
        .chat-row.mine .file-msg-bubble {
            background: #fff; border-color: #e2e8f0;
            border-radius: 16px 16px 4px 16px;
        }
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
        /* Image bubble + save button */
        .img-msg-bubble { margin-top: 6px; border-radius: 14px; overflow: hidden; max-width: 280px; box-shadow: 0 2px 10px rgba(0,0,0,.1); cursor: pointer; position: relative; }
        .img-msg-bubble img { width: 100%; display: block; }
        .chat-row.mine .img-msg-bubble { border-radius: 14px 14px 4px 14px; }
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
        .btn-cam-snap {
            width: 64px; height: 64px; border-radius: 50%; border: 4px solid white;
            background: white; cursor: pointer; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(0,0,0,.4);
        }
        .btn-cam-snap::after { content:''; width:52px; height:52px; border-radius:50%; background:#00a884; display:block; }
        .btn-cam-close { padding:10px 22px; background:rgba(255,255,255,.15); color:#fff; border: 1.5px solid rgba(255,255,255,.4); border-radius:24px; font-size:14px; cursor:pointer; backdrop-filter:blur(4px); }
        /* Back to group chat button */
        .btn-back-group {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
            background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #5b21b6;
            border: 1.5px solid #c4b5fd; cursor: pointer; text-decoration: none;
            transition: all .2s; flex-shrink: 0;
        }
        .btn-back-group:hover { background: linear-gradient(135deg, #ddd6fe, #c4b5fd); transform: translateY(-1px); box-shadow: 0 3px 10px rgba(109,40,217,.2); }

        /* Alerts */
        .alert { padding: 10px 16px; border-radius: 7px; margin-bottom: 12px; font-size: 14px; }
        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }

        /* ── Chat actions ── */
        .chat-actions { display: flex; gap: 4px; margin-top: 4px; flex-wrap: wrap; opacity: 0; transition: opacity .15s; pointer-events: none; }
        .chat-row.selected .chat-actions { opacity: 1; pointer-events: auto; }
        .chat-row.mine .chat-actions { justify-content: flex-end; }
        .btn-sm { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 14px; border: 1px solid #e2e8f0; border-radius: 50%; cursor: pointer; background: white; transition: all .15s; padding: 0; }
        .btn-sm:hover { background: #f1f5f9; transform: scale(1.1); }
        .btn-reply-msg { color: #667eea; border-color: #c7d2fe; }
        .btn-edit-msg  { color: #38a169; border-color: #a7f3d0; }
        .btn-delete-msg{ color: #e53e3e; border-color: #fecaca; }

        /* ── Reply preview (quoted message) ── */
        .reply-preview {
            border-left: 3px solid #667eea; background: #f0f0ff;
            border-radius: 6px; padding: 6px 10px; margin-bottom: 4px;
            font-size: 12px; color: #4a5568; max-width: 100%;
        }
        .reply-preview .rp-author { font-weight: 700; color: #667eea; margin-bottom: 2px; }
        .reply-preview .rp-body { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .chat-row.mine .reply-preview { border-color: rgba(255,255,255,.6); background: rgba(255,255,255,.15); color: rgba(255,255,255,.85); }
        .chat-row.mine .reply-preview .rp-author { color: #fff; }

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
    
    /* ── Page-load overlay ── */
    #page-loader {
        display:none; position:fixed; inset:0; z-index:99998;
        background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
        align-items:center; justify-content:center; flex-direction:column; gap:18px;
    }
    #page-loader.show { display:flex; }
    .pl-logo { width:64px; height:64px; border-radius:16px; background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; border:2px solid rgba(255,255,255,.3); }
    .pl-logo img { width:44px; height:44px; object-fit:contain; filter:drop-shadow(0 2px 6px rgba(0,0,0,.3)); }
    .pl-spinner { width:40px; height:40px; border:3px solid rgba(255,255,255,.25); border-top-color:#fff; border-radius:50%; animation:plSpin .7s linear infinite; }
    @keyframes plSpin { to { transform:rotate(360deg); } }
    .pl-text { color:rgba(255,255,255,.85); font-size:14px; font-weight:600; letter-spacing:.3px; }
    </style>
</head>
<body>

{{-- Navbar --}}
<nav class="navbar">
    <div style="display:flex;align-items:center;gap:10px;">
        <button class="mobile-toggle-btn" id="convToggleBtn" type="button" aria-label="Toggle conversation list">☰</button>
        <h1><img src="{{ asset('images/forum.png') }}" alt="Discussion Hub" style="height:34px;vertical-align:middle;margin-right:8px;"><span class="full-title">Messages</span></h1>
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

    {{-- Sidebar: conversation list + start-new search --}}
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-title">💬 Direct Messages</div>
            <form method="GET" action="{{ route('messages.index') }}">
                <div class="search-wrap">
                    <input type="text" name="search" class="search-bar" placeholder="Find someone to message..."
                        value="{{ request('search') }}" oninput="this.form.submit()">
                </div>
            </form>
        </div>

        @if(request()->filled('search'))
            <div class="topics-count">{{ $searchResults->count() }} {{ Str::plural('result', $searchResults->count()) }}</div>
            <div class="topic-list">
                @forelse($searchResults as $user)
                    <div class="topic-item" onclick="window.location='{{ route('messages.show', $user->id) }}'">
                        <div class="topic-item-inner">
                            <div class="topic-avatar">{{ strtoupper(substr($user->name,0,1)) }}</div>
                            <div class="topic-content">
                                <h4>{{ $user->name }}</h4>
                                <div class="topic-author">{{ ucfirst($user->role) }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="padding:30px 20px;text-align:center;color:#a0aec0;font-size:13px;">No users found.</div>
                @endforelse
            </div>
        @else
            <div class="topics-count">{{ $conversations->count() }} conversation{{ $conversations->count() !== 1 ? 's' : '' }}</div>
            <div class="topic-list">
                @forelse($conversations as $conv)
                    @php
                        $preview = $conv['last']
                            ? ($conv['last']->body ?: '🎤 Voice message')
                            : '';
                    @endphp
                    <div class="topic-item {{ isset($other) && $other && $other->id === $conv['user']->id ? 'active' : '' }}"
                         onclick="window.location='{{ route('messages.show', $conv['user']->id) }}'">
                        <div class="topic-item-inner">
                            <div class="topic-avatar">{{ strtoupper(substr($conv['user']->name,0,1)) }}</div>
                            <div class="topic-content">
                                <h4>{{ $conv['user']->name }}</h4>
                                <div class="topic-author">{{ Str::limit($preview, 34) }}</div>
                            </div>
                            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                                <span class="conv-time">{{ $conv['last_time']?->diffForHumans(null, true) }}</span>
                                @if($conv['unread'] > 0)
                                    <span class="unread-pill">{{ $conv['unread'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="padding:40px 20px;text-align:center;color:#a0aec0;font-size:13px;">
                        <div style="font-size:32px;margin-bottom:8px;">📭</div>
                        No conversations yet.<br>Search above to message someone.
                    </div>
                @endforelse
            </div>
        @endif
    </aside>

    {{-- Conversation Panel --}}
    <main class="conversation">
        @if($other)
            <div class="conv-header">
                <div class="conv-header-left">
                    <div class="topic-avatar">{{ strtoupper(substr($other->name,0,1)) }}</div>
                    <div style="min-width:0;">
                        <h2>{{ $other->name }}</h2>
                        <div class="conv-header-meta">{{ ucfirst($other->role) }} &middot; Private Message</div>
                    </div>
                </div>
                <a href="{{ route('topics.index') }}" class="btn-back-group" title="Back to Group Chat">
                    &#8592; Group Chat
                </a>
            </div>

            <div class="messages" id="messages">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif

                @forelse($messages as $msg)
                    @php $isMe = $msg->sender_id === auth()->id(); @endphp
                    <div class="chat-row {{ $isMe ? 'mine' : '' }}" id="msg-{{ $msg->id }}">
                        <div class="chat-avatar">{{ strtoupper(substr(($isMe ? auth()->user()->name : $other->name), 0, 1)) }}</div>
                        <div class="chat-bubble-wrap">
                            <div class="chat-meta">
                                <span class="author">{{ $isMe ? 'You' : $other->name }}</span>
                                <span>{{ $msg->created_at->diffForHumans() }}</span>
                            </div>
                            @if($msg->trashed())
                                <div class="chat-bubble" style="opacity:.5;font-style:italic;">🚫 This message was deleted</div>
                            @else
                            @if($msg->replyTo)
                                <div class="reply-preview">
                                    <div class="rp-author">{{ $msg->replyTo->sender_id === auth()->id() ? 'You' : $other->name }}</div>
                                    <div class="rp-body">{{ $msg->replyTo->body ?: '📎 Attachment' }}</div>
                                </div>
                            @endif
                            @if($msg->body)
                                <div class="chat-bubble" id="msg-body-{{ $msg->id }}">{{ $msg->body }}</div>
                            @endif
                                                                                    @if($msg->image_path)
                                <div class="img-msg-bubble">
                                    <img src="{{ asset('storage/' . $msg->image_path) }}" alt="Image" loading="lazy" onclick="this.closest('.img-msg-bubble').requestFullscreen&&this.closest('.img-msg-bubble').requestFullscreen()">
                                    <a href="{{ asset('storage/' . $msg->image_path) }}" download class="btn-img-save" title="Save image">&#8595;</a>
                                </div>
                            @endif
                                                                                    @if($msg->file_path)
                                @php
                                    $ext = strtolower(pathinfo($msg->file_name ?? '', PATHINFO_EXTENSION));
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
                                    $fileSize = $msg->file_size
                                        ? ($msg->file_size >= 1048576
                                            ? round($msg->file_size/1048576,1).'MB'
                                            : round($msg->file_size/1024,0).'KB')
                                        : strtoupper($ext);
                                @endphp
                                <div class="file-msg-bubble">
                                    <div class="file-type-icon">{{ $fileIcon }}</div>
                                    <div class="file-info">
                                        <div class="fname" title="{{ $msg->file_name }}">{{ $msg->file_name ?? 'Document' }}</div>
                                        <div class="fmeta">
                                            <span>{{ strtoupper($ext) }}</span>
                                            <span class="fmeta-dot"></span>
                                            <span>{{ $fileSize }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ asset('storage/' . $msg->file_path) }}" download="{{ $msg->file_name }}" class="btn-file-dl" title="Download">&#8595;</a>
                                </div>
                            @endif
                            @if($msg->audio_path)
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
                                    <audio preload="auto" src="{{ asset('storage/' . $msg->audio_path) }}" style="display:none"></audio>
                                </div>
                            @endif
                            <div class="chat-actions">
                                <button class="btn-sm btn-reply-msg" title="Reply" onclick="setReply({{ $msg->id }}, '{{ $isMe ? 'You' : addslashes($other->name) }}', '{{ addslashes(Str::limit($msg->body ?: 'Attachment', 60)) }}')">&#8617;</button>
                                @if($isMe)
                                    @if($msg->body)
                                        <button class="btn-sm btn-edit-msg" title="Edit" onclick="editMsg({{ $msg->id }}, '{{ addslashes($msg->body) }}')">&#9998;</button>
                                    @endif
                                    <button class="btn-sm btn-delete-msg" title="Delete" onclick="deleteMsg({{ $msg->id }})">&#128465;</button>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <span>👋</span>
                        Say hi to {{ $other->name }} — this is the start of your private conversation.
                    </div>
                @endforelse
            </div>

            <div class="input-area">
                <form action="{{ route('messages.store', $other->id) }}" method="POST" id="messageForm" enctype="multipart/form-data" data-no-loader>
                    @csrf
                    <input type="file" id="imgInput" name="image" accept="image/*" style="display:none">
                    <input type="file" id="docInput" name="file" style="display:none">
                    <input type="hidden" name="reply_to_id" id="replyToId">
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
                            {{-- Attachment (paperclip) --}}
                            <button type="button" class="bar-icon" id="docBtn" title="Send document">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .83-.67 1.5-1.5 1.5s-1.5-.67-1.5-1.5V6H9v9.5a3 3 0 0 0 6 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/></svg>
                            </button>
                            {{-- Camera --}}
                            <button type="button" class="bar-icon" id="camBtn" title="Take photo">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 15.2A3.2 3.2 0 1 0 12 8.8a3.2 3.2 0 0 0 0 6.4zm0-8.4a5.2 5.2 0 1 1 0 10.4A5.2 5.2 0 0 1 12 6.8zM9 2L7.17 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-3.17L15 2H9z"/></svg>
                            </button>
                            {{-- Image --}}
                            <button type="button" class="bar-icon" id="imgBtn" title="Send image">
                                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                            </button>
                        </div>
                        <textarea name="body" id="messageInput" class="msg-input" rows="1"
                            placeholder="Type a message"
                            oninput="onMsgInput()"
                            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();document.getElementById('messageForm').requestSubmit();}"></textarea>
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
                </form>
            </div>
        @else
            <div class="empty-state">
                <span>✉️</span>
                Select a conversation on the left, or search for someone above to start a private chat.
            </div>
        @endif
    </main>
</div>

{{-- Camera modal --}}
<div class="cam-modal" id="camModal">
    <video id="camVideo" autoplay playsinline></video>
    <canvas id="camCanvas" style="display:none"></canvas>
    <div class="cam-actions">
        <button class="btn-cam-close" id="camCloseBtn">&#10005; Cancel</button>
        <button class="btn-cam-snap" id="camSnapBtn" title="Capture"></button>
    </div>
</div>

<script>
    // ── Mobile sidebar toggle ──
    (function () {
        const toggleBtn = document.getElementById('convToggleBtn');
        const sidebar    = document.querySelector('.sidebar');
        const backdrop   = document.getElementById('panelBackdrop');
        if (!toggleBtn) return;
        function openSidebar()  { sidebar.classList.add('open'); backdrop.classList.add('show'); }
        function closeSidebar() { sidebar.classList.remove('open'); backdrop.classList.remove('show'); }
        toggleBtn.addEventListener('click', openSidebar);
        backdrop.addEventListener('click', closeSidebar);
    })();

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

    // ── Send/mic toggle ──
    function updateSendBtn() {
        const val = document.getElementById('messageInput') && document.getElementById('messageInput').value.trim();
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
    document.getElementById('micBtn') && document.getElementById('micBtn').addEventListener('click', function () {
        const val = document.getElementById('messageInput') && document.getElementById('messageInput').value.trim();
        if (val) { document.getElementById('messageForm').requestSubmit(); }
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

        // Override micBtn click to also send when attachment is staged
        document.getElementById('micBtn').addEventListener('click', function () {
            const hasAttach = imgInput.files[0] || docInput.files[0];
            if (hasAttach) {
                const fd = new FormData(document.getElementById('messageForm'));
                fetch(document.getElementById('messageForm').action, { method: 'POST', body: fd })
                    .then(r => r.redirected ? window.location.href = r.url : window.location.reload());
            }
        });

        // Camera
        const camModal  = document.getElementById('camModal');
        const camVideo  = document.getElementById('camVideo');
        const camCanvas = document.getElementById('camCanvas');
        const snapBtn   = document.getElementById('camSnapBtn');
        const closeBtn  = document.getElementById('camCloseBtn');
        let camStream   = null;

        camBtn.addEventListener('click', async function () {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                imgInput.setAttribute('capture', 'environment');
                imgInput.click();
                return;
            }
            try {
                camStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false });
                camVideo.srcObject = camStream;
                camModal.classList.add('open');
            } catch (e) {
                imgInput.setAttribute('capture', 'environment');
                imgInput.click();
            }
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

    // ── Audio bubble player ──
    document.querySelectorAll('.audio-msg-bubble').forEach(function(bubble) {
        const audio = bubble.querySelector('audio');
        const durEl = bubble.querySelector('.audio-duration');
        let fixingDuration = false;
        function setDurationText(s) { if (isFinite(s)) durEl.textContent = fmtTime(s); }
        audio.addEventListener('loadedmetadata', function() {
            if (audio.duration === Infinity || isNaN(audio.duration)) {
                fixingDuration = true;
                audio.currentTime = 1e101;
                audio.addEventListener('timeupdate', function onFix() {
                    audio.removeEventListener('timeupdate', onFix);
                    audio.currentTime = 0; fixingDuration = false;
                    setDurationText(audio.duration);
                }, { once: true });
            } else { setDurationText(audio.duration); }
        });
        audio.addEventListener('durationchange', function() { if (!fixingDuration) setDurationText(audio.duration); });
        audio.addEventListener('timeupdate', function() { if (!fixingDuration) durEl.textContent = fmtTime(audio.currentTime); });
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
                a.closest('.audio-msg-bubble').querySelector('.audio-play-btn').innerHTML = '&#9654;';
                a.closest('.audio-msg-bubble').querySelector('.audio-waveform').classList.remove('playing');
            }
        });
        if (audio.paused) { audio.play().catch(function(){}); btn.innerHTML = '&#9646;&#9646;'; wave.classList.add('playing'); }
        else { audio.pause(); btn.innerHTML = '&#9654;'; wave.classList.remove('playing'); }
    }

    // ── Audio Recorder ──
    (function () {
        const micBtn       = document.getElementById('micBtn');
        const audioPreview = document.getElementById('audioPreview');
        const discardBtn   = document.getElementById('discardAudio');
        const recTimerEl   = document.getElementById('recTimer');
        const sendAudioBtn = document.getElementById('sendAudioBtn');
        const messageForm  = document.getElementById('messageForm');
        if (!micBtn) return;
        let mediaRecorder, audioChunks = [], recInterval, recSeconds = 0, audioBlob = null;
        function fmtSecs(s) { return Math.floor(s/60)+':'+(s%60).toString().padStart(2,'0'); }

        micBtn.addEventListener('click', async function () {
            // If there's text, act as send button
            const val = document.getElementById('messageInput').value.trim();
            if (val) { messageForm.requestSubmit(); return; }
            if (mediaRecorder && mediaRecorder.state === 'recording') { mediaRecorder.stop(); return; }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioChunks = []; recSeconds = 0; recTimerEl.textContent = '0:00';
                const types = ['audio/webm;codecs=opus','audio/webm','audio/mp4','audio/ogg;codecs=opus'];
                const mime = types.find(t => MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(t));
                mediaRecorder = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream);
                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                mediaRecorder.onstop = function () {
                    stream.getTracks().forEach(t => t.stop());
                    audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                    audioPreview.style.display = 'flex';
                    micBtn.classList.remove('recording');
                    clearInterval(recInterval);
                };
                mediaRecorder.start();
                micBtn.classList.add('recording');
                recInterval = setInterval(() => { recSeconds++; recTimerEl.textContent = fmtSecs(recSeconds); }, 1000);
            } catch (err) { alert('Microphone access denied.'); }
        });

        discardBtn.addEventListener('click', function () {
            audioBlob = null; audioPreview.style.display = 'none'; recTimerEl.textContent = '0:00';
        });
        sendAudioBtn.addEventListener('click', async function () {
            if (!audioBlob) return;
            const ext = audioBlob.type.includes('mp4') ? 'mp4' : audioBlob.type.includes('ogg') ? 'ogg' : 'webm';
            const fd = new FormData();
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            fd.append('audio', audioBlob, 'voice-message.' + ext);
            fd.append('body', '');
            const res = await fetch(messageForm.action, { method: 'POST', body: fd });
            if (res.redirected) window.location.href = res.url; else window.location.reload();
        });
    })();

    function loadNotifications() {
        fetch('/notifications').then(r => r.json()).then(data => {
            alert(data.map(n => `• ${n.data.user}: ${n.data.excerpt}`).join('\n') || 'No notifications.');
            document.querySelector('.notif-badge') && document.querySelector('.notif-badge').remove();
        });
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // ── Reply ──
    function setReply(id, author, body) {
        document.getElementById('replyToId').value = id;
        document.getElementById('replyBarAuthor').textContent = author;
        document.getElementById('replyBarBody').textContent = body;
        document.getElementById('replyBar').style.display = 'flex';
        document.getElementById('messageInput').focus();
    }
    function cancelReply() {
        document.getElementById('replyToId').value = '';
        document.getElementById('replyBar').style.display = 'none';
    }

    // ── Edit ──
    let editingMsgId = null;
    function editMsg(id, body) {
        editingMsgId = id;
        const input = document.getElementById('messageInput');
        input.value = body;
        input.focus();
        updateSendBtn();
        // swap send to confirm edit on submit
        document.getElementById('messageForm').dataset.editing = id;
    }
    document.getElementById('messageForm') && document.getElementById('messageForm').addEventListener('submit', function (e) {
        const editId = this.dataset.editing;
        if (!editId) return; // normal send
        e.preventDefault();
        const body = document.getElementById('messageInput').value.trim();
        if (!body) return;
        fetch('/messages/' + editId + '/edit', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ body })
        }).then(r => r.json()).then(data => {
            if (data.success) {
                document.getElementById('msg-body-' + editId).textContent = data.body;
                document.getElementById('messageInput').value = '';
                delete document.getElementById('messageForm').dataset.editing;
                updateSendBtn();
            }
        });
    });

    // ── Delete ──
    function deleteMsg(id) {
        if (!confirm('Delete this message?')) return;
        fetch('/messages/' + id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                const wrap = document.querySelector('#msg-' + id + ' .chat-bubble-wrap');
                if (!wrap) return;
                // keep meta row, replace content with deleted placeholder
                const meta = wrap.querySelector('.chat-meta');
                wrap.innerHTML = '';
                if (meta) wrap.appendChild(meta);
                const placeholder = document.createElement('div');
                placeholder.className = 'chat-bubble';
                placeholder.style.cssText = 'opacity:.5;font-style:italic;';
                placeholder.textContent = '\uD83D\uDEAB This message was deleted';
                wrap.appendChild(placeholder);
            }
        });
    }
</script>

<div id="page-loader">
    <div class="pl-logo"><img src="{{ asset('images/forum.png') }}" alt=""></div>
    <div class="pl-spinner"></div>
    <div class="pl-text">Loading…</div>
</div>
<script>
(function(){
    var loader = document.getElementById('page-loader');
    document.addEventListener('click', function(e) {
        var a = e.target.closest('a[href]');
        if (!a) return;
        var href = a.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript') ||
            href.startsWith('http') || href.startsWith('//') ||
            a.hasAttribute('download') || a.target === '_blank') return;
        loader.classList.add('show');
    });
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'loginForm') return;
        loader.classList.add('show');
    });
    window.addEventListener('pageshow', function() { loader.classList.remove('show'); });
    setInterval(function() { fetch('/api/ping', {credentials:'same-origin'}).catch(function(){}); }, 240000);
})();
</script>
</body>
</html>
