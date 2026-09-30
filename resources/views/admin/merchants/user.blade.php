@extends('dashboards.layouts.dashboard')

@section('title', 'Merchants')

@section('content')

    <livewire:admin.user-merchants :user-id="$userId" />

@endsection
