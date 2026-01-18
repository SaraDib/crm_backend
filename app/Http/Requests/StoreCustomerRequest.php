<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We will handle authorization in the controller/middleware
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:individual,company',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'company_name' => 'required_if:type,company|nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'status' => 'required|in:lead,prospect,customer,inactive,blacklisted',
            'category_id' => 'nullable|exists:customer_categories,id',
        ];
    }
}
