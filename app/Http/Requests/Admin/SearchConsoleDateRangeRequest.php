<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SearchConsoleDateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'start_row' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }

    public function fromDate(): ?string
    {
        $value = $this->query('from', $this->input('from'));

        return filled($value) ? (string) $value : null;
    }

    public function toDate(): ?string
    {
        $value = $this->query('to', $this->input('to'));

        return filled($value) ? (string) $value : null;
    }

    public function limit(int $default = 25): int
    {
        $value = $this->query('limit', $this->input('limit'));

        return filled($value) ? (int) $value : $default;
    }

    public function startRow(): int
    {
        return max(0, (int) $this->query('start_row', $this->input('start_row', 0)));
    }
}
