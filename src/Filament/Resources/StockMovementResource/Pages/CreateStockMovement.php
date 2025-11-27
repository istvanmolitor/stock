<?php
namespace Molitor\Stock\Filament\Resources\StockMovementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Molitor\Stock\Filament\Resources\StockMovementResource;
use Molitor\Stock\Models\StockMovement;
use Molitor\Stock\Filament\Resources\StockMovementResource\Pages\Concerns\HandlesStockMovementExecution;

class CreateStockMovement extends CreateRecord
{
    use HandlesStockMovementExecution;

    protected static string $resource = StockMovementResource::class;

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            Actions\Action::make('save_and_close')
                ->label('Mentés és lezárás')
                ->color('success')
                ->action(fn () => $this->saveAndClose()),
        ];
    }

    protected function saveAndClose(): void
    {
        $this->create();

        /** @var StockMovement $stockMovement */
        $stockMovement = $this->record;

        $this->executeStockMovementAndNotify($stockMovement);

        $this->redirect($this->getRedirectUrl());
    }
}
