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
