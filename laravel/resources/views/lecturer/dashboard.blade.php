@extends('layouts.app')

@section('title', 'Lecturer Dashboard — SmartForum')

@section('content')

<div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;padding:32px 36px;margin-bottom:28px;color:#fff;position:relative;overflow:hidden;box-shadow:0 8px 28px rgba(99,102,241,.3)">
    <div style="position:absolute;top:-60px;right:-60px;width:200px;height:200px;background:rgba(255,255,255,.07);border-radius:50%"></div>
    <div style="font-size:13px;opacity:.7;margin-bottom:6px;text-transform:uppercase;letter-spacing:.8px;font-weight:700">
        <i class="fa-solid fa-chalkboard-user"></i> Lecturer Portal
    </div>
    <div style="font-size:26px;font-weight:900;margin-bottom:6px">Welcome back, {{ auth()->user()->name }} 👋</div>
    <div style="font-size:14px;opacity:.8">Manage your quizzes, track student progress, and view results.</div>
</div>

<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:28px">
    <a href="{{ route('lecturer.quizzes.index') }}" style="text-decoration:none">
        <div class="card" style="padding:24px;text-align:center;transition:all .2s;cursor:pointer" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(99,102,241,.15)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="font-size:36px;margin-bottom:10px">📋</div>
            <div style="font-size:16px;font-weight:800;color:#0f172a;margin-bottom:4px">My Quizzes</div>
            <div style="font-size:12px;color:#64748b">View and manage all your quizzes</div>
        </div>
    </a>
    <a href="{{ route('lecturer.quizzes.create') }}" style="text-decoration:none">
        <div class="card" style="padding:24px;text-align:center;transition:all .2s;cursor:pointer" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(99,102,241,.15)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="font-size:36px;margin-bottom:10px">➕</div>
            <div style="font-size:16px;font-weight:800;color:#0f172a;margin-bottom:4px">Create Quiz</div>
            <div style="font-size:12px;color:#64748b">Build a new assessment</div>
        </div>
    </a>
    <a href="{{ route('lecturer.analytics') }}" style="text-decoration:none">
        <div class="card" style="padding:24px;text-align:center;transition:all .2s;cursor:pointer" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 32px rgba(99,102,241,.15)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div style="font-size:36px;margin-bottom:10px">📊</div>
            <div style="font-size:16px;font-weight:800;color:#0f172a;margin-bottom:4px">Analytics</div>
            <div style="font-size:12px;color:#64748b">Evaluation roster &amp; compliance</div>
        </div>
    </a>
</div>

@endsection
