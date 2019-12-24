<?php

namespace WCML\RewriteRules;

class Hooks implements \IWPML_Backend_Action, \IWPML_Frontend_Action, \IWPML_DIC_Action {

	/** @var \SitePress $sitepress */
	private $sitepress;

	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	public function add_hooks() {
		add_filter( 'option_woocommerce_queue_flush_rewrite_rules', [ $this, 'preventFlushInNonDefaultLang' ] );
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	public function preventFlushInNonDefaultLang( $value ) {
		if (
			'yes' === $value
			&& $this->sitepress->get_current_language() !== $this->sitepress->get_default_language()
		) {
			return 'no';
		}

		return $value;
	}
}
