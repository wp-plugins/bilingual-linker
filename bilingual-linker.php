<?php
/*
Plugin Name: Bilingual Linker
Plugin URI: http://wordpress.org/extend/plugins/translation-linker/
Description: Allows for the storage and retrieve of custom links for translation of post/pages
Version: 2.1
Author: Yannick Lefebvre
Author URI: http://yannickcorner.nayanna.biz/
*/
if ( is_file( trailingslashit( ABSPATH . PLUGINDIR ) . 'bilingual-linker.php' ) ) {
	define( 'BL_FILE', trailingslashit( ABSPATH . PLUGINDIR ) . 'bilingual-linker.php' );
} else if ( is_file( trailingslashit( ABSPATH . PLUGINDIR ) . 'bilingual-linker/bilingual-linker.php' ) ) {
	define( 'BL_FILE', trailingslashit( ABSPATH . PLUGINDIR ) . 'bilingual-linker/bilingual-linker.php' );
}

require_once( ABSPATH . '/wp-admin/includes/template.php' );

function bilingual_linker_reset_options ( $setoptions = 'return' ) {
	$new_options['numberoflanguages']   = 1;
	$new_options['language1name']       = 'French';
	$new_options['language1langcode']   = 'French';
	$new_options['language1linktext']   = 'French';
	$new_options['language1beforelink'] = '';
	$new_options['language1afterlink']  = '';
	$new_options['language1defaulturl'] = 'wordpress.org';
	$new_options['hidesingle']          = false;
	$new_options['hidefrontpage']       = false;
	$new_options['hidesearchpage']      = false;
	$new_options['hidearchivepages']    = false;
	$new_options['hidecategorypages']   = false;

	if ( $setoptions == 'return_and_set' ) {
		add_option( 'BilingualLinkerGeneral', $new_options );
	}

	return $new_options;
}

function bilingual_linker_install() {
	global $wpdb;

	$table_name = $wpdb->get_blog_prefix() . 'posts_extrainfo';

	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) == $table_name ) {
		$postextradataquery = "select * from " . $wpdb->get_blog_prefix() . "posts_extrainfo";
		$extradata          = $wpdb->get_results( $postextradataquery, ARRAY_A );

		if ( $extradata ) {
			foreach ( $extradata as $datarec ) {
				update_post_meta( $datarec['post_id'], "bilingual-linker-other-lang-url-1", $datarec['post_otherlang_url'] );
			}
		}

		$wpdb->posts_extrainfo = $wpdb->get_blog_prefix() . 'posts_extrainfo';

		$result = $wpdb->query( "DROP TABLE `$wpdb->posts_extrainfo`" );
	}

	$wpdb->query( 'update ' . $wpdb->get_blog_prefix() . 'postmeta set meta_key = "bilingual-linker-other-lang-url-1" where meta_key = "bilingual-linker-other-lang-url"' );

	if ( get_option( 'BilingualLinkerGeneral' ) === false ) {
		bilingual_linker_reset_options( 'return_and_set' );
	}

	$creation_query =
		'CREATE TABLE IF NOT EXISTS ' . $wpdb->get_blog_prefix() . 'categorymeta (
        `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `category_id` bigint(20) unsigned NOT NULL DEFAULT "0",
        `meta_key` varchar(255) DEFAULT NULL,
        `meta_value` longtext,
        PRIMARY KEY (`meta_id`),
        KEY `meta_key` (`meta_key`)
        );';

	$wpdb->query( $creation_query );

}

if ( ! class_exists( 'BL_Admin' )) {

class BL_Admin {

function __construct() {
	add_action( 'admin_menu', array( $this, 'add_config_page' ), 100 );
	add_action( 'admin_init', array( $this, 'bl_admin_init' ) );
	add_filter( 'admin_head', array( $this, 'bl_admin_scripts' ) ); // the_posts gets triggered before wp_head
	add_action( 'edit_post', array( $this, 'bl_editsave_post_field' ) );
	add_action( 'save_post', array( $this, 'bl_editsave_post_field' ) );
	add_action( 'category_edit_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
	add_action( 'category_add_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
	add_action( 'post_tag_edit_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
	add_action( 'post_tag_add_form_fields', array( $this, 'bl_category_new_fields' ), 10, 2 );
	add_action( 'edited_category', array( $this, 'bl_save_category_new_fields' ), 10, 2 );
	add_action( 'edited_post_tag', array( $this, 'bl_save_category_new_fields' ), 10, 2 );
	add_action( 'created_category', array( $this, 'bl_save_category_new_fields' ), 10, 2 );
	add_action( 'created_post_tag', array( $this, 'bl_save_category_new_fields' ), 10, 2 );
}

function bl_admin_init() {
	$this->bl_add_nav_menu_meta_box();

	add_action( 'admin_post_save_bl_options', array( $this, 'process_bl_options' ) );
}

function bl_add_nav_menu_meta_box(){
	global $pagenow;
	if ( 'nav-menus.php' !== $pagenow ){
		return;
	}

	add_meta_box(
		'bilingual_linker_item_meta_box'
		,__( 'Bilingual Linker', 'bilingual-linker-item' )
		, array( $this, 'bilingual_linker_box_render' )
		,'nav-menus'
		,'side'
		,'low'
	);
}

function bilingual_linker_box_render(){
	global $_nav_menu_placeholder, $nav_menu_selected_id;

	$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

	$gen_options = get_option( 'BilingualLinkerGeneral' );

	$final_default_url = 'http://test.com';
	?>
	<div class="customlinkdiv" id="searchboxitemdiv">
		<div class="tabs-panel-active">
			<ul class="categorychecklist">
				<li>
					<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="custom">
					<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type_label]" value="Bilingual Link">

					<input type="hidden" class="menu-item-title" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="Bilingual Linker">
					<input type="hidden" class="menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" value="<?php echo $final_default_url; ?>">
					<input type="hidden" class="menu-item-classes" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-classes]" value="bilingual-link">

					<input type="checkbox" class="menu-item-object-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" value="<?php echo $_nav_menu_placeholder; ?>" checked="true">
				</li>
			</ul>
		</div>

		<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary right" value="<?php esc_attr_e( 'Add to menu', 'bop-nav-search-box-item' ); ?>" name="add-search-menu-item" id="submit-searchboxitemdiv">
					<span class="spinner"></span>
				</span>
		</p>
	</div>
<?php
}

function bl_admin_scripts() {
	echo '<script type="text/javascript" src="' . get_bloginfo( 'wpurl' ) . '/wp-content/plugins/link-library/tiptip/jquery.tipTip.minified.js"></script>' . "\n";
	echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo( 'wpurl' ) . '/wp-content/plugins/link-library/tiptip/tipTip.css">' . "\n";
}

function bl_editsave_post_field( $post_id ) {
	if ( isset( $_POST['bl_otherlang_link_1'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-1', $_POST['bl_otherlang_link_1'] );
	}

	if ( isset( $_POST['bl_otherlang_link_2'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-2', $_POST['bl_otherlang_link_2'] );
	}

	if ( isset( $_POST['bl_otherlang_link_3'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-3', $_POST['bl_otherlang_link_3'] );
	}

	if ( isset( $_POST['bl_otherlang_link_4'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-4', $_POST['bl_otherlang_link_4'] );
	}

	if ( isset( $_POST['bl_otherlang_link_5'] ) ) {
		update_post_meta( $post_id, 'bilingual-linker-other-lang-url-5', $_POST['bl_otherlang_link_5'] );
	}

}

function add_config_page() {
	if ( function_exists( 'add_submenu_page' ) ) {
		add_options_page( 'Bilingual Linker for Wordpress', 'Bilingual Linker', 'edit_pages', basename( __FILE__ ), array(
			$this,
			'config_page'
		) );
		add_filter( 'plugin_action_links', array( $this, 'filter_plugin_actions' ), 10, 2 );
	}

	if ( function_exists( 'get_post_types' ) ) {
		$post_types = get_post_types( array(), 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( $post_type->show_ui ) {
				add_meta_box( 'bilinguallinker_meta_box', __( 'Bilingual Linker - Additional Post / Page Parameters', 'bilingual-linker' ), array(
					$this,
					'bl_postpage_edit_extra'
				), $post_type->name, 'normal', 'high' );
			}
		}
	} else {
		add_meta_box( 'bilinguallinker_meta_box', __( 'Bilingual Linker - Additional Post / Page Parameters', 'bilingual-linker' ), array(
			$this,
			'bl_postpage_edit_extra'
		), 'post', 'normal', 'high' );

		add_meta_box( 'bilinguallinker_meta_box', __( 'Bilingual Linker - Additional Post / Page Parameters', 'bilingual-linker' ), array(
			$this,
			'bl_postpage_edit_extra'
		), 'page', 'normal', 'high' );
	}
} // end add_BL_config_page()

function filter_plugin_actions( $links, $file ) {
	//Static so we don't call plugin_basename on every plugin row.
	static $this_plugin;
	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}

	if ( $file == $this_plugin ) {
		$settings_link = '<a href="options-general.php?page=bilingual-linker.php">' . __( 'Settings', 'bilingual-linker' ) . '</a>';
		array_unshift( $links, $settings_link ); // before other links
	}

	return $links;
}

function bl_postpage_edit_extra( $post ) {
	$genoptions = get_option( 'BilingualLinkerGeneral' );

	$otherlangurl    = array();
	$otherlangurl[1] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-1", true );
	$otherlangurl[2] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-2", true );
	$otherlangurl[3] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-3", true );
	$otherlangurl[4] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-4", true );
	$otherlangurl[5] = get_post_meta( $post->ID, "bilingual-linker-other-lang-url-5", true );
	?>
	<table>

		<?php for ( $langcounter = 1; $langcounter <= $genoptions['numberoflanguages']; $langcounter ++ ) { ?>
			<tr>
				<td style='width: 200px'>
					<?php
					$langname = $genoptions[ 'language' . $langcounter . 'name' ];
					if ( empty( $langname ) ) {
						$langname = 'Undefined Language';
					}
					echo $langname; ?> Link
				</td>
				<td>
					<input type="text" id="bl_otherlang_link_<?php echo $langcounter; ?>" name="bl_otherlang_link_<?php echo $langcounter; ?>" size="60" value="<?php echo $otherlangurl[ $langcounter ]; ?>" />
				</td>
			</tr>
		<?php } ?>

	</table>
<?php
}

function bl_category_new_fields( $tag ) {
	if ( is_object( $tag ) ) {
		$mode = "edit";
	} else {
		$mode = 'new';
	}

	$genoptions = get_option( 'BilingualLinkerGeneral' );

	$otherlangurl    = array();
	$otherlangurl[1] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-1', true );
	$otherlangurl[2] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-2', true );
	$otherlangurl[3] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-3', true );
	$otherlangurl[4] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-4', true );
	$otherlangurl[5] = get_metadata( $tag->taxonomy, $tag->term_id, 'bilingual-linker-other-lang-url-5', true );

	for ( $langcounter = 1; $langcounter <= $genoptions['numberoflanguages']; $langcounter ++ ) {
		?>

		<?php if ( $mode == 'edit' ) {
			echo '<tr class="form-field">';
		} elseif ( $mode == 'new' ) {
			echo '<div class="form-field">';
		} ?>

		<?php if ( $mode == 'edit' ) {
			echo '<th scope="row" valign="top">';
		} ?>
		<label for="tag-language<?php echo $langcounter; ?>link">
			<?php $langname = $genoptions[ 'language' . $langcounter . 'name' ];
			if ( empty( $langname ) ) {
				$langname = "Undefined Language";
			}
			echo $langname; ?> Link</label>
		<?php if ( $mode == 'edit' ) {
			echo '</th>';
		} ?>

		<?php if ( $mode == 'edit' ) {
			echo '<td>';
		} ?>
		<input type="text" id="bl_otherlang_link_<?php echo $langcounter; ?>" name="bl_otherlang_link_<?php echo $langcounter; ?>" size="60" value="<?php echo $otherlangurl[ $langcounter ]; ?>" />
		<p class="description">Alternate Language link <?php echo $langcounter; ?> for Bilingual Linker</p>
		<?php if ( $mode == 'edit' ) {
			echo '</td>';
		} ?>
		<?php if ( $mode == 'edit' ) {
			echo '</tr>';
		} elseif ( $mode == 'new' ) {
			echo '</div>';
		} ?>
	<?php
	}
}

function bl_save_category_new_fields( $term_id, $tt_id ) {


	if ( ! $term_id ) {
		return;
	}

	if ( isset( $_POST['bl_otherlang_link_1'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-1', $_POST['bl_otherlang_link_1'] );
	}

	if ( isset( $_POST['bl_otherlang_link_2'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-2', $_POST['bl_otherlang_link_2'] );
	}

	if ( isset( $_POST['bl_otherlang_link_3'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-3', $_POST['bl_otherlang_link_3'] );
	}

	if ( isset( $_POST['bl_otherlang_link_4'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-4', $_POST['bl_otherlang_link_4'] );
	}

	if ( isset( $_POST['bl_otherlang_link_5'] ) ) {
		update_metadata( $_POST['taxonomy'], $term_id, 'bilingual-linker-other-lang-url-5', $_POST['bl_otherlang_link_5'] );
	}

}

function config_page() {
$genoptions = get_option( 'BilingualLinkerGeneral' );
$genoptions = wp_parse_args( $genoptions, bilingual_linker_reset_options( 'return' ) );
?>

<div class="wrap" id='bladmin' style='width:1000px'>
	<h2><?php _e( 'Bilingual Linker Configuration', 'bilingual-linker' ); ?> </h2>
	<a href="http://yannickcorner.nayanna.biz/wordpress-plugins/bilingual-linker/" target="bilinguallinker"><img src="<?php echo plugins_url( '/icons/btn_donate_LG.gif', __FILE__ ); ?>" /></a> |
	<a target='blinstructions' href='http://wordpress.org/extend/plugins/bilingual-linker/installation/'><?php _e( 'Installation Instructions', 'bilingual-linker' ); ?></a> |
	<a href='http://wordpress.org/extend/plugins/bilingual-linker/faq/' target='llfaq'><?php _e( 'FAQ', 'bilingual-linker' ); ?></a> | <?php _e( 'Help also in tooltips', 'bilingual-linker' ); ?> |
	<a href='http://yannickcorner.nayanna.biz/contact-me'><?php _e( 'Contact the Author', 'bilingual-linker' ); ?></a><br /><br />

	<div><strong>Usage Instructions</strong></div>
	<div>To use Bilingual Linker, just assign the web address for the translated version of a page or post when editing it in the Bilingual Linker box, then use the the_bilingual_link function to display a link to the translation version of the page or post.<br /><br />
		The function can be used without any arguments::<br />
		<strong>the_bilingual_link();</strong><br /><br />
		Optionally, it can be called with the following arguments:<br /><br />
		<strong>
			the_bilingual_link($args_array);</strong><br />
		    Where the following array parameters can be sent: language_id, post_id, link_text, before_link, after_link, default_url, echo, href_lang_code, hide_single, hide_front_page, hide_search_page, hide_archive_pages, hide_category_pages, url_only<br /><br />


		When using in The Loop in any template, you can use $post->ID as the second argument to pass the current post ID being processed.
	</div>

	<hr />
	<form method="post" action="admin-post.php">
		<input type="hidden" name="action"
		       value="save_bl_options" />

		<!-- Adding security through hidden referrer field -->
		<?php wp_nonce_field( 'bilinguallinker' ); ?>

		<table>
			<tr>
				<td>Number of languages</td>
				<td><select name="numberoflanguages" id="numberoflanguages">
						<?php for ( $counter = 1; $counter <= 5; $counter ++ ) { ?>
							<option value="<?php echo $counter; ?>" <?php selected( $counter, $genoptions['numberoflanguages'] ); ?>><?php echo $counter; ?></option>
						<?php } ?></select></td>
			</tr>
			<tr>
				<td></td>
			</tr>
			<tr>
				<td></td>
				<td><strong>Language Name</strong></td>
				<td><strong>HREFLang</strong></td>
				<td><strong>Default Translation URL</strong></td>
				<td><strong>Before Translation Link</strong></td>
				<td><strong>Translation Link Text</strong></td>
				<td><strong>After Translation Link</strong></td>

			</tr>
			<?php for ( $langcounter = 1; $langcounter <= $genoptions['numberoflanguages']; $langcounter ++ ) { ?>
				<tr>
					<td>Language # <?php echo $langcounter; ?></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>name" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'name' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( $genoptions[ 'language' . $langcounter . 'name' ] );
						} ?>" /></td>

					<td>
						<input size=4 type="text" name="language<?php echo $langcounter; ?>langcode" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'langcode' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'langcode' ] ) ) {
							echo esc_attr( $genoptions[ 'language' . $langcounter . 'langcode' ] );
						} ?>" /></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>defaulturl" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'defaulturl' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( $genoptions[ 'language' . $langcounter . 'defaulturl' ] );
						} ?>" /></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>beforelink" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'beforelink' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( stripslashes( $genoptions[ 'language' . $langcounter . 'beforelink' ] ) );
						} ?>" /></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>linktext" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'linktext' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( stripslashes( $genoptions[ 'language' . $langcounter . 'linktext' ] ) );
						} ?>" /></td>

					<td>
						<input type="text" name="language<?php echo $langcounter; ?>afterlink" value="<?php if ( isset( $genoptions[ 'language' . $langcounter . 'afterlink' ] ) && ! empty( $genoptions[ 'language' . $langcounter . 'name' ] ) ) {
							echo esc_attr( stripslashes( $genoptions[ 'language' . $langcounter . 'afterlink' ] ) );
						} ?>" /></td>


				</tr>
				<tr><td></td></tr>
				<tr>
					<td colspan="2">Hide on single posts / pages if no translation available</td>
					<td><input type="checkbox" id="hidesingle" name="hidesingle" <?php checked( $genoptions['hidesingle'] ); ?> /></td>
				</tr>
				<tr>
					<td colspan="2">Hide on front page</td>
					<td><input type="checkbox" id="hidefrontpage" name="hidefrontpage" <?php checked( $genoptions['hidefrontpage'] ); ?> /></td>
				</tr>
				<tr>
					<td colspan="2">Hide on search page</td>
					<td><input type="checkbox" id="hidesearchpage" name="hidesearchpage" <?php checked( $genoptions['hidesearchpage'] ); ?> /></td>
				</tr>
				<tr>
					<td colspan="2">Hide on archive pages</td>
					<td><input type="checkbox" id="hidearchivepages" name="hidearchivepages" <?php checked( $genoptions['hidearchivepages'] ); ?> /></td>
				</tr>
				<tr>
					<td colspan="2">Hide on category pages</td>
					<td><input type="checkbox" id="hidecategorypages" name="hidecategorypages" <?php checked( $genoptions['hidecategorypages'] ); ?> /></td>
				</tr>
			<?php } ?>

		</table>
		<input type="submit" value="Submit" class="button-primary" />
	</form>

	<?php
	} // end config_page()

	function process_bl_options() {
		// Check that user has proper security level
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}

		// Check that nonce field created in configuration form
		// is present
		check_admin_referer( 'bilinguallinker' );

		// Retrieve original plugin options array
		$options = get_option( 'BilingualLinkerGeneral' );

		// Cycle through all text form fields and store their values
		// in the options array
		foreach (
			array(
				'numberoflanguages',
				'language1name',
				'language2name',
				'language3name',
				'language4name',
				'language5name',
				'language1langcode',
				'language2langcode',
				'language3langcode',
				'language4langcode',
				'language5langcode',
				'language1defaulturl',
				'language2defaulturl',
				'language3defaulturl',
				'language4defaulturl',
				'language5defaulturl',
				'language1beforelink',
				'language2beforelink',
				'language3beforelink',
				'language4beforelink',
				'language5beforelink',
				'language1afterlink',
				'language2afterlink',
				'language3afterlink',
				'language4afterlink',
				'language5afterlink',
				'language1linktext',
				'language2linktext',
				'language3linktext',
				'language4linktext',
				'language5linktext'
			) as $option_name
		) {
			if ( isset( $_POST[ $option_name ] ) ) {
				$options[ $option_name ] =
					$_POST[ $option_name ];
			}
		}

		foreach (
			array(
				'hidesingle',
				'hidefrontpage',
				'hidesearchpage',
				'hidearchivepages',
				'hidecategorypages'
			) as $option_name
		) {
			if ( isset( $_POST[ $option_name ] ) ) {
				$options[ $option_name ] = true;
			} else {
				$options[ $option_name ] = false;
			}
		}

		// Store updated options array to database
		update_option( 'BilingualLinkerGeneral', $options );

		// Redirect the page to the configuration form that was
		// processed
		wp_redirect( add_query_arg( 'page', 'bilingual-linker', admin_url( 'options-general.php' ) ) );
		exit;
	}

	} // end class BL_Admin

	} //endif


	function the_bilingual_link(
		$language_id = '', $post_id = '', $link_text = '',
		$before_link = '', $after_link = '',
		$default_url = '', $echo = true,
		$href_lang_code = '', $hide_single = false,
	    $hide_front_page = false, $hide_search_page = false,
	    $hide_archive_pages = false, $hide_category_pages = false
	) {
		$default_args = array(
			'language_id' => '',
			'post_id' => '',
			'link_text' => '',
			'before_link' => '',
			'after_link' => '',
			'default_url' => '',
			'echo' => true,
			'href_lang_code' => '',
			'hide_single' => false,
			'hide_front_page' => false,
			'hide_search_page' => false,
			'hide_archive_pages' => false,
			'hide_category_pages' => false,
			'url_only' => false
		);

		if ( is_array( $language_id ) || func_num_args() == 0 ) {
			if ( func_num_args() == 0 ) {
				$language_id = array();
			}
			$parsed_args = wp_parse_args( $language_id, $default_args );
			extract( $parsed_args );
		}

		$gen_options = get_option( 'BilingualLinkerGeneral' );

		$lang_id = ! empty( $language_id ) ? $language_id : 1;
		$href_lang_code = ! empty( $href_lang_code ) ? $href_lang_code : $gen_options[ 'language' . $lang_id . 'langcode' ];
		$code_before_link = ! empty( $before_link ) ? $before_link : stripslashes( $gen_options[ 'language' . $lang_id . 'beforelink' ] );
		$code_after_link = ! empty( $after_link ) ? $after_link : stripslashes( $gen_options[ 'language' . $lang_id . 'afterlink' ] );
		$final_link_text = ! empty( $link_text ) ? $link_text : stripslashes( $gen_options[ 'language' . $lang_id . 'linktext' ] );
		$final_default_url = ! empty( $default_url ) ? $default_url : $gen_options[ 'language' . $lang_id . 'defaulturl' ];
		$hide_single = ! empty( $hide_single ) ? $hide_single : $gen_options[ 'hidesingle' ];
		$hide_front_page = ! empty( $hide_front_page ) ? $hide_front_page : $gen_options[ 'hidefrontpage' ];
		$hide_search_page = ! empty( $hide_search_page ) ? $hide_search_page : $gen_options[ 'hidesearchpage' ];
		$hide_archive_pages = ! empty( $hide_archive_pages ) ? $hide_archive_pages : $gen_options[ 'hidearchivepages' ];
		$hide_category_pages = ! empty( $hide_category_pages ) ? $hide_category_pages : $gen_options[ 'hidecategorypages' ];

		$output = '';
		$url_output = '';

		if ( preg_match( "#https?://#", $final_default_url ) === 0 ) {
			$final_default_url = 'http://' . $final_default_url;
		}

		if ( is_front_page() && !$hide_front_page ) {

			$output = $code_before_link . '<a href="' . $final_default_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
			$url_output = $final_default_url;

		} elseif ( is_search() && !$hide_search_page ) {

			$search_url = add_query_arg( 's', $_GET['s'], $final_default_url );
			$output     = $code_before_link . '<a href="' . $search_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
			$url_output = $search_url;

		} elseif ( is_page() || is_single() ) {

			$other_lang_url = get_post_meta( get_the_ID(), 'bilingual-linker-other-lang-url-' . $lang_id, true );

			if ( $other_lang_url != '' ) {
				if ( preg_match( "#https?://#", $other_lang_url ) === 0 ) {
					$other_lang_url = 'http://' . $other_lang_url;
				}

				$output = $code_before_link . '<a href="' . $other_lang_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
				$url_output = $other_lang_url;

			} elseif ( empty( $other_lang_url ) && !$hide_single && ! empty( $final_default_url ) ) {

				$output = $code_before_link . '<a href="' . $final_default_url . '" '. ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
				$url_output = $final_default_url;
			}
		} elseif ( is_category() && !$hide_category_pages ) {

			$other_lang_url = get_metadata( 'category', get_query_var( 'cat' ), 'bilingual-linker-other-lang-url-' . $lang_id, true );

			if ( $other_lang_url != '' ) {

				if ( preg_match( "#https?://#", $other_lang_url ) === 0 ) {
					$other_lang_url = 'http://' . $other_lang_url;
				}

				$output = $code_before_link . '<a href="' . $other_lang_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
				$url_output = $other_lang_url;

			} elseif ( empty( $other_lang_url ) && ! empty( $final_default_url ) ) {

				$output = $code_before_link . '<a href="' . $final_default_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
				$url_output = $final_default_url;
			}

		} else if ( ( is_archive() && ( is_date() || is_year() || is_month() ) ) && !$hide_archive_pages ) {

			if ( is_year() ) {
				$archive_url = add_query_arg( 'year', get_query_var( 'year' ), $final_default_url );
			} elseif ( is_month() ) {
				$archive_url = add_query_arg( array(
					'year'     => get_query_var( 'year' ),
					'monthnum' => get_query_var( 'monthnum' )
				), $final_default_url );
			} elseif ( is_day() ) {
				$archive_url = add_query_arg( array(
					'year'     => get_query_var( 'year' ),
					'monthnum' => get_query_var( 'monthnum' ),
					'day'      => get_query_var( 'day' )
				), $final_default_url );
			}

			$output = $code_before_link . '<a href="' . $archive_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
			$url_output = $archive_url;

		} elseif ( !is_front_page() && !is_search() && !is_page() && !is_single() && !is_category() && !is_archive() ) {
			$output = $code_before_link . '<a href="' . $final_default_url . '" ' . ( !empty( $href_lang_code ) ? 'rel="alternate" hreflang="' . $href_lang_code . '"' : '' ) . '>' . $final_link_text . '</a>' . $code_after_link;
			$url_output = $final_default_url;
		}

		if ( $echo == true ) {
			if ( !isset( $url_only ) || false == $url_only ) {
				echo $output;
			} elseif ( isset( $url_only ) && true == $url_only ) {
				echo $url_output;
			}
		} else {
			if ( !isset( $url_only ) || false == $url_only ) {
				return $output;
			} elseif ( isset( $url_only ) && true == $url_only ) {
				return $url_output;
			}
		}
	}

	register_activation_hook( BL_FILE, 'bilingual_linker_install' );

	if ( is_admin() ) {
		$my_bl_admin = new BL_Admin();
	}

	add_action( 'init', 'bl_init' );

	function bl_init() {
		global $wpdb;

		$wpdb->categorymeta = $wpdb->get_blog_prefix() . 'categorymeta';
	}

	add_shortcode( 'the-bilingual-link', 'bl_shortcode' );

	function bl_shortcode( $atts ) {
		$atts['echo'] = false;
		return the_bilingual_link( $atts );
	}

	add_filter( 'walker_nav_menu_start_el', 'bl_walker_nav_menu_start_el', 1, 4 );

	function bl_walker_nav_menu_start_el( $item_output, $item, $depth, $args ){

		if( $item->type != 'custom' && !in_array( 'BILINGUAL-LINK', $item->classes ) ) {
			return $item_output;
		}

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

		$item_output = $args->before;

		$item_output .= the_bilingual_link( array( 'echo' => false ) );

		$item_output .= $args->after;

		return $item_output;
	}

	?>
