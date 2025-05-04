<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Rent_A_Bike {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_rent_a_bike_meta_box'));
        add_action('save_post', array($this, 'save_rent_a_bike_options'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_rent_a_bike_options'), 5);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_rent_a_bike_data_to_cart'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_rent_a_bike_data_in_cart'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_rent_a_bike_data_to_order_item'), 10, 4);
    }

    public function add_rent_a_bike_meta_box() {
        add_meta_box(
            'rent_a_bike_options',
            'Rent a Bike Options',
            array($this, 'render_rent_a_bike_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_rent_a_bike_meta_box($post) {
        $rent_a_bike_enabled = get_post_meta($post->ID, '_rent_a_bike_enabled', true);
        $rent_a_bike_heading = get_post_meta($post->ID, '_rent_a_bike_heading', true);
        $rent_a_bike_price_per_day = get_post_meta($post->ID, '_rent_a_bike_price_per_day', true);
        $rent_a_bike_required = get_post_meta($post->ID, '_rent_a_bike_required', true);
        ?>
        <div id="rent_a_bike_options_container">
            <label>
                <input type="checkbox" name="rent_a_bike_enabled" value="1" <?php checked($rent_a_bike_enabled, 1); ?> /> Enable Rent a Bike
            </label>
            <div id="rent_a_bike_fields" style="margin-top: 10px;">
                <label>
                    Heading: <input type="text" name="rent_a_bike_heading" value="<?php echo esc_attr($rent_a_bike_heading); ?>" placeholder="Rent a Bike" />
                </label>
                <label>
                    Price Per Day: <input type="number" name="rent_a_bike_price_per_day" value="<?php echo esc_attr($rent_a_bike_price_per_day); ?>" placeholder="0.00" step="0.01" />
                </label>
                <label>
                    <input type="checkbox" name="rent_a_bike_required" value="1" <?php checked($rent_a_bike_required, 1); ?> /> Required
                </label>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('input[name="rent_a_bike_enabled"]').change(function() {
                    if ($(this).is(':checked')) {
                        $('#rent_a_bike_fields').show();
                    } else {
                        $('#rent_a_bike_fields').hide();
                    }
                }).trigger('change');
            });
        </script>
        <?php
    }

    public function save_rent_a_bike_options($post_id) {
        if (isset($_POST['rent_a_bike_enabled'])) {
            update_post_meta($post_id, '_rent_a_bike_enabled', 1);
            update_post_meta($post_id, '_rent_a_bike_heading', sanitize_text_field($_POST['rent_a_bike_heading']));
            update_post_meta($post_id, '_rent_a_bike_price_per_day', floatval($_POST['rent_a_bike_price_per_day']));
            update_post_meta($post_id, '_rent_a_bike_required', isset($_POST['rent_a_bike_required']) ? 1 : 0);
        } else {
            delete_post_meta($post_id, '_rent_a_bike_enabled');
            delete_post_meta($post_id, '_rent_a_bike_heading');
            delete_post_meta($post_id, '_rent_a_bike_price_per_day');
            delete_post_meta($post_id, '_rent_a_bike_required');
        }
    }

    public function display_rent_a_bike_options() {
        global $product;
        $product_id = $product->get_id();

        $rent_a_bike_enabled = get_post_meta($product_id, '_rent_a_bike_enabled', true);
        $rent_a_bike_heading = get_post_meta($product_id, '_rent_a_bike_heading', true);
        $rent_a_bike_price_per_day = get_post_meta($product_id, '_rent_a_bike_price_per_day', true);
        $rent_a_bike_required = get_post_meta($product_id, '_rent_a_bike_required', true);

        if (!$rent_a_bike_enabled) {
            return;
        }

        // Ensure Rent a Bike options are only displayed once
        if (!did_action('woocommerce_before_add_to_cart_button')) {
            echo '<div class="rent-a-bike-options" data-price-per-day="' . esc_attr($rent_a_bike_price_per_day) . '">';
            echo '<h4>' . esc_html($rent_a_bike_heading ?: 'Rent a Bike') . '</h4>';
            echo '<label>Pick-up Date: <input type="date" name="rent_a_bike_pickup_date" class="rent-a-bike-date" required /></label>';
            echo '<label>Drop-off Date: <input type="date" name="rent_a_bike_dropoff_date" class="rent-a-bike-date" required /></label>';
            echo '<span class="per-day-price"> (€' . esc_html($rent_a_bike_price_per_day) . ' per day)</span>';
            echo '</div>';
        }
    }

    public function add_rent_a_bike_data_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['rent_a_bike_pickup_date']) && isset($_POST['rent_a_bike_dropoff_date'])) {
            $cart_item_data['rent_a_bike_pickup_date'] = sanitize_text_field($_POST['rent_a_bike_pickup_date']);
            $cart_item_data['rent_a_bike_dropoff_date'] = sanitize_text_field($_POST['rent_a_bike_dropoff_date']);
        }
        return $cart_item_data;
    }

public function display_rent_a_bike_data_in_cart($item_data, $cart_item) {
    if (isset($cart_item['rent_a_bike_pickup_date'])) {
        // Create temporary array for dates
        $date_items = array(
            array(
                'key'   => __('Pick-up Date', 'wc-bike-resources'),
                'value' => $cart_item['rent_a_bike_pickup_date']
            ),
            array(
                'key'   => __('Drop-off Date', 'wc-bike-resources'),
                'value' => $cart_item['rent_a_bike_dropoff_date']
            )
        );
        
        // Merge dates at beginning of existing items
        $item_data = array_merge($date_items, $item_data);
    }
    return $item_data;
}

    public function add_rent_a_bike_data_to_order_item($item, $cart_item_key, $values, $order) {
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
    }
}