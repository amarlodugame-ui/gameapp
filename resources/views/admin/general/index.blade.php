@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form action="{{ route('admin.general.setting.update') }}" method="POST">
                        @csrf

                        <div class="row g-4">

                            <div class="col-md-6">
                                <label for="site_name" class="form-label fw-semibold">Site Name</label>
                                <input type="text"
                                       class="form-control @error('site_name') is-invalid @enderror"
                                       id="site_name"
                                       name="site_name"
                                       value="{{ old('site_name', $settingRow->site_name ?? '') }}"
                                       placeholder="Enter site name">
                                @error('site_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="cur_text" class="form-label fw-semibold">Currency Text</label>
                                <input type="text"
                                       class="form-control @error('cur_text') is-invalid @enderror"
                                       id="cur_text"
                                       name="cur_text"
                                       value="{{ old('cur_text', $settingRow->cur_text ?? '') }}"
                                       placeholder="e.g. BDT">
                                @error('cur_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="cur_sym" class="form-label fw-semibold">Currency Symbol</label>
                                <input type="text"
                                       class="form-control @error('cur_sym') is-invalid @enderror"
                                       id="cur_sym"
                                       name="cur_sym"
                                       value="{{ old('cur_sym', $settingRow->cur_sym ?? '') }}"
                                       placeholder="e.g. ৳">
                                @error('cur_sym')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="signup_bonus_amount" class="form-label fw-semibold">Registration Bonus Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $settingRow->cur_sym ?? '৳' }}</span>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           class="form-control @error('signup_bonus_amount') is-invalid @enderror"
                                           id="signup_bonus_amount"
                                           name="signup_bonus_amount"
                                           value="{{ old('signup_bonus_amount', $settingRow->signup_bonus_amount ?? 0) }}"
                                           placeholder="Enter signup bonus amount">
                                </div>
                                @error('signup_bonus_amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="paw_download" class="form-label fw-semibold">PAW Download URL</label>
                                <input type="text"
                                       class="form-control @error('paw_download') is-invalid @enderror"
                                       id="paw_download"
                                       name="paw_download"
                                       value="{{ old('paw_download', $settingRow->paw_download ?? '') }}"
                                       placeholder="https://example.com/download">
                                @error('paw_download')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="row g-3">

                                    <div class="col-md-4" style="display:none;">
                                        <div class="card border">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Maintenance Mode</h6>
                                                    <small class="text-muted">Enable or disable site maintenance</small>
                                                </div>
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           role="switch"
                                                           id="maintenance_mode"
                                                           name="maintenance_mode"
                                                           value="1"
                                                           {{ old('maintenance_mode', $settingRow->maintenance_mode ?? 0) ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card border">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Registration</h6>
                                                    <small class="text-muted">Allow new user registration</small>
                                                </div>
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           role="switch"
                                                           id="registration"
                                                           name="registration"
                                                           value="1"
                                                           {{ old('registration', $settingRow->registration ?? 0) ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card border">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">Deposit Commission</h6>
                                                    <small class="text-muted">Enable or disable deposit commission</small>
                                                </div>
                                                <div class="form-check form-switch m-0">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           role="switch"
                                                           id="deposit_commission"
                                                           name="deposit_commission"
                                                           value="1"
                                                           {{ old('deposit_commission', $settingRow->deposit_commission ?? 0) ? 'checked' : '' }}>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bx bx-save me-1"></i> Update Settings
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .card {
        border-radius: 12px;
    }
    .card-header h4 {
        font-weight: 600;
    }
    .form-label {
        margin-bottom: .5rem;
    }
    .form-control,
    .input-group-text {
        min-height: 45px;
        border-radius: 8px;
    }
    .btn-primary {
        border-radius: 8px;
        font-weight: 500;
    }
    .form-check-input {
        width: 3rem;
        height: 1.5rem;
        cursor: pointer;
    }
</style>
@endpush