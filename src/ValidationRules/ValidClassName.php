<?php

declare(strict_types=1);

namespace JeroenG\Packager\ValidationRules;

use Illuminate\Contracts\Validation\Rule;

class ValidClassName implements Rule
{
    public string $pattern = '/^[a-zA-Z_-\x80-\xff][a-zA-Z0-9_-\x80-\xff]*$/';

    public function passes($attribute, $value): bool
    {
        return preg_match($this->pattern, $value) === 1;
    }

    public function message(): string
    {
        return 'The package :attribute must conform to a valid PHP classname.';
    }
}
