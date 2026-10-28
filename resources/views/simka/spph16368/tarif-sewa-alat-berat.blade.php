@extends('layouts.SIMKA')

@section('title', 'SPPH 16368 - Tarif Dasar Sewa Alat Berat')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph16368.partials.tarif-sewa-alat-berat-form',
    ])
@endsection
