<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 15px; }
        .qr-code { text-align: right; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; }
        table, th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; width: 30%; }
        td { width: 70%; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    @foreach ($tripsData as $tripIndex => $trip)
        @foreach ($trip['assignments'] as $assignmentIndex => $assignment)
            <!-- Separate page for each assignment within the trip -->
            <div class="header">
                <h2>Trip Assignment</h2>
            </div>
            <div class="section">
                <h4>Assignment Details</h4>
                <table>
                    <tr>
                        <th>Trip Name</th>
                        <td>{{ $trip['trip_name'] }}</td>
                    </tr>
                    <tr>
                        <th>Truck No</th>
                        <td>{{ $trip['truck_no'] }}</td>
                    </tr>
                    <tr>
                        <th>Begin Date</th>
                        <td>{{ \Carbon\Carbon::parse($trip['begin_date'])->format('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <th>Driver ID/Name</th>
                        <td>{{ $assignment['driver_id'] }} - {{ $assignment['driver_name'] }}</td>
                    </tr>
                    <tr>
                        <th>Assignment ID</th>
                        <td>{{ $assignment['assignment_id'] }}</td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ \Carbon\Carbon::parse($assignment['created_at'])->format('Y-m-d H:i:s') }}</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <h4>Cash Advance Requests</h4>
                <table>
                    <tr>
                        <th>Cash Advance Number</th>
                        <th>Amount</th>
                        <th>Remaining Balance</th>
                        <th>Submit Date</th>
                        <th>Plan Use Date</th>
                        <th>Status</th>
                    </tr>
                    @foreach ($assignment['cashAdvanceRequests'] as $cashAdvance)
                    <tr>
                        <td>{{ $cashAdvance['ca_no'] }}</td>
                        <td>Rp{{ number_format($cashAdvance['amount'], 2) }}</td>
                        <td>Rp{{ number_format($cashAdvance['remaining_balance'] ?? 0, 2) }}</td>
                        <td>{{ \Carbon\Carbon::parse($cashAdvance['submit_date'])->format('Y-m-d') }}</td>
                        <td>{{ \Carbon\Carbon::parse($cashAdvance['plan_use_date'])->format('Y-m-d') }}</td>
                        <td>{{ ucfirst($cashAdvance['status']) }}</td>
                    </tr>
                    @endforeach
                </table>
            </div>

            <div class="section">
                <div class="qr-code">
                    <img src="{{ public_path('storage/qr_codes/assignment_' . $assignment['assignment_id'] . '.png') }}" alt="QR Code">
                </div>
            </div>

            <!-- Page break after each assignment except the last one in each trip -->
            @if ($tripIndex === count($tripsData) - 1 && $assignmentIndex === count($trip['assignments']) - 1 )
            @else    
                <div class="page-break"></div>
            @endif
        @endforeach

    @endforeach
</body>
</html>
