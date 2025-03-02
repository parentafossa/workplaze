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
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Trip Assignment</h2>
    </div>
    <div class="section">
        <h4>Assignment Details</h4>
        <table>
            <tr>
                <th>Trip Name</th>
                <td>{{ $assignment['trip_name'] }}</td>
            </tr>
            <tr>
                <th>Truck No</th>
                <td>{{ $assignment['truck_no'] }}</td>
            </tr>
            <tr>
                <th>Begin Date</th>
                <td>{{ \Carbon\Carbon::parse($assignment['begin_date'])->format('Y-m-d') }}</td>
            </tr>
            <tr>
                <th>Driver ID/Name</th>
                <td>{{ $assignment['driver_id'] }} - {{ $assignment['driver_name'] }}</td>
            </tr>
            <tr>
                <th>Assignment ID</th>
                <td>{{ $assignment['id'] }}</td>
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
                <th>Submit Date</th>
                <th>Plan Use Date</th>
            </tr>
            @foreach ($assignment['cashAdvanceRequests'] as $cashAdvance)
            <tr>
                <td>{{ $cashAdvance['ca_no'] }}</td>
                <td>Rp{{ number_format($cashAdvance['amount'], 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($cashAdvance['submit_date'])->format('Y-m-d') }}</td>
                <td>{{ \Carbon\Carbon::parse($cashAdvance['plan_use_date'])->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </table>
    </div>

    <div class="section">
        <div class="qr-code">
            <img src="{{ public_path('storage/qr_codes/assignment_' . $assignment['id'] . '.png') }}" alt="QR Code">
        </div>
    </div>
</body>
</html>
