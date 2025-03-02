<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;

use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;

class CustomersImport implements ToModel, WithHeadingRow, WithUpserts, WithUpsertColumns, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Customer([
            'id' => $row['account'] ?? $row['id'],
            'name' => $row['name'],
            'group' => $row['customer_group'] ?? $row['group'],
            'currency' => $row['currency'] ?? 'IDR',
            'telephone' => $row['telephone'],
            'updated_at' => now(), // Add this to track updates
        ]);
    }

    /**
     * @return string|array
     */
    public function uniqueBy()
    {
        return 'id';
    }

    /**
     * @return array
     */
    public function upsertColumns()
    {
        return [
            'name',
            'group',
            'currency',
            'telephone',
            'updated_at',
            // Add any other columns that should be updated
        ];
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'account' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'customer_group' => 'nullable|string|max:50',
            'currency' => 'nullable|string|in:IDR,USD,EUR',
            'telephone' => 'nullable|string|max:255',
        ];
    }
}
