@extends('layouts.app')

@section('title', 'Order Management')

@push('styles')
<style>
    .status-badge {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .status-preparing { background-color: #FEF3C7; color: #92400E; }
    .status-to-ship { background-color: #DBEAFE; color: #1E40AF; }
    .status-in-transit { background-color: #E0E7FF; color: #3730A3; }
    .status-out-for-delivery { background-color: #FCE7F3; color: #BE185D; }
    .status-delivered { background-color: #D1FAE5; color: #065F46; }
    .status-cancelled { background-color: #FEE2E2; color: #991B1B; }

    .payment-pending { background-color: #FEF3C7; color: #92400E; }
    .payment-completed { background-color: #D1FAE5; color: #065F46; }
    .payment-cancelled { background-color: #FEE2E2; color: #991B1B; }
    .payment-refunded { background-color: #F3E8FF; color: #6B21A8; }

    .tracking-timeline {
        position: relative;
        padding-left: 1.5rem;
    }

    .tracking-timeline::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #E5E7EB;
    }

    .tracking-step {
        position: relative;
        padding-bottom: 1rem;
        margin-left: 0.5rem;
    }

    .tracking-step::before {
        content: '';
        position: absolute;
        left: -0.625rem;
        top: 0.125rem;
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 50%;
        border: 3px solid #E5E7EB;
        background: white;
    }

    .tracking-step.completed::before {
        background: #10B981;
        border-color: #10B981;
    }

    .tracking-step.current::before {
        background: #3B82F6;
        border-color: #3B82F6;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .order-card {
        transition: all 0.3s ease;
        border: 1px solid #E5E7EB;
    }

    .order-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transform: translateY(-1px);
    }

    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Order Management</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                        <i class="fas fa-edit me-2"></i>Bulk Update
                    </button>
                    <button class="btn btn-primary" onclick="refreshData()">
                        <i class="fas fa-sync me-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2 col-6 mb-3">
            <div class="stats-card">
                <h4>{{ $stats['total_orders'] }}</h4>
                <small>Total Orders</small>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h4>{{ $stats['pending_orders'] }}</h4>
                <small>Preparing</small>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <h4>{{ $stats['in_transit_orders'] }}</h4>
                <small>In Transit</small>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <h4>{{ $stats['delivered_orders'] }}</h4>
                <small>Delivered</small>
            </div>
        </div>
        <div class="col-md-2 col-6 mb-3">
            <div class="stats-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <h4>{{ $stats['cancelled_orders'] }}</h4>
                <small>Cancelled</small>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search"
                           value="{{ request('search') }}"
                           placeholder="Transaction ID, customer, product...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Order Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Statuses</option>
                        @foreach($orderStatuses as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment Status</label>
                    <select class="form-select" name="payment_status">
                        <option value="">All Payments</option>
                        @foreach($paymentStatuses as $status)
                            <option value="{{ $status }}" {{ request('payment_status') === $status ? 'selected' : '' }}>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Orders List -->
    <div class="row">
        @forelse($orders as $order)
            <div class="col-lg-6 mb-4">
                <div class="card order-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <input type="checkbox" class="form-check-input me-2 order-checkbox"
                                   value="{{ $order->id }}" data-order-id="{{ $order->id }}">
                            <strong>{{ $order->payment->transaction_id }}</strong>
                        </div>
                        <div class="d-flex gap-2">
                            <span class="status-badge status-{{ str_replace(' ', '-', strtolower($order->status)) }}">
                                {{ $order->status }}
                            </span>
                            <span class="status-badge payment-{{ strtolower($order->payment->status) }}">
                                {{ $order->payment->status }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-2">{{ $order->product_name }}</h6>
                                <p class="text-muted small mb-1">
                                    <i class="fas fa-user me-1"></i>
                                    {{ $order->payment->user->name ?? 'Unknown Customer' }}
                                </p>
                                <p class="text-muted small mb-1">
                                    <i class="fas fa-envelope me-1"></i>
                                    {{ $order->payment->user->email ?? 'N/A' }}
                                </p>
                                <p class="text-muted small mb-1">
                                    <i class="fas fa-tag me-1"></i>
                                    {{ $order->category->name ?? 'No Category' }}
                                </p>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-calendar me-1"></i>
                                    {{ $order->created_at->format('M d, Y H:i') }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <div class="tracking-timeline">
                                    @foreach(['Preparing', 'To Ship', 'In Transit', 'Delivered'] as $status)
                                        @php
                                            $isCompleted = $order->status === $status ||
                                                          (array_search($order->status, ['Preparing', 'To Ship', 'In Transit', 'Out for Delivery', 'Delivered'])
                                                           > array_search($status, ['Preparing', 'To Ship', 'In Transit', 'Delivered']));
                                            $isCurrent = $order->status === $status;
                                        @endphp
                                        <div class="tracking-step {{ $isCompleted ? 'completed' : '' }} {{ $isCurrent ? 'current' : '' }}">
                                            <small class="text-muted d-block">{{ $status }}</small>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-6">
                                <strong>₱{{ number_format($order->subtotal, 2) }}</strong>
                                <small class="text-muted">({{ $order->quantity }} items)</small>
                            </div>
                            <div class="col-6 text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="viewOrder({{ $order->id }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-success"
                                            onclick="updateOrderStatus({{ $order->id }}, '{{ $order->status }}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No orders found</h5>
                    <p class="text-muted">Try adjusting your filters or check back later.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $orders->links() }}
    </div>
</div>

<!-- Bulk Update Modal -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkUpdateForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Selected Orders: <span id="selectedCount">0</span></label>
                        <div id="selectedOrders" class="small text-muted"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select class="form-select" name="status" required>
                            <option value="">Select Status</option>
                            @foreach($orderStatuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Orders</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Single Order Update Modal -->
<div class="modal fade" id="updateOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateOrderForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            @foreach($orderStatuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Note (Optional)</label>
                        <textarea class="form-control" name="note" rows="3"
                                  placeholder="Add a note for the customer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Handle checkbox selection
    $('.order-checkbox').change(function() {
        updateSelectedOrders();
    });

    // Select all checkbox
    $('#selectAll').change(function() {
        $('.order-checkbox').prop('checked', $(this).prop('checked'));
        updateSelectedOrders();
    });

    // Bulk update form
    $('#bulkUpdateForm').submit(function(e) {
        e.preventDefault();

        const selectedOrders = getSelectedOrders();
        if (selectedOrders.length === 0) {
            alert('Please select at least one order');
            return;
        }

        const formData = {
            order_ids: selectedOrders,
            status: $('select[name="status"]', this).val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: '{{ route("orders.bulk-update-status") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Something went wrong'));
                }
            },
            error: function() {
                alert('Error updating orders');
            }
        });
    });

    // Single order update form
    $('#updateOrderForm').submit(function(e) {
        e.preventDefault();

        const orderId = $(this).data('order-id');
        const formData = {
            status: $('select[name="status"]', this).val(),
            note: $('textarea[name="note"]', this).val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: `/orders/${orderId}/status`,
            method: 'PATCH',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert('Order status updated successfully');
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Something went wrong'));
                }
            },
            error: function() {
                alert('Error updating order status');
            }
        });
    });
});

function updateSelectedOrders() {
    const selected = getSelectedOrders();
    $('#selectedCount').text(selected.length);

    if (selected.length > 0) {
        $('#selectedOrders').text(`Orders: ${selected.join(', ')}`);
    } else {
        $('#selectedOrders').text('No orders selected');
    }
}

function getSelectedOrders() {
    return $('.order-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
}

function updateOrderStatus(orderId, currentStatus) {
    $('#updateOrderForm').data('order-id', orderId);
    $('select[name="status"]', '#updateOrderForm').val(currentStatus);
    $('#updateOrderModal').modal('show');
}

function viewOrder(orderId) {
    window.open(`/orders/${orderId}`, '_blank');
}

function refreshData() {
    location.reload();
}
</script>
@endpush
