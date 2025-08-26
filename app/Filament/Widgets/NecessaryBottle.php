<?php

namespace App\Filament\Widgets;

use App\Models\BottlePosition;
use App\Models\Herb;
use App\Models\Order;
use App\Models\Variant;
use Carbon\Carbon;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class NecessaryBottle extends Widget implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected string $view = 'filament.widgets.necessary-bottle';
    public Carbon $maxDate;
    public int $extrapolateMaxSize;
    public int $extrapolateMonths;
    public Collection $positions;
    public Collection $messages;
    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        $this->maxDate = Carbon::now()->subDays(7)->startOfDay();
        $this->extrapolateMaxSize = 200;
        $this->extrapolateMonths = 3;
        $this->positions = collect();
        $this->messages = collect();
    }

    protected function getViewData(): array
    {
        $positions = Order::with('positions')
            ->whereNotNull('paid_at')
            ->whereNull('shipped_at')
            ->where('created_at', '>=', $this->maxDate)
            ->whereHas('positions')
            ->get()
            ->flatMap(fn(Order $order) => $order->positions);

        $filteredPositions = $positions->groupBy(fn(BottlePosition $p) => $p->variant_id);

        //->map(fn(OrderPosition $orderPosition) => [
        //    'variant_id' => $orderPosition->variant_id,
        //    'count' => $orderPosition->variant->size <= $this->extrapolateMaxSize ? intval($this->extrapolateMonths * $orderPosition->variant->average_monthly_sales - $orderPosition->variant->stock + $orderPosition->quantity) : $orderPosition->quantity,
        //]);


        $positionsGrouped = collect();
        foreach ($positions as $position) {
            if ($positionsGrouped->contains($position['variant_id'])) {
                $positionsGrouped[$position['variant_id']] += $position['count'];
            } else {
                $positionsGrouped[$position['variant_id']] = $position['count'];
            }
        }

        $herbsNeeded = collect();
        foreach ($positionsGrouped as $variant_id => $quantity) {
            $variant = Variant::find($variant_id);
            $herbs = $variant->herbsNeededFor($quantity);
            foreach ($herbs as $herb_id => $amount) {
                if ($herbsNeeded->contains($herb_id)) {
                    $herbsNeeded[$herb_id] += $amount;
                } else {
                    $herbsNeeded[$herb_id] = $amount;
                }
            }
        }

        $this->messages = collect();
        foreach ($herbsNeeded as $herb_id => $amount) {
            $herb = Herb::find($herb_id);
            if ($herb->current_stock < $amount) {
                $this->messages->push("Nicht ausreichend $herb->name auf Lager. ({$herb->current_stock}g/{$amount}g");
            }
        }

        $this->positions = $positionsGrouped;

        return [
            'sizes' => Variant::pluck('size')->unique()->sort(),
        ];
    }
}
