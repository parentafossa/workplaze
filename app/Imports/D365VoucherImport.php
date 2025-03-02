<?php

namespace App\Imports;

use App\Models\D365VoucherTransactionImport;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\ImportHasFailedNotification;
use App\Models\User;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;

class D365VoucherImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading, WithProgressBar
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    use Importable;

    public function model(array $row)
    {
        // Convert header names to snake_case keys for consistent access
        $snakeCaseRow = collect($row)->mapWithKeys(function ($value, $key) {
            return [strtolower(str_replace(' ', '_', $key)) => $value];
        })->all();

        return new D365VoucherTransactionImport([
            'journal_number' => $snakeCaseRow['journal_number'],
            'tax_invoice_number' => $snakeCaseRow['tax_invoice_number'],
            'voucher' => $snakeCaseRow['voucher'],
            'date' => $this->parseDate($snakeCaseRow['date']),
            'year_closed' => strtolower($snakeCaseRow['year_closed'] ?? 'no') === 'yes',
            'ledger_account' => $snakeCaseRow['ledger_account'],
            'account_name' => $snakeCaseRow['account_name'],
            'description' => $snakeCaseRow['description'] ?? null,
            'currency' => $snakeCaseRow['currency'],
            'amount_in_transaction_currency' => $this->parseAmount($snakeCaseRow['amount_in_transaction_currency']),
            'amount' => $this->parseAmount($snakeCaseRow['amount']),
            'amount_in_reporting_currency' => $this->parseAmount($snakeCaseRow['amount_in_reporting_currency']),
            'posting_type' => $snakeCaseRow['posting_type'],
            'posting_layer' => $snakeCaseRow['posting_layer'],
            'vendor_account' => $snakeCaseRow['vendor_account'] ?? null,
            'vendor_name' => $snakeCaseRow['vendor_name'] ?? null,
            'customer_account' => $snakeCaseRow['customer_account'] ?? null,
            'customer_name' => $snakeCaseRow['customer_name'] ?? null,
            'sort_key' => $snakeCaseRow['sortkey'] ?? null,
            'job_id' => $snakeCaseRow['jobid'] ?? null,
            'bp_list' => $snakeCaseRow['bplist'] ?? null,
            'tax_invoice_number2' => $snakeCaseRow['tax_invoice_number2'] ?? null,
            'transaction_type' => $snakeCaseRow['transaction_type'],
            'created_by' => $snakeCaseRow['created_by'],
            'created_date_and_time' => $this->parseDateTime($snakeCaseRow['created_date_and_time']),
            'correction' => strtolower($snakeCaseRow['correction'] ?? 'no') === 'yes',
            'crediting' => strtolower($snakeCaseRow['crediting'] ?? 'no') === 'yes',
            'currency2' => $snakeCaseRow['currency2'] ?? null,
            'description2' => $snakeCaseRow['description2'] ?? null,
            'level' => intval($snakeCaseRow['level'] ?? 0),
            'main_account' => $snakeCaseRow['main_account'],
            'payment_reference' => $snakeCaseRow['payment_reference'] ?? null,
            'posting_type2' => $snakeCaseRow['posting_type2'] ?? null,
            'transaction_type2' => $snakeCaseRow['transaction_type2'] ?? null
        ]);
    }

    private function parseDate($value)
    {
        if (empty($value))
            return null;

        try {
            return Carbon::createFromFormat('m/d/y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    private function parseDateTime($value)
    {
        if (empty($value))
            return null;

        try {
            return Carbon::createFromFormat('m/d/y H:i', $value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return null;
            }
        }
    }

    private function parseAmount($value)
    {
        if (empty($value))
            return 0;

        // Remove any commas and currency symbols
        $value = str_replace([',', '$'], '', $value);
        return (float) $value;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }


}
