<?php

class WCML_Comments {

	const WCML_AVERAGE_RATING_KEY = '_wcml_average_rating';
	const WCML_REVIEW_COUNT_KEY   = '_wcml_review_count';
	const WC_REVIEW_COUNT_KEY     = '_wc_review_count';

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_Post_Translation */
	private $post_translations;
	/** @var wpdb */
	private $wpdb;

	/**
	 * WCML_Comments constructor.
	 *
	 * @param woocommerce_wpml      $woocommerce_wpml
	 * @param SitePress             $sitepress
	 * @param WPML_Post_Translation $post_translations
	 * @param wpdb $wpdb
	 */
	public function __construct( woocommerce_wpml $woocommerce_wpml, SitePress $sitepress, WPML_Post_Translation $post_translations, wpdb $wpdb ) {
		$this->woocommerce_wpml  = $woocommerce_wpml;
		$this->sitepress         = $sitepress;
		$this->post_translations = $post_translations;
		$this->wpdb              = $wpdb;
	}

	public function add_hooks() {

		add_action( 'comment_post', [ $this, 'add_comment_rating' ] );
		add_action( 'woocommerce_review_before_comment_meta', [ $this, 'add_comment_flag' ], 9 );

		add_filter( 'get_post_metadata', [ $this, 'filter_average_rating' ], 10, 4 );
		add_filter( 'comments_clauses', [ $this, 'comments_clauses' ], 10, 2 );
		add_action( 'comment_form_before', [ $this, 'comments_link' ] );

		add_filter( 'wpml_is_comment_query_filtered', [ $this, 'is_comment_query_filtered' ], 10, 2 );
		add_action( 'trashed_comment', [ $this, 'recalculate_average_rating_on_comment_hook' ], 10, 2 );
		add_action( 'deleted_comment', [ $this, 'recalculate_average_rating_on_comment_hook' ], 10, 2 );
		add_action( 'untrashed_comment', [ $this, 'recalculate_average_rating_on_comment_hook' ], 10, 2 );
		//before WCML_Synchronize_Product_Data::sync_product_translations_visibility hook
		add_action( 'woocommerce_product_set_visibility', [ $this, 'recalculate_comment_rating' ], 9 );

		add_filter( 'woocommerce_top_rated_products_widget_args', [ $this, 'top_rated_products_widget_args' ] );
		add_filter( 'woocommerce_rating_filter_count', [ $this, 'woocommerce_rating_filter_count' ], 10, 3 );
	}

	/**
	 * Add comment rating
	 *
	 * @param int $comment_id
	 */
	public function add_comment_rating( $comment_id ) {

		if ( isset( $_POST['comment_post_ID'] ) ) {

			$product_id = sanitize_text_field( $_POST['comment_post_ID'] );

			if ( 'product' === get_post_type( $product_id ) ) {

				$this->recalculate_comment_rating( $product_id );
			}
		}
	}

	/**
	 * Calculate rating field for comments based on reviews in all languages.
	 *
	 * @param int $product_id
	 */
	public function recalculate_comment_rating( $product_id ) {

		$translations          = $this->post_translations->get_element_translations( $product_id );
		$average_ratings_sum   = 0;
		$average_ratings_count = 0;
		$reviews_count         = 0;

		foreach ( $translations as $translation ) {
			$product = wc_get_product( $translation );

			$ratings      = WC_Comments::get_rating_counts_for_product( $product );
			$review_count = WC_Comments::get_review_count_for_product( $product );

			if ( is_array( $ratings ) ) {
				foreach ( $ratings as $rating => $count ) {
					$average_ratings_sum   += $rating * $count;
					$average_ratings_count += $count;
				}
			}

			if ( $review_count ) {
				$reviews_count += $review_count;
			} else {
				update_post_meta( $translation, self::WCML_AVERAGE_RATING_KEY, null );
				update_post_meta( $translation, self::WCML_REVIEW_COUNT_KEY, null );
			}
		}

		if ( $average_ratings_sum ) {
			$average_rating = number_format( $average_ratings_sum / $average_ratings_count, 2, '.', '' );

			foreach ( $translations as $translation ) {
				update_post_meta( $translation, self::WCML_AVERAGE_RATING_KEY, $average_rating );
				update_post_meta( $translation, self::WCML_REVIEW_COUNT_KEY, $reviews_count );
			}
		}

	}

	/**
	 * Filter WC reviews meta
	 *
	 * @param null|array|string $value    The value get_metadata() should return a single metadata value, or an
	 *                                    array of values.
	 * @param int               $object_id  Post ID.
	 * @param string            $meta_key Meta key.
	 * @param bool
	 * @return array|null|string Filtered metadata value, array of values, or null.
	 */
	public function filter_average_rating( $value, $object_id, $meta_key, $single ) {

		$filtered_value = $value;

		if ( in_array( $meta_key, [ '_wc_average_rating', self::WC_REVIEW_COUNT_KEY ] ) && 'product' === get_post_type( $object_id ) ) {

			switch ( $meta_key ) {
				case '_wc_average_rating':
					$filtered_value = get_post_meta( $object_id, self::WCML_AVERAGE_RATING_KEY, $single );
					break;
				case self::WC_REVIEW_COUNT_KEY:
					if ( $this->is_reviews_in_all_languages( $object_id ) ) {
						$filtered_value = get_post_meta( $object_id, self::WCML_REVIEW_COUNT_KEY, $single );
					}
					break;
			}
		}

		return ! empty( $filtered_value ) ? $filtered_value : $value;
	}

	/**
	 * Filters comment queries to display in all languages if needed
	 *
	 * @param string[]         $clauses
	 * @param WP_Comment_Query $obj
	 *
	 * @return string[]
	 */
	public function comments_clauses( $clauses, $obj ) {

		if ( $this->is_reviews_in_all_languages( $obj->query_vars['post_id'] ) ) {

			$ids = $this->get_translations_ids_list( $obj->query_vars['post_id'] );

			$clauses['where'] = str_replace( 'comment_post_ID = ' . $obj->query_vars['post_id'], 'comment_post_ID IN (' . $ids . ')', $clauses['where'] );
		}

		return $clauses;
	}

	/**
	 * Get list of translated ids for product
	 *
	 * @param int $product_id
	 *
	 * @return string list of ids
	 */
	private function get_translations_ids_list( $product_id ) {

		$translations = $this->post_translations->get_element_translations( $product_id );

		return implode( ',', array_filter( $translations ) );

	}

	/**
	 * Display link to show rating in all/current language
	 */
	public function comments_link() {

		if ( is_product() ) {
			$current_url      = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$current_language = $this->sitepress->get_current_language();

			if ( ! isset( $_GET['clang'] ) || $current_language === $_GET['clang'] ) {
				$comments_link                  = add_query_arg( [ 'clang' => 'all' ], $current_url );
				$all_languages_reviews_count    = $this->get_reviews_count( 'all' );
				$current_language_reviews_count = $this->get_reviews_count();

				if ( $all_languages_reviews_count > $current_language_reviews_count ) {
					$comments_link_text = sprintf( __( 'Show reviews in all languages  (%s)', 'woocommerce-multilingual' ), $all_languages_reviews_count );
				}
			} elseif ( 'all' === $_GET['clang'] ) {

				$current_language_reviews_count = $this->get_reviews_count();
				$comments_link                  = add_query_arg( [ 'clang' => $current_language ], $current_url );
				$language_details               = $this->sitepress->get_language_details( $current_language );
				$comments_link_text             = sprintf( __( 'Show only reviews in %1$s (%2$s)', 'woocommerce-multilingual' ), $language_details['display_name'], $current_language_reviews_count );
			}

			if ( isset( $comments_link_text ) && $comments_link_text ) {
				echo '<p><a id="lang-comments-link" href="' . $comments_link . '">' . $comments_link_text . '</a></p>';
			}
		}
	}

	/**
	 * Checks if comments needs filtering by language.
	 *
	 * @param bool $filtered
	 * @param int  $post_id
	 * @return bool
	 */
	public function is_comment_query_filtered( $filtered, $post_id ) {

		if ( $this->is_reviews_in_all_languages( $post_id ) ) {
			$filtered = false;
		}

		return $filtered;
	}

	/**
	 * Add flag to comment description
	 *
	 * @param WP_Comment $comment
	 */
	public function add_comment_flag( $comment ) {

		if ( $this->is_reviews_in_all_languages( $comment->comment_post_ID ) ) {
			$comment_language = $this->post_translations->get_element_lang_code( $comment->comment_post_ID );

			$html  = '<div style="float: left; padding-right: 5px;">';
			$html .= '<img src="' . $this->sitepress->get_flag_url( $comment_language ) . '" width=18" height="12">';
			$html .= '</div>';

			echo $html;
		}
	}

	/**
	 * Checks if reviews in all languages should be displayed.
	 *
	 * @param int $product_id
	 * @return bool
	 */
	public function is_reviews_in_all_languages( $product_id ) {

		return isset( $_GET['clang'] ) && 'all' === $_GET['clang'] && 'product' === get_post_type( $product_id );
	}

	/**
	 * Return reviews count in language
	 *
	 * @param string $language
	 * @return int
	 */
	public function get_reviews_count( $language = false ) {

		remove_filter( 'get_post_metadata', [ $this, 'filter_average_rating' ], 10, 4 );

		if ( ! metadata_exists( 'post', get_the_ID(), self::WCML_REVIEW_COUNT_KEY ) ) {
			$this->recalculate_comment_rating( get_the_ID() );
		}

		if ( 'all' === $language ) {
			$reviews_count = get_post_meta( get_the_ID(), self::WCML_REVIEW_COUNT_KEY, true );
		} else {
			$reviews_count = get_post_meta( get_the_ID(), self::WC_REVIEW_COUNT_KEY, true );
		}

		add_filter( 'get_post_metadata', [ $this, 'filter_average_rating' ], 10, 4 );

		return $reviews_count;
	}

	/**
	 * @param int             $comment_id
	 * @param WP_Comment|null $comment
	 */
	public function recalculate_average_rating_on_comment_hook( $comment_id, $comment ) {

		if ( ! $comment ) {
			$comment = get_comment( $comment_id );
		}

		if ( in_array( get_post_type( $comment->comment_post_ID ), [ 'product', 'product_variation' ] ) ) {
			$this->recalculate_comment_rating( (int) $comment->comment_post_ID );
		}
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function top_rated_products_widget_args( $args ) {
		$args['meta_key'] = self::WCML_AVERAGE_RATING_KEY;

		return $args;
	}

	/**
	 * @param string $label
	 * @param int $count
	 * @param int $rating
	 *
	 * @return array
	 */
	public function woocommerce_rating_filter_count( $label, $count, $rating ) {

		$ratingTerm = get_term_by( 'name', 'rated-' . $rating, 'product_visibility' );

		$productsCountInCurrentLanguage = $this->wpdb->get_var( $this->wpdb->prepare( "                
                SELECT COUNT( DISTINCT tr.object_id ) 
                FROM {$this->wpdb->term_relationships} tr
                LEFT JOIN {$this->wpdb->prefix}icl_translations t ON t.element_id = tr.object_id 
                WHERE tr.term_taxonomy_id = %d AND t.element_type='post_product' AND t.language_code = %s                 
        ", $ratingTerm->term_taxonomy_id, $this->sitepress->get_current_language() ) );

		return "({$productsCountInCurrentLanguage})";
	}
}
