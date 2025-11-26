<?php

namespace Molitor\Stock\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::In => __('stock::common.type_in'),
            self::Out => __('stock::common.type_out'),
            self::Transfer => __('stock::common.type_transfer'),
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
