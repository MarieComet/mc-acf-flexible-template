<?php
/*
* Register CPT 
*/
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

// Register Custom Post Type
function mc_acf_ft_register_cpt() {

    $labels = array(
        'name'                  => _x( 'ACF Templates', 'Post Type General Name', 'mc-acf-ft-template' ),
        'singular_name'         => _x( 'ACF Template', 'Post Type Singular Name', 'mc-acf-ft-template' ),
        'menu_name'             => __( 'ACF Templates', 'mc-acf-ft-template' ),
        'name_admin_bar'        => __( 'ACF Template', 'mc-acf-ft-template' ),
        'archives'              => __( 'ACF Template Archives', 'mc-acf-ft-template' ),
        'attributes'            => __( 'ACF Template Attributes', 'mc-acf-ft-template' ),
        'parent_item_colon'     => __( 'Parent Item:', 'mc-acf-ft-template' ),
        'all_items'             => __( 'All Items', 'mc-acf-ft-template' ),
        'add_new_item'          => __( 'Add New Item', 'mc-acf-ft-template' ),
        'add_new'               => __( 'Add New', 'mc-acf-ft-template' ),
        'new_item'              => __( 'New Item', 'mc-acf-ft-template' ),
        'edit_item'             => __( 'Edit Item', 'mc-acf-ft-template' ),
        'update_item'           => __( 'Update Item', 'mc-acf-ft-template' ),
        'view_item'             => __( 'View Item', 'mc-acf-ft-template' ),
        'view_items'            => __( 'View Items', 'mc-acf-ft-template' ),
        'search_items'          => __( 'Search Item', 'mc-acf-ft-template' ),
        'not_found'             => __( 'Not found', 'mc-acf-ft-template' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'mc-acf-ft-template' ),
        'featured_image'        => __( 'Featured Image', 'mc-acf-ft-template' ),
        'set_featured_image'    => __( 'Set featured image', 'mc-acf-ft-template' ),
        'remove_featured_image' => __( 'Remove featured image', 'mc-acf-ft-template' ),
        'use_featured_image'    => __( 'Use as featured image', 'mc-acf-ft-template' ),
        'insert_into_item'      => __( 'Insert into item', 'mc-acf-ft-template' ),
        'uploaded_to_this_item' => __( 'Uploaded to this item', 'mc-acf-ft-template' ),
        'items_list'            => __( 'Items list', 'mc-acf-ft-template' ),
        'items_list_navigation' => __( 'Items list navigation', 'mc-acf-ft-template' ),
        'filter_items_list'     => __( 'Filter items list', 'mc-acf-ft-template' ),
    );
    $args = array(
        'label'                 => __( 'ACF Template', 'mc-acf-ft-template' ),
        'description'           => __( 'ACF Template', 'mc-acf-ft-template' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'custom-fields' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 80,
        'menu_icon'             => 'dashicons-media-document',
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'map_meta_cap'        => true,
        'capability_type'       => 'post',
        'capabilities' => array(
            'create_posts' => false,
// Removes support for the "Add New" function ( use 'do_not_allow' instead of false for multisite set ups )
        ),
        'show_in_rest'          => true,
    );
    register_post_type( 'acf_template', $args );

}
add_action( 'init', 'mc_acf_ft_register_cpt', 0 );

// Register custom column in admin

add_filter( 'manage_acf_template_posts_columns', 'set_custom_edit_acf_template_columns' );
add_action( 'manage_acf_template_posts_custom_column' , 'custom_acf_template_column', 10, 2 );

function set_custom_edit_acf_template_columns($columns) {
    //unset( $columns['author'] );
    $columns['acf_template_group'] = __( 'Original Flexible', 'mc-acf-ft-template' );
    return $columns;
}

function custom_acf_template_column( $column, $post_id ) {
    switch ( $column ) {

        case 'acf_template_group' :
            $layout_parent_key = get_post_meta($post_id, '_flex_layout_parent', true );
            $layout_parent_obj = get_field_object($layout_parent_key);
            if ( $layout_parent_obj )
                echo $layout_parent_obj['label'];
            else
                _e( 'Unable to get parent flexible', 'mc-acf-ft-template' );
            break;
    }
}