@extends('layouts.SIMKA')

@section('title', 'SPPH 2287 - Tarif Gali Pekerjaan Tanah')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph2287.partials.tarif-gali-tanah-form',
    ])
@endsection
