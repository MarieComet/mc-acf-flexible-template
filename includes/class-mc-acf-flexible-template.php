<?php
/*
* Register Class
*/
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );


if( !class_exists('MC_Acf_Flexible_Template') ) {
    class MC_Acf_Flexible_Template {

        function __construct() {
            add_action( 'admin_init', array( $this, 'mc_ft_add_actions_filters' ) );
        }
        public function mc_ft_add_actions_filters() {
            // save templates data
            add_action( 'acf/save_post', array( $this, 'mc_ft_acf_update_template' ), 1 );
            // add option settings in flexible field
            add_action( 'acf/render_field_settings/type=flexible_content', array( $this, 'mc_ft_acf_field_groups_add_settings' ), 10, 1 );
            // display plugin import and export on flexible field
            add_filter( 'acf/get_field_label', array( $this, 'mc_ft_add_filter_label' ), 999, 2 );
            // ajax action for loading values
            add_action( 'wp_ajax_mc_acf_ft_save_template', array( $this, 'mc_acf_ft_save_template' ) );
            add_action( 'wp_ajax_mc_acf_import_template', array( $this, 'mc_acf_import_template' ) );

            // render our field for Templates CPT
            add_action( 'edit_form_after_title', array( $this, 'mc_acf_ft_after_title' ), 10, 1 );
            // enqueue js extension for acf
            // do this when ACF in enqueuing scripts
            add_action( 'acf/input/admin_enqueue_scripts', array( $this, 'enqueue_script' ) );
        }

        /*
        * mc_ft_acf_update_template
        * hooked on acf/save_post
        * Update ACF templates data
        * @param  $post_id INT
        * @since 1.0.2
        */
        public function mc_ft_acf_update_template( $post_id ) {

            if ( wp_is_post_revision( $post_id ) ) {
                return;
            }

            $post_type = get_post_type( $post_id );

            if ( $post_type !== 'acf_template' ) {
                return;
            }

            if ( empty( $_POST['acf'] ) ) {
                return;
            }

            $fields = $_POST['acf'];


            if ( ! empty( $fields ) && is_array( $fields ) ) {
                foreach ( $fields as $key => $field ) {

                    if ( ! is_serialized( $field ) ) {
                       $field = maybe_serialize( $field );
                    }

                    if ( is_string( $field ) ) {
                        $field =  wp_slash( $field );
                    }

                    if ( !empty( $field ) ) {
                        update_post_meta( $post_id, '_flex_layout_data', $field );
                    }
                }
            }
            // unset ACF post data because we don't want to add this to post_meta
            unset( $_POST['acf'] );
        }

        /*
        * mc_ft_acf_field_groupos_add_settings
        * hooked on acf/render_field_settings
        * Add an option to flexible field to turn on/off import and export function by field
        * @param  $field (array)
        * @since 1.0.1
        */
        public function mc_ft_acf_field_groups_add_settings( $field ) {
            // min
            acf_render_field_setting( $field, array(
                'label'         => __('Save and load templates functionality','mc-acf-ft-template'),
                'instructions'  => __('This flexible field should display save/load functionnality ?', 'mc-acf-ft-template'),
                'type'          => 'true_false',
                'name'          => 'mc_acf_ft_true_false',
                'ui'            => 1,
                'default_value' => true,
            ) );
        }
        /*
        *  mc_ft_add_filter_label
        *  hooked on acf_get_field_label
        *  Display the select box for import and export templates
        *  @param   $field (array)
        *  @return  $label (string)
        * @since 1.0.1
        */
        public function mc_ft_add_filter_label( $label, $field ){
            global $post, $pagenow, $typenow;

            if ( isset( $field['type'] )
                && $field['type'] == 'flexible_content'
                && isset( $field['mc_acf_ft_true_false'] ) && $field['mc_acf_ft_true_false']
                && ! in_array( $typenow, array( 'acf-field-group', 'attachment', 'acf_template' ) )
                && isset( $field['key'] )
                && ! empty( $field['key'] ) ) {

                $label .= '<div class="acf-mc-ft-wrap">';
                ob_start();
                // Capability for export and import
                $import_cap = 'edit_others_pages';
                $import_cap = apply_filters( 'mc_ft_import_cap', $import_cap );

                if ( current_user_can( $import_cap ) ) {
                    $label .= $this->mc_ft_select_display( $field['key'] );
                }

                $save_cap = 'edit_others_pages';
                $save_cap = apply_filters( 'mc_ft_save_cap', $save_cap );

                if ( current_user_can( $save_cap ) ) {
                    $label .= $this->mc_ft_save_display( $field['key'] );
                }
                $label .= ob_get_clean();
                $label .= '</div>';

            }

            return $label;
        }

        /*
        * mc_ft_select_display
        * get acf_template CPT list for the current field group
        * $field_key : the current field key group
        * @since 1.0.1
        */
        public function mc_ft_select_display( $field_key ) {

            $templates_tax = get_terms( array(
                'taxonomy' => 'acf_template_tax',
                'hide_empty' => true,
                'orderby' => 'name'
            ) );

            ?>
            <button type="button" class="button button-primary mc-acf-ft-open-import mc-open">
            <?php _e( 'Load template', 'mc-acf-ft-template' ); ?>
            </button>

            <div class="acf-mc-import-content popup acf-tooltip">
                <button type="button" class="handlediv acf-mc-ft-close"><span class="dashicons dashicons-no-alt"><span class="screen-reader-text"><?php _e('Close import modal.', 'mc-acf-ft-template'); ?></span></span></button>
                <div class="acf-mc-ft-save-wrap">
                    <div class="acf-mc-ft-import-success acf-success-message" style="display:none;"></div>
                    <div class="acf-mc-ft-import-error acf-error-message" style="display:none;"></div>

                    <?php
                    $args_templates = array(
                        'post_type' => 'acf_template',
                        'posts_per_page' => -1,
                        'post_status' => 'publish',
                        'meta_query' => array(
                            array(
                                'key'     => '_flex_layout_parent',
                                'value'   => $field_key,
                                'compare' => '=',
                            ),
                        ),
                    );
                    $acf_templates = get_posts( $args_templates );

                    if ( $acf_templates ) :
                    ?>
                    <label for="acf_templates"><?php _e( 'Select a template :', 'mc-acf-ft-template' ); ?>
                        <select name="acf_templates" class="acf-templates-select mc-acf-ft-select2" style="width: 100%" data-placeholder="<?php _e('Select a template', 'mc-acf-ft-template'); ?>">
                            <option></option>
                            <?php
                            // array for our templates without terms
                            $without_terms = array();
                            // if we have terms
                            if ( ! empty( $templates_tax ) && ! is_wp_error( $templates_tax ) ) :
                                foreach ( $templates_tax as $term ) :
                                    ?>
                                    <optgroup label="<?php echo $term->name; ?>">
                                    <?php
                                    foreach ( $acf_templates as $acf_template ) :
                                        if ( has_term( '', 'acf_template_tax', $acf_template->ID ) ) :
                                            if ( has_term( $term, 'acf_template_tax', $acf_template->ID ) ) :
                                                ?>
                                                <option value="<?php echo $acf_template->ID; ?>"><?php echo $acf_template->post_title; ?></option>
                                            <?php
                                            endif;
                                        else :
                                            // store templates without this term here for display later
                                            $without_terms[] = $acf_template->ID;
                                        endif;
                                        ?>
                                    <?php endforeach; ?>
                                    </optgroup>
                                <?php
                                endforeach;
                            // if we don't have terms at all
                            else :
                                foreach ( $acf_templates as $acf_template ) :
                                    $without_terms[] = $acf_template->ID;
                                endforeach;
                            endif;

                            // if we have templates without terms
                            if ( is_array( $without_terms ) && ! empty( $without_terms ) ) :
                                $without_terms = array_unique( $without_terms );
                            ?>
                                    <optgroup label="<?php _e( 'Uncategorised', 'mc-acf-ft-template' ); ?>">
                                    <?php foreach ( $without_terms as $template ) : ?>
                                        <option value="<?php echo $template; ?>"><?php echo get_the_title( $template ); ?></option>
                                    <?php endforeach; ?>
                                    </optgroup>
                            <?php endif; ?>
                        </select>
                    </label>
                    <label class="acf-mc-ft-replace-content"><input name="replace_content" value="" type="checkbox"><?php _e( 'Replace existing content', 'mc-acf-ft-template' ); ?></label>
                    <button class="acf-mc-ft-import acf-button button button-secondary"><?php _e( 'Load', 'mc-acf-ft-template' ); ?></button>
                    <?php else: ?>
                        <p><?php _e( 'No template found for this flexible', 'mc-acf-ft-template' ); ?></p>
                    <?php endif; ?>

                </div>
            </div>
            <?php
        }

        /*
        * mc_ft_save_display
        * get acf_template CPT list for the current field group
        * $field_key : the current field key group
        * @since 1.0.1
        */
        public function mc_ft_save_display( $field_key ){
            $templates_tax = get_terms( array(
                'taxonomy' => 'acf_template_tax',
                'hide_empty' => false,
                'orderby' => 'name'
            ) );

            ?>
            <button type="button" class="button button-primary mc-acf-ft-open-save mc-open">
            <?php _e( 'Save template', 'mc-acf-ft-template' ); ?>
            </button>

            <div class="acf-mc-save-content popup acf-tooltip">
            <button type="button" class="handlediv acf-mc-ft-close"><span class="dashicons dashicons-no-alt"><span class="screen-reader-text"><?php _e( 'Close save modal.', 'mc-acf-ft-template' ); ?></span></span></button>
                <div class="acf-mc-ft-save-wrap">
                    <div class="acf-mc-ft-save-success acf-success-message" style="display:none;"></div>
                    <div class="acf-mc-ft-save-error acf-error-message" style="display:none;"></div>
                    <?php
                    if ( ! empty( $templates_tax ) && ! is_wp_error( $templates_tax ) ) :
                        ?>
                    <label for="acf_templates_terms"><?php _e('Select one or more categories :', 'mc-acf-ft-template'); ?>
                        <select name="acf_templates_terms" class="acf-templates-terms-select mc-acf-ft-select2" style="width: 100%" data-placeholder="<?php _e( 'Select', 'mc-acf-ft-template' ); ?>" multiple="multiple">
                        <option></option>
                        <?php foreach ( $templates_tax as $term ) : ?>
                            <option value="<?php echo $term->term_id; ?>"><?php echo $term->name; ?></option>
                        <?php endforeach; ?>
                        </select>
                    </label>
                    <?php endif; ?>
                    <label for="mc_acf_template_name"><?php _e( 'Name the template :', 'mc-acf-ft-template' ); ?>
                    <span class="acf-required">*</span>
                    <input type="text" class="acf-mc-ft-template-name" value="" name="mc_acf_template_name">
                    </label>
                    <button class="acf-mc-ft-save acf-button button button-secondary"><?php _e( 'Save', 'mc-acf-ft-template' ); ?></button>
                </div>
            </div>
            <?php
        }

        /*
        * mc_acf_ft_save_template
        * Ajax function to save flexible template
        * @since 1.0.1
        */
        public function mc_acf_ft_save_template() {

            // Default json return if ok
            $json = array(
                'success' => true,
                'valid'     => 1,
                'errors'    => 0,
                'message'   => __( 'Ok!', 'mc-acf-ft-template' )
            );

            // default error array
            $error = array(
                'code' => '',
                'message' => '',
            );

            // we can use the acf nonce to verify the request
            if ( ! wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
                $error = -1;
                wp_send_json_error( $error );
                exit;
            }

            // make sure we have ACF data
            if ( ! isset( $_POST['acf'] ) || isset( $_POST['acf'] ) && empty( $_POST['acf'] ) ) {
                $error['code'] = 0;
                $error['message'] =  __( 'You can\'t save empty template.', 'mc-acf-ft-template' );
                wp_send_json_error( $error );
                exit;
            }

            // make sure our template name is set
            if ( ! isset( $_POST['mc_acf_template_name'] ) ) {
                $error['code'] = -1;
                $error['message'] =  __( 'Template name input not found.', 'mc-acf-ft-template' );
                wp_send_json_error( $error );
                exit;
            }

            // make sure our template name is set and not empty
            if ( isset( $_POST['mc_acf_template_name'] ) && empty( $_POST['mc_acf_template_name'] ) ) {
                $error['code'] = -2;
                $error['message'] =  __( 'Please fill the template name.', 'mc-acf-ft-template' );
                wp_send_json_error( $error );
                exit;
            }

            // we have our custom field and acf post data
            if ( isset( $_POST['mc_acf_parent_key'] ) && ! empty( $_POST['mc_acf_parent_key'] ) ) {

                $template_name = $_POST['mc_acf_template_name'];

                // check if we have an existing template with that name
                $maybe_exist_template = get_page_by_title( $template_name, OBJECT, 'acf_template');

                if ( $maybe_exist_template ) {
                    $error['code'] = 0;
                    $error['message'] =  __( 'A template already exists with that name. Please enter another name.', 'mc-acf-ft-template' );
                    wp_send_json_error( $error );
                    exit;
                }

                $parent_key = $_POST['mc_acf_parent_key'];

                $fields = $_POST['acf'][$parent_key];

                // look for clone parent field in $_POST['acf'] data if $_POST['acf'][$parent_key] fails
                if ( ! $fields && ! empty( $_POST['acf'] ) && is_array( $_POST['acf'] ) ) {
                    foreach ( $_POST['acf'] as $field ) {
                        if ( ! is_array( $field ) ) {
                            continue;
                        }

                        foreach ( $field as $key => $value ) {
                            $length = strlen( $parent_key );

                            if ( substr( $key, -$length ) === $parent_key ) {
                                $fields = $value;
                            }
                        }
                    }
                }

                if ( ! empty( $fields ) && is_array( $fields ) ) {

                    if ( ! is_serialized( $fields ) ) {
                        $fields = maybe_serialize( $fields );
                    }

                    // if we have some flexibles fields, save them in a CPT
                    $post_arr = array(
                        'post_title'   =>  $template_name,
                        'post_content' => '',
                        'post_status'  => 'publish',
                        'post_author'  => get_current_user_id(),
                        'post_type'    => 'acf_template',
                    );

                    $post_id = wp_insert_post( $post_arr );

                    if ( is_wp_error( $post_id ) ) {
                        $error['code'] = -3;
                        $error['message'] =  __( 'Error creating post.', 'mc-acf-ft-template' );
                        wp_send_json_error( $error );
                        exit;
                    } else {

                        if ( is_string( $fields ) ) {
                            $fields =  wp_slash( $fields );
                        }

                        if ( ! add_post_meta( $post_id, '_flex_layout_parent', $parent_key, true ) ) {
                           update_post_meta( $post_id, '_flex_layout_parent', $parent_key );
                        }

                        if ( ! add_post_meta( $post_id, '_flex_layout_data', $fields, true ) ) {
                           update_post_meta( $post_id, '_flex_layout_data', $fields );
                        }

                        if ( isset( $_POST['mc_acf_template_terms'] ) && ! empty( $_POST['mc_acf_template_terms'] ) ) {
                            $terms_selected = $_POST['mc_acf_template_terms'];
                            if( is_array( $terms_selected ) && ! empty( $terms_selected ) ) {
                                wp_set_post_terms( $post_id, $terms_selected, 'acf_template_tax', true );
                            }
                        }

                        $json['message'] = __( 'Template saved, remember to save the post.', 'mc-acf-ft-template' );
                    }

                } else {
                    $error['code'] = -4;
                    $error['message'] =  __( 'No layouts for this flexible field.', 'mc-acf-ft-template' );
                    wp_send_json_error( $error );
                    exit;
                }

            }

            wp_send_json_success( $json );

            exit;

        } // end public function mc_acf_ft_save_template

        /*
        * Import template
        * Ajac function to import flexible template
        * @since 1.0.1
        */
        public function mc_acf_import_template() {

            global $post;

            // Default json return if ok
            $json = array(
                'success' => true,
                'valid'     => 1,
                'errors'    => 0,
                'message'   => __( 'Ok!', 'mc-acf-ft-template' ),
            );

            // default error array
            $error = array(
                'code' => '',
                'message' => '',
            );

            // we can use the acf nonce to verify the request
            if ( ! wp_verify_nonce( $_POST['nonce'], 'acf_nonce' ) ) {
                $error = -1;
                wp_send_json_error( $error );
                exit;
            }

            // make sure our template name is set
            if ( ! isset( $_POST['acf_templates'] ) ) {
                $error['code'] = -1;
                $error['message'] =  __( 'Import template select not found.', 'mc-acf-ft-template' );
                wp_send_json_error( $error );
                exit;
            }

            // make sure our template name is set and not empty
            if ( isset( $_POST['acf_templates'] ) && empty( $_POST['acf_templates'] ) ) {
                $error['code'] = -2;
                $error['message'] =  __( 'Please select a template.', 'mc-acf-ft-template' );
                wp_send_json_error( $error );
                exit;
            }

            if ( isset( $_POST['acf_templates'] ) && ! empty( $_POST['acf_templates'] ) ) {

                // the CPT (template) selected
                $flex_layout_id = $_POST['acf_templates'];
                // layouts count in page (.values > .layout)
                // we can't get this values using get_row on post_id because maybe user has added layouts BEFORE import
                $initial_count = $_POST['number_layout'];

                // the flexible layout meta data saved in the template
                $layouts_serialized = get_post_meta( $flex_layout_id, '_flex_layout_data', true );

                $layouts = maybe_unserialize( $layouts_serialized );

                if ( is_array( $layouts ) && ! empty( $layouts ) ) {

                    // the original ACF field group from which template was saved
                    $layout_parent_key = get_post_meta( $flex_layout_id, '_flex_layout_parent', true );

                    // get the original field object
                    // needed in the render_layout function
                    $parent_object = get_field_object( $layout_parent_key, true, true );

                    // acf flexible main class
                    if ( class_exists('acf_field_flexible_content') ) {
                        $acf_flex_class = new acf_field_flexible_content();

                        /*
                        * Some loops, here's the point :
                        * When saving template, I add to post_meta ONLY the serialized values from the flexible content
                        * (which meen only layouts, and not all the flexible parent group) for the current post.
                        * I could save ALL the parent group object and simply output it here and avoid some loops
                        * But flexible content field can be VERY large and complex, which meen a lot of data.
                        * I prefer rebuild the field object here and put our layouts value in.
                        */
                        // we push our saved template values in group parent object
                        foreach ( $layouts as $i => $value ) {
                            $parent_object['value'][] = $value;
                            // add the name according to the group parent key, used in layout input hidden
                            // used by ACF in render_layout function
                            $parent_object['name'] = 'acf['.$layout_parent_key.']';
                        }

                        // here we are creating a "fake" layouts array
                        // based on the group parent object (flexible)
                        $fake_layouts = array();

                        // loop on group parent layouts and push the name, used later by ACF
                        foreach ( $parent_object['layouts'] as $k => $layout ) {
                            $fake_layouts[ $layout['name'] ] = $layout;
                        }

                        $item = array();
                        // loop on parent object values, now contain our templates values

                        foreach ( $parent_object['value'] as $i => $value ) {
                            // check if layout exist in parent group now
                            if ( array_key_exists( $value['acf_fc_layout'], $fake_layouts ) ) {
                                // unslash values
                                $value =  wp_unslash($value);

                                ob_start();
                                // render LAYOUT
                                $acf_flex_class->render_layout( $parent_object, $fake_layouts[ $value['acf_fc_layout'] ],
                                    $initial_count, $value );
                                $item[] = ob_get_clean();
                                // increment counter
                                $initial_count++;

                            }
                        }

                        $json['layouts'] = $item;
                        $json['message'] = __( 'Template imported, remember to save the post.', 'mc-acf-ft-template' );
                        if ( is_array( $item ) ) {
                            wp_send_json_success( $json );
                        }
                    } else {
                        $error['code'] = -3;
                        $error['message'] =  __( 'ACF main class not found.', 'mc-acf-ft-template' );
                        wp_send_json_error( $error );
                        exit;
                    }
                } else {
                    $error['code'] = -4;
                    $error['message'] =  __( 'You can\'t import empty template.', 'mc-acf-ft-template' );
                    wp_send_json_error( $error );
                    exit;
                }
            }
        }

        /*
        * mc_acf_ft_after_title
        * Display our custom flexible fields in acf template CPT
        * $post : post object
        * @since 1.0.1
        */
        public function mc_acf_ft_after_title( $post ) {
            if ( $post->post_type !== 'acf_template' ) {
                return;
            }

            $flex_layout_id = $post->ID;
            // the flexible layout meta data saved in the template
            $layouts_serialized = get_post_meta( $flex_layout_id, '_flex_layout_data', true );
            $layouts = maybe_unserialize( $layouts_serialized );

            // the original ACF field group from which template was saved
            $layout_parent_key = get_post_meta( $flex_layout_id, '_flex_layout_parent', true );

            // get the original field object
            // needed in the render_layout function
            $parent_object = get_field_object( $layout_parent_key, true, true );

            if ( is_array( $layouts ) && ! empty( $layouts ) ) {
                // acf flexible main class
                if ( class_exists( 'acf_field_flexible_content' ) ) {
                    // we push our saved template values in group parent object
                    foreach ( $layouts as $i => $value ) {

                        // unslash values
                        $value =  wp_unslash($value);
                        $parent_object['value'][] = $value;
                        // add the name according to the group parent key, used in layout input hidden
                        // used by ACF in render_layout function
                        $parent_object['name'] = 'acf['.$layout_parent_key.']';
                    }
                    // render field with values
                    acf_render_fields( $flex_layout_id, array( $parent_object ), $el = 'div', $instruction = 'label' );
                }
            } else {
                // render field without value
                acf_render_fields( $flex_layout_id, array( $parent_object ), $el = 'div', $instruction = 'label' );
            }
        }

        public function enqueue_script() {

            global $post;

            if ( $post &&
                isset( $post->ID ) &&
                get_post_type( $post->ID ) === 'acf-field-group') {
                return;
            }

            // the handle should be changed to your own unique handle
            $handle = 'mc-acf-ft-template-js';

            $src = MC_ACF_FT . '/assets/js/mc-acf-ft-template.js';
            // make this script dependent on acf-input
            $depends = array( 'acf-input' );

            $localize = array();
            $localize['ft_label']  = __( 'Save as template :', 'mc-acf-ft-template' );
            //$localized['ft_save']  = __( "Save this template", 'mc-acf-ft-template' );

            wp_register_script( $handle, $src, $depends );

            wp_enqueue_script( $handle );

            wp_localize_script( $handle, 'mc_acf_ft', $localize );

            // CSS
            wp_register_style( 'mc-acf-ft-css', MC_ACF_FT . 'assets/css/mc-acf-ft-css.css' );
            wp_enqueue_style( 'mc-acf-ft-css' );
        } // end public function enqueue_script

    }

    global $mc_acf_ft;

    if ( ! isset( $mc_acf_ft ) ) {

        $mc_acf_ft = new MC_Acf_Flexible_Template();

    }
}
