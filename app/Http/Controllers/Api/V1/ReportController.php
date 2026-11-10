<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSalesReport;
use App\Models\Report;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    private const JSON = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * Izveštaj o prodaji.
     *
     * Posao ide u red. Odgovor 202 stiže odmah, a status se prati na adresi
     * iz zaglavlja Location.
     */
    public function store(): JsonResponse
    {
        $report = Report::create(['type' => 'sales']);
        GenerateSalesReport::dispatch($report);

        return response()->json(['data' => self::present($report)], 202, [
            'Location' => route('api.v1.reports.show', $report),
        ], self::JSON);
    }

    /**
     * Status izveštaja.
     *
     * Dok se izveštaj pravi, odgovor ima zaglavlje Retry-After; kada je
     * gotov, sadrži i rezultat.
     */
    public function show(Report $report): JsonResponse
    {
        $headers = $report->status === 'pending' ? ['Retry-After' => '2'] : [];

        return response()->json(['data' => self::present($report)], 200, $headers, self::JSON);
    }

    private static function present(Report $report): array
    {
        return [
            'id' => $report->id,
            'type' => $report->type,
            'status' => $report->status,
            'result' => $report->result,
            'created_at' => $report->created_at?->toAtomString(),
            'links' => ['self' => route('api.v1.reports.show', $report)],
        ];
    }
}
