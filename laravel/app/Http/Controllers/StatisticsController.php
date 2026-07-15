<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\ParticipationRecord;
use App\Models\User;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

/**
 * StatisticsController — Analytics & Reporting (SDD §4.3).
 *
 * GET /analytics                → index()             (Fig 6.5 — Statistics screen)
 * GET /lecturer/analytics       → lecturerAnalytics() (Fig 6.4 right panel)
 * GET /reports/export           → export()            (PDF / CSV download)
 */
class StatisticsController extends Controller
{
    public function __construct(private readonly StatisticsService $statistics) {}

    // ── Statistics Screen (Fig 6.5) ──────────────────────────────────────────

    public function index(Request $request)
    {
        $user  = $request->user();
        $stats = $this->statistics->generateStatistics($user->id);

        return view('analytics.index', compact('stats', 'user'));
    }

    // ── Lecturer Analytics — live evaluation roster + compliance (Fig 6.4) ──

    public function lecturerAnalytics(Request $request)
    {
        $lecturer = $request->user();

        $quizzes = Quiz::where('created_by', $lecturer->id)
            ->with('group')
            ->withCount(['questions', 'attempts', 'participationRecords'])
            ->orderByDesc('created_at')
            ->get();

        $roster = ParticipationRecord::whereIn('quiz_id', $quizzes->pluck('id'))
            ->with('user', 'quiz')
            ->orderByDesc('completed_at')
            ->get();

        $compliance = $quizzes->map(function ($quiz) {
            $groupSize = $quiz->group?->members()->count() ?? 0;
            $submitted = $quiz->participation_records_count;
            $rate      = $groupSize > 0 ? round(($submitted / $groupSize) * 100) : 0;
            return [
                'quiz'       => $quiz,
                'group_size' => $groupSize,
                'submitted'  => $submitted,
                'pending'    => max($groupSize - $submitted, 0),
                'rate'       => $rate,
            ];
        });

        $totalStudents = User::where('role', 'member')
            ->whereHas('groups', fn($q) => $q->where('created_by', $lecturer->id))
            ->count();

        $avgScore = ParticipationRecord::whereIn('quiz_id', $quizzes->pluck('id'))
            ->avg('percentage') ?? 0;

        $totalSubmissions = ParticipationRecord::whereIn('quiz_id', $quizzes->pluck('id'))->count();

        return view('lecturer.analytics', compact(
            'lecturer', 'quizzes', 'roster', 'compliance',
            'totalStudents', 'avgScore', 'totalSubmissions'
        ));
    }

    // ── Export Report ────────────────────────────────────────────────────────

    public function export(Request $request)
    {
        $format = $request->query('format', 'pdf');
        $type   = $request->query('type', 'user');
        $userId = $type === 'user' ? $request->user()->id : null;

        $report   = $this->statistics->generateReport($type, $userId);
        $exported = $this->statistics->exportReport($report, $format);

        $filename = 'analytics_report_' . now()->format('Y-m-d');

        return match ($format) {
            'csv' => response($exported, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\""),

            'json' => response($exported, 200)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}.json\""),

            'pdf' => response($exported, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}.pdf\""),

            default => abort(400, 'Unsupported format'),
        };
    }
}
