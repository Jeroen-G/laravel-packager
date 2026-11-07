<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnit;

abstract class UnitTestCase extends PHPUnit
{
    use MockeryPHPUnitIntegration;
}
