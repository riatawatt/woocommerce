add_action( 'woocommerce_update_product', 'mp_sync_on_product_save', 10, 1 );
function mp_sync_on_product_save( $product_id ) {
     $product = wc_get_product( $product_id );
	 // do something with this product
	  
	 	$term_taxonomy_ids = wp_set_object_terms( get_the_ID(), 'jaune', 'pa_color', false );
		$thedata = Array(
			'pa_color'=>Array( 
				'name'=>'pa_color', 
				'value'=>'jaune',
				'is_visible' => '1',
				'is_variation' => '1',
				'is_taxonomy' => '1'
			)
		);
		update_post_meta( get_the_ID(),'_product_attributes',$thedata);
}
