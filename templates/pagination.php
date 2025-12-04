<?php if ( $totalpages > 1 ) { ?>
<div class="tablenav">
    <div class="tablenav-pages">
        <span class="displaying-num">
            <?php
            /* translators: %d: Number of items */
            printf( esc_html(__( '%d items', 'prom-import' )), esc_html( $total_products ) );
            ?>
        </span>
        <span class="pagination-links">
            <?php if ( $page_num > 1 ): ?>
                <a class="first-page button"
                   href="<?php echo esc_url( add_query_arg( array(
		               'page_num' => 1,
		               '_wpnonce' => $nonce
	               ), $current_page ) ); ?>">«</a>
                <a class="prev-page button"
                   href="<?php echo esc_url( add_query_arg( array(
		               'page_num' => $prev_num,
		               '_wpnonce' => $nonce
	               ), $current_page ) ); ?>">‹</a>
            <?php endif; ?>
            <span class="screen-reader-text">
                <?php esc_html_e( 'Current Page', 'prom-import' ); ?>
            </span>
            <span id="table-paging" class="paging-input">
                <span class="tablenav-paging-text">
                    <?php
                    /* translators: 1: Current page number, 2: Total number of pages */
                    printf( esc_html(__( '%1$s of %2$s', 'prom-import')), esc_html( $page_num ), esc_html( $totalpages ) );
                    ?>
                </span>
            </span>
            <?php if ( $page_num < $totalpages ): ?>
                <a class="next-page button"
                   href="<?php echo esc_url( add_query_arg( array(
		               'page_num' => $next_num,
		               '_wpnonce' => $nonce
	               ), $current_page ) ); ?>">›</a>
                <a class="last-page button"
                   href="<?php echo esc_url( add_query_arg( array(
		               'page_num' => $totalpages,
		               '_wpnonce' => $nonce
	               ), $current_page ) ); ?>">»</a>
            <?php endif; ?>
        </span>
        <div></div>
		<?php } ?>
    </div>
</div>