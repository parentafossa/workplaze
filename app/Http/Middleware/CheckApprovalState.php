<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApprovalState
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $record = $request->route('record');

        //dd($next($request)->getContent());

        if (!$record || !method_exists($record, 'isLocked')) {
            return $next($request);
        }

        if ($record->isLocked() && $request->isMethod('POST')) {
            $allowedActions = ['approve', 'reject', 'submit'];
            $currentAction = $request->input('action');

            if (!in_array($currentAction, $allowedActions)) {
                return redirect()->back()
                    ->with('error', 'Record is locked for editing while in approval process.')
                    ->withFragment('notification');
            }
        }

        return $next($request);
    }
}