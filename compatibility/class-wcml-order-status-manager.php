<?php
/**
 * Class WCML_Order_Status_Manager
 * compatibility class for WC Order Status Manager plugin.
 */
class WCML_Order_Status_Manager {
	/**
	 * WordPress query object.
	 *
	 * @var WP_Query
	 */
	private $wp_query;

	/**
	 * WCML_Order_Status_Manager constructor.
	 *
	 * @param WP_Query $wp_query WordPress query object.
	 */
	public function __construct( WP_Query $wp_query ) {
		$this->wp_query = $wp_query;
	}

	/**
	 * Adds WordPress hooks.
	 */
	public function add_hooks() {
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10, 1 );
	}

	/**
	 * Adds post__not_in to the query arguments.
	 *
	 * @param null $q the parsed query.
	 */
	public function pre_get_posts( $q = null ) {
		if ( isset( $q->query['post_type'] )
			&& 'wc_order_status' === $q->query['post_type']
			&& doing_filter( 'woocommerce_register_shop_order_post_statuses' )
		) {
			$q->set( 'post__not_in', $this->prepare_post_not_in( $q, $this->get_statuses() ) );
		}
	}

	/**
	 * Queries for all statuses in wp_posts table.
	 */
	private function get_statuses() {
		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );
		$this->wp_query->query(
			array(
				'post_type'        => 'wc_order_status',
				'posts_per_page'   => -1,
				'suppress_filters' => false,

			)
		);
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10, 1 );
		return $this->wp_query->posts;
	}

	/**
	 * Filters out elements not in the current language from query results.
	 *
	 * @param WP_Query $q        The WordPress query.
	 * @param void     $statuses Posts with post type wc_order_status
	 *
	 * @return array The post__not_in array.
	 */
	private function prepare_post_not_in( $q, $statuses ) {
		$post__not_in = array();

		if ( $statuses ) {
			$current_language = apply_filters( 'wpml_current_language', null );

			foreach( $statuses as $status ) {
				$post_language_details = apply_filters( 'wpml_post_language_details', '', $status->ID );
				if ( isset( $post_language_details['language_code'] ) ) {
					if ( $post_language_details['language_code'] !== $current_language ) {
						$post__not_in[] = $status->ID;
					}
				}
			}
		}

		$post__not_in_query  = isset( $q->query_vars['post__not_in'] ) ? $q->query_vars['post__not_in'] : array();
		return array_merge( $post__not_in_query, $post__not_in );
	}
}
