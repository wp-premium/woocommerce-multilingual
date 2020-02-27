<?php
/**
 * Class WCML_Product_Data_Store_CPT
 */
class WCML_Product_Data_Store_CPT extends WC_Product_Data_Store_CPT {

	/**
	 * @param int $product_id
	 */
	public function update_lookup_table_data( $product_id ) {
		if ( method_exists( $this, 'update_lookup_table' ) ) {
			$this->update_lookup_table( $product_id, 'wc_product_meta_lookup' );
		}
	}

}
