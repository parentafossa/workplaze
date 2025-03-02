<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\D365VoucherImport;
use App\Models\D365VoucherTransaction;
use App\Models\D365VoucherTransactionImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
class D365VoucherTransactionImportController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            DB::beginTransaction();

            // Step 1: Truncate temporary table
            D365VoucherTransactionImport::truncate();

            // Step 2: Import to temporary table
            Excel::import(new D365VoucherImport, $request->file('file'));

            // Step 3: Get date range and entity code
            $dateRange = D365VoucherTransactionImport::selectRaw('MIN(date) as min_date, MAX(date) as max_date')
                ->first();

            $entityCode = D365VoucherTransactionImport::select('journal_number')
                ->first();
            $entityCode = substr($entityCode->journal_number, 0, 3);

            // Step 4: Delete matching records from main table
            D365VoucherTransaction::whereBetween('date', [$dateRange->min_date, $dateRange->max_date])
                ->where('journal_number', 'like', $entityCode . '%')
                ->delete();

            // Step 5: Insert from temporary to main table with all fields
            DB::table('d365_voucher_transactions')
                ->insertUsing(
                    [
                        'journal_number',
                        'tax_invoice_number',
                        'voucher',
                        'date',
                        'year_closed',
                        'ledger_account',
                        'account_name',
                        'description',
                        'currency',
                        'amount_in_transaction_currency',
                        'amount',
                        'amount_in_reporting_currency',
                        'posting_type',
                        'posting_layer',
                        'vendor_account',
                        'vendor_name',
                        'customer_account',
                        'customer_name',
                        'sort_key',
                        'job_id',
                        'bp_list',
                        'tax_invoice_number2',
                        'transaction_type',
                        'created_by',
                        'created_date_and_time',
                        'correction',
                        'crediting',
                        'currency2',
                        'description2',
                        'level',
                        'main_account',
                        'payment_reference',
                        'posting_type2',
                        'transaction_type2',
                        'created_at',
                        'updated_at'
                    ],
                    DB::table('d365_voucher_transaction_imports')
                        ->select([
                            'journal_number',
                            'tax_invoice_number',
                            'voucher',
                            'date',
                            'year_closed',
                            'ledger_account',
                            'account_name',
                            'description',
                            'currency',
                            'amount_in_transaction_currency',
                            'amount',
                            'amount_in_reporting_currency',
                            'posting_type',
                            'posting_layer',
                            'vendor_account',
                            'vendor_name',
                            'customer_account',
                            'customer_name',
                            'sort_key',
                            'job_id',
                            'bp_list',
                            'tax_invoice_number2',
                            'transaction_type',
                            'created_by',
                            'created_date_and_time',
                            'correction',
                            'crediting',
                            'currency2',
                            'description2',
                            'level',
                            'main_account',
                            'payment_reference',
                            'posting_type2',
                            'transaction_type2',
                            DB::raw('NOW()'),
                            DB::raw('NOW()')
                        ])
                );

            // Step 6: Clear temporary table
            D365VoucherTransactionImport::truncate();

            DB::commit();

            Notification::make()
                ->success()
                ->title('Import Successful')
                ->body("Data imported for date range: {$dateRange->min_date} to {$dateRange->max_date}")
                ->send();

            return back();

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Import Failed')
                ->body($e->getMessage())
                ->send();

            return back();
        }
    }
}
