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


@push('scripts')
<script>
    $(document).ready(function() {
        $('#productTable').DataTable();
    });

    let productWizardData = {
        data: {},
        croppieInstance: null,
        croppedImageBlob: null,
        arFile: null
    };

    let editWizardData = {
        productId: null,
        data: {},
        croppieInstance: null,
        croppedImageBlob: null,
        arFile: null,
        currentImage: null,
        currentArModel: null
    };

    const steps = ['📝', '🖼️', '🥽', '📋'];
    const Queue = Swal.mixin({
        progressSteps: steps,
        confirmButtonText: 'Next >',
        showCancelButton: true,
        cancelButtonText: 'Cancel',
        showClass: { backdrop: 'swal2-noanimation' },
        hideClass: { backdrop: 'swal2-noanimation' },
        allowOutsideClick: false,
        allowEscapeKey: false
    });

    async function createProduct() {
        productWizardData = { data: {}, croppieInstance: null, croppedImageBlob: null, arFile: null };

        try {
            // Step 1: Product Details
            const step1Result = await showProductDetailsStep();
            if (!step1Result.isConfirmed) return;

            // Step 2: Image Upload & Cropping
            const step2Result = await showImageUploadStep();
            if (!step2Result.isConfirmed) return;

            // Step 3: AR Model Upload
            const step3Result = await showArModelStep();
            if (!step3Result.isConfirmed) return;

            // Step 4: Review & Submit
            const step4Result = await showReviewStep();
            if (step4Result.isConfirmed) {
                await submitProduct();
            }
        } catch (error) {
            console.error('Product creation cancelled or error occurred:', error);
        }
    }

    function showProductDetailsStep() {
        const categoriesOptions = @json($categories->pluck('name', 'id'));
        let categoryOptionsHtml = '<option value="">🏷️ Select Category</option>';

        Object.entries(categoriesOptions).forEach(([id, name]) => {
            categoryOptionsHtml += `<option value="${id}">${name}</option>`;
        });

        return Queue.fire({
            title: '📝 Product Details',
            currentProgressStep: 0,
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📝 Product Name *</label>
                        <input id="product-name" class="swal2-input" placeholder="Enter product name" value="${productWizardData.data.name || ''}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📄 Description</label>
                        <textarea id="product-description" class="swal2-textarea" placeholder="Enter product description" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; resize: vertical;">${productWizardData.data.description || ''}</textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">💰 Price *</label>
                            <input id="product-price" class="swal2-input" type="number" step="0.01" min="0" placeholder="0.00" value="${productWizardData.data.price || ''}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📦 Stock *</label>
                            <input id="product-stock" class="swal2-input" type="number" min="0" placeholder="0" value="${productWizardData.data.stock || ''}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🏷️ Category *</label>
                        <select id="product-category" class="swal2-select" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">${categoryOptionsHtml}</select>
                    </div>
                </div>
            `,
            didOpen: () => {
                if (productWizardData.data.category_id) {
                    document.getElementById('product-category').value = productWizardData.data.category_id;
                }
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

                productWizardData.data = { name, description, price, stock, category_id };
                return true;
            }
        });
    }

    function showImageUploadStep() {
        return Queue.fire({
            title: '🖼️ Product Image',
            currentProgressStep: 1,
            showDenyButton: true,
            denyButtonText: 'Skip Image',
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🖼️ Select Image</label>
                        <input id="image-upload" type="file" accept="image/*" style="margin-bottom: 15px; padding: 10px; border: 2px solid #e8f4f8; border-radius: 8px; width: 100%;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max size: 2MB. Formats: JPG, PNG. Image will be cropped to 500x500 pixels.</small>
                    </div>
                    <div id="crop-container" style="display: none; margin-top: 20px;">
                        <div id="croppie-container" style="width: 100%; height: 400px; border: 2px solid #e8f4f8; border-radius: 8px;"></div>
                        <div style="text-align: center; margin-top: 15px;">
                            <button type="button" id="crop-image" class="btn btn-success">✂️ Crop Image</button>
                            <button type="button" id="reset-crop" class="btn btn-warning">🔄 Reset</button>
                        </div>
                    </div>
                    <div id="cropped-preview" style="display: none; text-align: center; margin-top: 20px;">
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px solid #28a745;">
                            <p style="color: #28a745; margin-bottom: 15px;"><strong>✅ Image Ready!</strong></p>
                            <img id="preview-img" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #28a745;">
                            <div style="margin-top: 15px;">
                                <button type="button" id="change-image" class="btn btn-warning btn-sm">🔄 Change Image</button>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            didOpen: () => {
                initializeCroppie();
            },
            preConfirm: () => {
                if (!productWizardData.croppedImageBlob) {
                    Swal.showValidationMessage('Please upload and crop an image, or click "Skip Image" to continue without one.');
                    return false;
                }
                return true;
            },
            preDeny: () => {
                productWizardData.croppedImageBlob = null;
                return true;
            }
        });
    }

    function showArModelStep() {
        return Queue.fire({
            title: '🥽 AR Model',
            currentProgressStep: 2,
            showDenyButton: true,
            denyButtonText: 'Skip AR Model',
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🥽 AR Model File</label>
                        <input id="ar-upload" type="file" accept=".glb,.gltf,.usdz" style="margin-bottom: 15px; padding: 10px; border: 2px solid #e8f4f8; border-radius: 8px; width: 100%;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max size: 20MB. Formats: GLB, GLTF, USDZ. This step is optional.</small>
                    </div>
                    <div id="ar-file-info" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 2px solid #28a745; margin-top: 15px;">
                        <p style="color: #28a745; margin: 0;"><strong>✅ AR Model Selected!</strong></p>
                        <p id="ar-file-name" style="margin: 5px 0 0 0; color: #6c757d;"></p>
                    </div>
                </div>
            `,
            didOpen: () => {
                const arUpload = document.getElementById('ar-upload');
                const arFileInfo = document.getElementById('ar-file-info');
                const arFileName = document.getElementById('ar-file-name');

                arUpload.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        if (file.size > 20 * 1024 * 1024) {
                            Swal.showValidationMessage('AR model size must be less than 20MB');
                            arUpload.value = '';
                            return;
                        }
                        productWizardData.arFile = file;
                        arFileName.textContent = `File: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                        arFileInfo.style.display = 'block';
                    }
                });
            },
            preConfirm: () => {
                if (!productWizardData.arFile) {
                    Swal.showValidationMessage('Please upload an AR model file, or click "Skip AR Model" to continue without one.');
                    return false;
                }
                return true;
            },
            preDeny: () => {
                productWizardData.arFile = null;
                return true;
            }
        });
    }

    function showReviewStep() {
        const imagePreview = productWizardData.croppedImageBlob
            ? `<img src="${URL.createObjectURL(productWizardData.croppedImageBlob)}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #28a745;">`
            : '<div style="width: 100px; height: 100px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6c757d;">No Image</div>';

        const arInfo = productWizardData.arFile
            ? `<span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">✅ ${productWizardData.arFile.name}</span>`
            : '<span style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">❌ No AR Model</span>';

        return Queue.fire({
            title: '📋 Review & Create',
            currentProgressStep: 3,
            confirmButtonText: '✅ Create Product',
            confirmButtonColor: '#28a745',
            showCancelButton: false,
            showDenyButton: true,
            denyButtonText: '← Go Back',
            html: `
                <div style="text-align: left; background: #f8f9fa; padding: 25px; border-radius: 10px; margin: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: start; margin-bottom: 20px;">
                        <div>
                            <h4 style="color: #2c3e50; margin-bottom: 15px;">📝 ${productWizardData.data.name}</h4>
                            <p style="color: #7f8c8d; margin-bottom: 10px;">${productWizardData.data.description || 'No description'}</p>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div><strong>💰 Price:</strong> ₱${parseFloat(productWizardData.data.price).toFixed(2)}</div>
                                <div><strong>📦 Stock:</strong> ${productWizardData.data.stock} units</div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>🏷️ Category:</strong>
                                <span style="background: #3498db; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em; margin-left: 10px;">
                                    ${@json($categories->pluck('name', 'id'))[productWizardData.data.category_id]}
                                </span>
                            </div>
                            <div><strong>🥽 AR Model:</strong> ${arInfo}</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="margin-bottom: 10px;"><strong>🖼️ Image Preview</strong></div>
                            ${imagePreview}
                        </div>
                    </div>
                </div>
            `,
            preDeny: () => {
                // Go back to previous step
                throw new Error('Go back');
            }
        });
    }

    function initializeCroppie() {
        const imageUpload = document.getElementById('image-upload');
        const cropContainer = document.getElementById('crop-container');
        const croppieContainer = document.getElementById('croppie-container');
        const croppedPreview = document.getElementById('cropped-preview');
        const previewImg = document.getElementById('preview-img');

        imageUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    Swal.showValidationMessage('Image size must be less than 2MB');
                    imageUpload.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    cropContainer.style.display = 'block';
                    croppedPreview.style.display = 'none';

                    if (productWizardData.croppieInstance) {
                        productWizardData.croppieInstance.destroy();
                    }

                    productWizardData.croppieInstance = new Croppie(croppieContainer, {
                        viewport: { width: 300, height: 300, type: 'square' },
                        boundary: { width: 350, height: 350 },
                        showZoomer: true,
                        enableResize: false,
                        enableOrientation: true
                    });

                    productWizardData.croppieInstance.bind({
                        url: event.target.result
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('crop-image').addEventListener('click', function() {
            if (productWizardData.croppieInstance) {
                productWizardData.croppieInstance.result({
                    type: 'blob',
                    size: { width: 500, height: 500 },
                    format: 'png',
                    quality: 0.9
                }).then(function(blob) {
                    productWizardData.croppedImageBlob = blob;

                    const url = URL.createObjectURL(blob);
                    previewImg.src = url;

                    cropContainer.style.display = 'none';
                    croppedPreview.style.display = 'block';
                });
            }
        });

        document.getElementById('reset-crop').addEventListener('click', function() {
            if (productWizardData.croppieInstance) {
                productWizardData.croppieInstance.destroy();
                productWizardData.croppieInstance = null;
            }
            cropContainer.style.display = 'none';
            imageUpload.value = '';
        });

        document.getElementById('change-image').addEventListener('click', function() {
            croppedPreview.style.display = 'none';
            imageUpload.value = '';
            productWizardData.croppedImageBlob = null;
            if (productWizardData.croppieInstance) {
                productWizardData.croppieInstance.destroy();
                productWizardData.croppieInstance = null;
            }
        });
    }

    async function submitProduct() {
        console.log('=== SUBMIT PRODUCT DEBUG ===');
        console.log('Product wizard data:', productWizardData);

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('name', productWizardData.data.name);
        formData.append('description', productWizardData.data.description);
        formData.append('price', productWizardData.data.price);
        formData.append('stock', productWizardData.data.stock);
        formData.append('category_id', productWizardData.data.category_id);

        // Fix typo: croppiedImageBlob -> croppedImageBlob
        if (productWizardData.croppedImageBlob) {
            console.log('Image blob found:', productWizardData.croppedImageBlob);
            console.log('Image blob size:', productWizardData.croppedImageBlob.size);
            console.log('Image blob type:', productWizardData.croppedImageBlob.type);
            formData.append('image', productWizardData.croppedImageBlob, 'cropped_image.png');
        } else {
            console.log('No image blob found');
        }

        if (productWizardData.arFile) {
            console.log('AR file found:', productWizardData.arFile);
            console.log('AR file size:', productWizardData.arFile.size);
            console.log('AR file name:', productWizardData.arFile.name);
            formData.append('ar_model', productWizardData.arFile);
        } else {
            console.log('No AR file found');
        }

        // Log all FormData entries
        console.log('FormData entries:');
        for (let [key, value] of formData.entries()) {
            if (value instanceof File || value instanceof Blob) {
                console.log(`${key}:`, {
                    name: value.name || 'blob',
                    size: value.size,
                    type: value.type
                });
            } else {
                console.log(`${key}:`, value);
            }
        }

        // Show loading
        Swal.fire({
            title: 'Creating Product...',
            html: '<div class="spinner-border text-primary" role="status"></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        try {
            console.log('Sending AJAX request to /products');
            const response = await $.ajax({
                url: '/products',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    console.log('Request headers:', xhr.getAllResponseHeaders());
                }
            });

            console.log('Success response:', response);

            await Swal.fire({
                title: '🎉 Success!',
                text: 'Product has been created successfully.',
                icon: 'success',
                confirmButtonColor: '#27ae60',
                confirmButtonText: '👍 Great!'
            });

            location.reload();
        } catch (response) {
            console.error('Error response:', response);
            console.error('Error status:', response.status);
            console.error('Error response text:', response.responseText);

            if (response.status === 422) {
                let errors = response.responseJSON.errors;
                console.error('Validation errors:', errors);
                let errorMessages = '';
                for (let field in errors) {
                    errorMessages += `• ${errors[field].join(', ')}<br>`;
                }
                await Swal.fire({
                    title: '❌ Validation Error',
                    html: `<div style="text-align: left; color: #e74c3c;">${errorMessages}</div>`,
                    icon: 'error',
                    confirmButtonColor: '#e74c3c'
                });
            } else {
                await Swal.fire({
                    title: '❌ Error!',
                    text: 'There was an error creating the product.',
                    icon: 'error',
                    confirmButtonColor: '#e74c3c'
                });
            }
        }
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
                                <p style="margin: 5px 0; font-size: 1.2em; color: #27ae60; font-weight: bold;">₱${parseFloat(product.price).toFixed(2)}</p>
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
        editWizardData = {
            productId: productId,
            data: {},
            croppieInstance: null,
            croppedImageBlob: null,
            arFile: null,
            currentImage: null,
            currentArModel: null
        };

        // First, get the product data
        $.get('/products/' + productId, function(product) {
            editWizardData.data = {
                name: product.name,
                description: product.description || '',
                price: product.price,
                stock: product.stock,
                category_id: product.category_id
            };
            editWizardData.currentImage = product.image;
            editWizardData.currentArModel = product.ar_model_url;

            startEditWizard();
        });
    }

    async function startEditWizard() {
        try {
            // Step 1: Product Details
            const step1Result = await showEditProductDetailsStep();
            if (!step1Result.isConfirmed) return;

            // Step 2: Image Upload & Cropping
            const step2Result = await showEditImageUploadStep();
            if (!step2Result.isConfirmed && !step2Result.isDenied) return;

            // Step 3: AR Model Upload
            const step3Result = await showEditArModelStep();
            if (!step3Result.isConfirmed && !step3Result.isDenied) return;

            // Step 4: Review & Submit
            const step4Result = await showEditReviewStep();
            if (step4Result.isConfirmed) {
                await submitEditProduct();
            }
        } catch (error) {
            console.error('Product edit cancelled or error occurred:', error);
        }
    }

    function showEditProductDetailsStep() {
        const categoriesOptions = @json($categories->pluck('name', 'id'));
        let categoryOptionsHtml = '<option value="">🏷️ Select Category</option>';

        Object.entries(categoriesOptions).forEach(([id, name]) => {
            categoryOptionsHtml += `<option value="${id}">${name}</option>`;
        });

        return Queue.fire({
            title: '📝 Edit Product Details',
            currentProgressStep: 0,
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📝 Product Name *</label>
                        <input id="edit-product-name" class="swal2-input" placeholder="Enter product name" value="${editWizardData.data.name || ''}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📄 Description</label>
                        <textarea id="edit-product-description" class="swal2-textarea" placeholder="Enter product description" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8; resize: vertical;">${editWizardData.data.description || ''}</textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">💰 Price *</label>
                            <input id="edit-product-price" class="swal2-input" type="number" step="0.01" min="0" placeholder="0.00" value="${editWizardData.data.price || ''}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📦 Stock *</label>
                            <input id="edit-product-stock" class="swal2-input" type="number" min="0" placeholder="0" value="${editWizardData.data.stock || ''}" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🏷️ Category *</label>
                        <select id="edit-product-category" class="swal2-select" style="margin: 0; border-radius: 8px; border: 2px solid #e8f4f8;">${categoryOptionsHtml}</select>
                    </div>
                </div>
            `,
            didOpen: () => {
                if (editWizardData.data.category_id) {
                    document.getElementById('edit-product-category').value = editWizardData.data.category_id;
                }
            },
            preConfirm: () => {
                const name = document.getElementById('edit-product-name').value;
                const description = document.getElementById('edit-product-description').value;
                const price = document.getElementById('edit-product-price').value;
                const stock = document.getElementById('edit-product-stock').value;
                const category_id = document.getElementById('edit-product-category').value;

                if (!name || !price || !stock || !category_id) {
                    Swal.showValidationMessage('❗ Name, Price, Stock and Category are required fields');
                    return false;
                }

                editWizardData.data = { name, description, price, stock, category_id };
                return true;
            }
        });
    }

    function showEditImageUploadStep() {
        const currentImageHtml = editWizardData.currentImage
            ? `<div style="margin-bottom: 15px; text-align: center;">
                <p style="margin-bottom: 10px; font-weight: 600; color: #34495e;">Current Image:</p>
                <img src="${editWizardData.currentImage}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #dee2e6;">
               </div>`
            : '<p style="color: #6c757d; text-align: center; margin-bottom: 15px;">No current image</p>';

        return Queue.fire({
            title: '🖼️ Edit Product Image',
            currentProgressStep: 1,
            showDenyButton: true,
            denyButtonText: 'Keep Current',
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    ${currentImageHtml}
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🖼️ Upload New Image</label>
                        <input id="edit-image-upload" type="file" accept="image/*" style="margin-bottom: 15px; padding: 10px; border: 2px solid #e8f4f8; border-radius: 8px; width: 100%;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max size: 2MB. Formats: JPG, PNG. Image will be cropped to 500x500 pixels. Leave empty to keep current image.</small>
                    </div>
                    <div id="edit-crop-container" style="display: none; margin-top: 20px;">
                        <div id="edit-croppie-container" style="width: 100%; height: 400px; border: 2px solid #e8f4f8; border-radius: 8px;"></div>
                        <div style="text-align: center; margin-top: 15px;">
                            <button type="button" id="edit-crop-image" class="btn btn-success">✂️ Crop Image</button>
                            <button type="button" id="edit-reset-crop" class="btn btn-warning">🔄 Reset</button>
                        </div>
                    </div>
                    <div id="edit-cropped-preview" style="display: none; text-align: center; margin-top: 20px;">
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px solid #28a745;">
                            <p style="color: #28a745; margin-bottom: 15px;"><strong>✅ New Image Ready!</strong></p>
                            <img id="edit-preview-img" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #28a745;">
                            <div style="margin-top: 15px;">
                                <button type="button" id="edit-change-image" class="btn btn-warning btn-sm">🔄 Change Image</button>
                            </div>
                        </div>
                    </div>
                </div>
            `,
            didOpen: () => {
                initializeEditCroppie();
            },
            preConfirm: () => {
                if (!editWizardData.croppedImageBlob) {
                    Swal.showValidationMessage('Please upload and crop a new image, or click "Keep Current" to maintain the existing image.');
                    return false;
                }
                return true;
            },
            preDeny: () => {
                // Clear any new image data when keeping current
                editWizardData.croppedImageBlob = null;
                if (editWizardData.croppieInstance) {
                    editWizardData.croppieInstance.destroy();
                    editWizardData.croppieInstance = null;
                }
                return true;
            }
        });
    }

    function showEditArModelStep() {
        const currentArHtml = editWizardData.currentArModel
            ? `<div style="margin-bottom: 15px; text-align: center; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 2px solid #28a745;">
                <p style="margin-bottom: 5px; font-weight: 600; color: #34495e;">Current AR Model:</p>
                <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">✅ Available</span>
               </div>`
            : '<p style="color: #6c757d; text-align: center; margin-bottom: 15px;">No current AR model</p>';

        return Queue.fire({
            title: '🥽 Edit AR Model',
            currentProgressStep: 2,
            showDenyButton: true,
            denyButtonText: 'Keep Current',
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    ${currentArHtml}
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🥽 Upload New AR Model File</label>
                        <input id="edit-ar-upload" type="file" accept=".glb,.gltf,.usdz" style="margin-bottom: 15px; padding: 10px; border: 2px solid #e8f4f8; border-radius: 8px; width: 100%;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max size: 20MB. Formats: GLB, GLTF, USDZ. Leave empty to keep current AR model.</small>
                    </div>
                    <div id="edit-ar-file-info" style="display: none; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 2px solid #28a745; margin-top: 15px;">
                        <p style="color: #28a745; margin: 0;"><strong>✅ New AR Model Selected!</strong></p>
                        <p id="edit-ar-file-name" style="margin: 5px 0 0 0; color: #6c757d;"></p>
                    </div>
                </div>
            `,
            didOpen: () => {
                const arUpload = document.getElementById('edit-ar-upload');
                const arFileInfo = document.getElementById('edit-ar-file-info');
                const arFileName = document.getElementById('edit-ar-file-name');

                arUpload.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        if (file.size > 20 * 1024 * 1024) {
                            Swal.showValidationMessage('AR model size must be less than 20MB');
                            arUpload.value = '';
                            return;
                        }
                        editWizardData.arFile = file;
                        arFileName.textContent = `File: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                        arFileInfo.style.display = 'block';
                    }
                });
            },
            preConfirm: () => {
                if (!editWizardData.arFile) {
                    Swal.showValidationMessage('Please upload a new AR model file, or click "Keep Current" to maintain the existing AR model.');
                    return false;
                }
                return true;
            },
            preDeny: () => {
                // Clear any new AR file data when keeping current
                editWizardData.arFile = null;
                return true;
            }
        });
    }

    function showEditReviewStep() {
        let imagePreview;
        if (editWizardData.croppedImageBlob) {
            imagePreview = `<img src="${URL.createObjectURL(editWizardData.croppedImageBlob)}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #28a745;">`;
        } else if (editWizardData.currentImage) {
            imagePreview = `<img src="${editWizardData.currentImage}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #dee2e6;">`;
        } else {
            imagePreview = '<div style="width: 100px; height: 100px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6c757d;">No Image</div>';
        }

        let arInfo;
        if (editWizardData.arFile) {
            arInfo = `<span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">✅ ${editWizardData.arFile.name}</span>`;
        } else if (editWizardData.currentArModel) {
            arInfo = '<span style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">📁 Current AR Model</span>';
        } else {
            arInfo = '<span style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">❌ No AR Model</span>';
        }

        return Queue.fire({
            title: '📋 Review Changes',
            currentProgressStep: 3,
            confirmButtonText: '💾 Update Product',
            confirmButtonColor: '#f39c12',
            showCancelButton: false,
            showDenyButton: true,
            denyButtonText: '← Go Back',
            html: `
                <div style="text-align: left; background: #f8f9fa; padding: 25px; border-radius: 10px; margin: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: start; margin-bottom: 20px;">
                        <div>
                            <h4 style="color: #2c3e50; margin-bottom: 15px;">📝 ${editWizardData.data.name}</h4>
                            <p style="color: #7f8c8d; margin-bottom: 10px;">${editWizardData.data.description || 'No description'}</p>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div><strong>💰 Price:</strong> ₱${parseFloat(editWizardData.data.price).toFixed(2)}</div>
                                <div><strong>📦 Stock:</strong> ${editWizardData.data.stock} units</div>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong>🏷️ Category:</strong>
                                <span style="background: #3498db; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em; margin-left: 10px;">
                                    ${@json($categories->pluck('name', 'id'))[editWizardData.data.category_id]}
                                </span>
                            </div>
                            <div><strong>🥽 AR Model:</strong> ${arInfo}</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="margin-bottom: 10px;"><strong>🖼️ Image Preview</strong></div>
                            ${imagePreview}
                            ${editWizardData.croppedImageBlob ? '<p style="color: #28a745; font-size: 0.8em; margin-top: 5px;">New Image</p>' : ''}
                        </div>
                    </div>
                </div>
            `,
            preDeny: () => {
                // Go back to previous step
                throw new Error('Go back');
            }
        });
    }

    function initializeEditCroppie() {
        const imageUpload = document.getElementById('edit-image-upload');
        const cropContainer = document.getElementById('edit-crop-container');
        const croppieContainer = document.getElementById('edit-croppie-container');
        const croppedPreview = document.getElementById('edit-cropped-preview');
        const previewImg = document.getElementById('edit-preview-img');

        imageUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    Swal.showValidationMessage('Image size must be less than 2MB');
                    imageUpload.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    cropContainer.style.display = 'block';
                    croppedPreview.style.display = 'none';

                    if (editWizardData.croppieInstance) {
                        editWizardData.croppieInstance.destroy();
                    }

                    editWizardData.croppieInstance = new Croppie(croppieContainer, {
                        viewport: { width: 300, height: 300, type: 'square' },
                        boundary: { width: 350, height: 350 },
                        showZoomer: true,
                        enableResize: false,
                        enableOrientation: true
                    });

                    editWizardData.croppieInstance.bind({
                        url: event.target.result
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('edit-crop-image').addEventListener('click', function() {
            if (editWizardData.croppieInstance) {
                editWizardData.croppieInstance.result({
                    type: 'blob',
                    size: { width: 500, height: 500 },
                    format: 'png',
                    quality: 0.9
                }).then(function(blob) {
                    editWizardData.croppedImageBlob = blob;

                    const url = URL.createObjectURL(blob);
                    previewImg.src = url;

                    cropContainer.style.display = 'none';
                    croppedPreview.style.display = 'block';
                });
            }
        });

        document.getElementById('edit-reset-crop').addEventListener('click', function() {
            if (editWizardData.croppieInstance) {
                editWizardData.croppieInstance.destroy();
                editWizardData.croppieInstance = null;
            }
            cropContainer.style.display = 'none';
            imageUpload.value = '';
        });

        document.getElementById('edit-change-image').addEventListener('click', function() {
            croppedPreview.style.display = 'none';
            imageUpload.value = '';
            editWizardData.croppedImageBlob = null;
            if (editWizardData.croppieInstance) {
                editWizardData.croppieInstance.destroy();
                editWizardData.croppieInstance = null;
            }
        });
    }

    async function submitEditProduct() {
        console.log('=== SUBMIT EDIT PRODUCT DEBUG ===');
        console.log('Edit wizard data:', editWizardData);

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('_method', 'PUT');
        formData.append('name', editWizardData.data.name);
        formData.append('description', editWizardData.data.description);
        formData.append('price', editWizardData.data.price);
        formData.append('stock', editWizardData.data.stock);
        formData.append('category_id', editWizardData.data.category_id);

        if (editWizardData.croppedImageBlob) {
            console.log('New image blob found:', editWizardData.croppedImageBlob);
            console.log('Image blob size:', editWizardData.croppedImageBlob.size);
            console.log('Image blob type:', editWizardData.croppedImageBlob.type);
            formData.append('image', editWizardData.croppedImageBlob, 'cropped_image.png');
        } else {
            console.log('No new image blob - keeping current image');
        }

        if (editWizardData.arFile) {
            console.log('New AR file found:', editWizardData.arFile);
            console.log('AR file size:', editWizardData.arFile.size);
            console.log('AR file name:', editWizardData.arFile.name);
            formData.append('ar_model', editWizardData.arFile);
        } else {
            console.log('No new AR file - keeping current AR model');
        }

        // Log all FormData entries
        console.log('FormData entries:');
        for (let [key, value] of formData.entries()) {
            if (value instanceof File || value instanceof Blob) {
                console.log(`${key}:`, {
                    name: value.name || 'blob',
                    size: value.size,
                    type: value.type
                });
            } else {
                console.log(`${key}:`, value);
            }
        }

        // Show loading
        Swal.fire({
            title: 'Updating Product...',
            html: '<div class="spinner-border text-warning" role="status"></div>',
            allowOutsideClick: false,
            showConfirmButton: false
        });

        try {
            console.log('Sending AJAX request to /products/' + editWizardData.productId);
            const response = await $.ajax({
                url: '/products/' + editWizardData.productId,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    console.log('Request headers:', xhr.getAllResponseHeaders());
                }
            });

            console.log('Success response:', response);

            await Swal.fire({
                title: '🎉 Updated!',
                text: 'Product has been updated successfully.',
                icon: 'success',
                confirmButtonColor: '#27ae60',
                confirmButtonText: '👍 Great!'
            });

            location.reload();
        } catch (response) {
            console.error('Error response:', response);
            console.error('Error status:', response.status);
            console.error('Error response text:', response.responseText);

            if (response.status === 422) {
                let errors = response.responseJSON.errors;
                console.error('Validation errors:', errors);
                let errorMessages = '';
                for (let field in errors) {
                    errorMessages += `• ${errors[field].join(', ')}<br>`;
                }
                await Swal.fire({
                    title: '❌ Validation Error',
                    html: `<div style="text-align: left; color: #e74c3c;">${errorMessages}</div>`,
                    icon: 'error',
                    confirmButtonColor: '#e74c3c'
                });
            } else {
                await Swal.fire({
                    title: '❌ Error!',
                    text: 'There was an error updating the product.',
                    icon: 'error',
                    confirmButtonColor: '#e74c3c'
                });
            }
        }
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

    function viewArModel(productId, arModelUrl, productName) {
        Swal.fire({
            title: `<h3 style="color: #2c3e50;">🥽 AR Model - ${productName}</h3>`,
            html: `
                <div style="padding: 20px;">
                    <div style="margin-bottom: 20px; text-align: center;">
                        <p style="color: #7f8c8d; margin-bottom: 15px;">
                            Use your mouse to rotate, zoom, and interact with the 3D model.
                        </p>
                        <div style="background: #e8f4f8; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                            <small style="color: #2c3e50;">
                                📱 <strong>Mobile tip:</strong> Use touch gestures to rotate and pinch to zoom<br>
                                🖥️ <strong>Desktop tip:</strong> Click and drag to rotate, scroll to zoom
                            </small>
                        </div>
                    </div>

                    <div style="width: 100%; height: 500px; border: 2px solid #e8f4f8; border-radius: 10px; overflow: hidden; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <model-viewer
                            id="ar-model-viewer"
                            src="${arModelUrl}"
                            alt="3D model of ${productName}"
                            auto-rotate
                            camera-controls
                            interaction-policy="always-allow"
                            style="width: 100%; height: 100%;"
                            loading="eager"
                            reveal="auto"
                            environment-image="neutral"
                            shadow-intensity="1"
                            camera-orbit="0deg 75deg 105%"
                            min-camera-orbit="auto auto auto"
                            max-camera-orbit="auto auto auto"
                            min-field-of-view="30deg"
                            max-field-of-view="120deg">

                            <!-- Loading indicator -->
                            <div slot="poster" style="display: flex; align-items: center; justify-content: center; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <div style="text-align: center;">
                                    <div class="spinner-border text-light mb-3" role="status"></div>
                                    <p>Loading 3D Model...</p>
                                </div>
                            </div>

                            <!-- Error fallback -->
                            <div slot="error" style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f8f9fa; color: #6c757d;">
                                <div style="text-align: center;">
                                    <h4>❌ Unable to load 3D model</h4>
                                    <p>The AR model file may be corrupted or incompatible.</p>
                                </div>
                            </div>
                        </model-viewer>
                    </div>

                    <div style="margin-top: 20px; text-align: center;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 15px;">
                            <button type="button" id="reset-camera" class="btn btn-outline-primary btn-sm">
                                📷 Reset View
                            </button>
                            <button type="button" id="toggle-autorotate" class="btn btn-outline-secondary btn-sm">
                                🔄 Toggle Rotation
                            </button>
                            <button type="button" id="fullscreen-model" class="btn btn-outline-success btn-sm">
                                🔍 Fullscreen
                            </button>
                        </div>
                        <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 8px;">
                            <small style="color: #6c757d;">
                                <strong>Controls:</strong> Left click + drag to rotate • Right click + drag to pan • Scroll to zoom
                            </small>
                        </div>
                    </div>
                </div>
            `,
            width: '800px',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                popup: 'ar-model-popup'
            },
            didOpen: () => {
                initializeArModelControls();
            },
            willClose: () => {
                // Clean up model viewer if needed
                const modelViewer = document.getElementById('ar-model-viewer');
                if (modelViewer) {
                    modelViewer.autoRotate = false;
                }
            }
        });
    }

    function initializeArModelControls() {
        const modelViewer = document.getElementById('ar-model-viewer');

        if (!modelViewer) return;

        // Reset camera button
        document.getElementById('reset-camera').addEventListener('click', function() {
            modelViewer.cameraOrbit = '0deg 75deg 105%';
            modelViewer.fieldOfView = '45deg';
        });

        // Toggle auto-rotate button
        document.getElementById('toggle-autorotate').addEventListener('click', function() {
            modelViewer.autoRotate = !modelViewer.autoRotate;
            this.textContent = modelViewer.autoRotate ? '⏸️ Stop Rotation' : '🔄 Start Rotation';
            this.classList.toggle('btn-outline-warning', modelViewer.autoRotate);
            this.classList.toggle('btn-outline-secondary', !modelViewer.autoRotate);
        });

        // Fullscreen button
        document.getElementById('fullscreen-model').addEventListener('click', function() {
            if (modelViewer.requestFullscreen) {
                modelViewer.requestFullscreen();
            } else if (modelViewer.webkitRequestFullscreen) {
                modelViewer.webkitRequestFullscreen();
            } else if (modelViewer.msRequestFullscreen) {
                modelViewer.msRequestFullscreen();
            }
        });

        // Model viewer event listeners
        modelViewer.addEventListener('load', function() {
            console.log('3D model loaded successfully');
        });

        modelViewer.addEventListener('error', function(event) {
            console.error('Error loading 3D model:', event.detail);
        });

        modelViewer.addEventListener('camera-change', function(event) {
            // Optional: Log camera changes for debugging
            // console.log('Camera changed:', event.detail);
        });

        // Progress indicator
        modelViewer.addEventListener('progress', function(event) {
            const progress = event.detail.totalProgress;
            if (progress < 1) {
                console.log(`Loading progress: ${Math.round(progress * 100)}%`);
            }
        });
    }
</script>

<style>
.ar-model-popup .swal2-popup {
    padding: 0;
}

.ar-model-popup .swal2-html-container {
    padding: 0;
    margin: 0;
}

/* Custom styles for model viewer */
model-viewer {
    --poster-color: transparent;
    --progress-bar-color: #007bff;
    --progress-bar-height: 4px;
}

model-viewer::part(default-progress-bar) {
    background-color: #007bff;
    height: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .ar-model-popup {
        width: 95% !important;
    }

    .ar-model-popup model-viewer {
        height: 400px !important;
    }
}
</style>
@endpush
