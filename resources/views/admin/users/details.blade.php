@extends('admin.layouts.app')

@section('panel')
@php
    $userImage = $user->image ? asset('assets/profile/' . $user->image . '.webp') : asset('assets/img/avatars/avatar-placeholder.webp');

    $roleBadgeClass = $user->role == 'demo' ? 'bg-warning-light text-warning' : 'bg-info-light text-info';
    $roleText = ucfirst($user->role);

    $statusBadgeClass = $user->status == 1 ? 'bg-success-light text-success' : 'bg-danger-light text-danger';
    $statusText = $user->status == 1 ? 'Active' : 'Blocked';

    $verifiedText = $user->email_verified_at ? 'Verified' : 'Not Verified';
    $verifiedBadgeClass = $user->email_verified_at ? 'bg-success-light text-success' : 'bg-warning-light text-warning';
@endphp


    <section class="section">
        <!-- Profile Header -->
        <div class="card profile-header-card mb-4">
            <div class="profile-header-banner"></div>
            <div class="profile-header-content">
                <div class="profile-header-avatar">
                    <img src="{{ $userImage }}" alt="{{ $user->name }}">
                    <span class="profile-status-badge {{ $user->status == 1 ? 'bg-success' : 'bg-danger' }}"></span>
                </div>

                <div class="profile-header-info">
                    <div class="profile-header-name">
                        <h2>{{ $user->name }}</h2>
                        <span class="badge {{ $roleBadgeClass }}">{{ $roleText }}</span>
                        <span class="badge {{ $verifiedBadgeClass }}">
                            @if($user->email_verified_at)
                                <i class="bi bi-patch-check-fill me-1"></i>
                            @endif
                            {{ $verifiedText }}
                        </span>
                    </div>

                    <p class="profile-header-title">
                        Username: {{ $user->username ?: 'N/A' }}
                    </p>

                    <div class="profile-header-meta">
                        <span>
                            <i class="bi bi-envelope"></i>
                            {{ $user->email ?: 'No Email' }}
                        </span>
                        <span>
                            <i class="bi bi-phone"></i>
                            {{ ($user->phone_code ?? '') . ($user->phone ?? '') }}
                        </span>
                        <span>
                            <i class="bi bi-calendar3"></i>
                            Joined {{ $user->created_at ? $user->created_at->format('M Y') : 'N/A' }}
                        </span>
                    </div>
                </div>

                <div class="profile-header-stats">
                    <div class="profile-stat">
                        <div class="profile-stat-value">{{ number_format($user->balance, 2) }}</div>
                        <div class="profile-stat-label">Balance</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value">{{ number_format($user->strong_box_balance, 2) }}</div>
                        <div class="profile-stat-label">Strong Box</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value">{{ number_format($user->game_balance, 2) }}</div>
                        <div class="profile-stat-label">Game Balance</div>
                    </div>
                    <div class="profile-stat">
                        <div class="profile-stat-value">{{ number_format($user->turnover_completed, 2) }}</div>
                        <div class="profile-stat-label">Turnover Done</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-4">

                <!-- Contact Info -->
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Contact Information</h5>
                        <a href="javascript:void(0)" class="btn btn-sm btn-light">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="info-list">
                            <div class="info-list-item">
                                <div class="info-list-icon"><i class="bi bi-envelope"></i></div>
                                <div class="info-list-content">
                                    <div class="info-list-label">Email</div>
                                    <div class="info-list-value">{{ $user->email ?: 'N/A' }}</div>
                                </div>
                            </div>

                            <div class="info-list-item">
                                <div class="info-list-icon"><i class="bi bi-phone"></i></div>
                                <div class="info-list-content">
                                    <div class="info-list-label">Phone</div>
                                    <div class="info-list-value">{{ ($user->phone_code ?? '') . ($user->phone ?? '') }}</div>
                                </div>
                            </div>

                            <div class="info-list-item">
                                <div class="info-list-icon"><i class="bi bi-person"></i></div>
                                <div class="info-list-content">
                                    <div class="info-list-label">Username</div>
                                    <div class="info-list-value">{{ $user->username ?: 'N/A' }}</div>
                                </div>
                            </div>

                            <div class="info-list-item">
                                <div class="info-list-icon"><i class="bi bi-upc-scan"></i></div>
                                <div class="info-list-content">
                                    <div class="info-list-label">Referral Code</div>
                                    <div class="info-list-value">{{ $user->referral_code ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="info-list">
                            <div class="info-list-item">
                                <div class="info-list-icon"><i class="bi bi-shield"></i></div>
                                <div class="info-list-content">
                                    <div class="info-list-label">Role</div>
                                    <div class="info-list-value">{{ ucfirst($user->role) }}</div>
                                </div>
                            </div>

                            <div class="info-list-item">
                                <div class="info-list-icon"><i class="bi bi-check-circle"></i></div>
                                <div class="info-list-content">
                                    <div class="info-list-label">Status</div>
                                    <div class="info-list-value">{{ $statusText }}</div>
                                </div>
                            </div>

                            <div class="info-list-item">
                                <div class="info-list-icon"><i class="bi bi-calendar-check"></i></div>
                                <div class="info-list-content">
                                    <div class="info-list-label">Created At</div>
                                    <div class="info-list-value">{{ $user->created_at ? $user->created_at->format('d M Y, h:i A') : 'N/A' }}</div>
                                </div>
                            </div>

                            <div class="info-list-item">
                                <div class="info-list-icon"><i class="bi bi-arrow-repeat"></i></div>
                                <div class="info-list-content">
                                    <div class="info-list-label">Updated At</div>
                                    <div class="info-list-value">{{ $user->updated_at ? $user->updated_at->format('d M Y, h:i A') : 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Status -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Security Status</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="security-checklist">
                            <li class="security-checklist-item">
                                <div class="security-checklist-icon {{ $user->email_verified_at ? 'success' : 'warning' }}">
                                    <i class="bi {{ $user->email_verified_at ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
                                </div>
                                <div class="security-checklist-content">
                                    <div class="security-checklist-title">Email Verification</div>
                                    <div class="security-checklist-desc">
                                        {{ $user->email_verified_at ? 'Verified on ' . $user->email_verified_at->format('d M, Y h:i A') : 'Email not verified yet' }}
                                    </div>
                                </div>
                            </li>

                            <li class="security-checklist-item">
                                <div class="security-checklist-icon {{ $user->status == 1 ? 'success' : 'warning' }}">
                                    <i class="bi {{ $user->status == 1 ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }}"></i>
                                </div>
                                <div class="security-checklist-content">
                                    <div class="security-checklist-title">Account Status</div>
                                    <div class="security-checklist-desc">
                                        {{ $user->status == 1 ? 'This account is active' : 'This account is blocked' }}
                                    </div>
                                </div>
                            </li>

                            <li class="security-checklist-item">
                                <div class="security-checklist-icon success">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div class="security-checklist-content">
                                    <div class="security-checklist-title">Password Protected</div>
                                    <div class="security-checklist-desc">Password is stored securely</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header p-0 border-0">
                        <ul class="nav nav-tabs profile-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-future" type="button">
                                    <i class="bi bi-grid me-2"></i> Furure
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">
                                    <i class="bi bi-grid me-2"></i>Overview
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-balance" type="button">
                                    <i class="bi bi-wallet2 me-2"></i>Balances
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-account" type="button">
                                    <i class="bi bi-person-badge me-2"></i>Account
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body">
                        <div class="tab-content">

                            <!-- future -->
                            <div class="tab-pane fade active show" id="tab-future">
                                <div class="row g-3 mb-4">
                                    <button class="col-6 me-2 btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#balanceUpdateModal">
                                        <i class="bi bi-wallet2 me-1"></i> Balance Add / Sub
                                    </button>
                                    <button class="col-5 btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                        <i class="bi bi-key me-1"></i> Change Password
                                    </button>
                                    <button class="col-6 btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#statusUpdateModal">
                                        <i class="bi bi-person-check me-1"></i> Active / Inactive
                                    </button>
                                    <form class="col-6" action="{{ route('admin.users.login.as.user', $user->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success w-100">
                                            <i class="bi bi-box-arrow-in-right me-1"></i> Login As User
                                        </button>
                                    </form>
                                </div>

                                <div class="mb-4">
                                    <h6 class="section-title">About User</h6>
                                    <p class="text-muted mb-0">
                                        {{ $user->name }} is a {{ $user->role }} account.
                                        Current account status is <strong>{{ strtolower($statusText) }}</strong>.
                                        This user joined on
                                        <strong>{{ $user->created_at ? $user->created_at->format('d M Y') : 'N/A' }}</strong>.
                                    </p>
                                </div>
                            </div>
                            <!-- Overview -->
                            <div class="tab-pane fade" id="tab-overview">
                                <div class="row g-3 mb-4">
                                    <div class="col-sm-6 col-md-3">
                                        <div class="mini-stat-card">
                                            <div class="mini-stat-icon primary"><i class="bi bi-cash-stack"></i></div>
                                            <div class="mini-stat-content">
                                                <div class="mini-stat-value">{{ number_format($user->balance, 2) }}</div>
                                                <div class="mini-stat-label">Main Balance</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-md-3">
                                        <div class="mini-stat-card">
                                            <div class="mini-stat-icon success"><i class="bi bi-safe"></i></div>
                                            <div class="mini-stat-content">
                                                <div class="mini-stat-value">{{ number_format($user->strong_box_balance, 2) }}</div>
                                                <div class="mini-stat-label">Strong Box</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-md-3">
                                        <div class="mini-stat-card">
                                            <div class="mini-stat-icon warning"><i class="bi bi-controller"></i></div>
                                            <div class="mini-stat-content">
                                                <div class="mini-stat-value">{{ number_format($user->game_balance, 2) }}</div>
                                                <div class="mini-stat-label">Game Balance</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-md-3">
                                        <div class="mini-stat-card">
                                            <div class="mini-stat-icon info"><i class="bi bi-bar-chart"></i></div>
                                            <div class="mini-stat-content">
                                                <div class="mini-stat-value">{{ number_format($user->turnover_required, 2) }}</div>
                                                <div class="mini-stat-label">Turnover Req.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6 class="section-title">About User</h6>
                                    <p class="text-muted mb-0">
                                        {{ $user->name }} is a {{ $user->role }} account.
                                        Current account status is <strong>{{ strtolower($statusText) }}</strong>.
                                        This user joined on
                                        <strong>{{ $user->created_at ? $user->created_at->format('d M Y') : 'N/A' }}</strong>.
                                    </p>
                                </div>
                            </div>

                            <!-- Balance -->
                            <div class="tab-pane fade" id="tab-balance">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="project-card">
                                            <h6 class="project-card-title">Main Balance</h6>
                                            <p class="project-card-desc">Available wallet balance</p>
                                            <div class="fw-bold fs-4">{{ number_format($user->balance, 2) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="project-card">
                                            <h6 class="project-card-title">Strong Box Balance</h6>
                                            <p class="project-card-desc">Protected stored amount</p>
                                            <div class="fw-bold fs-4">{{ number_format($user->strong_box_balance, 2) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="project-card">
                                            <h6 class="project-card-title">Game Balance</h6>
                                            <p class="project-card-desc">Gaming wallet balance</p>
                                            <div class="fw-bold fs-4">{{ number_format($user->game_balance, 2) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="project-card">
                                            <h6 class="project-card-title">Turnover Completed</h6>
                                            <p class="project-card-desc">Completed turnover amount</p>
                                            <div class="fw-bold fs-4">{{ number_format($user->turnover_completed, 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Account -->
                            <div class="tab-pane fade" id="tab-account">
                                <div class="alert alert-info d-flex gap-2 mb-4">
                                    <i class="bi bi-info-circle flex-shrink-0"></i>
                                    <div>
                                        <strong>{{ ucfirst($user->role) }} Role</strong> -
                                        This user is currently marked as <strong>{{ $statusText }}</strong>.
                                    </div>
                                </div>

                                <h6 class="section-title">Account Summary</h6>

                                <div class="permissions-grid">
                                    <div class="permission-module">
                                        <div class="permission-module-header">
                                            <i class="bi bi-person"></i>
                                            <span>Name</span>
                                        </div>
                                        <div class="permission-badges">
                                            <span class="permission-badge granted">{{ $user->name }}</span>
                                        </div>
                                    </div>

                                    <div class="permission-module">
                                        <div class="permission-module-header">
                                            <i class="bi bi-at"></i>
                                            <span>Username</span>
                                        </div>
                                        <div class="permission-badges">
                                            <span class="permission-badge granted">{{ $user->username ?: 'N/A' }}</span>
                                        </div>
                                    </div>

                                    <div class="permission-module">
                                        <div class="permission-module-header">
                                            <i class="bi bi-envelope"></i>
                                            <span>Email</span>
                                        </div>
                                        <div class="permission-badges">
                                            <span class="permission-badge granted">{{ $user->email ?: 'N/A' }}</span>
                                        </div>
                                    </div>

                                    <div class="permission-module">
                                        <div class="permission-module-header">
                                            <i class="bi bi-telephone"></i>
                                            <span>Phone</span>
                                        </div>
                                        <div class="permission-badges">
                                            <span class="permission-badge granted">{{ ($user->phone_code ?? '') . ($user->phone ?? '') }}</span>
                                        </div>
                                    </div>

                                    <div class="permission-module">
                                        <div class="permission-module-header">
                                            <i class="bi bi-tag"></i>
                                            <span>Referral Code</span>
                                        </div>
                                        <div class="permission-badges">
                                            <span class="permission-badge granted">{{ $user->referral_code ?: 'N/A' }}</span>
                                        </div>
                                    </div>

                                    <div class="permission-module">
                                        <div class="permission-module-header">
                                            <i class="bi bi-check2-circle"></i>
                                            <span>Email Verification</span>
                                        </div>
                                        <div class="permission-badges">
                                            <span class="permission-badge granted">{{ $verifiedText }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<!-- 4) Balance Update modal -->
<div class="modal fade" id="balanceUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.users.balance.update', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Update Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Balance Type</label>
                        <select name="balance_type" class="form-select" required>
                            <option value="balance">Main Balance</option>
                            <option value="strong_box_balance">Strong Box Balance</option>
                            <option value="game_balance">Game Balance</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Action Type</label>
                        <select name="action_type" class="form-select" required>
                            <option value="add">Add</option>
                            <option value="subtract">Subtract</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reson </label>
                        <input type="text" name="resone" class="form-control" >
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 5) Change Password modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.users.change.password', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- 6) Status Update modal -->
<div class="modal fade" id="statusUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.users.status.update', $user->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Update User Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Select Status</label>
                    <select name="status" class="form-select" required>
                        <option value="1" {{ $user->status == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ $user->status == 0 ? 'selected' : '' }}>Inactive / Blocked</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" type="submit">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>




@endsection

@push('script')
@endpush