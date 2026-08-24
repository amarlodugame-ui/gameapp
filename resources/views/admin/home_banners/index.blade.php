@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid px-2">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Validation Error</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ $pageTitle }}</h5>

            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBannerModal">
                Add Banner
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Route</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Sort</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($banners as $key => $banner)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>
                                    <img src="{{ $banner->image }}"
                                         alt="banner"
                                         style="width:80px;height:42px;object-fit:cover;border-radius:6px;border:1px solid #eee;">
                                </td>
                                <td>{{ $banner->route_name }}</td>
                                <td>{{ $banner->title ?? 'N/A' }}</td>
                                <td>
                                    @if($banner->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $banner->sort_order }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button type="button"
                                                class="btn btn-outline-primary btn-sm editBtn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editBannerModal"
                                                data-update-route="{{ route('admin.home.banners.update', $banner->id) }}"
                                                data-image="{{ $banner->image }}"
                                                data-route-name="{{ $banner->route_name }}"
                                                data-title="{{ $banner->title }}"
                                                data-status="{{ $banner->is_active }}">
                                            Edit
                                        </button>

                                        <form action="{{ route('admin.home.banners.destroy', $banner->id) }}" method="POST" onsubmit="return confirm('Delete this banner?')">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">No banner found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addBannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('admin.home.banners.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label">Route Name</label>
                        <input type="text" class="form-control" value="user.home" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" value="N/A" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Banner Image</label>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" checked>
                        <label class="form-check-label" for="add_is_active">Active</label>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editBannerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <form id="editBannerForm" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Edit Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-2 text-center">
                        <img id="edit_preview"
                             src=""
                             alt="banner preview"
                             style="max-width:100%;height:120px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Route Name</label>
                        <input type="text" class="form-control" id="edit_route_name" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" id="edit_title" disabled>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Change Image</label>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">Active</label>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    'use strict';

    const editButtons = document.querySelectorAll('.editBtn');
    const editForm = document.getElementById('editBannerForm');
    const editPreview = document.getElementById('edit_preview');
    const editRouteName = document.getElementById('edit_route_name');
    const editTitle = document.getElementById('edit_title');
    const editIsActive = document.getElementById('edit_is_active');

    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            editForm.action = this.dataset.updateRoute;
            editPreview.src = this.dataset.image || '';
            editRouteName.value = this.dataset.routeName || '';
            editTitle.value = this.dataset.title && this.dataset.title !== 'null' ? this.dataset.title : 'N/A';
            editIsActive.checked = this.dataset.status == '1';
        });
    });
})();
</script>
@endpush