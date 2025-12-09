<?php
namespace App\Lib;

use Psr\SimpleCache\CacheInterface;

class BlockFileCache implements CacheInterface
{
    protected string $cacheDir;
    protected int $blockSize;

    public function __construct(string $cacheDir, int $blockSize = 100)
    {
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->blockSize = $blockSize;

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
        return $data !== false ? unserialize($data) : $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $file = $this->getPath($key);
        return file_put_contents($file, serialize($value)) !== false;
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
