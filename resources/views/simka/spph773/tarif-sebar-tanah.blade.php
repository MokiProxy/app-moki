@extends('layouts.SIMKA')

@section('title', 'SPPH 773 - Tarif Sebar Pekerjaan Tanah')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph773.partials.tarif-sebar-tanah-form',
    ])
@endsection
