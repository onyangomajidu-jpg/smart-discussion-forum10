@extends('layouts.app')

@section('title', 'Groups — SmartForum')

@push('styles')
<style>
@media(max-width:640px){
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
    .groups-table tbody td:first-child { font-size:14px; }
    .groups-table tbody td form { margin-left:auto; }
}
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="fa-solid fa-house"></i> Dashboard</a>
    <span class="sep"><i class="fa-solid fa-chevron-right" style="font-size:9px"></i></span>
    <span>Groups</span>
</div>

<div class="page-header">
    <h1><i class="fa-solid fa-people-group" style="color:#6366f1"></i> Groups</h1>
    <p>Join groups to access their topics and quizzes.</p>
</div>

{{-- My Groups --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <h2><i class="fa-solid fa-users"></i> My Groups</h2>
        <span style="font-size:12px;color:#64748b;font-weight:600">{{ $joined->count() }} joined</span>
    </div>
    @if($joined->isEmpty())
        <div style="text-align:center;padding:40px;color:#64748b;font-size:13px">
            <i class="fa-solid fa-users" style="font-size:28px;color:#c7d2fe;display:block;margin-bottom:10px"></i>
            You haven't joined any groups yet.
        </div>
    @else
        <div class="table-wrap">
            <table class="groups-table">
                <thead>
                    <tr><th>Group</th><th>Description</th><th>Members</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($joined as $group)
                    <tr>
                        <td data-label="Group" style="font-weight:700">{{ $group->name }}</td>
                        <td data-label="Description" style="color:#64748b;font-size:13px">{{ $group->description ?? '—' }}</td>
                        <td data-label="Members"><span style="background:#ede9fe;color:#5b21b6;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:700">{{ $group->members_count }}</span></td>
                        <td>
                            <form action="{{ route('groups.leave', $group) }}" method="POST"
                                  onsubmit="return confirm('Leave \'{{ $group->name }}\'?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fa-solid fa-right-from-bracket"></i> Leave
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Available Groups --}}
<div class="card">
    <div class="card-header">
        <h2><i class="fa-solid fa-magnifying-glass"></i> Available Groups</h2>
        <span style="font-size:12px;color:#64748b;font-weight:600">{{ $available->count() }} available</span>
    </div>
    @if($available->isEmpty())
        <div style="text-align:center;padding:40px;color:#64748b;font-size:13px">
            <i class="fa-solid fa-circle-check" style="font-size:28px;color:#a7f3d0;display:block;margin-bottom:10px"></i>
            You've joined all available groups!
        </div>
    @else
        <div class="table-wrap">
            <table class="groups-table">
                <thead>
                    <tr><th>Group</th><th>Description</th><th>Members</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($available as $group)
                    <tr>
                        <td data-label="Group" style="font-weight:700">{{ $group->name }}</td>
                        <td data-label="Description" style="color:#64748b;font-size:13px">{{ $group->description ?? '—' }}</td>
                        <td data-label="Members"><span style="background:#ede9fe;color:#5b21b6;padding:3px 10px;border-radius:10px;font-size:12px;font-weight:700">{{ $group->members_count }}</span></td>
                        <td>
                            <form action="{{ route('groups.join', $group) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="fa-solid fa-plus"></i> Join
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection
