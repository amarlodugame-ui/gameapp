@extends('admin.layouts.app')

@section('panel')
<section class="section">

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3 col-6 p-2 mt-0">
            <div class="card user-stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-stat-icon bg-primary-light">
                            <i class="bi bi-people text-primary"></i>
                        </div>
                        <div>
                            <div class="user-stat-value">{{ $stats['totalUsers'] }}</div>
                            <div class="user-stat-label">Total Users</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 col-6 p-2 mt-0">
            <div class="card user-stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-stat-icon bg-success-light">
                            <i class="bi bi-person-check text-success"></i>
                        </div>
                        <div>
                            <div class="user-stat-value">{{ $stats['activeUsers'] }}</div>
                            <div class="user-stat-label">Active Users</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 col-6 p-2 mt-0">
            <div class="card user-stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-stat-icon bg-warning-light">
                            <i class="bi bi-person-exclamation text-warning"></i>
                        </div>
                        <div>
                            <div class="user-stat-value">{{ $stats['blockedUsers'] }}</div>
                            <div class="user-stat-label">Blocked U</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 col-6 p-2 mt-0">
            <div class="card user-stat-card">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-stat-icon bg-info-light">
                            <i class="bi bi-person-plus text-info"></i>
                        </div>
                        <div>
                            <div class="user-stat-value">+{{ $stats['thisMonthUsers'] }}</div>
                            <div class="user-stat-label">This Month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="display:inline-block;">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search users...">
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="d-flex flex-wrap justify-content-md-end gap-2">
                        <select id="statusFilter" class="form-select" style="width:auto;">
                            <option value="all">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Blocked</option>
                        </select>

                        <select id="roleFilter" class="form-select" style="width:auto;">
                            <option value="all">All Roles</option>
                            <option value="user">User</option>
                            <option value="demo">Demo</option>
                        </select>

                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-plus-lg me-1"></i> Add User
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="userTableWrapper">
            @include('admin.users.partials.table', ['users' => $users, 'perPage' => $perPage])
        </div>
    </div>
</section>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title">Add New User</h5>
                    <p class="text-muted small mb-0">Create a new user account</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="addUserForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Enter name">
                            <small class="text-danger error-text name_error"></small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" placeholder="Enter username">
                            <small class="text-danger error-text username_error"></small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" placeholder="Enter email address">
                            <small class="text-danger error-text email_error"></small>
                        </div>

                        <div class="col-4">
                            <label class="form-label">Phone Code</label>
                            <input type="text" class="form-control" name="phone_code" value="+88">
                            <small class="text-danger error-text phone_code_error"></small>
                        </div>

                        <div class="col-8">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" placeholder="Enter phone number">
                            <small class="text-danger error-text phone_error"></small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role">
                                <option value="">Select role...</option>
                                <option value="demo">Demo</option>
                                <option value="user">User</option>
                            </select>
                            <small class="text-danger error-text role_error"></small>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Enter password">
                            <small class="text-danger error-text password_error"></small>
                        </div>

                        <div class="col-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm password">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="1">Active</option>
                                <option value="0">Blocked</option>
                            </select>
                            <small class="text-danger error-text status_error"></small>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn">
                    <i class="bi bi-plus-lg me-1"></i> Add User
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function ($) {
    "use strict";

    let typingTimer = null;
    let currentRequest = null;
    const delay = 350;

    function getFilters(page = 1) {
        return {
            search: $('#searchInput').val(),
            status: $('#statusFilter').val(),
            role: $('#roleFilter').val(),
            per_page: $('#perPage').val() || 10,
            page: page
        };
    }

    function loadUsers(page = 1) {
        if (currentRequest) {
            currentRequest.abort();
        }

        currentRequest = $.ajax({
            url: "{{ route('admin.users.ajax.list') }}",
            type: "GET",
            data: getFilters(page),
            beforeSend: function () {
                $('#userTableWrapper').css('opacity', '0.6');
            },
            success: function (response) {
                if (response.status) {
                    $('#userTableWrapper').html(response.html);
                }
            },
            error: function (xhr) {
                if (xhr.status !== 0) {
                    $('#userTableWrapper').html(
                        '<div class="p-4 text-center text-danger">Something went wrong while loading users.</div>'
                    );
                }
            },
            complete: function () {
                $('#userTableWrapper').css('opacity', '1');
                currentRequest = null;
            }
        });
    }

    $(document).on('keyup', '#searchInput', function () {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function () {
            loadUsers(1);
        }, delay);
    });

    $(document).on('change', '#statusFilter, #roleFilter, #perPage', function () {
        loadUsers(1);
    });

    $(document).on('click', '.js-page-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) {
            loadUsers(page);
        }
    });

    $(document).on('change', '#selectAll', function () {
        $('.row-checkbox').prop('checked', $(this).is(':checked'));
    });

    $('#avatarUpload').on('change', function () {
        const file = this.files[0];
        if (file) {
            $('#avatarPreview').attr('src', URL.createObjectURL(file));
        }
    });

    $('#saveUserBtn').on('click', function () {
        $('.error-text').text('');

        let formData = new FormData($('#addUserForm')[0]);

        $.ajax({
            url: "{{ route('admin.users.store') }}",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $('#saveUserBtn').prop('disabled', true);
            },
            success: function (response) {
                if (response.status) {
                    $('#addUserForm')[0].reset();
                    $('#avatarPreview').attr('src', "{{ asset('assets/img/avatars/avatar-placeholder.webp') }}");
                    $('#addUserModal').modal('hide');
                    loadUsers(1);
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, function (key, val) {
                        $('.' + key + '_error').text(val[0]);
                    });
                }
            },
            complete: function () {
                $('#saveUserBtn').prop('disabled', false);
            }
        });
    });

})(jQuery);
</script>
@endpush