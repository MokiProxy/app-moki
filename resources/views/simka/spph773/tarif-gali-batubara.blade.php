@extends('layouts.SIMKA')

@section('title', 'SPPH 773 - Tarif Gali Pekerjaan Batubara')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph773.partials.tarif-gali-batubara-form',
    ])
@endsection
