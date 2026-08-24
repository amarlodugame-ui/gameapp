@extends('admin.layouts.master')
@section('content')

@include('partials.notify')

@include('admin.partials.header')
@include('admin.partials.mobile_search_header')
@include('admin.partials.sidebar')

<main class="main">
    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">{{ $pageTitle }}</h1>
        </div>

        @yield('panel')
    </div>

    @include('admin.partials.footer')
</main>
<style>
    .container-fluid{
        padding:0;
    }
</style>
@endsection