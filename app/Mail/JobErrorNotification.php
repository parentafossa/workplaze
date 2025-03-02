<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobErrorNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $jobName;
    public $errorMessage;
    public $currentStep;
    public $recordCount;

    public function __construct($jobName, $errorMessage, $currentStep, $recordCount = null)
    {
        $this->jobName = $jobName;
        $this->errorMessage = $errorMessage;
        $this->currentStep = $currentStep;
        $this->recordCount = $recordCount;
    }

    public function build()
    {
        return $this->subject("Error in {$this->jobName}")
            ->markdown('emails.job-error', [
                'jobName' => $this->jobName,
                'errorMessage' => $this->errorMessage,
                'currentStep' => $this->currentStep,
                'recordCount' => $this->recordCount
            ]);
    }
}