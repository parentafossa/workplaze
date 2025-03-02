<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ApprovalNotifications extends Widget
{
    protected static string $view = 'filament.widgets.approval-notifications';

    protected static ?int $sort = 1;

    public function getNotifications(): Collection
    {
        return auth()
            ->user()
            ->unreadNotifications()
            ->where('type', 'App\Notifications\ApprovalAssigned')
            ->get()
            ->take(5);
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()
            ->user()
            ->notifications()
            ->findOrFail($notificationId);

        $notification->markAsRead();

        $this->dispatch('notification-read');
    }

    public function getRecord(string $type, string $id): ?Model
    {
        return app($type)->find($id);
    }

    protected function getPollingInterval(): ?string
    {
        return '10s';
    }
}