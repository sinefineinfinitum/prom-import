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

})(jQuery);