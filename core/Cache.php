<?php

namespace Core;

class Cache
{
    private string $cachePath;
    private bool $enabled;

    public function __construct(array $config)
    {
        $this->cachePath = rtrim($config['cache']['path'], '/\\') . DIRECTORY_SEPARATOR;
        $this->enabled = $config['cache']['enabled'] ?? true;

        if ($this->enabled && !is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    /**
     * Obtém dados do cache.
     */
    public function get(string $key): mixed
    {
        if (!$this->enabled) {
            return null;
        }

        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);

        if ($data === null || !isset($data['expires'], $data['value'])) {
            return null;
        }

        // Verifica expiração
        if (time() > $data['expires']) {
            @unlink($file);
            return null;
        }

        return $data['value'];
    }

    /**
     * Armazena dados no cache.
     */
    public function set(string $key, mixed $value, int $ttl = 60): void
    {
        if (!$this->enabled) {
            return;
        }

        $file = $this->getFilePath($key);

        $data = [
            'expires' => time() + $ttl,
            'value'   => $value,
        ];

        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Verifica se existe cache válido.
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Remove um item do cache.
     */
    public function delete(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Limpa todo o cache.
     */
    public function clear(): void
    {
        $files = glob($this->cachePath . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Obtém ou seta cache (helper).
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    private function getFilePath(string $key): string
    {
        return $this->cachePath . md5($key) . '.cache';
    }
}
