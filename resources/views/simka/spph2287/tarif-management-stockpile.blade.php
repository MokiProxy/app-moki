@extends('layouts.SIMKA')

@section('title', 'SPPH 2287 - Tarif Pekerjaan Management Stockpile')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph2287.partials.tarif-satu-baris-form',
        'tarif' => [
            'title' => 'E. Tarif Pekerjaan Management Stockpile',
            'subtitle' => 'Tarif dasar management stockpile',
            'icon' => 'mdi-warehouse',
            'color' => 'primary',
            'labelPrefix' => 'Tarif Management Stockpile',
            'inputName' => 'tarif_management_stockpile_dasar',
            'formId' => 'formTarifManagementStockpile',
            'routeSimpan' => 'simka.spph2287.management-stockpile.simpan',
            'routeHapus' => 'simka.spph2287.management-stockpile.hapus',
            'routeKalkulasi' => 'simka.spph2287.management-stockpile.kalkulasi',
            'emptyText' => 'Belum ada data tarif management stockpile.',
        ],
    ])
@endsection
