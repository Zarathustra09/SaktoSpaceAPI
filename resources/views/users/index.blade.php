@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">👥 Users Management</h2>
            <div class="d-flex gap-2">
                <select id="statusFilter" class="form-select" style="width: auto;">
                    <option value="">All Users</option>
                    <option value="verified">Email Verified</option>
                    <option value="unverified">Not Verified</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <table id="userTable" class="table table-hover table-striped">
                <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Email Status</th>
                    <th>Total Payments</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>
                            <span class="badge bg-secondary">#{{ $user->id }}</span>
                        </td>
                        <td>
                            <strong>{{ $user->name }}</strong>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->email_verified_at)
                                <span class="badge bg-success">✅ Verified</span>
                            @else
                                <span class="badge bg-warning text-dark">⏳ Pending</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info">₱{{ number_format($user->payments->sum('amount'), 2) }}</span>
                            <small class="text-muted">({{ $user->payments->count() }} transactions)</small>
                        </td>
                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-info" title="View Details">
                                    👁️ View
                                </a>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewUserStats({{ $user->id }})" title="View Statistics">
                                    📊 Stats
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var table = $('#userTable').DataTable({
            order: [[5, 'desc']], // Sort by joined date descending
            columnDefs: [
                { orderable: false, targets: [6] } // Disable sorting on Action column
            ]
        });

        // Status filter functionality
        $('#statusFilter').on('change', function() {
            var filterValue = this.value;
            if (filterValue === '') {
                table.column(3).search('').draw();
            } else if (filterValue === 'verified') {
                table.column(3).search('Verified').draw();
            } else if (filterValue === 'unverified') {
                table.column(3).search('Pending').draw();
            }
        });
    });

    function viewUserStats(userId) {
        // Add user statistics modal functionality here if needed
        Swal.fire({
            title: '📊 User Statistics',
            text: 'User statistics feature coming soon!',
            icon: 'info',
            confirmButtonColor: '#3498db'
        });
    }
</script>
@endpush
