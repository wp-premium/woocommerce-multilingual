<?php

/**
 * Class WPML_Templates_Factory
 * Used in the absence of WPML to render WCML basic menus
 */

abstract class WPML_Templates_Factory {
    protected $template_paths;
    /**
     * @var Twig_Environment
     */
    private $twig;

    public function __construct( $custom_functions = array(), $custom_filters = array() ) {
        $this->init_template_base_dir();
        $this->custom_functions = $custom_functions;
        $this->custom_filters   = $custom_filters;
    }

    abstract protected function init_template_base_dir();

    public function show( $template = null, $model = null ) {
        echo $this->get_view( $template, $model );
    }

    /**
     * @param $template
     * @param $model
     *
     * @return string
     */
    public function get_view( $template = null, $model = null ) {
        $this->maybe_init_twig();

        if ( $model === null ) {
            $model = $this->get_model();
        }
        if ( $template === null ) {
            $template = $this->get_template();
        }

        $view = $this->twig->render( $template, $model );

        return $view;
    }

    private function maybe_init_twig() {
        if ( ! isset( $this->twig ) ) {
            $loader = new Twig_Loader_Filesystem( $this->template_paths );

            $environment_args = array();
            if ( WP_DEBUG ) {
                $environment_args[ 'debug' ] = true;
            }

            $this->twig = new Twig_Environment( $loader, $environment_args );
            if ( isset( $this->custom_functions ) && count( $this->custom_functions ) > 0 ) {
                foreach ( $this->custom_functions as $custom_function ) {
                    $this->twig->addFunction( $custom_function );
                }
            }
            if ( isset( $this->custom_filters ) && count( $this->custom_filters ) > 0 ) {
                foreach ( $this->custom_filters as $custom_filter ) {
                    $this->twig->addFilter( $custom_filter );
                }
            }
        }
    }

    abstract public function get_template();

    abstract public function get_model();

    protected function &get_twig() {
        return $this->twig;
    }
}