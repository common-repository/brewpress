<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BrewPress_Setup Class.
 */
class BrewPress_Frontend_Page_Dashboard {

    /**
     * Shortcode ID
     * @var string
     */
    public $shortcode_id = 'brewpress_dashboard';
    

    public function __construct() {

        add_action( 'init', array( $this, 'init' ) );

        add_shortcode( $this->shortcode_id, array( $this, 'dashboard_shortcode' ) );

    }


    public function init() {

        $this->batch_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : null;

    }

    /**
     * Handle the dashboard output
     *
     * @param  array  $atts Array of shortcode attributes
     * @return string       Form html
     */
    public function dashboard_shortcode( $atts = array() ) {

        do_action( 'brewpress_dashboard_page_before', $this->batch_id );

        // Initiate our output variable
        $output = '<div class="brewpress">';
        $output .= '<div class="dashboard">';

        $output .= $this->batches();

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }

    public function batches() {

        ob_start();

        $times = brewpress_get_batches_times();
        
        ?>

        <div class="row">

            <div class='ui_box col-md-3 batches'>
                <i class="fa fa-beer"></i>
                <div class='stat'>
                    <span><?php echo count( brewpress_get_batches() ); ?></span>
                </div>
                <h3><?php _e( 'Batches', 'brewpress' ); ?></h3>
            </div>

            <div class='ui_box col-md-3 total'>
                <i class="far fa-clock"></i>
                <div class='stat'>
                    <span><?php esc_html_e( $times['total'] ); ?></span>
                </div>
                <h3><?php _e( 'Total Time', 'brewpress' ); ?></h3>
            </div>

            <div class='ui_box col-md-3 shortest'>
                <i class="fa fa-caret-right"></i>
                <div class='stat'>
                    <span><?php esc_html_e( $times['shortest'] ); ?></span>
                </div>
                <h3><?php _e( 'Quickest', 'brewpress' ); ?></h3>
            </div>

            <div class='ui_box col-md-3 longest'>
                <i class="fa fa-long-arrow-alt-right"></i>
                <div class='stat'>
                    <span><?php esc_html_e( $times['longest'] ); ?></span>
                </div>
                <h3><?php _e( 'Longest', 'brewpress' ); ?></h3>
            </div>

        </div>

        <?php
        $content = ob_get_contents();
        ob_end_clean();

        return $content;

    }


}

return new BrewPress_Frontend_Page_Dashboard;