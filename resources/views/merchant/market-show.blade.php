@extends('dashboards.layouts.dashboard')

@section('title', 'Market Details')

@section('content')
    <livewire:merchant.market-show :bet-id="$bet->id" />
@endsection
