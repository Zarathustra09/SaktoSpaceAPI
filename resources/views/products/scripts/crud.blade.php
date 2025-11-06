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
