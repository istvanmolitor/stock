<?php
namespace Molitor\Stock\Filament\Resources\StockMovementResource\Pages\Concerns;

use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\DB;
use Molitor\Stock\Models\StockMovement;
use Molitor\Stock\Services\StockMovementService;

/**
 * Közös segéd a készletmozgás végrehajtásához Create/Edit oldalak számára.
 */
trait HandlesStockMovementExecution
{
    /**
     * Lefuttatja a készletmozgást tranzakcióban, értesít és hibánál Halt-ot dob.
     */
    protected function executeStockMovementAndNotify(StockMovement $stockMovement): void
    {
        DB::transaction(function () use ($stockMovement) {
            /** @var StockMovementService $stockService */
            $stockService = app(StockMovementService::class);
            $errors = $stockService->execute($stockMovement);

            if (count($errors)) {
                $lines = [];
                foreach ($errors as $error) {
                    $lines[] = $this->errorToString($error);
                }

                Notification::make()
                    ->title('Nincs elegendő készlet')
                    ->body("Az alábbi tételeknél nincs elegendő készlet, a lezárás nem történt meg:\n" . implode("\n", $lines))
                    ->danger()
                    ->send();

                throw new Halt();
            }
        });
    }

    protected function errorToString(array $error): string
    {
        $product = $error['product'] ?? null;
        $available = $error['currentQuantity'] ?? null;
        $productLabel = $product?->sku ?? $product?->name ?? 'Termék';
        $regionLabel = $region?->name ?? 'Régió';
        return "- {$productLabel} ({$regionLabel}): Elérhető mennyiség: {$available}";
    }
}
