<?php

namespace App\Services;

use App\Models\CashAdvanceSequence;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CashAdvanceNumberGenerator
{
    protected $romanMonths = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
        5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
        9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];

    public function preview(int $companyId): string
    {
        $currentYear = now()->year;
        $currentMonth = now()->format('n');
        
        $nextNumber = $this->getNextNumber($companyId);
        $company = Company::find($companyId);
        
        $sequenceFormatted = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        $romanMonth = $this->romanMonths[$currentMonth];

        return "ADV-{$sequenceFormatted}/CA/{$company->short_name}/{$romanMonth}/{$currentYear}";
    }

    public function generate(int $companyId): string
    {
        $currentYear = now()->year;
        $currentMonth = now()->format('n');
        
        // Use transaction to ensure sequence integrity
        $sequence = DB::transaction(function () use ($companyId, $currentYear) {
            $sequenceRecord = CashAdvanceSequence::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'year' => $currentYear,
                ],
                [
                    'last_number' => 0
                ]
            );
            
            $sequenceRecord->increment('last_number');
            
            return $sequenceRecord->last_number;
        });

        $company = Company::find($companyId);
        $sequenceFormatted = str_pad($sequence, 5, '0', STR_PAD_LEFT);
        $romanMonth = $this->romanMonths[$currentMonth];

        return "ADV-{$sequenceFormatted}/CA/{$company->short_name}/{$romanMonth}/{$currentYear}";
    }

    protected function getNextNumber(int $companyId): int
    {
        $currentYear = now()->year;
        
        $sequence = CashAdvanceSequence::where('company_id', $companyId)
            ->where('year', $currentYear)
            ->first();
            
        return ($sequence ? $sequence->last_number : 0) + 1;
    }
}