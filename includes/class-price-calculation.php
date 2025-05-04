<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Price_Calculation {
    public function __construct() {
        add_action('woocommerce_before_calculate_totals', array($this, 'calculate_custom_price'), 20, 1);
    }

    private function calculate_deposit_price($cart_item, $original_price) {
        $deposit_options = get_post_meta($cart_item['product_id'], '_deposit_options', true);
        $deposit_amount = 0;
        
        if (empty($deposit_options) || !isset($cart_item['selected_deposit'])) {
            return 0;
        }
        
        $selected_deposit = $cart_item['selected_deposit'];
        foreach ($deposit_options as $option) {
            if ($option['name'] === $selected_deposit) {
                // Fixed price
                if (!empty($option['fixed_price'])) {
                    $deposit_amount += floatval($option['fixed_price']);
                }
                
                // Percentage calculation
                if (!empty($option['enable_percent']) && !empty($option['percent'])) {
                    $percentage = floatval($option['percent']);
                    $base_amount = $original_price;
                    
                    // Add bike price if enabled
                    if (!empty($option['enable_bike']) && isset($cart_item['selected_bike'])) {
                        $bikes = get_post_meta($cart_item['product_id'], '_bike_options', true);
                        foreach ($bikes as $bike) {
                            if ($bike['name'] === $cart_item['selected_bike']) {
                                $base_amount += floatval($bike['price']);
                                break;
                            }
                        }
                    }
                    
                    // Add resources price if enabled
                    if (!empty($option['enable_resource']) && isset($cart_item['selected_resources'])) {
                        $resources = get_post_meta($cart_item['product_id'], '_resource_options', true);
                        foreach ($cart_item['selected_resources'] as $selected_resource) {
                            foreach ($resources as $resource) {
                                if ($resource['name'] === $selected_resource['name']) {
                                    if (!empty($resource['fixed_price'])) {
                                        $base_amount += floatval($resource['fixed_price']);
                                    }
                                    
                                    if (isset($selected_resource['days'])) {
                                        $days = intval($selected_resource['days']);
                                        $per_day_price = floatval($resource['price'] ?? 0);
                                        
                                        if (!empty($resource['enable_calculation'])) {
                                            $operator = $resource['operator'] ?? '+';
                                            $resource_percentage = floatval($resource['percentage'] ?? 0);
                                            $percentage_value = ($original_price * $resource_percentage) / 100;

                                            switch ($operator) {
                                                case '+': $per_day_price = $percentage_value; break;
                                                case '-': $per_day_price = -$percentage_value; break;
                                                case '*': $per_day_price = $original_price * ($resource_percentage / 100); break;
                                                case '/': $per_day_price = $original_price / ($resource_percentage / 100); break;
                                            }
                                        }
                                        $base_amount += $per_day_price * $days;
                                    }
                                }
                            }
                        }
                    }
                    
                    $deposit_amount += ($base_amount * $percentage) / 100;
                }
                break;
            }
        }
        
        return $deposit_amount;
    }

    public function calculate_custom_price($cart) {
        if (defined('DOING_STEP_3') || did_action('woocommerce_before_calculate_totals') > 1) {
            return;
        }
        
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $original_price = $product->get_price();
            $new_price = 0;

            // Calculate deposit first (if selected)
            $deposit_amount = $this->calculate_deposit_price($cart_item, $original_price);
            
            if ($deposit_amount > 0) {
                $new_price = $deposit_amount;
            } else {
                // Start with original price (unless rent-a-bike is selected)
                if (!isset($cart_item['rent_a_bike_pickup_date'])) {
                    $new_price = $original_price;
                }

                // Add Rent a Bike price if selected
                if (isset($cart_item['rent_a_bike_pickup_date']) && isset($cart_item['rent_a_bike_dropoff_date'])) {
                    $pickup_date = strtotime($cart_item['rent_a_bike_pickup_date']);
                    $dropoff_date = strtotime($cart_item['rent_a_bike_dropoff_date']);
                    $days = ceil(($dropoff_date - $pickup_date) / (60 * 60 * 24));
                    $price_per_day = get_post_meta($cart_item['product_id'], '_rent_a_bike_price_per_day', true);
                    $new_price += $days * floatval($price_per_day);
                }

                // Add selected bike price
                if (isset($cart_item['selected_bike'])) {
                    $bikes = get_post_meta($cart_item['product_id'], '_bike_options', true);
                    foreach ($bikes as $bike) {
                        if ($bike['name'] === $cart_item['selected_bike']) {
                            $new_price += floatval($bike['price']);
                            break;
                        }
                    }
                }

 // Add selected resources price - UPDATED TO INCLUDE RENTAL DAYS
if (isset($cart_item['selected_resources'])) {
    $resources = get_post_meta($cart_item['product_id'], '_resource_options', true);
    
    // Calculate rental days if rent-a-bike is selected
    $rental_days = 1;
    if (isset($cart_item['rent_a_bike_pickup_date']) && isset($cart_item['rent_a_bike_dropoff_date'])) {
        $pickup = strtotime($cart_item['rent_a_bike_pickup_date']);
        $dropoff = strtotime($cart_item['rent_a_bike_dropoff_date']);
        $rental_days = max(1, ceil(($dropoff - $pickup) / (60 * 60 * 24)));
    }

    foreach ($cart_item['selected_resources'] as $selected_resource) {
        foreach ($resources as $resource) {
            if ($resource['name'] === $selected_resource['name']) {
                // Add fixed price if exists
                if (!empty($resource['fixed_price'])) {
                    $new_price += floatval($resource['fixed_price']);
                }

                // Determine days - use rental days if available, otherwise use stored value
                $days = isset($selected_resource['days']) ? max(1, intval($selected_resource['days'])) : $rental_days;
                
                // Determine base price for calculations
                $base_price = (isset($cart_item['rent_a_bike_pickup_date']) && $resource['enable_calculation']) 
                    ? ($rental_days * floatval(get_post_meta($cart_item['product_id'], '_rent_a_bike_price_per_day', true)))
                    : $original_price;

                // Calculate per-day price
                $per_day_price = floatval($resource['price'] ?? 0);
                
                // Percentage-based calculation if enabled
                if (!empty($resource['enable_calculation'])) {
                    $operator = $resource['operator'] ?? '+';
                    $percentage = floatval($resource['percentage'] ?? 0);
                    $percentage_value = ($base_price * $percentage) / 100;

                    switch ($operator) {
                        case '+': $per_day_price = $percentage_value; break;
                        case '-': $per_day_price = -$percentage_value; break;
                        case '*': $per_day_price = $base_price * ($percentage / 100); break;
                        case '/': $per_day_price = $base_price / ($percentage / 100); break;
                    }
                }

                // Add to total price
                $new_price += $per_day_price * $days;
                break;
            }
        }
    }
}
            }

            // Set the final price
            $product->set_price($new_price);
        }
    }
}