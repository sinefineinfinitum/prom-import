(function($) {
    "use strict";

    function import_product(
        nonce,
        product_id,
        product_title,
        product_description,
        product_price,
        product_category,
        product_featured_media
    ) {
        $.ajax({
            url: promImporterAjaxObj.ajaxurl,
            type: 'POST',
            data: {
                action: 'ajax_import_product',
                nonce: nonce,
                product_id: product_id,
                product_title: product_title,
                product_description: product_description,
                product_price: product_price,
                product_category: product_category,
                product_featured_media: product_featured_media
            },
            success: function(response) {
                if (response.success == true) {
                    window.open(response.data.url, '_blank');
                } else {
                    alert(response.data.message);
                }
            }
        });
    }

    $('.import-product').on('click', function(event) {
        event.preventDefault();
        import_product(
            $(this).attr('data-nonce'),
            $(this).attr('data-id'),
            $(this).attr('data-title'),
            $(this).attr('data-description'),
            $(this).attr('data-price'),
            $(this).attr('data-category'),
            $(this).attr('data-featured-media'),
        );
        $(this).text(promImporterAjaxObj.imported_text).removeClass('import-product').attr("data-nonce", '');
    });

    function import_categories(nonce, btn) {
        var mappings = [];

        // Iterate each row in categories table
        $('#categories-table tbody tr').each(function(index, row) {
            var $row = $(row);
            // Column 1 contains XML category id inside span
            var xmlId = $.trim($row.find('td').eq(0).find('span').text());
            // The select is in the 3rd column
            var $select = $row.find('td').eq(2).find('select');
            var selectedValue = $select.val();

            if (xmlId !== '' && typeof selectedValue !== 'undefined') {
                mappings.push({
                    id: xmlId,          // Prom XML category id
                    selected: selectedValue // Woo category (slug by current dropdown config)
                });
            }
        });
        console.log(mappings);

        $.ajax({
            url: promImporterAjaxObj.ajaxurl,
            type: 'POST',
            data: {
                action: 'ajax_import_categories',
                nonce: nonce,
                categories: JSON.stringify(mappings)
            },
            success: function(response) {
                if (response && response.success) {
                    btn
                        .text(promImporterAjaxObj.saved_text)
                        .removeClass('import-category')
                        .attr('data-nonce', '')
                        .prop('disabled', true);
                }
            },
            error: function(response) {
                alert(response.data.message);
            }
        });
    }

    $('#import-categories').on('click', function(event) {
        event.preventDefault();
        var $btn = $(this);
        import_categories($btn.attr('data-nonce'), $btn);
    });

})(jQuery);