<?php

namespace App\Models;

class LoginAttempt
{
    public function __construct(
        public int $id,
        public ?string $email,
        public string $ipAddress,
        public bool $success,
        public string $attemptedAt,
        public ?string $blockedUntil
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            email: isset($data['email']) ? (string) $data['email'] : null,
            ipAddress: (string) ($data['ip_address'] ?? ''),
            success: (bool) ($data['success'] ?? false),
            attemptedAt: (string) ($data['attempted_at'] ?? ''),
            blockedUntil: isset($data['blocked_until']) ? (string) $data['blocked_until'] : null
        );
    }
}
