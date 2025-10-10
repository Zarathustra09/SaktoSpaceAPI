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
            <table id="userAdminTable" class="table table-hover table-striped">
                <thead class="thead-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    @php
                        $isAdmin = $user->hasRole('Admin');
                    @endphp
                    <tr>
                        <td><span class="badge bg-secondary">#{{ $user->id }}</span></td>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($isAdmin)
                                <span class="badge bg-success">Admin</span>
                            @else
                                <span class="badge bg-warning text-dark">User</span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                @if(!$isAdmin)
                                    <form class="d-inline" method="POST" action="{{ route('admin.users.promote', $user) }}" onsubmit="return confirm('Promote this user to Admin?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Promote to Admin">⬆️ Promote</button>
                                    </form>
                                @else
                                    <form class="d-inline" method="POST" action="{{ route('admin.users.demote', $user) }}" onsubmit="return confirm('Demote this Admin to regular user?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Demote from Admin">⬇️ Demote</button>
                                    </form>
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
            order: [[4, 'desc']], // Sort by joined date descending
            columnDefs: [
                { orderable: false, targets: [5] } // Disable sorting on Action column
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
