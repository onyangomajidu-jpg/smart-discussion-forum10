@extends('layouts.app')

@section('title', 'My Groups — SmartForum')

@push('styles')
<style>
@media(max-width:640px) {
    .groups-create-grid {
        grid-template-columns: 1fr !important;
    }
    .groups-create-grid .btn { width:100%; justify-content:center; }
    .groups-table thead { display:none; }
    .groups-table tbody tr {
        display:flex; flex-direction:column; gap:6px;
        padding:14px 16px; border-bottom:1px solid #f1f5f9;
    }
    .groups-table tbody tr:last-child { border-bottom:none; }
    .groups-table tbody td {
        display:flex; align-items:center; justify-content:space-between;
        padding:0; border:none; font-size:13px;
    }
    .groups-table tbody td[data-label]::before {
        content:attr(data-label);
        font-size:10px; font-weight:700; color:#94a3b8;
        text-transform:uppercase; letter-spacing:.5px; flex-shrink:0; margin-right:8px;
    }
    .groups-table tbody td form { margin-left:auto; }
}
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('lecturer.dashboard') }}"><i class="fa-solid fa-house"></i> Dashboard</a>
    <span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></span>
    <span>My Groups</span>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">
    <div class="page-header" style="margin-bottom:0">
        <h1><i class="fa-solid fa-people-group" style="color:#6366f1"></i> My Groups</h1>
        <p>Create and manage your class groups</p>
    </div>
</div>

@if(session('success'))
    <div style="background:#d1fae5;color:#065f46;padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:13px">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
    </div>
@endif

{{-- Create Group Form --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h2><i class="fa-solid fa-circle-plus"></i> Create New Group</h2>
    </div>
    <div class="card-body">
        <form action="{{ route('lecturer.groups.store') }}" method="POST">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 2fr auto;gap:14px;align-items:end" class="groups-create-grid">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Group Name <span style="color:#ef4444">*</span></label>
                    <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                           value="{{ old('name') }}" placeholder="e.g. CS101 - Group A" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control"
                           value="{{ old('description') }}" placeholder="Optional description">
                </div>
                <button type="submit" class="btn btn-primary" style="white-space:nowrap">
                    <i class="fa-solid fa-plus"></i> Create Group
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Groups List --}}
@if($groups->isEmpty())
    <div style="text-align:center;padding:80px 40px;background:#fff;border-radius:18px;border:2px dashed #e2e8f0">
        <i class="fa-solid fa-people-group" style="font-size:56px;color:#c7d2fe;margin-bottom:20px;display:block"></i>
        <h3 style="font-size:20px;font-weight:700;margin-bottom:8px">No groups yet</h3>
        <p style="color:#64748b">Create your first group above to get started.</p>
    </div>
@else
    <div class="card">
        <div class="card-header">
            <h2><i class="fa-solid fa-list"></i> Your Groups</h2>
            <span style="font-size:12px;color:#64748b;font-weight:600">{{ $groups->count() }} group(s)</span>
        </div>
        <div class="table-wrap">
            <table class="groups-table">
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Description</th>
                        <th>Members</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                    <tr>
                        <td data-label="Group">
                            <div style="display:flex;align-items:center;gap:10px">
                                <div style="width:36px;height:36px;border-radius:9px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <span style="font-weight:700;color:#0f172a">{{ $group->name }}</span>
                            </div>
                        </td>
                        <td data-label="Description" style="color:#64748b;font-size:13px">{{ $group->description ?? '—' }}</td>
                        <td data-label="Members">
                            <span style="background:#ede9fe;color:#5b21b6;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:700">
                                <i class="fa-solid fa-user"></i> {{ $group->members_count }}
                            </span>
                        </td>
                        <td data-label="Created" style="color:#64748b;font-size:12px">{{ $group->created_at->format('d M Y') }}</td>
                        <td data-label="Actions">
                            <form action="{{ route('lecturer.groups.destroy', $group) }}" method="POST"
                                  onsubmit="return confirm('Delete group \'{{ $group->name }}\'? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm" style="background:#fee2e2;color:#ef4444;border:none">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
