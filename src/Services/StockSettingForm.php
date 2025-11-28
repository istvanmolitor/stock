<?php

namespace Molitor\Stock\Services;

use Filament\Forms\Components\TextInput;
use Molitor\Setting\Services\SettingForm;

class StockSettingForm extends SettingForm
{

    public function getSlug(): string
    {
        return 'stock';
    }

    public function getLabel(): string
    {
        return __('stock::stock.title');
    }

    public function getForm(): array
    {
        return [
            TextInput::make('default_warehouse_name')
                ->label(__('stock::common.default_warehouse_name'))
                ->required()
                ->maxLength(255),
            TextInput::make('default_warehouse_region_name')
                ->label(__('stock::common.default_warehouse_region_name'))
                ->required()
                ->maxLength(255),
        ];
    }

    public function getDefaults(): array
    {
        return [
            'default_warehouse_name' => 'Raktár',
            'default_warehouse_region_name' => 'Régió',
        ];
    }

    public function getFormFields(): array
    {
        return [
            'default_warehouse_name',
            'default_warehouse_region_name',
        ];
    }
}
