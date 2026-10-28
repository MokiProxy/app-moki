@extends('layouts.SIMKA')

@section('title', 'SPPH 773 - Tarif Gali Pekerjaan Tanah')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph773.partials.tarif-gali-tanah-form',
    ])
@endsection
