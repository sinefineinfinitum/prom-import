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
            categories: '/import/categories',
            config: '/import/config',
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
     * Import categories mapping via REST API
     */
    async function import_categories(nonce, btn) {
        try {
            const mappings = [];

            // Iterate each row in the categories table
            $('#categories-table tbody tr').each(function(index, row) {
                const $row = $(row);
                // Column 1 contains XML category id inside span
                const xmlId = $.trim($row.find('td').eq(0).find('span').text());
                // The select is in the 3rd column
                const $select = $row.find('td').eq(2).find('select');
                const selectedValue = $select.val();

                if (xmlId !== '' && typeof selectedValue !== 'undefined') {
                    mappings.push({
                        id: xmlId,          // Prom XML category id
                        selected: selectedValue // Woo category (slug by current dropdown config)
                    });
                }
            });


            if (mappings.length === 0) {
                alert(sinefinePromimportAjax.no_categories_text || 'No categories selected');
                return;
            }

            const response = await RestAPI.request(RestAPI.endpoints.categories, {
                categories: mappings
            });

            if (response.success) {
                btn
                    .text(sinefinePromimportAjax.saved_text || 'Saved')
                    .removeClass('import-category')
                    .attr('data-nonce', '')
                    .prop('disabled', true)
                    .addClass('button-disabled');

                // Show success notice
                if (typeof response.message === 'string') {
                    showNotice(response.message, 'success');
                }
            }

            return response;
        } catch (error) {
            alert(error.message || sinefinePromimportAjax.error_text || 'An error occurred');
            console.error('Categories import error:', error);
            throw error;
        }
    }

    async function import_config(
        nonce,
        url
    ) {
        try {
            const data = {
                url: url || '',
            };

            const response = await RestAPI.request(RestAPI.endpoints.config, data, 'POST', true);

            if (!response.success) {
                throw new Error(response.message || 'Config save failed');
            }

            // Show success notice
            if (typeof response.message === 'string') {
                showNotice(response.message, 'success');
            }

            return response;
        } catch (error) {
            alert(error.message || sinefinePromimportAjax.error_text || 'An error occurred');
            console.error('Config save error:', error);
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

    async function get_import_categories(id) {
        return await RestAPI.request(`${RestAPI.endpoints.imports}/${id}/categories`, {}, 'GET', true, 'v2');
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

    // Import categories button
    $('#import-categories').on('click', async function(event) {
        event.preventDefault();

        const $btn = $(this);
        const originalText = $btn.text();

        // Disable button and show loading state
        $btn.prop('disabled', true).text(sinefinePromimportAjax.loading_text || 'Saving...');

        try {
            await import_categories($btn.attr('data-nonce'), $btn);
        } catch (error) {
            // Error: restore button
            $btn.prop('disabled', false).text(originalText);
        }
    });

    // Import config
    $('#import-config').on('click', async function(event) {
        event.preventDefault();

        const $btn = $(this);
        const originalText = $btn.text();
        const url = $("#url").val();

        // Disable button and show loading state
        $btn.prop('disabled', true).text(sinefinePromimportAjax.loading_text || 'Saving...');

        try {
            await import_config($btn.attr('data-nonce'), url);

            // Success: restore button
            $btn.prop('disabled', false).text(originalText);
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

    // Edit import
    $('.edit-import').on('click', async function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const url = $(this).data('url');
        const mapping = $(this).data('mapping') || {};

        $('#modal-title').text(sinefinePromimportAjax.edit_text || 'Edit Import');
        $('#import-id').val(id);
        $('#import-name-input').val(name);
        $('#import-url-input').val(url);
        
        // Show mapping container
        $('#mapping-container').show();
        $('#mapping-list').html('<p>' + (sinefinePromimportAjax.loading_text || 'Loading categories...') + '</p>');
        
        $modal.show();

        try {
            const categories = await get_import_categories(id);
            renderMapping(categories, mapping);
        } catch (error) {
            $('#mapping-list').html('<p style="color:red;">' + error.message + '</p>');
        }
    });

    function renderMapping(categories, currentMapping) {
        const $list = $('#mapping-list');
        $list.empty();

        if (!categories || categories.length === 0) {
            $list.append('<p>' + (sinefinePromimportAjax.no_categories_text || 'No categories found in XML') + '</p>');
            return;
        }

        const wooCategories = window.sinefineWooCategories || [];
        
        categories.forEach(cat => {
            const selectedId = currentMapping[cat.id] || '';
            let options = '<option value="">' + (sinefinePromimportAjax.select_category_text || 'Select Category') + '</option>';
            
            wooCategories.forEach(wooCat => {
                const selected = String(wooCat.id) === String(selectedId) ? 'selected' : '';
                options += `<option value="${wooCat.id}" ${selected}>${wooCat.name}</option>`;
            });

            const row = `
                <div class="mapping-row" style="margin-bottom: 5px; display: flex; align-items: center;">
                    <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${cat.name}">${cat.name}</span>
                    <select name="mapping[${cat.id}]" class="mapping-select" data-cat-id="${cat.id}" style="flex: 1; margin-left: 10px;">
                        ${options}
                    </select>
                </div>
            `;
            $list.append(row);
        });
    }

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
            alert((sinefinePromimportAjax.imported_text || 'Imported: ') + result.imported_count + ' / ' + result.total_count);
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
        const id = $('#import-id').val();
        const name = $('#import-name-input').val();
        const url = $('#import-url-input').val();
        const $btn = $('#save-import-btn');
        const originalText = $btn.text();

        // Collect mapping
        const mapping = {};
        $('.mapping-select').each(function() {
            const catId = $(this).data('cat-id');
            const val = $(this).val();
            if (val) {
                mapping[catId] = val;
            }
        });

        $btn.prop('disabled', true).text(sinefinePromimportAjax.saving_text || 'Saving...');

        try {
            if (id) {
                await update_import(id, name, url);
                await update_import_mapping(id, mapping);
            } else {
                await create_import(name, url);
            }
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
        importCategories: import_categories,
        importConfig: import_config,
        updatePrices: update_prices,
        getImports: get_imports,
        createImport: create_import,
        updateImport: update_import,
        deleteImport: delete_import,
    };

    console.log('Prom Importer REST API initialized');

})(jQuery);
