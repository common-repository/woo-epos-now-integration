var measured = [1,2,5,6,7,8,9,11,12,18];
var weighted = [3,4,28,29];
var length = ["1", "2"];
var length_inches = ["18"];
var volume = ["5", "6", "7"];
var area = ["8", "9"];
var weight_uk = ["3", "4"];
var weight_us = ["28", "29"];
jQuery(document).ready(function($){

    $( '.wc-metaboxes-wrapper' ).on(
        'change',
        '#field_to_edit',
        function() {
            var do_variation_action = $( this ).val(),
                data = {},
                changes = 0,
                valid=false,
                error_msg = "",
                value;

            switch (do_variation_action) {
                case 'variable_sln_ignore_stock_enable':
                case 'variable_sln_ignore_product_enable':
                        data.value = true;
                    changes++;
                    break;
                case 'variable_sln_ignore_stock_disable':
                case 'variable_sln_ignore_product_disable':
                     data.value = false;
                     changes++;
                    break;
                case 'variable_sln_unit_multiplier':
                case 'variable_sln_epn_brand_id':
                case 'variable_sln_epn_supplier_id':
                    value = window.prompt("Enter value (only numeric value)" , "");
                    value = parseFloat(value);
                    value = value.toFixed(0); //Math.max(value, 1);
                    if (value != null) {
                        data.value = value;
                        changes++;
                    }else{
                        return false;
                    }
                    break;
                case 'variable_sln_epn_cost_price':
                case 'variable_sln_epn_eat_out_price':
                case 'variable_sln_epn_rrp_price':
                    value = window.prompt("Enter value (decimal value accepted)" , "");
                    value = parseFloat(value);
                    if (value != null) {
                        data.value = value;
                        changes++;
                    }else{
                        return false;
                    }
                    break;
                case 'variable_sln_epn_order_code':
                case 'variable_sln_epn_article_code':
                case 'variable_sln_product_category_master':
                    value = window.prompt(
                        "Enter value (alphanumeric value accepted)",
                    );
                    if (value != null) {
                        data.value = value;
                        changes++;
                    }else{
                        return false;
                    }
                    break;
            }

            if(changes > 0) {

                $.ajax({
                    url: woocommerce_admin_meta_boxes_variations.ajax_url,
                    data: {
                        action: 'sln_woocommerce_bulk_edit_variations',
                        security:
                        woocommerce_admin_meta_boxes_variations.bulk_edit_variations_nonce,
                        product_id: woocommerce_admin_meta_boxes_variations.post_id,
                        product_type: $('#product-type').val(),
                        bulk_action: do_variation_action,
                        data: data,
                    },
                    type: 'POST',
                    success: function () {
                        //wc_meta_boxes_product_variations_pagenav.go_to_page(
                        //    1
                        //);
                    },
                });
            }
        }
    );
    $(".sln_int_field").on('keydown keyup mousedown mouseup',function(e){
        var keycode = e.charCode || e.keyCode;
        console.log(keycode);
        if(keycode == 110 || keycode == 190 || keycode == 189 || keycode == 69 || keycode == 187)
        {
            return false;
        }
    });

    slnSimpleSelectBoxLoaded();
    $(document).on('woocommerce_variations_loaded', function(event) {
        console.log('Variation Loaded');
        slnVariationSelectBoxLoaded();
        //slnVariationEpnMeasuredLoaded();
    });
    $(document).on('woocommerce-product-type-change', function(event) {
		//slnVariationSelectBoxLoaded();
		jQuery('input#publish').prop('disabled', false);
		jQuery( "div#variable_product_options").find('button.save-variation-changes').parent('div.toolbar').show();
    });
    $(document).on('woocommerce_variations_input_changed', function(event) {
		//console.log('woocommerce_variations_input_changed Event loaded');
        refreshSaveButton();
    });
    $(document).on('woocommerce_variations_saved', function(event) {
		//console.log('woocommerce_variations_saved Event loaded');
		// refreshSaveButton();
    });
    $(document).on('woocommerce_variations_loaded', function(event) {
		//console.log('woocommerce_variations_loaded Event loaded');
		// refreshSaveButton();
    });
	$(document).on( "woocommerce_variations_added", function ( event, variation ) {
		// Fired when the user selects all the required dropdowns / attributes
		// and a final variation is selected / shown
		//console.log('woocommerce_variations_added Event loaded');
		slnVariationSelectBoxLoaded();
	});
});
function slnSimpleSelectBoxLoaded(){
    console.log('slnSimpleSelectBoxLoaded Loaded');
    jQuery('.sln_cost_price_read_only').prop('readonly',true);
    var valMeasured = jQuery( ".sln_measured_weighted" );
    var selVal = jQuery( ".sln_product_type_select option:selected" );
    jQuery(selVal).parent('select').on('change', function() {
        alert('Please note that changing the product type means that you will need to set/check the measurement units in the settings below. Please also ensure you update the stock level in EposNow accordingly. These changes will take effect once you save this product.');
        showHideOnSelect(this.value, valMeasured);
        var wcselect = jQuery(this).parents('div.woocommerce_options_panel').find('select.sln_wc_measure_unit');
        var epnselect = jQuery(this).parents('div.woocommerce_options_panel').find('select.sln_epn_measure_unit');
        checkIsValidMeasured(wcselect, epnselect);
    });
    showHideOnSelect(selVal.val(), valMeasured);
    slynkSimpleMeasurementUnitValidation();
    inputNumberValidation(jQuery('div#slynk_epos_now'));
    return;
}

function slnVariationSelectBoxLoaded(){
    console.log('slnSelectBoxLoaded Loaded');
    jQuery('.sln_cost_price_read_only').prop('readonly',true);
    jQuery('.sln_full_field').css({'width': '100%', 'max-width': '100%'});
    jQuery('.woocommerce_variable_attributes .form-field span.woocommerce-help-tip').css({'float': 'right'});
    var selVal = jQuery( "div.woocommerce_variation .sln_product_type_select option:selected" );
    selVal.each(function (){
        var eachSelect = jQuery(this);
        var valMeasured = eachSelect.parents('div.woocommerce_variation').find( '.sln_measured_weighted' );
        jQuery(this).parent('select').on('change', function() {
            alert('Please note that changing the product type means that you will need to set/check the measurement units in the settings below. Please also ensure you update the stock level in EposNow accordingly. These changes will take effect once you save this variation.');
            showHideOnSelect(this.value, valMeasured);
            refreshSaveButton();
        });
        showHideOnSelect(eachSelect.val(), valMeasured);
    });
    var measuredSelVal = jQuery( 'div.woocommerce_variation');
    measuredSelVal.each(function () {
        var eachMeasuredSelect = jQuery(this);
        slnVariationSelectMeasured(eachMeasuredSelect);
        inputNumberValidation(eachMeasuredSelect);
    });
    return;
}

/*function slnVariationEpnMeasuredLoaded(){
    console.log('slnVariationEpnMeasuredLoaded');
    var epnSelVal = jQuery( "div.woocommerce_variation select.sln_epn_measure_unit option:selected" );
    epnSelVal.each(function (){
        var eachSelect = jQuery(this);
        var epn_unit_select = jQuery(this).val();
        var wc_unit_select = jQuery(this).parents('div.woocommerce_variation').find('select.sln_wc_measure_unit').val();
        console.log('epn_unit_select '+epn_unit_select);
        console.log('wc_unit_select '+wc_unit_select);
    });
    return;
}*/

function inputNumberValidation(selectedElement){
    selectedElement.each(function () {
        var measurement_unit_volume = jQuery(this).find('input.sln_measurement_unit_volume');
        var unit_multiplier = jQuery(this).find('input.sln_unit_multiplier');
        measurement_unit_volume.on('input', function () {
            var value = jQuery(this).val();
            jQuery(this).val(Math.max(value, 1));
        });

       unit_multiplier.on('keydown blur', function (e) {
            //var value = jQuery(this).val();
            var value= parseFloat(jQuery(this).val());
            var value_with_decimal = parseFloat(value.toFixed(4));
            if(value_with_decimal != value) {
                jQuery(this).val(value_with_decimal);
            }
        });
    });
}

function showHideOnSelect(currentVal, valMeasured){
    console.log('showHideOnSelect Loaded - - ' + currentVal);
    if (( currentVal == 'standard'))
    {
        jQuery( valMeasured ).parents('p.form-field').hide();
    }
    else
    {
        //for measured and weighted correct unit option to enable
        valMeasured.each(function (){
            var selectedElement = jQuery(this);
            var values = selectedElement.val();
            var tagtype = selectedElement.prop("tagName");
            var validValues = false;
           if("SELECT" == tagtype){
               if( currentVal == 'measured')
               {
                   jQuery.each(weighted, function (i, val) {
                       selectedElement.children('option[value="'+val+'"]').attr('disabled', 'disabled').hide();
                   });
                   jQuery.each(measured, function (i, val) {
                       if(values == val){ validValues = true; }
                       selectedElement.children('option[value="'+val+'"]').removeAttr('disabled').show();
                   });
               }
               else{
                   jQuery.each(weighted, function (i, val) {
                       if(values == val){ validValues = true; }
                       selectedElement.children('option[value="'+val+'"]').removeAttr('disabled').show();
                   });
                   jQuery.each(measured, function (i, val) {
                       selectedElement.children('option[value="'+val+'"]').attr('disabled', 'disabled').hide();
                   });
               }
               if(validValues == false) {
                   var v = jQuery(this).children('option:enabled').val();
                   jQuery(this).children('option:enabled').eq(0).prop('selected', true);
                   jQuery(this).val(v);
               }
           }

        });
        jQuery( valMeasured ).parents('p.form-field').show();
    }
    return;
}


function slnVariationSelectMeasured(valMeasured){
    valMeasured.find('select.sln_epn_measure_unit, select.sln_wc_measure_unit').on('change', function () {
        var valid = false;
        if (jQuery(this).hasClass('sln_epn_measure_unit')) {
            var epnselect = jQuery(this);
            var wcselect = jQuery(this).parents('div.woocommerce_variation').find('select.sln_wc_measure_unit');
        } else {
            var wcselect = jQuery(this);
            var epnselect = jQuery(this).parents('div.woocommerce_variation').find('select.sln_epn_measure_unit');
        }
        var product_type = jQuery(this).parents('div.woocommerce_variation').find('select.sln_product_type_select').val();
        if(product_type == 'measured') {
            valid = checkIsValidMeasured(wcselect, epnselect);
        }
    });
}

function slynkSimpleMeasurementUnitValidation(){
    jQuery('div#slynk_epos_now').find('select.sln_epn_measure_unit, select.sln_wc_measure_unit').on('change', function () {
        var valid = false;
        if (jQuery(this).hasClass('sln_epn_measure_unit')) {
            var epnselect = jQuery(this);
            var wcselect = jQuery(this).parents('div.woocommerce_options_panel').find('select.sln_wc_measure_unit');
        } else {
            var wcselect = jQuery(this);
            var epnselect = jQuery(this).parents('div.woocommerce_options_panel').find('select.sln_epn_measure_unit');
        }
        var product_type = jQuery(this).parents('div.woocommerce_options_panel').find('select.sln_product_type_select').val();
        if(product_type == 'measured' || product_type == 'weighted') {
            valid = checkIsValidMeasured(wcselect, epnselect);
        }
    });
}

function checkIsValidMeasured(wcselect, epnselect){
    var valid = false;
    var epn_unit_select = epnselect.val();
    var wc_unit_select = wcselect.val();
    if (jQuery.inArray(epn_unit_select, length) >= 0 && jQuery.inArray(wc_unit_select, length) >= 0) {
        console.log("selected matches length");
        valid = true;
    }
    if (jQuery.inArray(epn_unit_select, length_inches) >= 0 && jQuery.inArray(wc_unit_select, length_inches) >= 0) {
        console.log("selected matches length_inches");
        valid = true;
    }
    if (jQuery.inArray(epn_unit_select, volume) >= 0 && jQuery.inArray(wc_unit_select, volume) >= 0) {
        console.log("selected matches volume");
        valid = true;
    }
    if (jQuery.inArray(epn_unit_select, area) >= 0 && jQuery.inArray(wc_unit_select, area) >= 0) {
        console.log("selected matches area");
        valid = true;
    }
    if (jQuery.inArray(epn_unit_select, weight_uk) >= 0 && jQuery.inArray(wc_unit_select, weight_uk) >= 0) {
        console.log("selected matches weight_uk");
        valid = true;
    }
    if (jQuery.inArray(epn_unit_select, weight_us) >= 0 && jQuery.inArray(wc_unit_select, weight_us) >= 0) {
        console.log("selected matches weight_us");
        valid = true;
    }
    if (epn_unit_select === wc_unit_select) {
        valid = true;
    }
    console.log(epn_unit_select +"==="+ wc_unit_select);
    if (valid === true) {
        wcselect.css('border', '1px solid #8c8f94');
        epnselect.css('border', '1px solid #8c8f94');
        jQuery('input#publish').prop('disabled', false);
    } else {
        jQuery('input#publish').prop('disabled', true);
        wcselect.css('border', '1px solid red');
        epnselect.css('border', '1px solid red');
        alert("Please select compatible measurement units. For example cm/m, yd/yd, ml/cl/l, g/kg, lb/per 1000lb")
    }
    return valid;
}

function refreshSaveButton(){
    var selVal = jQuery( "div.woocommerce_variation");
    var var_product_options = jQuery( "div#variable_product_options");
    selVal.each(function () {
        var type = jQuery(this).find('select.sln_product_type_select').val();
        var epn_unit_select = jQuery(this).find('select.sln_epn_measure_unit').val();
        var wc_unit_select = jQuery(this).find('select.sln_wc_measure_unit').val();
        var valid = true;
        if(type == 'measured' || type == 'weighted'){
            var valid = false;
            if (jQuery.inArray(epn_unit_select, length) >= 0 && jQuery.inArray(wc_unit_select, length) >= 0) {
                valid = true;
            }
            if (jQuery.inArray(epn_unit_select, length_inches) >= 0 && jQuery.inArray(wc_unit_select, length_inches) >= 0) {
                valid = true;
            }
            if (jQuery.inArray(epn_unit_select, volume) >= 0 && jQuery.inArray(wc_unit_select, volume) >= 0) {
                valid = true;
            }
            if (jQuery.inArray(epn_unit_select, area) >= 0 && jQuery.inArray(wc_unit_select, area) >= 0) {
                valid = true;
            }
            if (jQuery.inArray(epn_unit_select, weight_uk) >= 0 && jQuery.inArray(wc_unit_select, weight_uk) >= 0) {
                console.log("selected matches weight_uk");
                valid = true;
            }
            if (jQuery.inArray(epn_unit_select, weight_us) >= 0 && jQuery.inArray(wc_unit_select, weight_us) >= 0) {
                console.log("selected matches weight_us");
                valid = true;
            }
            if(epn_unit_select === wc_unit_select){
                valid = true;
            }
            if(valid == false){
                jQuery('input#publish').prop('disabled', true);
                var_product_options.find('button.save-variation-changes').attr( 'disabled', 'disabled' );
                return false;
            }
        }
        jQuery('input#publish').prop('disabled', false);
        var_product_options.find('button.save-variation-changes').removeAttr( 'disabled' );
    });
}