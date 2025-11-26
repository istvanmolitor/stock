<?php
namespace Molitor\Stock\Filament\Resources\StockMovementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Molitor\Stock\Filament\Resources\StockMovementResource;
use Illuminate\Support\Facades\DB;
use Molitor\Stock\Enums\StockMovementType;
use Molitor\Stock\Models\Stock;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

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
        DB::transaction(function () {
            $data = $this->form->getState();
            $data['closed_at'] = now();

            $this->record = $this->handleRecordCreation($data);

            $this->form->model($this->record)->saveRelationships();

            if ($this->record->type === StockMovementType::In) {
                $this->record->load('stockMovementItems');

                foreach ($this->record->stockMovementItems as $item) {
                    if (empty($item->warehouse_region_id) || empty($item->product_id)) {
                        continue;
                    }

                    $stock = Stock::where('warehouse_region_id', $item->warehouse_region_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($stock) {
                        $stock->quantity = (float) $stock->quantity + (float) $item->quantity;
                        $stock->save();
                    } else {
                        Stock::create([
                            'warehouse_region_id' => $item->warehouse_region_id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                        ]);
                    }
                }
            } elseif ($this->record->type === StockMovementType::Out) {
                $this->record->load(['stockMovementItems.product', 'stockMovementItems.warehouseRegion']);

                $shortages = [];

                foreach ($this->record->stockMovementItems as $item) {
                    if (empty($item->warehouse_region_id) || empty($item->product_id)) {
                        continue;
                    }

                    $stock = Stock::where('warehouse_region_id', $item->warehouse_region_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    $available = $stock ? (float) $stock->quantity : 0.0;
                    $requested = (float) $item->quantity;

                    if ($available < $requested) {
                        $missing = $requested - $available;
                        // Avoid casting Optional to string; cast the underlying model or null directly
                        $productName = (string) ($item->product ?? '');
                        $regionName = (string) optional($item->warehouseRegion)->name;
                        $shortages[] = "- {$productName} @ {$regionName}: hiányzik " . rtrim(rtrim(number_format($missing, 4, '.', ''), '0'), '.') ;
                    }
                }

                if (! empty($shortages)) {
                    Notification::make()
                        ->title('Nincs elegendő készlet')
                        ->body("Az alábbi tételeknél nincs elegendő készlet, a lezárás nem történt meg:\n" . implode("\n", $shortages))
                        ->danger()
                        ->send();

                    // Megállítjuk az akciót és visszagörgetünk mindent (beleértve a closed_at mentését is)
                    throw new Halt();
                }

                // Minden tételhez elegendő készlet van: csökkentjük
                foreach ($this->record->stockMovementItems as $item) {
                    if (empty($item->warehouse_region_id) || empty($item->product_id)) {
                        continue;
                    }

                    $stock = Stock::where('warehouse_region_id', $item->warehouse_region_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    $available = $stock ? (float) $stock->quantity : 0.0;
                    $requested = (float) $item->quantity;

                    // Itt már biztosan elegendő, biztonsági okból max(0) nem engedjük negatívra
                    if ($stock) {
                        $stock->quantity = max(0.0, $available - $requested);
                        $stock->save();
                    }
                }
            }
        });

        $this->redirect($this->getRedirectUrl());
    }
}
