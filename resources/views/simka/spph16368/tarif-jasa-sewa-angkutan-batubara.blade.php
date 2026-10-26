@extends('layouts.SIMKA')

@section('title', 'SPPH 16368 - Tarif Dasar Jasa Sewa Angkutan Batu Bara')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph16368.partials.tarif-jasa-sewa-angkutan-batubara-form',
    ])
@endsection
