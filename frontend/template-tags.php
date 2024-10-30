<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * //// GENERAL
 */
function brewpress_field_append( $text ) {
	$return = '
		<div class="input-group-append">
			<div class="input-group-text">' . $text . '</div>
		</div>
	</div>';
	return $return;
}
function brewpress_get_volume_unit_field( $user_id = null ) {
	$return = brewpress_field_append( brewpress_get_volume_unit( $user_id = null ) );
	return $return;
}

function brewpress_get_volume_unit($user_id = null ) {
	$data 		= brewpress_user_option( '_brewpress_volume_unit', true, $user_id );
	$return 	= $data == 'metric' ? 'L' : 'G';
	return $return;
}
function brewpress_get_temp_unit($user_id = null ) {
	$data 		= brewpress_user_option( '_brewpress_temp_unit', true, $user_id );
	$return 	= $data == 'c' ? '°C' : '°F';
	return $return;
}
/*
 * Used within the sse.php file to calculate temp depending on unit.
 */
function brewpress_temp_unit( $user_id = null ) {
	$default 	= null;
	$data 		= brewpress_user_option( '_brewpress_temp_unit', true, $user_id );
	return $data ? $data : $default;
}
function brewpress_heartbeat( $user_id = null ) {
	$default 	= '2';
	$data 		= brewpress_user_option( '_brewpress_heartbeat', true, $user_id );
	return $data ? $data : $default;
}
function brewpress_log_temps( $user_id = null ) {
	$default 	= '30';
	$data 		= brewpress_user_option( '_brewpress_log_temps', true, $user_id );
	return $data ? $data : $default;
}
function brewpress_debug( $user_id = null ) {
	$default 	= 'off';
	$data 		= brewpress_user_option( '_brewpress_debug', true, $user_id );
	return $data ? $data : $default;
}
function brewpress_testing( $user_id = null ) {
	$default 	= 'off';
	$data 		= brewpress_user_option( '_brewpress_testing', true, $user_id );
	$result 	= $data ? $data : $default;
	return $result == 'on' ? true : false;
}



/**
 * //// BATCHES
 */
function brewpress_get_batches() {
	return get_posts( array( 
		'post_type' => 'batch', 
		'post_status' => 'publish', 
		'fields' => 'ids', 
		'posts_per_page' => -1, 
		'author' => get_current_user_id(),  
	) );
}
function brewpress_get_batch_name( $id = null ) {
	$data = brewpress_meta( '_brewpress_batch_name', $id );
	return $data;
}
function brewpress_get_batch_created_date( $id = null ) {
	$data = get_the_date( null, $id );
	return $data;
}
function brewpress_get_batch_brewed_date( $id = null ) {
	$data = brewpress_meta( '_brewpress_program', $id );
	return isset( $data['start'] ) ? date_i18n( get_option( 'date_format' ), $data['start'] ) : '';
}
function brewpress_get_batch_volume( $id = null ) {
	$data = brewpress_meta( '_brewpress_batch_volume', $id );
	if( $data )
		$data .= '<span class="volume">' . brewpress_get_volume_unit() . '</span>';
	return $data;
}
function brewpress_get_batch_style( $id = null ) {
	$data = brewpress_meta( '_brewpress_batch_style', $id );
	return $data;
}

function brewpress_get_batch_steps( $id = null ) {
	$data = brewpress_meta( '_brewpress_batch_steps', $id );
	return $data;
}

function brewpress_get_sse_query_string( $id = null ) {
	$sensors 	= brewpress_get_sensors();
	$data 		= array();
	$data['id'] = $id;
	if( $sensors ) {
		foreach ( $sensors as $name => $sensor ) {
			$data[$name] = $sensor;
		}
	}
	return $data;
}

function brewpress_get_batch_program( $id = null ) {
	$data = brewpress_meta( '_brewpress_program', $id );
	return $data;
}

function brewpress_batch_program_started( $id = null ) {
	$program = brewpress_meta( '_brewpress_program', $id );
	if( isset( $program['start'] ) && $program['start'] != '' ) {
		return true;
	} else {
		return false;
	}
}

function brewpress_get_batch_brew_url( $id = null ) {
	return add_query_arg( array( 'id' => $id ), home_url( '/brewing') );
}

function brewpress_get_batches_times( $id = null ) {
	$batches 	= brewpress_get_batches();
	
	$total 		= array();
	$times 		= array();
	
	if( $batches ) {
		foreach ($batches as $key => $batch_id) {

			$program = brewpress_meta( '_brewpress_program', $batch_id );

			if( 
				( isset( $program['start'] ) && $program['start'] != '' ) && 
				( isset( $program['end'] ) && $program['end'] != '' ) 
			) {	

				$times[] = array( 
					'id' 	=> $batch_id,
					'total'	=> $program['end'] - $program['start'],
					'start' => $program['start'],
					'end' 	=> $program['end'],
				);

			}
		}
	}


	$totals 	= wp_list_pluck( $times, 'total' );
	$sum 		= $totals ? array_sum($totals) : 0;
	$min_value 	= $totals ? min($totals) : 0;
	$max_value 	= $totals ? max($totals) : 0;

	return array(
		'total' 	=> brewpress_format_hms( $sum ),
		'shortest' 	=> brewpress_format_hms( $min_value ),
		'longest' 	=> brewpress_format_hms( $max_value ),
	);
}


function brewpress_format_hms( $seconds ) {
	$hours      = sprintf("%02d", floor( $seconds / 3600 ) );
    $minutes    = sprintf("%02d", floor( ($seconds / 60 ) % 60) );
    $seconds    = sprintf("%02d", $seconds % 60 );
    return "$hours:$minutes:$seconds";
}

/**
 * //// HARDWARE
 */
function brewpress_get_elements( $user_id = null ) {
	$elements = brewpress_user_option( '_brewpress_elements', true, $user_id );
	if( ! $elements )
		return array();
	return $elements;
}

function brewpress_get_pumps() {
	$pumps = brewpress_user_option( '_brewpress_pumps', true );
	if( ! $pumps )
		return array();
	return $pumps;
}

function brewpress_get_all_hardware() {
	$pumps 		= brewpress_get_pumps();
	$elements 	= brewpress_get_elements();
	$hardware 	= array_merge( $pumps, $elements );
	return apply_filters( 'brewpress_get_all_hardware', $hardware );
}

// get a single element including all it's data
function brewpress_get_hardware( $hardware_name ) {
    if( ! $hardware_name )
		return;
    $hardwares = brewpress_get_all_hardware();
    if( $hardwares ) {
        foreach ($hardwares as $key => $hardware) {
            if( $hardware_name === $hardware['name'] )
            	return $hardware;
        }
    }
}

// get all sensors 
function brewpress_get_sensors( $user_id = null ) {
	
	$sensors = array();
	$elements = brewpress_get_elements( $user_id );
	
	if( $elements ) {
	    foreach ( $elements as $key => $element ) { 

	    	if( isset( $element['name'] ) && $element['name'] != '' && isset( $element['sensor'] ) && $element['sensor'] != '' ) {
	        	// ensures we only get the first one
	        	if( in_array( $element['sensor'], $sensors ) )
	        		continue;
	        	$sensors[$element['name']] = $element['sensor'];
	        }

	    }
	}

	$return = array_unique( $sensors );

	return $return;
}


function brewpress_get_sensor_vessel( $sensor = null ) {
	if( ! $sensor )
		return $sensor;
	$sensors = brewpress_get_sensors();

	foreach ( $sensors as $hardware_name => $sens ) { 

    	if( $sens == $sensor ) {
        	return $hardware_name;
        }

    } 
    
}