<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'payment_method' => ['required', 'string', 'in:bank_transfer'],
        ];

        if ($this->has('address_id')) {
            $rules['address_id'] = ['required', 'integer', \Illuminate\Validation\Rule::exists('addresses', 'id')->where('user_id', $this->user()->id)];
        } else {
            $rules['address'] = ['required', 'array'];
            $rules['address.full_name'] = ['required', 'string', 'max:255'];
            $rules['address.address_line'] = ['required', 'string', 'max:500'];
            $rules['address.city'] = ['required', 'string', 'max:100'];
            $rules['address.country'] = ['required', 'string', 'max:100'];
        }

        return $rules;
    }
}
