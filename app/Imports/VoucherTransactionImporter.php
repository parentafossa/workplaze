<?php

namespace App\Imports;

use App\Models\D365VoucherTransactionImport;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class VoucherTransactionImporter extends DefaultValueBinder implements
    ToModel,
    WithHeadingRow,
    WithCustomValueBinder,
    WithProgressBar
{
    use Importable;
    use RemembersRowNumber;

    public function model(array $row)
    {
        // Define the composite key conditions for upsert
        $conditions = [
            'journal_number' => $row['journal_number'] ?? null,
            'voucher' => $row['voucher'] ?? null,
            'ledger_account' => $row['ledger_account'] ?? null,
        ];

        // Handle date conversion
        $date = $this->safeConvertDate($row['date'] ?? null);
        $createdDateTime = $this->safeConvertDate($row['created_date_and_time'] ?? null);

        // Convert Yes/No to boolean values
        $yearClosed = $this->convertYesNoToBoolean($row['year_closed'] ?? 'No');
        $correction = $this->convertYesNoToBoolean($row['correction'] ?? 'No');
        $crediting = $this->convertYesNoToBoolean($row['crediting'] ?? 'No');

        // Prepare data for upsert
        $data = [
            'journal_number' => $row['journal_number'] ?? null,
            'tax_invoice_number' => $row['tax_invoice_number'] ?? null,
            'voucher' => $row['voucher'] ?? null,
            'date' => $date,
            'year_closed' => $yearClosed,
            'ledger_account' => $row['ledger_account'] ?? null,
            'account_name' => $row['account_name'] ?? null,
            'description' => $row['description'] ?? null,
            'currency' => $row['currency'] ?? null,
            'amount_in_transaction_currency' => $this->parseAmount($row['amount_in_transaction_currency'] ?? null),
            'amount' => $this->parseAmount($row['amount'] ?? null),
            'amount_in_reporting_currency' => $this->parseAmount($row['amount_in_reporting_currency'] ?? null),
            'posting_type' => $row['posting_type'] ?? null,
            'posting_layer' => $row['posting_layer'] ?? null,
            'vendor_account' => $row['vendor_account'] ?? null,
            'vendor_name' => $row['vendor_name'] ?? null,
            'customer_account' => $row['customer_account'] ?? null,
            'customer_name' => $row['customer_name'] ?? null,
            'sort_key' => $row['sort_key'] ?? null,
            'job_id' => $row['job_id'] ?? null,
            'bp_list' => $row['bp_list'] ?? null,
            'tax_invoice_number2' => $row['tax_invoice_number2'] ?? null,
            'transaction_type' => $row['transaction_type'] ?? null,
            'created_by' => $row['created_by'] ?? null,
            'created_date_and_time' => $createdDateTime,
            'correction' => $correction,
            'crediting' => $crediting,
            'currency2' => $row['currency2'] ?? null,
            'description2' => $row['description2'] ?? null,
            'level' => (int) ($row['level'] ?? 0),
            'main_account' => $row['main_account'] ?? null,
            'payment_reference' => $row['payment_reference'] ?? null,
            'posting_type2' => $row['posting_type2'] ?? null,
            'transaction_type2' => $row['transaction_type2'] ?? null,
        ];

        // Filter out null values from conditions
        $conditions = array_filter($conditions, function ($value) {
            return !is_null($value);
        });

        // Only proceed if we have at least one condition
        if (empty($conditions)) {
            \Log::warning('Skipping row ' . $this->getRowNumber() . ' due to missing key fields');
            return null;
        }

        try {
            return D365VoucherTransactionImport::updateOrCreate($conditions, $data);
        } catch (\Exception $e) {
            \Log::error('Error creating/updating record at row ' . $this->getRowNumber() . ' : ' . $e->getMessage(), [
                'conditions' => $conditions,
                'data' => $data
            ]);
            throw $e;
        }
    }

    protected function convertYesNoToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['yes', 'y', 'true', '1']);
        }

        return false;
    }

    public function rules(): array
    {
        return [
            'journal_number' => 'required|string',
            'voucher' => 'required|string',
            'ledger_account' => 'required|string',
            '*.journal_number' => 'required|string',
            '*.voucher' => 'required|string',
            '*.ledger_account' => 'required|string',
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value)) {
            // Clean and convert encoding
            $cleanValue = mb_convert_encoding($value, 'UTF-8', mb_detect_encoding($value, 'UTF-8, ISO-8859-1, Windows-1252', true));
            $cleanValue = iconv('UTF-8', 'UTF-8//IGNORE', $cleanValue);
            if ($cleanValue !== false) {
                $cell->setValueExplicit($cleanValue, DataType::TYPE_STRING);
                return true;
            }
        }

        return parent::bindValue($cell, $value);
    }

    protected function safeConvertDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Check if the value is numeric (Excel timestamp)
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value);
            }

            if (is_string($value)) {
                // d/m/Y format (e.g., 31/01/2025)
                    return Carbon::createFromFormat('m/d/Y', $value)->startOfDay();
            }
            // If it's a string, try parsing with Carbon
            return Carbon::parse($value);

        } catch (\Exception $e) {
            \Log::warning("Date conversion failed for value: " . $value . " at row " . $this->getRowNumber());
            return null;
        }
    }

    protected function parseAmount($value)
    {
        if (empty($value)) {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove any currency symbols and thousand separators, then convert to float
        $cleanValue = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($cleanValue) ? (float) $cleanValue : 0;
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}