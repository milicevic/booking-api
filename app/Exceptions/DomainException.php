<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Poslovno pravilo koje servis ne može da ispuni. Renderuje se kao JSON sa prevedenom porukom,
 * pa servisi ne zavise od HTTP sloja (abort()).
 */
class DomainException extends RuntimeException implements ShouldntReport
{
    public function __construct(
        string $message,
        private readonly int $status = 422,
        private readonly ?string $errorCode = null,
    ) {
        parent::__construct($message);
    }

    public static function unprocessable(string $message): self
    {
        return new self($message, 422);
    }

    public static function conflict(string $message): self
    {
        return new self($message, 409);
    }

    public static function notFound(string $message): self
    {
        return new self($message, 404);
    }

    public static function forbidden(string $message, ?string $errorCode = null): self
    {
        return new self($message, 403, $errorCode);
    }

    public static function unauthorized(string $message): self
    {
        return new self($message, 401);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function errorCode(): ?string
    {
        return $this->errorCode;
    }

    public function render(): JsonResponse
    {
        $body = ['message' => $this->getMessage()];

        if ($this->errorCode !== null) {
            $body['code'] = $this->errorCode;
        }

        return response()->json($body, $this->status);
    }
}
