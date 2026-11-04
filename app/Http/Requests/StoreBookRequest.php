<?php

namespace App\Http\Requests;

use App\Rules\Isbn13;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    /**
     * Privremeno: dok ne uvedemo prijavu (glava 11) i uloge (glava 12),
     * katalog sme da se menja samo na razvojnom računaru.
     */
    public function authorize(): bool
    {
        return app()->isLocal();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => ['required', new Isbn13, Rule::unique('books', 'isbn')],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'between:1,1000000'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'published_year' => [
                'nullable', 'integer', 'between:1450,'.now()->year],
            'category_id' => [
                'required', 'integer', Rule::exists('categories', 'id')],
            'author_ids' => ['required', 'array', 'min:1', 'max:10'],
            'author_ids.*' => [
                'integer', 'distinct', Rule::exists('authors', 'id')],
        ];
    }

    /**
     * Razmaci oko naslova i crtice u ISBN-u se uklanjaju pre provere.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'title' => is_string($this->title) ? trim($this->title) : $this->title,
            'isbn' => is_string($this->isbn)
            ? str_replace(['-', ' '], '', $this->isbn) : $this->isbn,
        ], fn ($v) => $v !== null));
    }
}
