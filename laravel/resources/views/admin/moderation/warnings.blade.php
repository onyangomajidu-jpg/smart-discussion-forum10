@extends('layouts.app')
@section('title', 'Warning Registry')

@section('content')
<div class="page-header">
    <div class="breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin</a><span class="sep">/</span> Warning Registry</div>
    <h1><i class="fa-solid fa-triangle-exclamation"></i> Warning Registry</h1>
</div>

<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h2><i class="fa-solid fa-plus"></i> Issue Warning</h2></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.warnings.store') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            @csrf
            <div class="form-group" style="flex:1;min-width:180px;margin:0">
                <label class="form-label">User</label>
                <select name="user_id" class="form-control" required>
                    <option value="">Select user…</option>
                    @foreach(\App\Models\User::where('role','member')->orderBy('name')->get() as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:2;min-width:220px;margin:0">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" placeholder="Reason for warning" required>
            </div>
            <div class="form-group" style="flex:0 0 160px;margin:0">
                <label class="form-label">Auto-ban duration (days)</label>
                <input type="number" name="auto_blacklist_days" class="form-control" value="30" min="1" max="365" required>
            </div>
            <button type="submit" class="btn btn-warning"><i class="fa-solid fa-triangle-exclamation"></i> Issue Warning</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2><i class="fa-solid fa-list"></i> All Warnings</h2></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>User</th><th>Reason</th><th>Issued By</th><th>Issued At</th><th>Status</th><th>Resolved By</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($warnings as $w)
                <tr>
                    <td>{{ $w->id }}</td>
                    <td>{{ $w->user->name ?? '—' }}</td>
                    <td>{{ $w->reason }}</td>
                    <td>{{ $w->issuer->name ?? '—' }}</td>
                    <td>{{ $w->created_at->format('M d, Y') }}</td>
                    <td>
                        @if($w->isResolved())
                            <span class="badge badge-published">Resolved</span>
                        @else
                            <span class="badge badge-closed">Unresolved</span>
                        @endif
                    </td>
                    <td>{{ $w->resolver->name ?? '—' }}</td>
                    <td style="display:flex;gap:6px">
                        @if(!$w->isResolved())
                        <form method="POST" action="{{ route('admin.warnings.resolve', $w->id) }}">
                            @csrf @method('PATCH')
                            <button class="btn btn-success btn-sm"><i class="fa-solid fa-check"></i> Resolve</button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('admin.warnings.destroy', $w->id) }}" onsubmit="return confirm('Delete this warning?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:32px">No warnings found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($warnings->hasPages())
    <div style="padding:16px 24px">{{ $warnings->links() }}</div>
    @endif
</div>
@endsection
