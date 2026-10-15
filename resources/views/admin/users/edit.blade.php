@extends('dashboards.layouts.dashboard')

@section('title', 'Edit User')

@section('content')

    <livewire:admin.user-edit :user-id="$userId" />

@endsection
