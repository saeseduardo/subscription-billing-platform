<?php

namespace App\Payments;

final readonly class PaymentResult
{
    private function __construct(
        public bool $successful,
        public ?string $transactionId = null,
        public ?string $failureReason = null,
    ) {
    }

    public static function success(string $transactionId): self
    {
        return new self(true, $transactionId);
    }

    public static function failure(string $reason): self
    {
        return new self(false, null, $reason);
    }
}
