<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = Auth::id();
        return [
            'name' => 'required|string|max:50',
            'email' => 'required|string|email|unique:users,email,'.$id,
            'phone' => 'required|string|phone:AUTO|unique:users,phone,'.$id,
            'favicon' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:6120'
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.phone' => 'Invalid Phone Number'
        ];
    }
}
