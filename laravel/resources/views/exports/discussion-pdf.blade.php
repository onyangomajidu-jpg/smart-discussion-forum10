<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; background: #fff; }

  /* ── Header banner ── */
  .header {
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: #fff;
    padding: 22px 32px 18px;
    border-radius: 0 0 8px 8px;
  }
  .header .brand { font-size: 18px; font-weight: bold; letter-spacing: 0.5px; }
  .header .brand span { opacity: 0.75; font-weight: normal; font-size: 13px; margin-left: 8px; }
  .header .topic-title { font-size: 15px; font-weight: bold; margin-top: 8px; line-height: 1.4; }
  .header .meta { font-size: 10px; opacity: 0.85; margin-top: 6px; }

  /* ── Info bar ── */
  .info-bar {
    display: table; width: 100%; margin: 16px 0 12px;
    border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden;
  }
  .info-cell {
    display: table-cell; padding: 10px 16px;
    border-right: 1px solid #e2e8f0; background: #f8fafc;
    font-size: 10px; vertical-align: middle;
  }
  .info-cell:last-child { border-right: none; }
  .info-cell .label { color: #64748b; text-transform: uppercase; font-size: 9px; letter-spacing: 0.5px; }
  .info-cell .value { font-weight: bold; color: #0f172a; margin-top: 2px; }

  /* ── Topic body ── */
  .topic-body {
    background: #f1f5f9; border-left: 4px solid #6366f1;
    padding: 14px 18px; border-radius: 0 6px 6px 0;
    margin-bottom: 20px; font-size: 11.5px; line-height: 1.6; color: #1e293b;
  }

  /* ── Section heading ── */
  .section-heading {
    font-size: 12px; font-weight: bold; color: #6366f1;
    border-bottom: 2px solid #e2e8f0; padding-bottom: 6px;
    margin: 20px 0 12px; text-transform: uppercase; letter-spacing: 0.5px;
  }

  /* ── Post card ── */
  .post-card {
    border: 1px solid #e2e8f0; border-radius: 6px;
    margin-bottom: 14px; overflow: hidden; page-break-inside: avoid;
  }
  .post-header {
    background: #f8fafc; padding: 8px 14px;
    border-bottom: 1px solid #e2e8f0;
    display: table; width: 100%;
  }
  .post-author { display: table-cell; font-weight: bold; font-size: 11px; color: #0f172a; }
  .post-date   { display: table-cell; text-align: right; font-size: 10px; color: #64748b; }
  .post-best   {
    display: inline-block; background: #10b981; color: #fff;
    font-size: 9px; padding: 1px 6px; border-radius: 10px; margin-left: 6px;
  }
  .post-body { padding: 10px 14px; line-height: 1.6; color: #1e293b; }

  /* ── Reply ── */
  .reply {
    margin: 8px 14px 8px 28px;
    border-left: 3px solid #c7d2fe; padding: 6px 10px;
    background: #f8fafc; border-radius: 0 4px 4px 0;
    page-break-inside: avoid;
  }
  .reply-author { font-weight: bold; font-size: 10px; color: #6366f1; }
  .reply-date   { font-size: 9px; color: #94a3b8; margin-left: 6px; }
  .reply-body   { font-size: 10.5px; color: #334155; margin-top: 3px; line-height: 1.5; }

  /* ── Footer ── */
  .footer {
    margin-top: 28px; padding-top: 10px;
    border-top: 1px solid #e2e8f0;
    font-size: 9px; color: #94a3b8; text-align: center;
  }

  .content { padding: 0 32px 32px; }
</style>
</head>
<body>

{{-- ── Header ── --}}
<div class="header">
  <div class="brand">🎓 Discussion Hub <span>Discussion Export</span></div>
  <div class="topic-title">{{ $topic->title }}</div>
  <div class="meta">
    Group: {{ $topic->group->name ?? 'General' }}
    &nbsp;·&nbsp;
    Exported: {{ now()->format('d M Y, H:i') }}
  </div>
</div>

<div class="content">

  {{-- ── Info bar ── --}}
  <div class="info-bar">
    <div class="info-cell">
      <div class="label">Author</div>
      <div class="value">{{ $topic->author->name }}</div>
    </div>
    <div class="info-cell">
      <div class="label">Created</div>
      <div class="value">{{ $topic->created_at->format('d M Y') }}</div>
    </div>
    <div class="info-cell">
      <div class="label">Posts</div>
      <div class="value">{{ $topic->posts->count() }}</div>
    </div>
    <div class="info-cell">
      <div class="label">Views</div>
      <div class="value">{{ number_format($topic->views) }}</div>
    </div>
    <div class="info-cell">
      <div class="label">Status</div>
      <div class="value">{{ $topic->is_locked ? 'Locked' : 'Open' }}</div>
    </div>
  </div>

  {{-- ── Topic body ── --}}
  <div class="topic-body">{{ $topic->body }}</div>

  {{-- ── Posts ── --}}
  @if($topic->posts->isNotEmpty())
  <div class="section-heading">Discussion Thread ({{ $topic->posts->count() }} posts)</div>

  @foreach($topic->posts as $i => $post)
  <div class="post-card">
    <div class="post-header">
      <div class="post-author">
        {{ $post->author->name }}
        @if($post->is_best_answer)
          <span class="post-best">✓ Best Answer</span>
        @endif
      </div>
      <div class="post-date">
        Post #{{ $i + 1 }}
        &nbsp;·&nbsp;
        {{ $post->created_at->format('d M Y, H:i') }}
        @if($post->upvotes > 0)
          &nbsp;·&nbsp; ▲ {{ $post->upvotes }}
        @endif
      </div>
    </div>
    <div class="post-body">{{ $post->body }}</div>

    {{-- Replies --}}
    @foreach($post->replies as $reply)
    <div class="reply">
      <span class="reply-author">↳ {{ $reply->author->name }}</span>
      <span class="reply-date">{{ $reply->created_at->format('d M Y, H:i') }}</span>
      <div class="reply-body">{{ $reply->body }}</div>
    </div>
    @endforeach
  </div>
  @endforeach

  @else
  <p style="color:#64748b;font-style:italic;margin-top:12px;">No posts in this discussion yet.</p>
  @endif

  {{-- ── Footer ── --}}
  <div class="footer">
    Generated by Discussion Hub &nbsp;·&nbsp; {{ config('app.url') }} &nbsp;·&nbsp; {{ now()->format('d M Y H:i:s') }}
  </div>

</div>
</body>
</html>
