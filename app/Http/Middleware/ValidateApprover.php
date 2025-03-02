<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Services\ApprovalService;

class ValidateApprover
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function __construct(private readonly ApprovalService $approvalService) 
    {

    }

    public function handle(Request $request, Closure $next): Response
    {
        $record = $request->route('record');
     /*\Log::info('Middleware ValidateApprover invoked', [
        'record_type' => gettype($record),
        'record_class' => is_object($record) ? get_class($record) : null,
    ]);*/  

        $user = $request->user();

        if (!$record || !method_exists($record, 'currentApprovalInstance')) {
            return $next($request);
        }

        $instance = $record->currentApprovalInstance();
        if (!$instance) {
            return $next($request);
        }

        $currentStep = $instance->currentStepConfig;
        if (!$currentStep) {
            return $next($request);
        }

        if (!$this->approvalService->isAuthorizedApprover($user, $currentStep)) {
            return redirect()->back()
                ->with('error', 'You are not authorized to perform this approval action.')
                ->withFragment('notification');
        }

        return $next($request);
    }
}
