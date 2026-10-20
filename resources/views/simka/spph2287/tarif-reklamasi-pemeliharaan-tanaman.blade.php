@extends('layouts.SIMKA')

@section('title', 'SPPH 2287 - Tarif Pekerjaan Reklamasi dan Pemeliharaan Tanaman')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph2287.partials.tarif-satu-baris-form',
        'tarif' => [
            'title' => 'D. Tarif Pekerjaan Reklamasi dan Pemeliharaan Tanaman',
            'subtitle' => 'Tarif dasar reklamasi dan pemeliharaan tanaman',
            'icon' => 'mdi-sprout',
            'color' => 'info',
            'labelPrefix' => 'Tarif Reklamasi dan Pemeliharaan Tanaman',
            'inputName' => 'tarif_reklamasi_pemeliharaan_tanaman_dasar',
            'formId' => 'formTarifReklamasiPemeliharaanTanaman',
            'routeSimpan' => 'simka.spph2287.reklamasi-pemeliharaan-tanaman.simpan',
            'routeHapus' => 'simka.spph2287.reklamasi-pemeliharaan-tanaman.hapus',
            'routeKalkulasi' => 'simka.spph2287.reklamasi-pemeliharaan-tanaman.kalkulasi',
            'emptyText' => 'Belum ada data tarif reklamasi dan pemeliharaan tanaman.',
        ],
    ])
@endsection
