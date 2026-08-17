<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly float $available,
        public readonly float $requested,
    ) {
        parent::__construct(
            "Insufficient stock for {$productName}. Available: {$available}, requested: {$requested}."
        );
    }
}
