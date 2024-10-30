(function($){

    'use strict';

    /** 
     * INIT SELECT2
     */
    $('select').select2({
        minimumResultsForSearch: 11,
        // placeholder: function(){
        //     $(this).data('placeholder');
        // }
    });

    // /** 
    //  * INIT Tooltips
    //  */
    $('[data-toggle="tooltip"]').tooltip()

    /** 
     * INIT confirmations
     */
    $('[data-toggle=confirmation]').confirmation({
        rootSelector: '[data-toggle=confirmation]',
    });

    /** 
     * Duplicate a batch
     */
    $('a#duplicate-item').click(function(){
        if ( confirm("Are you sure you want to duplicate this batch?") ) {
        } else {
            return false;
        }
    });

    /** 
     * Delete a batch
     */
    $('a#delete-item').click(function(){
        if ( confirm("Permanently delete this batch?") ) {
        } else {
            return false;
        }
    });

    /** 
     * Show the spinner
     */
    $( '#batch' ).on( 'submit', function(e){
        var spinner = ' <i class="fas fa-spinner fa-pulse"></i>';
        $(this).append( '<p class="">Updating the batch ' + spinner + '</p>' );
        // clear it as a precaution
        localStorage.removeItem( 'program' );
    });

    /** 
     * Delete a batch
     */
    $('a.more-link').click(function(e){
        e.preventDefault();
        $(this).find( 'i' ).toggleClass( 'open');
        $(this).parents( '.batch-row' ).next().slideToggle();
    });



/** 
 * FILTERING ON DASHBOARD PAGE
 *
 */
//var $ = jQuery;
$.fn.extend({
    filterTable: function(){
        return this.each(function(){
            
            $(this).on('keyup', function(e){

                $('.filterTable_no_results').remove();
                var $this = $(this), 
                    search = $this.val().toLowerCase(), 
                    target = $this.attr('data-filters'), 
                    $target = $(target), 
                    $rows = $target.find('tbody tr');

                if(search == '') {
                    $rows.show(); 
                } else {
                    //console.log($this); 
                    $rows.each(function(){
                        var $this = $(this);
                        $this.find( 'td:not(.no-search)' ).text().toLowerCase().indexOf(search) === -1 ? $this.hide() : $this.show();
                    })
                    if($target.find('tbody tr:visible').size() === 0) {
                        var col_count = $target.find('tr').first().find('td').size();
                        var no_results = $('<tr class="filterTable_no_results"><td colspan="'+col_count+'">No results found</td></tr>')
                        $target.find('tbody').append(no_results);
                    }
                }
            });
        });
    }
});

$('[data-action="filter"]').filterTable();
        
})(jQuery);