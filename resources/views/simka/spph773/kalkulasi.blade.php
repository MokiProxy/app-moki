@extends('layouts.SIMKA')

@section('title', 'SPPH 773 - Kalkulasi')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph773.partials.konfigurasi-kalkulasi',
    ])
@endsection