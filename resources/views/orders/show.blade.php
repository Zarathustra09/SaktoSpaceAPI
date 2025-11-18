@extends('layouts.app')

@section('title', 'Order Details - ' . $order->payment->transaction_id)

@push('styles')
    <style>
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }

        .timeline::after {
            content: '';
            position: absolute;
            width: 4px;
            background-color: #e5e7eb;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -2px;
        }

        .timeline-item {
            padding: 10px 40px;
            position: relative;
            background-color: inherit;
            width: 50%;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            right: -10px;
            background-color: white;
            border: 4px solid #e5e7eb;
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }

        .timeline-item.left {
            left: 0;
        }

        .timeline-item.right {
            left: 50%;
        }

        .timeline-item.right::after {
            left: -10px;
        }

        .timeline-item.completed::after {
            background-color: #10b981;
            border-color: #10b981;
        }

        .timeline-item.current::after {
            background-color: #3b82f6;
            border-color: #3b82f6;
            animation: pulse 2s infinite;
        }

        .timeline-content {
            padding: 20px 30px;
            background-color: white;
            position: relative;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #e5e7eb;
        }

        .timeline-item.completed .timeline-content {
            border-left-color: #10b981;
        }

        .timeline-item.current .timeline-content {
            border-left-color: #3b82f6;
        }

        .status-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .info-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            overflow: hidden;
        }

        .info-card .card-header {
            background: linear-gradient(45deg, #f8fafc, #e2e8f0);
            border-bottom: none;
            font-weight: 600;
            color: #4a5568;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        @media screen and (max-width: 600px) {
            .timeline::after {
                left: 31px;
            }

            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }

            .timeline-item.right {
                left: 0;
            }

            .timeline-item.left::after,
            .timeline-item.right::after {
                left: 22px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container py-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">Order Details</h2>
                <p class="text-muted mb-0">Transaction ID: {{ $order->payment->transaction_id }}</p>
            </div>
            <div>
                <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Orders
                </a>
                <button class="btn btn-primary" onclick="updateOrderStatus({{ $order->id }}, '{{ $order->status }}')">
                    <i class="fas fa-edit me-2"></i>Update Status
                </button>
            </div>
        </div>

        <!-- Current Status Card -->
        <div class="status-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="mb-2">{{ $order->status }}</h3>
                    <p class="mb-0 opacity-75">
                        @switch($order->status)
                            @case('Preparing')
                                Your furniture order is being carefully prepared
                            @break

                            @case('To Ship')
                                Your furniture is ready and will be shipped soon
                            @break

                            @case('In Transit')
                                Your furniture is on its way to you
                            @break

                            @case('Out for Delivery')
                                Your furniture is out for delivery today
                            @break

                            @case('Delivered')
                                Your furniture has been delivered successfully
                            @break

                            @case('Cancelled')
                                This order has been cancelled
                            @break
                        @endswitch
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="fs-4">
                        @switch($order->status)
                            @case('Preparing')
                                <i class="fas fa-hammer"></i>
                            @break

                            @case('To Ship')
                                <i class="fas fa-box"></i>
                            @break

                            @case('In Transit')
                                <i class="fas fa-truck"></i>
                            @break

                            @case('Out for Delivery')
                                <i class="fas fa-shipping-fast"></i>
                            @break

                            @case('Delivered')
                                <i class="fas fa-check-circle"></i>
                            @break

                            @case('Cancelled')
                                <i class="fas fa-times-circle"></i>
                            @break
                        @endswitch
                    </div>
                    @if ($order->status_updated_at)
                        <small class="opacity-75">
                            Updated: {{ $order->status_updated_at->format('M d, Y H:i') }}
                        </small>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Order Information -->
            <div class="col-lg-4 mb-4">
                <div class="card info-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Order Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Product</label>
                            <div>{{ $order->product_name }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Category</label>
                            <div>{{ $order->category->name ?? 'No Category' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Quantity</label>
                            <div>{{ $order->quantity }} items</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Unit Price</label>
                            <div>₱{{ number_format($order->price, 2) }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Total Amount</label>
                            <div class="fs-5 fw-bold text-primary">₱{{ number_format($order->subtotal, 2) }}</div>
                        </div>
                        <div>
                            <label class="fw-bold text-muted">Order Date</label>
                            <div>{{ $order->created_at->format('M d, Y H:i A') }}</div>
                        </div>
                    </div>
                </div>

                <div class="card info-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Name</label>
                            <div>{{ $order->payment->user->name ?? 'Unknown Customer' }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Email</label>
                            <div>{{ $order->payment->user->email ?? 'N/A' }}</div>
                        </div>
                        @if ($order->payment->shipping_address)
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Shipping Address</label>
                                <div class="small">{{ $order->payment->shipping_address }}</div>
                            </div>
                        @endif
                        @if ($order->payment->recipient_name || $order->payment->recipient_contact)
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Recipient</label>
                                <div class="small">{{ $order->payment->recipient_name ?? 'N/A' }} @if ($order->payment->recipient_contact)
                                        • {{ $order->payment->recipient_contact }}
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if ($order->payment->billing_address)
                            <div>
                                <label class="fw-bold text-muted">Billing Address</label>
                                <div class="small">{{ $order->payment->billing_address }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card info-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Payment Status</label>
                            <div>
                                <span
                                    class="badge bg-{{ $order->payment->status === 'Completed' ? 'success' : ($order->payment->status === 'Pending' ? 'warning' : 'danger') }}">
                                    {{ $order->payment->status }}
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Payment Method</label>
                            <div>{{ $order->payment->payment_method }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="fw-bold text-muted">Total Amount</label>
                            <div class="fs-5 fw-bold">₱{{ number_format($order->payment->amount, 2) }}</div>
                        </div>
                        @if ($order->payment->payment_date)
                            <div>
                                <label class="fw-bold text-muted">Payment Date</label>
                                <div>{{ $order->payment->payment_date->format('M d, Y H:i A') }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Order Tracking Timeline -->
            <div class="col-lg-8 mb-4">
                <div class="card info-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-route me-2"></i>Order Tracking Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach ($statusTimeline as $index => $step)
                                <div
                                    class="timeline-item {{ $index % 2 === 0 ? 'left' : 'right' }} {{ $step['is_completed'] ? 'completed' : '' }} {{ $step['is_current'] ? 'current' : '' }}">
                                    <div class="timeline-content">
                                        <div class="d-flex align-items-center mb-2">
                                            <i
                                                class="{{ $step['icon'] }} me-2 {{ $step['is_completed'] ? 'text-success' : ($step['is_current'] ? 'text-primary' : 'text-muted') }}"></i>
                                            <h6
                                                class="mb-0 {{ $step['is_completed'] ? 'text-success' : ($step['is_current'] ? 'text-primary' : 'text-muted') }}">
                                                {{ $step['label'] }}
                                            </h6>
                                        </div>
                                        <p class="mb-2 small text-muted">{{ $step['description'] }}</p>
                                        @if ($step['timestamp'])
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $step['timestamp']->format('M d, Y H:i A') }}
                                            </small>
                                        @elseif($step['is_current'])
                                            <small class="text-primary fw-bold">
                                                <i class="fas fa-circle me-1"></i>
                                                Current Status
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($order->product && $order->product->image)
                    <div class="card info-card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-image me-2"></i>Product Image</h5>
                        </div>
                        <div class="card-body text-center">
                            <img src="{{ $order->product->image }}" alt="{{ $order->product_name }}"
                                class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
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
                                @foreach (\App\Models\Order::getStatuses() as $status)
                                    <option value="{{ $status }}"
                                        {{ $status === $order->status ? 'selected' : '' }}>
                                        {{ $status }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note for Customer (Optional)</label>
                            <textarea class="form-control" name="note" rows="3"
                                placeholder="Add a note that will be sent to the customer..."></textarea>
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
        function updateOrderStatus(orderId, currentStatus) {
            $('#updateOrderForm').data('order-id', orderId);
            $('select[name="status"]').val(currentStatus);
            $('#updateOrderModal').modal('show');
        }

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
                error: function(xhr) {
                    console.error('Error updating order status:', xhr);
                    alert('Error updating order status');
                }
            });
        });
    </script>
@endpush
