<?php

namespace WCML\Media\Wrapper;

class NonTranslatable implements IMedia {

	public function add_hooks() {}

	/**
	 * @param int $product_id
	 *
	 * @return array
	 */
	public function product_images_ids( $product_id ) {
		return [];
	}

	/**
	 * @param int    $orig_post_id
	 * @param int    $trnsl_post_id
	 * @param string $lang
	 */
	public function sync_thumbnail_id( $orig_post_id, $trnsl_post_id, $lang ) {}

	/**
	 * @param int    $variation_id
	 * @param int    $translated_variation_id
	 * @param string $lang
	 */
	public function sync_variation_thumbnail_id( $variation_id, $translated_variation_id, $lang ) {}

	/**
	 * @param int $product_id
	 */
	public function sync_product_gallery( $product_id ) {}

	/**
	 * @param int    $attachment_id
	 * @param int    $parent_id
	 * @param string $target_lang
	 *
	 * @return int
	 */
	public function create_base_media_translation( $attachment_id, $parent_id, $target_lang ) {
		return 0;
	}

	/**
	 * @param int $att_id
	 * @param int $dup_att_id
	 */
	public function sync_product_gallery_duplicate_attachment( $att_id, $dup_att_id ) {}
}
