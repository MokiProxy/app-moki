@extends('layouts.SIMKA')

@section('title', 'SPPH 16368 - Tarif Gali Pekerjaan Batubara')

@section('content')
    @include('simka.partials.spph-subpage', [
        'contentView' => 'simka.spph16368.partials.tarif-gali-batubara-form',
    ])
@endsection
