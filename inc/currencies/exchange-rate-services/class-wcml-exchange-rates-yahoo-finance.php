<?php

class WCML_Exchange_Rates_YahooFinance extends WCML_Exchange_Rate_Service{

    private $id             = 'yahoo';
    private $name           = 'Yahoo! Finance';
    private $url            = 'https://finance.yahoo.com/currency-converter';
    private $api_url        = 'http://finance.yahoo.com/d/quotes.csv?e=.csv&f=c4l1&s=%s'; // EURUSD=X,GBPUSD=X

    protected $api_key      = '';
    const REQUIRES_KEY      = false;

    function __construct() {
        parent::__construct( $this->id, $this->name, $this->api_url, $this->url );
    }

    /**
     * @param $from string
     * @param $to array
     * @return array
     * @throws Exception
     */
    public function get_rates( $from, $tos ){

        parent::clear_last_error();
        $rates = array();

        $pairs = array();
        foreach( $tos as $to ){
            $pairs[] = $from . $to . '=X';
        }

        $url = sprintf( $this->api_url, join(',', $pairs) );

        $http = new WP_Http();
        $data = $http->request( $url );

        if( is_wp_error( $data ) ){

            $http_error = join("\n", $data->get_error_messages() );
            parent::save_last_error( $http_error );
            throw new Exception( $http_error );

        } else {

            // str_getcsv not working as expected
            $lines = explode("\n", trim( $data['body'] ) );
            foreach( $lines as $k => $line ){

                // Exception: sometimes it returns N/A
                if( substr( $line, 0, 3) === 'N/A' ){
                	$values = array_values( $tos );
                    $to     = $values[$k];
                    $rate   = trim( substr( $line, 4 ) );
                }else{
                    $to     = substr( $line, 1, 3);
                    $rate   = trim( substr( $line, 6 ) );
                }

                if( !is_numeric( $rate ) ){
                    $error = sprintf( __("Error reading the exchange rate for %s. Please try again. If the error persist, try selecting a different exchange rate service.", 'woocommerce-multilingual' ), $to );
                    parent::save_last_error( $error );
                    throw new Exception( $error );
                }

                $rates[$to] = $rate;
            }

        }

        return $rates;

    }

}