@extends('layouts.SIMKA')

@section('title', 'SPPH 773 - Tarif Angkutan Pekerjaan Batubara')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph773.partials.tarif-angkutan-batubara-form',
    ])
@endsection
