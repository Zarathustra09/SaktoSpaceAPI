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
                    <th>Image</th>
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
                            @if($product->image)
                                <img src="{{ $product->image }}" alt="{{ $product->name }}" style="width: 50px; height: 50px; object-fit: cover;">
                            @else
                                <span class="text-muted">No Image</span>
                            @endif
                        </td>
                        <td>{{ $product->name }}</td>
                        <td>{{ Str::limit($product->description ?? 'N/A', 50) }}</td>
                        <td>${{ number_format($product->price, 2) }}</td>
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

@push('scripts')
<script>
    $(document).ready(function() {
        $('#productTable').DataTable();
    });

    async function createProduct() {
        const categoriesOptions = @json($categories->pluck('name', 'id'));
        let categoryOptionsHtml = '<option value="">🏷️ Select Category</option>';

        Object.entries(categoriesOptions).forEach(([id, name]) => {
            categoryOptionsHtml += `<option value="${id}">${name}</option>`;
        });

        const { value: formValues } = await Swal.fire({
            title: '<h3 style="color: #2c3e50; margin-bottom: 20px;">🛍️ Create New Product</h3>',
            html: `
                <div style="text-align: left; padding: 0 20px; max-height: 500px; overflow-y: auto;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📝 Product Name *</label>
                        <input id="product-name" class="swal2-input" placeholder="Enter product name" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📄 Description</label>
                        <textarea id="product-description" class="swal2-textarea" placeholder="Enter product description" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; resize: vertical;"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">💰 Price *</label>
                            <input id="product-price" class="swal2-input" type="number" step="0.01" min="0" placeholder="0.00" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📦 Stock *</label>
                            <input id="product-stock" class="swal2-input" type="number" min="0" placeholder="0" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🏷️ Category *</label>
                        <select id="product-category" class="swal2-select" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">${categoryOptionsHtml}</select>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🖼️ Product Image</label>
                        <input id="product-image" class="swal2-file" type="file" accept="image/*" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; padding: 10px;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max size: 2MB. Formats: JPG, PNG, GIF</small>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🥽 AR Model</label>
                        <input id="product-ar" class="swal2-file" type="file" accept=".glb,.gltf,.usdz" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; padding: 10px;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max size: 20MB. Formats: GLB, GLTF, USDZ</small>
                    </div>
                </div>
            `,
            width: '600px',
            padding: '30px',
            confirmButtonText: '✅ Create Product',
            confirmButtonColor: '#27ae60',
            cancelButtonText: '❌ Cancel',
            showCancelButton: true,
            customClass: {
                confirmButton: 'btn btn-success btn-lg',
                cancelButton: 'btn btn-secondary btn-lg'
            },
            preConfirm: () => {
                const name = document.getElementById('product-name').value;
                const description = document.getElementById('product-description').value;
                const price = document.getElementById('product-price').value;
                const stock = document.getElementById('product-stock').value;
                const category_id = document.getElementById('product-category').value;

                if (!name || !price || !stock || !category_id) {
                    Swal.showValidationMessage('❗ Name, Price, Stock and Category are required fields');
                    return false;
                }

                const formData = new FormData();
                formData.append('name', name);
                formData.append('description', description);
                formData.append('price', price);
                formData.append('stock', stock);
                formData.append('category_id', category_id);

                const imageFile = document.getElementById('product-image').files[0];
                if (imageFile) {
                    formData.append('image', imageFile);
                }

                const arFile = document.getElementById('product-ar').files[0];
                if (arFile) {
                    formData.append('ar_model', arFile);
                }

                return formData;
            }
        });

        if (formValues) {
            storeProduct(formValues);
        }
    }

    function storeProduct(formData) {
        formData.append('_token', '{{ csrf_token() }}');

        // Show loading
        Swal.fire({
            title: 'Creating Product...',
            html: '<div class="spinner-border text-primary" role="status"></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        $.ajax({
            url: '/products',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    title: '🎉 Success!',
                    text: 'Product has been created successfully.',
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
                        text: 'There was an error creating the product.',
                        icon: 'error',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            }
        });
    }

    function viewProduct(productId) {
        $.get('/products/' + productId, function(product) {
            const imageHtml = product.image
                ? `<div style="text-align: center; margin-bottom: 20px;">
                     <img src="${product.image}" alt="${product.name}" style="max-width: 250px; max-height: 200px; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                   </div>`
                : '<div style="text-align: center; margin-bottom: 20px; padding: 40px; background: #f8f9fa; border-radius: 10px; color: #6c757d;">📷 No Image Available</div>';

            const arBadge = product.ar_model_url
                ? '<span style="background: #28a745; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85em;">🥽 AR Available</span>'
                : '<span style="background: #6c757d; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85em;">❌ No AR Model</span>';

            Swal.fire({
                title: `<h3 style="color: #2c3e50;">🛍️ ${product.name}</h3>`,
                html: `
                    ${imageHtml}
                    <div style="text-align: left; background: #f8f9fa; padding: 25px; border-radius: 10px; margin: 20px 0;">
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #34495e;">📄 Description:</strong>
                            <p style="margin: 8px 0; color: #7f8c8d; line-height: 1.5;">${product.description || 'No description provided'}</p>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px;">
                            <div>
                                <strong style="color: #34495e;">💰 Price:</strong>
                                <p style="margin: 5px 0; font-size: 1.2em; color: #27ae60; font-weight: bold;">$${parseFloat(product.price).toFixed(2)}</p>
                            </div>
                            <div>
                                <strong style="color: #34495e;">📦 Stock:</strong>
                                <p style="margin: 5px 0; font-size: 1.1em; color: ${product.stock > 0 ? '#27ae60' : '#e74c3c'};">${product.stock} units</p>
                            </div>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <strong style="color: #34495e;">🏷️ Category:</strong>
                            <span style="background: #3498db; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em; margin-left: 10px;">
                                ${product.category ? product.category.name : 'No Category'}
                            </span>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <strong style="color: #34495e;">🥽 AR Model:</strong>
                            <div style="margin-top: 8px;">${arBadge}</div>
                        </div>

                        <div>
                            <strong style="color: #34495e;">📅 Created:</strong>
                            <span style="color: #7f8c8d; margin-left: 10px;">${new Date(product.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonColor: '#3498db',
                confirmButtonText: '👍 Got it',
                width: '600px'
            });
        });
    }

    function editProduct(productId) {
        const categoriesOptions = @json($categories->pluck('name', 'id'));

        $.get('/products/' + productId, function(product) {
            let categoryOptionsHtml = '<option value="">🏷️ Select Category</option>';
            Object.entries(categoriesOptions).forEach(([id, name]) => {
                const selected = product.category_id == id ? 'selected' : '';
                categoryOptionsHtml += `<option value="${id}" ${selected}>${name}</option>`;
            });

            Swal.fire({
                title: `<h3 style="color: #2c3e50; margin-bottom: 20px;">✏️ Edit ${product.name}</h3>`,
                html: `
                    <div style="text-align: left; padding: 0 20px; max-height: 500px; overflow-y: auto;">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📝 Product Name *</label>
                            <input id="edit-name" class="swal2-input" value="${product.name}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📄 Description</label>
                            <textarea id="edit-description" class="swal2-textarea" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; resize: vertical;">${product.description || ''}</textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">💰 Price *</label>
                                <input id="edit-price" class="swal2-input" type="number" step="0.01" min="0" value="${product.price}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📦 Stock *</label>
                                <input id="edit-stock" class="swal2-input" type="number" min="0" value="${product.stock}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🏷️ Category *</label>
                            <select id="edit-category" class="swal2-select" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">${categoryOptionsHtml}</select>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🖼️ Product Image</label>
                            <input id="edit-image" class="swal2-file" type="file" accept="image/*" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; padding: 10px;">
                            <small style="color: #7f8c8d; font-size: 12px;">Leave empty to keep current image</small>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🥽 AR Model</label>
                            <input id="edit-ar" class="swal2-file" type="file" accept=".glb,.gltf,.usdz" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; padding: 10px;">
                            <small style="color: #7f8c8d; font-size: 12px;">Leave empty to keep current AR model</small>
                        </div>
                    </div>
                `,
                width: '600px',
                padding: '30px',
                showCancelButton: true,
                confirmButtonText: '💾 Update Product',
                confirmButtonColor: '#f39c12',
                cancelButtonText: '❌ Cancel',
                cancelButtonColor: '#95a5a6',
                customClass: {
                    confirmButton: 'btn btn-warning btn-lg',
                    cancelButton: 'btn btn-secondary btn-lg'
                },
                preConfirm: () => {
                    const name = document.getElementById('edit-name').value;
                    const description = document.getElementById('edit-description').value;
                    const price = document.getElementById('edit-price').value;
                    const stock = document.getElementById('edit-stock').value;
                    const category_id = document.getElementById('edit-category').value;

                    if (!name || !price || !stock || !category_id) {
                        Swal.showValidationMessage('❗ Name, Price, Stock and Category are required fields');
                        return false;
                    }

                    const formData = new FormData();
                    formData.append('_method', 'PUT');
                    formData.append('name', name);
                    formData.append('description', description);
                    formData.append('price', price);
                    formData.append('stock', stock);
                    formData.append('category_id', category_id);

                    const imageFile = document.getElementById('edit-image').files[0];
                    if (imageFile) {
                        formData.append('image', imageFile);
                    }

                    const arFile = document.getElementById('edit-ar').files[0];
                    if (arFile) {
                        formData.append('ar_model', arFile);
                    }

                    return formData;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updateProduct(productId, result.value);
                }
            });
        });
    }

    function updateProduct(productId, formData) {
        formData.append('_token', '{{ csrf_token() }}');

        // Show loading
        Swal.fire({
            title: 'Updating Product...',
            html: '<div class="spinner-border text-warning" role="status"></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        $.ajax({
            url: '/products/' + productId,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.fire({
                    title: '🎉 Updated!',
                    text: response.success,
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
                        text: 'There was an error updating the product.',
                        icon: 'error',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            }
        });
    }

    function deleteProduct(productId) {
        Swal.fire({
            title: '<h3 style="color: #e74c3c;">🗑️ Delete Product?</h3>',
            html: `
                <div style="padding: 20px; text-align: center;">
                    <p style="font-size: 16px; color: #7f8c8d; margin-bottom: 20px;">
                        This action cannot be undone. The product and all its files will be permanently removed.
                    </p>
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <strong style="color: #856404;">⚠️ Warning:</strong>
                        <span style="color: #856404;">This will also delete associated images and AR models.</span>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6',
            confirmButtonText: '🗑️ Yes, delete it!',
            cancelButtonText: '❌ Cancel',
            width: '500px',
            customClass: {
                confirmButton: 'btn btn-danger btn-lg',
                cancelButton: 'btn btn-secondary btn-lg'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting Product...',
                    html: '<div class="spinner-border text-danger" role="status"></div>',
                    allowOutsideClick: false,
                    showConfirmButton: false
                });

                $.ajax({
                    url: '/products/' + productId,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            title: '🎉 Deleted!',
                            text: response.success,
                            icon: 'success',
                            confirmButtonColor: '#27ae60',
                            confirmButtonText: '👍 Great!'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(response) {
                        Swal.fire({
                            title: '❌ Error!',
                            text: 'Error deleting product.',
                            icon: 'error',
                            confirmButtonColor: '#e74c3c',
                            confirmButtonText: '👍 Understood'
                        });
                    }
                });
            }
        });
    }
</script>
@endpush
