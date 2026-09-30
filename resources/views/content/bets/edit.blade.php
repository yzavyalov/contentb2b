@extends('dashboards.layouts.dashboard')

@section('title', 'Edit Bet')

@section('content')
    <livewire:content.create-bet :bet-id="$betId" />
@endsection
