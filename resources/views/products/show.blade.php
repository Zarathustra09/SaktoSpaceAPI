@extends('layouts.app')

@section('content')
@php
    $stats = $product->salesStats();
    $orders = $product->orders()->latest()->take(25)->get();
    $ratings = $product->ratings()->latest()->take(25)->get();
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">🛍️ {{ $product->name }}</h2>
        <a href="{{ route('products.index') }}" class="btn btn-secondary btn-sm">← Back</a>
    </div>

    <div class="row">
        <!-- Left: Product Details -->
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header fw-bold">Product Details</div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if($product->image)
                            <img src="{{ $product->image }}" alt="{{ $product->name }}" style="max-width:100%;max-height:220px;object-fit:cover;border-radius:8px;">
                        @else
                            <div class="p-4 bg-light text-muted rounded">No Image</div>
                        @endif
                    </div>
                    <p><strong>Description:</strong><br>{{ $product->description ?: '—' }}</p>
                    <p><strong>Price:</strong> ₱{{ number_format($product->price,2) }}</p>
                    <p><strong>Stock:</strong> {{ $product->stock }}</p>
                    <p><strong>Category:</strong> {{ $product->category?->name ?? 'N/A' }}</p>
                    <p><strong>AR Model:</strong>
                        @if($product->ar_model_url)
                            <span class="badge bg-success">Available</span>
                        @else
                            <span class="badge bg-secondary">None</span>
                        @endif
                    </p>
                    <p><strong>Created:</strong> {{ $product->created_at->format('M d, Y') }}</p>

                    @if($product->images->count())
                        <hr>
                        <strong>Additional Images ({{ $product->images->count() }}):</strong>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @foreach($product->images as $img)
                                <img src="{{ $img->url }}" alt="{{ $img->alt_text ?? $product->name }}" style="width:70px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Middle: Statistics -->
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header fw-bold">Statistics</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <div class="small text-muted">Orders</div>
                                <div class="h5 mb-0">{{ $stats['orders_count'] }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <div class="small text-muted">Units Sold</div>
                                <div class="h5 mb-0">{{ $stats['units_sold'] }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <div class="small text-muted">Revenue</div>
                                <div class="h5 mb-0">₱{{ number_format($stats['revenue'],2) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded">
                                <div class="small text-muted">Avg Rating</div>
                                <div class="h5 mb-0">{{ number_format($stats['average_rating'],2) }}/5</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6 class="fw-bold mb-2">Rating Breakdown</h6>
                    @for($i=5;$i>=1;$i--)
                        @php
                            $count = $stats['rating_breakdown'][$i] ?? 0;
                            $total = array_sum($stats['rating_breakdown']);
                            $percent = $total ? round(($count / $total)*100) : 0;
                        @endphp
                        <div class="d-flex align-items-center mb-1">
                            <div style="width:55px;">{{ $i }} ★</div>
                            <div class="progress flex-grow-1" style="height:12px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $percent }}%;"></div>
                            </div>
                            <div class="ms-2 small text-muted">{{ $count }}</div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Right: Recent Orders -->
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header fw-bold">Recent Orders ({{ $orders->count() }})</div>
                <div class="card-body p-0">
                    @if($orders->count())
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $o)
                                    <tr>
                                        <td>{{ $o->id }}</td>
                                        <td>{{ $o->quantity }}</td>
                                        <td>₱{{ number_format($o->subtotal,2) }}</td>
                                        <td><span class="badge bg-info">{{ $o->status }}</span></td>
                                        <td>{{ $o->purchased_at?->format('M d') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="p-3 text-muted">No orders yet.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Ratings Section -->
    <div class="card mt-3">
        <div class="card-header fw-bold">Recent Ratings ({{ $ratings->count() }})</div>
        <div class="card-body p-0">
            @if($ratings->count())
                <ul class="list-group list-group-flush">
                    @foreach($ratings as $r)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>{{ $r->user?->name ?? 'User #'.$r->user_id }}</strong>
                                    <span class="ms-2 text-warning">{{ str_repeat('★', $r->rating) }}{{ str_repeat('☆', 5-$r->rating) }}</span>
                                </div>
                                <small class="text-muted">{{ $r->created_at->diffForHumans() }}</small>
                            </div>
                            @if($r->comment)
                                <div class="mt-1 text-secondary" style="white-space:pre-line;">{{ $r->comment }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="p-3 text-muted">No ratings yet.</div>
            @endif
        </div>
    </div>
</div>
@endsection
