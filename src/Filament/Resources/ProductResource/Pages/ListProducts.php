<?php

namespace Molitor\Stock\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Molitor\Stock\Filament\Resources\ProductResource;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getBreadcrumb(): string
    {
        return __('stock::common.list');
    }

    public function getTitle(): string
    {
        return __('stock::stock.title');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}

