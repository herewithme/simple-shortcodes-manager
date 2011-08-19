<?php
class SSM_Admin_Post {
	private $quantity_per_page = 10;
	
	/**
	 * Constructor PHP4 like
	 *
	 * @return void
	 * @author Benjamin Niess
	 */
	function SSM_Admin_Post() {
		add_action( 'media_buttons_context', array( &$this, 'addButton') );
		add_action( 'admin_action_' . 'iframe_pagination_ssm', array(&$this, 'pageIframe') );
	}
	
	/**
	 * Action target that adds the "Insert Shortcode" button to the post/page edit screen
	 */
	function addButton($context){
		$context .= '<a href="'.admin_url('admin.php?action=iframe_pagination_ssm&TB_iframe=1').'" onclick="return false;" class="thickbox" title="' . __("Browse in shortcode manager", 'ssm') . '"><img src="'.SSM_URL . "/images/shortcodes.png".'" alt="' . __("Insert a shortcode", 'ssm') . '" /></a>';
		return $context;
	}
	
	/**
	 * Build HTML for page used on iframe by thickbox
	 */
	function pageIframe() {
		global $body_id, $shortcode_tags;
		
		// Define this body for CSS
		$body_id = 'media-upload';
		
		// Add CSS for media style
		wp_enqueue_style( 'media' );
		//wp_enqueue_script('ssm-main', SSM_URL . '/js/ssm-main.js', ('jquery') );
		
		// Send to editor ?
		if ( isset($_GET['shortcode']) && isset($shortcode_tags[$_GET['shortcode']]) ) {
			?>
			<script type="text/javascript">
			/* <![CDATA[ */
			var win = window.dialogArguments || opener || parent || top;
			win.send_to_editor('[<?php echo addslashes($_GET['shortcode']); ?>]');
			/* ]]> */
			</script>
			<?php
			die();
		}
		
		// Render list
		wp_iframe( array($this, 'form') );
		die();
	}
	
	function form() {
		global $shortcode_tags;
		
		// Copy array shortcodes for allow manipulation
		$_shortcode_tags = $shortcode_tags;
		
		// Sort shortcodes by name
		natksort($_shortcode_tags);
		
		// Get settings, put array empty if no exist...
		$ssm_fields = get_option('ssm_options');
		if ( $ssm_fields == false ) {
			$ssm_fields = array();
		}
		
		// Remove shortcode that explicity checkbox hide !
		foreach( $_shortcode_tags as $_shortcode_tag => $val ) {
			if ( isset($ssm_fields[$_shortcode_tag]) && isset($ssm_fields[$_shortcode_tag]['hide']) && $ssm_fields[$_shortcode_tag]['hide'] == 1 )
				unset($_shortcode_tags[$_shortcode_tag]);
		}
		
		// Counter shortcodes
		$_shortcode_tags_total = count($_shortcode_tags);
		
		// Pagination
		$_GET['paged'] = isset( $_GET['paged'] ) ? intval($_GET['paged']) : 0;
		if ( $_GET['paged'] < 1 )
			$_GET['paged'] = 1;
		
		// Split array shortcodes
		$_shortcode_tags = array_slice( $_shortcode_tags, ($this->quantity_per_page * ($_GET['paged'] - 1)), $this->quantity_per_page );
		?>
		<form method="get" action="" id="filter">
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
		</form>
		
		<form method="post" action="" class="media-upload-form validate" id="library-form">
			<div id="media-items">
				<?php foreach ( $_shortcode_tags as $shortcode_key => $shortcode_value ) : ?>
					<div class='media-item'>
						<a type="submit" class="button insert_link" href="<?php echo add_query_arg( array('shortcode' => $shortcode_key) ); ?>"><?php _e('Insert', 'ssm'); ?></a>
						
						<a class='toggle describe-toggle-on' href='#'><?php _e('Show usage', 'ssm'); ?></a>
						<a class='toggle describe-toggle-off' href='#'><?php _e('Hide usage', 'ssm'); ?></a>
						
						<div class='filename new'><span class='title'>[<?php echo esc_html($shortcode_key); ?>]</span> <span class="description"><?php echo stripslashes( $ssm_fields[$shortcode_key]['description'] ); ?></span></div>
						<div class="slidetoggle describe startclosed">
							<div class="usage-box">
								<strong><?php _e('Usage :', 'ssm'); ?></strong>
								<?php 
								remove_filter('the_content', 'do_shortcode', 11); // AFTER wpautop()
								echo apply_filters('the_content', stripslashes( $ssm_fields[$shortcode_key]['usage'] ));
								add_filter('the_content', 'do_shortcode', 11); // AFTER wpautop()
								?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</form>
		
		<script type="text/javascript">
			<!--
			// Also bind toggle to the links
			jQuery('a.toggle').click(function () {
				jQuery(this).siblings('.slidetoggle').slideToggle(350, function () {
					var w = jQuery(window).height(),
						t = jQuery(this).offset().top,
						h = jQuery(this).height(),
						b;
					if (w && t && h) {
						b = t + h;
						if (b > w && (h + 48) < w) window.scrollBy(0, b - w + 13);
						else if (b > w) window.scrollTo(0, t - 36);
					}
				});
				jQuery(this).siblings('.toggle').andSelf().toggle();
				jQuery(this).siblings('a.toggle').focus();
				return false;
			});
			-->
		</script>
		
		<style type="text/css">
			.usage-box { padding:5px 10px; }
			.usage-box p { margin: 0 0 7px 0; }
			.insert_link { display:block;float:right;margin:8px 10px 0 0; }
		</style>
		<?php
	}
}
?>