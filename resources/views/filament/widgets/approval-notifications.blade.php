<div 
    class="fi-wi-approval-notifications"
    wire:poll.10s
>
    @if($this->getNotifications()->isNotEmpty())
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
            <h3 class="text-base font-semibold leading-6 text-gray-900">
                Pending Approvals
            </h3>

            <div class="mt-4 divide-y divide-gray-200">
                @foreach($this->getNotifications() as $notification)
                    @php
                        $record = $this->getRecord(
                            $notification->data['approvable_type'],
                            $notification->data['approvable_id']
                        );
                    @endphp

                    @if($record)
                        <div class="py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $notification->data['title'] }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $notification->data['message'] }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="ml-4">
                                    <button
                                        wire:click="markAsRead('{{ $notification->id }}')"
                                        type="button"
                                        class="rounded-full p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    >
                                        <span class="sr-only">Dismiss</span>
                                        <x-heroicon-m-x-mark class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                            <div class="mt-3 flex">
                                <a
                                    href="{{ 
                                        route(
                                            'filament.admin.resources.' . 
                                            strtolower(class_basename($notification->data['approvable_type'])) . 
                                            's.edit',
                                            ['record' => $record]
                                        )
                                    }}"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-500"
                                >
                                    View Request
                                    <span aria-hidden="true"> &rarr;</span>
                                </a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>