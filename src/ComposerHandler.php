<?php

namespace JeroenG\Packager;

use Closure;
use RuntimeException;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;

trait ComposerHandler
{
    use ProcessRunner;

    protected function removeComposerRepository($name)
    {
        return self::modifyComposerJson(function (array $composer) use ($name){
            unset($composer['repositories'][$name]);
            return $composer;
        }, base_path());
    }

    /**
     * Determines the path to Composer executable
     * @todo Might not work on Windows
     * @return string
     */
    protected static function getComposerExecutable(): string
    {
        return trim(shell_exec('which composer')) ?: 'composer';
    }

    protected function removePackage(string $packageName): array
    {
        return self::runComposerCommand([
            'remove',
            strtolower($packageName),
            '--no-progress',
        ]);
    }

    protected function requirePackage(string $packageName, string $version = null, bool $prefer_source = true): bool
    {
        $package = strtolower($packageName);
        if ($version) {
            $package .= ':'.$version;
        }
        $result = self::runComposerCommand([
            'require',
            $package,
            '--prefer-'.($prefer_source ? 'source' : 'dist'),
            '--prefer-stable',
            '--no-suggest',
            '--no-progress',
        ]);
        if (!$result['success']) {
            return false;
        }
        return true;
    }

    protected function addComposerRepository(string $name, string $type = 'path', string $url = null)
    {
        $params = [
            'type' => $type,
            'url'  => $url
        ];
        return self::modifyComposerJson(function (array $composer) use ($params, $name){
            $composer['repositories'][$name] = $params;
            return $composer;
        }, base_path());
    }

    /**
     * Find the package's path in Composer's vendor folder
     * @param  string  $packageName
     * @return string
     * @throws RuntimeException
     */
    protected function findInstalledPath(string $packageName): string
    {
        $packageName = strtolower($packageName);
        $result = self::runComposerCommand([
            'info',
            $packageName,
            '--path']);
        if ($result['success'] &&preg_match('{'.$packageName.' (.*)$}m', $result['output'], $match)) {
            return trim($match[1]);
        }
        throw new DirectoryNotFoundException('Package ' . $packageName.' not found in vendor folder');
    }

    /**
     * @param  string  $path
     * @return array
     */
    protected static function getComposerJsonArray(string $path): array
    {
        return json_decode(file_get_contents($path), true);
    }

    protected static function modifyComposerJson(Closure $callback, string $composer_path)
    {
        $composer_path = rtrim($composer_path, '/');
        if (!preg_match('/composer\.json$/', $composer_path)){
            $composer_path .= '/composer.json';
        }
        $original = self::getComposerJsonArray($composer_path);
        $modified = $callback($original);
        return file_put_contents($composer_path, json_encode($modified, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param  array  $command
     * @param  string|null  $cwd
     * @return array
     */
    protected static function runComposerCommand(array $command, string $cwd = null): array
    {
        array_unshift($command, 'php', '-n', self::getComposerExecutable());
        return self::runProcess($command, $cwd);
    }
}
