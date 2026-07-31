@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __("Anda Tidak Memiliki Akses Ke Aplikasi Ini" ?: 'Forbidden'))
