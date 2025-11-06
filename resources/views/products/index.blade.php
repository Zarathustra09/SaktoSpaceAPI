@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Products</h2>
            <button class="btn btn-success" onclick="createProduct()">Create Product</button>
        </div>
        <div class="card-body">
            <table id="productTable" class="table table-hover table-striped">
                <thead class="thead-light">
                <tr>
                    <th>Images</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>AR Model</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($products as $product)
                    <tr>
                        <td>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                @if($product->image)
                                    <img src="{{ $product->image }}" alt="{{ $product->name }}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 2px solid #28a745;" title="Main Image">
                                @else
                                    <div style="width: 40px; height: 40px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #6c757d;">No Main</div>
                                @endif
                                @if($product->images->count() > 0)
                                    <span style="background: #3498db; color: white; padding: 2px 6px; border-radius: 10px; font-size: 10px;">+{{ $product->images->count() }}</span>
                                @endif
                            </div>
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ Str::limit($product->description ?? 'N/A', 50) }}</td>
                        <td>₱{{ number_format($product->price, 2) }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>{{ $product->category ? $product->category->name : 'N/A' }}</td>
                        <td>
                            @if($product->ar_model_url)
                                <span class="badge bg-success">Available</span>
                            @else
                                <span class="badge bg-secondary">None</span>
                            @endif
                        </td>
                        <td>{{ $product->created_at->format('M d, Y') }}</td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick="viewProduct({{ $product->id }})">View</button>
                            @if($product->ar_model_url)
                                <button class="btn btn-primary btn-sm" onclick="viewArModel({{ $product->id }}, '{{ $product->ar_model_url }}', '{{ $product->name }}')">🥽 AR</button>
                            @endif
                            <button class="btn btn-warning btn-sm" onclick="editProduct({{ $product->id }})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteProduct({{ $product->id }})">Delete</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection


@push('styles')
    @include('products.styles.css')
@endpush

@push('scripts')
    @include('products.scripts.crud')
    @include('products.scripts.create_wizard')
    @include('products.scripts.edit_wizard')
    @include('products.scripts.ar')
@endpush
