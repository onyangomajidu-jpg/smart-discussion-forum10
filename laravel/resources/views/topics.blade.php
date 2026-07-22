<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

        /* Typing indicator */
        .typing-indicator { padding: 6px 20px; font-size: 13px; color: #718096; font-style: italic; min-height: 28px; }
        .typing-dots span { display: inline-block; width: 6px; height: 6px; background: #718096; border-radius: 50%; margin: 0 2px; animation: bounce 1.2s infinite; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

        /* Input area */
        .input-area { padding: 16px 20px; background: white; border-top: 1px solid #e2e8f0; }
        .input-row { display: flex; gap: 10px; align-items: flex-end; }
        .msg-input { flex: 1; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: none; outline: none; font-family: inherit; }
        .msg-input:focus { border-color: #667eea; }
        .btn-send { padding: 10px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
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

                {{-- Posts --}}
                @foreach($posts as $post)
                @php $isMe = $post->user_id === auth()->id(); @endphp
                <div class="chat-row {{ $isMe ? 'mine' : '' }}" id="post-{{ $post->id }}">
                    @if(!$isMe)<div class="chat-avatar">{{ strtoupper(substr($post->author->name,0,1)) }}</div>@endif
                    <div class="chat-bubble-wrap">
                        <div class="chat-meta">
                            <span class="author">{{ $isMe ? 'You' : $post->author->name }}</span>
                            <span>{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="chat-bubble" id="post-body-{{ $post->id }}">{{ $post->body }}</div>
                        <div class="chat-actions">
                            <button class="btn-sm btn-reply" onclick="toggleReplyForm({{ $post->id }})">&#8617; Reply</button>
                            @if(auth()->id() === $post->user_id || auth()->user()->isAdmin())
                                <button class="btn-sm btn-edit" onclick="editPost({{ $post->id }}, `{{ addslashes($post->body) }}`)">&#9998; Edit</button>
                                <button class="btn-sm btn-delete" onclick="deletePost({{ $post->id }})">&#128465; Delete</button>
                            @endif
                        </div>
                        <form id="reply-form-{{ $post->id }}" style="display:none;margin-top:8px;"
                              action="{{ route('topics.answer', $post->id) }}" method="POST">
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
                @endif {{-- end $isRemoved check --}}
            </div>

            {{-- Typing indicator --}}
            <div class="typing-indicator" id="typingIndicator"></div>

            {{-- Input area --}}
            @if(!$activeTopic->is_locked && !$isRemoved)
                <div class="input-area">
                    <form action="{{ route('topics.participate', $activeTopic->id) }}" method="POST" id="postForm">
                        @csrf
                        <div class="input-row">
                            <textarea name="body" id="postInput" class="msg-input" rows="2"
                                placeholder="Write a message..." required
                                oninput="handleTyping()"
                                onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault();document.getElementById('postForm').requestSubmit();}"></textarea>
                            <button type="submit" class="btn-send">Send</button>
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
        if (typeof window.Echo === 'undefined') return;
        const topicChannel = window.Echo.channel('topic.{{ $activeTopic->id }}');
        topicChannel.listen('.new.post', (e) => {
            if (e.type !== 'post') return;
            const msgs = document.getElementById('messages');
            const div = document.createElement('div');
            div.className = 'post-card';
            div.innerHTML = `<div class="post-header"><span class="post-author">User #${e.userId}</span><span class="post-time">just now</span></div><div class="post-body">${e.body}</div>`;
            msgs.appendChild(div);
            msgs.scrollTop = msgs.scrollHeight;
        });
        topicChannel.listenForWhisper('typing', (e) => {
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
</script>
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
</body>
</html>
