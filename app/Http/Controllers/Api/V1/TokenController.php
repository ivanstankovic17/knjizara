<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CheckCredentials;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Tokeni za klijente koji nisu pregledač: mobilna aplikacija, skript,
 * drugi server, Bruno (Sanctum API tokeni).
 */
class TokenController extends Controller
{
    /**
     * POST /api/v1/tokens: token se vidi samo u ovom odgovoru.
     */
    public function store(Request $request, CheckCredentials $credentials): JsonResponse
    {
        $request->validate(['device_name' => ['required', 'string', 'max:100']]);
        $user = $credentials->handle($request);

        $token = $user->createToken(
            $request->string('device_name'), ['*'], now()->addDays(30));

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at->toAtomString(),
        ], 201);
    }

    /**
     * DELETE /api/v1/tokens/current: odjava ovog klijenta.
     */
    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
