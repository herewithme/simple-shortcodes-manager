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
		
		if ( isset($_GET['page']) && $_GET['page'] == 'ssm-options' ) {
			wp_enqueue_style('ssmadmin', SSM_URL . '/ressources/admin.css', false, false, 'screen');
			add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 25 );
			add_action( 'admin_print_footer_scripts', array(&$this, 'initTinyMCE'), 9999 );
		}
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
				$new_options[$key]['default'] 		= stripslashes($value['default']);
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
							<th scope='col' id='usage' class='manage-column column-title'><?php _e('Usage', 'ssm'); ?></th>
							<th scope='col' id='default' class='manage-column column-title'><?php _e('Default', 'ssm'); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th scope='col' class='manage-column column-comments' style="text-align: center;"><?php _e('Hide ?', 'ssm'); ?></th>
							<th scope='col' class='manage-column column-author'><?php _e('Name', 'ssm'); ?></th>
							<th scope='col' class='manage-column column-title'><?php _e('Description', 'ssm'); ?></th>
							<th scope='col' class='manage-column column-title'><?php _e('Usage', 'ssm'); ?></th>
							<th scope='col' class='manage-column column-title'><?php _e('Default', 'ssm'); ?></th>
						</tr>
					</tfoot>
					
					<tbody id="the-list">
						<?php foreach ( $_shortcode_tags as $shortcode_key => $shortcode_value ) :
							// Default values
							if ( !isset($ssm_fields[$shortcode_key]) )
								$ssm_fields[$shortcode_key] = array('hide' => '', 'description' => '', 'usage' => '', 'default' => '');
							
							$class = ( empty($class) ) ? 'alternate' : '';
							?>
							<tr class='<?php echo $class; ?>' valign="top"> 
								<td><input type="checkbox" name="ssm[<?php echo $shortcode_key; ?>][hide]" <?php checked( (($ssm_fields[$shortcode_key]['hide'] == 1) ? 1 : 0), 1 ); ?> value="1" /></td>
								<td>[<?php echo esc_html($shortcode_key); ?>]</td>
								<td><textarea class="widefat lmceEditor" rows="5" cols="15" name="ssm[<?php echo $shortcode_key; ?>][description]"><?php echo stripslashes( $ssm_fields[$shortcode_key]['description'] ); ?></textarea></td>
								<td><textarea class="widefat lmceEditor" rows="5" cols="15" name="ssm[<?php echo $shortcode_key; ?>][usage]"><?php echo stripslashes( $ssm_fields[$shortcode_key]['usage'] ); ?></textarea></td>
								<td><input class="widefat" style="width: 90%" name="ssm[<?php echo $shortcode_key; ?>][default]" value="<?php echo esc_attr( stripslashes( $ssm_fields[$shortcode_key]['default'] ) ); ?>" /></td>
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

	function initTinyMCE() {
		global $concatenate_scripts, $compress_scripts, $tinymce_version;
		
		if ( ! user_can_richedit() )
			return;
		
		$baseurl = includes_url('js/tinymce');
		
		$mce_locale = ( '' == get_locale() ) ? 'en' : strtolower( substr(get_locale(), 0, 2) ); // only ISO 639-1
		
		/*
		The following filter allows localization scripts to change the languages displayed in the spellchecker's drop-down menu.
		By default it uses Google's spellchecker API, but can be configured to use PSpell/ASpell if installed on the server.
		The + sign marks the default language. More information:
		http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/spellchecker
		*/
		$mce_spellchecker_languages = apply_filters('mce_spellchecker_languages', '+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv');
		$plugins = array( 'safari', 'inlinepopups', 'spellchecker', 'paste', 'wordpress', 'media', 'fullscreen', 'wpeditimage', 'wpgallery', 'tabfocus' );
		$plugins = implode($plugins, ',');
		
		$mce_buttons = apply_filters('_mce_buttons', array('bold', 'italic', 'strikethrough', '|', 'bullist', 'numlist', 'blockquote', '|', 'link', 'unlink', 'code' ));
		$mce_buttons = implode($mce_buttons, ',');
		
		$mce_buttons_2 = array();
		if ( is_multisite() )
			unset( $mce_buttons_2[ array_search( 'media', $mce_buttons_2 ) ] );
		$mce_buttons_2 = apply_filters('_mce_buttons_2', $mce_buttons_2);
		$mce_buttons_2 = implode($mce_buttons_2, ',');
		
		$mce_buttons_3 = apply_filters('_mce_buttons_3', array());
		$mce_buttons_3 = implode($mce_buttons_3, ',');
		
		$mce_buttons_4 = apply_filters('_mce_buttons_4', array());
		$mce_buttons_4 = implode($mce_buttons_4, ',');
		
		$no_captions = (bool) apply_filters( 'disable_captions', '' );
		
		// TinyMCE init settings
		$initArray = array (
			'mode' => 'specific_textareas',
			'editor_selector' => 'lmceEditor',
			'width' => '100%',
			'theme' => 'advanced',
			'skin' => 'wp_theme',
			'theme_advanced_buttons1' => $mce_buttons,
			'theme_advanced_buttons2' => $mce_buttons_2,
			'theme_advanced_buttons3' => $mce_buttons_3,
			'theme_advanced_buttons4' => $mce_buttons_4,
			'language' => $mce_locale,
			'spellchecker_languages' => $mce_spellchecker_languages,
			'theme_advanced_toolbar_location' => 'top',
			'theme_advanced_toolbar_align' => 'left',
			'theme_advanced_resizing' => true,
			'theme_advanced_resize_horizontal' => false,
			'dialog_type' => 'modal',
			'relative_urls' => false,
			'remove_script_host' => false,
			'convert_urls' => false,
			'apply_source_formatting' => false,
			'remove_linebreaks' => true,
			'gecko_spellcheck' => true,
			'entities' => '38,amp,60,lt,62,gt',
			'accessibility_focus' => true,
			'tabfocus_elements' => 'major-publishing-actions',
			'media_strict' => false,
			'paste_remove_styles' => true,
			'paste_remove_spans' => true,
			'paste_strip_class_attributes' => 'all',
			'wpeditimage_disable_captions' => $no_captions,
			'plugins' => $plugins
		);
		
		if ( empty($initArray['theme_advanced_buttons3']) && !empty($initArray['theme_advanced_buttons4']) ) {
			$initArray['theme_advanced_buttons3'] = $initArray['theme_advanced_buttons4'];
			$initArray['theme_advanced_buttons4'] = '';
		}
		
		if ( ! isset($concatenate_scripts) )
			script_concat_settings();
		
		$language = $initArray['language'];
		
		$compressed = false;
		
		/**
		 * Deprecated
		 *
		 * The tiny_mce_version filter is not needed since external plugins are loaded directly by TinyMCE.
		 * These plugins can be refreshed by appending query string to the URL passed to mce_external_plugins filter.
		 * If the plugin has a popup dialog, a query string can be added to the button action that opens it (in the plugin's code).
		 */
		$version = apply_filters('tiny_mce_version', '');
		$version = 'ver=' . $tinymce_version . $version;
		
		if ( 'en' != $language )
			include_once(ABSPATH . WPINC . '/js/tinymce/langs/wp-langs.php');
		
		$mce_options = '';
		foreach ( $initArray as $k => $v )
		    $mce_options .= $k . ':"' . $v . '", ';
		
		$mce_options = rtrim( trim($mce_options), '\n\r,' );
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
		tinyMCEPreInit = {
			base : "<?php echo $baseurl; ?>",
			suffix : "",
			query : "<?php echo $version; ?>",
			mceInit : {<?php echo $mce_options; ?>},
			load_ext : function(url,lang) {var sl=tinymce.ScriptLoader;sl.markDone(url+'/langs/'+lang+'.js');sl.markDone(url+'/langs/'+lang+'_dlg.js');}
		};
		/* ]]> */
		</script>
		
		<?php
		/*
			if ( $compressed )
				echo "<script type='text/javascript' src='$baseurl/wp-tinymce.php?c=1&amp;$version'></script>\n";
			else
				echo "<script type='text/javascript' src='$baseurl/tiny_mce.js?$version'></script>\n";
			
			if ( 'en' != $language && isset($lang) )
				echo "<script type='text/javascript'>\n$lang\n</script>\n";
			else
				echo "<script type='text/javascript' src='$baseurl/langs/wp-langs-en.js?$version'></script>\n";
		*/
		?>
		
		<script type="text/javascript">
		/* <![CDATA[ */
		<?php if ( $ext_plugins ) echo "$ext_plugins\n"; ?>
		<?php if ( $compressed ) { ?>
		tinyMCEPreInit.go();
		<?php } else { ?>
		(function() {var t=tinyMCEPreInit,sl=tinymce.ScriptLoader,ln=t.mceInit.language,th=t.mceInit.theme,pl=t.mceInit.plugins;sl.markDone(t.base+'/langs/'+ln+'.js');sl.markDone(t.base+'/themes/'+th+'/langs/'+ln+'.js');sl.markDone(t.base+'/themes/'+th+'/langs/'+ln+'_dlg.js');tinymce.each(pl.split(','),function(n) {if(n&&n.charAt(0)!='-') {sl.markDone(t.base+'/plugins/'+n+'/langs/'+ln+'.js');sl.markDone(t.base+'/plugins/'+n+'/langs/'+ln+'_dlg.js');}});})();
		<?php } ?>
		tinyMCE.init(tinyMCEPreInit.mceInit);
		/* ]]> */
		</script>
		<?php
	}
}
?>