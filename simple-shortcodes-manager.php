<?php
/*
Plugin Name: Simple Shortcodes Manager
Plugin URI: http://www.beapi.fr
Description: Add a lightbox button to easily insert shortcodes inside a post. Automatically list all actives shortcodes. Allow to add documentation for each shortcodes.
Version: 1.2
Author: BeAPI
Author URI: http://www.beapi.fr
Text Domain: ssm
Domain Path: /languages/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define( 'SSM_VERSION', '1.0' );
define( 'SSM_URL', plugins_url( '', __FILE__ ) );
define( 'SSM_DIR', dirname( __FILE__ ) );

if ( is_admin() ) {
	require( SSM_DIR . '/inc/functions.inc.php');
	require( SSM_DIR . '/inc/class.admin.php');
	require( SSM_DIR . '/inc/class.admin.post.php');
}

// Activate Simple Shortcode Manager
//register_activation_hook  ( __FILE__, 'SSM_Install' );

// Init SSM
function SSM_Init() {
	global $ssm;
	
	// Load up the localization file if we're using WordPress in a different language
	// Place it in this plugin's "lang" folder and name it "ssm-[value in wp-config].mo"
	load_plugin_textdomain( 'ssm', false, basename(rtrim(dirname(__FILE__), '/')) . '/languages' );
	
	// Admin
	if ( is_admin() ) {
		$ssm['admin'] = new SSM_Admin();
		$ssm['admin-post'] = new SSM_Admin_Post();
	}
}
add_action( 'plugins_loaded', 'SSM_Init' );
?>