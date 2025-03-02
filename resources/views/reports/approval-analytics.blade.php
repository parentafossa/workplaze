<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Approval Analytics Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
        }
        .container {
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #E5E7EB;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #E5E7EB;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            padding: 15px;
            background-color: #F9FAFB;
            border-radius: 8px;
        }
        .stat-title {
            font-size: 14px;
            font-weight: bold;
            color: #6B7280;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #1F2937;
            margin: 5px 0;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table th,
        .table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        .table th {
            background-color: #F9FAFB;
            font-weight: bold;
            color: #4B5563;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-success { background-color: #D1FAE5; color: #065F46; }
        .badge-warning { background-color: #FEF3C7; color: #92400E; }
        .badge-danger { background-color: #FEE2E2; color: #991B1B; }
        .meta-info {
            font-size: 12px;
            color: #6B7280;
            text-align: right;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Approval Analytics Report</h1>
            <p>{{ $startDate }} to {{ $endDate }}</p>
            @if($flow)
                <p>Flow: {{ $flow->name }}</p>
            @endif
        </div>

        <div class="section">
            <h2 class="section-title">Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Completion Rate</div>
                    <div class="stat-value">{{ number_format($metrics['summary']['completion_rate'], 1) }}%</div>
                    <div>{{ $metrics['total_requests'] }} total requests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Average Duration</div>
                    <div class="stat-value">{{ number_format($metrics['summary']['average_duration_hours'], 1) }}h</div>
                    <div>Per approval process</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Process Efficiency</div>
                    <div class="stat-value">{{ number_format($efficiency['average_score'], 1) }}%</div>
                    <div>Overall efficiency score</div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Bottlenecks</div>
                    <div class="stat-value">{{ $metrics['bottlenecks']->count() }}</div>
                    <div>Steps requiring attention</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">Step Performance Analysis</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Step</th>
                        <th>Avg. Duration</th>
                        <th>Actions</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metrics['step_metrics'] as $step)
                        <tr>
                            <td>Step {{ $step->step_number }}</td>
                            <td>{{ number_format($step->avg_duration / 60, 1) }} hours</td>
                            <td>{{ $step->count }}</td>
                            <td>
                                @if($step->is_bottleneck)
                                    <span class="badge badge-{{ $step->severity === 'high' ? 'danger' : 'warning' }}">
                                        Bottleneck ({{ ucfirst($step->severity) }})
                                    </span>
                                @else
                                    <span class="badge badge-success">Optimal</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2 class="section-title">Top Approvers</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Total Actions</th>
                        <th>Response Time</th>
                        <th>Efficiency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_slice($approverPerformance, 0, 5) as $approver)
                        <tr>
                            <td>{{ $approver->emp_name }}</td>
                            <td>{{ $approver->total_actions }}</td>
                            <td>{{ number_format($approver->avg_response_time / 60, 1) }} hours</td>
                            <td>
                                @php
                                    $efficiency = ($approver->approvals / $approver->total_actions) * 100;
                                @endphp
                                <span class="badge badge-{{ $efficiency >= 80 ? 'success' : ($efficiency >= 60 ? 'warning' : 'danger') }}">
                                    {{ number_format($efficiency, 1) }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(!empty($metrics['recommendations']))
            <div class="section">
                <h2 class="section-title">Recommendations</h2>
                @foreach($metrics['recommendations'] as $recommendation)
                    <div style="margin-bottom: 15px; padding: 10px; background-color: {{ $recommendation['severity'] === 'high' ? '#FEE2E2' : ($recommendation['severity'] === 'medium' ? '#FEF3C7' : '#F3F4F6') }};">
                        <div style="font-weight: bold; margin-bottom: 5px;">
                            {{ $recommendation['message'] }}
                        </div>
                        <div style="font-size: 14px;">
                            Current metric: {{ $recommendation['metric'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="meta-info">
            Generated on {{ $generatedAt }}
        </div>
    </div>
</body>
</html>