<?php

namespace App\Services;

use App\Models\ApprovalInstance;
use App\Models\ApprovalMetric;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ApprovalAnalyticsService
{
        public function getApprovalMetrics(Carbon $startDate, Carbon $endDate, ?string $flowId = null): array
    {
        $query = ApprovalInstance::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($flowId) {
            $query->where('approval_flow_id', $flowId);
        }

        $metrics = $query->select(
            'status',
            DB::raw('COUNT(*) as count'),
            DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_duration')
        )
        ->groupBy('status')
        ->get();

        $stepMetrics = ApprovalMetric::query()
            ->whereHas('instance', function ($query) use ($startDate, $endDate, $flowId) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
                if ($flowId) {
                    $query->where('approval_flow_id', $flowId);
                }
            })
            ->select(
                'step_number',
                DB::raw('AVG(duration_minutes) as avg_duration'),
                DB::raw('COUNT(*) as count'),
                DB::raw('MIN(duration_minutes) as min_duration'),
                DB::raw('MAX(duration_minutes) as max_duration')
            )
            ->groupBy('step_number')
            ->get();

        return [
            'status_metrics' => $metrics,
            'step_metrics' => $stepMetrics,
            'total_count' => $metrics->sum('count'),
            'overall_avg_duration' => $metrics->avg('avg_duration'),
            'bottlenecks' => $this->identifyBottlenecks($stepMetrics),
        ];
    }

    public function getApproverPerformance(
        Carbon $startDate, 
        Carbon $endDate, 
        ?string $flowId = null
    ): Collection {
        return DB::table('approval_actions as aa')
            ->join('approval_instances as ai', 'aa.approval_instance_id', '=', 'ai.id')
            ->join('employees as e', 'aa.user_id', '=', 'e.emp_id')
            ->when($flowId, fn($q) => $q->where('ai.approval_flow_id', $flowId))
            ->whereBetween('aa.created_at', [$startDate, $endDate])
            ->select(
                'e.emp_id',
                'e.emp_name',
                DB::raw('COUNT(*) as total_actions'),
                DB::raw('COUNT(CASE WHEN aa.action = "approve" THEN 1 END) as approvals'),
                DB::raw('COUNT(CASE WHEN aa.action = "reject" THEN 1 END) as rejections'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, ai.created_at, aa.created_at)) as avg_response_time')
            )
            ->groupBy('e.emp_id', 'e.emp_name')
            ->having('total_actions', '>', 0)
            ->orderByDesc('total_actions')
            ->get();
    }

    protected function identifyBottlenecks(Collection $stepMetrics): Collection
    {
        $avgDuration = $stepMetrics->avg('avg_duration');
        $stdDev = $this->calculateStdDev($stepMetrics->pluck('avg_duration'));

        return $stepMetrics
            ->map(function ($metric) use ($avgDuration, $stdDev) {
                $metric->is_bottleneck = $metric->avg_duration > ($avgDuration + $stdDev);
                $metric->severity = $this->calculateBottleneckSeverity(
                    $metric->avg_duration,
                    $avgDuration,
                    $stdDev
                );
                return $metric;
            })
            ->filter(fn ($metric) => $metric->is_bottleneck)
            ->sortByDesc('severity');
    }

    protected function calculateStdDev(Collection $values): float
    {
        $mean = $values->avg();
        $squaredDiffs = $values->map(fn ($value) => pow($value - $mean, 2));
        return sqrt($squaredDiffs->avg());
    }

    protected function calculateBottleneckSeverity(
        float $stepDuration,
        float $avgDuration,
        float $stdDev
    ): string {
        $deviations = ($stepDuration - $avgDuration) / $stdDev;
        return match(true) {
            $deviations >= 2 => 'high',
            $deviations >= 1.5 => 'medium',
            default => 'low'
        };
    }

    public function generateReport(Carbon $startDate, Carbon $endDate, ?string $flowId = null): array
    {
        $metrics = $this->getApprovalMetrics($startDate, $endDate, $flowId);
        $approverPerformance = $this->getApproverPerformance($startDate, $endDate, $flowId);

        return [
            'metrics' => $metrics,
            'approver_performance' => $approverPerformance,
            'summary' => $this->generateSummary($metrics, $approverPerformance),
            'recommendations' => $this->generateRecommendations($metrics),
        ];
    }

    protected function generateSummary(array $metrics, Collection $approverPerformance): array
    {
        $totalRequests = $metrics['total_count'];
        $completionRate = $metrics['status_metrics']
            ->where('status', 'completed')
            ->first()?->count / $totalRequests * 100 ?? 0;

        return [
            'total_requests' => $totalRequests,
            'completion_rate' => round($completionRate, 2),
            'average_duration_hours' => round($metrics['overall_avg_duration'] / 60, 1),
            'bottleneck_count' => $metrics['bottlenecks']->count(),
            'top_approvers' => $approverPerformance
                ->sortByDesc('total_actions')
                ->take(5)
                ->map(fn ($approver) => [
                    'name' => $approver->emp_name,
                    'total_actions' => $approver->total_actions,
                    'avg_response_hours' => round($approver->avg_response_time / 60, 1),
                ]),
        ];
    }

    protected function generateRecommendations(array $metrics): array
{
    $recommendations = [];

    // Check completion rate
    $completionRate = $metrics['status_metrics']
        ->where('status', 'completed')
        ->first()?->count / $metrics['total_count'] * 100 ?? 0;

    if ($completionRate < 70) {
        $recommendations[] = [
            'type' => 'completion_rate',
            'severity' => $completionRate < 50 ? 'high' : 'medium',
            'message' => 'Low completion rate detected. Consider reviewing approval flow steps and requirements.',
            'metric' => round($completionRate, 2) . '%'
        ];
    }

    // Check for bottlenecks
    foreach ($metrics['step_metrics'] as $step) {
        if (isset($step->is_bottleneck) && $step->is_bottleneck) {
            $recommendations[] = [
                'type' => 'bottleneck',
                'severity' => $step->severity,
                'message' => "Step {$step->step_number} is taking longer than expected to complete.",
                'metric' => round($step->avg_duration / 60, 1) . ' hours'
            ];
        }
    }

    // Check average duration
    $avgDuration = $metrics['overall_avg_duration'] / 60; // Convert to hours
    if ($avgDuration > 48) { // If average duration is more than 48 hours
        $recommendations[] = [
            'type' => 'duration',
            'severity' => $avgDuration > 72 ? 'high' : 'medium',
            'message' => 'Average approval duration is higher than expected.',
            'metric' => round($avgDuration, 1) . ' hours'
        ];
    }

    // Check rejection rate
    $rejectionRate = $metrics['status_metrics']
        ->where('status', 'rejected')
        ->first()?->count / $metrics['total_count'] * 100 ?? 0;

    if ($rejectionRate > 20) {
        $recommendations[] = [
            'type' => 'rejection_rate',
            'severity' => $rejectionRate > 30 ? 'high' : 'medium',
            'message' => 'High rejection rate detected. Consider providing better submission guidelines.',
            'metric' => round($rejectionRate, 2) . '%'
        ];
    }

    return $recommendations;
}

    public function getStepDurations(ApprovalInstance $instance): Collection
    {
        return $instance->actions()
            ->select(
                'step_number',
                DB::raw('MIN(created_at) as step_start'),
                DB::raw('MAX(created_at) as step_end'),
                DB::raw('COUNT(*) as action_count')
            )
            ->groupBy('step_number')
            ->orderBy('step_number')
            ->get()
            ->map(function ($step) {
                $step->duration = $step->step_start->diffInMinutes($step->step_end);
                return $step;
            });
    }

    public function getUserStats(?string $empId = null): array
    {
        $query = DB::table('approval_actions as aa')
            ->join('approval_instances as ai', 'aa.approval_instance_id', '=', 'ai.id')
            ->join('employees as e', 'aa.user_id', '=', 'e.emp_id');

        if ($empId) {
            $query->where('aa.user_id', $empId);
        }

        $stats = $query->select(
            'e.emp_id',
            'e.emp_name',
            DB::raw('COUNT(DISTINCT ai.id) as total_requests'),
            DB::raw('COUNT(*) as total_actions'),
            DB::raw('AVG(TIMESTAMPDIFF(MINUTE, ai.created_at, aa.created_at)) as avg_response_time'),
            DB::raw('COUNT(CASE WHEN aa.action = "approve" THEN 1 END) as approvals'),
            DB::raw('COUNT(CASE WHEN aa.action = "reject" THEN 1 END) as rejections'),
            DB::raw('COUNT(CASE WHEN aa.action = "submit" THEN 1 END) as submissions')
        )
        ->groupBy('e.emp_id', 'e.emp_name')
        ->get();

        return [
            'users' => $stats,
            'summary' => [
                'total_users' => $stats->count(),
                'avg_actions_per_user' => round($stats->avg('total_actions'), 1),
                'avg_response_time_hours' => round($stats->avg('avg_response_time') / 60, 1),
                'most_active_users' => $stats->sortByDesc('total_actions')->take(5),
                'fastest_responders' => $stats
                    ->where('total_actions', '>', 5)
                    ->sortBy('avg_response_time')
                    ->take(5),
            ]
        ];
    }

    public function getProcessEfficiency(Carbon $startDate, Carbon $endDate): array
    {
        $instances = ApprovalInstance::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'cancelled', 'rejected'])
            ->with(['actions', 'approvalFlow'])
            ->get();

        $efficiency = [];
        foreach ($instances as $instance) {
            $steps = $instance->approvalFlow->steps;
            $totalPossibleApprovers = collect($steps)->sum(fn($step) => count($step['approvers']));
            $actualApprovers = $instance->actions->unique('user_id')->count();
            
            $efficiency[] = [
                'instance_id' => $instance->id,
                'status' => $instance->status,
                'total_steps' => count($steps),
                'actual_approvers' => $actualApprovers,
                'possible_approvers' => $totalPossibleApprovers,
                'duration_hours' => $instance->created_at->diffInHours($instance->updated_at),
                'efficiency_score' => $this->calculateEfficiencyScore(
                    $actualApprovers,
                    $totalPossibleApprovers,
                    $instance->created_at->diffInHours($instance->updated_at),
                    count($steps)
                )
            ];
        }

        return [
            'instances' => $efficiency,
            'average_score' => collect($efficiency)->avg('efficiency_score'),
            'distribution' => $this->calculateEfficiencyDistribution($efficiency),
            'trends' => $this->calculateEfficiencyTrends($efficiency),
        ];
    }

    protected function calculateEfficiencyScore(
        int $actualApprovers,
        int $possibleApprovers,
        int $durationHours,
        int $totalSteps
    ): float {
        $approverEfficiency = $actualApprovers / $possibleApprovers;
        $timeEfficiency = max(0, 1 - ($durationHours / (24 * $totalSteps)));
        
        return round(($approverEfficiency * 0.6 + $timeEfficiency * 0.4) * 100, 2);
    }

    protected function calculateEfficiencyDistribution(array $efficiency): array
    {
        $scores = collect($efficiency)->pluck('efficiency_score');
        
        return [
            'ranges' => [
                'excellent' => $scores->filter(fn($score) => $score >= 80)->count(),
                'good' => $scores->filter(fn($score) => $score >= 60 && $score < 80)->count(),
                'fair' => $scores->filter(fn($score) => $score >= 40 && $score < 60)->count(),
                'poor' => $scores->filter(fn($score) => $score < 40)->count(),
            ],
            'statistics' => [
                'mean' => $scores->avg(),
                'median' => $scores->median(),
                'min' => $scores->min(),
                'max' => $scores->max(),
                'std_dev' => $this->calculateStdDev($scores),
            ]
        ];
    }

    protected function calculateEfficiencyTrends(array $efficiency): array
    {
        return collect($efficiency)
            ->groupBy(fn($item) => Carbon::parse($item['created_at'])->format('Y-m-d'))
            ->map(fn($group) => [
                'date' => $group->first()['created_at'],
                'average_score' => $group->avg('efficiency_score'),
                'count' => $group->count(),
            ])
            ->values()
            ->all();
    }
}