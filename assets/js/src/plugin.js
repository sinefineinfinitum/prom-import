(function($) {
    "use strict";

    /**
     * REST API configuration
     */
    const RestAPI = {
        namespace: 'spss12-prom-import/v1',
        endpoints: {
            product: '/import/product',
            categories: '/import/categories'
        },

        /**
         * Get full REST API URL
         */
        getUrl(endpoint) {
            return `${promImporterAjaxObj.rest_url}${this.namespace}${endpoint}`;
        },

        /**
         * Get nonce for REST API requests
         */
        getNonce() {
            return promImporterAjaxObj.rest_nonce || wp.apiFetch?.nonceMiddleware?.nonce;
        },

        /**
         * Make REST API request
         */
        async request(endpoint, data = {}, method = 'POST') {
            try {
                const response = await fetch(this.getUrl(endpoint), {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.getNonce()
                    },
                    body: JSON.stringify(data)
                });

                const json = await response.json();

                if (!response.ok) {
                    throw new Error(json.message || 'Request failed');
                }

                return json;
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
            alert(error.message || promImporterAjaxObj.error_text || 'An error occurred');
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

            // Iterate each row in categories table
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
                alert(promImporterAjaxObj.no_categories_text || 'No categories selected');
                return;
            }

            const response = await RestAPI.request(RestAPI.endpoints.categories, {
                categories: mappings
            });

            if (response.success) {
                btn
                    .text(promImporterAjaxObj.saved_text || 'Saved')
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
            alert(error.message || promImporterAjaxObj.error_text || 'An error occurred');
            console.error('Categories import error:', error);
            throw error;
        }
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
        $btn.prop('disabled', true).text(promImporterAjaxObj.loading_text || 'Importing...');

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
                .text(promImporterAjaxObj.imported_text || 'Imported')
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
        $btn.prop('disabled', true).text(promImporterAjaxObj.loading_text || 'Saving...');

        try {
            await import_categories($btn.attr('data-nonce'), $btn);
        } catch (error) {
            // Error: restore button
            $btn.prop('disabled', false).text(originalText);
        }
    });

    /**
     * Expose API for external usage (optional)
     */
    window.PromImporter = {
        RestAPI,
        importProduct: import_product,
        importCategories: import_categories
    };

    console.log('Prom Importer REST API initialized');

})(jQuery);
