<?php
declare(strict_types=1);

namespace Turysta\Http;

final class Request
{
    /**
     * @param array<string,string> $headers klucze malymi literami
     * @param array<string,mixed> $query
     * @param array<string,mixed> $body
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $headers = [],
        public readonly array $query = [],
        public readonly array $body = [],
        public readonly string $remoteAddr = '0.0.0.0'
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        $body = [];
        $raw = file_get_contents('php://input');
        if ($raw !== false && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                throw new ApiException(400, 'invalid_json', 'Nieprawidłowy format danych (oczekiwano JSON).');
            }
            $body = $decoded;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = preg_replace('#^/api#', '', $path) ?: '/';

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $headers,
            $_GET,
            $body,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /** Adres klienta. Za zaufanym proxy (Traefik w sieci prywatnej) bierzemy ostatni wpis X-Forwarded-For. */
    public function ip(): string
    {
        $isPrivate = filter_var(
            $this->remoteAddr,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
        $forwarded = $this->header('x-forwarded-for');
        if ($isPrivate && $forwarded) {
            $parts = array_map('trim', explode(',', $forwarded));
            $last = end($parts);
            if (filter_var($last, FILTER_VALIDATE_IP)) {
                return $last;
            }
        }
        return $this->remoteAddr;
    }

    public function userAgent(): string
    {
        return substr($this->header('user-agent') ?? '', 0, 255);
    }

    public function input(string $key): string
    {
        $value = $this->body[$key] ?? '';
        return is_string($value) ? $value : '';
    }
}
