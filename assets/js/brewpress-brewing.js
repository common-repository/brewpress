/*
 * Program Start
 */    

window.BrewPress = window.BrewPress || {};

    function pp(variable) {
        console.log(variable);
    }

(function(window, document, $, brewpress, undefined){
    'use strict';

    // global variables
    var l10n            = window.brewpress_l10;
    var $document;
    var batch_id        = $('.brewing').data('batch-id');
    var current_time    = l10n.current_time;
    var timer           = new Timer();
    var step_timer      = new Array( l10n.all_steps.length );
    var $start          = $('button.start');
    var $pause          = $('button.pause');
    var $reset          = $('button.reset');
    var $view           = $('a.view');
    var defaults = {
        defaults : {
            
        },
    };
    var ajaxQueue = $({});

    brewpress.ajaxQueue = function(ajaxOpts) {
        var oldComplete = ajaxOpts.complete;
        ajaxQueue.queue(function(next) {
            ajaxOpts.complete = function() {
                if (oldComplete) oldComplete.apply(this, arguments);
                    next();
            };
            $.ajax(ajaxOpts);
        });
    };

    brewpress.brewing = function() {
        if ( brewpress.$brewing ) {
            return brewpress.$brewing;
        }
        brewpress.$brewing = $('.brewing');
        return brewpress.$brewing;
    };

    brewpress.init = function() {

        $document = $( document );

        // Setup the BrewPress object defaults.
        $.extend( brewpress, defaults );

        brewpress.trigger( 'brewpress_pre_init' );

        var $brewing = brewpress.brewing();

        // start the heartbeat
        brewpress.initHeartbeat();

        // init timers
        brewpress.initMainTimer();
        brewpress.initStepTimers();

        // check if we have been offline and update accordingly
        brewpress.initBeenOffline();

        $brewing
            .on( 'click', 'button.start', brewpress.startProgram )
            .on( 'click', 'button.pause', brewpress.pauseProgram )
            .on( 'click', 'button.restart', brewpress.restartProgram )
            .on( 'click', 'button.reset', brewpress.resetProgram )
            .on( 'click', 'button.restart-step', brewpress.restartCurrentStep )
            .on( 'click', 'button.next-step', brewpress.skipToNextStep )
            .on( 'click', '.manual-controls button', brewpress.manualButton )
            .on( 'click', '.manual-mode', brewpress.exitManualMode );

        $document
            .on( 'brewpress_heartbeat', brewpress.outputTemps )
            .on( 'brewpress_heartbeat', brewpress.doStep )
            .on( 'brewpress_heartbeat', brewpress.updateSteps )
            .on( 'brewpress_heartbeat', brewpress.printProgram );

        brewpress.trigger( 'brewpress_init' );
    };



    /** 
     * Starts the program
     * 
     */
    brewpress.startProgram = function( evt ) {

        // if we are paused, do nothing as we want to Restart
        if( $start.hasClass( 'restart' ) )
            return false;

        brewpress.ajaxQueue({
            url : l10n.ajax_url, type : 'post', dataType: 'json',
            data : { 
                action      : "brewpress_start_program", 
                nonce       : l10n.nonce, 
                batch_id    : batch_id,
            },
            error : function( response ) {
                pp(response)
            },
            success : function( program ) {

                if( ! program )
                    return false;
                
                // start main timer
                timer.start();
                
                // change the pause and play button states
                brewpress.startBtn( 'on' );
                brewpress.pauseBtn( 'off' );
                
                // show the view button
                $view.show()

                // clear it as a precaution
                localStorage.removeItem('program' );

                // set the program into local storage
                localStorage.setItem( 'program', JSON.stringify( program ) );

                // output the step status
                brewpress.updateProgramStatus( program );

                // init the heartbeat again to let the SSE file know that we have started the program
                brewpress.initHeartbeat();

            }

        });

    };


    /** 
     * Pauses the program
     * 
     */
    brewpress.pauseProgram = function( evt ) {

        var program = JSON.parse( localStorage.getItem('program') );

        if( ! program || program.running == false )
            return

        console.log('pause');

        brewpress.ajaxQueue({
            url : l10n.ajax_url, type : 'post', dataType: 'json',
            data : { 
                action      : "brewpress_pause_program", 
                nonce       : l10n.nonce, 
                batch_id    : batch_id, 
            },
            success : function( program ) {
                
                if( ! program )
                    return false;

                // update local storage with new program
                localStorage.setItem( 'program', JSON.stringify( program ) );
                
                // pause step timers
                $.each( step_timer, function( index, s_timer ){
                    if( s_timer !== undefined ) {
                        s_timer.pause();
                    }
                });

                // change the pause and play button states
                brewpress.startBtn( 'off' );
                brewpress.pauseBtn( 'on' );
                $start.addClass('restart');

                // turn off manual buttons
                var $manualButton = $( '.manual-controls button' );
                $manualButton.removeClass( $manualButton.data('on') ).addClass( $manualButton.data('off') );

                // remove the confirmation on the start button, so that we can simply hit start again
                $view.data( 'toggle','' );

                // output the step status
                brewpress.updateProgramStatus( program );
                
            }

        });

    };


    /** 
     * Restarts the program after a pause
     * 
     */
    brewpress.restartProgram = function( evt ) {

        console.log('restart');

        brewpress.ajaxQueue({
            url : l10n.ajax_url, type : 'post', dataType: 'json',
            data : { 
                action      : "brewpress_restart_program", 
                nonce       : l10n.nonce, 
                batch_id    : batch_id, 
            },
            success : function( program ) {
                
                if( ! program )
                    return false;

                localStorage.setItem( 'program', JSON.stringify( program ) );

                // change the pause and play button states
                brewpress.startBtn( 'on' );
                brewpress.pauseBtn( 'off' ); 
                $start.removeClass('restart');

                // in case it was removed with reset
                $view.data( 'toggle', 'confirmation').css( 'display','inline-block' ); 

                // loop through each step and get our current step
                // and restart the timer
                $.each(program.steps, function( i, step ) {

                    if( parseInt( step.id ) === parseInt( program.current_step ) ) {
                        // only start the timer if the status is running
                        // if we are heating or paused, don't start as it will cause error
                        if( program.status[0] == 'running' ) {
                            step_timer[i].start();
                        }
                    }

                });

                // output the step status
                brewpress.updateProgramStatus( program );

            }

        });
    };

    /** 
     * Resets the program
     *
     */
    brewpress.resetProgram = function( evt ) {
                
        console.log('reset');

        brewpress.ajaxQueue({
            url : l10n.ajax_url, type : 'post', dataType: 'json',
            data : { 
                action      : "brewpress_reset_program", 
                nonce       : l10n.nonce, 
                batch_id    : batch_id, 
            },
            success : function( program ) { // should be empty response
                
                timer.reset();
                timer.stop();
                
                // change the pause and play button states
                brewpress.startBtn( 'off' );
                brewpress.pauseBtn( 'off' ); 
                $start.removeClass('restart');
                
                $view.data( 'toggle', '' ).css('display','none');

                $( '.row.program-info' ).remove(); 

                var $manualButton = $( '.manual-controls button' );
                $manualButton.removeClass( $manualButton.data('on') ).addClass( $manualButton.data('off') ); 
                $manualButton.find( 'i' ).removeClass('fa-exclamation-triangle').addClass('fa-power-off'); 
                $('.manual-mode').html('');

                $( '#progress .step' ).text( l10n.words.not_started ); 
                $( '#progress .status' ).text( '' ); 
                $( '.step' ).removeClass( 'running heating paused finished' );
                $( '.step h3 i' ).remove();
                
                // reset the step timers and re-initialize
                $.each( step_timer, function( index, s_timer ){
                    if( s_timer !== undefined ) {
                        s_timer.reset();
                        s_timer.stop();
                    }
                });
                brewpress.initStepTimers();

                localStorage.removeItem( 'program' );

                // init the heartbeat again to let the SSE file know that we have reset the program
                brewpress.initHeartbeat();
            }
        });
    };


    /** 
     * Heartbeat returns data for the temps every xx seconds
     * 
     */
    brewpress.initHeartbeat = function( evt ) {
        
        var queryString = $.param( l10n.sse_query_string );

        // if the program is running, 
        // add this to the query string and send to our SSE file
        // This is so we only record temps during the program
        var program = JSON.parse( localStorage.getItem( 'program' ) );

        if( program && program.running === true ) {
            queryString = queryString + '&program=running';
        } else if( program && program.running === false ) {
            queryString = queryString + '&program=false';
        }

        var evtSource = new EventSource( l10n.home_url + '/sse.php?' + queryString );
        //var evtSource = new EventSource( l10n.home_url + '/wp-content/plugins/brewpress/sse.php?' + queryString );

        evtSource.onopen = function() {
            console.log("Connection to server opened.");
        };
        evtSource.onerror = function() {
            console.log("EventSource failed.");
        };

        evtSource.onmessage = function(e) {
            var data = JSON.parse( e.data );
            brewpress.trigger( 'brewpress_heartbeat', data );
        };

    }    


    /**
     * Output the temps
     * runs every heartbeat
     */
    brewpress.outputTemps = function( evt, temps ) {
        $.each(temps,function( sensor, temp ) {
            var $sensor = $( '.row.temps' ).find("[data-sensor-id='" + sensor + "']"); 
            $sensor.find( '.temp' ).text( temp );
        });
    }
    

    /**
     * Do the switching of the element within the step
     * runs every heartbeat
     */
    brewpress.doStep = function( evt, temps ) {

        var program = JSON.parse( localStorage.getItem('program') );

        if( ! program || program.end != '' || program.running != true || program.status[0] == 'paused' )
            return;

        if( program.mode == 'manual' )
            return;

        brewpress.trigger( 'brewpress_before_step', temps, program );

        var currentStep = program.current_step;
        var step        = program.steps[currentStep];
        var currentTemp = parseFloat( temps[step.sensor] );
        var command     = null;

        /*
         * If we need to switch element on
         */
        if( currentTemp < step.temp_on ) {
            command = 'on';
        }

        /*
         * If we need to switch element off
         */
        if( currentTemp > step.temp_off ) {
            command = 'off';
        }
        
        /*
         * If we haven't started the step
         * and the temp is below target temp, turn it on.
         * This will likely cause an overshoot, so may change this.
         */
        if( 
            ( ! step.start || step.start.length <= 0 ) && 
            ( currentTemp < step.target_temp ) 
        ) {
            command = 'on';
        }

        /*
         * If we have a command and it is different to current step state
         */
        if( command != null && command != step.state ) {
            brewpress.doProgramSwitch( command, step );
        }

        /*
         * Start the step
         * Once we hit the temp for the first time
         */
        if( currentTemp >= step.target_temp ) {
            if( step.start == '' ) {
                brewpress.startStep( step );
            }
        }

    }


    /** 
     * Do a switch when the program is running
     */
    brewpress.doProgramSwitch = function( command, step ) {

        brewpress.ajaxQueue({
            url :       l10n.ajax_url,
            type :      'post',
            dataType:   'json',
            data : { 
                action : "brewpress_do_program_switch", 
                nonce : l10n.nonce, 
                batch_id : batch_id, 
                step : step,
                command : command
            },
            success : function( program ) {

                // sets the program with the new state of the element
                localStorage.setItem( 'program', JSON.stringify( program ) );

                // update the button states
                var button = $( "[data-name='" + step.element +"']" );

                if( command == 'on' ) {
                    $( button ).data( 'state', 'on' );
                    $( button ).removeClass( $( button ).data('off') ).addClass( $( button ).data('on') );
                }
                if( command == 'off' ) {
                    $( button ).data( 'state', 'off' );
                    $( button ).removeClass( $( button ).data('on') ).addClass( $( button ).data('off') ); 
                }

            }

        });

    }


    /**
     * Start a step
     * Run when the temp reaches target temp for the step
     */
    brewpress.startStep = function( step ) {

        brewpress.ajaxQueue({
            url :       l10n.ajax_url,
            type :      'post',
            dataType:   'json',
            data : { 
                action : "brewpress_start_step", 
                nonce : l10n.nonce, 
                batch_id : batch_id, 
                step : step,
            },
            success : function(program) {
                // sets the program 
                // as running and start time that was set in php
                localStorage.setItem('program', JSON.stringify( program ) );

                // clear our manual mode
                if( program.mode != 'manual' ) {
                    $( '.manual-controls button' ).find('i').removeClass('fa-exclamation-triangle').addClass('fa-power-off');
                    $('.manual-mode').html('');
                }

                // start timer intially
                step_timer[step.id].reset();
                step_timer[step.id].start();

                // output the step status
                brewpress.updateProgramStatus( program );

            }
            
        });

    }

    /**
     * Go to next step
     */
    brewpress.nextStep = function( step ) {

        brewpress.ajaxQueue({
            url :       l10n.ajax_url,
            type :      'post',
            dataType:   'json',
            data : { 
                action : "brewpress_next_step", 
                nonce : l10n.nonce, 
                batch_id : batch_id, 
                step : step,
            },
            success : function( program ) {

                // sets the program that was set in php
                localStorage.setItem( 'program', JSON.stringify( program ) );
                
                // stop the current step timer
                step_timer[step.id].stop();

                // output the step status
                brewpress.updateProgramStatus( program );

                $('.step h3 span').html('');
            }
            
        });

    }


    /**
     * Finish the program
     */
    brewpress.finish = function( step ) {

        brewpress.ajaxQueue({
            url :       l10n.ajax_url,
            type :      'post',
            dataType:   'json',
            data : { 
                action : "brewpress_finish_program", 
                nonce : l10n.nonce, 
                batch_id : batch_id, 
                step : step,
            },
            success : function( program ) {
                
                // sets the program 
                // as finished as was set in php
                localStorage.setItem('program', JSON.stringify( program ) );
                
                // stop main timer
                timer.stop();

                // stop step timer
                step_timer[step.id].stop();

                // turn start button off
                brewpress.startBtn( 'off' );
                
                // turn all manual buttons off
                var $manualButton = $( '.manual-controls button' );
                $manualButton.removeClass( $manualButton.data('on') ).addClass( $manualButton.data('off') ); ;

                // remove all classes from steps
                $('.step').removeClass( 'running heating paused' ).addClass( 'finished' );
                
                // output the step status
                brewpress.updateProgramStatus( program );

                var last_step = l10n.all_steps.length;
                var $step = $(".step[data-id='" + last_step +"']");
                $step.addClass( program.status[0] );
                $step.find('h3 span').html('');

                // init the heartbeat again to let the SSE file know that we have finished the program
                // this will stop the logging of temps
                brewpress.initHeartbeat();

            }
            
        });

    }



    /**
     * Look for any updates that need to be run for the steps
     * 
     * Updated on the heartbeat.
     */
    brewpress.updateSteps = function( evt, temps ) {

        var program = JSON.parse( localStorage.getItem('program') );

        if( ! program || program === null || program.running != true || ! program.steps || program.steps == '' ) {
            $( '.step' ).removeClass( 'running heating paused' );
            return;
        }

        var current = program.current_step;
        
        // if no current step
        // meaning we have finished
        if( ! current )
            return;

        var step    = program.steps[current];
        var time    = brewpress.currentTime();
        var $step   = $(".step[data-id='" + step.id +"']");
        var icon;

        // update the step status
        $('.step').removeClass( 'running heating paused' );
        
        if( ! $step.hasClass( program.status[0] ) ) {

            $step.addClass( program.status[0] );

            switch ( program.status[0] ) {
                case 'heating':
                    icon = '<i class="fa fa-fire"></i>';
                    break;
                case 'running':
                    icon = '<i class="fa fa-cog fa-spin"></i>';
                    break;
                case 'paused':
                    icon = '<i class="far fa-pause-circle"></i>';
                    break;
            }
        }

        $step.find('h3 span').html(icon);

        // have we ended the step
        if( parseInt( time ) > parseInt( step.end ) ) {

            // if we need to go to the next step
            if( l10n.all_steps.length > step.id ) {

                brewpress.nextStep( step );

            // else we are finished
            } else {

                brewpress.finish( step );

            }

        }

    }



    /** 
     * Do something after a control button click
     *
     */
    brewpress.manualButton = function( evt ) {

        evt.preventDefault();

        var $this           = $( this );
        var state           = $this.attr('data-state');
        var hardware_name   = $this.attr('data-name');
        var switch_to       = state == 'off' ? 'on' : 'off';

        brewpress.ajaxQueue({

            url : l10n.ajax_url, type : 'post', dataType: 'json',
            data : { 
                action          : "brewpress_manual_button", 
                nonce           : l10n.nonce, 
                hardware_name   : hardware_name, 
                switch_to       : switch_to, 
                batch_id        : batch_id,
            },
            success : function(program) {

                if( ! program )
                    return false;

                // sets the program 
                // as was set in php
                localStorage.setItem( 'program', JSON.stringify( program ) );

                // output the step status
                brewpress.updateProgramStatus( program );

                $this.removeClass( $this.data( state ) ).addClass( $this.data( switch_to ) );
                $this.attr( 'data-state', switch_to );

                // if we are in manual mode
                // meaning we are taking this element out of the program ie. overriding the program
                if(program.mode && program.mode == 'manual' ) {
                    $this.find('i').removeClass('fa-power-off').addClass('fa-exclamation-triangle');
                    $('.manual-mode').html( l10n.words.manual_mode_alert );
                }

            }

        });

    }


    /** 
     * Exit manual mode and go back to the main program
     *
     */
    brewpress.exitManualMode = function( evt ) {

        evt.preventDefault();

        var $this = $( this );
        brewpress.ajaxQueue({

            url : l10n.ajax_url, type : 'post', dataType: 'json',
            data : { 
                action          : "brewpress_exit_manual_mode", 
                nonce           : l10n.nonce, 
                batch_id        : batch_id,
            },
            success : function(program) {

                if( ! program )
                    return false;

                // sets the program 
                // as was set in php
                localStorage.setItem('program', JSON.stringify( program ) );

                // output the step status
                brewpress.updateProgramStatus( program );

                // if we are in manual mode
                // meaning we are taking this element out of the program ie. overriding the program
                var $manualButton = $( '.manual-controls button' );
                $manualButton.find( 'i' ).removeClass('fa-exclamation-triangle').addClass('fa-power-off'); 
                $('.manual-mode').html('');

                brewpress.initHeartbeat();

            }

        });

    }


    /** 
     * Restart the current step
     *
     */
    brewpress.restartCurrentStep = function( evt ) {
        
        var program = JSON.parse( localStorage.getItem('program') );

        if( ! program.steps || program.steps == null )
            return;

        // loop through each step and get our current step
        $.each(program.steps, function( i, step ) {

            if( parseInt( step.id ) === parseInt( program.current_step ) ) {

                brewpress.startStep( step );

            }

        });    

    }

    /** 
     * Skip to next step
     *
     */
    brewpress.skipToNextStep = function( evt ) {

        var program = JSON.parse( localStorage.getItem('program') );

        if( ! program.steps || program.steps == null )
            return;

        // loop through each step and get our current step
        $.each(program.steps, function( i, step ) {

            if( parseInt( step.id ) === parseInt( program.current_step ) ) {

                // if we need to go to the next step
                if( l10n.all_steps.length == step.id ) {

                    brewpress.finish( step );

                // else we are finished
                } else {

                    brewpress.nextStep( step );

                }

            }

        }); 

    }



    /** 
     * Init the main timer
     * 
     */
    brewpress.initMainTimer = function() {
        timer.addEventListener('secondsUpdated', function (e) {
            $('#main-timer .hours .digits').html(timer.getTimeValues().toString(['hours']));
            $('#main-timer .minutes .digits').html(timer.getTimeValues().toString(['minutes']));
            $('#main-timer .seconds .digits').html(timer.getTimeValues().toString(['seconds']));
        });
        timer.addEventListener('reset', function (e) {
            $('#main-timer .hours .digits').html('00');
            $('#main-timer .minutes .digits').html('00');
            $('#main-timer .seconds .digits').html('00');
        });
    }


    /** 
     * Init all of the step timers
     * 
     */
    brewpress.initStepTimers = function() {
        $( l10n.all_steps ).each(function( index ) {
            var step = index + 1;
            if ( $('#step-timer-' + ( step ) ) ) {
                step_timer[step] = new Timer();
                step_timer[step].addEventListener('secondsUpdated', function (e) {     
                    $('#step-timer-' + ( step ) ).html( step_timer[step].getTimeValues().toString() );
                });
                step_timer[step].addEventListener('reset', function (e) {
                    $('#step-timer-' + ( step ) ).html('00:00:00');
                });
            }
        });
    }

    /** 
     * Restores the program state if we go offline
     * or the browser is reloaded 
     */
    brewpress.initBeenOffline = function() {

        var program = JSON.parse( localStorage.getItem('program') );

        // only run if we are running the program
        if( ! program || program.running != true )
            return;

        brewpress.updateProgramStatus( program );

        // start the timer with the elapsed total time
        var seconds = current_time - program.start;
        timer.start({ startValues: { seconds: seconds } });

        // do stuff based on status
        if( program.status[0] == 'paused' ) {

            // change the pause and play button states
            brewpress.startBtn( 'off' );
            brewpress.pauseBtn( 'on' ); 
            $start.addClass('restart');

            // loop through each step
            $.each(program.steps, function( i, step ) {
                
                // if the step has started
                if( step.start != '' ) {

                    // the step has been finished
                    if( step.running == false ) {
                        var step_seconds = step.end - step.start;
                    }

                    // this step is still running
                    if( step.running == true ) {
                        var step_seconds = step.timer;
                    }

                    // output the values and then pause, because we are paused
                    step_timer[i].start({ startValues: {seconds: step_seconds } });
                    window.setTimeout(function(){
                        step_timer[i].pause();
                    }, 1001);

                }

            });

        } else {

            // change the pause and play button states
            brewpress.startBtn( 'on' );
            brewpress.pauseBtn( 'off' ); 

            // loop through each step
            $.each(program.steps, function( i, step ) {
                
                // if the step has started
                if( step.start != '' ) {

                    // the step has been finished
                    if( step.running == false ) {
                        var step_seconds = step.end - step.start;
                    }

                    // this step is still running
                    if( step.running == true ) {
                        var step_seconds = current_time - ( step.end - step.time );
                    }

                    // output the values and then pause, because we are paused
                    step_timer[i].start({ startValues: {seconds: step_seconds } });

                    // if not the currently running timer, output the value then stop
                    if( step.running == false ) {
                        step_timer[i].stop();
                    }

                }

            });

        }
        
    }


    /** 
     * Update the status on the program
     * 
     */
    brewpress.updateProgramStatus = function( program ) {

        if( ! program.status )
            return;
        
        var output_step = program.current_step ? l10n.words.step + ' ' + program.current_step : '';
        $('#progress .step').text( output_step ); 
        $('#progress .status').text( program.status[1] );
    }

    /** 
     * Get the current local timestamp
     * 
     */
    brewpress.currentTime = function() {
        var diff    = l10n.current_time - l10n.current_time_gmt;
        var time    = Math.floor((Date.now()/1000) + diff);
        return parseInt(time);
    };

    /** 
     * Change state of start button
     * 
     */
    brewpress.startBtn = function( state ) {
        if( state == 'on' ) {
            $start.removeClass( $start.data('off') ).addClass( $start.data('on') ); 
        } else {
            $start.removeClass( $start.data('on') ).addClass( $start.data('off') ); 
        }
    }

    /** 
     * Change state of pause button
     * 
     */
    brewpress.pauseBtn = function( state ) {
        if( state == 'on' ) {
            $pause.removeClass( $pause.data('off') ).addClass( $pause.data('on') ); 
        } else {
            $pause.removeClass( $pause.data('on') ).addClass( $pause.data('off') ); 
        }
    }


    /** 
     * Prints out the program, pins state etc
     * useful for debuggin or just seeing what is happening
     */
    brewpress.printProgram = function( evt, data ) {
        if( l10n.debug == 'off' )
            return;
        var program = JSON.parse( localStorage.getItem('program') );
        //data = JSON.parse( program );
        var output = ''; 
        for (var key in program) {
            output = output + ( '<strong>' + key + '</strong> : ' +JSON.stringify( program[key], null, "\t" ) )+'<br>';
        }
        $('#print').html(output);
    }


    brewpress.trigger = function( evtName ) {
        var args = Array.prototype.slice.call( arguments, 1 );
        args.push( brewpress );
        $document.trigger( evtName, args );
    };

    brewpress.triggerElement = function( $el, evtName ) {
        var args = Array.prototype.slice.call( arguments, 2 );
        args.push( brewpress );
        $el.trigger( evtName, args );
    };


    $( brewpress.init );


})(window, document, jQuery, window.BrewPress);