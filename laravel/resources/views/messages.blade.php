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

        /* Input area */
        .input-area { padding: 14px 20px; background: white; border-top: 1px solid #e2e8f0; }
        .attach-toolbar { display: flex; gap: 6px; margin-bottom: 8px; }
        .btn-attach {
            width: 36px; height: 36px; border-radius: 10px; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            transition: all .2s; font-size: 16px;
        }
        .btn-attach.img { background: linear-gradient(135deg,#dbeafe,#bfdbfe); color: #1d4ed8; }
        .btn-attach.img:hover { background: linear-gradient(135deg,#bfdbfe,#93c5fd); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(29,78,216,.2); }
        .btn-attach.doc { background: linear-gradient(135deg,#dcfce7,#bbf7d0); color: #15803d; }
        .btn-attach.doc:hover { background: linear-gradient(135deg,#bbf7d0,#86efac); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(21,128,61,.2); }
        .btn-attach.cam { background: linear-gradient(135deg,#fef9c3,#fef08a); color: #a16207; }
        .btn-attach.cam:hover { background: linear-gradient(135deg,#fef08a,#fde047); transform: translateY(-2px); box-shadow: 0 4px 10px rgba(161,98,7,.2); }
        .attach-preview-bar {
            display: none; align-items: center; gap: 10px; margin-bottom: 8px;
            padding: 8px 12px; background: #f8fafc; border: 1.5px solid #e2e8f0;
            border-radius: 12px; font-size: 13px; color: #475569;
        }
        .attach-preview-bar img { max-height: 48px; border-radius: 6px; object-fit: cover; }
        .attach-preview-bar .attach-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .btn-attach-remove { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 16px; flex-shrink: 0; }
        .btn-attach-remove:hover { color: #ef4444; }
        .input-row { display: flex; gap: 10px; align-items: flex-end; }
        .msg-input { flex: 1; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: none; outline: none; font-family: inherit; }
        .msg-input:focus { border-color: #667eea; }
        .btn-send { padding: 10px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        /* Image bubble */
        .img-msg-bubble { margin-top: 4px; border-radius: 14px; overflow: hidden; max-width: 280px; box-shadow: 0 2px 10px rgba(0,0,0,.1); cursor: pointer; }
        .img-msg-bubble img { width: 100%; display: block; }
        .chat-row.mine .img-msg-bubble { border-radius: 14px 14px 4px 14px; }
        /* File bubble */
        .file-msg-bubble {
            display: flex; align-items: center; gap: 10px; margin-top: 4px;
            padding: 10px 14px; border-radius: 14px; background: #f8fafc;
            border: 1.5px solid #e2e8f0; max-width: 280px; box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .chat-row.mine .file-msg-bubble { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.3); border-radius: 14px 14px 4px 14px; }
        .file-icon { font-size: 26px; flex-shrink: 0; }
        .file-info { flex: 1; min-width: 0; }
        .file-info .fname { font-size: 13px; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .chat-row.mine .file-info .fname { color: #fff; }
        .file-info .fsize { font-size: 11px; color: #94a3b8; }
        .chat-row.mine .file-info .fsize { color: rgba(255,255,255,.65); }
        .btn-file-dl { width: 32px; height: 32px; border-radius: 50%; border: none; cursor: pointer; flex-shrink: 0; background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; box-shadow: 0 2px 8px rgba(102,126,234,.35); transition: all .2s; }
        .btn-file-dl:hover { transform: scale(1.1); }
        /* Camera modal */
        .cam-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.85); z-index:600; align-items:center; justify-content:center; flex-direction:column; gap:16px; }
        .cam-modal.open { display:flex; }
        .cam-modal video { border-radius:14px; max-width:90vw; max-height:60vh; background:#000; }
        .cam-actions { display:flex; gap:12px; }
        .btn-cam-snap { padding:10px 28px; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border:none; border-radius:10px; font-size:15px; font-weight:700; cursor:pointer; }
        .btn-cam-close { padding:10px 20px; background:#374151; color:#fff; border:none; border-radius:10px; font-size:15px; cursor:pointer; }
        .btn-mic {
            width: 44px; height: 44px; border-radius: 50%; border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 19px; flex-shrink: 0; transition: all .25s;
            background: linear-gradient(135deg, #667eea, #764ba2); color: #fff;
            box-shadow: 0 4px 14px rgba(102,126,234,.45);
        }
        .btn-mic:hover { opacity: .88; transform: scale(1.07); box-shadow: 0 6px 18px rgba(102,126,234,.55); }
        .btn-mic.recording {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff; animation: micPulse 1s ease-in-out infinite;
            box-shadow: 0 4px 14px rgba(239,68,68,.45);
        }
        @keyframes micPulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,.5); }
            50%     { box-shadow: 0 0 0 12px rgba(239,68,68,0); }
        }
        .audio-preview {
            display: none; align-items: center; gap: 10px; margin-top: 10px;
            background: linear-gradient(135deg, #f5f3ff, #ede9fe);
            border: 1.5px solid #c4b5fd;
            border-radius: 16px;
            padding: 10px 14px;
            box-shadow: 0 2px 8px rgba(109,40,217,.08);
        }
        .rec-timer { font-size: 13px; font-weight: 800; color: #6d28d9; min-width: 40px; letter-spacing: .5px; font-variant-numeric: tabular-nums; }
        .btn-discard {
            width: 30px; height: 30px; border-radius: 50%; border: none;
            background: #fee2e2; color: #dc2626; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0; transition: all .15s;
            box-shadow: 0 1px 4px rgba(220,38,38,.15);
        }
        .btn-discard:hover { background: #fecaca; transform: scale(1.08); }
        .btn-send-audio {
            width: 38px; height: 38px; border-radius: 50%; border: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff; cursor: pointer; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; transition: all .2s;
            box-shadow: 0 4px 12px rgba(102,126,234,.45);
        }
        .btn-send-audio:hover { opacity: .9; transform: scale(1.1); }
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
                    <div class="chat-row {{ $isMe ? 'mine' : '' }}">
                        <div class="chat-avatar">{{ strtoupper(substr(($isMe ? auth()->user()->name : $other->name), 0, 1)) }}</div>
                        <div class="chat-bubble-wrap">
                            <div class="chat-meta">
                                <span class="author">{{ $isMe ? 'You' : $other->name }}</span>
                                <span>{{ $msg->created_at->diffForHumans() }}</span>
                            </div>
                            @if($msg->body)
                                <div class="chat-bubble">{{ $msg->body }}</div>
                            @endif
                            @if($msg->image_path)
                                <div class="img-msg-bubble" onclick="this.querySelector('img').requestFullscreen&&this.querySelector('img').requestFullscreen()">
                                    <img src="{{ asset('storage/' . $msg->image_path) }}" alt="Image" loading="lazy">
                                </div>
                            @endif
                            @if($msg->file_path)
                                <div class="file-msg-bubble">
                                    <span class="file-icon">📄</span>
                                    <div class="file-info">
                                        <div class="fname">{{ $msg->file_name ?? 'Document' }}</div>
                                        <div class="fsize">Attachment</div>
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
                <form action="{{ route('messages.store', $other->id) }}" method="POST" id="messageForm" enctype="multipart/form-data">
                    @csrf
                    <input type="file" id="imgInput" name="image" accept="image/*" style="display:none">
                    <input type="file" id="docInput" name="file" style="display:none">
                    <div class="attach-toolbar">
                        <button type="button" class="btn-attach img" id="imgBtn" title="Send image">&#128444;</button>
                        <button type="button" class="btn-attach doc" id="docBtn" title="Send document">&#128196;</button>
                        <button type="button" class="btn-attach cam" id="camBtn" title="Take photo">&#128247;</button>
                    </div>
                    <div class="attach-preview-bar" id="attachPreviewBar">
                        <span id="attachPreviewThumb"></span>
                        <span class="attach-name" id="attachPreviewName"></span>
                        <button type="button" class="btn-attach-remove" id="attachRemoveBtn" title="Remove">&#10005;</button>
                    </div>
                    <div class="input-row">
                        <button type="button" class="btn-mic" id="micBtn" title="Record audio message">&#127897;</button>
                        <textarea name="body" id="messageInput" class="msg-input" rows="2"
                            placeholder="Write a private message…"
                            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();document.getElementById('messageForm').requestSubmit();}"></textarea>
                        <button type="submit" class="btn-send">Send</button>
                    </div>
                    <div class="audio-preview" id="audioPreview">
                        <button type="button" class="btn-discard" id="discardAudio" title="Discard">&#10005;</button>
                        <span class="rec-timer" id="recTimer">0:00</span>
                        <div style="flex:1;display:flex;align-items:center;gap:2px;height:28px" id="previewWave">
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
    <canvas id="camCanvas"></canvas>
    <div class="cam-actions">
        <button class="btn-cam-snap" id="camSnapBtn">&#128247; Capture</button>
        <button class="btn-cam-close" id="camCloseBtn">&#10005; Cancel</button>
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

    function fmtTime(s) {
        if (!isFinite(s) || isNaN(s)) return '0:00';
        return Math.floor(s/60)+':'+(Math.floor(s%60)).toString().padStart(2,'0');
    }

    // ── Attachment toolbar ──
    (function () {
        const imgBtn    = document.getElementById('imgBtn');
        const docBtn    = document.getElementById('docBtn');
        const camBtn    = document.getElementById('camBtn');
        const imgInput  = document.getElementById('imgInput');
        const docInput  = document.getElementById('docInput');
        const previewBar  = document.getElementById('attachPreviewBar');
        const previewThumb = document.getElementById('attachPreviewThumb');
        const previewName  = document.getElementById('attachPreviewName');
        const removeBtn    = document.getElementById('attachRemoveBtn');
        if (!imgBtn) return;

        function showPreview(name, thumbHtml) {
            previewThumb.innerHTML = thumbHtml;
            previewName.textContent = name;
            previewBar.style.display = 'flex';
        }
        function clearPreview() {
            previewBar.style.display = 'none';
            previewThumb.innerHTML = '';
            previewName.textContent = '';
            imgInput.value = '';
            docInput.value = '';
        }

        imgBtn.addEventListener('click', () => imgInput.click());
        docBtn.addEventListener('click', () => docInput.click());
        removeBtn.addEventListener('click', clearPreview);

        imgInput.addEventListener('change', function () {
            if (!this.files[0]) return;
            const url = URL.createObjectURL(this.files[0]);
            showPreview(this.files[0].name, `<img src="${url}">`);
        });
        docInput.addEventListener('change', function () {
            if (!this.files[0]) return;
            showPreview(this.files[0].name, '📄');
        });

        // Camera capture
        const camModal  = document.getElementById('camModal');
        const camVideo  = document.getElementById('camVideo');
        const camCanvas = document.getElementById('camCanvas');
        const snapBtn   = document.getElementById('camSnapBtn');
        const closeBtn  = document.getElementById('camCloseBtn');
        let camStream   = null;

        camBtn.addEventListener('click', async function () {
            try {
                camStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                camVideo.srcObject = camStream;
                camModal.classList.add('open');
            } catch (e) {
                alert('Camera access denied.');
            }
        });

        function stopCam() {
            if (camStream) { camStream.getTracks().forEach(t => t.stop()); camStream = null; }
            camModal.classList.remove('open');
        }

        closeBtn.addEventListener('click', stopCam);

        snapBtn.addEventListener('click', function () {
            camCanvas.width  = camVideo.videoWidth;
            camCanvas.height = camVideo.videoHeight;
            camCanvas.getContext('2d').drawImage(camVideo, 0, 0);
            camCanvas.toBlob(function (blob) {
                const file = new File([blob], 'photo-' + Date.now() + '.jpg', { type: 'image/jpeg' });
                const dt = new DataTransfer();
                dt.items.add(file);
                imgInput.files = dt.files;
                const url = URL.createObjectURL(blob);
                showPreview(file.name, `<img src="${url}">`);
                stopCam();
            }, 'image/jpeg', 0.92);
        });
    })();

    // ── Audio bubble player ──
    document.querySelectorAll('.audio-msg-bubble').forEach(function(bubble) {
        const audio = bubble.querySelector('audio');
        const durEl = bubble.querySelector('.audio-duration');
        let fixingDuration = false;

        function setDurationText(seconds) {
            if (isFinite(seconds)) durEl.textContent = fmtTime(seconds);
        }

        audio.addEventListener('loadedmetadata', function() {
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
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioChunks = []; recSeconds = 0;
                recTimerEl.textContent = '0:00';
                const preferredTypes = ['audio/webm;codecs=opus','audio/webm','audio/mp4','audio/ogg;codecs=opus'];
                const supportedType = preferredTypes.find(t => window.MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(t));
                mediaRecorder = supportedType ? new MediaRecorder(stream, { mimeType: supportedType }) : new MediaRecorder(stream);
                mediaRecorder.ondataavailable = e => audioChunks.push(e.data);
                mediaRecorder.onstop = function () {
                    stream.getTracks().forEach(t => t.stop());
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

        sendAudioBtn.addEventListener('click', async function () {
            if (!audioBlob) return;
            const fd = new FormData();
            const ext = audioBlob.type.includes('mp4') ? 'mp4' : audioBlob.type.includes('ogg') ? 'ogg' : 'webm';
            fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            fd.append('audio', audioBlob, 'voice-message.' + ext);
            fd.append('body', '');
            const res = await fetch(messageForm.action, { method: 'POST', body: fd });
            if (res.redirected) { window.location.href = res.url; }
            else { window.location.reload(); }
        });
    })();

    function loadNotifications() {
        fetch('/notifications')
            .then(r => r.json())
            .then(data => {
                const list = data.map(n => `• ${n.data.user}: ${n.data.excerpt}`).join('\n');
                alert(list || 'No notifications.');
                document.querySelector('.notif-badge') && (document.querySelector('.notif-badge').remove());
            });
    }
</script>
</body>
</html>
