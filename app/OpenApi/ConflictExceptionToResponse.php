<?php

namespace App\OpenApi;

use App\Exceptions\ConflictException;
use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

/**
 * Sopstveni izuzeci sukoba (OutOfStockException, ...) postaju 409, kao
 * u App\Http\Problem. Telo odgovora popunjava ProblemResponses.
 */
class ConflictExceptionToResponse extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(ConflictException::class);
    }

    public function toResponse(Type $type): Response
    {
        return Response::make(409)->setDescription('Sukob sa stanjem podataka');
    }
}
