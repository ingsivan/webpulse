<?php

namespace App\Http\Requests;

use App\Models\Site;
use App\Rules\SafeUrl;
use App\Support\UrlNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSiteRequest extends FormRequest
{
    public const MAX_SITES_PER_USER = 10;

    public function authorize(): bool
    {
        return $this->user()->can('create', Site::class);
    }

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
            'name' => ['nullable', 'string', 'max:80'],
            'url' => [
                'required', 'string', 'max:2048', 'url:http,https', new SafeUrl,
                Rule::unique('sites', 'url')->where('user_id', $this->user()->id),
            ],
            'check_interval_minutes' => ['required', 'integer', Rule::in([15, 30, 60, 360, 1440])],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->user()->sites()->count() >= self::MAX_SITES_PER_USER) {
                    $validator->errors()->add('url', 'Puedes monitorear hasta '.self::MAX_SITES_PER_USER.' sitios.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Escribe la dirección del sitio.',
            'url.url' => 'Escribe una dirección web válida, por ejemplo https://ejemplo.com.',
            'url.unique' => 'Ya estás monitoreando este sitio.',
            'check_interval_minutes.in' => 'Elige una frecuencia de la lista.',
        ];
    }
}
