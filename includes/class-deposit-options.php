<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Deposit_Options {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_deposit_options_meta_box'));
        add_action('save_post', array($this, 'save_deposit_options'));
    }

    public function add_deposit_options_meta_box() {
        add_meta_box(
            'deposit_options',
            __('Deposit Options', 'wc-bike-resources'),
            array($this, 'render_deposit_options_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_deposit_options_meta_box($post) {
        $deposit_options = get_post_meta($post->ID, '_deposit_options', true);
        $deposit_options = is_array($deposit_options) ? $deposit_options : array();
        $deposit_heading = get_post_meta($post->ID, '_deposit_heading', true) ?: __('Deposit Options', 'wc-bike-resources');
        $deposit_label = get_post_meta($post->ID, '_deposit_label', true) ?: __('Deposit', 'wc-bike-resources');
        ?>
        <div id="deposit_options_container">
            <div class="deposit-option-heading">
                <label>
                    <?php _e('Section Heading:', 'wc-bike-resources'); ?>
                    <input type="text" name="deposit_heading" value="<?php echo esc_attr($deposit_heading); ?>" />
                </label>
            </div>
            <div class="deposit-label">
                <label>
                    <?php _e('Deposit Label:', 'wc-bike-resources'); ?>
                    <input type="text" name="deposit_label" value="<?php echo esc_attr($deposit_label); ?>" placeholder="<?php _e('Deposit', 'wc-bike-resources'); ?>" />
                </label>
            </div>
            <button type="button" id="add_deposit_option" class="button"><?php _e('Add Deposit Option', 'wc-bike-resources'); ?></button>
            <div id="deposit_options_list">
                <?php foreach ($deposit_options as $index => $option) : ?>
                    <div class="deposit_option">
                        <input type="text" name="deposit_options[<?php echo $index; ?>][name]" value="<?php echo esc_attr($option['name']); ?>" placeholder="<?php _e('Option Name', 'wc-bike-resources'); ?>" />
                        <input type="number" name="deposit_options[<?php echo $index; ?>][fixed_price]" value="<?php echo esc_attr($option['fixed_price']); ?>" placeholder="<?php _e('Fixed Price', 'wc-bike-resources'); ?>" step="0.01" />
                        <label>
                            <input type="checkbox" name="deposit_options[<?php echo $index; ?>][enable_percent]" value="1" <?php checked(isset($option['enable_percent']) && $option['enable_percent'], 1); ?> class="enable-percent" /> <?php _e('Enable Percentage', 'wc-bike-resources'); ?>
                        </label>
                        <input type="number" name="deposit_options[<?php echo $index; ?>][percent]" value="<?php echo esc_attr($option['percent'] ?? ''); ?>" placeholder="<?php _e('Percentage', 'wc-bike-resources'); ?>" step="1" min="0" max="100" class="percent-field" <?php echo empty($option['enable_percent']) ? 'disabled' : ''; ?> />
                        <label>
                            <input type="checkbox" name="deposit_options[<?php echo $index; ?>][enable_bike]" value="1" <?php checked(isset($option['enable_bike']) && $option['enable_bike'], 1); ?> /> <?php _e('Apply to Bike', 'wc-bike-resources'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="deposit_options[<?php echo $index; ?>][enable_resource]" value="1" <?php checked(isset($option['enable_resource']) && $option['enable_resource'], 1); ?> /> <?php _e('Apply to Resource', 'wc-bike-resources'); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="deposit_options[<?php echo $index; ?>][enable_rent]" value="1" <?php checked(isset($option['enable_rent']) && $option['enable_rent'], 1); ?> /> <?php _e('Apply to Rent', 'wc-bike-resources'); ?>
                        </label>
                        <button type="button" class="remove_deposit_option button"><?php _e('Remove', 'wc-bike-resources'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $(document).on('change', '.enable-percent', function() {
                    $(this).closest('.deposit_option').find('.percent-field').prop('disabled', !$(this).is(':checked'));
                });

                $('#add_deposit_option').click(function() {
                    let index = $('#deposit_options_list .deposit_option').length;
                    $('#deposit_options_list').append(
                        '<div class="deposit_option">' +
                        '<input type="text" name="deposit_options[' + index + '][name]" value="" placeholder="<?php _e('Option Name', 'wc-bike-resources'); ?>" />' +
                        '<input type="number" name="deposit_options[' + index + '][fixed_price]" value="" placeholder="<?php _e('Fixed Price', 'wc-bike-resources'); ?>" step="0.01" />' +
                        '<label><input type="checkbox" name="deposit_options[' + index + '][enable_percent]" value="1" class="enable-percent" /> <?php _e('Enable Percentage', 'wc-bike-resources'); ?></label>' +
                        '<input type="number" name="deposit_options[' + index + '][percent]" value="" placeholder="<?php _e('Percentage', 'wc-bike-resources'); ?>" step="1" min="0" max="100" class="percent-field" disabled />' +
                        '<label><input type="checkbox" name="deposit_options[' + index + '][enable_bike]" value="1" /> <?php _e('Apply to Bike', 'wc-bike-resources'); ?></label>' +
                        '<label><input type="checkbox" name="deposit_options[' + index + '][enable_resource]" value="1" /> <?php _e('Apply to Resource', 'wc-bike-resources'); ?></label>' +
                        '<label><input type="checkbox" name="deposit_options[' + index + '][enable_rent]" value="1" /> <?php _e('Apply to Rent', 'wc-bike-resources'); ?></label>' +
                        '<button type="button" class="remove_deposit_option button"><?php _e('Remove', 'wc-bike-resources'); ?></button>' +
                        '</div>'
                    );
                });

                $(document).on('click', '.remove_deposit_option', function() {
                    $(this).closest('.deposit_option').remove();
                    reindexDepositOptions();
                });

                function reindexDepositOptions() {
                    $('#deposit_options_list .deposit_option').each(function(index) {
                        $(this).find('input[name^="deposit_options"]').each(function() {
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

    public function save_deposit_options($post_id) {
        if (isset($_POST['deposit_label'])) {
            update_post_meta($post_id, '_deposit_label', sanitize_text_field($_POST['deposit_label']));
        }
        
        if (isset($_POST['deposit_heading'])) {
            update_post_meta($post_id, '_deposit_heading', sanitize_text_field($_POST['deposit_heading']));
        }
        
        if (isset($_POST['deposit_options'])) {
            $deposit_options = array_values($_POST['deposit_options']);
            update_post_meta($post_id, '_deposit_options', $deposit_options);
        } else {
            delete_post_meta($post_id, '_deposit_options');
        }
    }
}