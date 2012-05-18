/**
 * @description Implement the rich layout for the module admin pages
 * @author Cláudio Esperança, Diogo Serra
 * @version 1.0
 */
var $j = jQuery.noConflict();

$j(function(){
    
    // Localize and set the common options for the calendars
    var calendarOptions = {
        closeText: vpmAdmin.closeText,
        currentText: vpmAdmin.currentText,
        dateFormat: vpmAdmin.dateFormat,
        dayNames: [
            vpmAdmin.dayNamesSunday,
            vpmAdmin.dayNamesMonday,
            vpmAdmin.dayNamesTuesday,
            vpmAdmin.dayNamesWednesday,
            vpmAdmin.dayNamesThursday,
            vpmAdmin.dayNamesFriday,
            vpmAdmin.dayNamesSaturday
        ],
        dayNamesMin: [
            vpmAdmin.dayNamesMinSu,
            vpmAdmin.dayNamesMinMo,
            vpmAdmin.dayNamesMinTu,
            vpmAdmin.dayNamesMinWe,
            vpmAdmin.dayNamesMinTh,
            vpmAdmin.dayNamesMinFr,
            vpmAdmin.dayNamesMinSa
        ],
        dayNamesShort: [
            vpmAdmin.dayNamesShortSun,
            vpmAdmin.dayNamesShortMon,
            vpmAdmin.dayNamesShortTue,
            vpmAdmin.dayNamesShortWed,
            vpmAdmin.dayNamesShortThu,
            vpmAdmin.dayNamesShortFri,
            vpmAdmin.dayNamesShortSat
        ],
        monthNames: [
            vpmAdmin.monthNamesJanuary,
            vpmAdmin.monthNamesFebruary,
            vpmAdmin.monthNamesMarch,
            vpmAdmin.monthNamesApril,
            vpmAdmin.monthNamesMay,
            vpmAdmin.monthNamesJune,
            vpmAdmin.monthNamesJuly,
            vpmAdmin.monthNamesAugust,
            vpmAdmin.monthNamesSeptember,
            vpmAdmin.monthNamesOctober,
            vpmAdmin.monthNamesNovember,
            vpmAdmin.monthNamesDecember
        ],
        monthNamesShort: [
            vpmAdmin.monthNamesShortJan,
            vpmAdmin.monthNamesShortFeb,
            vpmAdmin.monthNamesShortMar,
            vpmAdmin.monthNamesShortApr,
            vpmAdmin.monthNamesShortMay,
            vpmAdmin.monthNamesShortJun,
            vpmAdmin.monthNamesShortJul,
            vpmAdmin.monthNamesShortAug,
            vpmAdmin.monthNamesShortSep,
            vpmAdmin.monthNamesShortOct,
            vpmAdmin.monthNamesShortNov,
            vpmAdmin.monthNamesShortDec
        ],
        nextText: vpmAdmin.nextText,
        prevText: vpmAdmin.prevText,
        weekHeader: vpmAdmin.weekHeader,
        altFormat: "yy-m-d",
        autoSize: true,
        changeMonth: true,
        changeYear: true
    };
    
    $j('#vpm-project-admin').css({
        'padding': '6px 10px 8px',
        'margin-top' : '6px'
    });
    
    // Attach the spinner to the time fields
    var timeDefaults = {
        'min': 0,
        'showOn': 'none',
        'width': 24,
        'mouseWheel': true,
        'step': 1,
        'largeStep': 1
    };
    
    $j('#vpm-starthours, #vpm-endhours').spinner($j.extend(true, {}, timeDefaults, {
        'max': 23
    })).css({
        'margin-right': 0,
        'text-align': 'right'
    });
    
    $j('#vpm-startminutes, #vpm-endminutes').spinner($j.extend(true, {}, timeDefaults, {
        'max': 59
    })).css({
        'margin-right': 0,
        'text-align': 'right'
    });
    
    // Hide the hidden elements
    $j(".start-hidden").hide();
    
    // Set the CSS for the fieldset
    $j(".vpm-enable-container").css({
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
    
    
    // Attach the date picker components and set their dates based on the timestamp values
    var startDate = null;
    if($j("#vpm-hidden-startdate").val()){
        startDate = $j.datepicker.parseDate("yy-m-d", $j("#vpm-hidden-startdate").val()) || null;
    }
    var endDate = null;
    if($j("#vpm-hidden-enddate").val()){
        endDate = $j.datepicker.parseDate("yy-m-d", $j("#vpm-hidden-enddate").val()) || null;
    }
    
    $j("#vpm-startdate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "+1w",
        altField: "#vpm-hidden-startdate",
        maxDate: endDate,
        onSelect: function( selectedDate ) {
            var instance = $j(this).data( "datepicker" ), 
                date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );
            $j("#vpm-enddate").datepicker( "option", "minDate", date );
        }
    })).datepicker("setDate", startDate);
    
    $j("#vpm-enddate").datepicker($j.extend(true, {}, calendarOptions, {
        defaultDate: "+2w",
        altField: "#vpm-hidden-enddate",
        minDate: startDate,
        onSelect: function( selectedDate ) {
            var instance = $j(this).data( "datepicker" ), 
                date = $j.datepicker.parseDate(instance.settings.dateFormat || $j.datepicker._defaults.dateFormat, selectedDate, instance.settings );
            $j("#vpm-startdate").datepicker( "option", "maxDate", date );
        }
    })).datepicker("setDate", endDate);
    
    
    
});