var $j = jQuery.noConflict();
$j(function(){
    $j(window).load(function() {
        $j('#ui-datepicker-div').wrap('<span class="ctd-jquery-ui"></span>');
    });
});