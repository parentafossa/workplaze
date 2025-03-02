<?php

namespace App\Enums;

enum WarehouseType: string
{
    case NONE = 'none';
    case OWNED = 'owned';
    case RENTAL = 'rental';
    case CUSTOMER = 'customer';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match($this) {
            self::NONE => 'none',
            self::OWNED => 'owned',
            self::RENTAL => 'rental',
            self::CUSTOMER => 'customer',
            self::OTHER => 'other',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::NONE => 'gray',
            self::OWNED => 'success',
            self::RENTAL => 'info',
            self::CUSTOMER => 'warning',
            self::OTHER => 'gray',
        };
    }

    public static function toArray(): array
    {
        return [
            self::NONE->value => self::NONE->getLabel(),
            self::OWNED->value => self::OWNED->getLabel(),
            self::RENTAL->value => self::RENTAL->getLabel(),
            self::CUSTOMER->value => self::CUSTOMER->getLabel(),
            self::OTHER->value => self::OTHER->getLabel(),

        ];
    }
}