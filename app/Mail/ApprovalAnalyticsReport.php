<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Carbon;

class ApprovalAnalyticsReport extends Mailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $reportPath,
        protected string $reportName,
        protected Carbon $startDate,
        protected Carbon $endDate
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                '%s Approval Analytics Report (%s - %s)',
                $this->reportName,
                $this->startDate->format('M j, Y'),
                $this->endDate->format('M j, Y')
            )
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.approval-analytics-report',
            with: [
                'reportName' => $this->reportName,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->reportPath)
                ->as(basename($this->reportPath))
                ->withMime('application/pdf'),
        ];
    }
}