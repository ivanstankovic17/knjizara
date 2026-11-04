<?php

namespace App\Http\Requests;

use App\Rules\Isbn13;
use Illuminate\Validation\Rule;

/**
 * PATCH: sva polja su neobavezna, ali poslato polje mora biti ispravno.
 */
class UpdateBookRequest extends StoreBookRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['isbn'] = [
            'required', new Isbn13,
            Rule::unique('books', 'isbn')->ignore($this->route('book')),
        ];

        foreach (['title', 'isbn', 'price', 'category_id', 'author_ids'] as $field) {
            array_unshift($rules[$field], 'sometimes');
        }

        return $rules;
    }
}
