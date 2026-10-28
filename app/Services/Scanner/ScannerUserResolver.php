<?php

namespace App\Services\Scanner;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ScannerUserResolver
{
    /**
     * Mapping eksplisit dari users.scanner_folder.
     *
     * @var array<string, User>
     */
    private array $byExplicitFolder = [];

    /**
     * @var array<string, array<string|int, User>>
     */
    private array $byEmployeeId = [];

    /**
     * @var array<string, array<string|int, User>>
     */
    private array $byEmailLocalPart = [];

    /**
     * @var array<string, array<string|int, User>>
     */
    private array $byName = [];

    /**
     * @var array<string, array<string|int, User>>
     */
    private array $byEmployeeName = [];

    /**
     * @param  Collection<int, User>  $users
     */
    public function __construct(Collection $users)
    {
        foreach ($users as $user) {
            $this->addExplicit($user->scanner_folder, $user);

            // Urutan kandidat auto-match mengikuti prioritas di strategi:
            // employee_id/nopeg, email, nama user, lalu nama employee.
            $this->addCandidate($this->byEmployeeId, $user->employee_id, $user);
            $this->addCandidate($this->byEmployeeId, $user->nopeg, $user);
            $this->addCandidate($this->byEmailLocalPart, self::emailLocalPart($user->email), $user);
            $this->addCandidate($this->byName, $user->name, $user);

            if ($user->relationLoaded('employee')) {
                $this->addCandidate($this->byEmployeeName, $user->employee?->name, $user);
            }
        }
    }

    public function resolveExplicit(string $folder): ?User
    {
        $normalized = self::normalize($folder);

        return $this->byExplicitFolder[$normalized] ?? null;
    }

    public function resolveAuto(string $folder): UserResolution
    {
        $normalized = self::normalize($folder);

        if ($normalized === '') {
            return UserResolution::unresolved($folder);
        }

        $candidates = [
            'employee_id' => $this->byEmployeeId,
            'email' => $this->byEmailLocalPart,
            'name' => $this->byName,
            'employee_name' => $this->byEmployeeName,
        ];

        foreach ($candidates as $matchType => $map) {
            $matches = array_values($map[$normalized] ?? []);

            if (count($matches) === 1) {
                return UserResolution::resolved($matches[0], $folder, $matchType);
            }

            if (count($matches) > 1) {
                return UserResolution::ambiguous($folder);
            }
        }

        return UserResolution::unresolved($folder);
    }

    public static function normalize(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '');
    }

    private function addExplicit(?string $folder, User $user): void
    {
        $key = self::normalize($folder);

        if ($key === '') {
            return;
        }

        $this->byExplicitFolder[$key] = $user;
    }

    /**
     * @param  array<string, array<string|int, User>>  $map
     */
    private function addCandidate(array &$map, ?string $value, User $user): void
    {
        $key = self::normalize($value);

        if ($key === '') {
            return;
        }

        $userKey = $user->getKey() ?? spl_object_id($user);
        $map[$key][$userKey] = $user;
    }

    private static function emailLocalPart(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return Str::before(Str::before($email, '@'), '+');
    }
}
