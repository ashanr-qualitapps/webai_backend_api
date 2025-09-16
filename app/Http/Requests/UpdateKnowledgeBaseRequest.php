<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateKnowledgeBaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string',
            'embedding' => 'required|array|size:1536',
            'embedding.*' => 'numeric',
            'updated_by' => 'nullable|uuid|exists:users,id'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'The content field is required.',
            'embedding.required' => 'The embedding field is required.',
            'embedding.array' => 'The embedding must be an array.',
            'embedding.size' => 'The embedding array must have exactly 1536 values.',
            'embedding.*.numeric' => 'Each embedding value must be a number.',
            'updated_by.uuid' => 'The updated_by must be a valid UUID.',
            'updated_by.exists' => 'The specified user does not exist.',
        ];
    }
}
