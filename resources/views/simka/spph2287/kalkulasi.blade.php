@extends('layouts.SIMKA')

@section('title', 'SPPH 2287 - Kalkulasi')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph2287.partials.konfigurasi-kalkulasi',
    ])
@endsection
