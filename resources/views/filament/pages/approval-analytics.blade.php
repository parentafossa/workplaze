<x-filament::page>
    <div class="space-y-6">
        {{ $this->form }}

        {{-- Overall Statistics Cards --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Completion Rate</h3>
                    <span @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-medium',
                        'bg-success-100 text-success-800' => $metrics['status_metrics']->where('status', 'completed')->first()?->count / $metrics['total_count'] * 100 >= 70,
                        'bg-warning-100 text-warning-800' => $metrics['status_metrics']->where('status', 'completed')->first()?->count / $metrics['total_count'] * 100 < 70,
                        'bg-danger-100 text-danger-800' => $metrics['status_metrics']->where('status', 'completed')->first()?->count / $metrics['total_count'] * 100 < 50,
                    ])>
                        {{ number_format($metrics['status_metrics']->where('status', 'completed')->first()?->count / $metrics['total_count'] * 100, 1) }}%
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    {{ $metrics['total_count'] }} total requests
                </p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Average Duration</h3>
                    <span class="text-sm font-medium text-gray-900">
                        {{ number_format($metrics['overall_avg_duration'] / 60, 1) }} hours
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    Per approval process
                </p>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Process Efficiency</h3>
                    <span @class([
                        'rounded-full px-2.5 py-0.5 text-xs font-medium',
                        'bg-success-100 text-success-800' => $efficiency['average_score'] >= 80,
                        'bg-warning-100 text-warning-800' => $efficiency['average_score'] >= 60 && $efficiency['average_score'] < 80,
                        'bg-danger-100 text-danger-800' => $efficiency['average_score'] < 60,
                    ])>
                        {{ number_format($efficiency['average_score'], 1) }}%
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    Overall efficiency score
                </p>
            </div>
        </div>

        {{-- Status Distribution Chart --}}
        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <h3 class="text-base font-semibold text-gray-900">Status Distribution</h3>
            <div class="mt-4 h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </div>

        {{-- Step Performance Table --}}
        @if($metrics['step_metrics']->isNotEmpty())
            <div class="rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-900">Step Performance</h3>
                </div>
                <div class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Step
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Average Duration
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Efficiency
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($metrics['step_metrics'] as $step)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            Step {{ $step->step_number }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ number_format($step->avg_duration / 60, 1) }} hours
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Range: {{ number_format($step->min_duration / 60, 1) }} - {{ number_format($step->max_duration / 60, 1) }} hours
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $step->count }} actions
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if(isset($step->is_bottleneck) && $step->is_bottleneck)
                                            <span @class([
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                'bg-danger-100 text-danger-800' => $step->severity === 'high',
                                                'bg-warning-100 text-warning-800' => $step->severity === 'medium',
                                                'bg-gray-100 text-gray-800' => $step->severity === 'low',
                                            ])>
                                                Bottleneck ({{ ucfirst($step->severity) }})
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-success-100 px-2.5 py-0.5 text-xs font-medium text-success-800">
                                                Optimal
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    {{-- Recommendations Section --}}
    @if(isset($metrics['recommendations']) && !empty($metrics['recommendations']))
        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <h3 class="text-base font-semibold text-gray-900">Recommendations</h3>
            <div class="mt-4 space-y-4">
                @foreach($metrics['recommendations'] as $recommendation)
                    <div @class([
                        'rounded-lg p-4',
                        'bg-danger-50' => $recommendation['severity'] === 'high',
                        'bg-warning-50' => $recommendation['severity'] === 'medium',
                        'bg-gray-50' => $recommendation['severity'] === 'low',
                    ])>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-heroicon-m-exclamation-circle @class([
                                    'h-5 w-5',
                                    'text-danger-400' => $recommendation['severity'] === 'high',
                                    'text-warning-400' => $recommendation['severity'] === 'medium',
                                    'text-gray-400' => $recommendation['severity'] === 'low',
                                ]) />
                            </div>
                            <div class="ml-3">
                                <h4 @class([
                                    'text-sm font-medium',
                                    'text-danger-800' => $recommendation['severity'] === 'high',
                                    'text-warning-800' => $recommendation['severity'] === 'medium',
                                    'text-gray-800' => $recommendation['severity'] === 'low',
                                ])>
                                    {{ $recommendation['message'] }}
                                </h4>
                                <div class="mt-2 text-sm text-gray-700">
                                    Current metric: {{ $recommendation['metric'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Status Distribution Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($metrics['status_metrics']->pluck('status')) !!},
                    datasets: [{
                        data: {!! json_encode($metrics['status_metrics']->pluck('count')) !!},
                        backgroundColor: [
                            '#10B981', // completed
                            '#EF4444', // rejected
                            '#F59E0B', // pending
                            '#6B7280', // draft
                        ],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    }
                }
            });
        </script>
    @endpush
</x-filament::page>