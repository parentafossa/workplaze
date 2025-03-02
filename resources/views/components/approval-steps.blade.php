@props([
    'instance',
    'flow',
    'class' => '',
])

<nav {{ $attributes->merge(['class' => 'flex' . ($class ? ' ' . $class : '')]) }} aria-label="Progress">
    <ol role="list" class="space-y-6">
        @foreach($flow->steps as $index => $step)
            <li>
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div @class([
                            'relative flex h-5 w-5 items-center justify-center rounded-full',
                            'bg-primary-600' => $index < $instance->current_step,
                            'border-2 border-primary-600' => $index === $instance->current_step,
                            'border-2 border-gray-300' => $index > $instance->current_step,
                        ])>
                            @if($index < $instance->current_step)
                                <x-heroicon-m-check class="h-3 w-3 text-white" />
                            @elseif($index === $instance->current_step)
                                <div class="h-2.5 w-2.5 rounded-full bg-primary-600"></div>
                            @endif
                        </div>
                    </div>
                    <div class="ml-4 min-w-0">
                        <div class="flex items-center text-sm font-medium">
                            <span @class([
                                'text-primary-600' => $index <= $instance->current_step,
                                'text-gray-500' => $index > $instance->current_step,
                            ])>{{ $step['name'] }}</span>
                            @if($index === $instance->current_step && $instance->status === 'pending')
                                <span class="ml-2 text-xs font-normal text-gray-500">
                                    (Current)
                                </span>
                            @endif
                        </div>
                        <div class="mt-0.5">
                            <p class="text-xs text-gray-500">
                                @if($step['step_type'] === 'approve')
                                    {{ $step['approval_type'] === 'OR' ? 
                                        'Any approver can approve' : 
                                        'All approvers must approve' }}
                                @else
                                    {{ implode(', ', array_map(
                                        fn($opt) => str_replace('_', ' ', ucfirst($opt)),
                                        $step['submit_options'] ?? ['submit_for_approval']
                                    )) }}
                                @endif
                            </p>
                        </div>
                        @if($index === $instance->current_step)
                            <div class="mt-2">
                                <p class="text-xs text-gray-500">
                                    @if($instance->status === 'pending')
                                        Waiting for approval from: {{ $instance->approvable->current_approver_names }}
                                    @elseif($instance->status === 'pending_cancellation')
                                        Cancellation request pending
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
                @unless($loop->last)
                    <div @class([
                        'absolute left-0 top-5 -ml-px mt-0.5 h-full w-0.5',
                        'bg-primary-600' => $index < $instance->current_step,
                        'bg-gray-300' => $index >= $instance->current_step,
                    ])></div>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>