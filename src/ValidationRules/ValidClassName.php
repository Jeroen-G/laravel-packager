<?php

declare(strict_types=1);

namespace JeroenG\Packager\ValidationRules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidClassName implements ValidationRule
{
    public string $pattern = '/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff-]*$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || preg_match($this->pattern, $value) !== 1) {
            $fail('The package :attribute must conform to a valid PHP classname.');
        }
    }
}
