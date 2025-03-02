@component('mail::message')
# {{ $reportName }} Approval Analytics Report

Dear {{ $recipient->name ?? 'Team Member' }},

The approval analytics report for {{ $reportName }} covering the period from **{{ $startDate->format('F j, Y') }}** to **{{ $endDate->format('F j, Y') }}** is now available. Please find the detailed report attached to this email.

This report includes:
- Overall completion rates and efficiency metrics
- Step-by-step performance analysis
- Top approver performance statistics
- Identified bottlenecks and recommendations

@component('mail::button', ['url' => route('filament.admin.pages.approval-analytics')])
View Online Dashboard
@endcomponent

If you have any questions about this report or would like to discuss specific metrics, please don't hesitate to reach out.

Best regards,  
{{ config('app.name') }} Team

---
*This is an automated report. Please do not reply to this email.*
@endcomponent