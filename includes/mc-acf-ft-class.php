<?php
/*
* Register Class
*/
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );


if( !class_exists('MC_Acf_Fexlible_Template') ) {
    class MC_Acf_Fexlible_Template {
        
        public function __construct() {

            add_filter('acf/get_field_label', array($this, 'mc_ft_add_filter_label'), 10, 2);
            // ajax action for loading values
            add_action('wp_ajax_mc_acf_ft_save_template', array($this, 'mc_acf_ft_save_template'));
            add_action('wp_ajax_mc_acf_import_template', array($this, 'mc_acf_import_template'));
            //add_action('acf/save_post',array($this, 'test'));

            // enqueue js extension for acf
            // do this when ACF in enqueuing scripts
            add_action('acf/input/admin_enqueue_scripts', array($this, 'enqueue_script'));

        }

        public function test($post) {
            error_log(print_r($_POST, true));
        }
        /*
        *  mc_ft_add_filter_label
        *  hooked on acf_get_field_label
        *  Display the select box for import templates
        *  @param   $field (array)
        *  @return  $label (string)
        */
        public function mc_ft_add_filter_label($label, $field){
            global $post;

            if( isset($field['type']) 
                && $field['type'] == 'flexible_content' 
                && $post->post_type != 'acf-field-group'
                && isset($field['key'])
                && !empty($field['key'])) {
                $label .= $this->mc_ft_get_templates($field['key']);
            }

            return $label;
        }

        /*
        * mc_ft_add_filter_label
        * get acf_template CPT list for the current field group 
        * $field_key : the current field key group
        */
        public function mc_ft_get_templates($field_key){

            $args_templates = array(
                'post_type' => 'acf_template',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'meta_key'     => '_flex_layout_parent',
                'meta_value'   => $field_key,
                'meta_compare' => '='
            );

            $acf_templates = get_posts( $args_templates );
            echo '<div class="acf-mc-import-wrap postbox closed">';

            echo '<button type="button" class="handlediv mc-acf-ft-open-import" aria-expanded="false">
            <span class="screen-reader-text">'.__('Import template', 'mc-acf-ft-template').'</span>
            <span class="toggle-indicator" aria-hidden="true"></span>
            </button>';
            echo '<h3 class="">'.__('Import template', 'mc-acf-ft-template').'</h3>';
            
            echo '<div class="acf-mc-import-content inside">';
            if( $acf_templates ) {
                
                echo '<div class="acf-mc-ft-import-success acf-success-message" style="display:none;"></div>';
                echo '<div class="acf-mc-ft-import-error acf-error-message" style="display:none;"></div>';
                echo '<label for="acf_templates">'. __('Import template', 'mc-acf-ft-template').'</label>';
                echo '<select name="acf_templates" class="acf-templates-select">';
                echo '<option value="0">--</option>';

                foreach( $acf_templates as $acf_template ) {

                    echo '<option value="'.$acf_template->ID.'">'.$acf_template->post_title.'</option>';
                }

                echo '</select>';
            } else {
                echo '<p>'. __('No template found', 'mc-acf-ft-template').'</p>';
            }
            echo '</div>'; // content
            echo '</div>'; // wrap
        }

        /*
        * mc_acf_ft_save_template
        * Ajax function to import flexible template
        */
        public function mc_acf_ft_save_template() {

            // Default json return if ok
            $json = array(
                'success' => true,
                'valid'     => 1,
                'errors'    => 0,
                'message'   => __('Ok!', 'mc-acf-ft-template')
            );

            // default error array
            $error = array(
                'code' => '',
                'message' => '',
            );

            // we can use the acf nonce to verify the request
            if (!wp_verify_nonce($_POST['nonce'], 'acf_nonce')) {
                $error = -1;
                wp_send_json_error($error);
                exit;
            }

            // make sure we have ACF data
            if( !isset( $_POST['acf'] ) || isset( $_POST['acf'] ) && empty($_POST['acf'])) {
                $error['code'] = 0;
                $error['message'] =  __('You can\'t save empty template.', 'mc-acf-ft-template');
                wp_send_json_error($error);
                exit;
            }

            // make sure our template name is set
            if ( !isset($_POST['mc_acf_template_name']) ) {
                $error['code'] = -1;
                $error['message'] =  __('No save template index', 'mc-acf-ft-template');
                wp_send_json_error($error);
                exit;
            }

            // make sure our template name is set and not empty
            if( isset( $_POST['mc_acf_template_name'] ) && empty( $_POST['mc_acf_template_name'] ) ) {
                $error['code'] = -2;
                $error['message'] =  __('Please fill the template name.', 'mc-acf-ft-template');
                wp_send_json_error($error);
                exit;
            }

            // we have our custom field and acf post data
            if( isset( $_POST['mc_acf_parent_key'] ) && !empty( $_POST['mc_acf_parent_key'] ) ) {

                //error_log(print_r($_POST, true));

                $template_name = $_POST['mc_acf_template_name'];
                $parent_key = $_POST['mc_acf_parent_key'];
                
                $fields = $_POST['acf'][$parent_key];

                if( !empty($fields) && is_array($fields) ) { 
                    $flex_layouts = maybe_serialize( $fields );

                    // if we have some flexibles fields, save them in a CPT
                    $post_arr = array(
                        'post_title'   =>  $template_name,
                        'post_content' => '',
                        'post_status'  => 'publish',
                        'post_author'  => get_current_user_id(),
                        'post_type' => 'acf_template',
                        'meta_input'   => array(
                            '_flex_layout_parent' => $parent_key,
                            '_flex_layout_data' => $flex_layouts,

                        ),
                    );

                    $post_id = wp_insert_post($post_arr);

                    if( is_wp_error($post_id) ) { 
                        $error['code'] = -3;
                        $error['message'] =  __('Error creating post.', 'mc-acf-ft-template');
                        wp_send_json_error($error);
                        exit;
                    } else {
                        $json['message'] = __('Template saved, remember to save the post.', 'mc-acf-ft-template');
                    }

                } else {
                    $error['code'] = -4;
                    $error['message'] =  __('No parent key found.', 'mc-acf-ft-template');
                    wp_send_json_error($error);
                    exit;
                }

            }

            wp_send_json_success($json);

            exit;

        } // end public function mc_acf_ft_save_template

        /*
        * Import template
        */
        public function mc_acf_import_template() {

            global $post;

            // Default json return if ok
            $json = array(
                'success' => true,
                'valid'     => 1,
                'errors'    => 0,
                'message'   => __('Ok!', 'mc-acf-ft-template')
            );

            // default error array
            $error = array(
                'code' => '',
                'message' => '',
            );

            // we can use the acf nonce to verify the request
            if (!wp_verify_nonce($_POST['nonce'], 'acf_nonce')) {
                $error = -1;
                wp_send_json_error($error);
                exit;
            }

            // make sure our template name is set
            if ( !isset($_POST['acf_templates']) ) {
                $error['code'] = -1;
                $error['message'] =  __('No template selected.', 'mc-acf-ft-template');
                wp_send_json_error($error);
                exit;
            }

            // make sure our template name is set and not empty
            if( isset( $_POST['acf_templates'] ) && empty( $_POST['acf_templates'] ) ) {
                $error['code'] = -2;
                $error['message'] =  __('Please select a template.', 'mc-acf-ft-template');
                wp_send_json_error($error);
                exit;
            }

            if( isset($_POST['acf_templates']) && !empty($_POST['acf_templates'] ) ) {

                // the CPT (template) selected
                $flex_layout_id = $_POST['acf_templates'];
                // layouts count in page (.values > .layout)
                // we can't get this values using get_row on post_id because maybe user has added layouts BEFORE import
                $initial_count = $_POST['number_layout'];

                // the flexible layout meta data saved in the template
                $layouts_serialized = get_post_meta($flex_layout_id, '_flex_layout_data', true );

                $layouts = maybe_unserialize($layouts_serialized);

                if( is_array($layouts) && !empty($layouts) ) {

                    // the original ACF field group from which template was saved
                    $layout_parent_key = get_post_meta($flex_layout_id, '_flex_layout_parent', true );
                    // get the original field object 
                    // needed in the render_layout function
                    $parent_object = get_field_object($layout_parent_key, true, true);

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
                    foreach($layouts as $i => $value ) {
                        $parent_object['value'][] = $value;
                        // add the name according to the group parent key, used in layout input hidden
                        // used by ACF in render_layout function
                        $parent_object['name'] = 'acf['.$layout_parent_key.']';
                    }

                    // here we are creating a "fake" layouts array
                    // based on the group parent object (flexible)
                    $fake_layouts = array();

                    // loop on group parent layouts and push the name, used later by ACF
                    foreach( $parent_object['layouts'] as $k => $layout ) {
                        $fake_layouts[ $layout['name'] ] = $layout;
                    }

                    $item = array();
                    // loop on parent object values, now contain our templates values

                    foreach( $parent_object['value'] as $i => $value ):
                        ob_start();
                        // render LAYOUT
                        $acf_flex_class->render_layout( $parent_object, $fake_layouts[ $value['acf_fc_layout'] ], $initial_count, $value );
                        $item[] = ob_get_clean();
                        // increment counter
                        $initial_count++;
                    endforeach;
                    
                    $json['layouts'] = $item;
                    $json['message'] = __('Template imported, remember to save the post.', 'mc-acf-ft-template');
                    if(is_array($item)) {
                        wp_send_json_success($json);
                    }
                } else {
                    $error['code'] = -3;
                    $error['message'] =  __('You can\'t import empty template.', 'mc-acf-ft-template');
                    wp_send_json_error($error);
                    exit;
                }
            }
        }

        public function enqueue_script() {

            global $post;

            // the handle should be changed to your own unique handle
            $handle = 'mc-acf-ft-template-js';
            
            // I'm using this method to set the src because
            // I don't know where this file will be located
            // you should alter this to use the correct fundtions
            // to set the src value to point to the javascript file
            $src = MC_ACF_FT . '/assets/js/mc-acf-ft-template.js';
            // make this script dependent on acf-input
            $depends = array('acf-input');

            $localize = array();
            $localize['ft_label']  = __('Save as template :', 'mc-acf-ft-template');
            //$localized['ft_save']  = __("Save this template", 'mc-acf-ft-template');

            wp_register_script($handle, $src, $depends);

            wp_enqueue_script($handle);

            wp_localize_script($handle, 'mc_acf_ft', $localize );

            // CSS
            wp_register_style('mc-acf-ft-css', MC_ACF_FT . 'assets/css/mc-acf-ft-css.css');
            wp_enqueue_style('mc-acf-ft-css');
        } // end public function enqueue_script
        
    }
    new MC_Acf_Fexlible_Template();
}
