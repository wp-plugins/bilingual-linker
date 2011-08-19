<?php
/*
Plugin Name: Bilingual Linker
Plugin URI: http://wordpress.org/extend/plugins/translation-linker/
Description: Allows for the storage and retrieve of custom links for translation of post/pages
Version: 1.1
Author: Yannick Lefebvre
Author URI: http://yannickcorner.nayanna.biz/
Network: true
*/

if (is_file(trailingslashit(ABSPATH.PLUGINDIR).'bilingual-linker.php')) {
	define('BL_FILE', trailingslashit(ABSPATH.PLUGINDIR).'bilingual-linker.php');
}
else if (is_file(trailingslashit(ABSPATH.PLUGINDIR).'bilingual-linker/bilingual-linker.php')) {
	define('BL_FILE', trailingslashit(ABSPATH.PLUGINDIR).'bilingual-linker/bilingual-linker.php');
}

require_once(ABSPATH . '/wp-admin/includes/template.php');

function bilingual_linker_install() {
	global $wpdb;

    $charset_collate = '';
	if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
		if (!empty($wpdb->charset)) {
			$charset_collate .= " DEFAULT CHARACTER SET $wpdb->charset";
		}
		if (!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}
	
	$charset_collate = '';
	if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
		if (!empty($wpdb->charset)) {
			$charset_collate .= " DEFAULT CHARACTER SET $wpdb->charset";
		}
		if (!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}
	
	$wpdb->posts_extrainfo = $wpdb->get_blog_prefix() .'posts_extrainfo';

	$result = $wpdb->query("
			CREATE TABLE IF NOT EXISTS `$wpdb->posts_extrainfo` (
			`post_id` bigint(20) NOT NULL DEFAULT '0',
			`post_otherlang_url` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
			PRIMARY KEY (`post_id`)
			) $charset_collate");


}     

if ( ! class_exists( 'BL_Admin' ) ) {

	class BL_Admin {

		function add_config_page() {
			global $wpdb;
			if ( function_exists('add_submenu_page') ) {
				add_options_page('Bilingual Linker for Wordpress', 'Bilingual Linker', 9, basename(__FILE__), array('BL_Admin','config_page'));
				add_filter( 'plugin_action_links', array( 'BL_Admin', 'filter_plugin_actions'), 10, 2 );
			}
			
			if ( function_exists( 'get_post_types' ) )
			{
				$post_types = get_post_types( array(), 'objects' );
				foreach ( $post_types as $post_type )
				{
					if ( $post_type->show_ui )
					{
						add_meta_box ('bilinguallinker_meta_box', __('Bilingual Linker - Additional Post / Page Parameters', 'bilingual-linker'), 'bl_postpage_edit_extra', $post_type->name, 'normal', 'high');
					}
				}
			} 
			else
			{
				add_meta_box ('bilinguallinker_meta_box', __('Bilingual Linker - Additional Post / Page Parameters', 'bilingual-linker'), 'bl_postpage_edit_extra', 'post', 'normal', 'high');

				add_meta_box ('bilinguallinker_meta_box', __('Bilingual Linker - Additional Post / Page Parameters', 'bilingual-linker'), 'bl_postpage_edit_extra', 'page', 'normal', 'high');
			}
		} // end add_BL_config_page()

		function filter_plugin_actions( $links, $file ){
			//Static so we don't call plugin_basename on every plugin row.
			static $this_plugin;
			if ( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);

			if ( $file == $this_plugin ){
				$settings_link = '<a href="options-general.php?page=bilingual-linker.php">' . __('Settings', 'bilingual-linker') . '</a>';
				array_unshift( $links, $settings_link ); // before other links
			}
			return $links;
		}
		
		function config_page() {
			global $dlextensions;
			
			// Pre-2.6 compatibility
			if ( !defined('WP_CONTENT_URL') )
				define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
			if ( !defined('WP_CONTENT_DIR') )
				define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
			if ( !defined('WP_ADMIN_URL') )
				define( 'WP_ADMIN_URL', get_option('siteurl') . '/wp-admin');

			// Guess the location
			$blpluginpath = WP_CONTENT_URL.'/plugins/'.plugin_basename(dirname(__FILE__)).'/';

			if (isset($_POST['submitgen']))
			{				
				if (!current_user_can('manage_options')) die(__('You cannot edit the Bilingual Linker for WordPress options.', 'bilingual-linker'));
				check_admin_referer('bilinguallinkerpp-config');
				
				$genoptions = get_option('BilingualLinkerGeneral');

				foreach (array() as $option_name) {
					if (isset($_POST[$option_name])) {
						$genoptions[$option_name] = $_POST[$option_name];
					}
				}

				foreach (array() as $option_name) {
					if (isset($_POST[$option_name])) {
						$genoptions[$option_name] = true;
					} else {
						$genoptions[$option_name] = false;
					}
				}

				update_option('BilingualLinkerGeneral', $genoptions);

			}

			$genoptions = get_option('BilingualLinkerGeneral');
 ?>

			<div class="wrap" id='bladmin' style='width:1000px'>
				<h2><?php _e('Bilingual Linker Configuration','bilingual-linker'); ?> </h2>
				<a href="http://yannickcorner.nayanna.biz/wordpress-plugins/bilingual-linker/" target="bilinguallinker"><img src="<?php echo $blpluginpath; ?>/icons/btn_donate_LG.gif" /></a> | <a target='blinstructions' href='http://wordpress.org/extend/plugins/bilingual-linker/installation/'><?php _e('Installation Instructions','bilingual-linker'); ?></a> | <a href='http://wordpress.org/extend/plugins/bilingual-linker/faq/' target='llfaq'><?php _e('FAQ','bilingual-linker'); ?></a> | <?php _e('Help also in tooltips','bilingual-linker'); ?> | <a href='http://yannickcorner.nayanna.biz/contact-me'><?php _e('Contact the Author','bilingual-linker'); ?></a><br /><br />

				<div><strong>Usage Instructions</strong></div>
				<div>To use Bilingual Linker, just assign the web address for the translated version of a page or post when editing it in the Bilingual Linker box, then use the OutputBilingualLink function to display a link to the translation version of the page or post.<br /><br />
				The arguments of the OutputBilingualLink are:<br />
				<strong>OutputBilingualLink($post_id, $linktext, $beforelink, $afterlink);</strong><br /><br />
				
				When using in The Loop in any template, you can use $post->ID as the first argument to pass the current post ID being processed.
				</div>

			<?php 
		} // end config_page()
	
	} // end class BL_Admin

} //endif

function OutputBilingualLink($post_id, $linktext = "Translation", $beforelink = "<div class='BilingualLink'>", $afterlink = "</div>")
{
	global $wpdb;
	
	$postextradataquery = "select * from " . $wpdb->get_blog_prefix() . "posts_extrainfo where post_id = " . $post_id;
	$extradata = $wpdb->get_row($postextradataquery, ARRAY_A);
	
	if ($extradata['post_otherlang_url'] != '')
	{
		echo $beforelink . "<a href='" . $extradata['post_otherlang_url'] . "'>" . $linktext . "</a>" . $afterlink;
	}
}

function bl_admin_scripts() {
	echo '<script type="text/javascript" src="'.get_bloginfo('wpurl').'/wp-content/plugins/link-library/tiptip/jquery.tipTip.minified.js"></script>'."\n";
	echo '<link rel="stylesheet" type="text/css" href="'.get_bloginfo('wpurl').'/wp-content/plugins/link-library/tiptip/tipTip.css">'."\n";
}

function bl_editsave_post_field($post_id) {
	global $wpdb;
	
	$extradatatable = $wpdb->get_blog_prefix() . "posts_extrainfo";
	
	$postextradataquery = "select * from " . $wpdb->get_blog_prefix() . "posts_extrainfo where post_id = " . $post_id;
	$extradata = $wpdb->get_row($postextradataquery, ARRAY_A);
	
	if ($extradata)
		$wpdb->update( $extradatatable, array( 'post_otherlang_url' => $_POST['bl_otherlang_link'] ), array( 'post_id' => $post_id ));
	else
		$wpdb->insert( $extradatatable, array( 'post_id' => $post_id, 'post_otherlang_url' => $_POST['bl_otherlang_link']));
}

function bl_delete_post_field($post_id) {
	global $wpdb;
	
	$deletequery = "delete from " . $wpdb->get_blog_prefix() . "posts_extrainfo where post_id = " . $post_id;
	$wpdb->get_results($deletequery);
}


add_action('admin_menu', array('BL_Admin','add_config_page'), 100);

add_filter('admin_head', 'bl_admin_scripts'); // the_posts gets triggered before wp_head

add_action('edit_post', 'bl_editsave_post_field');

add_action('save_post', 'bl_editsave_post_field');

add_action('delete_post', 'bl_delete_post_field');

register_activation_hook(BL_FILE, 'bilingual_linker_install');

function bl_postpage_edit_extra($post) {
	$genoptions = get_option('BilingualLinkerGeneral');
    
    global $wpdb;
    
    $postextradataquery = "select * from " . $wpdb->get_blog_prefix() . "posts_extrainfo where post_id = " . $post->ID;
	$extradata = $wpdb->get_row($postextradataquery, ARRAY_A);
    ?>
    <table>
        <tr>
            <td style='width: 200px'>
                Alternate Language Link
            </td>
            <td>
                <input type="text" id="bl_otherlang_link" name="bl_otherlang_link" size="80" value="<?php echo $extradata['post_otherlang_url']; ?>"/>
            </td>
        </tr>
    </table>
    <?php
}

?>
