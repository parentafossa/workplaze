<?php

namespace App\Enums;

enum QuotationType: string
{
    case SINGLE = 'single';
    case CONTINUOUS = 'continuous';

    public function getLabel(): string
    {
        return match($this) {
            self::SINGLE => 'single',
            self::CONTINUOUS => 'continuous',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::SINGLE => 'info',
            self::CONTINUOUS => 'success',
        };
    }

    public static function toArray(): array
    {
        return [
            self::SINGLE->value => self::SINGLE->getLabel(),
            self::CONTINUOUS->value => self::CONTINUOUS->getLabel(),
        ];
    }
}