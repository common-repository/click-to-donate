/**
 * @description Implement the graphbanners layout for the module admin pages
 * @author Cláudio Esperança, Diogo Serra
 * @version 1.0
 */

var $j = jQuery.noConflict();

// jQuery plugin for the graphbanners
(function($) {
    /* Private methods */
    var privateMethods = {
	/* Call private handler for internal use (no external access) */
	call : function(context, method){
	    if ( privateMethods[method] ) {
		return privateMethods[ method ].apply( context, Array.prototype.slice.call( arguments, 2 ));
	    } else {
		$.error( ctdGraphBannersL10n.privateMethodDoesNotExist.replace("{0}", method) );
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
                    animation:{
                        duration: 1000,
                        easing: 'out'
                    },
                    isStacked: true,
                    'height': '300'
                };

                var chart = new google.visualization.ColumnChart($(this).get(0));
                chart.draw(data, options);

                $(this).removeClass("loading");
            }else{
                $(this).removeClass("loading").html(ctdGraphParticipantsL10n.nogoogle);
            }
            
            return containerElement;
        },
        
        dataLoaded : function(data){
            var containerElement = this;
            if(!$.isEmptyObject(data)){
                privateMethods.call(containerElement, 'drawGraph', data);
            }else{
                $(this).removeClass("loading").html(ctdGraphBannersL10n.withoutdata);
            }
            
            $(this).trigger('dataLoaded.ctdGraphBanners', data);
            return this;
        },
	
	/* Return the data from the table */
	loadData : function(args){
            var containerElement = this;
            
            $(this).addClass("loading").html(ctdGraphBannersL10n.loading);
            
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
    $.fn.ctdGraphBanners = function(method) {
	/* Method calling logic */
	if ( publicMethods[method] ) {
	    return publicMethods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
	} else if ( typeof method === 'object' || ! method ) {
	    return privateMethods.init.apply( this, arguments );
	} else {
	    $.error( ctdGraphBannersL10n.methodDoesNotExist.replace("{0}", method) );
	}
	return null;
    };
})($j);


// View code
$j(function(){
    var calendarOptions = {
        closeText: ctdGraphBannersL10n.closeText,
        currentText: ctdGraphBannersL10n.currentText,
        dateFormat: ctdGraphBannersL10n.dateFormat,
        dayNames: [
            ctdGraphBannersL10n.dayNamesSunday,
            ctdGraphBannersL10n.dayNamesMonday,
            ctdGraphBannersL10n.dayNamesTuesday,
            ctdGraphBannersL10n.dayNamesWednesday,
            ctdGraphBannersL10n.dayNamesThursday,
            ctdGraphBannersL10n.dayNamesFriday,
            ctdGraphBannersL10n.dayNamesSaturday
        ],
        dayNamesMin: [
            ctdGraphBannersL10n.dayNamesMinSu,
            ctdGraphBannersL10n.dayNamesMinMo,
            ctdGraphBannersL10n.dayNamesMinTu,
            ctdGraphBannersL10n.dayNamesMinWe,
            ctdGraphBannersL10n.dayNamesMinTh,
            ctdGraphBannersL10n.dayNamesMinFr,
            ctdGraphBannersL10n.dayNamesMinSa
        ],
        dayNamesShort: [
            ctdGraphBannersL10n.dayNamesShortSun,
            ctdGraphBannersL10n.dayNamesShortMon,
            ctdGraphBannersL10n.dayNamesShortTue,
            ctdGraphBannersL10n.dayNamesShortWed,
            ctdGraphBannersL10n.dayNamesShortThu,
            ctdGraphBannersL10n.dayNamesShortFri,
            ctdGraphBannersL10n.dayNamesShortSat
        ],
        monthNames: [
            ctdGraphBannersL10n.monthNamesJanuary,
            ctdGraphBannersL10n.monthNamesFebruary,
            ctdGraphBannersL10n.monthNamesMarch,
            ctdGraphBannersL10n.monthNamesApril,
            ctdGraphBannersL10n.monthNamesMay,
            ctdGraphBannersL10n.monthNamesJune,
            ctdGraphBannersL10n.monthNamesJuly,
            ctdGraphBannersL10n.monthNamesAugust,
            ctdGraphBannersL10n.monthNamesSeptember,
            ctdGraphBannersL10n.monthNamesOctober,
            ctdGraphBannersL10n.monthNamesNovember,
            ctdGraphBannersL10n.monthNamesDecember
        ],
        monthNamesShort: [
            ctdGraphBannersL10n.monthNamesShortJan,
            ctdGraphBannersL10n.monthNamesShortFeb,
            ctdGraphBannersL10n.monthNamesShortMar,
            ctdGraphBannersL10n.monthNamesShortApr,
            ctdGraphBannersL10n.monthNamesShortMay,
            ctdGraphBannersL10n.monthNamesShortJun,
            ctdGraphBannersL10n.monthNamesShortJul,
            ctdGraphBannersL10n.monthNamesShortAug,
            ctdGraphBannersL10n.monthNamesShortSep,
            ctdGraphBannersL10n.monthNamesShortOct,
            ctdGraphBannersL10n.monthNamesShortNov,
            ctdGraphBannersL10n.monthNamesShortDec
        ],
        nextText: ctdGraphBannersL10n.nextText,
        prevText: ctdGraphBannersL10n.prevText,
        weekHeader: ctdGraphBannersL10n.weekHeader,
        altFormat: "@",
        autoSize: true,
        changeMonth: true,
        changeYear: true
    },
    startDate = null,
    endDate = null;
    
    // Attach the date picker components and set their dates based on the timestamp values
    if($j("#ctd-hidden-graphbanners-startdate").val()){
        startDate = $j.datepicker.parseDate(calendarOptions.altFormat, $j("#ctd-hidden-graphbanners-startdate").val()) || null;
    }
    if($j("#ctd-hidden-graphbanners-enddate").val()){
        endDate = $j.datepicker.parseDate(calendarOptions.altFormat, $j("#ctd-hidden-graphbanners-enddate").val()) || null;
    }
    
    $j("#ctd-graphbanners-startdate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "-1w",
        altField: "#ctd-hidden-graphbanners-startdate",
        maxDate: endDate,
        onSelect: function( selectedDate ) {
            var instance = $j(this).data( "datepicker" ), 
            date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );

            $j("#ctd-graphbanners-enddate").datepicker( "option", "minDate", date );
        }
    })).datepicker("setDate", startDate);
    
    $j("#ctd-graphbanners-enddate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "+0w",
        altField: "#ctd-hidden-graphbanners-enddate",
        minDate: startDate,
        onSelect: function( selectedDate ) {
            var instance = $j(this).data( "datepicker" ), 
                date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );
            $j("#ctd-graphbanners-startdate").datepicker( "option", "maxDate", date );
        }
    })).datepicker("setDate", endDate);
});