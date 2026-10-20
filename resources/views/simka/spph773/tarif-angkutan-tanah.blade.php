@extends('layouts.SIMKA')

@section('title', 'SPPH 773 - Tarif Angkutan Pekerjaan Tanah')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph773.partials.tarif-angkutan-tanah-form',
    ])
@endsection
