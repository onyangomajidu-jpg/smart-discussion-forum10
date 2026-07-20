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
        .sidebar-header { padding: 16px; border-bottom: 1px solid #e2e8f0; }
        .search-bar { width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; margin-bottom: 10px; outline: none; }
        .search-bar:focus { border-color: #667eea; }
        .btn-create { width: 100%; padding: 9px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; }
        .btn-create:hover { opacity: 0.9; }
        .topic-list { flex: 1; overflow-y: auto; }
        .topic-item { padding: 14px 16px; border-bottom: 1px solid #f0f2f5; cursor: pointer; transition: background 0.2s; }
        .topic-item:hover, .topic-item.active { background: #f0f0ff; }
        .topic-item h4 { font-size: 14px; color: #2d3748; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .topic-meta { font-size: 12px; color: #718096; display: flex; justify-content: space-between; }
        .topic-badges { display: flex; gap: 4px; margin-top: 4px; }
        .badge { font-size: 10px; padding: 1px 6px; border-radius: 10px; font-weight: 600; }
        .badge-locked { background: #fed7d7; color: #9b2c2c; }
        .badge-pinned { background: #fefcbf; color: #744210; }

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

        .alert { padding: 10px 16px; border-radius: 7px; margin-bottom: 12px; font-size: 14px; }
        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #9b2c2c; }
    </style>
</head>
<body>

<nav class="navbar">
    <h1>🎓 Smart Discussion Forum — Lecturer</h1>
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

    {{-- Sidebar --}}
    <aside class="sidebar">
        <div class="sidebar-header">
            <form method="GET" action="{{ route('lecturer.topics.index') }}">
                <input type="text" name="search" class="search-bar" placeholder="🔍 Search topics..."
                    value="{{ request('search') }}" oninput="this.form.submit()">
            </form>
            <button class="btn-create" onclick="document.getElementById('createModal').classList.add('open')">
                + Create Topic
            </button>
        </div>
        <div class="topic-list">
            @forelse($topics as $topic)
                <div class="topic-item {{ isset($activeTopic) && $activeTopic->id === $topic->id ? 'active' : '' }}"
                     onclick="window.location='{{ route('lecturer.topics.show', $topic) }}'">
                    <h4>{{ $topic->title }}</h4>
                    <div class="topic-meta">
                        <span>{{ $topic->author->name }}</span>
                        <span>{{ $topic->posts_count }} posts · {{ $topic->views }} views</span>
                    </div>
                    <div class="topic-badges">
                        @if($topic->is_pinned) <span class="badge badge-pinned">📌 Pinned</span> @endif
                        @if($topic->is_locked) <span class="badge badge-locked">🔒 Locked</span> @endif
                    </div>
                    <form action="{{ route('lecturer.topics.destroy', $topic) }}" method="POST"
                          onsubmit="return confirm('Delete this topic?')" onclick="event.stopPropagation()" style="margin-top:6px;">
                        @csrf @method('DELETE')
                        <button type="submit" style="font-size:11px;color:#e53e3e;background:none;border:1px solid #e53e3e;border-radius:4px;padding:2px 8px;cursor:pointer;">🗑 Delete</button>
                    </form>
                </div>
            @empty
                <div style="padding:20px;text-align:center;color:#a0aec0;font-size:14px;">No topics yet.</div>
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
                            <button class="btn-sm btn-edit" onclick="editPost({{ $post->id }}, `{{ addslashes($post->body) }}`)">✏ Edit</button>
                            <button class="btn-sm btn-delete" onclick="deletePost({{ $post->id }})">🗑 Delete</button>
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
                @else
                    <div class="participant-actions">
                        <form action="{{ route('lecturer.topics.blockUser', [$activeTopic, $participant->id]) }}" method="POST"
                              onsubmit="return confirm('Block {{ addslashes($participant->name) }}?')">
                            @csrf
                            <button type="submit" class="btn-block-user">Block</button>
                        </form>
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

        <div class="section-label" style="margin-top:8px;">🚫 Blocked</div>
        @forelse($activeTopic->blockedParticipants as $blocked)
            <div class="participant-item blocked-item">
                <span class="participant-name">{{ $blocked->name }}</span>
                <form action="{{ route('lecturer.topics.unblockUser', [$activeTopic, $blocked->id]) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-unblock-user">Unblock</button>
                </form>
            </div>
        @empty
            <div style="padding:10px 14px;font-size:13px;color:#a0aec0;">No blocked users.</div>
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

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let editingPostId = null;
    let typingTimer = null;

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
