@extends('layouts.SIMKA')

@section('title', 'SPPH 2287 - Tarif Pekerjaan Lingkungan')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph2287.partials.tarif-satu-baris-form',
        'tarif' => [
            'title' => 'C. Tarif Pekerjaan Lingkungan',
            'subtitle' => 'Tarif dasar pekerjaan lingkungan',
            'icon' => 'mdi-leaf',
            'color' => 'success',
            'labelPrefix' => 'Tarif Pekerjaan Lingkungan',
            'inputName' => 'tarif_pekerjaan_lingkungan_dasar',
            'formId' => 'formTarifPekerjaanLingkungan',
            'routeSimpan' => 'simka.spph2287.pekerjaan-lingkungan.simpan',
            'routeHapus' => 'simka.spph2287.pekerjaan-lingkungan.hapus',
            'routeKalkulasi' => 'simka.spph2287.pekerjaan-lingkungan.kalkulasi',
            'emptyText' => 'Belum ada data tarif pekerjaan lingkungan.',
        ],
    ])
@endsection
