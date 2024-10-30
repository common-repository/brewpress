<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * BrewPress_Setup Class.
 */
class BrewPress_Frontend_Batch_Output {


	public function __construct() {
        add_action( 'brewpress_batch_output_row_1', array( $this, 'row_1' ) );
        add_action( 'brewpress_batch_output_row_2', array( $this, 'row_2' ) );
		add_action( 'brewpress_batch_output_row_3', array( $this, 'row_3' ) );
	}
	

    public function row_1( $post ) {
        
        $this->start();
        
        $program = brewpress_get_batch_program( $post->ID );

        ?>
        <div class="row upper">

            <div class="col-sm-12">

                <h3><?php esc_html_e( brewpress_get_batch_name( $post->ID ) ); ?></h3>
                <p class="style"><?php esc_html_e( brewpress_get_batch_style( $post->ID ) ); ?></p>

            </div>   

        </div> 
        <?php
        
        $this->output();

    }


	public function row_2( $post ) {
		
        $this->start();
        
        $program = brewpress_get_batch_program( $post->ID );

        ?>

            <dl class="row info">

                <dt class="col-sm-2 text-right"><?php _e( 'Program' ); ?>:</dt>
                <dd class="col-sm-10">
                    <?php if( isset( $program['start'] ) && $program['start'] != '' ) {
                        echo date_i18n( get_option( 'time_format' ) , $program['start'] ); 
                    }
                    ?><?php if( isset( $program['end'] ) && $program['end'] != '' ) {
                        echo ' - ' . date_i18n( get_option( 'time_format' ) , $program['end'] ); 
                    }
                    ?>
                </dd>

                <?php if( $program['steps'] ) { 
                    foreach ($program['steps'] as $key => $step) { ?>

                        <dt class="col-sm-2 text-right"><?php echo __( 'Step ' ) . $step['id']; ?>:</dt>
                        <dd class="col-sm-10">
                            <span><?php esc_html_e( $step['target_temp'] ); ?><?php esc_html_e( brewpress_get_temp_unit(get_current_user_id()) ); ?> - <?php esc_html_e( $step['time'] / 60 ); ?> <?php _e( 'Minutes'); ?></span>
                            <span><?php echo date_i18n( get_option( 'time_format' ), $step['start'] );  ?> - <?php echo date_i18n( get_option( 'time_format' ), $step['end'] );  ?></span>
                            
                        </dd>

                <?php }
                } ?>
                
            </dl>

        <?php
        
        $this->output();

	}


	public function row_3( $post ) {
		
        $this->start();
        
        $program    = brewpress_get_batch_program( $post->ID );
        $temps      = brewpress_meta( '_brewpress_temp_log', $post->ID );

        $datasets   = array();
        $annotations = array();

        $i = 0;
        if( $temps ) {
            foreach ($temps as $sensor => $val) {

                $rgb = $this->rgb( $sensor );
                $datasets[$i]['label']              = brewpress_get_sensor_vessel( $sensor );
                $datasets[$i]['type']               = 'line';
                $datasets[$i]['borderWidth']        = '1';
                $datasets[$i]['backgroundColor']    = 'rgba('.$rgb[0].','.$rgb[1].','.$rgb[2].',0.4)';
                $datasets[$i]['borderColor']        = 'rgba('.$rgb[0].','.$rgb[1].','.$rgb[2].',0.8)';
                
                if( $val ) {
                    foreach ($val as $index => $data) {
                        $datasets[$i]['data'][$index]['x'] = date_i18n( 'H:i:s', $data['time'] );
                        $datasets[$i]['data'][$index]['y'] = $data['temp'];
                    }
                }
                
                $i++;
            }
        }

        if( isset( $program['steps'] ) && is_array( $program['steps'] ) && ! empty( $program['steps'] ) ) {
            
            $count = count($program['steps']);

            foreach ( $program['steps'] as $i => $step ) {
                
                $y_adjust = round( $i * ( 100 / $count ) );
                $rgb = $this->rgb( $sensor, $y_adjust );

                // do the start time
                if( isset( $step['start'] ) && $step['start'] != '' ) {
                    $annotations[$i]['label']['content']          = __( 'Step ', 'brewpress' ) . $i;
                    $annotations[$i]['label']['backgroundColor']  = 'rgba('.absint( $rgb[0] ).','.absint( $rgb[1] ).','.absint( $rgb[2] ).',0.6)';
                    $annotations[$i]['label']['enabled']          = true;
                    $annotations[$i]['label']['position']         = 'bottom';
                    $annotations[$i]['label']['yAdjust']          = absint( $y_adjust );
                    $annotations[$i]['type']                      = 'line';
                    $annotations[$i]['mode']                      = 'vertical';
                    $annotations[$i]['scaleID']                   = 'x-axis-0';
                    $annotations[$i]['borderColor']               = 'rgba(0,0,0,0.4)';
                    $annotations[$i]['borderWidth']               = 2;
                    $annotations[$i]['value']                     = date_i18n( 'H:i:s', $step['start'] );
                }
                    
                // do the end time
                if( isset( $step['end'] ) && $step['end'] != '' ) {
                    $annotations[$i*100]['label']['content']          = __( 'Step ', 'brewpress' ) . $i;
                    $annotations[$i*100]['label']['backgroundColor']  = 'rgba('.absint( $rgb[0] ).','.absint( $rgb[1] ).','.absint( $rgb[2] ).',0.7)';
                    $annotations[$i*100]['label']['enabled']          = true;
                    $annotations[$i*100]['label']['position']         = 'bottom';
                    $annotations[$i*100]['label']['yAdjust']          = absint( $y_adjust );
                    $annotations[$i*100]['type']                      = 'line';
                    $annotations[$i*100]['mode']                      = 'vertical';
                    $annotations[$i*100]['scaleID']                   = 'x-axis-0';
                    $annotations[$i*100]['borderColor']               = 'rgba(0,0,0,0.4)';
                    $annotations[$i*100]['borderWidth']               = 2;
                    $annotations[$i*100]['value']                     = date_i18n( 'H:i:s', $step['end'] );
                }

            }

        }

        $annotations = array_values($annotations);

        ?>

            <div class="chart-container">
                <h3></h3>
                <canvas height="300" id="canvas" style="width:100%"></canvas>
            </div>

            <script>

                var chartData = {
                    datasets: <?php echo json_encode( $datasets ); ?>
                };

                window.onload = function() {
                    var ctx = document.getElementById('canvas').getContext('2d');
                    window.brewbressChart = new Chart(ctx, {
                        type: 'line',
                        fill: true,
                        data: chartData,
                        options: {
                            // Elements options apply to all of the options unless overridden in a dataset
                            // In this case, we are setting the border of each horizontal bar to be 2px wide
                            responsive: false,
                            maintainAspectRatio: false,
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: false,
                            },
                            scales: {
                                xAxes: [{
                                    type: 'time',
                                    display: true,
                                    time: {
                                        format: 'HH:mm:ss',
                                        min: '<?php echo date_i18n( 'H:i:s', $program['start'] - 60 ); ?>',
                                        max: '<?php echo $program['end'] != '' ? date_i18n( 'H:i:s', $program['end'] + 60 ) : null; ?>',
                                    }
                                }],
                            },
                            annotation: {
                                events: ["click"],
                                annotations: <?php echo json_encode( $annotations ); ?>
                            }
                        }
                    });

                };

            </script>

        <?php

        $this->output();

	}


    public function rgb( $sensor, $adjust = '' ) {
        // something stupid to get the same colors for each sensor every time
        $sensor = $sensor . $adjust;
        $numbers = preg_replace("/[^0-9]/", "", md5( $sensor . '12' ) );
        $r = substr( $numbers, 0, 3 );
        $g = substr( $numbers, 2, 3 );
        $b = substr( $numbers, 4, 3 );
        $r = $r > 255 ? $r / 3 : $r;
        $g = $g > 255 ? $g / 4 : $g;
        $b = $b > 255 ? $b / 3 : $b;
        return array($r,$g,$b);
    }

	/**
	 * Simple wrappers
	 *
	 * @since 1.0.0
	 */
	public function start() {
		ob_start( null, 0 );
	}

	public function output() {
		$content = ob_get_contents(); 
		ob_end_clean(); 
		echo $content;
	}


}

return new BrewPress_Frontend_Batch_Output;