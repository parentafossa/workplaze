<?php

namespace App\Filament\Pages;

use App\Services\ApprovalAnalyticsService;
use App\Services\ApprovalReportGenerator;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use App\Models\ApprovalFlow;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\View\View;

//use App\Services\ApprovalAnalyticsService;

class ApprovalAnalytics extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $title = 'Approval Analytics';
    protected static ?string $slug = 'approval-analytics';
    protected static string $view = 'filament.pages.approval-analytics';

    public ?string $selectedFlow = null;
    public ?string $dateRange = '30';
    public ?string $startDate = null;
    public ?string $endDate = null;

    protected $analyticsService;

    public function mount(ApprovalAnalyticsService $analyticsService): void
    {
        $this->analyticsService = $analyticsService;
        $this->startDate = now()->subDays(30)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selectedFlow')
                ->label('Approval Flow')
                ->options(ApprovalFlow::pluck('name', 'id'))
                ->placeholder('All Flows')
                ->live(),

            Select::make('dateRange')
                ->label('Date Range')
                ->options([
                    '7' => 'Last 7 Days',
                    '30' => 'Last 30 Days',
                    '90' => 'Last 90 Days',
                    'custom' => 'Custom Range',
                ])
                ->default('30')
                ->live()
                ->afterStateUpdated(function ($state) {
                    if ($state !== 'custom') {
                        $this->startDate = now()->subDays((int)$state)->format('Y-m-d');
                        $this->endDate = now()->format('Y-m-d');
                    }
                }),

            DatePicker::make('startDate')
                ->label('Start Date')
                ->visible(fn ($get) => $get('dateRange') === 'custom')
                ->required()
                ->live(),

            DatePicker::make('endDate')
                ->label('End Date')
                ->visible(fn ($get) => $get('dateRange') === 'custom')
                ->required()
                ->live(),
        ];
    }

    public function getMetrics(): array
    {
        $metrics = $this->analyticsService->getApprovalMetrics(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
            $this->selectedFlow
        );

        // Ensure required structure exists
        return array_merge([
            'status_metrics' => collect(),
            'step_metrics' => collect(),
            'total_count' => 0,
            'overall_avg_duration' => 0,
            'recommendations' => [], // Add this line
        ], $metrics);
    }

    public function getApproverPerformance(): array
    {
        return $this->analyticsService->getApproverPerformance(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate),
            $this->selectedFlow
        )->toArray();
    }

    public function getEfficiencyStats(): array
    {
        return $this->analyticsService->getProcessEfficiency(
            Carbon::parse($this->startDate),
            Carbon::parse($this->endDate)
        );
    }

    protected function getViewData(): array
    {
        return [
            'metrics' => $this->getMetrics(),
            'approverPerformance' => $this->getApproverPerformance(),
            'efficiency' => $this->getEfficiencyStats(),
        ];
    }

    protected function getActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-m-arrow-down-tray')
                ->action(function (ApprovalReportGenerator $reportGenerator) {
                    try {
                        $startDate = Carbon::parse($this->startDate);
                        $endDate = Carbon::parse($this->endDate);
                        
                        $pdf = $reportGenerator->generateReport(
                            $startDate,
                            $endDate,
                            $this->selectedFlow
                        );

                        $filename = sprintf(
                            'approval-analytics-%s-to-%s.pdf',
                            $startDate->format('Y-m-d'),
                            $endDate->format('Y-m-d')
                        );

                        return response($pdf)
                            ->header('Content-Type', 'application/pdf')
                            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error generating report')
                            ->body('An error occurred while generating the report. Please try again.')
                            ->danger()
                            ->send();

                        return null;
                    }
                })
                ->color('success'),
        ];
    }
}