<?php

namespace App\Filament\Widgets;

use App\Models\ApprovalInstance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ApprovalDashboard extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();

        // Get counts for approvals assigned to current user
        $pendingApprovals = ApprovalInstance::whereIn('status', ['pending', 'pending_cancellation'])
            ->whereHas('approvalFlow', function ($query) use ($user) {
                $query->whereJsonContains('steps->0->approvers', [
                    'type' => 'user',
                    'id' => $user->emp_id
                ]);
            })
            ->count();

        // Get approval metrics
        $metrics = DB::table('approval_instances')
            ->select(
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected'),
                DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_duration')
            )
            ->whereMonth('created_at', now()->month)
            ->first();

        // Get user's approval performance
        $userPerformance = DB::table('approval_actions')
            ->where('user_id', $user->emp_id)
            ->whereMonth('created_at', now()->month)
            ->select(
                DB::raw('COUNT(*) as total_actions'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_response_time')
            )
            ->first();

        return [
            Stat::make('Pending Approvals', $pendingApprovals)
                ->description('Awaiting your approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('This Month', $metrics->total)
                ->description(sprintf(
                    '%d completed, %d rejected, %d cancelled',
                    $metrics->completed ?? 0,
                    $metrics->rejected ?? 0,
                    $metrics->cancelled ?? 0
                ))
                ->chart([
                    $metrics->completed ?? 0,
                    $metrics->rejected ?? 0,
                    $metrics->cancelled ?? 0,
                ])
                ->color('success'),

            Stat::make('Average Response Time', 
                number_format($userPerformance->avg_response_time ?? 0, 1) . ' hours')
                ->description(sprintf(
                    'From %d actions this month',
                    $userPerformance->total_actions ?? 0
                ))
                ->color('primary'),
        ];
    }
}