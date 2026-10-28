<?php

namespace App\Services\Scanner;

use App\Models\User;

final class UserResolution
{
    public function __construct(
        public ?User $user,
        public string $folder,
        public string $matchType = 'unresolved',
    ) {}

    public static function resolved(User $user, string $folder, string $matchType): self
    {
        return new self($user, $folder, $matchType);
    }

    public static function ambiguous(string $folder): self
    {
        return new self(null, $folder, 'ambiguous');
    }

    public static function unresolved(string $folder): self
    {
        return new self(null, $folder, 'unresolved');
    }

    public function isResolved(): bool
    {
        return $this->user !== null;
    }

    public function isAmbiguous(): bool
    {
        return $this->matchType === 'ambiguous';
    }
}
