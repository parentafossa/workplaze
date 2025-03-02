<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttLogAnalysisWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    protected ?string $heading = 'Attendance I/F';
    // Set widget to take up less space
    protected int|string|array $columnSpan = '2';

    protected static ?string $cluster = DataOn::class;
    
    protected function getGridData(): array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 4,
            'lg' => 4,
            'xl' => 4,
            '2xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        $now = Carbon::now();
        $lastHour = $now->copy()->subHour();
        $last24Hours = $now->copy()->subHours(24);

        // Get all records from last 24 hours
        $records = DB::table('dataon_ifatt_log')
            ->where('process_name','in', ['DB Att Update','WP Abs Update'])
            ->where('created_at', '>=', $last24Hours)
            ->orderBy('created_at', 'desc')
            ->get();

        // Process status checks with timestamp
        $hasConsecutiveStarts = false;
        $previousWasStart = false;
        $issueTimestamp = null;
        $previousStartTime = null;

        foreach ($records as $record) {
            if (str_contains($record->remark, 'Process Start')) {
                if ($previousWasStart) {
                    $hasConsecutiveStarts = true;
                    $issueTimestamp = $previousStartTime;
                    break;
                }
                $previousWasStart = true;
                $previousStartTime = $record->created_at;
            } else {
                $previousWasStart = false;
            }
        }

        // Error tracking
        $errorCount = $records->filter(function ($record) {
            return str_contains($record->remark, 'Error');
        })->count();

        $recentErrors = $records
            ->where('created_at', '>=', $lastHour)
            ->filter(function ($record) {
                return str_contains($record->remark, 'Error');
            })->count();

        // Entity-specific record counting
        $entityCounts = [
            'BML' => ['total' => 0, 'lastHour' => 0],
            'VID' => ['total' => 0, 'lastHour' => 0],
            'LID' => ['total' => 0, 'lastHour' => 0]
        ];

        foreach ($records as $record) {
            if (str_contains($record->remark, 'Retrieved from ATT_LOG:')) {
                foreach (['BML', 'VID', 'LID'] as $entity) {
                    if (str_contains($record->remark, $entity)) {
                        preg_match('/(\d+) records ' . $entity . '/', $record->remark, $matches);
                        if (isset($matches[1])) {
                            $count = (int) $matches[1];
                            $entityCounts[$entity]['total'] += $count;

                            if ($record->created_at >= $lastHour) {
                                $entityCounts[$entity]['lastHour'] += $count;
                            }
                        }
                    }
                }
            }
        }

        // Format process status description
        $processStatusDesc = $hasConsecutiveStarts
            ? 'Issue detected at ' . Carbon::parse($issueTimestamp)->format('H:i:s')
            : 'All processes completing normally';

        $groupCount = $entityCounts['BML']['total'] + $entityCounts['VID']['total'] + $entityCounts['LID']['total'];
        $groupCountLastHour = $entityCounts['BML']['lastHour'] + $entityCounts['VID']['lastHour'] + $entityCounts['LID']['lastHour'];

        return [
            // Process Status
            Stat::make('Process Status', $hasConsecutiveStarts ? 'Issue Detected' : 'Normal')
                ->description($processStatusDesc)
                ->descriptionIcon($hasConsecutiveStarts ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                ->color($hasConsecutiveStarts ? 'danger' : 'success')
                ->chart($hasConsecutiveStarts ? [0, 1, 0] : [1, 1, 1])
                ,

            // Error Status
            Stat::make('Errors Found', $errorCount)
                ->description($recentErrors > 0 ? "{$recentErrors} errors in last hour" : 'No recent errors')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color($errorCount > 0 ? 'danger' : 'success')
                ,

            // Import Records
            Stat::make('Total Records', number_format($groupCount))
                ->description("Total BML/VID/LID: " . number_format($entityCounts['BML']['total']) . '/' . number_format($entityCounts['VID']['total']) . '/' . number_format($entityCounts['LID']['total']))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ,

            Stat::make('Last Hour Records', number_format($groupCountLastHour))
                ->description("Last Hour BML/VID/LID: " . number_format($entityCounts['BML']['lastHour']) . '/' . number_format($entityCounts['VID']['lastHour']) . '/' . number_format($entityCounts['LID']['lastHour']))
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info')
                ,

        ];
    }
}