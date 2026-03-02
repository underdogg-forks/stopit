<?php

namespace Stopit\src\Providers\src\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Enums\Severity;

class StoreExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $severityValues = implode(',', array_map(fn ($case) => $case->value, Severity::cases()));

        return [
            'exception_class' => ['required', 'string', 'max:500'],
            'message'         => ['required', 'string'],
            'file'            => ['nullable', 'string', 'max:1000'],
            'line'            => ['nullable', 'integer', 'min:0'],
            'stack_trace'     => ['nullable', 'string'],
            'request_method'  => ['nullable', 'string', 'max:10'],
            'request_url'     => ['nullable', 'string', 'max:2048'],
            'headers'         => ['nullable', 'string'],
            'user_agent'      => ['nullable', 'string', 'max:1000'],
            'ip_address'      => ['nullable', 'string', 'max:45'],
            'user_id'         => ['nullable', 'string', 'max:255'],
            'context'         => ['nullable', 'array'],
            'severity'        => ['nullable', 'string', 'in:' . $severityValues],
        ];
    }
}
