<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Booking_Form_Options {
    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_booking_form_meta_box'));
        add_action('save_post', array($this, 'save_booking_form_options'));
    }

    public function add_booking_form_meta_box() {
        add_meta_box(
            'booking_form_options',
            __('Booking Form Options', 'wc-bike-resources'),
            array($this, 'render_booking_form_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    public function render_booking_form_meta_box($post) {
        $form_fields = get_post_meta($post->ID, '_booking_form_fields', true);
        $form_fields = is_array($form_fields) ? $form_fields : $this->get_default_fields();
        $form_heading = get_post_meta($post->ID, '_booking_form_heading', true) ?: __('Booking Form', 'wc-bike-resources');
        
        wp_nonce_field('wc_br_booking_form_nonce', 'wc_br_booking_form_nonce');
        ?>
        <div id="booking_form_options_container">
            <div class="form-option-heading">
                <label>
                    <?php _e('Form Heading:', 'wc-bike-resources'); ?>
                    <input type="text" name="booking_form_heading" value="<?php echo esc_attr($form_heading); ?>" />
                </label>
            </div>
            
            <div id="booking_form_fields_list">
                <?php foreach ($form_fields as $index => $field) : ?>
                    <div class="booking-form-field" data-index="<?php echo $index; ?>">
                        <div class="field-controls">
                            <span class="dashicons dashicons-menu handle"></span>
                            <button type="button" class="remove-field button"><?php _e('Remove', 'wc-bike-resources'); ?></button>
                        </div>
                        
                        <div class="field-settings">
                            <label>
                                <?php _e('Field Type:', 'wc-bike-resources'); ?>
                                <select name="booking_form_fields[<?php echo $index; ?>][type]" class="field-type">
                                    <option value="text" <?php selected($field['type'], 'text'); ?>><?php _e('Text', 'wc-bike-resources'); ?></option>
                                    <option value="email" <?php selected($field['type'], 'email'); ?>><?php _e('Email', 'wc-bike-resources'); ?></option>
                                    <option value="tel" <?php selected($field['type'], 'tel'); ?>><?php _e('Phone', 'wc-bike-resources'); ?></option>
                                    <option value="date" <?php selected($field['type'], 'date'); ?>><?php _e('Date', 'wc-bike-resources'); ?></option>
                                    <option value="textarea" <?php selected($field['type'], 'textarea'); ?>><?php _e('Textarea', 'wc-bike-resources'); ?></option>
                                </select>
                            </label>
                            
                            <label>
                                <?php _e('Field Name:', 'wc-bike-resources'); ?>
                                <input type="text" name="booking_form_fields[<?php echo $index; ?>][name]" value="<?php echo esc_attr($field['name']); ?>" placeholder="customer_full_name" />
                            </label>
                            
                            <label>
                                <?php _e('Field Label:', 'wc-bike-resources'); ?>
                                <input type="text" name="booking_form_fields[<?php echo $index; ?>][label]" value="<?php echo esc_attr($field['label']); ?>" placeholder="<?php _e('Full Name', 'wc-bike-resources'); ?>" />
                            </label>
                            
                            <label>
                                <input type="checkbox" name="booking_form_fields[<?php echo $index; ?>][required]" value="1" <?php checked($field['required'], 1); ?> />
                                <?php _e('Required', 'wc-bike-resources'); ?>
                            </label>
                            
                            <label class="placeholder-label" <?php echo ($field['type'] === 'textarea') ? 'style="display:none;"' : ''; ?>>
                                <?php _e('Placeholder:', 'wc-bike-resources'); ?>
                                <input type="text" name="booking_form_fields[<?php echo $index; ?>][placeholder]" value="<?php echo esc_attr($field['placeholder']); ?>" />
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" id="add_booking_form_field" class="button"><?php _e('Add Field', 'wc-bike-resources'); ?></button>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                // Field type change handler
                $(document).on('change', '.field-type', function() {
                    const $field = $(this).closest('.booking-form-field');
                    const type = $(this).val();
                    
                    if (type === 'textarea') {
                        $field.find('.placeholder-label').hide();
                    } else {
                        $field.find('.placeholder-label').show();
                    }
                });
                
                // Add new field
                $('#add_booking_form_field').click(function() {
                    const index = Date.now();
                    const $field = $(
                        '<div class="booking-form-field" data-index="' + index + '">' +
                            '<div class="field-controls">' +
                                '<span class="dashicons dashicons-menu handle"></span>' +
                                '<button type="button" class="remove-field button"><?php _e("Remove", "wc-bike-resources"); ?></button>' +
                            '</div>' +
                            '<div class="field-settings">' +
                                '<label><?php _e("Field Type:", "wc-bike-resources"); ?>' +
                                    '<select name="booking_form_fields[' + index + '][type]" class="field-type">' +
                                        '<option value="text"><?php _e("Text", "wc-bike-resources"); ?></option>' +
                                        '<option value="email"><?php _e("Email", "wc-bike-resources"); ?></option>' +
                                        '<option value="tel"><?php _e("Phone", "wc-bike-resources"); ?></option>' +
                                        '<option value="date"><?php _e("Date", "wc-bike-resources"); ?></option>' +
                                        '<option value="textarea"><?php _e("Textarea", "wc-bike-resources"); ?></option>' +
                                    '</select>' +
                                '</label>' +
                                '<label><?php _e("Field Name:", "wc-bike-resources"); ?>' +
                                    '<input type="text" name="booking_form_fields[' + index + '][name]" value="" placeholder="customer_full_name" />' +
                                '</label>' +
                                '<label><?php _e("Field Label:", "wc-bike-resources"); ?>' +
                                    '<input type="text" name="booking_form_fields[' + index + '][label]" value="" placeholder="<?php _e("Full Name", "wc-bike-resources"); ?>" />' +
                                '</label>' +
                                '<label>' +
                                    '<input type="checkbox" name="booking_form_fields[' + index + '][required]" value="1" /> <?php _e("Required", "wc-bike-resources"); ?>' +
                                '</label>' +
                                '<label class="placeholder-label"><?php _e("Placeholder:", "wc-bike-resources"); ?>' +
                                    '<input type="text" name="booking_form_fields[' + index + '][placeholder]" value="" />' +
                                '</label>' +
                            '</div>' +
                        '</div>'
                    );
                    
                    $('#booking_form_fields_list').append($field);
                });
                
                // Remove field
                $(document).on('click', '.remove-field', function() {
                    $(this).closest('.booking-form-field').remove();
                });
                
                // Make fields sortable
                $('#booking_form_fields_list').sortable({
                    handle: '.handle',
                    axis: 'y',
                    update: function() {
                        // No need to reindex as we're using timestamp indexes
                    }
                });
            });
        </script>
        
        <style>
            .booking-form-field {
                border: 1px solid #ddd;
                padding: 10px;
                margin-bottom: 10px;
                background: #f9f9f9;
            }
            .field-controls {
                margin-bottom: 10px;
                display: flex;
                align-items: center;
            }
            .field-controls .handle {
                cursor: move;
                margin-right: 10px;
            }
            .field-settings {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 10px;
            }
            .field-settings label {
                display: block;
                margin-bottom: 5px;
            }
        </style>
        <?php
    }

    private function get_default_fields() {
        return array(
            array(
                'type' => 'text',
                'name' => 'customer_full_name',
                'label' => __('Full Name', 'wc-bike-resources'),
                'placeholder' => __('Enter your full name', 'wc-bike-resources'),
                'required' => 1
            ),
            array(
                'type' => 'date',
                'name' => 'customer_dob',
                'label' => __('Date of Birth', 'wc-bike-resources'),
                'placeholder' => '',
                'required' => 0
            ),
            array(
                'type' => 'textarea',
                'name' => 'customer_address',
                'label' => __('Address', 'wc-bike-resources'),
                'placeholder' => '',
                'required' => 0
            ),
            array(
                'type' => 'tel',
                'name' => 'customer_phone',
                'label' => __('Contact Number', 'wc-bike-resources'),
                'placeholder' => __('Phone number', 'wc-bike-resources'),
                'required' => 1
            ),
            array(
                'type' => 'email',
                'name' => 'customer_email',
                'label' => __('Email Address', 'wc-bike-resources'),
                'placeholder' => __('Your email', 'wc-bike-resources'),
                'required' => 1
            ),
            array(
                'type' => 'text',
                'name' => 'customer_passport',
                'label' => __('Passport Number', 'wc-bike-resources'),
                'placeholder' => __('Passport number', 'wc-bike-resources'),
                'required' => 0
            ),
            array(
                'type' => 'text',
                'name' => 'emergency_contact_name',
                'label' => __('Emergency Contact Name', 'wc-bike-resources'),
                'placeholder' => __('Full name', 'wc-bike-resources'),
                'required' => 0
            ),
            array(
                'type' => 'tel',
                'name' => 'emergency_contact_phone',
                'label' => __('Emergency Contact Number', 'wc-bike-resources'),
                'placeholder' => __('Phone number', 'wc-bike-resources'),
                'required' => 0
            )
        );
    }

    public function save_booking_form_options($post_id) {
        if (!isset($_POST['wc_br_booking_form_nonce']) || 
            !wp_verify_nonce($_POST['wc_br_booking_form_nonce'], 'wc_br_booking_form_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['booking_form_heading'])) {
            update_post_meta($post_id, '_booking_form_heading', sanitize_text_field($_POST['booking_form_heading']));
        }

        if (isset($_POST['booking_form_fields'])) {
            $form_fields = array();
            
            foreach ($_POST['booking_form_fields'] as $field) {
                $form_fields[] = array(
                    'type' => sanitize_text_field($field['type']),
                    'name' => sanitize_text_field($field['name']),
                    'label' => sanitize_text_field($field['label']),
                    'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
                    'required' => isset($field['required']) ? 1 : 0
                );
            }
            
            update_post_meta($post_id, '_booking_form_fields', $form_fields);
        } else {
            delete_post_meta($post_id, '_booking_form_fields');
        }
    }
}