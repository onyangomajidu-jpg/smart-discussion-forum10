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
        <h3>ðŸŒ Share Discussion</h3>
        <p style="font-size:13px;color:#718096;margin-bottom:14px;">Choose a platform to share the entire conversation.</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
            <button class="share-card" data-platform="whatsapp" onclick="selectSharePlatform(this)">
                <span style="font-size:22px;">ðŸ’¬</span> WhatsApp
            </button>
            <button class="share-card" data-platform="twitter" onclick="selectSharePlatform(this)">
                <span style="font-size:22px;">ð•</span> Twitter / X
            </button>
            <button class="share-card" data-platform="facebook" onclick="selectSharePlatform(this)">
                <span style="font-size:22px;">ðŸ“˜</span> Facebook
            </button>
            <button class="share-card" data-platform="linkedin" onclick="selectSharePlatform(this)">
                <span style="font-size:22px;">ðŸ’¼</span> LinkedIn
            </button>
        </div>
        <div id="shareStatus"></div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="document.getElementById('shareModal').classList.remove('open')">Cancel</button>
            <button class="btn-submit" id="shareBtn" onclick="submitShare()" disabled style="opacity:0.5;">ðŸš€ Share Now</button>
        </div>
    </div>
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
        let lines = ['ðŸ“š Discussion: "' + title + '"', ''];
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
                    if (ra) lines.push('  â†© ' + ra.textContent.trim() + ': ' + (rb ? rb.textContent.trim() : ''));
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
        const shortText = encodeURIComponent('ðŸ“š "' + document.querySelector('.conv-header h2').textContent.trim() + '" â€” join the discussion on Discussion Hub');
        const urls = {
            whatsapp: 'https://wa.me/?text=' + text,
            twitter:  'https://twitter.com/intent/tweet?text=' + shortText + '&url=' + topicUrl,
            facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + topicUrl + '&quote=' + text,
            linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + topicUrl,
        };
        window.open(urls[selectedPlatform], '_blank', 'noopener,noreferrer');
        const statusEl = document.getElementById('shareStatus');
        statusEl.style.color = '#276749';
        statusEl.textContent = 'âœ… ' + selectedPlatform.charAt(0).toUpperCase() + selectedPlatform.slice(1) + ' opened in a new tab.';
    }

    let replyingToPostId = null;

    function setReply(postId, author, body, parentReplyId) {
        replyingToPostId = postId;
        document.getElementById('replyBarAuthor').textContent = author;
        document.getElementById('replyBarBody').textContent = body;
        document.getElementById('replyBar').style.display = 'flex';
        document.getElementById('postInput').focus();
        document.getElementById('replyForm').action = '/lecturer/posts/' + postId + '/answer';
        document.getElementById('replyFormParentId').value = parentReplyId || '';
    }
    function cancelReply() {
        replyingToPostId = null;
        document.getElementById('replyBar').style.display = 'none';
        document.getElementById('replyForm').action = '';
        document.getElementById('replyFormParentId').value = '';
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

    function toggleReplyForm(postId) {
        setReply(postId, '', '');
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
                const msgs = data.map(n => `â€¢ ${n.data.user}: ${n.data.excerpt}`).join('\n');
                alert(msgs || 'No notifications.');
                const badge = document.querySelector('.notif-badge');
                if (badge) badge.remove();
            });
    }

    @if(isset($activeTopic))
    document.addEventListener('DOMContentLoaded', () => {
        const palette = ['#e91e8c','#00bcd4','#4caf50','#ff9800','#9c27b0','#f44336','#2196f3','#009688'];
        function nameColor(name) { return palette[Math.abs(name.split('').reduce((a,c)=>a+c.charCodeAt(0),0)) % palette.length]; }
        const myId = {{ auth()->id() }};
        let lastFetch = new Date().toISOString();

        function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        const storageBase = '{{ rtrim(Storage::url(""), "/") }}/';
        function storageUrl(path) { return path ? storageBase + path : ''; }

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
            if (post.image_path) {
                const imgUrl = storageUrl(post.image_path);
                const authorTag = (!post.body && !isMe) ? `<span class="bubble-author" style="color:${color};display:block;padding:5px 8px 0;font-size:12.5px;font-weight:700">${escHtml(authorName)}</span>` : '';
                inner += `${authorTag}<div class="img-msg-bubble"><img src="${imgUrl}" alt="Image" loading="lazy"><span class="img-time-badge">${timeStr}</span><a href="${imgUrl}" download class="btn-img-save" title="Save image">&#8595;</a></div>`;
            }
            if (post.file_path) {
                const ext = (post.file_name || '').split('.').pop().toLowerCase();
                const icons = {pdf:'ðŸ“•',doc:'ðŸ“˜',docx:'ðŸ“˜',xls:'ðŸ“—',xlsx:'ðŸ“—',csv:'ðŸ“—',ppt:'ðŸ“™',pptx:'ðŸ“™',zip:'ðŸ—œï¸',rar:'ðŸ—œï¸','7z':'ðŸ—œï¸',mp3:'ðŸŽµ',wav:'ðŸŽµ',ogg:'ðŸŽµ',mp4:'ðŸŽ¬',mov:'ðŸŽ¬',avi:'ðŸŽ¬'};
                const fileIcon = icons[ext] || 'ðŸ“„';
                const fileUrl = storageUrl(post.file_path);
                const rawSize = post.file_size || 0;
                const fileSize = rawSize >= 1048576 ? (rawSize/1048576).toFixed(1)+'MB' : Math.round(rawSize/1024)+'KB';
                const authorTag = (!post.body && !isMe) ? `<span class="bubble-author" style="color:${color};display:block;font-size:12.5px;font-weight:700;margin-bottom:3px">${escHtml(authorName)}</span>` : '';
                inner += `${authorTag}<div class="file-msg-bubble"><div class="file-type-icon">${fileIcon}</div><div class="file-info"><div class="fname" title="${escHtml(post.file_name||'Document')}">${escHtml(post.file_name||'Document')}</div><div class="fmeta"><span>${ext.toUpperCase()}</span><span class="fmeta-dot"></span><span>${fileSize}</span></div></div><a href="${fileUrl}" download="${escHtml(post.file_name||'file')}" class="btn-file-dl" title="Download">&#8595;</a></div><div class="file-bubble-footer"><span class="file-bubble-time">${timeStr}</span></div>`;
            }
            if (post.audio_path) {
                const audioUrl = storageUrl(post.audio_path);
                const authorTag = (!post.body && !isMe) ? `<span class="bubble-author" style="color:${color};display:block;font-size:12.5px;font-weight:700;margin-bottom:3px">${escHtml(authorName)}</span>` : '';
                const waveHeights = [8,14,20,28,22,16,26,18,10,24,20,14,22,8,18,26,12,20,30,14];
                const waveBars = waveHeights.map(h => `<span style="height:${h}px"></span>`).join('');
                inner += `${authorTag}<div class="audio-msg-bubble"><button class="audio-play-btn" onclick="toggleAudio(this)" type="button">&#9654;</button><div style="flex:1;display:flex;flex-direction:column;gap:3px;min-width:0;"><span class="audio-label">Voice message</span><div class="audio-waveform">${waveBars}</div></div><span class="audio-duration">0:00</span><audio preload="auto" src="${audioUrl}" style="display:none"></audio></div><div class="audio-bubble-footer">${timeStr}</div>`;
            }
            let actionsHtml = `<div class="chat-actions"><button class="btn-sm btn-reply" title="Reply" onclick="setReply(${post.id},'${isMe?'You':authorName.replace(/'/g,"\\'")}','${escHtml(post.body||'Attachment').replace(/'/g,"\\'")}');">&#8617;</button>`;
            if (isMe) actionsHtml += `<button class="btn-sm btn-edit" title="Edit" onclick="editPost(${post.id},\`${(post.body||'').replace(/`/g,'\\`')}\`)">&#9998;</button><button class="btn-sm btn-delete" title="Delete" onclick="deletePost(${post.id})">&#128465;</button>`;
            actionsHtml += '</div>';
            row.innerHTML =
                (!isMe ? avatarHtml : '') +
                `<div class="chat-bubble-wrap">${inner}${actionsHtml}</div>` +
                (isMe ? avatarHtml : '');
            // Wire up audio players added dynamically
            row.querySelectorAll('.audio-msg-bubble').forEach(function(bubble) {
                const audio = bubble.querySelector('audio');
                const durEl = bubble.querySelector('.audio-duration');
                audio.addEventListener('loadedmetadata', function() {
                    if (audio.duration === Infinity || isNaN(audio.duration)) {
                        audio.currentTime = 1e101;
                        audio.addEventListener('timeupdate', function onFix() {
                            audio.removeEventListener('timeupdate', onFix);
                            audio.currentTime = 0;
                            if (isFinite(audio.duration)) durEl.textContent = fmtTime(audio.duration);
                        }, { once: true });
                    } else { durEl.textContent = fmtTime(audio.duration); }
                });
                audio.addEventListener('timeupdate', function() { durEl.textContent = fmtTime(audio.currentTime); });
                audio.addEventListener('ended', function() {
                    bubble.querySelector('.audio-play-btn').innerHTML = '&#9654;';
                    bubble.querySelector('.audio-waveform').classList.remove('playing');
                    if (isFinite(audio.duration)) durEl.textContent = fmtTime(audio.duration);
                });
            });
            return row;
        }

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
                        var _row = buildBubble(post);
                        var _ck = 'topic_cleared_{{ auth()->id() }}_' + {{ $activeTopic->id }};
                        var _ct = localStorage.getItem(_ck);
                        if (_ct && post.created_at && post.created_at <= _ct) _row.style.display = 'none';
                        container.appendChild(_row);
                    });
                    if (atBottom) container.scrollTop = container.scrollHeight;
                })
                .catch(() => {});
        }

        setInterval(pollPosts, 3000);

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

    // â”€â”€ Clear Chat (device-only, localStorage) â”€â”€
    function clearTopicChat(topicId) {
        if (!confirm('Clear this chat on this device only?\nOther users will not be affected.')) return;
        const key = 'topic_cleared_{{ auth()->id() }}_' + topicId;
        const ts  = new Date().toISOString();
        localStorage.setItem(key, ts);
        document.querySelectorAll('#messages .chat-row[data-ts]').forEach(function(row) {
            if (row.dataset.ts <= ts) row.style.display = 'none';
        });
    }
    // Apply existing clear on page load
    (function() {
        @if(isset($activeTopic))
        const key = 'topic_cleared_{{ auth()->id() }}_{{ $activeTopic->id }}';
        const ts  = localStorage.getItem(key);
        if (!ts) return;
        document.querySelectorAll('#messages .chat-row[data-ts]').forEach(function(row) {
            if (row.dataset.ts <= ts) row.style.display = 'none';
        });
        @endif
    })();

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
    });

    const msgs = document.getElementById('messages');
    if (msgs) msgs.scrollTop = msgs.scrollHeight;

    // â”€â”€ Select message row on click to reveal actions â”€â”€
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

    // â”€â”€ Audio bubble player â”€â”€
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

    // â”€â”€ Send/mic toggle â”€â”€
    function updateSendBtn() {
        const val = document.getElementById('postInput') && document.getElementById('postInput').value.trim();
        const imgInput2 = document.getElementById('imgInput');
        const docInput2 = document.getElementById('docInput');
        const hasAttach = (imgInput2 && imgInput2.files[0]) || (docInput2 && docInput2.files[0]);
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

    // â”€â”€ Attachment toolbar â”€â”€
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
                previewThumb.textContent = 'ðŸ“„';
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

    // â”€â”€ Audio Recorder â”€â”€
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
                // Not every browser supports the same container â€” Safari/iOS
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

        // Send audio independently â€” no text required
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
</div>

<script>
setInterval(function() { fetch('/api/ping', {credentials:'same-origin'}).catch(function(){}); }, 240000);
</script>
</body>
</html>
