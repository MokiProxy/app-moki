<?php

use App\Enums\ErkapRatingLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Konversi data rating lama ke nilai pedoman (G5):
     *   - B  -> BBB
     *   - A+ -> A (dihapus)
     * Sasaran (department_targets) yang menunjuk ke baris lama dialihkan ke baris kanonik.
     *
     * Hanya berjalan bila ada data lama (B / A+); database kosong/fresh tidak terpengaruh.
     */
    public function up(): void
    {
        $ratingCriteria = 'erkap_rating_criterias';
        $departmentTargets = 'erkap_department_targets';
        $fk = 'erkap_rating_criteria_id';

        $legacy = DB::table($ratingCriteria)
            ->whereIn('rating', ['B', 'A+'])
            ->pluck('id', 'rating');

        if ($legacy->isEmpty()) {
            return;
        }

        // 1. Kanonikkan BBB (dari seeder lama 'B').
        $bbbId = DB::table($ratingCriteria)->where('rating', 'BBB')->orderBy('id')->value('id');

        if ($bbbId === null && $legacy->has('B')) {
            $bbbId = $legacy['B'];
            DB::table($ratingCriteria)->where('id', $bbbId)->update([
                'rating' => 'BBB',
                'qualification' => ErkapRatingLevel::BBB->qualification(),
                'description' => ErkapRatingLevel::BBB->description(),
            ]);
        }

        // 2. Alihkan FK departemen dari 'B' ke BBB lalu hapus baris 'B' yang tersisa.
        if ($bbbId !== null) {
            $this->redirectAndDelete($ratingCriteria, $departmentTargets, $fk, 'B', $bbbId);
        }

        // 3. Kanonikkan A (buang 'A+').
        $canonicalA = DB::table($ratingCriteria)->where('rating', 'A')->orderBy('id')->first();

        if (! $canonicalA && $legacy->has('A+')) {
            $legacyAId = $legacy['A+'];
            DB::table($ratingCriteria)->where('id', $legacyAId)->update([
                'rating' => 'A',
                'qualification' => ErkapRatingLevel::A->qualification(),
                'description' => ErkapRatingLevel::A->description(),
            ]);
            $canonicalA = DB::table($ratingCriteria)->where('id', $legacyAId)->first();
        }

        // 4. Alihkan FK departemen dari 'A+' ke A lalu hapus baris 'A+' yang tersisa.
        if ($canonicalA) {
            $this->redirectAndDelete($ratingCriteria, $departmentTargets, $fk, 'A+', $canonicalA->id);
        }
    }

    public function down(): void
    {
        // Best-effort: nilai BBB/A+ lama tidak dikembalikan otomatis.
        // Jalankan ulang seeder ErkapRatingCriteriaSeeder pada staging untuk kondisi bersih.
    }

    protected function redirectAndDelete(string $criteriaTable, string $targetTable, string $fk, string $legacyRating, int $canonicalId): void
    {
        $legacyIds = DB::table($criteriaTable)->where('rating', $legacyRating)->pluck('id');

        foreach ($legacyIds as $legacyId) {
            DB::table($targetTable)->where($fk, $legacyId)->update([$fk => $canonicalId]);
            DB::table($criteriaTable)->where('id', $legacyId)->delete();
        }
    }
};