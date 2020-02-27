<?php

use WPML\Core\Twig_Environment;
use WPML\Core\Twig_Error_Syntax;
use WPML\Core\Twig_Error_Runtime;
use WPML\Core\Twig_Error_Loader;
use WPML\Core\Twig_LoaderInterface;
use WPML\Core\Twig_Loader_String;
use WPML\Core\Twig_Loader_Filesystem;

abstract class WCML_Templates_Factory extends WPML_Templates_Factory {

	/**
	 * @param $template
	 * @param $model
	 *
	 * @return string
	 * @throws Twig_Error_Syntax
	 * @throws Twig_Error_Runtime
	 * @throws Twig_Error_Loader
	 */
	public function get_view( $template = null, $model = null ) {
		$output = '';
		$this->maybe_init_twig();

		if ( null === $model ) {
			$model = $this->get_model();
		}
		if ( null === $template ) {
			$template = $this->get_template();
		}

		try {
			$output = $this->twig->render( $template, $model );
		} catch ( RuntimeException $e ) {
			if ( $this->is_caching_enabled() ) {
				$this->disable_twig_cache();
				$this->twig = null;
				$this->maybe_init_twig();
				$output = $this->get_view( $template, $model );
			} else {
				$this->add_exception_notice( $e );
			}
		} catch ( Twig_Error_Syntax $e ) {
			$message = 'Invalid Twig template string: ' . $e->getRawMessage() . "\n" . $template;
			$this->get_wp_api()->error_log( $message );
		}

		return $output;
	}

	/**
	 * Maybe init twig for WCML
	 */
	protected function maybe_init_twig() {
		if ( ! $this->twig ) {
			$loader = $this->get_twig_loader();

			$environment_args = [];

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$environment_args['debug'] = true;
			}

			if ( $this->is_caching_enabled() ) {
				$wpml_cache_directory  = new WPML_Cache_Directory( $this->get_wp_api() );
				$this->cache_directory = $wpml_cache_directory->get( 'twig' );

				if ( $this->cache_directory ) {
					$environment_args['cache']       = $this->cache_directory;
					$environment_args['auto_reload'] = true;
				} else {
					$this->disable_twig_cache();
				}
			}

			$this->twig = $this->get_twig_environment( $loader, $environment_args );
			if ( $this->custom_functions && count( $this->custom_functions ) > 0 ) {
				foreach ( $this->custom_functions as $custom_function ) {
					$this->twig->addFunction( $custom_function );
				}
			}
			if ( $this->custom_filters && count( $this->custom_filters ) > 0 ) {
				foreach ( $this->custom_filters as $custom_filter ) {
					$this->twig->addFilter( $custom_filter );
				}
			}
		}
	}

	/**
	 * @return Twig_LoaderInterface
	 */
	protected function get_twig_loader() {
		if ( $this->is_string_template() ) {
			$loader = $this->get_twig_loader_string();
		} else {
			$loader = $this->get_twig_loader_filesystem( $this->template_paths );
		}

		return $loader;
	}

	/**
	 * @param Twig_LoaderInterface $loader
	 * @param array                $environment_args
	 *
	 * @return Twig_Environment
	 */
	private function get_twig_environment( $loader, $environment_args ) {
		return new Twig_Environment( $loader, $environment_args );
	}

	/**
	 * @return Twig_Loader_String
	 */
	private function get_twig_loader_string() {
		return new Twig_Loader_String();
	}

	/**
	 * @param string|array $template_paths
	 *
	 * @return Twig_Loader_Filesystem
	 */
	private function get_twig_loader_filesystem( $template_paths ) {
		return new Twig_Loader_Filesystem( $template_paths );
	}
}
