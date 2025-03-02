@component('mail::message')
# Error in {{ $jobName }}

An error occurred while executing the job.

**Step:** {{ $currentStep }}

**Error Details:**
{{ $errorMessage }}

@if($recordCount !== null)
    **Records Processed Before Error:** {{ $recordCount }}
@endif

@component('mail::button', ['url' => config('app.url')])
View Logs
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent