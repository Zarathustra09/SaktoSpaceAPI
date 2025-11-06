<script>
    let productWizardData = {
        data: {},
        croppieInstance: null,
        croppedImageBlob: null,
        arFile: null,
        additionalImages: [] // New property for additional images
    };


    async function createProduct() {
        productWizardData = { data: {}, croppieInstance: null, croppedImageBlob: null, arFile: null, additionalImages: [] };

        try {
            // Step 1: Product Details
            const step1Result = await showProductDetailsStep();
            if (!step1Result.isConfirmed) return;

            // Step 2: Main Image Upload & Cropping
            const step2Result = await showImageUploadStep();
            if (!step2Result.isConfirmed) return;

            // Step 3: Additional Product Images (NEW STEP)
            const step3Result = await showAdditionalImagesStep();
            if (!step3Result.isConfirmed) return;

            // Step 4: AR Model Upload
            const step4Result = await showArModelStep();
            if (!step4Result.isConfirmed) return;

            // Step 5: Review & Submit
            const step5Result = await showReviewStep();
            if (step5Result.isConfirmed) {
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
            title: '🖼️ Main Product Image',
            currentProgressStep: 1,
            showDenyButton: true,
            denyButtonText: 'Skip Main Image',
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🖼️ Select Main Image</label>
                        <input id="image-upload" type="file" accept="image/*" style="margin-bottom: 15px; padding: 10px; border: 2px solid #e8f4f8; border-radius: 8px; width: 100%;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max size: 2MB. Formats: JPG, PNG. This will be the primary product image displayed in listings.</small>
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
                            <p style="color: #28a745; margin-bottom: 15px;"><strong>✅ Main Image Ready!</strong></p>
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
                    Swal.showValidationMessage('Please upload and crop a main image, or click "Skip Main Image" to continue without one.');
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

    // NEW STEP: Additional Product Images
    function showAdditionalImagesStep() {
        return Queue.fire({
            title: '📸 Additional Product Images',
            currentProgressStep: 2,
            showDenyButton: true,
            denyButtonText: 'Skip Additional Images',
            width: '800px',
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📸 Add More Product Images</label>
                        <input id="additional-images-upload" type="file" accept="image/*" multiple style="margin-bottom: 15px; padding: 10px; border: 2px solid #e8f4f8; border-radius: 8px; width: 100%;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max 5 additional images. Max size: 2MB each. Formats: JPG, PNG. These will be shown in the product gallery.</small>
                    </div>
                    <div id="additional-images-preview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 20px;">
                        <!-- Additional images will be displayed here -->
                    </div>
                </div>
            `,
            didOpen: () => {
                initializeAdditionalImages();
                displayAdditionalImages();
            },
            preConfirm: () => {
                return true; // Always allow proceeding since this step is optional
            },
            preDeny: () => {
                productWizardData.additionalImages = [];
                return true;
            }
        });
    }

    function showArModelStep() {
        return Queue.fire({
            title: '🥽 AR Model',
            currentProgressStep: 3,
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

        const additionalImagesPreview = productWizardData.additionalImages.length > 0
            ? `<div style="margin-top: 15px;">
                <strong>📸 Additional Images (${productWizardData.additionalImages.length}):</strong>
                <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                    ${productWizardData.additionalImages.map(img =>
                        `<img src="${URL.createObjectURL(img)}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #28a745;">`
                    ).join('')}
                </div>
               </div>`
            : '<div style="margin-top: 15px;"><strong>📸 Additional Images:</strong> <span style="color: #6c757d;">None</span></div>';

        const arInfo = productWizardData.arFile
            ? `<span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">✅ ${productWizardData.arFile.name}</span>`
            : '<span style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">❌ No AR Model</span>';

        return Queue.fire({
            title: '📋 Review & Create',
            currentProgressStep: 4,
            confirmButtonText: '✅ Create Product',
            confirmButtonColor: '#28a745',
            showCancelButton: false,
            showDenyButton: true,
            denyButtonText: '← Go Back',
            width: '800px',
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
                            ${additionalImagesPreview}
                        </div>
                        <div style="text-align: center;">
                            <div style="margin-bottom: 10px;"><strong>🖼️ Main Image</strong></div>
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

        if (productWizardData.croppedImageBlob) {
            console.log('Main image blob found:', productWizardData.croppedImageBlob);
            formData.append('image', productWizardData.croppedImageBlob, 'cropped_image.png');
        }

        // Add additional images
        if (productWizardData.additionalImages.length > 0) {
            console.log('Additional images found:', productWizardData.additionalImages.length);
            productWizardData.additionalImages.forEach((file, index) => {
                formData.append(`additional_images[${index}]`, file);
            });
        }

        if (productWizardData.arFile) {
            console.log('AR file found:', productWizardData.arFile);
            formData.append('ar_model', productWizardData.arFile);
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
                contentType: false
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

    function initializeAdditionalImages() {
        const additionalImagesUpload = document.getElementById('additional-images-upload');

        additionalImagesUpload.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);

            if (productWizardData.additionalImages.length + files.length > 5) {
                Swal.showValidationMessage('You can only upload a maximum of 5 additional images');
                return;
            }

            files.forEach(file => {
                if (file.size > 2 * 1024 * 1024) {
                    Swal.showValidationMessage(`Image "${file.name}" is too large. Max size is 2MB.`);
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    Swal.showValidationMessage(`"${file.name}" is not a valid image file.`);
                    return;
                }

                productWizardData.additionalImages.push(file);
            });

            displayAdditionalImages();
            additionalImagesUpload.value = '';
        });
    }

    function displayAdditionalImages() {
        const container = document.getElementById('additional-images-preview');

        container.innerHTML = productWizardData.additionalImages.map((file, index) => `
            <div style="position: relative; border: 2px solid #28a745; border-radius: 8px; overflow: hidden;">
                <img src="${URL.createObjectURL(file)}" style="width: 100%; height: 150px; object-fit: cover;">
                <button type="button" onclick="removeAdditionalImage(${index})" style="position: absolute; top: 5px; right: 5px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; font-size: 12px; cursor: pointer;">×</button>
                <div style="background: rgba(0,0,0,0.7); color: white; padding: 5px; font-size: 12px; text-align: center;">
                    ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                </div>
            </div>
        `).join('');
    }

    function removeAdditionalImage(index) {
        productWizardData.additionalImages.splice(index, 1);
        displayAdditionalImages();
    }
</script>
