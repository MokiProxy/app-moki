<?php

namespace App\Enums;

/**
 * Satu-satunya sumber nilai strategi & perlakuan risiko (sesuai Pedoman RKAP Bagian 2 & 4).
 *
 * Nilai: avoidance, reduction, sharing, acceptance
 */
enum ErkapRiskTreatmentType: string
{
    case Avoidance = 'avoidance';
    case Reduction = 'reduction';
    case Sharing = 'sharing';
    case Acceptance = 'acceptance';

    public function label(): string
    {
        return match ($this) {
            self::Avoidance => 'Hindari',
            self::Reduction => 'Kurangi',
            self::Sharing => 'Berbagi',
            self::Acceptance => 'Terima',
        };
    }

    /**
     * Seluruh nilai yang diakui sistem.
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }

    /**
     * Pemetaan nilai legacy (nilai DB lama & kata Indonesia dari Excel Form 1)
     * ke nilai baku:
     * - avoid / reduce / mitigate / transfer / accept (DB lama)
     * - hindari / kurangi / mitigasi / berbagi / terima / transferkan (kata)
     */
    public static function legacyMap(): array
    {
        return [
            'avoidance' => self::Avoidance->value,
            'avoid' => self::Avoidance->value,
            'hindari' => self::Avoidance->value,
            'reduction' => self::Reduction->value,
            'reduce' => self::Reduction->value,
            'mitigate' => self::Reduction->value,
            'kurangi' => self::Reduction->value,
            'mitigasi' => self::Reduction->value,
            'sharing' => self::Sharing->value,
            'transfer' => self::Sharing->value,
            'transferkan' => self::Sharing->value,
            'berbagi' => self::Sharing->value,
            'acceptance' => self::Acceptance->value,
            'accept' => self::Acceptance->value,
            'terima' => self::Acceptance->value,
        ];
    }

    /**
     * Resolve nilai baku dari nilai legacy / kata; null bila tak dikenal.
     */
    public static function fromLegacy(string $value): ?self
    {
        $target = self::legacyMap()[strtolower(trim($value))] ?? null;

        return $target ? self::tryFrom($target) : null;
    }
}