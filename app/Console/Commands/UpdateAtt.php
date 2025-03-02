<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\JobErrorNotification;
class UpdateAtt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:update';

    protected $description = 'Updates attendance temporary tables and logs the process';

    protected $currentStep;
    protected $recordCount;
    protected $startDate;

    protected $processName;
    public function __construct($startDate = null)
    {
        parent::__construct();
        $this->startDate = $startDate ?? '2024-12-16';
        $this->processName = 'WP Abs Update';
    }

    public function handle()
    {
        try {
            // Start by logging
            $this->logProcess('Process Start');

            // Step 1: Insert into att_temp_prep
            $this->currentStep = 'Inserting into att_temp_prep';
            $this->insertIntoTempPrep();
            //Log::info('Step 1 completed');

            // Step 2: Copy to att_temp
            $this->currentStep = 'Copying data to att_temp';
            $this->copyToAttTemp();
            //Log::info('Step 2 completed');

            // Step 3: Log statistics
            $this->currentStep = 'Logging statistics';
            $this->logStatistics();
            //Log::info('Step 3 completed');

            // Step 4: Direct cleanup
            $this->currentStep = 'Cleaning up temporary table';
            DB::connection('dataon')->statement('TRUNCATE TABLE att_temp_prep');

            //Log::info('Step 4 completed');

            // Log success
            $this->logProcess('Process Completed Successfully');

        } catch (Exception $e) {
            $this->handleDatabaseError($e);
        }
    }

    protected function insertIntoTempPrep()
    {
        DB::connection('dataon')->statement("
            INSERT INTO dataon.att_temp_prep
                SELECT a.*
                FROM dataon.v_att_log a
                WHERE NOT EXISTS (
                    SELECT 1 FROM dataon.att_temp b 
                    WHERE b.hash = a.hash
                    )
                    AND NOT EXISTS (
                        SELECT 1 FROM dataon.att_pulled c 
                        WHERE c.hash = a.hash
                    )
                    AND a.scan_date > '2024-12-15'
                    AND (a.pin LIKE '1%' OR a.pin LIKE '2%' OR a.pin LIKE '3%')
        ");

        $this->recordCount = DB::connection('dataon')
            ->table('att_temp_prep')
            ->count();
        $this->logProcess("Prepared records: {$this->recordCount} records");
    }

    protected function copyToAttTemp()
    {
        DB::connection('dataon')->unprepared('INSERT INTO att_temp SELECT * FROM att_temp_prep');
    }

    protected function logStatistics()
    {
        $categories = ['1', '2', '3'];
        foreach ($categories as $category) {
            $count = DB::connection('dataon')
                ->table('att_temp_prep')
                ->where('pin', 'like', $category . '%')
                ->count();

            $categoryName = $this->getCategoryName($category);
            $this->logProcess("Retrieved from ATT_LOG: {$count} records {$categoryName}");
        }
    }

    protected function cleanup()
    {
        /*         try {
                    $db = DB::connection('dataon');
                    Log::info('Inside cleanup - transaction level: ' . $db->transactionLevel());

                    // TRUNCATE without transaction
                    $db->statement('TRUNCATE TABLE att_temp_prep');
                    Log::info('Cleanup completed - att_temp_prep table truncated');
                } catch (Exception $e) {
                    Log::error('Cleanup failed: ' . $e->getMessage());
                    throw $e;
                } */
    }

    protected function getCategoryName($category)
    {
        return [
            '1' => 'BML',
            '2' => 'VID',
            '3' => 'LID'
        ][$category] ?? 'Unknown';
    }

    protected function logProcess($remark)
    {
        try {
            DB::connection('dataon')->table('process_log')->insert([
                'process_name' => $this->processName,
                'remark' => $remark,
                'created_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log process: ' . $e->getMessage());
        }
    }


    protected function handleDatabaseError($e)
    {
        $errorMessage = $this->formatErrorMessage($e);
        Log::error($errorMessage);

        try {
            $this->logProcess("Error: {$errorMessage}");
            if ($this->recordCount !== null) {
                $this->logProcess("Records processed before error: {$this->recordCount}");
            }
        } catch (Exception $logException) {
            Log::error('Failed to log error to database: ' . $logException->getMessage());
        }

        // Send notification without throwing
        try {
            $this->sendErrorNotification($errorMessage);
        } catch (Exception $mailException) {
            Log::error('Failed to send error notification: ' . $mailException->getMessage());
        }
    }

    protected function handleGeneralError(Exception $e)
    {
        /*         $errorMessage = $this->formatErrorMessage($e);

                // Log to database
                $this->logProcess("Error: {$errorMessage}");

                // Log to Laravel log
                Log::error($errorMessage);

                // Send email notification
                $this->sendErrorNotification($errorMessage);

                throw $e; */
    }

    protected function formatErrorMessage($e)
    {
        return sprintf(
            'Error in step: %s | Error Code: %s | Message: %s',
            $this->currentStep,
            $e->getCode(),
            $e->getMessage()
        );
    }

    protected function sendErrorNotification($errorMessage)
    {
        $notificationEmail = config('mail.error_notification_address') ?? 'your-default-email@example.com';

        // Add logging to debug
        Log::info('Sending error notification to: ' . $notificationEmail);

        try {
            Mail::to($notificationEmail)
                ->send(new JobErrorNotification(
                    'Update Att Temp Job',
                    $errorMessage,
                    $this->currentStep,
                    $this->recordCount
                ));
        } catch (Exception $e) {
            // Log the email error but don't throw it
            Log::error('Failed to send error notification: ' . $e->getMessage());
        }
    }
}