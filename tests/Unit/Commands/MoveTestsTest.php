<?php

declare(strict_types=1);

namespace JeroenG\Packager\Tests\Unit\Commands;

use JeroenG\Packager\Tests\CommandTestCase;

final class MoveTestsTest extends CommandTestCase
{
    public function test_it_moves_the_tests_of_a_single_package(): void
    {
        mkdir($this->packagePath('acme', 'rocket').'/tests', 0777, true);
        file_put_contents($this->packagePath('acme', 'rocket').'/tests/RocketTest.php', '<?php');

        $this->command('packager:tests', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsOutputToContain('Moving tests for the package: acme/rocket')
            ->assertExitCode(0);

        $this->assertFileExists($this->basePath.'/tests/packages/acme/rocket/RocketTest.php');
    }

    public function test_it_reports_a_package_without_tests(): void
    {
        $this->command('packager:tests', ['vendor' => 'acme', 'name' => 'rocket'])
            ->expectsOutputToContain('No tests found for: acme/rocket')
            ->assertExitCode(0);
    }

    public function test_it_moves_the_tests_of_all_autoloaded_packages(): void
    {
        $this->writeComposerJson(['autoload' => ['psr-4' => [
            'App\\' => 'app/',
            'Tests\\' => 'tests/',
            'Acme\\Rocket\\' => 'packages/acme/rocket/src',
            'Acme\\Anvil\\' => 'packages/acme/anvil/src',
        ]]]);
        mkdir($this->packagePath('acme', 'rocket').'/tests', 0777, true);
        file_put_contents($this->packagePath('acme', 'rocket').'/tests/RocketTest.php', '<?php');

        $this->command('packager:tests')
            ->expectsOutputToContain('Moving tests for the package: Acme\Rocket')
            ->expectsOutputToContain('No tests found for: Acme\Anvil')
            ->doesntExpectOutputToContain('App')
            ->assertExitCode(0);

        $this->assertFileExists($this->basePath.'/tests/packages/Acme/Rocket/RocketTest.php');
    }
}
