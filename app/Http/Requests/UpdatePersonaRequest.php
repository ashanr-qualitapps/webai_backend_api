<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonaRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'title' => 'nullable|string|max:255',
            'profile_picture_url' => 'nullable|url|max:500',
            'ai_expertise_description' => 'nullable|string|max:5000',
            'associated_profile_snippet_id' => 'nullable|uuid|exists:snippets,id',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The persona name is required.',
            'name.max' => 'The persona name may not be greater than 255 characters.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'profile_picture_url.url' => 'The profile picture URL must be a valid URL.',
            'profile_picture_url.max' => 'The profile picture URL may not be greater than 500 characters.',
            'ai_expertise_description.max' => 'The AI expertise description may not be greater than 5000 characters.',
            'associated_profile_snippet_id.uuid' => 'The associated profile snippet ID must be a valid UUID.',
            'associated_profile_snippet_id.exists' => 'The selected snippet does not exist.',
            'is_active.boolean' => 'The is active field must be true or false.'
        ];
    }
}
