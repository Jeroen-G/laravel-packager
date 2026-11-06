<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\ValidationRules;

use JeroenG\Packager\Tests\UnitTestCase;
use JeroenG\Packager\ValidationRules\ValidClassName;
use PHPUnit\Framework\Attributes\DataProvider;

final class ValidClassNameTest extends UnitTestCase
{
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
        $this->assertTrue((new ValidClassName())->passes('name', $name));
    }

    #[DataProvider('invalidNames')]
    public function test_it_rejects_invalid_class_names(string $name): void
    {
        $this->assertFalse((new ValidClassName())->passes('name', $name));
    }

    public function test_it_has_a_message_with_the_attribute_placeholder(): void
    {
        $this->assertSame(
            'The package :attribute must conform to a valid PHP classname.',
            (new ValidClassName())->message()
        );
    }
}
