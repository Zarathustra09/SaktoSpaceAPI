<script>

    $(document).ready(function() {
        $('#productTable').DataTable();
    });

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

    function viewProduct(productId) {
        $.get('/products/' + productId, function(product) {
            // Create main image display
            const mainImageHtml = product.image
                ? `<div style="text-align: center; margin-bottom: 20px;">
                     <img id="main-product-image" src="${product.image}" alt="${product.name}" style="max-width: 250px; max-height: 200px; object-fit: cover; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); cursor: pointer;" onclick="expandImage('${product.image}', '${product.name} - Main Image')">
                     <p style="font-size: 12px; color: #6c757d; margin-top: 5px;">📷 Main Image (click to expand)</p>
                   </div>`
                : '<div style="text-align: center; margin-bottom: 20px; padding: 40px; background: #f8f9fa; border-radius: 10px; color: #6c757d;">📷 No Main Image Available</div>';

            // Create additional images gallery
            let additionalImagesHtml = '';
            if (product.images && product.images.length > 0) {
                additionalImagesHtml = `
                    <div style="margin-bottom: 20px;">
                        <strong style="color: #34495e;">📸 Additional Images (${product.images.length}):</strong>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; margin-top: 10px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            ${product.images.map((img, index) => `
                                <div style="position: relative; border: 2px solid #dee2e6; border-radius: 8px; overflow: hidden; transition: all 0.3s ease;">
                                    <img src="${img.url}" alt="${img.alt_text || product.name + ' - Image ' + (index + 1)}"
                                         style="width: 100%; height: 80px; object-fit: cover; cursor: pointer;"
                                         onclick="expandImage('${img.url}', '${img.alt_text || product.name + ' - Image ' + (index + 1)}')">
                                </div>
                            `).join('')}
                        </div>
                        <p style="font-size: 12px; color: #6c757d; margin-top: 5px; text-align: center;">💡 Click any image to expand</p>
                    </div>
                `;
            } else {
                additionalImagesHtml = `
                    <div style="margin-bottom: 20px;">
                        <strong style="color: #34495e;">📸 Additional Images:</strong>
                        <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; margin-top: 10px; color: #6c757d;">
                            No additional images available
                        </div>
                    </div>
                `;
            }

            const arBadge = product.ar_model_url
                ? '<span style="background: #28a745; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85em;">🥽 AR Available</span>'
                : '<span style="background: #6c757d; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85em;">❌ No AR Model</span>';

            Swal.fire({
                title: `<h3 style="color: #2c3e50;">🛍️ ${product.name}</h3>`,
                html: `
                    ${mainImageHtml}
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

                        ${additionalImagesHtml}

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
                width: '700px',
                customClass: {
                    popup: 'product-view-popup'
                }
            });
        });
    }

    // Function to expand images in a larger view
    function expandImage(imageUrl, altText) {
        Swal.fire({
            title: altText,
            imageUrl: imageUrl,
            imageAlt: altText,
            imageWidth: '80%',
            imageHeight: 'auto',
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                image: 'expanded-product-image'
            },
            didOpen: () => {
                // Add some styling for the expanded image
                const style = document.createElement('style');
                style.textContent = `
                    .expanded-product-image {
                        border-radius: 10px;
                        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
                        max-height: 80vh;
                        object-fit: contain;
                    }
                    .product-view-popup .swal2-popup {
                        max-height: 90vh;
                        overflow-y: auto;
                    }
                `;
                document.head.appendChild(style);
            }
        });
    }

    async function editProduct(productId) {
        try {
            // Show loading
            Swal.fire({
                title: 'Loading Product...',
                html: '<div class="spinner-border text-primary" role="status"></div>',
                allowOutsideClick: false,
                showConfirmButton: false
            });

            // Fetch product data
            const response = await $.ajax({
                url: `/products/${productId}`,
                type: 'GET'
            });

            console.log('Product data loaded:', response);

            // Initialize edit wizard data
            editWizardData = {
                productId: productId,
                data: {
                    name: response.name,
                    description: response.description,
                    price: response.price,
                    stock: response.stock,
                    category_id: response.category_id
                },
                croppieInstance: null,
                croppedImageBlob: null,
                arFile: null,
                currentImage: response.image,
                currentArModel: response.ar_model_url,
                additionalImages: [], // New images to add
                currentAdditionalImages: response.images || [], // Existing images from server
                deletedImageIds: [] // Images to delete
            };

            console.log('Edit wizard data initialized:', editWizardData);

            // Close loading and start wizard
            Swal.close();
            await startEditWizard();

        } catch (error) {
            console.error('Error loading product:', error);
            await Swal.fire({
                title: '❌ Error!',
                text: 'Failed to load product data.',
                icon: 'error',
                confirmButtonColor: '#e74c3c'
            });
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
</script>
