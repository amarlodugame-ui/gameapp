@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid px-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">+ Create Announcement</a>
    </div>

<div class="card-body p-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Button Text</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th width="180">Action</th>
                </tr>
            </thead>
            <tbody>
                    @forelse($announcements as $key => $item)
                        <tr>
                            <td>{{ $announcements->firstItem() + $key }}</td>
                            <td>{{ $item->title }}</td>
                            <td>{{ $item->button_text ?? 'N/A' }}</td>
                            <td>
                                @if($item->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $item->created_at->format('d M Y h:i A') }}</td>
                            <td>
                                <a href="{{ route('admin.announcements.edit', $item->id) }}" class="btn btn-sm btn-info">
                                    Edit
                                </a>

                                <form action="{{ route('admin.announcements.status', $item->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $item->is_active ? 'btn-warning' : 'btn-success' }}">
                                        {{ $item->is_active ? 'Inactive' : 'Active' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No data found</td>
                        </tr>
                    @endforelse
            </tbody>
        </table>
    </div>
</div>

    <div class="mt-3">
        {{ $announcements->links() }}
    </div>
</div>
@endsection