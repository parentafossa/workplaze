<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::ACTIVE => 'success',
            self::EXPIRED => 'danger',
            self::CANCELLED => 'gray',
        };
    }

    public static function toArray(): array
    {
        return [
            self::ACTIVE->value => self::ACTIVE->getLabel(),
            self::EXPIRED->value => self::EXPIRED->getLabel(),
            self::CANCELLED->value => self::CANCELLED->getLabel(),
        ];
    }
}