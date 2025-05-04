jQuery(document).ready(function($) {
    // Initialize variables
    const originalPrice = typeof wc_br_vars !== 'undefined' ? parseFloat(wc_br_vars.original_price) : 0;
    const rentABikeEnabled = typeof wc_br_vars !== 'undefined' ? wc_br_vars.rent_a_bike_enabled : false;
    const bikeRequired = typeof wc_br_vars !== 'undefined' ? wc_br_vars.bike_required : false;
    const isUserLoggedIn = typeof wc_br_vars !== 'undefined' ? wc_br_vars.is_user_logged_in : false;
    const ownBikeEnabled = typeof wc_br_vars !== 'undefined' ? wc_br_vars.own_bike_enabled : false;
	
    // 1. Store original required states
    var originalRequiredFields = {};
    $('[required]').each(function() {
        originalRequiredFields[this.name] = true;
        $(this).data('was-required', true);
    });

    // 2. Initialize form validation
    if (typeof $.fn.validate !== 'undefined') {
        $('form.cart').validate({
            errorClass: 'error',
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            ignore: ":hidden"
        });
    }

    // 3. Enhanced toggle function
function toggleBikeAndResources(hasOwnBike) {
    if (hasOwnBike) {
        // Hide bike options
        $('.bike-options').hide();
        
        // Remove required attribute (not just prop)
        $('#selected_bike').removeAttr('required');
        
        // Hide and disable only paired resources
        if (typeof wc_br_conditional_pairs !== 'undefined') {
            // Get all paired resources from conditional pairs
            const pairedResources = Object.values(wc_br_conditional_pairs);
            
            $('.resource-option').each(function() {
                const $resource = $(this);
                const resourceName = $resource.find('input[name^="selected_resources"]').val();
                
                // Check if this resource is in any of the paired resources
                const isPairedResource = pairedResources.includes(resourceName);
                
                if (isPairedResource) {
                    $resource.hide();
                    $resource.find('input[type="checkbox"]')
                        .prop('checked', false)
                        .removeAttr('required'); // Remove required attribute completely
                }
            });
        }
    } else {
        // Show bike options if bike is required
        if (wc_br_bike_required) {
            $('.bike-options').show();
            $('#selected_bike').attr('required', 'required'); // Set required attribute properly
        }
        
        // Show all resources and restore required status only for paired resources
        $('.resource-option').show();
        $('.resource-option input[type="checkbox"]').each(function() {
            const $checkbox = $(this);
            const resourceName = $checkbox.val();
            const wasRequired = $checkbox.data('was-required');
            
            // Only restore required status if this is a paired resource and was originally required
            if (typeof wc_br_conditional_pairs !== 'undefined') {
                const isPairedResource = Object.values(wc_br_conditional_pairs).includes(resourceName);
                if (isPairedResource && wasRequired) {
                    $checkbox.attr('required', 'required'); // Set required attribute properly
                } else {
                    $checkbox.removeAttr('required');
                }
            }
            
            // Always enforce required if set in admin (regardless of paired status)
            if ($checkbox.data('was-required')) {
                $checkbox.attr('required', 'required');
            }
        });
    }
    updatePriceDisplay();
}

// Initialize with proper event binding
function initOwnBikeToggle() {
    if (typeof wc_br_own_bike_enabled === 'undefined' || !wc_br_own_bike_enabled) return;
    
    // Set initial state
    const initialOwnBike = $('input[name="has_own_bike"]:checked').val() === 'yes';
    toggleBikeAndResources(initialOwnBike);
    
    // Handle changes - FIXED EVENT BINDING
    $(document).on('change', 'input[name="has_own_bike"]', function() {
        toggleBikeAndResources($(this).val() === 'yes');
    });
}
initOwnBikeToggle();

    // Initialize steps
    function initSteps() {
        $('.booking-step').hide();
        $('#step-1').show().addClass('active');
        $('.step-header').removeClass('active');
        $('.step-header[data-step="1"]').addClass('active');
    }
    initSteps();

// Initialize datepicker with proper UI formatting
function initDatePickers() {
    // Rental date pickers (jQuery UI)
    $('.rent-a-bike-date').datepicker({
        dateFormat: 'yy-mm-dd',
        minDate: 1, // Blocks previous dates
        beforeShow: function(input, inst) {
            inst.dpDiv.css({
                'margin-top': '-40px',
                'margin-left': '10px',
                'z-index': '999999'
            });
        },
        onSelect: function(selectedDate) {
            if (this.name === 'rent_a_bike_pickup_date') {
                $('input[name="rent_a_bike_dropoff_date"]')
                    .datepicker('option', 'minDate', selectedDate)
                    .val('')
                    .focus();
            }
            updatePriceDisplay();
        }
    });

    // DOB picker (unchanged)
    $('.dob-field').datepicker({
        dateFormat: 'yy-mm-dd',
        changeYear: true,
        yearRange: '-100:-18',
        maxDate: '-18Y'
    });
}
initDatePickers();

    // Store original required status of resources
    $('.resource-option input[type="checkbox"][required]').each(function() {
        $(this).data('was-required', true);
    });

    // Own bike radio button functionality
    if (ownBikeEnabled) {
        // Set default state (Yes)
        $('input[name="has_own_bike"][value="yes"]').prop('checked', true);
        toggleBikeAndResources(true);
        
        // Handle radio button change
        $('input[name="has_own_bike"]').on('change', function() {
            const hasOwnBike = $(this).val() === 'yes';
            toggleBikeAndResources(hasOwnBike);
        });
    }



    // Step navigation handlers
$('#next-step-1').off('click').on('click', function(e) {
    e.preventDefault();
    
    let isValid = true;
    let errorMessages = [];
    
    // 1. Validate bike field if required and visible
    if (!$('.bike-options').is(':hidden') && wc_br_bike_required && !$('#selected_bike').val()) {
        isValid = false;
        errorMessages.push('Please select a bike option');
    }
    
    // 2. Validate ALL required resources (both paired and admin-marked required)
    $('.resource-option:visible input[type="checkbox"][required]').each(function() {
        if (!$(this).is(':checked')) {
            const resourceName = $(this).closest('.resource-option').find('label').text().trim();
            errorMessages.push(`Please select "${resourceName}"`);
            isValid = false;
        }
    });
    
    // 3. Validate paired resources when "Own Bike" is No
// 3. Validate paired resources (smart handling based on "Own Bike" toggle)
if (typeof wc_br_conditional_pairs !== 'undefined') {
    // Check if "Own Bike" is enabled AND set to "Yes" (skip validation)
    const skipPairedValidation = $('input[name="has_own_bike"]').length > 0 && 
                               $('input[name="has_own_bike"]:checked').val() === 'yes';

    if (!skipPairedValidation) {
        const pairedResources = Object.values(wc_br_conditional_pairs);
        const selectedResources = [];
        
        // Get visible checked resources
        $('.resource-option:visible input[type="checkbox"]:checked').each(function() {
            selectedResources.push($(this).val());
        });

        // Check paired validation
        const pairs = {};
        let hasPairError = false;
        
        Object.entries(wc_br_conditional_pairs).forEach(([resourceA, resourceB]) => {
            const pairKey = [resourceA, resourceB].sort().join('-');
            if (!pairs[pairKey]) pairs[pairKey] = [resourceA, resourceB];
            
            if (!selectedResources.includes(resourceA) && !selectedResources.includes(resourceB)) {
                hasPairError = true;
            }
        });

        if (hasPairError) {
            isValid = false;
            const pairMessages = Object.values(pairs).map(pair => {
                const [resA, resB] = pair;
                return `"${resA}" or "${resB}"`;
            });
            errorMessages.push(`You must select at least one option from these required pairs: ${pairMessages.join(', ')}`);
        }
    }
}
    
    // 4. Validate other required fields (non-resource)
    $(':input:visible[required]').not('#selected_bike, .resource-option input').each(function() {
        if (!$(this).val()) {
            isValid = false;
            const fieldName = $(this).attr('name') || $(this).attr('id');
            errorMessages.push(`Please fill in the ${fieldName} field`);
        }
    });
    
    // Show all error messages in alert
    if (errorMessages.length > 0) {
        alert('Please fix the following:\n\n' + errorMessages.join('\n'));
    }
    
    if (isValid) {
        showStep(2);
    }
});

    $('#prev-step-2').on('click', function(e) {
        e.preventDefault();
        showStep(1);
    });

    $('#next-step-2').on('click', function(e) {
        e.preventDefault();
        if (validateStep2()) {
            updateSummaryContent();
            showStep(3);
        }
    });

    $('#prev-step-3').on('click', function(e) {
        e.preventDefault();
        showStep(2);
    });

    // Proceed to Payment handler

// Update the click handler for the Proceed to Payment button
$(document).on('click', '#proceed-to-payment', function(e) {
    e.preventDefault();
    const $button = $(this);
    const originalText = $button.text();
    $button.prop('disabled', true).text('Processing...');
    
    // Call with fromStep3=true
    addCompleteCartItem($button, originalText, true);
});

// Modified addCompleteCartItem function to handle both flows
function addCompleteCartItem($button, originalText, fromStep3 = false) {
    const $form = $('form.cart');
    const product_id = $form.find('input[name="add-to-cart"]').val() || 
                     (typeof wc_br_vars !== 'undefined' ? wc_br_vars.product_id : 0);

    if (!product_id) {
        alert('Product ID not found. Please refresh the page.');
        $button.prop('disabled', false).text(originalText);
        return;
    }

    // Prepare form data - CRITICAL CHANGE: Use FormData for proper array handling
    const formData = new FormData();
    formData.append('action', 'wc_br_add_to_cart');
    formData.append('security', wc_br_vars.ajax_nonce);
    formData.append('add-to-cart', product_id);
    formData.append('quantity', 1);
    formData.append('from_step_3', fromStep3);

    // Add ALL form fields including resource days
    $('.booking-form input, .booking-form textarea, .resource-options input').each(function() {
        if (this.name && !['add-to-cart', 'quantity'].includes(this.name)) {
            // Handle resource days differently
            if (this.name.startsWith('resource_days[')) {
                const resourceName = this.name.match(/\[(.*?)\]/)[1];
                formData.append(`resource_days[${resourceName}]`, $(this).val());
            } else {
                formData.append(this.name, $(this).val());
            }
        }
    });

    // Add bike selection if not own bike
    const hasOwnBike = wc_br_own_bike_enabled && $('input[name="has_own_bike"]:checked').val() === 'yes';
    if (!hasOwnBike && $('#selected_bike').val()) {
        formData.append('selected_bike', $('#selected_bike').val());
    }

  // Replace the selected resources collection part with this:
// ===== [FINAL FIX] Resource Handling ===== //
let selectedResources = [];

// Only process CHECKED resources
$('input[name="selected_resources[]"]:checked').each(function() {
    const $resource = $(this);
    const resourceName = $resource.val();
    
    // Get days selection (default to 1 if not specified)
    const daysInput = $(`input[name="resource_days[${resourceName}]"]`);
    const days = daysInput.length ? parseInt(daysInput.val()) || 1 : 1;

    // Push ONLY selected resources with their full data
    selectedResources.push({
        name: resourceName,
        days: days,
        price: parseFloat($resource.data('price')) || 0,
        fixed_price: parseFloat($resource.data('fixed-price')) || 0,
        enable_calculation: $resource.data('enable-calculation') ? 1 : 0,
        operator: $resource.data('operator') || '+',
        percentage: parseFloat($resource.data('percentage')) || 0
    });
});

// Clear previous resource data and only add selected ones
formData.delete('selected_resources[]'); // Remove any existing

// Add each selected resource with proper array format
selectedResources.forEach((resource, index) => {
    for (const [key, value] of Object.entries(resource)) {
        formData.append(`selected_resources[${index}][${key}]`, value);
    }
});

    // Add other fields
    if (wc_br_own_bike_enabled) {
        formData.append('has_own_bike', $('input[name="has_own_bike"]:checked').val());
    }
    if (wc_br_rent_a_bike_enabled) {
        formData.append('rent_a_bike_pickup_date', $('input[name="rent_a_bike_pickup_date"]').val());
        formData.append('rent_a_bike_dropoff_date', $('input[name="rent_a_bike_dropoff_date"]').val());
    }
    if ($('#selected_deposit').val()) {
        formData.append('selected_deposit', $('#selected_deposit').val());
    }

    // Submit with proper headers
    $.ajax({
        url: wc_br_vars.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $(document.body).trigger('wc_fragment_refresh');
                setTimeout(function() {
                    showStep(4);
                    refreshCheckoutForm();
                }, 500);
            } else {
                alert(response.data?.message || 'Error adding to cart');
            }
            $button.prop('disabled', false).text(originalText);
        },
        error: function(xhr) {
            console.error('AJAX Error:', xhr.responseText);
            alert('Failed to process booking. Please try again.');
            $button.prop('disabled', false).text(originalText);
        }
    });
}

 
    
// Refresh checkout form via AJAX
    function refreshCheckoutForm() {
        if (typeof wc_checkout_params === 'undefined') {
            console.warn('WooCommerce checkout params not loaded');
            return;
        }
        
        $('#woocommerce-checkout-wrapper').html(
            '<div class="loading-spinner"></div> ' + 
            (wc_br_vars.i18n_loading_checkout || 'Loading checkout form...')
        );
        
        $.ajax({
            url: wc_br_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'wc_br_get_checkout_form',
                security: wc_br_vars.ajax_nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#woocommerce-checkout-wrapper').html(response.data);
                    $(document.body).trigger('init_checkout');
                    $(document.body).trigger('update_checkout');
                    
                    setTimeout(function() {
                        $.ajax({
                            url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'),
                            type: 'POST',
                            success: function(data) {
                                if (data && data.fragments) {
                                    $.each(data.fragments, function(key, value) {
                                        $(key).replaceWith(value);
                                    });
                                }
                                $(document.body).trigger('updated_checkout');
                            }
                        });
                    }, 300);
                } else {
                    $('#woocommerce-checkout-wrapper').html(
                        '<div class="checkout-error">' + 
                        (response.data.message || (wc_br_vars.i18n_checkout_error || 'Checkout loading failed')) + 
                        '</div>'
                    );
                }
            },
            error: function() {
                $('#woocommerce-checkout-wrapper').html(
                    '<div class="checkout-error">' + 
                    (wc_br_vars.i18n_checkout_error || 'Checkout loading failed') + 
                    '</div>'
                );
            }
        });
    }

    // Show step function with animation
    function showStep(stepNumber) {
        $('.booking-step').removeClass('active').hide();
        $('#step-' + stepNumber).addClass('active').fadeIn(300);
        $('.step-header').removeClass('active');
        $('.step-header[data-step="' + stepNumber + '"]').addClass('active');
        
        if (stepNumber === 4) {
            refreshCheckoutForm();
        }
        
        $('html, body').animate({
            scrollTop: $('#step-' + stepNumber).offset().top - 20
        }, 300);
    }

    // New Paired Resource Validation
    function validatePairedResources() {
        if (typeof wc_br_conditional_pairs === 'undefined') return true;
        
        let isValid = true;
        const selectedResources = $('input[name="selected_resources[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        $('.resource-option').removeClass('paired-required');
        
        for (const [resourceA, resourceB] of Object.entries(wc_br_conditional_pairs)) {
            const isASelected = selectedResources.includes(resourceA);
            const isBSelected = selectedResources.includes(resourceB);
            
            if (!isASelected && !isBSelected) {
                $(`input[name="selected_resources[]"][value="${resourceA}"]`).closest('.resource-option').addClass('paired-required');
                $(`input[name="selected_resources[]"][value="${resourceB}"]`).closest('.resource-option').addClass('paired-required');
                isValid = false;
            }
        }
        
        return isValid;
    }

    function validateStep1() {
        let isValid = true;
        let errorMessages = [];
        
        const hasOwnBike = ownBikeEnabled && $('input[name="has_own_bike"]:checked').val() === 'yes';
        
        // Rent-a-bike validation
        if (rentABikeEnabled) {
            if (!$('input[name="rent_a_bike_pickup_date"]').val()) {
                errorMessages.push('Please select a pick-up date');
                $('input[name="rent_a_bike_pickup_date"]').addClass('error');
                isValid = false;
            }
            if (!$('input[name="rent_a_bike_dropoff_date"]').val()) {
                errorMessages.push('Please select a drop-off date');
                $('input[name="rent_a_bike_dropoff_date"]').addClass('error');
                isValid = false;
            }
        }
        
        // Bike validation (only if own bike is not selected)
        if (!hasOwnBike && bikeRequired && $('#selected_bike').length && $('#selected_bike').children('option').length > 1) {
            if (!$('#selected_bike').val()) {
                errorMessages.push('Please select a bike option');
                $('#selected_bike').addClass('error');
                isValid = false;
            }
        }
        
        // Resource validation (only visible resources)
        $('.resource-option:visible input[type="checkbox"][required]').each(function() {
            if (!$(this).is(':checked')) {
                const resourceName = $(this).closest('.resource-option').find('label').text().trim();
                errorMessages.push(`Please select "${resourceName}"`);
                $(this).addClass('error');
                isValid = false;
            }
        });
        
        // Paired resources validation (only if own bike is not selected)
        if (!hasOwnBike && !validatePairedResources()) {
            const pairs = {};
            Object.entries(wc_br_conditional_pairs).forEach(([resourceA, resourceB]) => {
                const pairKey = [resourceA, resourceB].sort().join('-');
                if (!pairs[pairKey]) {
                    pairs[pairKey] = [resourceA, resourceB];
                }
            });
            
            const pairMessages = Object.values(pairs).map(pair => {
                const [resA, resB] = pair;
                return `"${resA}" or "${resB}"`;
            });
            
            errorMessages.push(`You must select at least one option from these required pairs: ${pairMessages.join(', ')}`);
            isValid = false;
        }
        
        if (errorMessages.length > 0) {
            alert('Please fix the following:\n\n' + errorMessages.join('\n'));
        }
        
        return isValid;
    }
			
			
			// Step 2 Next Button Handler
$(document).on('click', '#next-step-2', function(e) {
    e.preventDefault();
    
    let isValid = true;
    let errorMessages = [];
    let firstErrorField = null;

    // Validate all required fields
    $('.booking-form [required]').each(function() {
        if (!$(this).val().trim()) {
            const fieldLabel = $(this).closest('.form-group').find('label').text().replace('*', '').trim();
            errorMessages.push(`Please fill in the ${fieldLabel} field`);
            $(this).addClass('error');
            if (!firstErrorField) firstErrorField = $(this);
            isValid = false;
        } else {
            $(this).removeClass('error');
            
            // Additional validation for specific field types
            if ($(this).attr('type') === 'email' && !isValidEmail($(this).val())) {
                errorMessages.push('Please enter a valid email address');
                $(this).addClass('error');
                if (!firstErrorField) firstErrorField = $(this);
                isValid = false;
            }
            
            if ($(this).attr('type') === 'tel' && !isValidPhone($(this).val())) {
                errorMessages.push('Please enter a valid phone number');
                $(this).addClass('error');
                if (!firstErrorField) firstErrorField = $(this);
                isValid = false;
            }
        }
    });

    if (errorMessages.length > 0) {
        alert('Please fix the following:\n\n' + errorMessages.join('\n'));
        if (firstErrorField) {
            $('html, body').animate({
                scrollTop: firstErrorField.offset().top - 100
            }, 300);
        }
    }

    if (isValid) {
        updateSummaryContent();
        showStep(3);
    }
});

// Helper functions for validation
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[0-9\-\+\(\)\s]{6,}$/.test(phone);
}

    // Validate step 2 (customer details)
    function validateStep2() {
        let isValid = true;
        let errorMessages = [];
        let firstErrorField = null;

        // Required fields validation
        const requiredFields = [
            { name: 'customer_full_name', label: 'Full Name' },
            { name: 'customer_phone', label: 'Contact Number' },
            { name: 'customer_email', label: 'Email Address' }
        ];

        requiredFields.forEach(field => {
            const value = $(`input[name="${field.name}"]`).val().trim();
            if (!value) {
                errorMessages.push(`Please enter your ${field.label}`);
                $(`input[name="${field.name}"]`).addClass('error');
                if (!firstErrorField) firstErrorField = $(`input[name="${field.name}"]`);
                isValid = false;
            } else {
                $(`input[name="${field.name}"]`).removeClass('error');
            }
        });

        // Email format validation
        const email = $('input[name="customer_email"]').val().trim();
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errorMessages.push('Please enter a valid email address');
            $('input[name="customer_email"]').addClass('error');
            if (!firstErrorField) firstErrorField = $('input[name="customer_email"]');
            isValid = false;
        }

        // Phone number validation (basic)
        const phone = $('input[name="customer_phone"]').val().trim();
        if (phone && !/^[0-9\-\+\(\)\s]{6,}$/.test(phone)) {
            errorMessages.push('Please enter a valid phone number');
            $('input[name="customer_phone"]').addClass('error');
            if (!firstErrorField) firstErrorField = $('input[name="customer_phone"]');
            isValid = false;
        }

        if (errorMessages.length > 0) {
            alert('Please fix the following:\n\n' + errorMessages.join('\n'));
            if (firstErrorField) {
                $('html, body').animate({
                    scrollTop: firstErrorField.offset().top - 100
                }, 300);
            }
        }

        return isValid;
    }

    // Update summary content
function updateSummaryContent() {
    const summaryContent = $('#summary-content');
    let summaryHTML = '<div class="summary-section">';
    
    // Product info
    summaryHTML += `
        <div class="summary-item">
            <strong>Product:</strong>
            <div>${$('.product-title').text()}</div>
        </div>`;
    
    if (originalPrice > 0) {
        summaryHTML += `
            <div class="summary-item">
                <strong>Base Price:</strong>
                <div>${wc_br_vars.original_price}</div>
            </div>`;
    }

    // Own bike status
    if (ownBikeEnabled) {
        summaryHTML += `
            <div class="summary-item">
                <strong>${$('.own-bike-question h4').text()}:</strong>
                <div>${$('input[name="has_own_bike"]:checked').val() === 'yes' ? 'Yes' : 'No'}</div>
            </div>`;
    }

    // Bike selection (only if own bike is not selected)
    const hasOwnBike = ownBikeEnabled && $('input[name="has_own_bike"]:checked').val() === 'yes';
    if (!hasOwnBike) {
        const selectedBike = $('#selected_bike').val();
        if (selectedBike && selectedBike !== "") {
            const bikeOption = $('#selected_bike option:selected');
            const bikePrice = parseFloat(bikeOption.data('price')) || 0;
            summaryHTML += `
                <div class="summary-item">
                    <strong>${$('.bike-options h4').text()}:</strong>
                    <div>${bikeOption.text()} - ${bikePrice.toFixed(2)}</div>
                </div>`;
        }
    }
    
    // Rent a Bike summary
    if (rentABikeEnabled) {
        const pickupDate = $('input[name="rent_a_bike_pickup_date"]').val();
        const dropoffDate = $('input[name="rent_a_bike_dropoff_date"]').val();
        
        if (pickupDate && dropoffDate) {
            const pickup = new Date(pickupDate);
            const dropoff = new Date(dropoffDate);
            const daysDiff = Math.ceil((dropoff - pickup) / (1000 * 60 * 60 * 24));
            const pricePerDay = $('.rent-a-bike-options').data('price-per-day');
            const rentTotal = daysDiff * pricePerDay;
            
            summaryHTML += `
                <div class="summary-item">
                    <strong>${$('.rent-a-bike-options h4').text()}:</strong>
                    <div>${pickupDate} to ${dropoffDate} (${daysDiff} days) - ${rentTotal.toFixed(2)}</div>
                </div>`;
        }
    }
    
    // Resources summary
    $('input[name="selected_resources[]"]:checked').each(function() {
        const resource = $(this);
        const resourceName = resource.val();
        const resourceLabel = resource.closest('.resource-option').find('label').text().trim();
        const resourceDays = parseInt($('input[name="resource_days[' + resourceName + ']"]').val()) || 1;
        const fixedPrice = parseFloat(resource.data('fixed-price')) || 0;
        const enableCalculation = resource.data('enable-calculation') === 1;
        const percentage = parseFloat(resource.data('percentage')) || 0;
        const operator = resource.data('operator') || '+';
        let resourceTotal = fixedPrice;
        let priceDescription = '';

        if (enableCalculation && percentage > 0) {
            let calculatedValue = 0;
            const basePrice = originalPrice;
            
            switch (operator) {
                case '+': calculatedValue = basePrice + (basePrice * percentage / 100); break;
                case '-': calculatedValue = basePrice - (basePrice * percentage / 100); break;
                case '*': calculatedValue = basePrice * (percentage / 100); break;
                case '/': calculatedValue = basePrice / (percentage / 100); break;
            }
            
            resourceTotal += calculatedValue * resourceDays;
            priceDescription = `${percentage}% ${operator} base price`;
        } else {
            const perDayPrice = parseFloat(resource.data('price')) || 0;
            if (perDayPrice > 0) {
                resourceTotal += perDayPrice * resourceDays;
                priceDescription = `${perDayPrice.toFixed(2)} per day`;
            }
        }

        summaryHTML += `
            <div class="summary-item">
                <strong>Resource:</strong>
                <div>${resourceLabel} (${resourceDays} ${resourceDays > 1 ? 'days' : 'day'})</div>
                <div class="resource-calculation">${priceDescription}</div>
                <div class="resource-total">Total: ${resourceTotal.toFixed(2)}</div>
            </div>`;
    });
    
    // Deposit summary
    const selectedDeposit = $('#selected_deposit').val();
    if (selectedDeposit && selectedDeposit !== "") {
        const depositOption = $('#selected_deposit option:selected');
        summaryHTML += `
            <div class="summary-item">
                <strong>${$('.deposit-options h4').text()}:</strong>
                <div>${depositOption.text()}</div>
            </div>`;
    }
    
    // Customer details summary - now dynamic
    summaryHTML += `
        <div class="summary-item">
            <strong>Customer Details:</strong>`;
    
    $('.booking-form .form-group').each(function() {
        const $field = $(this);
        const fieldName = $field.find('input, textarea').attr('name');
        const fieldLabel = $field.find('label').text().replace('*', '').trim();
        const fieldValue = $field.find('input, textarea').val();
        
        if (fieldValue) {
            summaryHTML += `<div>${fieldLabel}: ${fieldValue}</div>`;
        }
    });
    
    // Total price
    const totalPrice = $('#dynamic-total-price').text();
    summaryHTML += `
        <div class="summary-total">
            <strong>${$('.total-price').text().split(':')[0]}:</strong> $${totalPrice}
        </div>`;
    
    summaryHTML += '</div>';
    summaryContent.html(summaryHTML);
}

function updatePriceDisplay() {
    // Initialize base values
    let totalPrice = rentABikeEnabled ? 0 : originalPrice;
    let depositAmount = 0;
    let isDepositSelected = false;
    const hasOwnBike = ownBikeEnabled && $('input[name="has_own_bike"]:checked').val() === 'yes';

    // Calculate rental days and base price if applicable
    let rentalBasePrice = 0;
    let rentalDays = 1;
    if (rentABikeEnabled) {
        const pickupDate = $('input[name="rent_a_bike_pickup_date"]').val();
        const dropoffDate = $('input[name="rent_a_bike_dropoff_date"]').val();
        if (pickupDate && dropoffDate) {
            const pickup = new Date(pickupDate);
            const dropoff = new Date(dropoffDate);
            rentalDays = Math.ceil((dropoff - pickup) / (1000 * 60 * 60 * 24));
            const pricePerDay = parseFloat($('.rent-a-bike-options').data('price-per-day')) || 0;
            rentalBasePrice = rentalDays * pricePerDay;
            totalPrice = rentalBasePrice;
        }
    }

    // Deposit calculation
    const selectedDeposit = $('#selected_deposit').val();
    if (selectedDeposit && selectedDeposit !== "") {
        isDepositSelected = true;
        const depositOption = $('#selected_deposit option:selected').text();
        
        const fixedPriceMatch = depositOption.match(/\$([\d\.]+)/);
        if (fixedPriceMatch && fixedPriceMatch[1]) {
            depositAmount += parseFloat(fixedPriceMatch[1]);
        }
        
        const percentMatch = depositOption.match(/(\d+)%/);
        if (percentMatch && percentMatch[1]) {
            const percentage = parseFloat(percentMatch[1]);
            let baseAmount = totalPrice; // Use current total as base
            
            depositAmount += (baseAmount * percentage) / 100;
        }
    }

    // Bike selection (only if own bike is not selected)
    if (!hasOwnBike && !isDepositSelected) {
        const selectedBike = $('#selected_bike').val();
        if (selectedBike && selectedBike !== "") {
            const bikeOption = $('#selected_bike option:selected');
            totalPrice += parseFloat(bikeOption.data('price')) || 0;
        }
    }

// Resource calculation - FINAL FIX FOR RENTAL DAY MULTIPLIER
if (!isDepositSelected) {
    $('input[name="selected_resources[]"]:checked').each(function() {
        if ($(this).is(':visible')) {
            const $resource = $(this);
            const resourceName = $resource.val();
            
            // Get days - handles both rental and original price
            let days = 1;
            const daysInput = $(`input[name="resource_days[${resourceName}]"]`);
            if (daysInput.length) {
                days = parseInt(daysInput.val()) || 1;
            }
            
            // Add fixed price if exists (works same for both)
            const fixedPrice = parseFloat($resource.data('fixed-price')) || 0;
            if (fixedPrice > 0) {
                totalPrice += fixedPrice;
            }
            
            // Handle percentage or per-day calculation
            if ($resource.data('enable-calculation') === 1) {
                // Percentage calculation
                const percentage = parseFloat($resource.data('percentage')) || 0;
                const basePrice = rentABikeEnabled ? rentalBasePrice : originalPrice;
                const percentageValue = (basePrice * percentage) / 100;
                // Apply day multiplier for BOTH rental and original
                totalPrice += percentageValue * days;
            } else {
                // Per-day calculation
                const perDayPrice = parseFloat($resource.data('price')) || 0;
                if (perDayPrice > 0) {
                    // Apply day multiplier for BOTH rental and original
                    totalPrice += perDayPrice * days;
                }
            }
        }
    });
}

    // Apply deposit if selected
    if (isDepositSelected) {
        totalPrice = depositAmount;
    }

    // Update display
    $('#dynamic-total-price').text(totalPrice.toFixed(2));
    
    // Handle visibility of bike options and paired resources for original price
    if (ownBikeEnabled && !rentABikeEnabled) {
        const showOptions = $('input[name="has_own_bike"]:checked').val() === 'no';
        $('.bike-options').toggle(showOptions);
        
        // Handle paired resources
        if (typeof wc_br_conditional_pairs !== 'undefined') {
            Object.keys(wc_br_conditional_pairs).forEach(pair => {
                $(`input[value="${pair}"]`).closest('.resource-option').toggle(showOptions);
            });
        }
    }
}

    // Event listeners for price updates
    $('input[name="rent_a_bike_pickup_date"], input[name="rent_a_bike_dropoff_date"], #selected_bike, #selected_deposit, input[name="selected_resources[]"], .resource-days, input[name="has_own_bike"]')
        .on('change input', updatePriceDisplay);

    // Event listener for paired resources
    $(document).on('change', 'input[name="selected_resources[]"]', function() {
        updatePriceDisplay();
        if (!(ownBikeEnabled && $('input[name="has_own_bike"]:checked').val() === 'yes')) {
            validatePairedResources();
        }
    });

    // Reinitialize datepickers if loaded via AJAX
    $(document).ajaxComplete(function() {
        initDatePickers();
    });

    // Initial price update
    updatePriceDisplay();
});
