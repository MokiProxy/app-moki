@extends('layouts.SIMKA')

@section('title', 'SPPH 2287 - Tarif Pekerjaan Perawatan Jalan dari Stockpile keluar IUP')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph2287.partials.tarif-satu-baris-form',
        'tarif' => [
            'title' => 'F. Tarif Pekerjaan Perawatan Jalan dari Stockpile keluar IUP',
            'subtitle' => 'Tarif dasar perawatan jalan dari stockpile keluar IUP',
            'icon' => 'mdi-road-variant',
            'color' => 'danger',
            'labelPrefix' => 'Tarif Perawatan Jalan',
            'inputName' => 'tarif_perawatan_jalan_stockpile_iup_dasar',
            'formId' => 'formTarifPerawatanJalanStockpileIup',
            'routeSimpan' => 'simka.spph2287.perawatan-jalan-stockpile-iup.simpan',
            'routeHapus' => 'simka.spph2287.perawatan-jalan-stockpile-iup.hapus',
            'routeKalkulasi' => 'simka.spph2287.perawatan-jalan-stockpile-iup.kalkulasi',
            'emptyText' => 'Belum ada data tarif perawatan jalan dari stockpile keluar IUP.',
        ],
    ])
@endsection
