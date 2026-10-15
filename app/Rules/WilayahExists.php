<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WilayahExists implements ValidationRule
{
    public function __construct(private string $model) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_scalar($value) || !$this->model::query()->whereKey($value)->exists()) {
            $fail('Pilihan tidak ditemukan atau berada di luar wilayah penugasan Anda.');
        }
    }
}
