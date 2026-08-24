@extends('admin.layouts.master')

@section('content')
<div class="auth-layout auth-layout-split">

    <div class="auth-form-side">
        <div class="auth-container">

            <a href="{{ route('admin.login')}}" class="auth-logo">
                <span>{{ gs('site_name')}}</span>
            </a>

            <div class="auth-card">

                <div class="auth-card-header">
                    <h1 class="auth-title">Sign in to your account</h1>
                    <p class="auth-subtitle">Enter your credentials to access your dashboard</p>
                </div>

                <form class="auth-form" method="POST" action="{{ route('admin.login') }}">
                    @csrf

                    <div class="form-group">
                        <label class="form-label">Email address</label>

                        <input type="email" class="form-control" name="email" placeholder="name@company.com" required>

                    </div>

                    <div class="form-group">

                        <label class="form-label">Password</label>

                        <div class="input-group">

                            <input type="password" class="form-control" name="password"
                                placeholder="Enter your password" required>

                            <button class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-eye"></i>
                            </button>

                        </div>

                    </div>

                    <div class="form-check">

                        <input class="form-check-input" type="checkbox" name="remember">

                        <label class="form-check-label">
                            Keep me signed in
                        </label>

                    </div>

                    <button type="submit" class="btn btn-primary btn-block">

                        Sign in

                    </button>

                </form>

            </div>
        </div>
    </div>
</div>
@endsection