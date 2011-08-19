<?php
class SSM_Admin {
	private $quantity_per_page = 5;
	
	// Error management
	private $message = '';
	private $status  = '';
	
	/**
	 * Constructor PHP4 like
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function SSM_Admin() {
		add_action( 'admin_menu', array(&$this, 'addPluginMenu') );
		add_action( 'admin_init', array(&$this, 'checkImportExport') );
	}
	
	/**
	 * Add plugin in options menu
	 */
	function addPluginMenu() {
		add_options_page( __('Simple Shortcodes Manager : Settings', 'ssm'), __('Shortcode Manager', 'ssm'), 'manage_options', 'ssm-options', array( &$this, 'displayOptions' ) );
	}
	
	/**
	 * Call the admin option template
	 * 
	 * @echo the shortcode 
	 * @author Benjamin Niess
	 */
	function displayOptions() {
		global $shortcode_tags;
		
		// Copy array shortcodes for allow manipulation
		$_shortcode_tags = $shortcode_tags;
		$_shortcode_tags_total = count($_shortcode_tags);
		
		// Sort shortcodes by name
		natksort($_shortcode_tags);
		
		if ( isset($_POST['save']) ) {
			check_admin_referer('ssm_save_settings');
			$new_options = array();
			
			// Get the old option in order to merge it (if a plugin with a shortcode is is_not_default, we'll keep it's infos)
			$old_options = get_option('ssm_options');
			if ( empty( $old_options ) || !is_array( $old_options ) )
				$old_options = array(); 
			
			// Update existing
			foreach( (array) $_POST['ssm'] as $key => $value ) {
				$new_options[$key]['description'] 	= stripslashes($value['description']);
				$new_options[$key]['usage'] 		= stripslashes($value['usage']);
				$new_options[$key]['hide'] 			= isset($value['hide']) ? 1 : 0;
			}
			
			// Merge old and new options
			$ssm_fields = array_merge( $old_options, $new_options );
			update_option( 'ssm_options', $ssm_fields );
			
			$this->message = __('Options updated!', 'ssm');
		}
		
		// Get settings, put array empty if no exist...
		$ssm_fields = get_option('ssm_options');
		if ( $ssm_fields == false ) {
			$ssm_fields = array();
		}
		
		// Pagination
		$_GET['paged'] = isset( $_GET['paged'] ) ? intval($_GET['paged']) : 0;
		if ( $_GET['paged'] < 1 )
			$_GET['paged'] = 1;
		
		// Split array shortcodes
		$_shortcode_tags = array_slice( $_shortcode_tags, ($this->quantity_per_page * ($_GET['paged'] - 1)), $this->quantity_per_page );
		
		$this->displayMessage();
		?>
		<div class="wrap" id="ssm_options">
			<h2><?php _e('Simple Shortcodes Manager', 'ssm'); ?></h2>
			
			<form method="post" action="">
				<div class="tablenav">
					<?php
					$page_links = paginate_links( array(
						'base' => add_query_arg( 'paged', '%#%' ),
						'format' => '',
						'prev_text' => __('&laquo;'),
						'next_text' => __('&raquo;'),
						'total' => ceil($_shortcode_tags_total / $this->quantity_per_page ),
						'current' => $_GET['paged']
					));
					
					if ( $page_links )
						echo "<div class='tablenav-pages'>$page_links</div>";
					?>
				</div>
	
				<table class="wp-list-table widefat fixed posts" cellspacing="0"> 
					<thead>
						<tr>
							<th scope='col' id='cb' class='manage-column column-comments' style="text-align: center;"><?php _e('Hide ?', 'ssm'); ?></th>
							<th scope='col' id='title' class='manage-column column-author'><?php _e('Name', 'ssm'); ?></th>
							<th scope='col' id='description' class='manage-column column-title'><?php _e('Description', 'ssm'); ?></th>
							<th scope='col' id='usage' class='manage-column column-title'><?php _e('Usage', 'ssm'); ?></th></tfoot>
						</tr>
					</thead>
					
					<tfoot>
						<tr>
							<th scope='col' class='manage-column column-comments' style="text-align: center;"><?php _e('Hide ?', 'ssm'); ?></th>
							<th scope='col' class='manage-column column-author'><?php _e('Name', 'ssm'); ?></th>
							<th scope='col' class='manage-column column-title'><?php _e('Description', 'ssm'); ?></th>
							<th scope='col' class='manage-column column-title'><?php _e('Usage', 'ssm'); ?></th></tfoot>
						</tr>
					</tfoot>
					
					<tbody id="the-list">
						<?php foreach ( $_shortcode_tags as $shortcode_key => $shortcode_value ) :
							$class = ( empty($class) ) ? 'alternate' : '';
							?>
							<tr class='<?php echo $class; ?>' valign="top"> 
								<td><input type="checkbox" name="ssm[<?php echo $shortcode_key; ?>][hide]" <?php checked( (($ssm_fields[$shortcode_key]['hide'] == 1) ? 1 : 0), 1 ); ?> value="1" /></td>
								<td>[<?php echo esc_html($shortcode_key); ?>]</td>
								<td><textarea class="widefat" rows="5" cols="15" name="ssm[<?php echo $shortcode_key; ?>][description]"><?php echo stripslashes( $ssm_fields[$shortcode_key]['description'] ); ?></textarea></td>
								<td><textarea class="widefat" rows="5" cols="15" name="ssm[<?php echo $shortcode_key; ?>][usage]"><?php echo stripslashes( $ssm_fields[$shortcode_key]['usage'] ); ?></textarea></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				
				<p class="submit">
					<?php wp_nonce_field( 'ssm_save_settings' ); ?>
					<input type="submit" name="save" class="button-primary" value="<?php _e('Save Changes', 'ssm') ?>" />
				</p>
			</form>
		</div>
		
		<div class="wrap">
			<h2><?php _e("Simple Shortcode Manager : Export/Import", 'ssm'); ?></h2>
			
			<a class="button" href="<?php echo wp_nonce_url($this->admin_url.'?action=export_config_ssm', 'export-config-ssm'); ?>"><?php _e("Export config file", 'ssm'); ?></a>
			<a class="button" href="#" id="toggle-import_form"><?php _e("Import config file", 'ssm'); ?></a>
			<script type="text/javascript">
				jQuery("#toggle-import_form").click(function(event) {
					event.preventDefault();
					jQuery('#import_form').removeClass('hide-if-js');
				});
			</script>
			<div id="import_form" class="hide-if-js">
				<form action="<?php echo $this->admin_url ; ?>" method="post" enctype="multipart/form-data">
					<p>
						<label><?php _e("Config file", 'ssm'); ?></label>
						<input type="file" name="config_file" />
					</p>
					<p class="submit">
						<?php wp_nonce_field( 'import_config_file_ssm' ); ?>
						<input class="button-primary" type="submit" name="import_config_file_ssm" value="<?php _e('I want import a config from a previous backup, this action will REPLACE current configuration', 'ssm'); ?>" />
					</p>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Check $_GET/$_POST/$_FILES for Export/Import
	 * 
	 * @return boolean
	 */
	function checkImportExport() {
		if ( isset($_GET['action']) && $_GET['action'] == 'export_config_ssm' ) {
			check_admin_referer('ssm-export-config-ssm');
			
			// No cache
			header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' ); 
			header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' ); 
			header( 'Cache-Control: no-store, no-cache, must-revalidate' ); 
			header( 'Cache-Control: post-check=0, pre-check=0', false ); 
			header( 'Pragma: no-cache' ); 
			
			// Force download dialog
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");

			// use the Content-Disposition header to supply a recommended filename and
			// force the browser to display the save dialog.
			header("Content-Disposition: attachment; filename=shortcode-manager-config-".date('U').".txt;"); 
			die('SIMPLESHORTCODEMANAGER'.base64_encode(serialize(get_option( 'ssm_options' ))));
		} elseif( isset($_POST['import_config_file_ssm']) && isset($_FILES['config_file']) ) {
			check_admin_referer( 'import_config_file_ssm' );
			
			if ( $_FILES['config_file']['error'] > 0 ) {
				$this->message = __('An error occured during the config file upload. Please fix your server configuration and retry.', 'ssm');
				$this->status  = 'error';
			} else {
				$config_file = file_get_contents( $_FILES['config_file']['tmp_name'] );
				if ( substr($config_file, 0, strlen('SIMPLESHORTCODEMANAGER')) !== 'SIMPLESHORTCODEMANAGER' ) {
					$this->message = __('This is really a config file for Simple Shortcode Manager ? Probably corrupt :(', 'ssm');
					$this->status  = 'error';
				} else {
					$config_file = unserialize(base64_decode(substr($config_file, strlen('SIMPLESHORTCODEMANAGER'))));
					if ( !is_array($config_file) ) {
						$this->message = __('This is really a config file for Simple Shortcode Manager ? Probably corrupt :(', 'ssm');
						$this->status  = 'error';
					} else {
						update_option( 'ssm_options', $config_file);
						$this->message = __('OK. Configuration is restored.', 'ssm');
						$this->status  = 'updated';
					}
				}
			}
		}
	}

	/**
	 * Display WP alert
	 *
	 */
	function displayMessage() {
		if ( $this->message != '') {
			$message = $this->message;
			$status = $this->status;
			$this->message = $this->status = ''; // Reset
		}
		
		if ( isset($message) && !empty($message) ) {
		?>
			<div id="message" class="<?php echo ($status != '') ? $status :'updated'; ?> fade">
				<p><strong><?php echo $message; ?></strong></p>
			</div>
		<?php
		}
	}
}
?>