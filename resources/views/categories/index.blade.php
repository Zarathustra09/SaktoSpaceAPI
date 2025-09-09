@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Categories</h2>
            <button class="btn btn-success" onclick="createCategory()">Create Category</button>
        </div>
        <div class="card-body">
            <table id="categoryTable" class="table table-hover table-striped">
                <thead class="thead-light">
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Products</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>{{ $category->description ?? 'N/A' }}</td>
                        <td>{{ ucfirst($category->type) }}</td>
                        <td>{{ $category->products_count }}</td>
                        <td>{{ $category->created_at->format('M d, Y') }}</td>
                        <td>
                            <button class="btn btn-info btn-sm" onclick="viewCategory({{ $category->id }})">View</button>
                            <button class="btn btn-warning btn-sm" onclick="editCategory({{ $category->id }})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteCategory({{ $category->id }})">Delete</button>
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
        $('#categoryTable').DataTable();
    });

    async function createCategory() {
        const { value: formValues } = await Swal.fire({
            title: '<h3 style="color: #2c3e50; margin-bottom: 20px;">✨ Create New Category</h3>',
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">Category Name *</label>
                        <input id="category-name" class="swal2-input" placeholder="Enter category name" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">Description</label>
                        <textarea id="category-description" class="swal2-textarea" placeholder="Enter category description" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; resize: vertical;"></textarea>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">Category Type *</label>
                        <select id="category-type" class="swal2-select" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                            <option value="">🏷️ Select Type</option>
                            <option value="furniture">🪑 Furniture</option>
                            <option value="decor">🎨 Decor</option>
                            <option value="lighting">💡 Lighting</option>
                            <option value="outdoor">🌿 Outdoor</option>
                        </select>
                    </div>
                </div>
            `,
            width: '500px',
            padding: '30px',
            background: '#ffffff',
            confirmButtonText: '✅ Create Category',
            confirmButtonColor: '#27ae60',
            cancelButtonText: '❌ Cancel',
            showCancelButton: true,
            buttonsStyling: true,
            customClass: {
                confirmButton: 'btn btn-success btn-lg',
                cancelButton: 'btn btn-secondary btn-lg'
            },
            preConfirm: () => {
                const name = document.getElementById('category-name').value;
                const description = document.getElementById('category-description').value;
                const type = document.getElementById('category-type').value;

                if (!name || !type) {
                    Swal.showValidationMessage('❗ Name and Type are required fields');
                    return false;
                }

                return {
                    name: name,
                    description: description,
                    type: type
                }
            }
        });

        if (formValues) {
            storeCategory(formValues);
        }
    }

    function storeCategory(data) {
        // Show loading
        Swal.fire({
            title: 'Creating Category...',
            html: '<div class="spinner-border text-primary" role="status"></div>',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '/categories',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                ...data
            },
            success: function(response) {
                Swal.fire({
                    title: '🎉 Success!',
                    text: 'Category has been created successfully.',
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
                        text: 'There was an error creating the category.',
                        icon: 'error',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            }
        });
    }

    function viewCategory(categoryId) {
        $.get('/categories/' + categoryId, function(category) {
            Swal.fire({
                title: `<h3 style="color: #2c3e50;">📋 ${category.name}</h3>`,
                html: `
                    <div style="text-align: left; padding: 20px; background: #f8f9fa; border-radius: 10px; margin: 20px 0;">
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #34495e;">📝 Description:</strong>
                            <p style="margin: 5px 0; color: #7f8c8d;">${category.description || 'No description provided'}</p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <strong style="color: #34495e;">🏷️ Type:</strong>
                            <span style="background: #3498db; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em; margin-left: 10px;">
                                ${category.type.charAt(0).toUpperCase() + category.type.slice(1)}
                            </span>
                        </div>

                        <div>
                            <strong style="color: #34495e;">📅 Created:</strong>
                            <span style="color: #7f8c8d; margin-left: 10px;">${new Date(category.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                        </div>
                    </div>
                `,
                icon: 'info',
                confirmButtonColor: '#3498db',
                confirmButtonText: '👍 Got it',
                width: '500px'
            });
        });
    }

    function editCategory(categoryId) {
        $.get('/categories/' + categoryId, function(category) {
            Swal.fire({
                title: `<h3 style="color: #2c3e50; margin-bottom: 20px;">✏️ Edit ${category.name}</h3>`,
                html: `
                    <div style="text-align: left; padding: 0 20px;">
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">Category Name *</label>
                            <input id="edit-name" class="swal2-input" value="${category.name}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">Description</label>
                            <textarea id="edit-description" class="swal2-textarea" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; resize: vertical;">${category.description || ''}</textarea>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">Category Type *</label>
                            <select id="edit-type" class="swal2-select" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                                <option value="furniture" ${category.type === 'furniture' ? 'selected' : ''}>🪑 Furniture</option>
                                <option value="decor" ${category.type === 'decor' ? 'selected' : ''}>🎨 Decor</option>
                                <option value="lighting" ${category.type === 'lighting' ? 'selected' : ''}>💡 Lighting</option>
                                <option value="outdoor" ${category.type === 'outdoor' ? 'selected' : ''}>🌿 Outdoor</option>
                            </select>
                        </div>
                    </div>
                `,
                width: '500px',
                padding: '30px',
                showCancelButton: true,
                confirmButtonText: '💾 Update Category',
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
                    const type = document.getElementById('edit-type').value;

                    if (!name || !type) {
                        Swal.showValidationMessage('❗ Name and Type are required fields');
                        return false;
                    }

                    return {
                        name: name,
                        description: description,
                        type: type
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Updating Category...',
                        html: '<div class="spinner-border text-warning" role="status"></div>',
                        allowOutsideClick: false,
                        showConfirmButton: false
                    });

                    $.ajax({
                        url: '/categories/' + categoryId,
                        type: 'PUT',
                        data: {
                            _token: '{{ csrf_token() }}',
                            ...result.value
                        },
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
                            Swal.fire({
                                title: '❌ Error!',
                                text: 'There was an error updating the category.',
                                icon: 'error',
                                confirmButtonColor: '#e74c3c'
                            });
                        }
                    });
                }
            });
        });
    }

    function deleteCategory(categoryId) {
        Swal.fire({
            title: '<h3 style="color: #e74c3c;">🗑️ Delete Category?</h3>',
            html: `
                <div style="padding: 20px; text-align: center;">
                    <p style="font-size: 16px; color: #7f8c8d; margin-bottom: 20px;">
                        This action cannot be undone. The category will be permanently removed.
                    </p>
                    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;">
                        <strong style="color: #856404;">⚠️ Warning:</strong>
                        <span style="color: #856404;">Categories with associated products cannot be deleted.</span>
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
                    title: 'Deleting Category...',
                    html: '<div class="spinner-border text-danger" role="status"></div>',
                    allowOutsideClick: false,
                    showConfirmButton: false
                });

                $.ajax({
                    url: '/categories/' + categoryId,
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
                            title: '❌ Cannot Delete',
                            text: response.responseJSON.error || 'Error deleting category.',
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
