<?php 


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class BrewPress_Frontend_Switching {
    

    public function __construct() {

        add_action( 'brewpress_switch_all_off', array( $this, 'all_off' ) );
        add_filter( 'brewpress_do_switch', array( $this, 'do_switch' ), 10 );

    }

        
    public function all_off() {
        if( brewpress_testing() )
            return;

        $hardwares  = brewpress_get_all_hardware();
        $command    = 0; // command is off

        if( $hardwares ) :
            foreach ( $hardwares as $key => $hardware ) {
                if( isset( $hardware['gpio'] ) && $hardware['gpio'] != '' ) {
                    $gpio       = $hardware['gpio'];
                    $setmode    = shell_exec("gpio -g mode $gpio out ");
                    $switch     = shell_exec("gpio -g write $gpio $command");
                }
            }
        endif;

    }


    public function do_switch( $state ) {
        if( brewpress_testing() )
            return;
        
        $hardware = brewpress_get_hardware( $state['hardware_name'] );
        if( isset( $hardware['gpio'] ) && $hardware['gpio'] != '' ) {
            $gpio       = $hardware['gpio'];
            $command    = $state['command'] == 'on' ? 1 : 0; // command is either on or off
            $setmode    = shell_exec("gpio -g mode $gpio out ");
            $switch     = shell_exec("gpio -g write $gpio $command");
        }
        return $state;
    }


}

return new BrewPress_Frontend_Switching();
?>