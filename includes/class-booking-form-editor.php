<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Booking_Form_Editor {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_booking_form_meta_box'));
        add_action('save_post', array($this, 'save_booking_form_fields'));
    }

    public function add_booking_form_meta_box() {
        add_meta_box(
            'booking_form_fields',
            __('Booking Form Fields', 'wc-bike-resources'),
            array($this, 'render_booking_form_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_booking_form_meta_box($post) {
        $booking_fields = get_post_meta($post->ID, '_booking_form_fields', true);
        $booking_fields = is_array($booking_fields) ? $booking_fields : array();
        $booking_form_heading = get_post_meta($post->ID, '_booking_form_heading', true) ?: __('Booking Form', 'wc-bike-resources');
        ?>
        <div id="booking_form_container">
            <div class="booking-form-heading">
                <label>
                    <?php _e('Section Heading:', 'wc-bike-resources'); ?>
                    <input type="text" name="booking_form_heading" value="<?php echo esc_attr($booking_form_heading); ?>" />
                </label>
            </div>
            <button type="button" id="add_booking_field" class="button"><?php _e('Add Field', 'wc-bike-resources'); ?></button>
            <div id="booking_fields_list">
                <?php foreach ($booking_fields as $index => $field) : ?>
                    <div class="booking_field">
                        <input type="text" name="booking_form_fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr($field['label']); ?>" placeholder="<?php _e('Field Label', 'wc-bike-resources'); ?>" />
                        <input type="text" name="booking_form_fields[<?php echo $index; ?>][placeholder]" value="<?php echo esc_attr($field['placeholder']); ?>" placeholder="<?php _e('Placeholder', 'wc-bike-resources'); ?>" />
                        <select name="booking_form_fields[<?php echo $index; ?>][type]">
                            <option value="text" <?php selected($field['type'], 'text'); ?>><?php _e('Text', 'wc-bike-resources'); ?></option>
                            <option value="number" <?php selected($field['type'], 'number'); ?>><?php _e('Number', 'wc-bike-resources'); ?></option>
                            <option value="date" <?php selected($field['type'], 'date'); ?>><?php _e('Date', 'wc-bike-resources'); ?></option>
                            <option value="email" <?php selected($field['type'], 'email'); ?>><?php _e('Email', 'wc-bike-resources'); ?></option>
                        </select>
                        <label>
                            <input type="checkbox" name="booking_form_fields[<?php echo $index; ?>][required]" value="1" <?php checked(isset($field['required']) && $field['required'], 1); ?> /> <?php _e('Required', 'wc-bike-resources'); ?>
                        </label>
                        <button type="button" class="remove_booking_field button"><?php _e('Remove', 'wc-bike-resources'); ?></button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#add_booking_field').click(function() {
                    let index = $('#booking_fields_list .booking_field').length;
                    $('#booking_fields_list').append(
                        '<div class="booking_field">' +
                        '<input type="text" name="booking_form_fields[' + index + '][label]" value="" placeholder="<?php _e('Field Label', 'wc-bike-resources'); ?>" />' +
                        '<input type="text" name="booking_form_fields[' + index + '][placeholder]" value="" placeholder="<?php _e('Placeholder', 'wc-bike-resources'); ?>" />' +
                        '<select name="booking_form_fields[' + index + '][type]">' +
                        '<option value="text"><?php _e('Text', 'wc-bike-resources'); ?></option>' +
                        '<option value="number"><?php _e('Number', 'wc-bike-resources'); ?></option>' +
                        '<option value="date"><?php _e('Date', 'wc-bike-resources'); ?></option>' +
                        '<option value="email"><?php _e('Email', 'wc-bike-resources'); ?></option>' +
                        '</select>' +
                        '<label><input type="checkbox" name="booking_form_fields[' + index + '][required]" value="1" /> <?php _e('Required', 'wc-bike-resources'); ?></label>' +
                        '<button type="button" class="remove_booking_field button"><?php _e('Remove', 'wc-bike-resources'); ?></button>' +
                        '</div>'
                    );
                });

                $(document).on('click', '.remove_booking_field', function() {
                    $(this).closest('.booking_field').remove();
                    reindexBookingFields();
                });

                function reindexBookingFields() {
                    $('#booking_fields_list .booking_field').each(function(index) {
                        $(this).find('input[name^="booking_form_fields"]').each(function() {
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

    public function save_booking_form_fields($post_id) {
        if (isset($_POST['booking_form_fields'])) {
            $booking_fields = array_values($_POST['booking_form_fields']);
            update_post_meta($post_id, '_booking_form_fields', $booking_fields);
        } else {
            delete_post_meta($post_id, '_booking_form_fields');
        }
        
        if (isset($_POST['booking_form_heading'])) {
            update_post_meta($post_id, '_booking_form_heading', sanitize_text_field($_POST['booking_form_heading']));
        }
    }
}