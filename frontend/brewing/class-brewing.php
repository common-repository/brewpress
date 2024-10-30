<?php 


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BrewPress_Setup Class.
 */
class BrewPress_Frontend_Brewing {

    public $batch_id;
    public $current_step;
    public $element;
    public $steps;
    public $time;
    public $statuses;
    

    public function __construct() {

        // ajax functions for getting the temp
        //add_action( 'wp_ajax_brewpress_heartbeat', array( $this, 'heartbeat' ) );

        // ajax functions for manual switch control
        add_action( 'wp_ajax_brewpress_manual_button', array( $this, 'manual_button' ) );
        add_action( 'wp_ajax_brewpress_exit_manual_mode', array( $this, 'exit_manual_mode' ) );

        // ajax functions for running the program
        add_action( 'wp_ajax_brewpress_start_program', array( $this, 'start_program' ) );
        add_action( 'wp_ajax_brewpress_pause_program', array( $this, 'pause' ) );
        add_action( 'wp_ajax_brewpress_restart_program', array( $this, 'restart' ) );
        add_action( 'wp_ajax_brewpress_reset_program', array( $this, 'reset' ) );

        add_action( 'wp_ajax_brewpress_do_program_switch', array( $this, 'do_program_switch' ) );

        add_action( 'wp_ajax_brewpress_start_step', array( $this, 'start_step' ) );
        add_action( 'wp_ajax_brewpress_next_step', array( $this, 'next_step' ) );
        add_action( 'wp_ajax_brewpress_finish_program', array( $this, 'finish' ) );

    }


    /**
     * Set the batch id
     */
    public function set_batch_id() {
        $this->batch_id = isset( $_REQUEST['batch_id'] ) ? absint( $_REQUEST['batch_id'] ) : false;
    }

    /**
     * Set the steps
     */
    public function set_all_steps() {
        $this->steps = brewpress_get_batch_steps( $this->batch_id );
    }

    /**
     * Set the current step
     */
    public function set_current_step( $current_step = 1 ) {
        $this->current_step = isset( $_REQUEST['step']['id'] ) ? absint( $_REQUEST['step']['id'] ) : $current_step;
    }

    /**
     * Set the current step
     */
    public function set_step() {
        $this->step = isset( $_REQUEST['step'] ) ? $_REQUEST['step'] : false;
    }

    /**
     * Set the current time
     */
    public function set_time() {
        $this->time = current_time( 'timestamp' );
    }

    /**
     * Set the statuses
     */
    public function set_statuses() {
        $this->statuses = array(
            'running'   => __( 'Running', 'brewpress' ),
            'paused'    => __( 'Paused', 'brewpress' ),
            'finished'  => __( 'Finished', 'brewpress' ),
            'heating'   => __( 'Heating', 'brewpress' ),
        );
    }

    public function init() {

        $this->set_batch_id();
        $this->set_all_steps();
        $this->set_current_step();
        $this->set_step();
        $this->set_time();
        $this->set_statuses();
        
        if( ! $this->batch_id ) {
            return array( 
                'type'  => 'missing',
                'msg'   => __( 'Missing batch id', 'brewpress' ),
            );
        }

    }


    public function start_program() {
        
        $this->ajax_checks();
        $this->init();

        $element = brewpress_get_hardware( $this->steps[0]['element'] );

        $program = apply_filters( 'brewpress_program_start', array( 
            'running'       => true,
            'start'         => $this->time,
            'end'           => '',
            'mode'          => $element['mode'], // hysteresis etc
            'all_steps'     => $this->steps,
            'status'        => $this->get_status( 'heating' ),
            'status_prev'   => '',
            'current_step'  => 1,
            'steps'         => $this->set_step_data(),
            'restarted'     => array(), // used for logging
            'paused'        => array(), // used for logging
        ), $this->batch_id );

        $this->update_program( $program );

        $this->ajax_response( $program );

    }


    public function set_step_data() {

        $data = array();
        foreach ($this->steps as $i => $step) {

            $element = brewpress_get_hardware( $step['element'] );

            $data[$i + 1] = array(
                'id'            => $i + 1,
                'start'         => '', 
                'end'           => '', 
                'mode'          => $element['mode'], 
                'sensor'        => $element['sensor'], 
                'element'       => $element['name'], 
                'target_temp'   => absint( $step['temperature'] ), 
                'temp_on'       => $step['temperature'] + $element['hysteresis_on'], 
                'temp_off'      => $step['temperature'] + $element['hysteresis_off'], 
                'time'          => $step['time'] * 60, // convert minutes to seconds
                'state'         => null,
                'running'       => false,
            );

        }

        return $data;

    }


    public function pause() {

        $this->ajax_checks();
        $this->init();
        
        $program = $this->get_program();

        $current_step           = $program['current_step'];
        $program['status_prev'] = $program['status'];
        $program['status']      = $this->get_status( 'paused' );
        $program['paused'][]    = $this->time;
        $program['steps'][$current_step]['state']   = null;

        $program['steps'][$current_step]['timer']   = $program['steps'][$current_step]['time'] - ( $program['steps'][$current_step]['end'] - $this->time ); // used in JS

        $program['steps'][$current_step]['end']     = ''; // set to nothing and redo end time in the restart
        

        do_action( 'brewpress_switch_all_off' );

        $this->update_program( $program );

        $this->ajax_response( $program );
        
    }


    public function restart() {
        
        $this->ajax_checks();
        $this->init();

        $program = $this->get_program();

        $current_step = $program['current_step'];

        // And setup the new step end time
        // based on how long we have been paused for
        if ( $program['steps'][$current_step]['running'] ) {

            $start_time     = $program['steps'][$current_step]['start'];
            $total_paused   = array();

            // Work out total of how long we were paused for since the start of this step
            // could be multiple pauses 
            if( $program['paused'] ) {
                foreach ( $program['paused'] as $key => $pause ) {
                    if( $pause > $start_time ) {

                        // check if we have a matching restarted key,
                        // meaning we have had multiple pauses during step
                        if( isset( $program['restarted'][$key] ) ) {
                            $total_paused[] = $program['restarted'][$key] - $pause;
                        } else {
                            // else this is our current pause
                            $total_paused[] = $this->time - $pause;
                        }
                        
                    }
                }
            }
                        
            $diff           = array_sum( $total_paused );
            $start_time     = $program['steps'][$current_step]['start'];
            $step_time      = $program['steps'][$current_step]['time'];

            $program['steps'][$current_step]['end'] = $start_time + $step_time + $diff;
            unset( $program['steps'][$current_step]['timer'] );
        }

        $program['status']      = $program['status_prev'];
        $program['status_prev'] = $this->get_status( 'paused' );
        $program['restarted'][] = $this->time;
        

        $this->update_program( $program );

        $this->ajax_response( $program );

    }


    public function reset() {
        $this->ajax_checks();
        $this->init();
        do_action( 'brewpress_switch_all_off' );
        $this->update_program( null );
        $this->set_temp_logs( null );
        $this->ajax_response( null );
    }

    /*
     * Do a switch
     */
    public function do_program_switch() {
        
        $this->ajax_checks();
        $this->init();

        $state['hardware_name'] = $this->step['element'];
        $state['command']       = sanitize_text_field( $_REQUEST['command'] );
        
        // return should be either 1 or 0
        $result = apply_filters( 'brewpress_do_switch', $state );

        $program = $this->get_program();
        $program['steps'][$this->current_step]['state'] = $state['command'];
        
        $this->update_program( $program );

        $this->ajax_response( $program ); 

    }

    /*
     * Start a step
     * Sets the start and end time of a step and sets it as running
     */
    public function start_step() {

        $this->ajax_checks();
        $this->init();

        $program    = $this->get_program();
        $id         = $this->current_step;

        $program['steps'][$id]['running']   = true;
        $program['steps'][$id]['start']     = $this->time;
        $program['steps'][$id]['end']       = $this->time + $this->step['time'];
        $program['status_prev']             = $program['status'];
        $program['status']                  = $this->get_status( 'running' );
        
        $this->update_program( $program );

        $this->ajax_response( $program ); 

    }



    /*
     * Go to our next step
     */
    public function next_step() {

        $this->ajax_checks();
        $this->init();

        $this_step  = $this->current_step;
        $next_step  = $this->current_step + 1;
        $i          = $next_step - 1; // for zero indexing
        $program    = $this->get_program();

        // turn off current step
        // set time to actual finishing time, in case of 'next step' button being used
        $program['steps'][$this_step]['running']    = false;
        $program['steps'][$this_step]['state']      = null;
        $program['steps'][$this_step]['end']        = $this->time;

        // set the next step as current
        $program['current_step']    = $next_step;
        $program['status_prev']     = $program['status'];
        $program['status']          = $this->get_status( 'heating' );

        $this->update_program( $program );

        $this->ajax_response( $program ); 

    }

    /*
     * Finish program
     */
    public function finish() {

        $this->ajax_checks();
        $this->init();

        $program = $this->get_program();

        // turn off current step
        $program['steps'][$this->current_step]['running']   = false;
        $program['steps'][$this->current_step]['state']     = null;
        $program['steps'][$this->current_step]['end']       = $this->time;

        $program['running']         = false;
        $program['status_prev']     = $program['status'];
        $program['status']          = $this->get_status( 'finished' );
        $program['end']             = $this->time;
        $program['current_step']    = null;
        $program['mode']            = null;

        do_action( 'brewpress_switch_all_off' );

        $this->update_program( $program );

        $this->ajax_response( $program ); 

    }


    /*
     * A manual button has been pressed
     */
    public function manual_button() {

        $this->ajax_checks();
        $this->init();

        // do the switching
        $state['hardware_name'] = sanitize_text_field( $_REQUEST['hardware_name'] );
        $state['command']       = sanitize_text_field( $_REQUEST['switch_to'] );

        $result = apply_filters( 'brewpress_do_switch', $state );

        // handle any program related stuff
        // ie. if the hardware was in the program, put it into manual mode
        $program = $this->get_program();
        if( isset( $program['current_step'] ) ) {

            $cur_step       = $program['current_step'];
            $step           = $program['steps'][$cur_step];
            $step_element   = brewpress_get_hardware( $step['element'] );

            // if we have switched the current element, change mode to manual
            if( $state['hardware_name'] == $step_element['name'] ) {
                $program['mode'] = 'manual';
                $program['steps'][$cur_step]['running'] = false;
                $program['steps'][$cur_step]['state'] = null;
            } 

            $this->update_program( $program );

            $this->ajax_response( $program );

        }

        // js expects a response
        $this->ajax_response( $result );

    }

    /*
     * Exit manual mode
     */
    public function exit_manual_mode() {

        $this->ajax_checks();
        $this->init();

        $program = $this->get_program();
        if( isset( $program['current_step'] ) ) {

            $cur_step   = $program['current_step'];
            $step       = $program['steps'][$cur_step];

            $program['mode'] = $step['mode'];
            $program['steps'][$cur_step]['running'] = true;
            $program['steps'][$cur_step]['state'] = null;

        }

        $this->update_program( $program );

        $this->ajax_response( $program );

    }

    

    /*
     * Log the temps
     */
    public function log_temp( $temp, $sensor ) {

        $log        = get_post_meta( $this->batch_id, "_brewpress_temp_log", true );
        $time       = current_time( 'timestamp' );
        $interval   = brewpress_log_temps();

        if( isset( $log[$sensor] ) ) {
            $last_key = key( array_slice( $log[$sensor], -1, 1, TRUE ) );
            // log every 10+ seconds
            if( $time - $log[$sensor][$last_key]['time'] > $interval ) {
                $log[$sensor][] = array( 'time' => $time, 'temp' => $temp );
            }
        } else {
            $log[$sensor][] = array( 'time' => $time, 'temp' => $temp );
        }

        update_post_meta( $this->batch_id, "_brewpress_temp_log", $log );


    }


    /*
     * Get the current program
     * and set the batch_id
     */
    public function get_program( $batch_id = null ) {
        //$this->batch_id = isset( $_REQUEST['batch_id'] ) ? $_REQUEST['batch_id'] : $batch_id;
        return get_post_meta( $this->batch_id, '_brewpress_program', true );
    }

    /*
     * Update the program in the database
     */
    public function update_program( $program ) {
        update_post_meta( $this->batch_id, '_brewpress_program', $program );
    }

    /*
     * Update the temp logs
     */
    public function set_temp_logs( $temps ) {
        update_post_meta( $this->batch_id, '_brewpress_temp_log', $temps );
    }

    /*
     * return the status as an array
     */
    public function get_status( $status ) {
        $statuses = $this->statuses;
        return array( $status, $statuses[ $status ] );
    }


    /*
     * Verify our nonce
     */
    public function ajax_checks() {
        if ( ! ( isset( $_REQUEST['nonce'] ) && wp_verify_nonce( $_REQUEST['nonce'], 'brewpress_nonce' ) ) ) {
            $error = array( 
                'type'  => 'nonce',
                'msg'   => 'Nonce error',
            );
            $this->ajax_response( $error, false );
        }
    }


    /*
     * send the ajax resonse
     */
    public function ajax_response( $data, $success = true ) {
        wp_send_json( $data, $success );
    }

    
}

return new BrewPress_Frontend_Brewing;
?>