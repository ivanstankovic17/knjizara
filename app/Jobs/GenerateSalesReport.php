<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Prodaja po kategorijama: broj prodatih primeraka i prihod.
 */
class GenerateSalesReport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [5, 30];

    /** Posao koji radi duže od 2 minuta smatra se zaglavljenim. */
    public int $timeout = 120;

    public function __construct(public Report $report) {}

    public function handle(): void
    {
        $rows = DB::table('order_items')
            ->join('books', 'books.id', '=', 'order_items.book_id')
            ->join('categories', 'categories.id', '=', 'books.category_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->select('categories.name as category')
            ->selectRaw('sum(order_items.quantity) as copies')
            ->selectRaw('sum(order_items.quantity * order_items.unit_price)'
                .' as revenue')
            ->get()
            // MySQL vraća sum() kao decimalni broj, a PDO ga daje kao string
            // ("2"); SQLite vraća ceo broj. U JSON-u izveštaja treba broj.
            ->map(fn (object $row) => [
                'category' => $row->category,
                'copies' => (int) $row->copies,
                'revenue' => (int) $row->revenue,
            ]);

        $this->report->update([
            'status' => 'done',
            'result' => [
                'categories' => $rows,
                'total' => $rows->sum('revenue'),
                'generated_at' => now()->toAtomString(),
            ],
        ]);
    }

    /**
     * Poziva se kada propadnu svi pokušaji.
     */
    public function failed(?Throwable $e): void
    {
        $this->report->update(['status' => 'failed']);
    }
}
