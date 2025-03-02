<div class="space-y-4">
    @php
        $instance = $getRecord()->currentApprovalInstance();
        $flow = $instance?->approvalFlow;
        $currentStep = $instance?->currentStepConfig;
    @endphp

    @if($instance && $flow)
        {{-- Current Status --}}
        <div class="rounded-lg bg-gray-50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-medium text-gray-900">Current Status</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($instance->status === 'pending' && $currentStep)
                            Waiting for {{ $currentStep['name'] }}
                            <span class="text-xs text-gray-400">
                                ({{ $currentStep['step_type'] === 'submit' ? 'Submission' : 'Approval' }} Step)
                            </span>
                        @elseif($instance->status === 'pending_cancellation')
                            Cancellation Request - Waiting for Approval
                        @else
                            {{ match($instance->status) {
                                'completed' => 'Approval Process Completed',
                                'cancelled' => 'Request Cancelled',
                                'rejected' => 'Request Rejected',
                                'draft' => 'Draft',
                                default => ucfirst($instance->status)
                            } }}
                        @endif
                    </p>
                </div>
                <div @class([
                    'inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium',
                    'bg-green-100 text-green-800' => $instance->status === 'completed',
                    'bg-red-100 text-red-800' => in_array($instance->status, ['cancelled', 'rejected']),
                    'bg-yellow-100 text-yellow-800' => in_array($instance->status, ['pending', 'pending_cancellation']),
                    'bg-gray-100 text-gray-800' => $instance->status === 'draft'
                ])>
                    {{ match($instance->status) {
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'rejected' => 'Rejected',
                        'pending' => 'Pending',
                        'pending_cancellation' => 'Pending Cancellation',
                        'draft' => 'Draft',
                        default => ucfirst($instance->status)
                    } }}
                </div>
            </div>
        </div>

        {{-- Approval Flow Steps --}}
        <div class="rounded-lg border border-gray-200">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <h3 class="text-sm font-medium text-gray-900">Approval Flow Steps</h3>
            </div>
            <div class="divide-y divide-gray-200">
                @foreach($flow->steps as $index => $step)
                    <div class="p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $step['name'] }}
                                    <span class="ml-2 text-xs text-gray-500">
                                        ({{ $step['step_type'] === 'submit' ? 'Submit' : 'Approval' }} Step)
                                    </span>
                                </p>
                                <p class="text-xs text-gray-500">
                                    @if($step['step_type'] === 'approve')
                                        {{ $step['approval_type'] === 'OR' ? 'Any person can process' : 'All person must process' }} : {{ $getRecord()->current_approver_names }}
                                    @else
                                        Submit options: {{ implode(', ', array_map(
                                            fn($opt) => str_replace('_', ' ', ucfirst($opt)), 
                                            $step['submit_options'] ?? ['submit_for_approval']
                                        )) }}
                                    @endif
                                </p>
                            </div>
                            <div @class([
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-800' => $index < $instance->current_step,
                                'bg-yellow-100 text-yellow-800' => $index === $instance->current_step,
                                'bg-gray-100 text-gray-800' => $index > $instance->current_step
                            ])>
                                {{ $index < $instance->current_step ? 'Completed' : 
                                   ($index === $instance->current_step ? 'Current' : 'Pending') }}
                            </div>
                        </div>

                        @if($index === $instance->current_step && $instance->status === 'pending')
                            <div class="mt-2 text-xs text-gray-500">
                                Current Processor: {{ $getRecord()->current_approver_names }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Approval History --}}
        @if($instance->actions()->exists())
            <div class="rounded-lg border border-gray-200">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                    <h3 class="text-sm font-medium text-gray-900">Approval History</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @foreach($instance->actions()->with(['user.employeeInfo'])->latest()->get() as $action)
                        @php
                            $employee = $action->employee;
                            $stepInfo = $flow->steps[$action->step_number] ?? null;
                        @endphp
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $employee?->emp_name ?? 'Unknown User' }}
                                        </p>
                                        <div class="flex text-sm text-gray-500 items-center space-x-1">
                                            <span class="text-xs text-gray-400">({{ $action->user_id }})</span>
                                            <span>•</span>
                                            <span>{{ $stepInfo['name'] ?? "Step {$action->step_number}" }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-green-100 text-green-800' => $action->action === 'approve',
                                    'bg-red-100 text-red-800' => $action->action === 'reject',
                                    'bg-blue-100 text-blue-800' => $action->action === 'submit',
                                    'bg-gray-100 text-gray-800' => !in_array($action->action, ['approve', 'reject', 'submit'])
                                ])>
                                    @if($action->action === 'submit')
                                        {{ str_replace('_', ' ', ucfirst($action->submit_type)) }}
                                    @else
                                        {{ ucfirst($action->action) }}
                                    @endif
                                </div>
                            </div>
                            @if($action->comments)
                                <div class="mt-2 text-sm text-gray-500 bg-gray-50 rounded p-2">
                                    {{ $action->comments }}
                                </div>
                            @endif
<div class="mt-2 text-xs text-gray-400">
                                {{ $action->created_at?->diffForHumans() ?? 'Unknown time' }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Timeline View --}}
        <div class="rounded-lg border border-gray-200">
            <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                <h3 class="text-sm font-medium text-gray-900">Approval Timeline</h3>
            </div>
            <div class="p-4">
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach($instance->actions()->with(['user.employeeInfo'])->orderBy('created_at', 'asc')->get() as $action)
                            <li>
                                <div class="relative pb-8">
                                    @unless($loop->last)
                                        <span class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endunless
                                    <div class="relative flex items-start space-x-3">
                                        {{-- Icon/Status Indicator --}}
                                        <div @class([
                                            'relative flex h-10 w-10 items-center justify-center rounded-full',
                                            'bg-green-100' => $action->action === 'approve',
                                            'bg-red-100' => $action->action === 'reject',
                                            'bg-blue-100' => $action->action === 'submit',
                                            'bg-gray-100' => !in_array($action->action, ['approve', 'reject', 'submit'])
                                        ])>
                                            <x-heroicon-m-check-circle @class([
                                                'h-6 w-6',
                                                'text-green-600' => $action->action === 'approve',
                                                'text-red-600' => $action->action === 'reject',
                                                'text-blue-600' => $action->action === 'submit',
                                                'text-gray-600' => !in_array($action->action, ['approve', 'reject', 'submit'])
                                            ]) />
                                        </div>
                                        
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <div class="text-sm">
                                                    <span class="font-medium text-gray-900">
                                                        {{ $action->employee?->emp_name ?? 'Unknown User' }}
                                                    </span>
                                                    <span class="text-gray-500">
                                                        {{ match($action->action) {
                                                            'approve' => 'approved',
                                                            'reject' => 'rejected',
                                                            'submit' => $action->submit_type === 'submit_for_cancellation' 
                                                                ? 'submitted for cancellation'
                                                                : 'submitted for approval',
                                                            default => $action->action
                                                        } }}
                                                    </span>
                                                </div>
                                                <p class="mt-0.5 text-xs text-gray-500">
                                                    {{ $action->created_at?->format('M d, Y h:i A') }}
                                                </p>
                                            </div>
                                            @if($action->comments)
                                                <div class="mt-2 text-sm text-gray-700">
                                                    <p>{{ $action->comments }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

    @else
        <div class="rounded-lg bg-gray-50 p-4">
            <p class="text-sm text-gray-500">
                {{ !$instance ? 'No approval process started yet.' : 'Approval flow configuration is missing.' }}
            </p>
        </div>
    @endif
</div>