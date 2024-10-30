<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BrewPress_Setup Class.
 */
class BrewPress_Frontend_Page_Brewing {

    /**
     * Shortcode ID
     * @var string
     */
    public $shortcode_id = 'brewpress_brewing';

    public $batch_id = '';
    

    public function __construct() {

        add_action( 'init', array( $this, 'init' ) );

        add_shortcode( $this->shortcode_id, array( $this, 'brewing_shortcode' ) );

    }

    public function init() {
        $this->batch_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : null;
        $this->steps    = brewpress_get_batch_steps( $this->batch_id );
        $this->elements = brewpress_get_elements();
        $this->pumps    = brewpress_get_pumps();
        $this->sensors  = brewpress_get_sensors();
        $this->time     = current_time( 'timestamp' );
        $this->program  = brewpress_get_batch_program( $this->batch_id );
    }

    /**
     * Handle the dashboard output
     *
     * @param  array  $atts Array of shortcode attributes
     * @return string       Form html
     */
    public function brewing_shortcode( $atts = array() ) {

        if( ! $this->batch_id )
            return 'No batch ID is set';

        do_action( 'brewpress_brewing_page_before', $this->batch_id );

        $output = '<div class="brewpress">';
        $output .= '<div class="brewing" data-batch-id="' . esc_attr( $this->batch_id ) . '">';


        $output .= $this->title();
        $output .= $this->info();
        $output .= $this->controls();
        $output .= $this->program();
        $output .= $this->temps();
        $output .= $this->steps();

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }


    public function title() {
        ob_start();

        ?>
        <div class="row upper">

            <div class="col-sm-12">

                <h3>
                    <?php esc_html_e( brewpress_get_batch_name( $this->batch_id ) ); ?> 
                         
                    <?php $display = $this->program ? '' : 'none'; ?>
                    <span class="small float-right">
                        <a class="btn btn-sm btn-info view" style="display:<?php esc_attr_e( $display ); ?>" target="_blank" href="<?php echo esc_url( get_the_permalink( $this->batch_id ) ); ?>"><?php _e( 'View Chart', 'brewpress' ); ?></a>
                    </span>

                </h3>

                <p class="style"><?php esc_html_e( brewpress_get_batch_style( $this->batch_id ) ); ?></p>

            </div>   

        </div>    
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;

    }

    public function info() {
        ob_start();

        if( ! isset( $this->program['start'] ) )
            return;
        ?>
        
        <div class="row program-info">

            <div class="col-sm-12">
                <div class="alert alert-info">
                    <?php printf( 
                            __( 'Program was started at %1s on %2s', 'brewpress' ), 
                            date_i18n( get_option( 'time_format' ), $this->program['start'] ), 
                            date_i18n( get_option( 'date_format' ), $this->program['start'] ) 
                    ); 

                    if( isset( $this->program['end'] ) && $this->program['end'] != '' ) {
                        echo '<br>';
                        printf( 
                            __( 'Program finished at %1s on %2s', 'brewpress' ), 
                            date_i18n( get_option( 'time_format' ), $this->program['end'] ), 
                            date_i18n( get_option( 'date_format' ), $this->program['end'] ) 
                        ); 

                    } ?>
                </div>
            </div>

        </div>

        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;

    }


    public function controls() {
        
        ob_start();
        ?>
        
        <div class="row manual-controls">

            <div class="col-sm-12">
                
                <?php if( $this->elements ) : ?>
                    <div class="elements">   

                        <?php foreach ($this->elements as $key => $element) { 
                            if( ! isset( $element['name'] ) ) {
                                _e( 'No name set', 'brewpress' );
                                continue;
                            } ?>

                            <div class="btn-wrap">
                                <button type="button" class="btn btn-sm btn-light" data-off="btn-light" data-on="btn-success" data-state="off" data-name="<?php esc_attr_e( $element['name'] ); ?>"><?php esc_attr_e( $element['name'] ); ?> &nbsp; <i class="fa fa-power-off"></i></button>
                            </div>

                        <?php } ?>
                    </div>    
                <?php endif; ?>
               
                <?php if( $this->pumps ) : ?>
                    <div class="pumps">

                        <?php foreach ($this->pumps as $key => $pump) { 
                            if( ! isset( $pump['name'] ) ) {
                                _e( 'No name set', 'brewpress' );
                                continue;
                            } ?>
                            <div class="btn-wrap">
                                <button type="button" class="btn btn-sm btn-light" data-off="btn-light" data-on="btn-success" data-state="off" data-name="<?php esc_attr_e( $pump['name'] ); ?>"><?php esc_attr_e( $pump['name'] ); ?> &nbsp; <i class="fa fa-power-off"></i></button>
                            </div>
                        <?php } ?>
                    </div>
                <?php endif; ?>
                
                <div class="manual-mode"></div>
                
            </div>
        </div>

        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;

    }

    public function program() {

        ob_start();
        ?>
        
        <div class="row program">

            <div class="col-sm-12">
                
                <div class="card program-buttons">
                    <div class="card-header">
                            <?php _e( 'Program', 'brewpress' ); ?>
                    </div>
                    <div class="card-body">

                        <?php 
                        $started = '';
                        if( isset( $this->program['end'] ) && $this->program['end'] != '' ) {
                            $started = 'data-title="' . __( 'Program has been run for this batch. Starting the program again will delete previous program and all temp logs.', 'brewpress' ) . '" data-toggle="confirmation" data-btn-ok-label="Continue" data-btn-cancel-label="Cancel"';
                        } ?>

                        <button type="button" data-off="btn-secondary" data-on="btn-success" class="btn btn-secondary btn-sm start" <?php echo $started; ?>><?php _e( 'Start', 'brewpress' ); ?> <i class="fa fa-play"></i></button>

                        <button type="button" data-off="btn-secondary" data-on="btn-warning" class="btn btn-secondary btn-sm pause"><?php _e( 'Pause', 'brewpress' ); ?> <i class="fa fa-pause"></i></button>

                        <button type="button" data-off="btn-danger" data-on="btn-danger" class="btn btn-danger btn-sm reset float-right" data-toggle="confirmation"><?php _e( 'Reset', 'brewpress' ); ?> <i class="fa fa-sync"></i></button>

                    </div>
                </div> 

            </div>
        </div>


        <div class="row total-time">

            <div class="col-sm-12">

                <div class="card text-center">

                    <div class="card-header">
                        <?php _e( 'Total Elapsed Time', 'brewpress' ); ?>
                    </div>

                    <div class="card-body">
                            
                        <div id="main-timer">
                            <div class="hours">
                                <span class="digits">00</span>
                                <span class="text"><?php _e( 'Hours', 'brewpress' ); ?></span>
                            </div>
                            <div class="minutes">
                                <span class="digits">00</span>
                                <span class="text"><?php _e( 'Minutes', 'brewpress' ); ?></span>
                            </div>
                            <div class="seconds">
                                <span class="digits">00</span>
                                <span class="text"><?php _e( 'Seconds', 'brewpress' ); ?></span>
                            </div>
                        </div>

                    </div>

                    <div class="card-footer text-muted" id="progress">
                        <span class="step"><?php $this->program ? _e( 'Finished', 'brewpress' ) : _e( 'Not Started', 'brewpress' ); ?></span> 
                        <span class="status"></span>
                    </div>

                </div>  

            </div>

        </div>

        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;

    }




    public function temps() {

        ob_start();
        ?>
        
        <div class="row temps">

            <div class="col-sm-12">
                <div class="card-group">
                    <?php 
                    if( $this->sensors ) {
                        foreach ( $this->sensors as $name => $sensor ) { 
                            ?>
                            
                                <div class="card sensor" data-sensor-id="<?php esc_attr_e( $sensor ); ?>">
                                    <div class="card-header">
                                        <?php esc_html_e( $name ); ?>
                                    </div>
                                    <div class="card-body">
                                        <h1>
                                            <span class="temp">0</span> 
                                            <span class="temp_unit"><?php esc_html_e( brewpress_get_temp_unit() ); ?></span>
                                        </h1>
                                    </div>
                                </div>
                            
                        <?php }
                    } ?>

                </div>
            </div>
        </div>

        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;

    }




    public function steps() {

        ob_start();
        ?>

        <div class="row steps">

            <div class="col-sm-12">

                <div class="card">

                    <div class="card-header">

                        <?php _e( 'Steps', 'brewpress' ); ?>

                        <div class="btn-group float-right">

                            <button class="btn btn-sm btn-light restart-step" data-title="<?php esc_attr_e( __( 'Are you sure you want to restart the current step.', 'brewpress' ) ); ?>" data-toggle="confirmation" data-btn-ok-label="Yes" data-btn-cancel-label="No"><i class="fa fa-play"></i></button>

                            <button class="btn btn-sm btn-light next-step" data-title="<?php esc_attr_e( __( 'Are you sure you want to move to the next step.', 'brewpress' ) ); ?>" data-toggle="confirmation" data-btn-ok-label="Yes" data-btn-cancel-label="No"><i class="fa fa-forward"></i></button>

                        </div>

                    </div>

                    <ul class="list-group list-group-flush">
                        <?php
                        if( $this->steps ) {
                            foreach ($this->steps as $key => $step) { 

                                $temp = isset( $step['temperature'] ) ? $step['temperature'] : '0';
                                $time = isset( $step['time'] ) ? $step['time'] : '0';

                                if( ! $temp || ! $time )
                                    continue;
                                ?>

                                <li class="list-group-item step" data-id="<?php esc_attr_e( $key + 1 ); ?>">

                                    <h3><?php _e( 'Step', 'brewpress' ); ?> <?php esc_html_e( $key + 1 ); ?> <span></span></h3>
                                    <h4><?php esc_html_e( $step['element'] ); ?></h4>
                                    <div class="target"><?php _e( 'Target Temp:', 'brewpress' ); ?> <?php esc_html_e( $temp ); ?><?php esc_html_e( brewpress_get_temp_unit() ); ?></div>
                                    <div class="time"><?php _e( 'Minutes:', 'brewpress' ); ?> <?php esc_html_e( $time ); ?></div>

                                    <div id="step-timer-<?php esc_attr_e( $key + 1 ); ?>" class="step-timer">00:00:00</div>

                                </li>
                            
                            <?php }
                        }
                        ?>
                    </ul>
                </div>

               
            </div>
        </div>
        <pre id="print"></pre>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;

    }

    
}

return new BrewPress_Frontend_Page_Brewing;