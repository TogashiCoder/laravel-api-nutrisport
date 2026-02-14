<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgentProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
            'prices' => ['required', 'array'],
            'prices.fr' => ['required', 'numeric', 'min:0'],
            'prices.it' => ['required', 'numeric', 'min:0'],
            'prices.be' => ['required', 'numeric', 'min:0'],
        ];
    }
}
