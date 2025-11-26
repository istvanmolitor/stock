<?php
namespace Molitor\Stock\Filament\Resources\StockMovementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Molitor\Stock\Filament\Resources\StockMovementResource;

class EditStockMovement extends EditRecord
{
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
        $data = $this->form->getState();
        $data['closed_at'] = now();

        // Filament v3: handleRecordUpdate expects the record as first argument, then the data array
        $this->record = $this->handleRecordUpdate($this->record, $data);

        // Save relations like repeater items
        $this->form->model($this->record)->saveRelationships();

        $this->redirect($this->getRedirectUrl());
    }
}
