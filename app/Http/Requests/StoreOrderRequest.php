<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.book_id' => ['required', 'integer', 'distinct', Rule::exists('books', 'id')],
            'items.*.quantity' => ['required', 'integer', 'between:1,10'],
        ];
    }

    /**
     * Korpa u obliku koji očekuje PlaceOrder: [id knjige => količina].
     *
     * @return array<int, int>
     */
    public function cart(): array
    {
        return collect($this->validated('items'))
            ->mapWithKeys(fn (array $item) => [$item['book_id'] => $item['quantity']])
            ->all();
    }
}
