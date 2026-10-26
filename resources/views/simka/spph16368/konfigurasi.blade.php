@extends('layouts.SIMKA')

@section('title', 'SPPH 16368 - Konfigurasi Data')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph16368.partials.konfigurasi-form',
    ])
@endsection
