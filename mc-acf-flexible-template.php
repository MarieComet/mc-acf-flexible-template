<?php
/**
 * Plugin Name: MC ACF Flexible Template
 * Plugin URI: https://github.com/MarieComet/MC-ACF-Flexible-Template
 * Description: This WordPress plugin makes it possible to save the ACF flexible content fields as templates and to use them again
 * Author: Marie Comet
 * Author URI: https://www.mariecomet.fr
 * Version: 1.0.2
 * License: GPLv2
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: mc-acf-ft-template
 * Domain Path: /languages/
 *
 */
defined( 'ABSPATH' ) or die( 'Cheatin&#8217; uh?' );

define( 'MC_ACF_FT', plugin_dir_url( __FILE__ ) );
// i18n

load_plugin_textdomain( 'mc-acf-ft-template', false, basename( dirname( __FILE__ ) ) . '/languages/' );

/* Register activation hook. */
register_activation_hook( __FILE__, 'mc_acf_ft_activation_hook' );

/**
 * Runs only when the plugin is activated.
 * @since 1.0.1
 */
function mc_acf_ft_activation_hook() {
    /* Create transient data */
    set_transient( 'mc-acf-admin-notice', true, 5 );
    set_transient( 'mc-acf-version-admin-notice', true, 5 );
}

add_action( 'admin_notices', 'mc_acf_ft_missing_notice' );

/**
 * Admin Notice on Activation.
 * @since 1.0.1
 */
function mc_acf_ft_missing_notice(){

    $outdated_version = true;

    if ( defined( 'ACF_VERSION' ) && version_compare( ACF_VERSION, '5.7.0' ) >= 0 ) {
        $outdated_version = false;
    }
 
    /* Check transient, if available display notice */
    if ( get_transient( 'mc-acf-admin-notice' ) && ! class_exists('acf') ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'MC ACF Flexible Template plugin needs "Advanced Custom Fields Pro" to run. Please download and activate it', 'mc-acf-ft-template' ); ?></p>
        </div>
        <?php
        /* Delete transient, only display this notice once. */
        delete_transient( 'mc-acf-admin-notice' );
    }

    if ( get_transient( 'mc-acf-version-admin-notice' ) && class_exists('acf') && $outdated_version ) {

        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'MC ACF Flexible Template plugin requires at least "Advanced Custom Fields Pro 5.7" to run. Please update ACF.', 'mc-acf-ft-template' ); ?></p>
        </div>
        <?php
        /* Delete transient, only display this notice once. */
        delete_transient( 'mc-acf-version-admin-notice' );
    }
}
add_action( 'plugins_loaded', 'mc_acf_ft_load' );
function mc_acf_ft_load() {
    if ( class_exists('acf') ) {
        include_once('includes/mc-acf-ft-register-cpt.php');
        include_once('includes/class-mc-acf-flexible-template.php');
    }
}