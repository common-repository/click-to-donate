/**
 * @description Implement the rich layout for the module admin pages
 * @author Cláudio Esperança, Diogo Serra
 * @version 1.0
 */
var $j = jQuery.noConflict();

$j(function(){
    // Localize and set the common options for the calendars
    var calendarOptions = {
        closeText: ctdAdminL10n.closeText,
        currentText: ctdAdminL10n.currentText,
        dateFormat: ctdAdminL10n.dateFormat,
        dayNames: [
            ctdAdminL10n.dayNamesSunday,
            ctdAdminL10n.dayNamesMonday,
            ctdAdminL10n.dayNamesTuesday,
            ctdAdminL10n.dayNamesWednesday,
            ctdAdminL10n.dayNamesThursday,
            ctdAdminL10n.dayNamesFriday,
            ctdAdminL10n.dayNamesSaturday
        ],
        dayNamesMin: [
            ctdAdminL10n.dayNamesMinSu,
            ctdAdminL10n.dayNamesMinMo,
            ctdAdminL10n.dayNamesMinTu,
            ctdAdminL10n.dayNamesMinWe,
            ctdAdminL10n.dayNamesMinTh,
            ctdAdminL10n.dayNamesMinFr,
            ctdAdminL10n.dayNamesMinSa
        ],
        dayNamesShort: [
            ctdAdminL10n.dayNamesShortSun,
            ctdAdminL10n.dayNamesShortMon,
            ctdAdminL10n.dayNamesShortTue,
            ctdAdminL10n.dayNamesShortWed,
            ctdAdminL10n.dayNamesShortThu,
            ctdAdminL10n.dayNamesShortFri,
            ctdAdminL10n.dayNamesShortSat
        ],
        monthNames: [
            ctdAdminL10n.monthNamesJanuary,
            ctdAdminL10n.monthNamesFebruary,
            ctdAdminL10n.monthNamesMarch,
            ctdAdminL10n.monthNamesApril,
            ctdAdminL10n.monthNamesMay,
            ctdAdminL10n.monthNamesJune,
            ctdAdminL10n.monthNamesJuly,
            ctdAdminL10n.monthNamesAugust,
            ctdAdminL10n.monthNamesSeptember,
            ctdAdminL10n.monthNamesOctober,
            ctdAdminL10n.monthNamesNovember,
            ctdAdminL10n.monthNamesDecember
        ],
        monthNamesShort: [
            ctdAdminL10n.monthNamesShortJan,
            ctdAdminL10n.monthNamesShortFeb,
            ctdAdminL10n.monthNamesShortMar,
            ctdAdminL10n.monthNamesShortApr,
            ctdAdminL10n.monthNamesShortMay,
            ctdAdminL10n.monthNamesShortJun,
            ctdAdminL10n.monthNamesShortJul,
            ctdAdminL10n.monthNamesShortAug,
            ctdAdminL10n.monthNamesShortSep,
            ctdAdminL10n.monthNamesShortOct,
            ctdAdminL10n.monthNamesShortNov,
            ctdAdminL10n.monthNamesShortDec
        ],
        nextText: ctdAdminL10n.nextText,
        prevText: ctdAdminL10n.prevText,
        weekHeader: ctdAdminL10n.weekHeader,
        altFormat: "yy-m-d",
        autoSize: true,
        changeMonth: true,
        changeYear: true
    }, 
    timeDefaults = {
        'min': 0,
        'showOn': 'none',
        'width': 24,
        'mouseWheel': true,
        'step': 1,
        'largeStep': 1
    },
    startDate = null,
    endDate = null;
    
    // Attach the spinner to the clicks limit field
    $j('#ctd-maximum-clicks-limit').spinner({
        min: 0, 
        increment: 'fast',
        showOn: 'both',
        mouseWheel: true,
        step: 100,
        largeStep: 1000
    });
    
    $j('#ctd-cool-off-period').spinner({
        min: 0, 
        increment: 'fast',
        showOn: 'both',
        mouseWheel: true,
        step: 1,
        largeStep: 3600
    }).trigger('change');
    
    // Attach the spinner to the time fields
    $j('#ctd-starthours, #ctd-endhours').spinner($j.extend(true, {}, timeDefaults, {
        'max': 23
    }))/*.css({
        'margin-right': 0,
        'text-align': 'right'
    })*/;
    
    $j('#ctd-startminutes, #ctd-endminutes').spinner($j.extend(true, {}, timeDefaults, {
        'max': 59
    }))/*.css({'margin-right': '0'})*/;
    
    // Hide the hidden elements
    $j(".start-hidden").hide();
    
    // Container function to show and style the fieldsets accordingly
    function showContainer(innerContainer, outerContainer, show){
        if(show){
            $j(outerContainer).addClass("ctd-visible");
            $j(innerContainer).show();
        }else{
            $j(innerContainer).hide();
            $j(outerContainer).removeClass("ctd-visible");
        }
    };
    
    // Show the fieldset when the checkbox is checked
    $j("#ctd-enable-cool-off").click(function(){
        var checked = $j(this).is(":checked");
        showContainer("#ctd-cool-off-container", "#ctd-enable-cool-off-container", checked);
        $j('#ctd-restrict-by-cookie').attr('checked', checked);
        $j('#ctd-restrict-by-login').attr('checked', checked);
    });
    
    $j("#ctd-enable-maxclicks").click(function(){
        showContainer("#ctd-maxclicks-container", "#ctd-enable-maxclicks-container", $j(this).is(":checked"));
    });
    
    $j("#ctd-enable-startdate").click(function(){
        showContainer("#ctd-startdate-container", "#ctd-enable-startdate-container", $j(this).is(":checked"));
        
        // Reset the minDate for the other calendar
        if(!$j(this).is(":checked")){
            $j("#ctd-enddate").datepicker("option", "minDate", null);
        }
    });
    $j("#ctd-enable-enddate").click(function(){
        showContainer("#ctd-enddate-container", "#ctd-enable-enddate-container", $j(this).is(":checked"));
        
        // Reset the minDate for the other calendar
        if(!$j(this).is(":checked")){
            $j("#ctd-startdate").datepicker("option", "maxDate", null);
        }
    });
    
    // Attach the date picker components and set their dates based on the timestamp values
    if($j("#ctd-hidden-startdate").val()){
        startDate = $j.datepicker.parseDate(calendarOptions.altFormat, $j("#ctd-hidden-startdate").val()) || null;
    }
    if($j("#ctd-hidden-enddate").val()){
        endDate = $j.datepicker.parseDate(calendarOptions.altFormat, $j("#ctd-hidden-enddate").val()) || null;
    }
    
    $j("#ctd-startdate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "+1w",
        altField: "#ctd-hidden-startdate",
        maxDate: $j("#ctd-enable-enddate").is(":checked")?endDate:null,
        onSelect: function( selectedDate ) {
            if($j("#ctd-enable-enddate").is(":checked")){
                var instance = $j(this).data( "datepicker" ), 
                date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );
            
                $j("#ctd-enddate").datepicker( "option", "minDate", date );
            }else{
                $j("#ctd-enddate").datepicker( "option", "minDate", null );
            }
        }
    })).datepicker("setDate", startDate);
    
    $j("#ctd-enddate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "+2w",
        altField: "#ctd-hidden-enddate",
        minDate: $j("#ctd-enable-startdate").is(":checked")?startDate:null,
        onSelect: function( selectedDate ) {
            if($j("#ctd-enable-startdate").is(":checked")){
                var instance = $j(this).data( "datepicker" ), 
                    date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );
                $j("#ctd-startdate").datepicker( "option", "maxDate", date );
            }else{
                $j("#ctd-startdate").datepicker( "option", "minDate", null );
            }
        }
    })).datepicker("setDate", endDate);
    
    // Set the initial visibility of the fieldsets
    showContainer("#ctd-cool-off-container", "#ctd-enable-cool-off-container", $j("#ctd-enable-cool-off").is(":checked"));
    showContainer("#ctd-maxclicks-container", "#ctd-enable-maxclicks-container", $j("#ctd-enable-maxclicks").is(":checked"));
    showContainer("#ctd-startdate-container", "#ctd-enable-startdate-container", $j("#ctd-enable-startdate").is(":checked"));
    showContainer("#ctd-enddate-container", "#ctd-enable-enddate-container", $j("#ctd-enable-enddate").is(":checked"));
    
    
});