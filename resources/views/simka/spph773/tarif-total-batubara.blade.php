@extends('layouts.SIMKA')

@section('title', 'SPPH 773 - Tarif Total Pekerjaan Batubara')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.partials.spph-tarif-total-form',
        'tarifTotal' => [
            'title' => 'B. Tarif Total - Pekerjaan Batubara',
            'subtitle' => 'Total tarif pekerjaan batubara per rentang jarak (interval 250)',
            'icon' => 'mdi-sigma',
            'color' => 'warning',
            'labelPrefix' => 'Tarif Total',
            'formId' => 'formTarifTotalBatubara',
            'routeKalkulasi' => 'simka.spph773.tarif-total-batubara.kalkulasi',
            'routeHapus' => 'simka.spph773.tarif-total-batubara.hapus',
            'emptyText' => 'Belum ada data tarif total pekerjaan batubara.',
        ],
    ])
@endsection
