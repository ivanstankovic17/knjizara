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
     * POST /api/v1/reports/sales: posao ide u red, odgovor stiže odmah.
     */
    public function store(): JsonResponse
    {
        abort_unless(app()->isLocal(), 403,
            'Izveštaji su za sada dostupni samo lokalno.');

        $report = Report::create(['type' => 'sales']);
        GenerateSalesReport::dispatch($report);

        return response()->json(['data' => self::present($report)], 202, [
            'Location' => route('api.v1.reports.show', $report),
        ], self::JSON);
    }

    /**
     * GET /api/v1/reports/{report}: status, a kada je gotov i rezultat.
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
