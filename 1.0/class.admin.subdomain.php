<?php

/*
 * Class to crate admin area for managing settings
 *
 * The main plugin class, holds everything our plugin does,
 * initialized right after declaration
 * Original Code : http://theme.fm/2011/10/how-to-create-tabs-with-the-settings-api-in-wordpress-2590/
 */
class mcs_admin {
	
	
	/*
	 * For easier overriding we declared the keys
	 * here as well as our tabs array which is populated
	 * when registering settings
	 */
	private $selected_categories_key 	= 'mcs_categories';
	private $advanced_settings_key		= 'mcs_subdomain_settings';
	
	private $plugin_options_key 		= 'mcs_page_subdomain';
	private $plugin_settings_tabs 		= array();
	
	/*
	 * Fired during plugins_loaded (very very early),
	 * so don't miss-use this, only actions and filters,
	 * current ones speak for themselves.
	 */
	public function __construct() {		
		add_action( 'init', array( &$this, 'load_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_selected_categories' ) );
		add_action( 'admin_init', array( &$this, 'register_advanced_settings' ) );		
		add_action( 'admin_menu', array( &$this, 'add_admin_menus' ) );
		
		add_action( 'admin_notices', array( &$this, 'error_admin_notice') );
		//echo plugin_basename(__FILE__);
		add_filter( 'plugin_action_links_categories-subdomains/categories-subdomains.php',  array( &$this, 'my_plugin_action_links') );

	}
	
	/*
	 * Loads both the general and advanced settings from
	 * the database into their respective arrays. Uses
	 * array_merge to merge with default values if they're
	 * missing.
	 */
	function load_settings() {
		$this->selected_categories 	= (array) get_option( $this->selected_categories_key );				
		$this->advanced_settings 	= (array) get_option( $this->advanced_settings_key );
		if( empty($this->theme_settings)) $this->theme_settings = array();
			

	}
	
	
	public function error_admin_notice() {
		$permalink_structure  = get_option('permalink_structure');
		if(empty( $permalink_structure )) {
			echo "<div class=\"error\"><p><span style=\"color:#cc0000\">WARNING!</span> For <strong>Main Category As Subdomain Plugin</strong> To Works Properly. Please Don't Use Default Permalink Settings</p></div>";
		}
	}
	
	public function my_plugin_action_links( $links ) {
	   $links[] = '<a href="' . get_admin_url(null, 'options-general.php?page=mcs_page_subdomain') . '">Settings</a>';
	   $links[] = '<a href="' . get_admin_url(null, 'options-general.php?page=mcs_page_subdomain&tab=mcs_help') . '" >Configure Wild Card</a>';
	   return $links;
	}	
	
	/*
	 * Registers the general settings via the Settings API,
	 * appends the setting to the tabs array of the object.
	 */
	function register_selected_categories() {
		$this->plugin_settings_tabs[$this->selected_categories_key] = array('title'=>'Main Categories','button'=> 'Change To Subdomain');
		
		register_setting( $this->selected_categories_key, $this->selected_categories_key ,array( &$this, 'sanitize_category_callback'));
		
			add_settings_section( 'section_general', 'READ PLEASE!!', array( &$this, 'section_general_desc' ), $this->selected_categories_key );
			//add_settings_field( 'general_option', 'A General Option', array( &$this, 'field_general_option' ), $this->selected_categories_key, 'section_general' );
			foreach(get_categories() as $cat ) {
				if( 0 == $cat->parent) {
					add_settings_field( $cat->slug, $cat->name , array( &$this, 'field_categories_option' ), $this->selected_categories_key , 'section_general' , array ($cat) );
				}
			}		
		
	}
	
	
	
	
	
	function sanitize_category_callback( $input ) {
	/*
	INPUT FORMAT :
	
	Array
	(
		[id] => Array
			(
				[0] => 2004
				[1] => 2
				[2] => 292
				[3] => 1840
			)

		[theme] => Array
			(
				[ 2004] => ridizain
				[ 292] => ArcadePress

	)

	*/
		$selected_cats = array();
		if(isset($input['id'])) {
			foreach($input['id'] as $id) {
				$selected_cats[$id] = '';
			}
		}
		
		if(isset($input['theme'])) {
			foreach($input['theme'] as $id=>$theme) {
				if(isset($selected_cats[$id]))
					$selected_cats[$id] = $theme;
			}			
		}
		
		return $selected_cats;
		
	}	

	
	function field_categories_option( $args ) {

		$cat = $args[0];
		$check = array_key_exists( $cat->term_id, $this->selected_categories );
		echo '<input value="' . $cat->term_id .'" type="checkbox" name="' . $this->selected_categories_key . '[id][]" ' . checked( true,$check , false ) . ' /> ';
		if($check)	$this->get_all_theme($args );
		
			
	}
	function get_all_theme($args ) {
		$cat = $args[0];
		$list_theme = '<select name="'. $this->selected_categories_key .'[theme]' . '['. $cat->term_id . ']" >';
		$list_theme .= '<option value=""></option>';
		foreach(wp_get_themes() as $theme_name =>$noneed) {//selected="selected"
			$list_theme .= '<option value="' . $theme_name . '"' . (($this->selected_categories[$cat->term_id] == $theme_name) ? ' selected="selected"' : ''). '> ' . $theme_name . ' </option>';
		}
		$list_theme .= '</select>';	
		echo $list_theme;
	}		
	
	
	
	/*
	 * Registers the advanced settings and appends the
	 * key to the plugin settings tabs array.
	 */
	function register_advanced_settings() {
		$this->plugin_settings_tabs[$this->advanced_settings_key] = array('title'=>'Subdomain Settings','button'=> 'Save Settings');
		
		register_setting( $this->advanced_settings_key, $this->advanced_settings_key ,array( &$this, 'sanitize_subdomain_settings_callback' ) );
		add_settings_section( 'section_advanced', 'Advanced Plugin Settings', array( &$this, 'section_advanced_desc' ), $this->advanced_settings_key );
		//add_settings_field( 'advanced_option', 'An Advanced Option', array( &$this, 'field_advanced_option' ), $this->advanced_settings_key, 'section_advanced' );
		
		add_settings_field( 'field_subdomain_option', 'Redirect Old Url: ', array( &$this, 'field_subdomain_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_home_option', 'Change Homepage URL: ', array( &$this, 'field_home_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_home_desc_option', 'Change Home Desc', array( &$this, 'field_home_desc_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_subcategory_option', 'Child Categories', array( &$this, 'field_subcategory_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_using_index_option', 'Using index.php', array( &$this, 'field_using_index_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_widget_recent_post_option', 'Widget Recent Post', array( &$this, 'field_widget_recent_post_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_widget_categories_option', 'Widget Categories', array( &$this, 'field_widget_categories_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_remove_category_permalink_option', 'Remove %category% permalink', array( &$this, 'field_remove_category_permalink_option' ), $this->advanced_settings_key , 'section_advanced' );		
		
	}
	
	function sanitize_subdomain_settings_callback($input) {
		$new_settings = $this->advanced_settings;
		if($input['remove_category_permalink'] == 1) {
			if( stripos(get_option('permalink_structure'),'%category%') === false)
				$input['remove_category_permalink'] =0;
		}

		foreach($input as $key=>$value) {
			$new_settings[$key] = $value;
		}
	
		return $new_settings;
	}
	
	function section_subdomain() {
		echo 'You can set the subdomain setting here';
	}
		
	function field_subdomain_option() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[redirect]" value="1"  ' . checked( 1, $this->advanced_settings['redirect'] , false ) .'/> Yes&nbsp;&nbsp;';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[redirect]"  value="0" ' .  checked( 0, $this->advanced_settings['redirect'] , false ) .' /> No&nbsp;';
		echo '<br><br><label><em>To Avoid duplicate content, I Recommend you to turn on this option</em></label><hr>';
	}
	
	function field_home_option() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[home_url]" value="1"  ' .  checked( 1, $this->advanced_settings['home_url'] , false ) .'/> Yes&nbsp;&nbsp;';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[home_url]"  value="0" ' .  checked( 0, $this->advanced_settings['home_url'] , false ) .' />  No';
		echo '<br><br><label><em>In Subdomain, Your home url will be replaced with subdomain home url. Only if Your theme using either home_url() or bloginfo("url")
			 <br>You can also use  function: mcs_home_url()  to replace your home url in subdomain </em></label><hr>';
	}
	
	function field_home_desc_option() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[bloginfo]" value="1"  ' .  checked( 1, $this->advanced_settings['bloginfo'] , false ) .'/> Yes&nbsp;&nbsp;';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[bloginfo]"  value="0" ' .  checked( 0, $this->advanced_settings['bloginfo'] , false ) .' /> No';
		echo "<br><br><label><em>Using Category Name &amp; Describtion To Replace Blog Name &amp; Describtion in Subdomain<br>
			Don't forget to update your blog describtion (Usually it's empty) <a href='" . home_url() . "/wp-admin/edit-tags.php?taxonomy=category'> Edit </a></em></label><hr>";
	}
	
	function field_subcategory_option() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[child_categories]" value="main_categories_subdomains"  ' .  checked( 'main_categories_subdomains', $this->advanced_settings['child_categories'] , false ) .'/> Change to Categories in Subdomain&nbsp;&nbsp; ';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[child_categories]"  value="all_subdomain" ' .  checked( 'all_subdomain', $this->advanced_settings['child_categories'] , false ) .' /> Change All To Subdomain&nbsp;&nbsp;';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[child_categories]"  value="none" ' .  checked( 'none', $this->advanced_settings['child_categories'] , false ) .' /> Original';		
		echo '<br><br><label><em></em></label><hr>';
	}
	
	function field_using_index_option() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[using_index]" value="1"  ' .  checked( 1, $this->advanced_settings['using_index'] , false ) .'/> Yes&nbsp;&nbsp;';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[using_index]"  value="0" ' .  checked( 0, $this->advanced_settings['using_index'] , false ) .' />  No';
		echo '<br><br><label><em>By Default We are using category.php in your theme, if you want use index.php check "yes"</em></label><hr>';
	}


	function field_widget_recent_post_option() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[widget_recent_post]" value="1"  ' .  checked( 1, $this->advanced_settings['widget_recent_post'] , false ) .'/> Yes&nbsp;&nbsp;';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[widget_recent_post]"  value="0" ' .  checked( 0, $this->advanced_settings['widget_recent_post'] , false ) .' />  No';
		echo '<br><br><label><em>"Widget Recent Posts" will only show recent post from Same Subdomain</em></label><hr>';
	}

	function field_widget_categories_option() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[widget_categories]" value="1"  ' .  checked( 1, $this->advanced_settings['widget_categories'] , false ) .'/> Yes&nbsp;&nbsp;';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[widget_categories]"  value="0" ' .  checked( 0, $this->advanced_settings['widget_categories'] , false ) .' />  No';
		echo '<br><br><label><em>Only Show Child Categories from Subdomain in "Categories Widget"</em></label><hr>';
	}	
	
	function field_remove_category_permalink_option() {	
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[remove_category_permalink]" value="1"  ' .  checked( 1, $this->advanced_settings['remove_category_permalink'] , false ) .'/> Yes&nbsp;&nbsp;';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[remove_category_permalink]"  value="0" ' .  checked( 0, $this->advanced_settings['remove_category_permalink'] , false ) .' />  No';
		if ( stripos(get_option('permalink_structure'),'%category%') === false)
			echo '<br><br><label><em>Your Permalink Structure doesn\'t has %category%, No need to do Anything here</em></label><hr>';
		else
			echo '<br><br><label><em>Your Permalink Structure has %category%, you can remove it in subdomain by check "yes"</em></label><hr>';
	}		
	
	
	
	
	
	
	
	/*
	 * The following methods provide descriptions
	 * for their respective sections, used as callbacks
	 * with add_settings_section
	 */
	function section_general_desc() { echo '<ol>
											<li>Don\'t Forget to  Set Up Wildcard (*) Subdomains in your Web Server <a href="/wp-admin/options-general.php?page=mcs_page_subdomain&tab=mcs_help"> HELP </a></li>
											<li><strong style="color:red">REMEMBER: </strong>If Your post has two or more categories, we will pick the first category order by an ID.
											<br>And the first category maybe not subdomain</li>
											</ol>'; }
	function section_advanced_desc() { }
	
	/*
	 * Called during admin_menu, adds an options
	 * page under Settings called My Settings, rendered
	 * using the plugin_options_page method.
	 */
	function add_admin_menus() {
		add_options_page( 'My Plugin Settings', 'Subdomain Settings', 'manage_options', $this->plugin_options_key, array( &$this, 'plugin_options_page' ) );
	}
	
	/*
	 * Plugin Options page rendering goes here, checks
	 * for active tab and replaces key with the related
	 * settings key. Uses the plugin_options_tabs method
	 * to render the tabs.
	 */
	function plugin_options_page() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->selected_categories_key;
		?>
		<div class="wrap">
			<?php $this->plugin_options_tabs(); ?>
			<?php if($tab == 'mcs_help') { ?>
			
			
			
			
<h1>Configuring Wildcard Subdomains</h1>
This page contains some examples of how to configure wildcard subdomains in different circumstances. If you cannot determine how to set up wildcard subdomains on your particular web server, contact your webhost for directions.
</a><h2> <span class="mw-headline"> CPanel </span></h2>
<p>Make a sub-domain named "*" (wildcard) at your CPanel (*.example.com). <strong>Make sure to point this at the same folder location where your wordpress folder is located</strong>.
</p>

<h3 class="subject2blue">Wildcard DNS for the Primary Domain</h3>

<p>You can set up wildcard DNS via cPanel by following these steps:</p>

<ol>
	<li>Log into your cPanel.</li>
	<li>Navigate to the <strong>Subdomains</strong> icon in the <em>Domains</em> section.</li>
	<li>Add a subdomain called *</li>
	<li>Make sure to set the document root to your <em>public_html</em> folder.</li>
</ol>

<h3 class="subject2blue">Wildcard DNS for Addon Domains</h3>

<p>The process for setting up wildcard DNS for an addon domain is mostly the same with the exception of the document root which is used.</p>

<ol>
	<li>In cPanel, navigate to the <strong>Subdomains</strong> icon in the <em>Domains</em> section.</li>
	<li>Add a subdomain called *</li>
	<li>Make sure to set the document root to match the document root of the addon domain. The document root can be found inside of your cPanel within the <em>Addon Domain</em> area.<span id="cke_bm_164E" style="display: none;">&nbsp;</span></li>
</ol>

<img src="http://lh6.googleusercontent.com/-BihrFLWyFZ8/U6zud30Wf8I/AAAAAAAAA2U/jsT0tkZbyhs/s726/wilcard-addon-domains.gif" />

<hr><p></p><p></p>
<h2> <span class="mw-headline"> Plesk </span></h2>
<p>There are several steps that differ when setting up the server for wildcard subdomains on a server using Plesk Panel compared to a server using cPanel (or no control panel).  This article <a href="http://codex.wordpress.org/Configuring_Wildcard_Subdomains_for_multi_site_under_Plesk_Control_Panel" title="Configuring Wildcard Subdomains for multi site under Plesk Control Panel">Configuring Wildcard Subdomains for multi site under Plesk Control Panel</a> details all the steps involved.  
</p>
<hr><p></p><p></p>
<h2> <span class="mw-headline"> DirectAdmin panel </span></h2>
Click "User Panel" -&gt; DNS Management -&gt; add the following three entries using the three columns: <pre>* A xxx.xx.xx.xxx</pre> (Replace "xxx.xx.xx.xxx" with your website IP.) 
Click "Admin Panel" (If you have no "admin panel" ask your host to do this.) -&gt; Custom Httpd -&gt; yourdomain.com -&gt; In the text input area, just paste and "save" precisely the following: <pre>ServerAlias *.|DOMAIN|</pre> (If you ever need to un-do a custom Httpd: return here, delete text from input area, save.)
<ul><li> DirectAdmin.com: <a href="http://help.directadmin.com/item.php?id=127" class="external text" title="http://help.directadmin.com/item.php?id=127">Apache Wildcard Documentation</a>... DirectAdmin.com forum: <a href="http://www.directadmin.com/forum/showthread.php?p=195033" class="external text" title="http://www.directadmin.com/forum/showthread.php?p=195033">Wordpress wildcard subdomains</a>.
</li></ul>			
			
			
					
			
			
			
			
			
			
			<?php } else { ?>		
			<form method="post" action="options.php">
				<?php wp_nonce_field( 'update-options' ); ?>
				<?php settings_fields( $tab ); ?>
				<?php do_settings_sections( $tab ); ?>
				<?php submit_button( $this->plugin_settings_tabs[$tab]['button'] ); ?>
			</form>
		</div>
		<?php }
	}
	
	/*
	 * Renders our tabs in the plugin options page,
	 * walks through the object's tabs array and prints
	 * them one by one. Provides the heading for the
	 * plugin_options_page method.
	 */
	function plugin_options_tabs() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $this->selected_categories_key;

		//screen_icon(); deprecated
		$this->plugin_settings_tabs['mcs_help'] = array('title'=>'Help','button'=> '');	
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->plugin_options_key . '&tab=' . $tab_key . '">' . $tab_caption['title'] . '</a>';	
		}
		echo '</h2>';
	}
}




?>