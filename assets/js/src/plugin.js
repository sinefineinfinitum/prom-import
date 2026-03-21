(function($) {
    "use strict";

    /**
     * REST API configuration
     */
    const RestAPI = {
        namespace: 'spss12-prom-import/v1',
        namespaceV2: 'spss12-prom-import/v2',
        endpoints: {
            product: '/import/product',
            updatePrices: '/import/update-prices',
            imports: '/imports'
        },

        /**
         * Get full REST API URL
         */
        getUrl(endpoint, version = 'v1') {
            const ns = version === 'v2' ? this.namespaceV2 : this.namespace;
            return `${sinefinePromimportAjax.rest_url}${ns}${endpoint}`;
        },

        /**
         * Get nonce for REST API requests
         */
        getNonce() {
            return sinefinePromimportAjax.rest_nonce || wp.apiFetch?.nonceMiddleware?.nonce;
        },

        /**
         * Make a REST API request
         */
        async request(endpoint, data = {}, method = 'POST', isJsonResponse = true, version = 'v1' ) {
            try {
                const url = this.getUrl(endpoint, version);
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.getNonce()
                    }
                };

                if (method !== 'GET' && method !== 'HEAD') {
                    options.body = JSON.stringify(data);
                }

                const response = await fetch(url, options);

                const responseBody = isJsonResponse ? await response.json() : await response.text();

                if (!response.ok) {
                    throw new Error(responseBody.message || 'Request failed');
                }

                return responseBody;
            } catch (error) {
                console.error('REST API Error:', error);
                throw error;
            }
        }
    };

    /**
     * Import single product via REST API
     */
    async function import_product(
        nonce,
        product_id,
        product_title,
        product_description,
        product_price,
        product_category,
        product_featured_media
    ) {
        try {
            // Parse media URLs if it's a JSON string
            let mediaUrls = product_featured_media;
            if (typeof mediaUrls === 'string' && mediaUrls.trim() !== '') {
                try {
                    mediaUrls = JSON.parse(mediaUrls);
                } catch (e) {
                    mediaUrls = [mediaUrls];
                }
            }

            const data = {
                product_id: parseInt(product_id, 10),
                product_title: product_title,
                product_description: product_description || '',
                product_price: parseFloat(product_price) || 0,
                product_category: parseInt(product_category, 10) || 0,
                product_featured_media: Array.isArray(mediaUrls) ? mediaUrls : []
            };

            const response = await RestAPI.request(RestAPI.endpoints.product, data);

            if (response.success && response.data?.edit_url) {
                window.open(response.data.edit_url, '_blank');
                return response;
            } else {
                throw new Error(response.message || 'Import failed');
            }
        } catch (error) {
            alert(error.message || sinefinePromimportAjax.error_text || 'An error occurred');
            console.error('Product import error:', error);
            throw error;
        }
    }


    /**
     * Update all product prices via REST API
     */
    async function update_prices(nonce) {
        try {
            const response = await RestAPI.request(RestAPI.endpoints.updatePrices, {}, 'POST', true);

            if (response.success) {
                alert(response.message || 'Prices updated successfully');
                location.reload();
                return response;
            } else {
                throw new Error(response.message || 'Update failed');
            }
        } catch (error) {
            alert(error.message || sinefinePromimportAjax.error_text || 'An error occurred');
            console.error('Update prices error:', error);
            throw error;
        }
    }

    /**
     * CRUD for Imports (v2)
     */
    async function get_imports() {
        return await RestAPI.request(RestAPI.endpoints.imports, {}, 'GET', true, 'v2');
    }

    async function create_import(name, url) {
        return await RestAPI.request(RestAPI.endpoints.imports, { name, url }, 'POST', true, 'v2');
    }

    async function update_import(id, name, url) {
        return await RestAPI.request(`${RestAPI.endpoints.imports}/${id}`, { name, url }, 'PATCH', true, 'v2');
    }

    async function delete_import(id) {
        return await RestAPI.request(`${RestAPI.endpoints.imports}/${id}`, {}, 'DELETE', true, 'v2');
    }

    async function run_import(id) {
        return await RestAPI.request(`${RestAPI.endpoints.imports}/${id}/run`, {}, 'POST', true, 'v2');
    }


    async function update_import_mapping(id, mapping) {
        return await RestAPI.request(`${RestAPI.endpoints.imports}/${id}/mapping`, { mapping }, 'PATCH', true, 'v2');
    }

    /**
     * Show admin notice (if wp.notices available)
     */
    function showNotice(message, type = 'success') {
        // Try wp.data notices (Gutenberg)
        if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch('core/notices')) {
            wp.data.dispatch('core/notices').createNotice(type, message, {
                isDismissible: true,
            });
        } else {
            // Fallback: show browser alert
            alert(`${type.toUpperCase()}: ${message}`);
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    /**
     * Event Handlers
     */

    // Import single product button
    $('.import-product').on('click', async function(event) {
        event.preventDefault();

        const $btn = $(this);
        const originalText = $btn.text();

        // Disable button and show loading state
        $btn.prop('disabled', true).text(sinefinePromimportAjax.loading_text || 'Importing...');

        try {
            await import_product(
                $btn.attr('data-nonce'),
                $btn.attr('data-id'),
                $btn.attr('data-title'),
                $btn.attr('data-description'),
                $btn.attr('data-price'),
                $btn.attr('data-category'),
                $btn.attr('data-featured-media')
            );

            // Success: update button state
            $btn
                .text(sinefinePromimportAjax.imported_text || 'Imported')
                .removeClass('import-product')
                .addClass('button-disabled')
                .attr('data-nonce', '');
        } catch (error) {
            // Error: restore button
            $btn.prop('disabled', false).text(originalText);
        }
    });

    // Update prices
    $('#update-prices').on('click', async function(event) {
        event.preventDefault();

        const $btn = $(this);
        const originalText = $btn.text();

        // Disable button and show loading state
        $btn.prop('disabled', true).text(sinefinePromimportAjax.loading_text || 'Updating...');

        try {
            await update_prices($btn.attr('data-nonce'));
        } catch (error) {
            // Error: restore button
            $btn.prop('disabled', false).text(originalText);
        }
    });

    /**
     * Imports Management UI
     */
    const $modal = $('#import-modal');
    const $form = $('#import-form');

    $('#open-create-import-modal').on('click', function() {
        $('#modal-title').text(sinefinePromimportAjax.add_new_text || 'Add New Import');
        $form[0].reset();
        $('#import-id').val('');
        $('#mapping-container').hide();
        $modal.show();
    });

    $('#close-modal').on('click', function() {
        $modal.hide();
    });

    // Run import
    $('.run-import').on('click', async function() {
        const id = $(this).data('id');
        const $btn = $(this);
        const originalText = $btn.text();

        if (!confirm(sinefinePromimportAjax.confirm_run_text || 'Start import process?')) {
            return;
        }

        $btn.prop('disabled', true).text(sinefinePromimportAjax.running_text || 'Running...');

        try {
            const result = await run_import(id);
            alert((sinefinePromimportAjax.imported_text || 'Added to the queue Import with ID:') + ' ' + result.import_id);
            location.reload();
        } catch (error) {
            alert(error.message);
            $btn.prop('disabled', false).text(originalText);
        }
    });

    // Delete import
    $('.delete-import').on('click', async function() {
        if (!confirm(sinefinePromimportAjax.confirm_delete_text || 'Are you sure you want to delete this import?')) {
            return;
        }

        const id = $(this).data('id');
        const $row = $(this).closest('tr');

        try {
            await delete_import(id);
            $row.fadeOut(300, function() {
                $(this).remove();
                if ($('#imports-table tbody tr').length === 0) {
                    $('#imports-table tbody').append('<tr><td colspan="5">' + (sinefinePromimportAjax.no_imports_text || 'No imports found') + '</td></tr>');
                }
            });
        } catch (error) {
            alert(error.message);
        }
    });

    // Form submission
    $form.on('submit', async function(e) {
        e.preventDefault();
        const name = $('#import-name-input').val();
        const url = $('#import-url-input').val();
        const $btn = $('#save-import-btn');
        const originalText = $btn.text();

        $btn.prop('disabled', true).text(sinefinePromimportAjax.saving_text || 'Saving...');

        try {
            await create_import(name, url);
            $modal.hide();
            location.reload(); // Refresh to show changes in table
        } catch (error) {
            alert(error.message);
            $btn.prop('disabled', false).text(originalText);
        }
    });

    /**
     * Expose API for external usage (optional)
     */
    window.sinefinePromimporter = {
        RestAPI,
        importProduct: import_product,
        updatePrices: update_prices,
        getImports: get_imports,
        createImport: create_import,
        updateImport: update_import,
        deleteImport: delete_import,
    };

    /**
     * Edit Import Page Logic
     */
    $('#edit-import-form').on('submit', async function(e) {
        e.preventDefault();
        console.log('Edit Import Form Submitted');
        const id = $('#import-id').val();
        const name = $('#import-name').val();
        const url = $('#import-url').val();
        const $btn = $('#save-edit-import');
        const $spinner = $('.spinner');
        const originalText = $btn.text();

        // Collect mapping
        const mapping = {};
        $('.category-select').each(function() {
            const externalId = $(this).data('external-id');
            const val = $(this).val();
            if (val && val !== '0') {
                mapping[externalId] = val;
            }
        });

        $btn.prop('disabled', true).text(sinefinePromimportAjax.saving_text || 'Saving...');
        $spinner.addClass('is-active');

        try {
            await update_import(id, name, url);
            await update_import_mapping(id, mapping);
            
            showNotice(sinefinePromimportAjax.saved_text || 'Import settings saved successfully', 'success');
            
            // Optionally redirect back or stay on page
            // window.location.href = 'admin.php?page=prom-imports';
        } catch (error) {
            alert(error.message);
        } finally {
            $btn.prop('disabled', false).text(originalText);
            $spinner.removeClass('is-active');
        }
    });

    console.log('Prom Importer REST API initialized');

})(jQuery);
