<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Bike_Options {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_bike_options_meta_box'));
        add_action('save_post', array($this, 'save_bike_options'));
    }

    public function add_bike_options_meta_box() {
        add_meta_box(
            'bike_options',
            __('Bike Options', 'wc-bike-resources'),
            array($this, 'render_bike_options_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_bike_options_meta_box($post) {
        $bikes = get_post_meta($post->ID, '_bike_options', true);
        $bikes = is_array($bikes) ? $bikes : array();
        $bike_heading = get_post_meta($post->ID, '_bike_heading', true) ?: __('Bike Options', 'wc-bike-resources');
        $bike_required = get_post_meta($post->ID, '_bike_required', true);
        $own_bike_enabled = get_post_meta($post->ID, '_own_bike_enabled', true);
        $own_bike_label = get_post_meta($post->ID, '_own_bike_label', true) ?: __('Do you have your own motorcycle?', 'wc-bike-resources');
        ?>
        <div id="bike_options_container">
            <div class="bike-option-heading">
                <label>
                    <?php _e('Section Heading:', 'wc-bike-resources'); ?>
                    <input type="text" name="bike_heading" value="<?php echo esc_attr($bike_heading); ?>" />
                </label>
                <label style="margin-left: 15px;">
                    <input type="checkbox" name="bike_required" value="1" <?php checked($bike_required, 1); ?> />
                    <?php _e('Make bike selection required', 'wc-bike-resources'); ?>
                </label>
            </div>
            
            <div class="own-bike-option" style="margin: 15px 0;">
                <label>
                    <input type="checkbox" name="own_bike_enabled" value="1" <?php checked($own_bike_enabled, 1); ?> class="toggle-own-bike" />
                    <?php _e('Enable "Own Motorcycle" option', 'wc-bike-resources'); ?>
                </label>
                <div class="own-bike-fields" style="margin-top: 10px; <?php echo !$own_bike_enabled ? 'display:none;' : ''; ?>">
                    <label>
                        <?php _e('Question Label:', 'wc-bike-resources'); ?>
                        <input type="text" name="own_bike_label" value="<?php echo esc_attr($own_bike_label); ?>" />
                    </label>
                </div>
            </div>
            
            <button type="button" id="add_bike_option" class="button"><?php _e('Add Bike', 'wc-bike-resources'); ?></button>
            <div id="bike_options_list">
                <?php foreach ($bikes as $index => $bike) : ?>
                    <div class="bike_option">
                        <input type="text" name="bike_options[<?php echo $index; ?>][name]" value="<?php echo esc_attr($bike['name']); ?>" placeholder="<?php _e('Bike Name', 'wc-bike-resources'); ?>" />
                        <input type="number" name="bike_options[<?php echo $index; ?>][price]" value="<?php echo esc_attr($bike['price']); ?>" placeholder="<?php _e('Price', 'wc-bike-resources'); ?>" step="0.01" />
                        <button type="button" class="remove_bike_option button"><?php _e('Remove', 'wc-bike-resources'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('.toggle-own-bike').change(function() {
                    $(this).closest('.own-bike-option').find('.own-bike-fields').toggle($(this).is(':checked'));
                });
                
                $('#add_bike_option').click(function() {
                    let index = $('#bike_options_list .bike_option').length;
                    $('#bike_options_list').append(
                        '<div class="bike_option">' +
                        '<input type="text" name="bike_options[' + index + '][name]" value="" placeholder="<?php _e('Bike Name', 'wc-bike-resources'); ?>" />' +
                        '<input type="number" name="bike_options[' + index + '][price]" value="" placeholder="<?php _e('Price', 'wc-bike-resources'); ?>" step="0.01" />' +
                        '<button type="button" class="remove_bike_option button"><?php _e('Remove', 'wc-bike-resources'); ?></button>' +
                        '</div>'
                    );
                });

                $(document).on('click', '.remove_bike_option', function() {
                    $(this).closest('.bike_option').remove();
                    reindexBikeOptions();
                });

                function reindexBikeOptions() {
                    $('#bike_options_list .bike_option').each(function(index) {
                        $(this).find('input[name^="bike_options"]').each(function() {
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

    public function save_bike_options($post_id) {
        if (isset($_POST['bike_options'])) {
            $bike_options = array_values($_POST['bike_options']);
            update_post_meta($post_id, '_bike_options', $bike_options);
        } else {
            delete_post_meta($post_id, '_bike_options');
        }
        
        if (isset($_POST['bike_heading'])) {
            update_post_meta($post_id, '_bike_heading', sanitize_text_field($_POST['bike_heading']));
        }
        
        update_post_meta($post_id, '_bike_required', isset($_POST['bike_required']) ? 1 : 0);
        update_post_meta($post_id, '_own_bike_enabled', isset($_POST['own_bike_enabled']) ? 1 : 0);
        
        if (isset($_POST['own_bike_label'])) {
            update_post_meta($post_id, '_own_bike_label', sanitize_text_field($_POST['own_bike_label']));
        }
    }
}
