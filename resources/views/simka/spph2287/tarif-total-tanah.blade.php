@extends('layouts.SIMKA')

@section('title', 'SPPH 2287 - Tarif Total Pekerjaan Tanah')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.partials.spph-tarif-total-form',
        'tarifTotal' => [
            'title' => 'A. Tarif Total - Pekerjaan Tanah',
            'subtitle' => 'Total tarif pekerjaan tanah per rentang jarak (interval 250)',
            'icon' => 'mdi-sigma',
            'color' => 'primary',
            'labelPrefix' => 'Tarif Total',
            'formId' => 'formTarifTotalTanah',
            'routeKalkulasi' => 'simka.spph2287.tarif-total-tanah.kalkulasi',
            'routeHapus' => 'simka.spph2287.tarif-total-tanah.hapus',
            'emptyText' => 'Belum ada data tarif total pekerjaan tanah.',
        ],
    ])
@endsection
