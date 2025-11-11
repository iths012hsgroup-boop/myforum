<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopikRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /**
     * Terima input lama "topik" dan map ke "topik_title"
     * (kalau masih ada view/JS yang kirim "topik", tidak error lagi).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('topik') && !$this->has('topik_title')) {
            $this->merge(['topik_title' => $this->input('topik')]);
        }
    }

    public function rules(): array
    {
        return [
            'topik_title' => ['required','string','max:255'],
            'status'      => ['required', Rule::in([0,1])],
        ];
    }

    public function attributes(): array
    {
        return [
            'topik_title' => 'Topik',
            'status'      => 'Status',
        ];
    }
}
