@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

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
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5>Total Products</h5>
                                    <h2>{{ $totalProducts }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5>Categories</h5>
                                    <h2>{{ $totalCategories }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5>Cart Items</h5>
                                    <h2>{{ $cartItemsCount }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5>Total Payments</h5>
                                    <h2>₱{{ number_format($totalPayments, 2) }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row">
                        <!-- Recent Payments -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Recent Payments</h5>
                                </div>
                                <div class="card-body">
                                    @if($recentPayments->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Amount</th>
                                                        <th>Method</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recentPayments as $payment)
                                                        <tr>
                                                            <td>₱{{ number_format($payment->amount, 2) }}</td>
                                                            <td>{{ ucfirst($payment->payment_method) }}</td>
                                                            <td>
                                                                <span class="badge badge-{{ $payment->status === 'completed' ? 'success' : 'warning' }}">
                                                                    {{ ucfirst($payment->status) }}
                                                                </span>
                                                            </td>
                                                            <td>{{ $payment->payment_date->format('M j, Y') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted">No payments found.</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Categories Overview -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Categories Overview</h5>
                                </div>
                                <div class="card-body">
                                    @if($categoriesWithCounts->count() > 0)
                                        @foreach($categoriesWithCounts as $category)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span>{{ $category->name }}</span>
                                                <span class="badge badge-primary">{{ $category->products_count }} products</span>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted">No categories found.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Summary -->
                    @if($userCart && count($userCart->items) > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5>Your Cart</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>You have {{ count($userCart->items) }} items in your cart.</p>
                                        <a href="#" class="btn btn-primary">View Cart</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
