@extends('layouts.SIMKA')

@section('title', 'SPPH 773 - Konfigurasi Data')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph773.partials.konfigurasi-form',
    ])
@endsection
