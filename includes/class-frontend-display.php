<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Frontend_Display {
    private $conditional_pairs = array();

    public function __construct() {
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_bike_and_resource_options'));
    }

    private function calculate_display_price($original_price, $resource) {
        if (empty($resource['enable_calculation'])) {
            return 0;
        }
        $percentage = floatval($resource['percentage'] ?? 0);
        return ($original_price * $percentage) / 100;
    }

    public function display_bike_and_resource_options() {
        global $product;
        $product_id = $product->get_id();
        $original_price = $product->get_price();
        $rent_a_bike_enabled = get_post_meta($product_id, '_rent_a_bike_enabled', true);
        $initial_total = $rent_a_bike_enabled ? 0 : $original_price;

        // Get all custom headings and options
        $bike_heading = get_post_meta($product_id, '_bike_heading', true) ?: __('Bike Options', 'wc-bike-resources');
        $bike_required = get_post_meta($product_id, '_bike_required', true);
        $resource_heading = get_post_meta($product_id, '_resource_heading', true) ?: __('Resource Options', 'wc-bike-resources');
        $deposit_heading = get_post_meta($product_id, '_deposit_heading', true) ?: __('Deposit Options', 'wc-bike-resources');
        $booking_form_heading = get_post_meta($product_id, '_booking_form_heading', true) ?: __('Booking Form', 'wc-bike-resources');
        
        // Own bike feature options
        $own_bike_enabled = get_post_meta($product_id, '_own_bike_enabled', true);
        $own_bike_label = get_post_meta($product_id, '_own_bike_label', true) ?: __('Do you have your own motorcycle?', 'wc-bike-resources');
        
        $rent_a_bike_heading = get_post_meta($product_id, '_rent_a_bike_heading', true);
        $rent_a_bike_price_per_day = get_post_meta($product_id, '_rent_a_bike_price_per_day', true);
        $booking_fields = get_post_meta($product_id, '_booking_form_fields', true);
        $booking_fields = is_array($booking_fields) ? $booking_fields : array();
        $bikes = get_post_meta($product_id, '_bike_options', true);
        $bikes = is_array($bikes) ? $bikes : array();
        $resources = get_post_meta($product_id, '_resource_options', true);
        $resources = is_array($resources) ? $resources : array();
        $deposit_options = get_post_meta($product_id, '_deposit_options', true);
        $deposit_options = is_array($deposit_options) ? $deposit_options : array();
        $deposit_label = get_post_meta($product_id, '_deposit_label', true) ?: __('Deposit', 'wc-bike-resources');

        // Build conditional pairs array
        foreach ($resources as $resource) {
            if (!empty($resource['has_conditional']) && !empty($resource['paired_resource'])) {
                $this->conditional_pairs[$resource['name']] = $resource['paired_resource'];
            }
        }

        // Display product title and price above step headers
        echo '<div class="product-booking-header">';
        echo '<h2 class="product-title">' . esc_html($product->get_name()) . '</h2>';
        if ($original_price > 0) {
            echo '<div class="product-price">' . wc_price($original_price) . '</div>';
        }
        echo '</div>';

        // Step headers
        echo '<div class="step-headers">';
        echo '<div class="step-header active" data-step="1">' . __('Step 1: Options', 'wc-bike-resources') . '</div>';
        if (!empty($booking_fields)) {
            echo '<div class="step-header" data-step="2">' . __('Step 2: Details', 'wc-bike-resources') . '</div>';
            echo '<div class="step-header" data-step="3">' . __('Step 3: Summary', 'wc-bike-resources') . '</div>';
            echo '<div class="step-header" data-step="4">' . __('Step 4: Payment', 'wc-bike-resources') . '</div>';
        }
        echo '</div>';

        // Step 1: Booking Options
        echo '<div id="step-1" class="booking-step active">';

        // Rent a Bike Section
        if ($rent_a_bike_enabled) {
            echo '<div class="rent-a-bike-options" data-price-per-day="' . esc_attr($rent_a_bike_price_per_day) . '">';
            echo '<h4>' . esc_html($rent_a_bike_heading ?: __('Rent a Bike', 'wc-bike-resources')) . '</h4>';
            echo '<div class="price-per-day">€' . esc_html($rent_a_bike_price_per_day) . '/day</div>';
            echo '<div class="date-inputs">';
          echo '<label>' . __('Pick-up Date:', 'wc-bike-resources') . ' <input type="text" name="rent_a_bike_pickup_date" class="rent-a-bike-date" placeholder="yyyy-mm-dd" required readonly /></label>';
echo '<label>' . __('Drop-off Date:', 'wc-bike-resources') . ' <input type="text" name="rent_a_bike_dropoff_date" class="rent-a-bike-date" placeholder="yyyy-mm-dd" required readonly /></label>';
            echo '</div>';
            echo '</div>';
        }

        // Own Bike Question
       if ($own_bike_enabled) {
    echo '<div class="own-bike-question">';
    echo '<h4>' . esc_html($own_bike_label) . '</h4>';
    echo '<div class="own-bike-options">';
    echo '<label><input type="radio" name="has_own_bike" value="yes" checked /> ' . __('Yes', 'wc-bike-resources') . '</label>';
    echo '<label><input type="radio" name="has_own_bike" value="no" /> ' . __('No', 'wc-bike-resources') . '</label>';
    echo '</div>';
    echo '</div>';
}

        // Bike Options
        if (!empty($bikes)) {
            echo '<div class="bike-options' . ($own_bike_enabled ? ' hide-if-own-bike' : '') . '"><h4>' . esc_html($bike_heading) . '</h4>';
            echo '<select name="selected_bike" id="selected_bike"' . ($bike_required ? ' required' : '') . '>';
            echo '<option value="">' . __('Select Bike', 'wc-bike-resources') . '</option>';
            foreach ($bikes as $bike) {
                if (!empty($bike['name']) && isset($bike['price'])) {
                    echo '<option value="' . esc_attr($bike['name']) . '" data-price="' . esc_attr($bike['price']) . '">';
                    echo esc_html($bike['name']) . ' - €' . esc_html($bike['price']);
                    echo '</option>';
                }
            }
            echo '</select>';
            echo '</div>';
        }

        // Resource Options
        if (!empty($resources)) {
            echo '<div class="resource-options"><h4>' . esc_html($resource_heading) . '</h4>';
            foreach ($resources as $resource) {
                if (!empty($resource['name']) && (isset($resource['price']) || isset($resource['fixed_price']))) {
                    $is_paired_with_bike = isset($this->conditional_pairs[$resource['name']]);
                    $hidden_class = $own_bike_enabled && $is_paired_with_bike ? ' hide-if-own-bike' : '';
                    
                    echo '<div class="resource-option' . $hidden_class . '">';
                    echo '<label>';
                    echo '<input type="checkbox" name="selected_resources[]" value="' . esc_attr($resource['name']) . '" ';
                    echo 'data-price="' . esc_attr($resource['price'] ?? 0) . '" ';
                    echo 'data-fixed-price="' . esc_attr($resource['fixed_price'] ?? 0) . '" ';
                    echo 'data-enable-calculation="' . (!empty($resource['enable_calculation']) ? 1 : 0) . '" ';
                    echo 'data-percentage="' . esc_attr($resource['percentage'] ?? 0) . '" ';
                    echo (!empty($resource['required']) && !($own_bike_enabled && $is_paired_with_bike) ? 'required' : '') . '> ';
                    echo esc_html($resource['name']);
                    echo '</label>';

                    if (!empty($resource['fixed_price'])) {
                        echo '<span class="fixed-price">€' . esc_html($resource['fixed_price']) . '</span>';
                    }

                    if (!empty($resource['enable_calculation'])) {
                        $calculated_price = $this->calculate_display_price($original_price, $resource);
                        echo '<span class="calculation-price">€' . number_format($calculated_price, 2) . '</span>';
                        echo '<span class="calculation-info">(' . esc_html($resource['percentage'] ?? 0) . '%)</span>';
                    } elseif (!empty($resource['price'])) {
                        echo '<span class="per-day-price">€' . esc_html($resource['price']) . '/day</span>';
                    }

                    if (!empty($resource['enable_days']) || (!empty($resource['enable_calculation']) && !empty($resource['enable_days']))) {
                        echo '<input type="number" name="resource_days[' . esc_attr($resource['name']) . ']" value="1" min="1" ';
                        echo 'class="resource-days" data-resource-name="' . esc_attr($resource['name']) . '" placeholder="' . __('Days', 'wc-bike-resources') . '" />';
                    }

                    echo '</div>';
                }
            }
            echo '</div>';
        }

        // Deposit Options
        if (!empty($deposit_options)) {
            echo '<div class="deposit-options"><h4>' . esc_html($deposit_heading) . '</h4>';
            echo '<select name="selected_deposit" id="selected_deposit">';
            echo '<option value="">' . sprintf(__('No %s', 'wc-bike-resources'), esc_html($deposit_label)) . '</option>';
            foreach ($deposit_options as $option) {
                if (!empty($option['name'])) {
                    $option_text = esc_html($option['name']);
                    if (!empty($option['fixed_price'])) {
                        $option_text .= ' - €' . esc_html($option['fixed_price']);
                    }
                    if (!empty($option['enable_percent']) && !empty($option['percent'])) {
                        $option_text .= ' - ' . esc_html($option['percent']) . '%';
                    }
                    echo '<option value="' . esc_attr($option['name']) . '">' . $option_text . '</option>';
                }
            }
            echo '</select>';
            echo '</div>';
        }

        // Step 1 buttons
        if (!empty($booking_fields)) {
            echo '<div class="step-buttons step-1-buttons">';
            echo '<button type="button" id="next-step-1" class="button alt">' . __('Next Step', 'wc-bike-resources') . '</button>';
            echo '</div>';
        } else {
            echo '<button type="submit" name="add-to-cart" value="' . esc_attr($product_id) . '" class="single_add_to_cart_button button alt">';
            echo esc_html($product->single_add_to_cart_text());
            echo '</button>';
        }
        echo '</div>'; // Close step-1

        if (!empty($booking_fields)) {
          // Step 2: Dynamic Customer Details Form
echo '<div id="step-2" class="booking-step">';
echo '<div class="booking-form"><h4>' . esc_html($booking_form_heading) . '</h4>';

$booking_fields = get_post_meta($product_id, '_booking_form_fields', true);
$booking_fields = is_array($booking_fields) ? $booking_fields : array();

foreach ($booking_fields as $field) {
    $required_attr = !empty($field['required']) ? ' required' : '';
    $placeholder_attr = !empty($field['placeholder']) ? ' placeholder="' . esc_attr($field['placeholder']) . '"' : '';
    
    echo '<div class="form-group">';
    echo '<label>' . esc_html($field['label']);
    if (!empty($field['required'])) {
        echo ' <span class="required">*</span>';
    }
    echo '</label>';
    
    if ($field['type'] === 'textarea') {
        echo '<textarea name="' . esc_attr($field['name']) . '"' . $required_attr . $placeholder_attr . '></textarea>';
    } else {
        $extra_attrs = '';
        if ($field['type'] === 'date') {
            $extra_attrs = ' class="dob-field" max="' . date('Y-m-d', strtotime('-10 years')) . '"';
        }
        
        echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($field['name']) . '"' . 
             $required_attr . $placeholder_attr . $extra_attrs . ' />';
    }
    
    echo '</div>';
}

echo '</div>';
echo '<div class="step-buttons step-2-buttons">';
echo '<button type="button" id="prev-step-2" class="button">' . __('Back', 'wc-bike-resources') . '</button>';
echo '<button type="button" id="next-step-2" class="button alt">' . __('Next Step', 'wc-bike-resources') . '</button>';
echo '</div>';
echo '</div>'; // Close step-2

            // Step 3: Summary
            echo '<div id="step-3" class="booking-step">';
            echo '<div class="booking-summary"><h4>' . __('Booking Summary', 'wc-bike-resources') . '</h4>';
            echo '<div id="summary-content"></div>';
            echo '</div>';
            
            echo '<div class="step-buttons step-3-buttons">';
            echo '<button type="button" id="prev-step-3" class="button">' . __('Back', 'wc-bike-resources') . '</button>';
            echo '<button type="button" id="proceed-to-payment" class="button alt">' . __('Proceed to Payment', 'wc-bike-resources') . '</button>';
            echo '</div>';
            echo '</div>'; // Close step-3

            // Step 4: Checkout
            echo '<div id="step-4" class="booking-step">';
            echo '<div class="booking-checkout"><h4>' . __('Complete Your Booking', 'wc-bike-resources') . '</h4>';
            echo '<div id="woocommerce-checkout-wrapper">';
            echo '<div class="loading-spinner"></div> ' . __('Loading checkout form...', 'wc-bike-resources');
            echo '</div></div>';
            echo '</div>'; // Close step-4
        }

        echo '<div class="total-price-container">';
        echo '<div class="price-breakdown" style="display:none;"></div>';
        echo '<div class="total-price">' . __('Total:', 'wc-bike-resources') . ' €<span id="dynamic-total-price">' . number_format($initial_total, 2) . '</span></div>';
        echo '</div>';
        
        echo '<script type="text/javascript">';
        echo 'var wc_br_original_price = ' . $original_price . ';';
        echo 'var wc_br_rent_a_bike_enabled = ' . ($rent_a_bike_enabled ? 'true' : 'false') . ';';
        echo 'var wc_br_bike_required = ' . ($bike_required ? 'true' : 'false') . ';';
        echo 'var wc_br_conditional_pairs = ' . json_encode($this->conditional_pairs) . ';';
        echo 'var wc_br_own_bike_enabled = ' . ($own_bike_enabled ? 'true' : 'false') . ';';
        echo '</script>';
    }
}