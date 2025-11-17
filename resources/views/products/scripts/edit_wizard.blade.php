<script>
    let editWizardData = {
        productId: null,
        data: {},
        croppieInstance: null,
        croppedImageBlob: null,
        arFile: null,
        currentImage: null,
        currentArModel: null,
        additionalImages: [],
        currentAdditionalImages: [], // Ensure this is always initialized as array
        deletedImageIds: []
    };

    async function startEditWizard() {
        try {
            // Step 1: Product Details
            const step1Result = await showEditProductDetailsStep();
            if (!step1Result.isConfirmed) return;

            // Step 2: Main Image Upload & Cropping
            const step2Result = await showEditImageUploadStep();
            if (!step2Result.isConfirmed) return;

            // Step 3: Additional Product Images
            const step3Result = await showEditAdditionalImagesStep();
            if (!step3Result.isConfirmed) return;

            // Step 4: AR Model Upload
            const step4Result = await showEditArModelStep();
            if (!step4Result.isConfirmed) return;

            // Step 5: Review & Submit
            const step5Result = await showEditReviewStep();
            if (step5Result.isConfirmed) {
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
                    document.getElementById('edit-product-category').value = editWizardData.data
                        .category_id;
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

                editWizardData.data = {
                    name,
                    description,
                    price,
                    stock,
                    category_id
                };
                return true;
            }
        });
    }

    function showEditImageUploadStep() {
        const currentImageHtml = editWizardData.currentImage ?
            `<div style="margin-bottom: 15px; text-align: center;">
                <p style="margin-bottom: 10px; font-weight: 600; color: #34495e;">Current Main Image:</p>
                <img src="${editWizardData.currentImage}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #dee2e6;">
               </div>` :
            '<p style="color: #6c757d; text-align: center; margin-bottom: 15px;">No current main image</p>';

        return Queue.fire({
            title: '🖼️ Edit Main Product Image',
            currentProgressStep: 1,
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    ${currentImageHtml}
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🖼️ Upload New Main Image</label>
                        <input id="edit-image-upload" type="file" accept="image/*" style="margin-bottom: 15px; padding: 10px; border: 2px solid #e8f4f8; border-radius: 8px; width: 100%;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max size: 2MB. Formats: JPG, PNG. Leave empty to keep current image.</small>
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
                            <p style="color: #28a745; margin-bottom: 15px;"><strong>✅ New Main Image Ready!</strong></p>
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
                // Always allow proceeding - if no new image, keep current
                return true;
            },
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    }

    // NEW STEP: Additional Product Images
    function showEditAdditionalImagesStep() {
        // Ensure currentAdditionalImages is always an array
        if (!Array.isArray(editWizardData.currentAdditionalImages)) {
            editWizardData.currentAdditionalImages = [];
        }

        const currentImagesHtml = editWizardData.currentAdditionalImages.length > 0 ?
            `<div style="margin-bottom: 20px;">
                <p style="margin-bottom: 10px; font-weight: 600; color: #34495e;">Current Additional Images:</p>
                <div id="current-additional-images" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px;">
                    ${editWizardData.currentAdditionalImages.map(img => `
                        <div style="position: relative; border: 2px solid #dee2e6; border-radius: 8px; overflow: hidden;" data-image-id="${img.id}">
                            <img src="${img.url}" style="width: 100%; height: 120px; object-fit: cover;">
                            <button type="button" onclick="removeCurrentAdditionalImage(${img.id})" style="position: absolute; top: 5px; right: 5px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; font-size: 12px; cursor: pointer;">×</button>
                        </div>
                    `).join('')}
                </div>
               </div>` :
            '<p style="color: #6c757d; margin-bottom: 15px;">No current additional images</p>';

        return Queue.fire({
            title: '📸 Edit Additional Product Images',
            currentProgressStep: 2,
            width: '800px',
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    ${currentImagesHtml}
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">📸 Add New Additional Images</label>
                        <input id="edit-additional-images-upload" type="file" accept="image/*" multiple style="margin-bottom: 15px; padding: 10px; border: 2px solid #e8f4f8; border-radius: 8px; width: 100%;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max 5 additional images total. Max size: 2MB each. Formats: JPG, PNG. Leave empty to keep current images.</small>
                    </div>
                    <div id="edit-additional-images-preview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; margin-top: 20px;">
                        <!-- New additional images will be displayed here -->
                    </div>
                </div>
            `,
            didOpen: () => {
                initializeEditAdditionalImages();
                displayEditAdditionalImages();
            },
            preConfirm: () => {
                // Ensure arrays exist before accessing length
                const currentImagesCount = (editWizardData.currentAdditionalImages || []).filter(img => !(
                    editWizardData.deletedImageIds || []).includes(img.id)).length;
                const newImagesCount = (editWizardData.additionalImages || []).length;
                const totalImages = currentImagesCount + newImagesCount;

                if (totalImages > 5) {
                    Swal.showValidationMessage('You can only have a maximum of 5 additional images total.');
                    return false;
                }
                return true;
            },
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    }

    function showEditArModelStep() {
        const currentArHtml = editWizardData.currentArModel ?
            `<div style="margin-bottom: 15px; text-align: center; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 2px solid #28a745;">
                <p style="margin-bottom: 5px; font-weight: 600; color: #34495e;">Current AR Model:</p>
                <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">✅ Available</span>
               </div>` :
            '<p style="color: #6c757d; text-align: center; margin-bottom: 15px;">No current AR model</p>';

        return Queue.fire({
            title: '🥽 Edit AR Model',
            currentProgressStep: 3,
            html: `
                <div style="text-align: left; padding: 0 20px;">
                    ${currentArHtml}
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #34495e;">🥽 Upload New AR Model File</label>
                        <input id="edit-ar-upload" type="file" accept=".glb,.gltf,.usdz" style="margin-bottom: 15px; padding: 10px; border: 2px solid #e8f4f8; border-radius: 8px; width: 100%;">
                        <small style="color: #7f8c8d; font-size: 12px;">Max size: 100MB. Formats: GLB, GLTF, USDZ. Leave empty to keep current AR model.</small>
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
                        if (file.size > 100 * 1024 * 1024) {
                            Swal.showValidationMessage('AR model size must be less than 100MB');
                            arUpload.value = '';
                            return;
                        }
                        editWizardData.arFile = file;
                        arFileName.textContent =
                            `File: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                        arFileInfo.style.display = 'block';
                    }
                });
            },
            preConfirm: () => {
                // Always allow proceeding - if no new AR file, keep current
                return true;
            },
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    }

    function showEditReviewStep() {
        let imagePreview;
        if (editWizardData.croppedImageBlob) {
            imagePreview =
                `<img src="${URL.createObjectURL(editWizardData.croppedImageBlob)}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #28a745;">`;
        } else if (editWizardData.currentImage) {
            imagePreview =
                `<img src="${editWizardData.currentImage}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #dee2e6;">`;
        } else {
            imagePreview =
                '<div style="width: 100px; height: 100px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6c757d;">No Image</div>';
        }

        // Calculate remaining additional images after deletions with safe array access
        const currentImages = editWizardData.currentAdditionalImages || [];
        const deletedIds = editWizardData.deletedImageIds || [];
        const additionalImages = editWizardData.additionalImages || [];

        const remainingCurrentImages = currentImages.filter(img => !deletedIds.includes(img.id));
        const totalAdditionalImages = remainingCurrentImages.length + additionalImages.length;

        const additionalImagesPreview = totalAdditionalImages > 0 ?
            `<div style="margin-top: 15px;">
                <strong>📸 Additional Images (${totalAdditionalImages}):</strong>
                <div style="display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap;">
                    ${remainingCurrentImages.map(img =>
                        `<div style="position: relative;">
                            <img src="${img.url}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6;">
                            <span style="position: absolute; bottom: -5px; right: -5px; background: #6c757d; color: white; border-radius: 50%; width: 15px; height: 15px; font-size: 8px; display: flex; align-items: center; justify-content: center;">📁</span>
                        </div>`
                    ).join('')}
                    ${additionalImages.map(img =>
                        `<div style="position: relative;">
                            <img src="${URL.createObjectURL(img)}" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #28a745;">
                            <span style="position: absolute; bottom: -5px; right: -5px; background: #28a745; color: white; border-radius: 50%; width: 15px; height: 15px; font-size: 8px; display: flex; align-items: center; justify-content: center;">✅</span>
                        </div>`
                    ).join('')}
                </div>
                ${deletedIds.length > 0 ? `<p style="color: #e74c3c; font-size: 0.9em; margin-top: 10px;">⚠️ ${deletedIds.length} image(s) will be deleted</p>` : ''}
               </div>` :
            '<div style="margin-top: 15px;"><strong>📸 Additional Images:</strong> <span style="color: #6c757d;">None</span></div>';

        let arInfo;
        if (editWizardData.arFile) {
            arInfo =
                `<span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">✅ ${editWizardData.arFile.name}</span>`;
        } else if (editWizardData.currentArModel) {
            arInfo =
                '<span style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">📁 Current AR Model</span>';
        } else {
            arInfo =
                '<span style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.9em;">❌ No AR Model</span>';
        }

        return Queue.fire({
            title: '📋 Review Changes',
            currentProgressStep: 4,
            confirmButtonText: '💾 Update Product',
            confirmButtonColor: '#f39c12',
            showCancelButton: false,
            showDenyButton: true,
            denyButtonText: '← Go Back',
            width: '800px',
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
                            <div style="margin-bottom: 15px;"><strong>🥽 AR Model:</strong> ${arInfo}</div>
                            ${additionalImagesPreview}
                        </div>
                        <div style="text-align: center;">
                            <div style="margin-bottom: 10px;"><strong>🖼️ Main Image</strong></div>
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
                        viewport: {
                            width: 300,
                            height: 300,
                            type: 'square'
                        },
                        boundary: {
                            width: 350,
                            height: 350
                        },
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
                    size: {
                        width: 500,
                        height: 500
                    },
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
            formData.append('image', editWizardData.croppedImageBlob, 'cropped_image.png');
        }

        // Add new additional images
        if (editWizardData.additionalImages.length > 0) {
            console.log('New additional images found:', editWizardData.additionalImages.length);
            editWizardData.additionalImages.forEach((file, index) => {
                formData.append(`additional_images[${index}]`, file);
            });
        }

        // Add deleted image IDs
        if (editWizardData.deletedImageIds.length > 0) {
            console.log('Images to delete:', editWizardData.deletedImageIds);
            editWizardData.deletedImageIds.forEach((id, index) => {
                formData.append(`deleted_image_ids[${index}]`, id);
            });
        }

        if (editWizardData.arFile) {
            console.log('New AR file found:', editWizardData.arFile);
            formData.append('ar_model', editWizardData.arFile);
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

        // Show loading with progress bar
        Swal.fire({
            title: 'Uploading Changes...',
            html: `
                <div style="text-align:left; padding: 20px;">
                    <div id="upload-progress-container" style="width:100%; margin-top:10px;">
                        <div style="background:#e9ecef; border-radius:6px; overflow:hidden; height:20px;">
                            <div id="upload-progress-bar" style="height:100%; width:0%; background:linear-gradient(90deg, #f39c12, #f1c40f); transition: width 0.3s ease;"></div>
                        </div>
                        <div id="upload-progress-text" style="margin-top:12px; font-size:14px; text-align:center; color:#34495e; font-weight:600;">Preparing upload... 0%</div>
                    </div>
                </div>
            `,
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
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            const bar = document.getElementById('upload-progress-bar');
                            const text = document.getElementById('upload-progress-text');
                            if (bar) bar.style.width = percentComplete + '%';
                            if (text) text.textContent = `Uploading... ${percentComplete}%`;
                        }
                    }, false);
                    return xhr;
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

    function initializeEditAdditionalImages() {
        const additionalImagesUpload = document.getElementById('edit-additional-images-upload');

        additionalImagesUpload.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);

            // Ensure arrays exist before calculating
            const currentImages = editWizardData.currentAdditionalImages || [];
            const deletedIds = editWizardData.deletedImageIds || [];
            const additionalImages = editWizardData.additionalImages || [];

            const remainingCurrentImages = currentImages.filter(img => !deletedIds.includes(img.id)).length;
            const totalAfterUpload = remainingCurrentImages + additionalImages.length + files.length;

            if (totalAfterUpload > 5) {
                Swal.showValidationMessage('You can only have a maximum of 5 additional images total');
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

                // Ensure additionalImages array exists
                if (!editWizardData.additionalImages) {
                    editWizardData.additionalImages = [];
                }
                editWizardData.additionalImages.push(file);
            });

            displayEditAdditionalImages();
            additionalImagesUpload.value = '';
        });
    }

    function displayEditAdditionalImages() {
        const container = document.getElementById('edit-additional-images-preview');

        // Ensure additionalImages array exists
        const additionalImages = editWizardData.additionalImages || [];

        container.innerHTML = additionalImages.map((file, index) => `
            <div style="position: relative; border: 2px solid #28a745; border-radius: 8px; overflow: hidden;">
                <img src="${URL.createObjectURL(file)}" style="width: 100%; height: 150px; object-fit: cover;">
                <button type="button" onclick="removeNewAdditionalImage(${index})" style="position: absolute; top: 5px; right: 5px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; font-size: 12px; cursor: pointer;">×</button>
                <div style="background: rgba(40, 167, 69, 0.9); color: white; padding: 5px; font-size: 12px; text-align: center;">
                    NEW: ${file.name.length > 12 ? file.name.substring(0, 12) + '...' : file.name}
                </div>
            </div>
        `).join('');
    }

    function removeCurrentAdditionalImage(imageId) {
        // Ensure deletedImageIds array exists
        if (!editWizardData.deletedImageIds) {
            editWizardData.deletedImageIds = [];
        }

        editWizardData.deletedImageIds.push(imageId);
        const imageElement = document.querySelector(`[data-image-id="${imageId}"]`);
        if (imageElement) {
            imageElement.style.opacity = '0.5';
            imageElement.style.filter = 'grayscale(100%)';
            const button = imageElement.querySelector('button');
            button.innerHTML = '↻';
            button.onclick = () => restoreCurrentAdditionalImage(imageId);
        }
    }

    function restoreCurrentAdditionalImage(imageId) {
        // Ensure deletedImageIds array exists
        if (!editWizardData.deletedImageIds) {
            editWizardData.deletedImageIds = [];
        }

        const index = editWizardData.deletedImageIds.indexOf(imageId);
        if (index > -1) {
            editWizardData.deletedImageIds.splice(index, 1);
        }
        const imageElement = document.querySelector(`[data-image-id="${imageId}"]`);
        if (imageElement) {
            imageElement.style.opacity = '1';
            imageElement.style.filter = 'none';
            const button = imageElement.querySelector('button');
            button.innerHTML = '×';
            button.onclick = () => removeCurrentAdditionalImage(imageId);
        }
    }

    function removeNewAdditionalImage(index) {
        // Ensure additionalImages array exists
        if (!editWizardData.additionalImages) {
            editWizardData.additionalImages = [];
        }

        editWizardData.additionalImages.splice(index, 1);
        displayEditAdditionalImages();
    }
</script>
