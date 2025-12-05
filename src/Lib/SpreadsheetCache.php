<?php
declare(strict_types=1);

namespace App\Lib;

use Psr\SimpleCache\CacheInterface;

class SpreadsheetCache implements CacheInterface
{
    protected string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    protected function getPath(string $key): string
    {
        return $this->cacheDir . md5($key) . '.cache';
    }

    public function get($key, $default = null): mixed
    {
        $file = $this->getPath($key);
        if (!file_exists($file)) {
            return $default;
        }
        $data = file_get_contents($file);
        $unserialized = unserialize($data);
        if ($unserialized === false) {
            return $default;
        }
        return $unserialized;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $file = $this->getPath($key);
        $data = serialize($value);
        $result = file_put_contents($file, $data) !== false;

        \Cake\Log\Log::info("PhpSpreadsheet cache wrote key {$key} to {$file}");

        return $result;
    }

    public function delete($key): bool
    {
        $file = $this->getPath($key);
        if (file_exists($file)) {
            unlink($file);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->get($key, $default);
        }
        return $results;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key): bool
    {
        return file_exists($this->getPath($key));
    }
}
