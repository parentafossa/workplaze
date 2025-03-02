<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\{Cell, DefaultValueBinder, DataType};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;

class EmployeesExport extends DefaultValueBinder implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithStyles, WithCustomValueBinder
{
    protected Builder $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->select([
            'emp_id',
            'company_id',
            'emp_name',
            'first_name',
            'middle_name',
            'last_name',
            'joined_date',
            'gender',
            'status',
            'job_title',
            'org_department',
            'org_division',
            'business_area',
            'location_city',
            'email_official',
            'bpjshealth_no',
            'bpjsnaker_no',
            'npwp',
            'ktp_no',
            'contact_no1',
            'contact_no2',
            'contact_no3'
        ]);
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Company ID',
            'Employee Name',
            'First Name',
            'Middle Name',
            'Last Name',
            'Join Date',
            'Gender',
            'Status',
            'Job Title',
            'Department',
            'Division',
            'Business Area',
            'Location',
            'Email',
            'BPJS Health No',
            'BPJS TK No',
            'NPWP',
            'KTP No',
            'Primary Contact',
            'Secondary Contact',
            'Other Contact'
        ];
    }

    public function map($row): array
    {
        // Format date
        $joinDate = $row->joined_date ? Carbon::parse($row->joined_date)->format('Y-m-d') : null;

        // Add prefix to force string treatment for numeric values
        $stringPrefix = "";

        return [
            $stringPrefix . $row->emp_id,               // Force string
            $stringPrefix . $row->company_id,           // Force string
            $row->emp_name,
            $row->first_name,
            $row->middle_name,
            $row->last_name,
            $joinDate,
            $row->gender,
            $row->status,
            $row->job_title,
            $row->org_department,
            $row->org_division,
            $row->business_area,
            $row->location_city,
            $row->email_official,
            $stringPrefix . $row->bpjshealth_no,       // Force string
            $stringPrefix . $row->bpjsnaker_no,        // Force string
            $stringPrefix . $row->npwp,                // Force string
            $stringPrefix . $row->ktp_no,              // Force string
            $stringPrefix . $row->contact_no1,         // Force string
            $stringPrefix . $row->contact_no2,         // Force string
            $stringPrefix . $row->contact_no3,         // Force string
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'G2:G' . $sheet->getHighestRow() => ['numberFormat' => ['formatCode' => 'yyyy-mm-dd']],
        ];
    }

/*     public function columnFormats(): array
    {
        return [
            'A' => DataType::TYPE_STRING2,
            //'Q' => '#0',

        ];
    } */

     public function bindValue(Cell $cell, $value)
    {
        $numericalColumns = ['']; // columns with numerical values

        if (!in_array($cell->getColumn(), $numericalColumns) || $value == '' || $value == null) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        if (in_array($cell->getColumn(), $numericalColumns)) {
            $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);

            return true;
        }

        // else return default behavior
        return parent::bindValue($cell, $value);
    } 
}