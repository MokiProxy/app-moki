<?php

namespace App\Http\Controllers\SIMKA;

use App\Http\Controllers\Controller;

abstract class SpphController extends Controller
{
    /**
     * Key SPPH. Dipakai sebagai nama folder view dan prefix route, contoh "spph773".
     */
    abstract protected function spphKey(): string;

    /**
     * Judul SPPH yang ditampilkan pada UI, contoh "SPPH 773".
     */
    abstract protected function spphTitle(): string;

    public function index()
    {
        return view("simka.{$this->spphKey()}.index", $this->viewData());
    }

    public function konfigurasi()
    {
        return view("simka.{$this->spphKey()}.konfigurasi", $this->viewData() + [
            'pageTitle' => 'Konfigurasi Data',
            'pageIcon' => 'mdi-database-cog-outline',
            'pageColor' => 'primary',
            'pageDescription' => "Kelola master data dan parameter {$this->spphTitle()}.",
        ]);
    }

    public function kalkulasi()
    {
        return view("simka.{$this->spphKey()}.kalkulasi", $this->viewData() + [
            'pageTitle' => 'Kalkulasi',
            'pageIcon' => 'mdi-calculator-variant-outline',
            'pageColor' => 'success',
            'pageDescription' => "Jalankan dan tinjau proses kalkulasi {$this->spphTitle()}.",
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function viewData(): array
    {
        return [
            'spphKey' => $this->spphKey(),
            'spphTitle' => $this->spphTitle(),
        ];
    }
}
