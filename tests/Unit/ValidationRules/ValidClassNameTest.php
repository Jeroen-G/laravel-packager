<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\ValidationRules;

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Translation\PotentiallyTranslatedString;
use JeroenG\Packager\Tests\UnitTestCase;
use JeroenG\Packager\ValidationRules\ValidClassName;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;

final class ValidClassNameTest extends UnitTestCase
{
    /**
     * @return array<string, list<string>>
     */
    public static function validNames(): array
    {
        return [
            'simple' => ['Foo'],
            'lowercase' => ['foo'],
            'underscore start' => ['_foo'],
            'with digits' => ['Foo123'],
            'with hyphen' => ['my-package'],
            'multibyte' => ['Café'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function invalidNames(): array
    {
        return [
            'empty' => [''],
            'digit start' => ['1234-Invalid'],
            'hyphen start' => ['-foo'],
            'space' => ['foo bar'],
            'dot' => ['foo.bar'],
            'backtick' => ['foo`bar'],
            'curly brace' => ['foo{bar'],
            'backslash' => ['Foo\\Bar'],
        ];
    }

    #[DataProvider('validNames')]
    public function test_it_accepts_valid_class_names(string $name): void
    {
        $this->assertNull($this->validate($name));
    }

    #[DataProvider('invalidNames')]
    public function test_it_rejects_invalid_class_names(string $name): void
    {
        $this->assertSame('The package :attribute must conform to a valid PHP classname.', $this->validate($name));
    }

    public function test_it_rejects_non_string_values(): void
    {
        $this->assertNotNull($this->validate(['Foo']));
        $this->assertNotNull($this->validate(null));
    }

    private function validate(mixed $value): ?string
    {
        $message = null;

        $fail = function (string $error, ?string $translate = null) use (&$message): PotentiallyTranslatedString {
            $message = $error;

            return new PotentiallyTranslatedString($error, Mockery::mock(Translator::class));
        };

        (new ValidClassName())->validate('name', $value, $fail);

        return $message;
    }
}
