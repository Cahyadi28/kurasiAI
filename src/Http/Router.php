<?php

declare(strict_types=1);

namespace KurasiAI\Http;

/**
 * Mencocokkan metode dan alamat permintaan dengan daftar rute.
 * Bagian {nama} di alamat menerima angka saja (paling banyak 9 digit), misalnya /drafts/{id} cocok dengan /drafts/12.
 */
final class Router
{
    /** @var list<array{method: string, pattern: string, handler: callable(Request, array<string, int>): Response}> */
    private array $routes = [];

    /**
     * @param callable(Request, array<string, int>): Response $handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * @param callable(Request, array<string, int>): Response $handler
     */
    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(Request $request): Response
    {
        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $request->path, $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $request->method) {
                // Alamatnya cocok, metodenya tidak. Dicatat untuk jawaban 405.
                $allowed[] = $route['method'];
                continue;
            }

            $params = [];

            foreach ($matches as $name => $value) {
                if (is_string($name)) {
                    $params[$name] = (int) $value;
                }
            }

            return ($route['handler'])($request, $params);
        }

        if ($allowed !== []) {
            throw new HttpException(405, 'Metode ' . $request->method . ' tidak diizinkan untuk alamat ini.', [
                'Allow' => implode(', ', array_unique($allowed)),
            ]);
        }

        throw new HttpException(404, 'Halaman tidak ditemukan.');
    }

    /**
     * @param callable(Request, array<string, int>): Response $handler
     */
    private function add(string $method, string $path, callable $handler): void
    {
        $this->routes[] = ['method' => $method, 'pattern' => $this->compile($path), 'handler' => $handler];
    }

    /**
     * '/drafts/{id}/submit' menjadi '#^/drafts/(?P<id>[0-9]{1,9})/submit$#'.
     */
    private function compile(string $path): string
    {
        $parts = preg_split('/(\{[a-z]+\})/', $path, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $regex = '';

        foreach ($parts as $part) {
            $regex .= preg_match('/^\{([a-z]+)\}$/', $part, $name) === 1
                ? '(?P<' . $name[1] . '>[0-9]{1,9})'
                : preg_quote($part, '#');
        }

        return '#^' . $regex . '$#';
    }
}
