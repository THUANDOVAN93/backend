<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::PENDING => 'Pending',
            self::SUSPENDED => 'Suspended',
            self::INACTIVE => 'Inactive',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::PENDING => 'yellow',
            self::SUSPENDED => 'red',
            self::INACTIVE => 'gray',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
            ],
            self::cases()
        );
    }
}
