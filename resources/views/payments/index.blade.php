@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">💳 Payment History</h2>
            <div class="d-flex gap-2">
                <select id="statusFilter" class="form-select" style="width: auto;">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>
                <button id="exportExcel" class="btn btn-success">
                    📊 Export Excel
                </button>
            </div>
        </div>
        <div class="card-body">
            <table id="paymentTable" class="table table-hover table-striped">
                <thead class="thead-light">
                <tr>
                    <th>Transaction ID</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Items Count</th>
                    <th>Payment Date</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>
                            <code style="background: #e9ecef; padding: 2px 6px; border-radius: 4px; font-size: 0.875em;">
                                {{ $payment->transaction_id }}
                            </code>
                        </td>
                        <td>{{ $payment->user ? $payment->user->name : 'N/A' }}</td>
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
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-info" onclick="viewPayment({{ $payment->id }})" title="View Details">
                                    👁️ View
                                </button>
                                <button class="btn btn-sm btn-outline-warning" onclick="editPayment({{ $payment->id }})" title="Update Status">
                                    ✏️ Update
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
        var table = $('#paymentTable').DataTable({
            order: [[6, 'desc']], // Sort by payment date descending
            columnDefs: [
                { orderable: false, targets: [7] } // Disable sorting on Action column
            ]
        });

        // Status filter functionality
        $('#statusFilter').on('change', function() {
            var filterValue = this.value;
            if (filterValue === '') {
                table.column(4).search('').draw();
            } else {
                table.column(4).search(filterValue).draw();
            }
        });

        // Export respecting current status filter
        $('#exportExcel').on('click', function() {
            const status = $('#statusFilter').val();
            const base = '{{ route('payments.export') }}';
            const url = status ? `${base}?status=${encodeURIComponent(status)}` : base;
            window.location.href = url;
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

    function editPayment(paymentId) {
        $.get('/payments/' + paymentId, function(response) {
            const payment = response.data;

            Swal.fire({
                title: `<h3 style="color: #2c3e50; margin-bottom: 20px;">✏️ Update Payment Status</h3>`,
                html: `
                    <div style="text-align: left; padding: 0 20px;">
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 25px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <strong style="color: #34495e;">Transaction ID:</strong>
                                    <br>
                                    <code style="background: #fff; padding: 4px 8px; border-radius: 4px;">${payment.transaction_id}</code>
                                </div>
                                <div>
                                    <strong style="color: #34495e;">Amount:</strong>
                                    <br>
                                    <span style="font-weight: bold; color: #28a745;">₱${parseFloat(payment.amount).toFixed(2)}</span>
                                </div>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📊 Payment Status *</label>
                            <select id="payment-status" class="swal2-select" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                                <option value="">Select Status</option>
                                <option value="pending" ${payment.status === 'pending' ? 'selected' : ''}>⏳ Pending</option>
                                <option value="completed" ${payment.status === 'completed' ? 'selected' : ''}>✅ Completed</option>
                                <option value="failed" ${payment.status === 'failed' ? 'selected' : ''}>❌ Failed</option>
                                <option value="refunded" ${payment.status === 'refunded' ? 'selected' : ''}>🔄 Refunded</option>
                            </select>
                        </div>

                        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; border-left: 4px solid #bee5eb;">
                            <small style="color: #0c5460;">
                                <strong>ℹ️ Note:</strong> Changing status to "Refunded" will not automatically process the refund. Handle refunds through your payment gateway.
                            </small>
                        </div>
                    </div>
                `,
                width: '500px',
                padding: '30px',
                showCancelButton: true,
                confirmButtonText: '💾 Update Status',
                confirmButtonColor: '#f39c12',
                cancelButtonText: '❌ Cancel',
                cancelButtonColor: '#95a5a6',
                customClass: {
                    confirmButton: 'btn btn-warning btn-lg',
                    cancelButton: 'btn btn-secondary btn-lg'
                },
                preConfirm: () => {
                    const status = document.getElementById('payment-status').value;

                    if (!status) {
                        Swal.showValidationMessage('❗ Please select a payment status');
                        return false;
                    }

                    return {
                        status: status
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updatePayment(paymentId, result.value);
                }
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

    function updatePayment(paymentId, data) {
        // Show loading
        Swal.fire({
            title: 'Updating Payment Status...',
            html: '<div class="spinner-border text-warning" role="status"></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        $.ajax({
            url: '/payments/' + paymentId,
            type: 'PUT',
            data: {
                _token: '{{ csrf_token() }}',
                ...data
            },
            success: function(response) {
                Swal.fire({
                    title: '🎉 Updated!',
                    text: 'Payment status has been updated successfully.',
                    icon: 'success',
                    confirmButtonColor: '#27ae60',
                    confirmButtonText: '👍 Great!'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(response) {
                if (response.status === 422) {
                    let errors = response.responseJSON.errors;
                    let errorMessages = '';
                    for (let field in errors) {
                        errorMessages += `• ${errors[field].join(', ')}<br>`;
                    }
                    Swal.fire({
                        title: '❌ Validation Error',
                        html: `<div style="text-align: left; color: #e74c3c;">${errorMessages}</div>`,
                        icon: 'error',
                        confirmButtonColor: '#e74c3c'
                    });
                } else {
                    Swal.fire({
                        title: '❌ Error!',
                        text: 'There was an error updating the payment status.',
                        icon: 'error',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            }
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
