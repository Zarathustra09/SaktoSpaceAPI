@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">🔐 Admin Access Control</h2>
            <div class="d-flex gap-2">
                <select id="roleFilter" class="form-select" style="width: auto;">
                    <option value="">All Users</option>
                    <option value="admin">Admins</option>
                    <option value="user">Non-admins</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Admin Management:</strong> Promote users to Admin role or demote existing Admins. At least one Admin must remain in the system.
            </div>

            <table id="userAdminTable" class="table table-hover table-striped">
                <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Orders</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    @php
                        $isAdmin = $user->hasRole('Admin');
                        $totalOrders = $user->payments->sum(function($payment) {
                            return $payment->orders->count();
                        });
                    @endphp
                    <tr>
                        <td><span class="badge bg-secondary">#{{ $user->id }}</span></td>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            @if($user->id === auth()->id())
                                <small class="badge bg-info ms-1">You</small>
                            @endif
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($isAdmin)
                                <span class="badge bg-success"><i class="fas fa-crown me-1"></i>Admin</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="fas fa-user me-1"></i>User</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-primary">{{ $totalOrders }} orders</span>
                            <br><small class="text-muted">₱{{ number_format($user->payments->sum('amount'), 2) }}</small>
                        </td>
                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                @if(!$isAdmin)
                                    <form class="d-inline" method="POST" action="{{ route('admin.users.promote', $user) }}" onsubmit="return confirm('Promote {{ $user->name }} to Admin role?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Promote to Admin">
                                            <i class="fas fa-arrow-up me-1"></i>Promote
                                        </button>
                                    </form>
                                @else
                                    @if($user->id !== auth()->id())
                                        <form class="d-inline" method="POST" action="{{ route('admin.users.demote', $user) }}" onsubmit="return confirm('Demote {{ $user->name }} from Admin role to regular user?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Demote from Admin">
                                                <i class="fas fa-arrow-down me-1"></i>Demote
                                            </button>
                                        </form>
                                    @else
                                        <small class="text-muted">Cannot demote yourself</small>
                                    @endif
                                @endif
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
        var table = $('#userAdminTable').DataTable({
            order: [[5, 'desc']], // Sort by joined date descending
            columnDefs: [
                { orderable: false, targets: [6] } // Disable sorting on Action column
            ]
        });

        // Role filter functionality
        $('#roleFilter').on('change', function() {
            var filterValue = this.value;
            if (filterValue === '') {
                table.column(3).search('').draw();
            } else if (filterValue === 'admin') {
                table.column(3).search('Admin').draw();
            } else if (filterValue === 'user') {
                table.column(3).search('User').draw();
            }
        });
    });
</script>
@endpush
