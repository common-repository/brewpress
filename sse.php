<?php
header('Cache-Control: no-cache');
header("Content-Type: text/event-stream\n\n");
require_once( 'wp-load.php' );
$interval = (int) brewpress_heartbeat();

// 1 is always true, so repeat the while loop forever (aka event-loop)
while (1) {
 
    $sensors = array();
 
    $batch_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : false;
 
    if( isset( $_GET ) ) {
        foreach ( $_GET as $key => $sensor ) {
             
            if( $key == 'id' || $key == 'program' )
                continue;
 
            if( ! $sensor )
                $sensors[$sensor] = 0;
 
            $temp_path  = "/sys/bus/w1/devices/$sensor/w1_slave";
            $str        = @file_get_contents($temp_path);
                 
            if( $str === false || ! $str || $str == 0 )
                $sensors[$sensor] = 0;
 
            if( preg_match('|t\=([0-9]+)|mi', $str, $m) ){ 
                $the_temp = $m[1] / 1000; 
            } 
 
            if( brewpress_testing() ) {
                $the_temp = rand( 69, 73 );
            }
 
            $unit = brewpress_temp_unit();
            if( $unit == 'f' ) {
                $the_temp = $the_temp * 9.0 / 5.0 + 32.0;
            }
 
            $sensors[$sensor] = number_format( $the_temp, 2 );
 
            brewpress_log_temp( $batch_id, $the_temp, $sensor );
        }
    }
 
    $id = time();
    echo "id: $id" . PHP_EOL;
    echo "data: " . json_encode( $sensors ) . PHP_EOL;
    echo PHP_EOL;
    //echo 'data: This is a message at time ' . $curDate, "\n\n";
    // flush the output buffer and send echoed messages to the browser
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
 
    // break the loop if the client aborted the connection (closed the page)
    if ( connection_aborted() ) break;
 
    // sleep for x seconds before running the loop again
    sleep( (int) $interval );
}
 
 
/*
 * Log the temps
 */
function brewpress_log_temp( $batch_id, $temp, $sensor ) {
 
    $running = isset( $_GET['program'] ) && $_GET['program'] == 'running' ? true : false;
    if( ! $running )
        return;
 
    $log            = get_post_meta( $batch_id, "_brewpress_temp_log", true );
    $time           = current_time( 'timestamp' );
    $log_interval   = brewpress_log_temps();
    
    do_action( 'brewpress_get_temp_log_before_logging', $log );

    if( isset( $log[$sensor] ) ) {
         
        $last_key = key( array_slice( $log[$sensor], -1, 1, TRUE ) );
 
        if( $time - $log[$sensor][$last_key]['time'] > $log_interval ) {
            $log[$sensor][] = array( 'time' => $time, 'temp' => $temp );
        }
 
    } else {
 
        $log[$sensor][] = array( 'time' => $time, 'temp' => $temp );
 
    }
 
    update_post_meta( $batch_id, "_brewpress_temp_log", $log );
 
}