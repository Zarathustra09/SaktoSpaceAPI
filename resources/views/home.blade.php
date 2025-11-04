@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Analytics Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <!-- Welcome Message -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4>Welcome back, {{ Auth::user()->name }}!</h4>
                            <p class="text-muted">Here's your personalized shopping analytics</p>
                        </div>
                    </div>

                    <!-- Enhanced Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6>Total Products</h6>
                                    <h3>{{ $totalProducts }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Completed Orders</h6>
                                    <h3>{{ $totalOrders }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6>Cart Items</h6>
                                    <h3>{{ $cartItemsCount }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h6>Total Spent</h6>
                                    <h3>₱{{ number_format($totalPayments, 0) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-dark text-white">
                                <div class="card-body text-center">
                                    <h6>Items Purchased</h6>
                                    <h3>{{ $totalPurchasedItems }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h6>Avg Order Value</h6>
                                    <h3>₱{{ number_format($averageOrderValue, 0) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Analytics Row -->
                    <div class="row mb-4">
                        <!-- Monthly Spending Trend -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-line"></i> Spending Trend (Last 6 Months)</h5>
                                </div>
                                <div class="card-body">
                                    @if($monthlySpending->count() > 0)
                                        @foreach($monthlySpending as $month)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span>{{ $month['period'] }}</span>
                                                <div>
                                                    <span class="badge badge-success">₱{{ number_format($month['amount'], 2) }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No completed purchase history available.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Category Spending Breakdown -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-pie"></i> Category Spending</h5>
                                </div>
                                <div class="card-body">
                                    @if($categorySpendingData->count() > 0)
                                        @foreach($categorySpendingData as $category => $amount)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span>{{ $category }}</span>
                                                <div>
                                                    <span class="badge badge-primary">₱{{ number_format($amount, 2) }}</span>
                                                    <small class="text-muted">
                                                        ({{ $totalPayments > 0 ? number_format(($amount / $totalPayments) * 100, 1) : 0 }}%)
                                                    </small>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No category data available.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Analytics Row -->
                    <div class="row mb-4">
                        <!-- Most Purchased Products -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-shopping-cart"></i> Most Purchased Products</h5>
                                </div>
                                <div class="card-body">
                                    @if($topPurchasedProducts->count() > 0)
                                        @foreach($topPurchasedProducts as $product)
                                            <div class="media mb-3">
                                                @if($product['image'])
                                                    <img src="{{ $product['image'] }}" class="mr-3" style="width: 50px; height: 50px; object-fit: cover;" alt="{{ $product['name'] }}">
                                                @endif
                                                <div class="media-body">
                                                    <h6 class="mt-0">{{ $product['name'] }}</h6>
                                                    <small class="text-muted">{{ $product['category'] }}</small>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="badge badge-info">Qty: {{ $product['quantity'] }}</span>
                                                        <span class="badge badge-success">₱{{ number_format($product['total_spent'], 2) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No completed purchase history available.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Highest Value Products -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-dollar-sign"></i> Highest Value Purchases</h5>
                                </div>
                                <div class="card-body">
                                    @if($topSpentProducts->count() > 0)
                                        @foreach($topSpentProducts as $product)
                                            <div class="media mb-3">
                                                @if($product['image'])
                                                    <img src="{{ $product['image'] }}" class="mr-3" style="width: 50px; height: 50px; object-fit: cover;" alt="{{ $product['name'] }}">
                                                @endif
                                                <div class="media-body">
                                                    <h6 class="mt-0">{{ $product['name'] }}</h6>
                                                    <small class="text-muted">{{ $product['category'] }} • Last: {{ $product['last_purchased']->format('M j') }}</small>
                                                    <div class="d-flex justify-content-between">
                                                        <span class="badge badge-warning">{{ $product['quantity'] }}x purchased</span>
                                                        <span class="badge badge-success">₱{{ number_format($product['total_spent'], 2) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No completed purchase history available.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Row -->
                    <div class="row">
                        <!-- Recent Payments with Details -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-history"></i> Recent Completed Orders</h5>
                                </div>
                                <div class="card-body">
                                    @if($recentPayments->count() > 0)
                                        @foreach($recentPayments as $payment)
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6>₱{{ number_format($payment->amount, 2) }} - {{ ucfirst($payment->payment_method) }}</h6>
                                                            <small class="text-muted">{{ $payment->payment_date->format('M j, Y g:i A') }}</small>
                                                            <span class="badge badge-success ml-2">Completed</span>
                                                        </div>
                                                    </div>
                                                    @if($payment->purchased_products->count() > 0)
                                                        <div class="mt-2">
                                                            <small class="text-muted">Items purchased:</small>
                                                            <div class="row mt-1">
                                                                @foreach($payment->purchased_products->take(3) as $item)
                                                                    <div class="col-md-4">
                                                                        <div class="d-flex align-items-center">
                                                                            @if($item->image)
                                                                                <img src="{{ $item->image }}" style="width: 30px; height: 30px; object-fit: cover;" class="mr-2" alt="{{ $item->name }}">
                                                                            @endif
                                                                            <div>
                                                                                <small><strong>{{ $item->product ? $item->product->name : ($item->name ?? 'Unknown Product') }}</strong></small><br>
                                                                                <small class="text-muted">Qty: {{ $item->quantity ?? 1 }} • ₱{{ number_format($item->price ?? 0, 2) }}</small>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                                @if($payment->purchased_products->count() > 3)
                                                                    <div class="col-md-4">
                                                                        <small class="text-muted">+{{ $payment->purchased_products->count() - 3 }} more items</small>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No completed orders found.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Categories Overview -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-tags"></i> Store Categories</h5>
                                </div>
                                <div class="card-body">
                                    @if($categoriesWithCounts->count() > 0)
                                        @foreach($categoriesWithCounts as $category)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span>{{ $category->name }}</span>
                                                <span class="badge badge-primary">{{ $category->products_count }}</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No categories found.</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Cart Summary -->
                            @if($userCart && $cartItemsCount > 0)
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5><i class="fas fa-shopping-bag"></i> Your Cart</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>You have <strong>{{ $cartItemsCount }}</strong> items in your cart.</p>
                                        <a href="#" class="btn btn-primary btn-sm">View Cart</a>
                                        <a href="#" class="btn btn-success btn-sm">Checkout</a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-body h6 {
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
}
.card-body h3 {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
}
.media img {
    border-radius: 4px;
}
</style>
@endsection
