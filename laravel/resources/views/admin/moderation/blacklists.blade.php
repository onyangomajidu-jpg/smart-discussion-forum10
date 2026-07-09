@extends('layouts.app')
@section('title', 'Blacklist Log')

@section('content')
<div class="page-header">
    <div class="breadcrumb"><a href="{{ route('admin.dashboard') }}">Admin</a><span class="sep">/</span> Blacklist Log</div>
    <h1><i class="fa-solid fa-ban"></i> Blacklist Log</h1>
</div>

<div class="card" style="margin-bottom:24px">
    <div class="card-header"><h2><i class="fa-solid fa-plus"></i> Blacklist User</h2></div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.blacklists.store') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            @csrf
            <div class="form-group" style="flex:1;min-width:160px;margin:0">
                <label class="form-label">User</label>
                <select name="user_id" class="form-control" required>
                    <option value="">Select user…</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" style="flex:2;min-width:200px;margin:0">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" placeholder="Reason for ban" required>
            </div>
            <div class="form-group" style="width:100px;margin:0">
                <label class="form-label">Days</label>
                <input type="number" name="days" class="form-control" value="30" min="1" max="365" required>
            </div>
            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Blacklist</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h2><i class="fa-solid fa-list"></i> All Blacklist Entries</h2></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>User</th><th>Reason</th><th>Banned By</th><th>Banned At</th><th>Expires At</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($blacklists as $b)
                <tr>
                    <td>{{ $b->id }}</td>
                    <td>{{ $b->user->name ?? '—' }}</td>
                    <td>{{ $b->reason }}</td>
                    <td>{{ $b->banner->name ?? '—' }}</td>
                    <td>{{ $b->created_at->format('M d, Y') }}</td>
                    <td>{{ $b->expires_at ? $b->expires_at->format('M d, Y') : 'Permanent' }}</td>
                    <td>
                        @if($b->isActive())
                            <span class="badge badge-closed">Active</span>
                        @else
                            <span class="badge badge-published">Expired</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.blacklists.destroy', $b->id) }}" onsubmit="return confirm('Remove this ban?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-secondary btn-sm"><i class="fa-solid fa-unlock"></i> Lift Ban</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:32px">No blacklist entries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($blacklists->hasPages())
    <div style="padding:16px 24px">{{ $blacklists->links() }}</div>
    @endif
</div>
@endsection
