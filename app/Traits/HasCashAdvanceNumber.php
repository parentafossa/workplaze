<?php

namespace App\Traits;

trait HasCashAdvanceNumber
{
    protected static function bootHasCashAdvanceNumber()
    {
        static::creating(function ($model) {
            $generator = new \App\Services\CashAdvanceNumberGenerator();
            $model->ca_no = $generator->generate($model->company_id);
        });
    }

    public static function previewNextNumber($companyId)
    {
        $generator = new \App\Services\CashAdvanceNumberGenerator();
        return $generator->preview($companyId);
    }
}