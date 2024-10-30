/**
 * @description Implement the graphparticipants layout for the module admin pages
 * @author Cláudio Esperança, Diogo Serra
 * @version 1.0
 */

var $j = jQuery.noConflict();

// jQuery plugin for the graphparticipants
(function($) {
    /* Private methods */
    var privateMethods = {
	/* Call private handler for internal use (no external access) */
	call : function(context, method){
	    if ( privateMethods[method] ) {
		return privateMethods[ method ].apply( context, Array.prototype.slice.call( arguments, 2 ));
	    } else {
		$.error( ctdGraphParticipantsL10n.privateMethodDoesNotExist.replace("{0}", method) );
	    }
	    return null;
	},
        
        /* Plugin initialization method */
	init : function() {
        },
        
        drawGraph : function(rows){
            var containerElement = this;
            if(google){
                var data = google.visualization.arrayToDataTable(rows);
                var options = {
                    backgroundColor: 'transparent',
                    legend:'none',
                    'height': '300'
                };

                var table = new google.visualization.Table($(this).get(0));

                table.draw(data, options);
            }else{
                $(this).removeClass("loading").html(ctdGraphParticipantsL10n.nogoogle);
            }

            $(this).removeClass("loading");
            
            return containerElement;
        },
        
        dataLoaded : function(data){
            var containerElement = this;
            if(!$.isEmptyObject(data)){
                privateMethods.call(containerElement, 'drawGraph', data);
            }else{
                $(this).removeClass("loading").html(ctdGraphParticipantsL10n.withoutdata);
            }
            
            $(this).trigger('dataLoaded.ctdGraphVisitors', data);
            return this;
        },
	
	/* Return the data from the table */
	loadData : function(args){
            var containerElement = this;
            
            $(this).addClass("loading").html(ctdGraphParticipantsL10n.loading);
            
            $.post( 
                ajaxurl, 
                args, 
                function(data) {
                    privateMethods.call(containerElement, 'dataLoaded', data);
                }, "json" 
            );
                
	    return containerElement;
	}
    };
    
    /* Public methods */
    var publicMethods = {
	/* Get number of data columns */
	loadData : function(args){ 
	    return privateMethods.call(this, 'loadData', args);
	},
	drawGraph : function(){ 
	    return privateMethods.call(this, 'drawGraph');
	}
    };
    
    /*  */
    $.fn.ctdGraphVisitors = function(method) {
	/* Method calling logic */
	if ( publicMethods[method] ) {
	    return publicMethods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
	    return privateMethods.init.apply( this, arguments );
	} else {
	    $.error( ctdGraphParticipantsL10n.methodDoesNotExist.replace("{0}", method) );
	}
	return null;
    };
})($j);


// View code
$j(function(){
    var calendarOptions = {
        closeText: ctdGraphParticipantsL10n.closeText,
        currentText: ctdGraphParticipantsL10n.currentText,
        dateFormat: ctdGraphParticipantsL10n.dateFormat,
        dayNames: [
            ctdGraphParticipantsL10n.dayNamesSunday,
            ctdGraphParticipantsL10n.dayNamesMonday,
            ctdGraphParticipantsL10n.dayNamesTuesday,
            ctdGraphParticipantsL10n.dayNamesWednesday,
            ctdGraphParticipantsL10n.dayNamesThursday,
            ctdGraphParticipantsL10n.dayNamesFriday,
            ctdGraphParticipantsL10n.dayNamesSaturday
        ],
        dayNamesMin: [
            ctdGraphParticipantsL10n.dayNamesMinSu,
            ctdGraphParticipantsL10n.dayNamesMinMo,
            ctdGraphParticipantsL10n.dayNamesMinTu,
            ctdGraphParticipantsL10n.dayNamesMinWe,
            ctdGraphParticipantsL10n.dayNamesMinTh,
            ctdGraphParticipantsL10n.dayNamesMinFr,
            ctdGraphParticipantsL10n.dayNamesMinSa
        ],
        dayNamesShort: [
            ctdGraphParticipantsL10n.dayNamesShortSun,
            ctdGraphParticipantsL10n.dayNamesShortMon,
            ctdGraphParticipantsL10n.dayNamesShortTue,
            ctdGraphParticipantsL10n.dayNamesShortWed,
            ctdGraphParticipantsL10n.dayNamesShortThu,
            ctdGraphParticipantsL10n.dayNamesShortFri,
            ctdGraphParticipantsL10n.dayNamesShortSat
        ],
        monthNames: [
            ctdGraphParticipantsL10n.monthNamesJanuary,
            ctdGraphParticipantsL10n.monthNamesFebruary,
            ctdGraphParticipantsL10n.monthNamesMarch,
            ctdGraphParticipantsL10n.monthNamesApril,
            ctdGraphParticipantsL10n.monthNamesMay,
            ctdGraphParticipantsL10n.monthNamesJune,
            ctdGraphParticipantsL10n.monthNamesJuly,
            ctdGraphParticipantsL10n.monthNamesAugust,
            ctdGraphParticipantsL10n.monthNamesSeptember,
            ctdGraphParticipantsL10n.monthNamesOctober,
            ctdGraphParticipantsL10n.monthNamesNovember,
            ctdGraphParticipantsL10n.monthNamesDecember
        ],
        monthNamesShort: [
            ctdGraphParticipantsL10n.monthNamesShortJan,
            ctdGraphParticipantsL10n.monthNamesShortFeb,
            ctdGraphParticipantsL10n.monthNamesShortMar,
            ctdGraphParticipantsL10n.monthNamesShortApr,
            ctdGraphParticipantsL10n.monthNamesShortMay,
            ctdGraphParticipantsL10n.monthNamesShortJun,
            ctdGraphParticipantsL10n.monthNamesShortJul,
            ctdGraphParticipantsL10n.monthNamesShortAug,
            ctdGraphParticipantsL10n.monthNamesShortSep,
            ctdGraphParticipantsL10n.monthNamesShortOct,
            ctdGraphParticipantsL10n.monthNamesShortNov,
            ctdGraphParticipantsL10n.monthNamesShortDec
        ],
        nextText: ctdGraphParticipantsL10n.nextText,
        prevText: ctdGraphParticipantsL10n.prevText,
        weekHeader: ctdGraphParticipantsL10n.weekHeader,
        altFormat: "@",
        autoSize: true,
        changeMonth: true,
        changeYear: true
    },
    startDate = null,
    endDate = null;
    
    // Attach the date picker components and set their dates based on the timestamp values
    if($j("#ctd-hidden-graphparticipants-startdate").val()){
        startDate = $j.datepicker.parseDate(calendarOptions.altFormat, $j("#ctd-hidden-graphparticipants-startdate").val()) || null;
    }
    if($j("#ctd-hidden-graphparticipants-enddate").val()){
        endDate = $j.datepicker.parseDate(calendarOptions.altFormat, $j("#ctd-hidden-graphparticipants-enddate").val()) || null;
    }
    
    $j("#ctd-graphparticipants-startdate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "-1w",
        altField: "#ctd-hidden-graphparticipants-startdate",
        maxDate: endDate,
        onSelect: function( selectedDate ) {
            var instance = $j(this).data( "datepicker" ), 
            date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );

            $j("#ctd-graphparticipants-enddate").datepicker( "option", "minDate", date );
        }
    })).datepicker("setDate", startDate);
    
    $j("#ctd-graphparticipants-enddate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "+0w",
        altField: "#ctd-hidden-graphparticipants-enddate",
        minDate: startDate,
        onSelect: function( selectedDate ) {
            var instance = $j(this).data( "datepicker" ), 
                date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );
            $j("#ctd-graphparticipants-startdate").datepicker( "option", "maxDate", date );
        }
    })).datepicker("setDate", endDate);
});