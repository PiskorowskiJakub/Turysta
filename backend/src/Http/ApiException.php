<?php
declare(strict_types=1);

namespace Turysta\Http;

use RuntimeException;

final class ApiException extends RuntimeException
{
    /** @param array<string,string> $fields */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $fields = []
    ) {
        parent::__construct($message);
    }

    /** @return array{error:array<string,mixed>} */
    public function toBody(): array
    {
        $error = ['code' => $this->errorCode, 'message' => $this->getMessage()];
        if ($this->fields) {
            $error['fields'] = $this->fields;
        }
        return ['error' => $error];
    }
}
