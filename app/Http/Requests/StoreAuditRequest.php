<?php

namespace App\Http\Requests;

use App\Rules\SafeUrl;
use App\Support\UrlNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Lets visitors type "example.com" without the scheme.
     */
    protected function prepareForValidation(): void
    {
        if (filled($this->input('url'))) {
            $this->merge(['url' => UrlNormalizer::normalize((string) $this->input('url'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', 'url:http,https', new SafeUrl],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Escribe la dirección del sitio que quieres auditar.',
            'url.max' => 'La dirección es demasiado larga.',
            'url.url' => 'Escribe una dirección web válida, por ejemplo https://ejemplo.com.',
        ];
    }
}
