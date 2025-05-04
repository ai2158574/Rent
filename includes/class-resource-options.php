<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Resource_Options {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_resource_options_meta_box'));
        add_action('save_post', array($this, 'save_resource_options'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_script('select2');
        wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css');
    }

    public function add_resource_options_meta_box() {
        add_meta_box(
            'resource_options',
            __('Resource Options', 'wc-bike-resources'),
            array($this, 'render_resource_options_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_resource_options_meta_box($post) {
        $resources = get_post_meta($post->ID, '_resource_options', true);
        $resources = is_array($resources) ? $resources : array();
        $resource_heading = get_post_meta($post->ID, '_resource_heading', true) ?: __('Resource Options', 'wc-bike-resources');
        ?>
        <div id="resource_options_container">
            <div class="resource-option-heading">
                <label>
                    <?php _e('Section Heading:', 'wc-bike-resources'); ?>
                    <input type="text" name="resource_heading" value="<?php echo esc_attr($resource_heading); ?>" />
                </label>
            </div>
            <button type="button" id="add_resource_option" class="button"><?php _e('Add Resource', 'wc-bike-resources'); ?></button>
            <div id="resource_options_list">
                <?php foreach ($resources as $index => $resource) : ?>
                    <div class="resource_option">
                        <input type="text" name="resource_options[<?php echo $index; ?>][name]" value="<?php echo esc_attr($resource['name']); ?>" placeholder="<?php _e('Resource Name', 'wc-bike-resources'); ?>" />
                        <input type="number" name="resource_options[<?php echo $index; ?>][fixed_price]" value="<?php echo esc_attr($resource['fixed_price']); ?>" placeholder="<?php _e('Fixed Price', 'wc-bike-resources'); ?>" step="0.01" />
                        <input type="number" name="resource_options[<?php echo $index; ?>][price]" value="<?php echo esc_attr($resource['price']); ?>" placeholder="<?php _e('Price per Day', 'wc-bike-resources'); ?>" step="0.01" class="price-per-day" />
                        <label>
                            <input type="checkbox" name="resource_options[<?php echo $index; ?>][enable_calculation]" value="1" <?php checked(isset($resource['enable_calculation']) && $resource['enable_calculation'], 1); ?> class="enable-calculation" /> <?php _e('Enable Calculation', 'wc-bike-resources'); ?>
                        </label>
                        <select name="resource_options[<?php echo $index; ?>][operator]">
                            <option value="+" <?php selected($resource['operator'] ?? '', '+'); ?>>+</option>
                            <option value="-" <?php selected($resource['operator'] ?? '', '-'); ?>>-</option>
                            <option value="*" <?php selected($resource['operator'] ?? '', '*'); ?>>*</option>
                            <option value="/" <?php selected($resource['operator'] ?? '', '/'); ?>>/</option>
                        </select>
                        <input type="number" name="resource_options[<?php echo $index; ?>][percentage]" value="<?php echo esc_attr($resource['percentage'] ?? ''); ?>" placeholder="<?php _e('Percentage', 'wc-bike-resources'); ?>" step="1" />
                        <label>
                            <input type="checkbox" name="resource_options[<?php echo $index; ?>][enable_days]" value="1" <?php checked(isset($resource['enable_days']) && $resource['enable_days'], 1); ?> /> <?php _e('Enable Days', 'wc-bike-resources'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="resource_options[<?php echo $index; ?>][required]" value="1" <?php checked(isset($resource['required']) && $resource['required'], 1); ?> /> <?php _e('Required', 'wc-bike-resources'); ?>
                        </label>
                        
                        <!-- New Paired Resource Section -->
                        <div class="resource-conditional">
                            <label>
                                <input type="checkbox" name="resource_options[<?php echo $index; ?>][has_conditional]" value="1" 
                                    <?php checked(isset($resource['has_conditional']) && $resource['has_conditional'], 1); ?> 
                                    class="toggle-conditional" /> 
                                <?php _e('Enable Paired Requirement', 'wc-bike-resources'); ?>
                            </label>
                            <div class="conditional-pair" <?php echo (isset($resource['has_conditional']) && $resource['has_conditional']) ? '' : 'style="display:none;"'; ?>>
                                <select name="resource_options[<?php echo $index; ?>][paired_resource]" class="wc-enhanced-select">
                                    <option value=""><?php _e('Select Paired Resource', 'wc-bike-resources'); ?></option>
                                    <?php foreach ($resources as $res_index => $res) : ?>
                                        <?php if ($res_index != $index && !empty($res['name'])) : ?>
                                            <option value="<?php echo esc_attr($res['name']); ?>" 
                                                <?php selected(isset($resource['paired_resource']) && $resource['paired_resource'] === $res['name'], true); ?>>
                                                <?php echo esc_html($res['name']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <button type="button" class="remove_resource_option button"><?php _e('Remove', 'wc-bike-resources'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                // Toggle conditional fields
                $(document).on('change', '.toggle-conditional', function() {
                    $(this).closest('.resource-conditional').find('.conditional-pair').toggle($(this).is(':checked'));
                });

                // Initialize select2
                $('.wc-enhanced-select').select2();

                // Add new resource option
                $('#add_resource_option').click(function() {
                    let index = $('#resource_options_list .resource_option').length;
                    $('#resource_options_list').append(`
                        <div class="resource_option">
                            <input type="text" name="resource_options[${index}][name]" value="" placeholder="<?php _e('Resource Name', 'wc-bike-resources'); ?>" />
                            <input type="number" name="resource_options[${index}][fixed_price]" value="" placeholder="<?php _e('Fixed Price', 'wc-bike-resources'); ?>" step="0.01" />
                            <input type="number" name="resource_options[${index}][price]" value="" placeholder="<?php _e('Price per Day', 'wc-bike-resources'); ?>" step="0.01" class="price-per-day" />
                            <label><input type="checkbox" name="resource_options[${index}][enable_calculation]" value="1" class="enable-calculation" /> <?php _e('Enable Calculation', 'wc-bike-resources'); ?></label>
                            <select name="resource_options[${index}][operator]">
                                <option value="+">+</option>
                                <option value="-">-</option>
                                <option value="*">*</option>
                                <option value="/">/</option>
                            </select>
                            <input type="number" name="resource_options[${index}][percentage]" value="" placeholder="<?php _e('Percentage', 'wc-bike-resources'); ?>" step="1" />
                            <label><input type="checkbox" name="resource_options[${index}][enable_days]" value="1" /> <?php _e('Enable Days', 'wc-bike-resources'); ?></label>
                            <label><input type="checkbox" name="resource_options[${index}][required]" value="1" /> <?php _e('Required', 'wc-bike-resources'); ?></label>
                            
                            <div class="resource-conditional">
                                <label>
                                    <input type="checkbox" name="resource_options[${index}][has_conditional]" value="1" class="toggle-conditional" />
                                    <?php _e('Enable Paired Requirement', 'wc-bike-resources'); ?>
                                </label>
                                <div class="conditional-pair" style="display:none;">
                                    <select name="resource_options[${index}][paired_resource]" class="wc-enhanced-select">
                                        <option value=""><?php _e('Select Paired Resource', 'wc-bike-resources'); ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="button" class="remove_resource_option button"><?php _e('Remove', 'wc-bike-resources'); ?></button>
                        </div>
                    `);
                    
                    // Initialize select2 for new element
                    $('#resource_options_list .resource_option').last().find('.wc-enhanced-select').select2();
                });

                $(document).on('click', '.remove_resource_option', function() {
                    $(this).closest('.resource_option').remove();
                    reindexResourceOptions();
                });

                function reindexResourceOptions() {
                    $('#resource_options_list .resource_option').each(function(index) {
                        $(this).find('input, select').each(function() {
                            let name = $(this).attr('name');
                            name = name.replace(/\[\d+\]/, '[' + index + ']');
                            $(this).attr('name', name);
                        });
                    });
                }
            });
        </script>
        <?php
    }

    public function save_resource_options($post_id) {
        if (isset($_POST['resource_options'])) {
            $resource_options = array();
            foreach ($_POST['resource_options'] as $index => $option) {
                $resource_options[$index] = array(
                    'name' => sanitize_text_field($option['name']),
                    'fixed_price' => isset($option['fixed_price']) ? floatval($option['fixed_price']) : 0,
                    'price' => isset($option['price']) ? floatval($option['price']) : 0,
                    'enable_calculation' => isset($option['enable_calculation']) ? 1 : 0,
                    'operator' => isset($option['operator']) ? sanitize_text_field($option['operator']) : '+',
                    'percentage' => isset($option['percentage']) ? intval($option['percentage']) : 0,
                    'enable_days' => isset($option['enable_days']) ? 1 : 0,
                    'required' => isset($option['required']) ? 1 : 0,
                    'has_conditional' => isset($option['has_conditional']) ? 1 : 0,
                    'paired_resource' => isset($option['paired_resource']) ? sanitize_text_field($option['paired_resource']) : ''
                );
            }
            update_post_meta($post_id, '_resource_options', $resource_options);
        } else {
            delete_post_meta($post_id, '_resource_options');
        }
        
        if (isset($_POST['resource_heading'])) {
            update_post_meta($post_id, '_resource_heading', sanitize_text_field($_POST['resource_heading']));
        }
    }
}