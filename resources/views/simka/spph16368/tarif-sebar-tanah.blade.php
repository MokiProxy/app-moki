@extends('layouts.SIMKA')

@section('title', 'SPPH 16368 - Tarif Sebar Pekerjaan Tanah')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph16368.partials.tarif-sebar-tanah-form',
    ])
@endsection
