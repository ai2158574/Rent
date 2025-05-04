<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Cart_Handler {
    public function __construct() {
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_custom_data_to_cart_item'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_custom_data_in_cart'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_custom_data_to_order_item'), 10, 4);
        add_filter('woocommerce_cart_item_price', array($this, 'display_correct_item_price'), 10, 3);
    }

    public function add_custom_data_to_cart_item($cart_item_data, $product_id, $variation_id) {
       
		 if (empty($product_id)) {
        if (isset($_POST['add-to-cart']) && !empty($_POST['add-to-cart'])) {
            $product_id = absint($_POST['add-to-cart']);
        } else {
            // Log error for debugging
            error_log('Missing product ID in cart item data');
            return $cart_item_data;
        }
    }
		
		// Handle all booking form fields
    $booking_fields = get_post_meta($product_id, '_booking_form_fields', true);
    $booking_fields = is_array($booking_fields) ? $booking_fields : array();
    
    foreach ($booking_fields as $field) {
        $field_name = $field['name'];
        if (isset($_POST[$field_name])) {
            $cart_item_data[$field_name] = sanitize_text_field($_POST[$field_name]);
        }
    }
		
    // Bike selection
    if (isset($_POST['selected_bike'])) {
        $cart_item_data['selected_bike'] = sanitize_text_field($_POST['selected_bike']);
    }

    // Resources handling
    if (isset($_POST['selected_resources']) && is_array($_POST['selected_resources'])) {
        $cart_item_data['selected_resources'] = array();
        
        foreach ($_POST['selected_resources'] as $resource) {
            $resource_data = array(
                'name' => is_array($resource) ? sanitize_text_field($resource['name']) : sanitize_text_field($resource),
                'days' => 1 // Default
            );
            
            // Set days if provided
            if (is_array($resource) && isset($resource['days'])) {
                $resource_data['days'] = max(1, intval($resource['days']));
            } elseif (isset($_POST['resource_days'][$resource_data['name']])) {
                $resource_data['days'] = max(1, intval($_POST['resource_days'][$resource_data['name']]));
            }
            
            // Get pricing data from product meta
            $product_resources = get_post_meta($product_id, '_resource_options', true);
            foreach ($product_resources as $res) {
                if ($res['name'] === $resource_data['name']) {
                    $resource_data += array(
                        'price' => floatval($res['price'] ?? 0),
                        'fixed_price' => floatval($res['fixed_price'] ?? 0),
                        'enable_calculation' => $res['enable_calculation'] ?? 0,
                        'operator' => $res['operator'] ?? '+',
                        'percentage' => floatval($res['percentage'] ?? 0)
                    );
                    break;
                }
            }
            
            $cart_item_data['selected_resources'][] = $resource_data;
        }
    }

    // Rental dates
    if (isset($_POST['rent_a_bike_pickup_date'])) {
        $cart_item_data['rent_a_bike_pickup_date'] = sanitize_text_field($_POST['rent_a_bike_pickup_date']);
        $cart_item_data['rent_a_bike_dropoff_date'] = sanitize_text_field($_POST['rent_a_bike_dropoff_date']);
    }

    // Deposit
    if (isset($_POST['selected_deposit'])) {
        $cart_item_data['selected_deposit'] = sanitize_text_field($_POST['selected_deposit']);
    }

    // Own bike status
    if (isset($_POST['has_own_bike'])) {
        $cart_item_data['has_own_bike'] = sanitize_text_field($_POST['has_own_bike']) === 'yes' ? 'yes' : 'no';
    }

    return $cart_item_data;
}

public function display_custom_data_in_cart($item_data, $cart_item) {
    
	// Temporary debug output
    error_log('Cart Item Data: ' . print_r($cart_item, true));
	
	// Selected Bike
    if (isset($cart_item['selected_bike'])) {
        $item_data[] = array(
            'key' => __('Selected Bike', 'wc-bike-resources'),
            'value' => $cart_item['selected_bike']
        );
    }

    // Resource Options - FIXED TO SHOW DAYS AND CALCULATIONS
    // Enhanced Resources Display
    // Resource Options Display - Original Working Version
    if (isset($cart_item['selected_resources'])) {
        $product_resources = get_post_meta($cart_item['product_id'], '_resource_options', true);
        
        foreach ($cart_item['selected_resources'] as $resource) {
            $display_text = $resource['name'];
            $days = isset($resource['days']) ? max(1, intval($resource['days'])) : 1;
            
            // Show days calculation
            $display_text .= ' (' . $days . ' ' . _n('day', 'days', $days, 'wc-bike-resources') . ')';
            
            // Find matching resource from product meta
            foreach ($product_resources as $product_resource) {
                if ($product_resource['name'] === $resource['name']) {
                    // Show fixed price if exists
                    if (!empty($product_resource['fixed_price']) && $product_resource['fixed_price'] > 0) {
                        $display_text .= ' - ' . wc_price($product_resource['fixed_price']);
                    }
                    
                    // Show per-day price calculation
                    if (!empty($product_resource['price']) && $product_resource['price'] > 0) {
                        $per_day_price = floatval($product_resource['price']);
                        $total_for_resource = $per_day_price * $days;
                        $display_text .= ' - ' . wc_price($per_day_price) . '/day × ' . $days . ' = ' . wc_price($total_for_resource);
                    }
                    
                    // Show calculation method if enabled
                    if (!empty($product_resource['enable_calculation']) && !empty($product_resource['percentage'])) {
                        $operator = $product_resource['operator'] ?? '+';
                        $display_text .= ' (' . $product_resource['percentage'] . '% ' . $operator . ' base)';
                    }
                    
                    break;
                }
            }

            $item_data[] = array(
                'key' => __('Resource', 'wc-bike-resources'),
                'value' => $display_text
            );
        }
    }
    
    

 

    // Deposit
    if (isset($cart_item['selected_deposit'])) {
        $deposit_label = get_post_meta($cart_item['product_id'], '_deposit_label', true) ?: __('Deposit', 'wc-bike-resources');
        $deposit_options = get_post_meta($cart_item['product_id'], '_deposit_options', true);
        
        foreach ($deposit_options as $option) {
            if ($option['name'] === $cart_item['selected_deposit']) {
                $value = '';
                if (!empty($option['fixed_price'])) {
                    $value .= wc_price($option['fixed_price']);
                }
                if (!empty($option['enable_percent']) && !empty($option['percent'])) {
                    if (!empty($value)) $value .= ' + ';
                    $value .= $option['percent'] . '%';
                }
                
                $item_data[] = array(
                    'key' => $deposit_label,
                    'value' => $value
                );
                break;
            }
        }
    }

    // Dynamic Booking Form Fields
    $booking_fields = get_post_meta($cart_item['product_id'], '_booking_form_fields', true);
    if (is_array($booking_fields)) {
        foreach ($booking_fields as $field) {
            $field_name = $field['name'];
            if (isset($cart_item[$field_name]) && !empty($cart_item[$field_name])) {
                $item_data[] = array(
                    'key' => $field['label'] ?? ucfirst(str_replace('_', ' ', $field_name)),
                    'value' => $cart_item[$field_name]
                );
            }
        }
    }

    // Default Customer Details (fallback if not using dynamic fields)
    $customer_details = array(
        'customer_full_name' => __('Full Name', 'wc-bike-resources'),
        'customer_dob' => __('Date of Birth', 'wc-bike-resources'),
        'customer_address' => __('Address', 'wc-bike-resources'),
        'customer_phone' => __('Contact Number', 'wc-bike-resources'),
        'customer_email' => __('Email', 'wc-bike-resources'),
        'customer_passport' => __('Passport Number', 'wc-bike-resources'),
        'emergency_contact_name' => __('Emergency Contact', 'wc-bike-resources'),
        'emergency_contact_phone' => __('Emergency Phone', 'wc-bike-resources')
    );

    foreach ($customer_details as $key => $label) {
        if (isset($cart_item[$key]) && !empty($cart_item[$key])) {
            // Check if this field wasn't already added by dynamic fields
            $already_added = false;
            foreach ($item_data as $item) {
                if ($item['key'] === $label) {
                    $already_added = true;
                    break;
                }
            }
            
            if (!$already_added) {
                $item_data[] = array(
                    'key' => $label,
                    'value' => $cart_item[$key]
                );
            }
        }
    }

    return $item_data;
}

    public function display_correct_item_price($price, $cart_item, $cart_item_key) {
        return wc_price($cart_item['data']->get_price());
    }

    public function add_custom_data_to_order_item($item, $cart_item_key, $values, $order) {
        // Add selected bike to order item meta
        if (isset($values['selected_bike'])) {
            $item->add_meta_data(__('Selected Bike', 'wc-bike-resources'), $values['selected_bike']);
        }

        // Add selected resources to order item meta
        if (isset($values['selected_resources'])) {
            foreach ($values['selected_resources'] as $resource) {
                $display_text = $resource['name'] . ' (' . $resource['days'] . ' ' . __('days', 'wc-bike-resources') . ')';
                
                if (!empty($resource['fixed_price']) && $resource['fixed_price'] > 0) {
                    $display_text .= ' - ' . wc_price($resource['fixed_price']);
                }
                
                if (!empty($resource['price']) && $resource['price'] > 0) {
                    $total_price = $resource['price'] * $resource['days'];
                    $display_text .= ' - ' . wc_price($total_price);
                }
                
                $item->add_meta_data(__('Resource', 'wc-bike-resources'), $display_text);
            }
        }

        // Add rental dates to order item meta
        if (isset($values['rent_a_bike_pickup_date']) && isset($values['rent_a_bike_dropoff_date'])) {
            $pickup_date = $values['rent_a_bike_pickup_date'];
            $dropoff_date = $values['rent_a_bike_dropoff_date'];
            $days = ceil((strtotime($dropoff_date) - strtotime($pickup_date)) / (60 * 60 * 24));
            $price_per_day = get_post_meta($values['product_id'], '_rent_a_bike_price_per_day', true);
            $rent_a_bike_price = $days * floatval($price_per_day);

            $item->add_meta_data('Pick-up Date', $pickup_date);
            $item->add_meta_data('Drop-off Date', $dropoff_date);
            $item->add_meta_data('Rent a Bike Price', wc_price($rent_a_bike_price));
        }

        // Add selected deposit to order item meta
        if (isset($values['selected_deposit'])) {
            $deposit_label = get_post_meta($values['product_id'], '_deposit_label', true) ?: __('Deposit', 'wc-bike-resources');
            $deposit_options = get_post_meta($values['product_id'], '_deposit_options', true);
            
            foreach ($deposit_options as $option) {
                if ($option['name'] === $values['selected_deposit']) {
                    $value = '';
                    if (!empty($option['fixed_price'])) {
                        $value .= wc_price($option['fixed_price']);
                    }
                    if (!empty($option['enable_percent']) && !empty($option['percent'])) {
                        if (!empty($value)) $value .= ' + ';
                        $value .= $option['percent'] . '%';
                    }
                    
                    $item->add_meta_data($deposit_label, $value);
                    break;
                }
            }
        }
 // Add booking form fields to order meta
    $booking_fields = get_post_meta($values['product_id'], '_booking_form_fields', true);
    $booking_fields = is_array($booking_fields) ? $booking_fields : array();
    
    foreach ($booking_fields as $field) {
        $field_name = $field['name'];
        if (!empty($values[$field_name])) {
            $label = $field['label'] ?? ucfirst(str_replace('_', ' ', $field_name));
            $item->add_meta_data($label, $values[$field_name]);
        }
    }
        // Add customer details to order item meta
        $customer_details = array(
            'customer_full_name' => __('Full Name', 'wc-bike-resources'),
            'customer_dob' => __('Date of Birth', 'wc-bike-resources'),
            'customer_address' => __('Address', 'wc-bike-resources'),
            'customer_phone' => __('Contact Number', 'wc-bike-resources'),
            'customer_email' => __('Email', 'wc-bike-resources'),
            'customer_passport' => __('Passport Number', 'wc-bike-resources'),
            'emergency_contact_name' => __('Emergency Contact', 'wc-bike-resources'),
            'emergency_contact_phone' => __('Emergency Phone', 'wc-bike-resources')
        );

        foreach ($customer_details as $key => $label) {
            if (!empty($values[$key])) {
                $item->add_meta_data($label, $values[$key]);
            }
        }
    }
}