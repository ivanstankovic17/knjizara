<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Vrednost je spisak odvojen zarezima, a svaki član je sa spiska
 * dozvoljenih, uz minus ispred ako je $signed ("-price,title").
 */
class CommaList implements ValidationRule
{
    /**
     * @param  list<string>  $allowed
     */
    public function __construct(
        private array $allowed,
        private bool $signed = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (explode(',', (string) $value) as $item) {
            $name = $this->signed ? ltrim($item, '-') : $item;
            if (! in_array($name, $this->allowed, true)) {
                $fail("Vrednost „{$item}” nije dozvoljena. Dozvoljeno: "
                    .implode(', ', $this->allowed).'.');
            }
        }
    }
}
