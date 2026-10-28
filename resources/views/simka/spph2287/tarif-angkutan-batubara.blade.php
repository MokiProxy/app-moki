@extends('layouts.SIMKA')

@section('title', 'SPPH 2287 - Tarif Angkutan Pekerjaan Batubara')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph2287.partials.tarif-angkutan-batubara-form',
    ])
@endsection
