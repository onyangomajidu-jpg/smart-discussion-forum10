<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

/**
 * StatisticsService — Analytics & Reporting subsystem (SDD §4.3).
 *
 * generateStatistics() — collect activity data, compute stats, store results
 * generateReport()     — aggregate into structured report
 * exportReport()       — export to PDF/CSV/JSON
 */
class StatisticsService
{
    // ── generateStatistics() ────────────────────────────────────────────────

    public function generateStatistics(?int $userId = null): array
    {
        return $userId
            ? $this->userStatistics($userId)
            : $this->globalStatistics();
    }

    // ── generateReport() ────────────────────────────────────────────────────

    public function generateReport(string $type, ?int $userId = null): array
    {
        $report = [
            'generated_at' => now()->toDateTimeString(),
            'type'         => $type,
            'statistics'   => $this->generateStatistics($userId),
        ];

        if ($type === 'user' && $userId) {
            $u = User::findOrFail($userId);
            $report['user'] = ['id' => $u->id, 'name' => $u->name, 'email' => $u->email];
        }

        return $report;
    }

    // ── exportReport() ──────────────────────────────────────────────────────

    public function exportReport(array $report, string $format = 'pdf'): mixed
    {
        return match ($format) {
            'csv'  => $this->toCsv($report),
            'json' => json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'pdf'  => $this->toPdf($report),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }

    // ── User statistics ──────────────────────────────────────────────────────

    private function userStatistics(int $userId): array
    {
        $quiz = DB::table('participation_records')
            ->where('user_id', $userId)
            ->selectRaw('COUNT(*) as attempts, AVG(percentage) as avg_score,
                         MAX(percentage) as max_score, MIN(percentage) as min_score')
            ->first();

        $forum = DB::table('posts')
            ->where('user_id', $userId)
            ->selectRaw('COUNT(*) as total_posts, COUNT(DISTINCT topic_id) as topics_joined')
            ->first();

        $totalQuizzes     = Quiz::whereHas('group.members', fn($q) => $q->where('user_id', $userId))->count();
        $completedQuizzes = DB::table('participation_records')->where('user_id', $userId)->count();
        $completionRate   = $totalQuizzes > 0 ? round(($completedQuizzes / $totalQuizzes) * 100) : 0;

        $weekly = DB::table('participation_records')
            ->where('user_id', $userId)
            ->where('completed_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(completed_at) as date, AVG(percentage) as avg_score')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => ['date' => $r->date, 'avg_score' => round($r->avg_score, 2)])
            ->values();

        $subjects = DB::table('participation_records')
            ->join('quizzes', 'participation_records.quiz_id', '=', 'quizzes.id')
            ->join('groups',  'quizzes.group_id', '=', 'groups.id')
            ->where('participation_records.user_id', $userId)
            ->selectRaw('groups.name as subject, COUNT(*) as attempts, AVG(participation_records.percentage) as avg_score')
            ->groupBy('groups.id', 'groups.name')
            ->get()
            ->map(fn($r) => ['subject' => $r->subject, 'attempts' => $r->attempts, 'avg_score' => round($r->avg_score, 2)])
            ->values();

        return [
            'user_id' => $userId,
            'quiz' => [
                'total_attempts'  => $quiz->attempts ?? 0,
                'average_score'   => round($quiz->avg_score ?? 0, 2),
                'max_score'       => round($quiz->max_score ?? 0, 2),
                'min_score'       => round($quiz->min_score ?? 0, 2),
                'completion_rate' => $completionRate,
            ],
            'forum' => [
                'total_posts'   => $forum->total_posts ?? 0,
                'topics_joined' => $forum->topics_joined ?? 0,
            ],
            'weekly_performance' => $weekly,
            'subject_allocation' => $subjects,
        ];
    }

    // ── Global statistics ────────────────────────────────────────────────────

    private function globalStatistics(): array
    {
        $quiz = DB::table('participation_records')
            ->selectRaw('COUNT(*) as total_attempts, AVG(percentage) as avg_score, MAX(percentage) as max_score')
            ->first();

        $forum = DB::table('topics')
            ->selectRaw('COUNT(*) as total_topics, SUM(views) as total_views')
            ->first();

        $weekly = DB::table('participation_records')
            ->where('completed_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as attempts, AVG(percentage) as avg_score')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($r) => ['date' => $r->date, 'attempts' => $r->attempts, 'avg_score' => round($r->avg_score, 2)])
            ->values();

        $subjects = DB::table('participation_records')
            ->join('quizzes', 'participation_records.quiz_id', '=', 'quizzes.id')
            ->join('groups',  'quizzes.group_id', '=', 'groups.id')
            ->selectRaw('groups.name as subject, COUNT(*) as attempts, AVG(participation_records.percentage) as avg_score')
            ->groupBy('groups.id', 'groups.name')
            ->orderBy('attempts', 'desc')
            ->get()
            ->map(fn($r) => ['subject' => $r->subject, 'attempts' => $r->attempts, 'avg_score' => round($r->avg_score, 2)])
            ->values();

        return [
            'platform' => [
                'total_users' => User::count(),
                'members'     => User::where('role', 'member')->count(),
                'lecturers'   => User::where('role', 'lecturer')->count(),
                'admins'      => User::where('role', 'admin')->count(),
            ],
            'quiz' => [
                'total_attempts' => $quiz->total_attempts ?? 0,
                'average_score'  => round($quiz->avg_score ?? 0, 2),
                'max_score'      => round($quiz->max_score ?? 0, 2),
            ],
            'forum' => [
                'total_topics' => $forum->total_topics ?? 0,
                'total_views'  => $forum->total_views ?? 0,
            ],
            'weekly_trend'       => $weekly,
            'subject_allocation' => $subjects,
        ];
    }

    // ── CSV export ───────────────────────────────────────────────────────────

    private function toCsv(array $report): string
    {
        $rows = [];
        $rows[] = ['Report Type', 'Generated At'];
        $rows[] = [$report['type'], $report['generated_at']];
        $rows[] = [];

        $s = $report['statistics'];

        if (isset($s['quiz'])) {
            $rows[] = ['QUIZ STATISTICS'];
            $rows[] = ['Total Attempts',  $s['quiz']['total_attempts']];
            $rows[] = ['Average Score',   $s['quiz']['average_score'] . '%'];
            $rows[] = ['Completion Rate', ($s['quiz']['completion_rate'] ?? 'N/A') . '%'];
            $rows[] = [];
        }

        if (isset($s['forum'])) {
            $rows[] = ['FORUM STATISTICS'];
            $rows[] = ['Total Posts',   $s['forum']['total_posts']];
            $rows[] = ['Topics Joined', $s['forum']['topics_joined']];
            $rows[] = [];
        }

        $rows[] = ['WEEKLY PERFORMANCE'];
        $rows[] = ['Date', 'Avg Score'];
        foreach ($s['weekly_performance'] ?? $s['weekly_trend'] ?? [] as $w) {
            $rows[] = [$w['date'], $w['avg_score'] . '%'];
        }
        $rows[] = [];

        $rows[] = ['SUBJECT ALLOCATION'];
        $rows[] = ['Subject', 'Attempts', 'Avg Score'];
        foreach ($s['subject_allocation'] ?? [] as $sa) {
            $rows[] = [$sa['subject'], $sa['attempts'], $sa['avg_score'] . '%'];
        }

        $buf = fopen('php://memory', 'r+');
        foreach ($rows as $row) {
            fputcsv($buf, $row);
        }
        rewind($buf);
        return stream_get_contents($buf);
    }

    // ── PDF export via dompdf ────────────────────────────────────────────────

    private function toPdf(array $report): string
    {
        $s    = $report['statistics'];
        $type = ucfirst($report['type']);
        $date = $report['generated_at'];
        $userName = $report['user']['name'] ?? 'Platform';

        $quizAttempts   = $s['quiz']['total_attempts'] ?? 0;
        $avgScore       = $s['quiz']['average_score'] ?? 0;
        $completionRate = $s['quiz']['completion_rate'] ?? 'N/A';
        $maxScore       = $s['quiz']['max_score'] ?? 0;
        $minScore       = $s['quiz']['min_score'] ?? 0;
        $totalPosts     = $s['forum']['total_posts'] ?? 0;
        $topicsJoined   = $s['forum']['topics_joined'] ?? 0;

        $weeklyRows = '';
        foreach ($s['weekly_performance'] ?? $s['weekly_trend'] ?? [] as $w) {
            $bar   = min((int) $w['avg_score'], 100);
            $color = $bar >= 75 ? '#10b981' : ($bar >= 50 ? '#f59e0b' : '#ef4444');
            $weeklyRows .= "
            <tr>
              <td>{$w['date']}</td>
              <td>
                <table width='100%' cellpadding='0' cellspacing='0'>
                  <tr>
                    <td width='{$bar}%' style='background:{$color};height:8px;border-radius:4px'></td>
                    <td width='" . (100 - $bar) . "%' style='background:#e2e8f0;height:8px'></td>
                    <td width='50px' style='text-align:right;font-weight:700;color:{$color};font-size:11px'>{$w['avg_score']}%</td>
                  </tr>
                </table>
              </td>
            </tr>";
        }

        $colors      = ['#6366f1', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#3b82f6'];
        $subjectRows = '';
        foreach ($s['subject_allocation'] ?? [] as $i => $sa) {
            $c = $colors[$i % count($colors)];
            $subjectRows .= "
            <tr>
              <td><span style='display:inline-block;width:10px;height:10px;border-radius:50%;background:{$c};margin-right:6px'></span>{$sa['subject']}</td>
              <td style='text-align:center'>{$sa['attempts']}</td>
              <td style='text-align:center;font-weight:700;color:{$c}'>{$sa['avg_score']}%</td>
            </tr>";
        }

        $html = "<!DOCTYPE html><html><head><meta charset='utf-8'>
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  body{font-family:DejaVu Sans,Arial,sans-serif;font-size:11px;color:#1e293b;background:#fff}
  .header{background:#4f46e5;color:#fff;padding:28px 32px;margin-bottom:24px}
  .header h1{font-size:20px;font-weight:700;margin-bottom:4px}
  .header p{font-size:11px;opacity:.8;margin-top:4px}
  .section{padding:0 32px;margin-bottom:22px}
  .section-title{font-size:13px;font-weight:700;color:#4f46e5;border-bottom:2px solid #4f46e5;padding-bottom:6px;margin-bottom:14px}
  .kpi-table{width:100%;border-collapse:separate;border-spacing:8px}
  .kpi{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;text-align:center;border-top:3px solid #4f46e5}
  .kpi .v{font-size:22px;font-weight:800;color:#4f46e5}
  .kpi .l{font-size:9px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-top:3px}
  table.data{width:100%;border-collapse:collapse;font-size:11px}
  table.data th{background:#f1f5f9;padding:9px 12px;text-align:left;font-weight:700;color:#475569;font-size:10px;text-transform:uppercase;border-bottom:2px solid #e2e8f0}
  table.data td{padding:9px 12px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
  .footer{margin:24px 32px 0;padding-top:12px;border-top:1px solid #e2e8f0;font-size:9px;color:#94a3b8;text-align:center}
</style>
</head><body>
<div class='header'>
  <h1>Analytics Report</h1>
  <p>Smart Discussion Forum &mdash; Assessment Platform</p>
  <p style='margin-top:10px;font-size:10px;opacity:.75'>
    Report Type: <strong>{$type}</strong> &nbsp;|&nbsp;
    User: <strong>{$userName}</strong> &nbsp;|&nbsp;
    Generated: <strong>{$date}</strong>
  </p>
</div>

<div class='section'>
  <div class='section-title'>Overview Statistics</div>
  <table class='kpi-table'>
    <tr>
      <td class='kpi'><div class='v'>{$quizAttempts}</div><div class='l'>Quizzes Taken</div></td>
      <td class='kpi'><div class='v'>{$avgScore}%</div><div class='l'>Avg Score</div></td>
      <td class='kpi'><div class='v'>{$completionRate}%</div><div class='l'>Completion Rate</div></td>
    </tr>
    <tr>
      <td class='kpi'><div class='v'>{$maxScore}%</div><div class='l'>Best Score</div></td>
      <td class='kpi'><div class='v'>{$totalPosts}</div><div class='l'>Forum Posts</div></td>
      <td class='kpi'><div class='v'>{$topicsJoined}</div><div class='l'>Topics Joined</div></td>
    </tr>
  </table>
</div>

<div class='section'>
  <div class='section-title'>Weekly Performance Trend</div>
  <table class='data'>
    <thead><tr><th>Date</th><th>Average Score</th></tr></thead>
    <tbody>{$weeklyRows}</tbody>
  </table>
</div>

<div class='section'>
  <div class='section-title'>Quiz Subject Allocation</div>
  <table class='data'>
    <thead><tr><th>Subject</th><th style='text-align:center'>Attempts</th><th style='text-align:center'>Avg Score</th></tr></thead>
    <tbody>{$subjectRows}</tbody>
  </table>
</div>

<div class='footer'>Smart Discussion Forum &mdash; Analytics System &mdash; Confidential</div>
</body></html>";

        return Pdf::loadHTML($html)->setPaper('a4', 'portrait')->output();
    }
}
