<?php

namespace App\Imports;

use App\Models\GwmsDeliveryNote;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Contracts\Queue\ShouldQueue;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;

class DeliveryNotesImport extends DefaultValueBinder implements 
    ToModel, 
    WithHeadingRow,
    WithCustomValueBinder,
    WithProgressBar
{

    use Importable;
    use RemembersRowNumber;
    public function model(array $row)
    {
        // Debug log to see the raw data
        /* \Log::info('Available keys in row:', array_keys($row));

        \Log::info('Processing row:', [
            'system_id' => $row['system_id'] ?? 'not set',
            'site_cd' => $row['site_cd'] ?? 'not set',
            'st_no' => $row['s_t_no'] ?? $row['st_no'] ?? $row['s/t_no'] ?? 'not set',
            'etd_raw' => $row['etd'] ?? 'not set',
            'bulk_m3' => $row['bulkm3'] ?? 'not set',
            'wgt_kg' => $row['wgtkg'] ?? 'not set',

        ]); */

        // Define the composite key conditions for upsert
        $conditions = [
            'system_id' => $row['system_id'] ?? null,
            'site_cd' => $row['site_cd'] ?? null,
            'st_no' => $row['s_t_no'] ?? $row['st_no'] ?? $row['s/t_no'] ?? null,
            'ship_to_cd' => $row['ship_to_cd'] ?? null,
        ];

        // Safe date conversions
        $etd = $this->safeConvertDate($row['etd'] ?? null);
        $eta = $this->safeConvertDate($row['eta'] ?? null);
        $printDate = $this->safeConvertDate($row['sj_receipt_print_date'] ?? null);
        $receivedDate = $this->safeConvertDate($row['sj_received_date'] ?? null);
        $dueDate = $this->safeConvertDate($row['due_date'] ?? null);

        // Handle bulk_m3 and wgt_kg with proper numeric conversion
        $bulkM3 = null;
        if (isset($row['bulk/m3']) || isset($row['bulkm3'])) {
            $bulkValue = $row['bulk/m3'] ?? $row['bulkm3'] ?? null;
            if (is_numeric($bulkValue)) {
                $bulkM3 = (float) $bulkValue;
            }
        }

        $wgtKg = null;
        if (isset($row['wgt/kg']) || isset($row['wgtkg'])) {
            $wgtValue = $row['wgt/kg'] ?? $row['wgtkg'] ?? null;
            if (is_numeric($wgtValue)) {
                $wgtKg = (float) $wgtValue;
            }
        }

        // Prepare data for upsert
        $data = [
            'system_id' => $row['system_id'] ?? null,
            'site_cd' => $row['site_cd'] ?? null,
            'site_name' => $row['site_name'] ?? null,
            'owner_cd' => $row['owner_cd'] ?? null,
            'owner_name' => $row['owner_name'] ?? null,
            'st_no' => $row['s_t_no'] ?? $row['st_no'] ?? $row['s/t_no'] ?? null, // Try different possible header names
            'status' => $row['status'] ?? null,
            'etd' => $etd,
            'eta' => $eta,
            'ship_to_cd' => $row['ship_to_cd'] ?? null,
            'ship_to_name' => $row['ship_to_name'] ?? null,
            'ship_to_adr1' => $row['ship_to_adr1'] ?? null,
            'ship_to_adr2' => $row['ship_to_adr2'] ?? null,
            'sj_barcode' => $row['s_j_barcode'] ?? null,
            'truck_cd' => $row['truck_cd'] ?? null,
            'truck_name' => $row['truck_name'] ?? null,
            'truck_no' => $row['truck_no'] ?? null,
            'ctn_qty' => is_numeric($row['ctn_qty'] ?? null) ? $row['ctn_qty'] : null,
            'bulk_m3' => is_numeric($row['bulkm3'] ?? null) ? (float)$row['bulkm3'] : null,
            'wgt_kg' => is_numeric($row['wgtkg'] ?? null) ? (float)$row['wgtkg'] : null,
            'sj_receipt_print_flg' => $row['sj_receipt_print_flg'] ?? false,
            'sj_receipt_print_flg_name' => $row['sj_receipt_print_flg_name'] ?? null,
            'sj_receipt_print_user_id' => $row['sj_receipt_print_user_id'] ?? null,
            'sj_receipt_print_user_name' => $row['sj_receipt_print_user_name'] ?? null,
            'sj_receipt_print_date' => $printDate,
            'sj_receipt_print_time' => $row['sj_receipt_print_time'] ?? null,
            'sj_qty' => is_numeric($row['sj_qty'] ?? null) ? $row['sj_qty'] : null,
            'sj_received_date' => $receivedDate,
            'sj_received_user' => $row['sj_received_user'] ?? null,
            'lt' => is_numeric($row['lt'] ?? null) ? $row['lt'] : null,
            'due_date' => $dueDate,
            'remarks' => $row['remarks'] ?? null,
        ];

        // Filter out null values from conditions
        $conditions = array_filter($conditions, function ($value) {
            return !is_null($value);
        });

        // Only proceed if we have at least one condition
        if (empty($conditions)) {
            \Log::warning('Skipping row due to missing key fields');
            return null;
        }

        try {
            return GwmsDeliveryNote::updateOrCreate($conditions, $data);
        } catch (\Exception $e) {
            \Log::error('Error creating/updating record at row ' . $this->getRowNumber() . ' : ' . $e->getMessage(), [
                'conditions' => $conditions,
                'data' => $data
            ]);
            throw $e;
        }
    }

    public function rules(): array
    {
        return [
            'system_id' => 'required|string',
            'site_cd' => 'required|string',
            's_t_no' => 'required|string',
            '*.system_id' => 'required|string',
            '*.site_cd' => 'required|string',
            '*.s_t_no' => 'required|string',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function headingRow(): int
    {
        return 1;
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

            // If it's a string, check for supported date formats
            if (is_string($value)) {
                // d/m/Y format (e.g., 31/01/2025)
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                    return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
                }

                // d-m-Y format (e.g., 31-01-2025)
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
                    return Carbon::createFromFormat('d-m-Y', $value)->startOfDay();
                }

                // d/m/Y H:i:s format (e.g., 31/01/2025 11:34:07)
                if (preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/', $value)) {
                    return Carbon::createFromFormat('d/m/Y H:i:s', $value);
                }

                // d-m-Y H:i:s format (e.g., 31-01-2025 11:34:07)
                if (preg_match('/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}$/', $value)) {
                    return Carbon::createFromFormat('d-m-Y H:i:s', $value);
                }
            }

            // Default fallback: try parsing with Carbon
            return Carbon::parse($value);

        } catch (\Exception $e) {
            \Log::warning("Date conversion failed for value: " . $value . " at row " . $this->getRowNumber());
            return null;
        }
    }


}
