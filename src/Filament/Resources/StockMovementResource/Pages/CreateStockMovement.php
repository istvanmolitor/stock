<?php
namespace Molitor\Stock\Filament\Resources\StockMovementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Molitor\Stock\Filament\Resources\StockMovementResource;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Molitor\Stock\Models\StockMovement;
use Molitor\Stock\Services\StockMovementService;

class CreateStockMovement extends CreateRecord
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
        if(!$this->record) {
            return;
        }

        /** @var StockMovement $stockMovement */
        $stockMovement = $this->record;

        DB::transaction(function () use ($stockMovement) {
            /** @var StockMovementService $stockService */
            $stockService = app(StockMovementService::class);
            $errors = $stockService->execute($stockMovement);
            if(count($errors)) {

                $shortages = [];
                foreach ($errors as $error) {
                    $shortages[] = "- " . $error['product_name'] . " (" . $error['warehouse_region_name'] . "): szükséges mennyiség: " . $error['required_quantity'] . ", elérhető mennyiség: " . $error['available_quantity'];
                }

                Notification::make()
                    ->title('Nincs elegendő készlet')
                    ->body("Az alábbi tételeknél nincs elegendő készlet, a lezárás nem történt meg:\n" . implode("\n", $shortages))
                    ->danger()
                    ->send();

                throw new Halt();
            }
            else {
                Notification::make()
                    ->title('OK')
                    ->success()
                    ->send();
            }
        });

        $this->redirect($this->getRedirectUrl());
    }
}
