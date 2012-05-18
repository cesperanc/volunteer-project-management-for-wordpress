/**
 * @description Implement the rich layout for the module admin pages
 * @author Cláudio Esperança, Diogo Serra
 * @version 1.0
 */
var $j = jQuery.noConflict();

$j(function(){
    
    // Localize and set the common options for the calendars
    var calendarOptions = {
        closeText: ctdAdmin.closeText,
        currentText: ctdAdmin.currentText,
        dateFormat: ctdAdmin.dateFormat,
        dayNames: [
            ctdAdmin.dayNamesSunday,
            ctdAdmin.dayNamesMonday,
            ctdAdmin.dayNamesTuesday,
            ctdAdmin.dayNamesWednesday,
            ctdAdmin.dayNamesThursday,
            ctdAdmin.dayNamesFriday,
            ctdAdmin.dayNamesSaturday
        ],
        dayNamesMin: [
            ctdAdmin.dayNamesMinSu,
            ctdAdmin.dayNamesMinMo,
            ctdAdmin.dayNamesMinTu,
            ctdAdmin.dayNamesMinWe,
            ctdAdmin.dayNamesMinTh,
            ctdAdmin.dayNamesMinFr,
            ctdAdmin.dayNamesMinSa
        ],
        dayNamesShort: [
            ctdAdmin.dayNamesShortSun,
            ctdAdmin.dayNamesShortMon,
            ctdAdmin.dayNamesShortTue,
            ctdAdmin.dayNamesShortWed,
            ctdAdmin.dayNamesShortThu,
            ctdAdmin.dayNamesShortFri,
            ctdAdmin.dayNamesShortSat
        ],
        monthNames: [
            ctdAdmin.monthNamesJanuary,
            ctdAdmin.monthNamesFebruary,
            ctdAdmin.monthNamesMarch,
            ctdAdmin.monthNamesApril,
            ctdAdmin.monthNamesMay,
            ctdAdmin.monthNamesJune,
            ctdAdmin.monthNamesJuly,
            ctdAdmin.monthNamesAugust,
            ctdAdmin.monthNamesSeptember,
            ctdAdmin.monthNamesOctober,
            ctdAdmin.monthNamesNovember,
            ctdAdmin.monthNamesDecember
        ],
        monthNamesShort: [
            ctdAdmin.monthNamesShortJan,
            ctdAdmin.monthNamesShortFeb,
            ctdAdmin.monthNamesShortMar,
            ctdAdmin.monthNamesShortApr,
            ctdAdmin.monthNamesShortMay,
            ctdAdmin.monthNamesShortJun,
            ctdAdmin.monthNamesShortJul,
            ctdAdmin.monthNamesShortAug,
            ctdAdmin.monthNamesShortSep,
            ctdAdmin.monthNamesShortOct,
            ctdAdmin.monthNamesShortNov,
            ctdAdmin.monthNamesShortDec
        ],
        nextText: ctdAdmin.nextText,
        prevText: ctdAdmin.prevText,
        weekHeader: ctdAdmin.weekHeader,
        altFormat: "yy-m-d",
        autoSize: true,
        changeMonth: true,
        changeYear: true
    };
    
    $j('#ctd-campaign-admin').css({
        'padding': '6px 10px 8px',
        'margin-top' : '6px'
    });
    
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
    })/*.change(function() {
        var time = $j(this).val();
        if(!isNaN(time)){
            var seconds = time % 60;
            time /= 60;
            var minutes = time % 60;
            time /= 60;
            var hours = time % 24;
            time /= 24;
            var days = Math.floor(time);
            
            var timeString = "";
            if(days>0){
                timeString += days+" days ";
            }
            if(hours>0){
                timeString += hours+" hours ";
            }
            if(minutes>0){
                timeString += minutes+" minutes ";
            }
            if(seconds>0){
                timeString += seconds+" seconds";
            }
            $j("#ctd-readable-cool-off-period").html(timeString);
        }
    })*/.trigger('change');
    
    // Attach the spinner to the time fields
    var timeDefaults = {
        'min': 0,
        'showOn': 'none',
        'width': 24,
        'mouseWheel': true,
        'step': 1,
        'largeStep': 1
    };
    
    $j('#ctd-starthours, #ctd-endhours').spinner($j.extend(true, {}, timeDefaults, {
        'max': 23
    })).css({
        'margin-right': 0,
        'text-align': 'right'
    });
    
    $j('#ctd-startminutes, #ctd-endminutes').spinner($j.extend(true, {}, timeDefaults, {
        'max': 59
    })).css({
        'margin-right': 0,
        'text-align': 'right'
    });
    
    // Hide the hidden elements
    $j(".start-hidden").hide();
    
    // Set the CSS for the fieldset
    $j(".ctd-enable-container").css({
        'margin': '0 2px',
        'padding': '5px',
        'border': '0px none',
        'border-radius': '5px'
    });
    
    // Container function to show and style the fieldsets accordingly
    var showContainer = function(innerContainer, outerContainer, show){
        if(show){
            $j(innerContainer).show();
            $j(outerContainer).css({'margin': '0 2px 10px', 'border': '1px solid #ECECEC'});
        }else{
            $j(innerContainer).hide();
            $j(outerContainer).css({'margin': '0 2px', 'border': '0px none'});
        }
    };
    
    // Show the fieldset when the checkbox is checked
    $j("#ctd-enable-cool-off").click(function(){
        showContainer("#ctd-cool-off-container", "#ctd-enable-cool-off-container", $j(this).is(":checked"));
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
    var startDate = null;
    if($j("#ctd-hidden-startdate").val()){
        startDate = $j.datepicker.parseDate("yy-m-d", $j("#ctd-hidden-startdate").val()) || null;
    }
    var endDate = null;
    if($j("#ctd-hidden-enddate").val()){
        endDate = $j.datepicker.parseDate("yy-m-d", $j("#ctd-hidden-enddate").val()) || null;
    }
    
    $j("#ctd-startdate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "+1w",
        altField: "#ctd-hidden-startdate",
        maxDate: endDate,
        onSelect: function( selectedDate ) {
            var instance = $j(this).data( "datepicker" ), 
                date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );
            $j("#ctd-enddate").datepicker( "option", "minDate", date );
        }
    })).datepicker("setDate", startDate);
    
    $j("#ctd-enddate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "+2w",
        altField: "#ctd-hidden-enddate",
        minDate: startDate,
        onSelect: function( selectedDate ) {
            var instance = $j(this).data( "datepicker" ), 
                date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );
            $j("#ctd-startdate").datepicker( "option", "maxDate", date );
        }
    })).datepicker("setDate", endDate);
    
    // Set the initial visibility of the fieldsets
    showContainer("#ctd-cool-off-container", "#ctd-enable-cool-off-container", $j("#ctd-enable-cool-off").is(":checked"));
    showContainer("#ctd-maxclicks-container", "#ctd-enable-maxclicks-container", $j("#ctd-enable-maxclicks").is(":checked"));
    showContainer("#ctd-startdate-container", "#ctd-enable-startdate-container", $j("#ctd-enable-startdate").is(":checked"));
    showContainer("#ctd-enddate-container", "#ctd-enable-enddate-container", $j("#ctd-enable-enddate").is(":checked"));
    
    
});