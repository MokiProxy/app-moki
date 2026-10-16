@php
$authUserName = auth()->user()->name;
@endphp

@extends('layouts.SIMKA')

@section('title', 'Dashboard SIMKA')

@section('css')
<style>
    .text-dark { color: #000000 !important; }

    .stat-card {
        border-radius: 16px;
        color: #fff;
        position: relative;
        overflow: hidden;
        transition: 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-4px);
    }

    .stat-card .icon-overlay {
        position: absolute;
        right: -10px;
        bottom: -10px;
        font-size: 4.5rem;
        opacity: 0.18;
        transform: rotate(-15deg);
    }

    .heat-cell {
        min-width: 52px;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        border: 2px solid #fff;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 700;
        color: #1e293b;
        transition: transform 0.15s, box-shadow 0.15s;
    }

    .heat-cell:hover {
        transform: scale(1.06);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
    }

    .heat-cell .heat-count {
        font-size: 0.7rem;
        font-weight: 600;
    }

    .gantt-dot {
        color: #3b82f6;
        font-size: 1rem;
    }

    .progress {
        background-color: #e9ecef;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-2">
</div>

@endsection

@section('plugin')
@endsection
