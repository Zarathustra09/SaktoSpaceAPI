@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>👤 User Details</h1>
                <a href="{{ route('users.index') }}" class="btn btn-secondary">← Back to Users</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>📋 User Information</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if($user->profile_image)
                            <img src="{{ asset('storage/' . $user->profile_image) }}" class="rounded-circle" width="100" height="100" alt="Profile">
                        @else
                            <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                                <span class="text-white fs-2">{{ substr($user->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <p><strong>Name:</strong> {{ $user->name }}</p>
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p><strong>Member Since:</strong> {{ $user->created_at->format('M d, Y') }}</p>
                    <p><strong>Email Verified:</strong>
                        @if($user->email_verified_at)
                            <span class="badge bg-success">✅ Yes</span>
                        @else
                            <span class="badge bg-warning">⏳ No</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>📊 Payment Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary">₱{{ number_format($payments->sum('amount'), 2) }}</h4>
                            <small class="text-muted">Total Spent</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info">{{ $payments->total() }}</h4>
                            <small class="text-muted">Total Orders</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <span class="badge bg-success">{{ $payments->where('status', 'completed')->count() }}</span>
                            <br><small>Completed</small>
                        </div>
                        <div class="col-4">
                            <span class="badge bg-warning">{{ $payments->where('status', 'pending')->count() }}</span>
                            <br><small>Pending</small>
                        </div>
                        <div class="col-4">
                            <span class="badge bg-danger">{{ $payments->where('status', 'failed')->count() }}</span>
                            <br><small>Failed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>💳 Transaction History</h5>
                    <select id="statusFilter" class="form-select" style="width: auto;">
                        <option value="">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="paymentTable" class="table table-hover table-striped">
                            <thead class="thead-light">
                            <tr>
                                <th>Transaction ID</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Items Count</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($payments as $payment)
                                <tr>
                                    <td>
                                        <code style="background: #e9ecef; padding: 2px 6px; border-radius: 4px; font-size: 0.875em;">
                                            {{ $payment->transaction_id }}
                                        </code>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">₱{{ number_format($payment->amount, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($payment->payment_method) }}</span>
                                    </td>
                                    <td>
                                        @switch($payment->status)
                                            @case('completed')
                                                <span class="badge bg-success">✅ Completed</span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning text-dark">⏳ Pending</span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">❌ Failed</span>
                                                @break
                                            @case('refunded')
                                                <span class="badge bg-secondary">🔄 Refunded</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">{{ $payment->status }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ count($payment->purchased_items ?? []) }} items</span>
                                    </td>
                                    <td>{{ $payment->payment_date ? $payment->payment_date->format('M d, Y H:i') : 'N/A' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-info" onclick="viewPayment({{ $payment->id }})" title="View Details">
                                            👁️ View
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No transactions found</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $payments->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        var table = $('#paymentTable').DataTable({
            order: [[5, 'desc']], // Sort by payment date descending
            columnDefs: [
                { orderable: false, targets: [6] } // Disable sorting on Action column
            ],
            paging: false, // Disable DataTables pagination since we're using Laravel pagination
            info: false // Disable info display
        });

        // Status filter functionality
        $('#statusFilter').on('change', function() {
            var filterValue = this.value;
            if (filterValue === '') {
                table.column(3).search('').draw();
            } else {
                table.column(3).search(filterValue).draw();
            }
        });
    });

    function viewPayment(paymentId) {
        $.get('/payments/' + paymentId, function(response) {
            const payment = response.data;

            // Create purchased items HTML
            let itemsHtml = '';
            if (payment.purchased_items && payment.purchased_items.length > 0) {
                itemsHtml = '<div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">';
                itemsHtml += '<h6 style="color: #495057; margin-bottom: 15px;">🛍️ Purchased Items:</h6>';

                payment.purchased_items.forEach((item, index) => {
                    itemsHtml += `
                        <div style="border-bottom: 1px solid #dee2e6; padding: 10px 0; ${index === payment.purchased_items.length - 1 ? 'border-bottom: none;' : ''}">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong style="color: #2c3e50;">${item.name}</strong>
                                    <br>
                                    <small style="color: #6c757d;">Qty: ${item.quantity} × ₱${parseFloat(item.price).toFixed(2)}</small>
                                </div>
                                <div style="text-align: right;">
                                    <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.8em;">
                                        ₱${parseFloat(item.subtotal).toFixed(2)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                itemsHtml += '</div>';
            } else {
                itemsHtml = '<div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; color: #856404;">No items found</div>';
            }

            const statusBadge = getStatusBadge(payment.status);
            const paymentMethodBadge = `<span style="background: #17a2b8; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">${payment.payment_method.toUpperCase()}</span>`;

            Swal.fire({
                title: `<h3 style="color: #2c3e50;">💳 Payment Details</h3>`,
                html: `
                    <div style="text-align: left; padding: 20px; max-height: 500px; overflow-y: auto;">
                        <div style="background: #e9ecef; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div>
                                    <strong style="color: #34495e;">🆔 Transaction ID:</strong>
                                    <br>
                                    <code style="background: #fff; padding: 4px 8px; border-radius: 4px; font-size: 0.9em;">${payment.transaction_id}</code>
                                </div>
                                <div>
                                    <strong style="color: #34495e;">👤 Customer:</strong>
                                    <br>
                                    <span style="color: #7f8c8d;">${payment.user ? payment.user.name : 'N/A'}</span>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div>
                                    <strong style="color: #34495e;">💰 Amount:</strong>
                                    <br>
                                    <span style="font-size: 1.3em; font-weight: bold; color: #28a745;">₱${parseFloat(payment.amount).toFixed(2)}</span>
                                </div>
                                <div>
                                    <strong style="color: #34495e;">💳 Payment Method:</strong>
                                    <br>
                                    ${paymentMethodBadge}
                                </div>
                            </div>

                            <div style="margin-bottom: 15px;">
                                <strong style="color: #34495e;">📊 Status:</strong>
                                <br>
                                ${statusBadge}
                            </div>

                            <div style="margin-bottom: 15px;">
                                <strong style="color: #34495e;">📅 Payment Date:</strong>
                                <br>
                                <span style="color: #7f8c8d;">${new Date(payment.payment_date).toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                })}</span>
                            </div>
                        </div>

                        ${itemsHtml}

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <strong style="color: #34495e;">📍 Billing Address:</strong>
                                <p style="margin: 8px 0; color: #7f8c8d; line-height: 1.4;">${payment.billing_address || 'N/A'}</p>
                            </div>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                                <strong style="color: #34495e;">🚚 Shipping Address:</strong>
                                <p style="margin: 8px 0; color: #7f8c8d; line-height: 1.4;">${payment.shipping_address || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonColor: '#3498db',
                confirmButtonText: '👍 Got it',
                width: '700px'
            });
        }).fail(function() {
            Swal.fire({
                title: '❌ Error!',
                text: 'Could not load payment details.',
                icon: 'error',
                confirmButtonColor: '#e74c3c'
            });
        });
    }

    function getStatusBadge(status) {
        const badges = {
            'completed': '<span style="background: #28a745; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85em;">✅ Completed</span>',
            'pending': '<span style="background: #ffc107; color: #212529; padding: 6px 12px; border-radius: 20px; font-size: 0.85em;">⏳ Pending</span>',
            'failed': '<span style="background: #dc3545; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85em;">❌ Failed</span>',
            'refunded': '<span style="background: #6c757d; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85em;">🔄 Refunded</span>'
        };
        return badges[status] || `<span style="background: #f8f9fa; color: #212529; padding: 6px 12px; border-radius: 20px; font-size: 0.85em; border: 1px solid #dee2e6;">${status}</span>`;
    }
</script>
@endpush
