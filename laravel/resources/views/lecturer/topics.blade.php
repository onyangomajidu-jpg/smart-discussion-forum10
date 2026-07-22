<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Topic Participation - Lecturer</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; flex-direction: column; height: 100vh; }

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
        .btn-logout { background: rgba(255,255,255,0.2); padding: 6px 14px; border: 1px solid white; border-radius: 6px; color: white; cursor: pointer; text-decoration: none; font-size: 14px; }

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

            .sidebar, .participants-panel {
                position: fixed; top: 0; height: 100vh; z-index: 500;
                transition: transform .25s ease;
            }
            .sidebar { left: 0; transform: translateX(-100%); width: 85%; max-width: 300px; }
            .sidebar.open { transform: translateX(0); }
            .participants-panel { right: 0; transform: translateX(100%); width: 80%; max-width: 230px; }
            .participants-panel.open { transform: translateX(0); }

            .conversation { width: 100%; }
            .conv-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .conv-header-actions { flex-wrap: wrap; }
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

        .messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
        .post-card { background: white; border-radius: 10px; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
        .post-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .post-author { font-weight: 600; font-size: 14px; color: #4a5568; }
        .post-time { font-size: 12px; color: #a0aec0; }
        .post-body { font-size: 14px; color: #2d3748; line-height: 1.6; }
        .post-actions { margin-top: 10px; display: flex; gap: 8px; }
        .btn-sm { padding: 4px 10px; font-size: 12px; border: 1px solid #e2e8f0; border-radius: 5px; cursor: pointer; background: white; }
        .btn-reply { color: #667eea; border-color: #667eea; }
        .btn-edit { color: #38a169; border-color: #38a169; }
        .btn-delete { color: #e53e3e; border-color: #e53e3e; }

        .replies { margin-top: 12px; padding-left: 20px; border-left: 3px solid #e2e8f0; display: flex; flex-direction: column; gap: 10px; }
        .reply-card { background: #f7fafc; border-radius: 8px; padding: 10px 14px; }
        .reply-author { font-weight: 600; font-size: 13px; color: #4a5568; }
        .reply-body { font-size: 13px; color: #4a5568; margin-top: 4px; }

        .typing-indicator { padding: 6px 20px; font-size: 13px; color: #718096; font-style: italic; min-height: 28px; }
        .typing-dots span { display: inline-block; width: 6px; height: 6px; background: #718096; border-radius: 50%; margin: 0 2px; animation: bounce 1.2s infinite; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

        .input-area { padding: 16px 20px; background: white; border-top: 1px solid #e2e8f0; }
        .input-row { display: flex; gap: 10px; align-items: flex-end; }
        .msg-input { flex: 1; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; resize: none; outline: none; font-family: inherit; }
        .msg-input:focus { border-color: #667eea; }
        .btn-send { padding: 10px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }

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
    <div style="display:flex;align-items:center;gap:10px;">
        <button class="mobile-toggle-btn" id="topicsToggleBtn" type="button" aria-label="Toggle topics list">☰</button>
        <h1><img src="{{ asset('images/forum.png') }}" alt="Discussion Hub" style="height:34px;vertical-align:middle;margin-right:8px;"><span class="full-title">Discussion Hub</span></h1>
    </div>
    <div class="navbar-right">
        <button class="notif-btn" onclick="loadNotifications()">
            🔔
            @if(auth()->user()->unreadNotifications->count() > 0)
                <span class="notif-badge">{{ auth()->user()->unreadNotifications->count() }}</span>
            @endif
        </button>
        <span>{{ auth()->user()->name }}</span>
        <a href="{{ route('lecturer.dashboard') }}" class="btn-logout">← Dashboard</a>
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
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-error">{{ $errors->first() }}</div>
                @endif

                {{-- Original topic body --}}
                <div class="post-card" style="border-left: 4px solid #667eea;">
                    <div class="post-header">
                        <span class="post-author">{{ $activeTopic->author->name }}</span>
                        <span class="post-time">{{ $activeTopic->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="post-body">{{ $activeTopic->body }}</div>
                </div>

                {{-- Posts --}}
                @foreach($posts as $post)
                    <div class="post-card" id="post-{{ $post->id }}">
                        <div class="post-header">
                            <span class="post-author">{{ $post->author->name }}</span>
                            <span class="post-time">{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="post-body" id="post-body-{{ $post->id }}">{{ $post->body }}</div>
                        <div class="post-actions">
                            <button class="btn-sm btn-reply" onclick="toggleReplyForm({{ $post->id }})">↩ Reply</button>
                            @if($post->user_id === auth()->id())
                                <button class="btn-sm btn-edit" onclick="editPost({{ $post->id }}, `{{ addslashes($post->body) }}`)">✏ Edit</button>
                                <button class="btn-sm btn-delete" onclick="deletePost({{ $post->id }})">🗑 Delete</button>
                            @endif
                        </div>

                        <form id="reply-form-{{ $post->id }}" style="display:none;margin-top:10px;"
                              action="{{ route('lecturer.topics.answer', $post->id) }}" method="POST">
                            @csrf
                            <div style="display:flex;gap:8px;">
                                <input type="text" name="body" placeholder="Write a reply..." class="msg-input" style="flex:1;padding:8px 12px;">
                                <button type="submit" class="btn-send" style="padding:8px 14px;">Send</button>
                            </div>
                        </form>

                        @if($post->replies->count())
                            <div class="replies">
                                @foreach($post->replies as $reply)
                                    <div class="reply-card">
                                        <span class="reply-author">{{ $reply->author->name }}</span>
                                        <span style="font-size:11px;color:#a0aec0;margin-left:8px;">{{ $reply->created_at->diffForHumans() }}</span>
                                        <div class="reply-body">{{ $reply->body }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="typing-indicator" id="typingIndicator"></div>

            @if(!$activeTopic->is_locked)
                <div class="input-area">
                    <form action="{{ route('lecturer.topics.participate', $activeTopic->id) }}" method="POST">
                        @csrf
                        <div class="input-row">
                            <textarea name="body" class="msg-input" rows="2"
                                placeholder="Write a message..." required
                                oninput="handleTyping()"></textarea>
                            <button type="submit" class="btn-send">Send</button>
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
        const topicBody   = document.querySelector('.post-card[style*="border-left"] .post-body');
        const topicAuthor = document.querySelector('.post-card[style*="border-left"] .post-author');
        const topicTime   = document.querySelector('.post-card[style*="border-left"] .post-time');
        if (topicAuthor && topicBody)
            lines.push('[' + (topicTime ? topicTime.textContent.trim() : '') + '] ' + topicAuthor.textContent.trim() + ': ' + topicBody.textContent.trim(), '');
        document.querySelectorAll('.post-card:not([style*="border-left"])').forEach(card => {
            const author = card.querySelector('.post-author');
            const body   = card.querySelector('.post-body');
            const time   = card.querySelector('.post-time');
            if (author && body) {
                lines.push('[' + (time ? time.textContent.trim() : '') + '] ' + author.textContent.trim() + ': ' + body.textContent.trim());
                card.querySelectorAll('.reply-card').forEach(r => {
                    const ra = r.querySelector('.reply-author');
                    const rb = r.querySelector('.reply-body');
                    const rt = r.querySelector('span[style*="color:#a0aec0"]');
                    if (ra && rb) lines.push('  ↩ [' + (rt ? rt.textContent.trim() : '') + '] ' + ra.textContent.trim() + ': ' + rb.textContent.trim());
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
</script>
</body>
</html>
