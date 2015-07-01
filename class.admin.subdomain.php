<?php

/*
 * Class to crate admin area for managing settings
 *
 * The main plugin class, holds everything our plugin does,
 * initialized right after declaration
 * Original Code : http://theme.fm/2011/10/how-to-create-tabs-with-the-settings-api-in-wordpress-2590/
 */
class mcs_admin {
	
	private $id_subdomain = 0;	
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
		add_filter( 'plugin_action_links_main-category-as-subdomain/main-category-as-subdomain.php',  array( &$this, 'my_plugin_action_links') );
		new mcs_menus_location();		
	
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
			
		if( isset( $_GET['subdomain'] ) && isset( $this->selected_categories[$_GET['subdomain']] ) ) {
			$this->id_subdomain = $_GET['subdomain'];
		}
	}
	
	
	public function error_admin_notice() {
		$permalink_structure  = get_option('permalink_structure');
		if(empty( $permalink_structure )) {
			echo "<div class=\"error\"><p><span style=\"color:#cc0000\">WARNING!</span> For <strong>Main Category As Subdomain Plugin</strong> To Works Properly. Please Don't Use Default Permalink Settings</p></div>";
		}
		if( !$this->advanced_settings['mode']  ) {
			echo "<div class=\"updated\"><p><span style=\"color:#00FF00\">Notice : </span> <strong>'Main Category As Subdomain' plugin in testing mode </p></div>";
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
		$button = 'Change To Subdomain';
		if ( $this->id_subdomain) 
			$button = 'Save';
	
		$this->plugin_settings_tabs[$this->selected_categories_key] = array('title'=>'Main Categories','button'=> $button);	
		
		register_setting( $this->selected_categories_key, $this->selected_categories_key ,array( &$this, 'sanitize_category_callback'));
		
			add_settings_section( 'section_general', 'READ PLEASE!!', array( &$this, 'section_general_desc' ), $this->selected_categories_key );
			
			if( $this->id_subdomain ) { 				
				add_settings_field( 'field_subdomain_theme', 'Change Theme : ', array( &$this, 'field_subdomain_theme' ), $this->selected_categories_key , 'section_general' ,$_GET['subdomain'] );	
				add_settings_field( 'field_subdomain_fix_css', ' Fix Relative CSS path', array( &$this, 'field_subdomain_fix_css' ), $this->selected_categories_key , 'section_general' ,$_GET['subdomain'] );				
				add_settings_field( 'field_subdomain_page_shows', 'Blog pages show at most : ', array( &$this, 'field_subdomain_page_shows' ), $this->selected_categories_key , 'section_general' ,$_GET['subdomain'] );
				add_settings_field( 'field_subdomain_front_page', ' A static page >> Front page: ', array( &$this, 'field_subdomain_front_page' ), $this->selected_categories_key , 'section_general' ,$_GET['subdomain'] );
				
				add_settings_field( 'field_subdomain_hidden', '', array( &$this, 'field_subdomain_hidden' ), $this->selected_categories_key , 'section_general' ,$_GET['subdomain'] );	

				
			} else {
						
				//add_settings_field( 'general_option', 'A General Option', array( &$this, 'field_general_option' ), $this->selected_categories_key, 'section_general' );
				$all_categories = get_categories();
				usort($all_categories, '_usort_terms_by_ID'); // order by ID
				foreach($all_categories  as $cat ) {
					if( 0 == $cat->parent) {
						add_settings_field( $cat->slug, $cat->term_id .'. ' . $cat->name , array( &$this, 'field_categories_option' ), $this->selected_categories_key , 'section_general' , array ($cat) );
					}
				}

			}
		
	}
	
	
	
	
	
	
	
	function sanitize_category_callback( $inputs ) {
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
	
	
		$results = $this->selected_categories;
		if( isset( $inputs['hidden_id'] )) {
			$id = $inputs['hidden_id'] ;
			if( isset($this->selected_categories[$id]) ) {
				unset($inputs['hidden_id']);
				if( ! empty($inputs) ) {
					$arrayresult = array();
					foreach( $inputs as $key => $value ) {
						if(!empty($value)) {
							$arrayresult[$key] = $value;
						}
					}
					$results[$id] = $arrayresult;
				}else
					$results[$id] = array();					
			}
			
		} else { 
		
			$selected_cats = array();
			if(isset($inputs['id'])) {
				foreach($inputs['id'] as $id) {
					if( isset($this->selected_categories[$id]) )
						$selected_cats[$id] = $this->selected_categories[$id];
					else
						$selected_cats[$id] = array();
				}
				
				$results = $selected_cats;
			}

		}
		
		/*
		if(isset($input['theme'])) {
			foreach($input['theme'] as $id=>$theme) {
				if(isset($selected_cats[$id]))
					$selected_cats[$id] = $theme;
			}			
		}
		*/
		
		return $results;
		
	}	


	/*
	 * The following methods provide descriptions
	 * for their respective sections, used as callbacks
	 * with add_settings_section
	 */
	function section_general_desc() { 
		if( $this->id_subdomain  ) {	
			$the_cat = get_category( $this->id_subdomain );
			echo '<h3>' . ucwords( $the_cat->name ) . ' Subdomain Settings</h3><br>';		
			echo '<a style="font:bold 24px;" href="/wp-admin/options-general.php?page=mcs_page_subdomain" > &lt;&lt; BACK </a>';
		} else {
		
		
			$subdomain_slug = $this->generateRandomString(7);
			$link = str_replace( '/www.' , '/' , home_url() );
			$link = str_replace( 'https://' , 'https://' . $subdomain_slug . '.' , $link);
			$link = str_replace( 'http://' , 'http://' . $subdomain_slug . '.', $link);
			
			$detect_subdomain = $this->Visit($link);
			$wilcard_message = '';
			if( $detect_subdomain ) {
				$wilcard_message = 'You have set up willcard subdomain, good job!';
			} elseif ( $detect_subdomain === false) {
				$wilcard_message .= '<span style="color:red"> We detect you haven\'t set wildcard subdomain in your host, Please Follow this <a href="/wp-admin/options-general.php?page=mcs_page_subdomain&tab=mcs_help">instruction</a></span> ';
			} elseif($detect_subdomain === null) {
				$wilcard_message .= 'Don\'t Forget to  Set Up Wildcard (*) Subdomains in your Web Server <a href="/wp-admin/options-general.php?page=mcs_page_subdomain&tab=mcs_help"> HELP </a>';
			}
			
			$multicategories_message = '';
			if( $this->advanced_settings['multicat'] == 'by_id' ) {
				$multicategories_message = '<strong style="color:red">REMEMBER: </strong>If Your post has two or more categories, we will pick the first category order by an ID.
					<br>And the first category maybe not subdomain';			
			} else {
				$multicategories_message = '<strong style="color:red">REMEMBER: </strong>If Your post has two or more categories, we will find the subdomain
					<br>And update the post to single category';				
			}
			
			$xml_sitemap_message = '<strong style="color:red">ATTENTION</strong> FOR Xml Sitemap plugin User, Check Your plugin support subdomain or not, <a href="/wp-admin/options-general.php?page=mcs_page_subdomain&tab=mcs_help#xmlsitemap"> Read Here </a></strong>';

			
			echo 	'<ol>				
					<li>' . $wilcard_message . '</li>								
					<li>' . $multicategories_message .'</li>
					<li>' . $xml_sitemap_message .'</li>
					</ol><hr>';
			
		}
		
	}
	
	function field_categories_option( $args ) {

		$cat = $args[0];
		$check = array_key_exists( $cat->term_id, $this->selected_categories );
		echo '<input value="' . $cat->term_id .'" type="checkbox" name="' . $this->selected_categories_key . '[id][]" ' . checked( true,$check , false ) . ' /> ';
		if($check)	//$this->get_all_theme($args );
			echo '<a style="margin-left:40px;" href="' . admin_url('options-general.php?page=mcs_page_subdomain&tab=mcs_categories&subdomain=' . $cat->term_id  ) . '">Customize</a>';				
	}
	
	//uppdate
	function field_subdomain_theme($args ) {
		$list_theme = '<select name="'. $this->selected_categories_key .'[theme]" >';
		$list_theme .= '<option value=""></option>';
		$current_theme = isset( $this->selected_categories[$this->id_subdomain]['theme'] ) ? $this->selected_categories[$this->id_subdomain]['theme'] : '';
		foreach(wp_get_themes() as $theme_name =>$noneed) {//selected="selected"
			if(  $current_theme == $theme_name )
				$list_theme .= '<option selected="selected" value="' . $theme_name . '"' . '' . '> ' . $theme_name . ' </option>';				
			else			
				$list_theme .= '<option value="' . $theme_name . '"' . '' . '> ' . $theme_name . ' </option>';
		}		
		$list_theme .= '</select>';	
		echo $list_theme ;
		echo '<br><br><hr><label><em>
				To customize theme : <br> Activate the Theme, Customize it, Then Deactivate again by comeback to default theme <br>
				Your setting on theme should be still accessible and aplied even the theme is inactive
			  </em></label><br><br>';
		
	}
	
	//update
	function  field_subdomain_page_shows($args ) {	
		$r = isset($this->selected_categories[$this->id_subdomain]['posts_per_page']) ? $this->selected_categories[$this->id_subdomain]['posts_per_page'] : '';		
		echo '<input name="' . $this->selected_categories_key .'[posts_per_page]" type="number" step="1" min="1" id="posts_per_page" value="'. $r .'" class="small-text" /> posts</td>';	
	}
	
	function field_subdomain_front_page() {
		$r = isset($this->selected_categories[$this->id_subdomain]['front_page']) ? $this->selected_categories[$this->id_subdomain]['front_page'] : '';	
		echo wp_dropdown_pages( array( 'name' => $this->selected_categories_key . '[front_page]', 'echo' => 0, 'show_option_none' => __( ' ' ), 'option_none_value' => '0', 'selected' =>  $r  ) );
	}
	
	function field_subdomain_fix_css() {
		$r = isset($this->selected_categories[$this->id_subdomain]['fix_relative_css']) ? $this->selected_categories[$this->id_subdomain]['fix_relative_css'] : '';
		echo '<input name="' . $this->selected_categories_key . '[fix_relative_css]" type="checkbox" id="" value="1"' . checked('1', $r ,false ) .' />';	
		echo '<br><br><hr><label><em>
				if you found the images or logo in your theme is not appear, check it
			  </em></label><br><br>';		
	}
	
	//update
	function field_subdomain_hidden($args ) {
		$hidden_input = '<input type="hidden" name="' . $this->selected_categories_key .'[hidden_id]' . '" value="' . $this->id_subdomain . '">';	
		echo $hidden_input ;
		
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

		add_settings_field( 'field_subdomain_mode', 'Mode : ', array( &$this, 'field_subdomain_mode' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_subdomain_multicat', 'Subdomain on Post with Multicategories', array( &$this, 'field_subdomain_multicat' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_subcategory_option', 'Child Categories', array( &$this, 'field_subcategory_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_remove_category_permalink_option', '%category% permalink', array( &$this, 'field_remove_category_permalink_option' ), $this->advanced_settings_key , 'section_advanced' );							
		add_settings_field( 'field_single_category_url', 'Single Category in Url', array( &$this, 'field_single_category_url' ), $this->advanced_settings_key , 'section_advanced' );		
		add_settings_field( 'field_subdomain_option', 'Redirect Old Url: ', array( &$this, 'field_subdomain_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_home_option', 'Change Homepage URL: ', array( &$this, 'field_home_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_home_desc_option', 'Change Home Desc', array( &$this, 'field_home_desc_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_using_index_option', 'Using index.php', array( &$this, 'field_using_index_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_widget_recent_post_option', 'Widget Recent Post', array( &$this, 'field_widget_recent_post_option' ), $this->advanced_settings_key , 'section_advanced' );
		add_settings_field( 'field_widget_categories_option', 'Widget Categories', array( &$this, 'field_widget_categories_option' ), $this->advanced_settings_key , 'section_advanced' );
		
	}
	
	function sanitize_subdomain_settings_callback($input) {
		$new_settings = $this->advanced_settings;
		if($input['remove_category_permalink'] ) {
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


	function field_subdomain_mode() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[mode]" value="1"  ' . checked( 1, $this->advanced_settings['mode'] , false ) .'/><strong>ACTIVATE</strong>&nbsp;&nbsp;';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[mode]"  value="0" ' .  checked( 0, $this->advanced_settings['mode'] , false ) .' /> TESTING&nbsp;';
		echo '<br><br><hr><label><em>
			  Testing\'s Mode means This plugin only works if Admin (you) login OR when user visits subdomain,<br>
			  your visitors will never see any changes on Main Domain, <br>so you can test this plugin without any worry<br><br>
			  if everything work like you expected, check "ACTIVATE"<br>
			  if not, just uninstalls it or better ask me <a target="_blank" href="https://wordpress.org/support/plugin/main-category-as-subdomain">here</a></em>
			  </label><br><br>';
	}

	function field_subdomain_multicat() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[multicat]" value="by_id"  ' . checked( 'by_id', $this->advanced_settings['multicat'] , false ) .'/>  1. Order by ID (Recommended)&nbsp;&nbsp;<br>';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[multicat]"  value="by_find" ' .  checked( 'by_find', $this->advanced_settings['multicat'] , false ) .' /> 2. Find Subdomain and update to single category&nbsp;';
		echo '<br><br><hr><label><em>
				1.  we will pick the first category order by an ID. <br><br>
					pros : No duplicate content if you activate new subdomain so no need to update anything<br>
					cons: First category maybe not subdomain, so even you have subdomain inside the post, <br>
					because the first category isn\'t subdomain, then the post will never become subdomain<br><br>
				2. 	Find Subdomain in categories, ignoring the first category <br><br>
				    pros : if the post has subdomain, it will be converted to subdomain<br>
					cons: To avoid changes  to another subdomain if you activate new one,<br>
					the post have to be updated to single category<br><br>
					
					Don\'t worry, The updating to single category not working in "testing" mode
				
			  </em></label>';
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
			Don't forget to update your category describtion (Usually it's empty) <a href='" . home_url() . "/wp-admin/edit-tags.php?taxonomy=category'> Edit </a></em></label><hr>";
	}
	
	function field_subcategory_option() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[child_categories]" value="main_categories_subdomains"  ' .  checked( 'main_categories_subdomains', $this->advanced_settings['child_categories'] , false ) .'/> Change to Categories in Subdomain&nbsp;&nbsp; <br>';
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[child_categories]"  value="all_subdomain" ' .  checked( 'all_subdomain', $this->advanced_settings['child_categories'] , false ) .' /> Change All To Subdomain&nbsp;&nbsp;<br>';
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
		if ( stripos(get_option('permalink_structure'),'%category%') !== false) {
			echo '<Input type="radio" name="' .$this->advanced_settings_key . '[remove_category_permalink]" value="2"  ' .  checked( 2, $this->advanced_settings['remove_category_permalink'] , false ) .'/> Remove OR Change to Child Categories if exists &nbsp;&nbsp; <br>';	
			echo '<Input type="radio" name="' .$this->advanced_settings_key . '[remove_category_permalink]" value="1"  ' .  checked( 1, $this->advanced_settings['remove_category_permalink'] , false ) .'/> Remove Completely &nbsp;&nbsp;<br>';
			echo '<Input type="radio" name="' .$this->advanced_settings_key . '[remove_category_permalink]"  value="0" ' .  checked( 0, $this->advanced_settings['remove_category_permalink'] , false ) .' />  do nothing <hr>';
		} else {
			echo 'You don\'t have one, No need to do anything here<hr>';
		}
		
	}

	function field_single_category_url() {
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[single_category_url]" value="1"  ' .  checked( 1, $this->advanced_settings['single_category_url'] , false ) .'/> Yes&nbsp;&nbsp;';	
		echo '<Input type="radio" name="' .$this->advanced_settings_key . '[single_category_url]" value="0"  ' .  checked( 0, $this->advanced_settings['single_category_url'] , false ) .'/> No';	
		echo '<br><br><hr><label><em>
			Make url in subdomain only contain one single category, well something like this<br><br>
			http://cat1.yourdomain.com/category/cat2/cat3/cat4 <br> to <br> http://cat1.yourdomain.com/category/cat4<br><br>			
			http://cat1.yourdomain.com/cat2/cat3/cat4/this_is_my_posts.html <br> to <br> http://cat1.yourdomain.com/cat4/this_is_my_posts.html <br><br>
			</em>  </label><br><br>';		
	}
	
	
	
	
	
	
	

	function section_advanced_desc() { }
	
	/*
	 * Called during admin_menu, adds an options
	 * page under Settings called My Settings, rendered
	 * using the plugin_options_page method.
	 */
	function add_admin_menus() {
		add_options_page( 'Subdomain Plugin Settings', 'Subdomain Settings', 'manage_options', $this->plugin_options_key, array( &$this, 'plugin_options_page' ) );
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

<span style="color:red;"> <br> Attention : Some Hosting need Several Hours To Fully works (Like Hosgator.com ) <br> if in one day wildcard subdomain doesn't load, please ask your hosting for assistance</span>


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

<img src="https://lh6.googleusercontent.com/-a-7HC1jEXKk/VSEMfy2PV4I/AAAAAAAAA48/c1i_1BtNoio/w726-h588-no/wilcard-addon-domains.gif" />

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

						
<h2 style="margin-top:120px"> <span class="mw-headline"> Xml Sitemap plugin's Problem </span></h2>	
<a NAME="xmlsitemap"></a>		
<p>There's bad news for you, in the recent updates, most all xml sitemap plugin don't support subdomains anymore
<br>( <em> Actually, their purpose is to prevent link from external domain, unfortunately also has impact on  subdomain</em>)	
<br>The worst is Nothing I can do on my side... :'(
</p>				
<p>
Here's list xml plugins which Support subdomain :

<ul>
<ol><a href="https://wordpress.org/plugins/xml-sitemap-feed/">XML Sitemap & Google News feeds</a></ol>
<ol>...</ol>
<ul>

If You know another xml plugin which supports subdomain, don't hestitate to contact me ASAP
</p>			

<p>Popular Plugins Don't support subdomain</p>
<ul>
<ol><a href="https://wordpress.org/plugins/xml-sitemaps/">XML Sitemaps</a></ol>
<ol><a href="https://wordpress.org/plugins/wordpress-seo/">WordPress SEO by Yoast</a> (No need uninstall the plugin, just disable xml feature)</ol>
</ul>

<div style="margin-bottom:800px"></div>						
			
			
			
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
	
	function Visit($url){
	
		if ( function_exists('curl_init') ) {
			$agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
			$ch=curl_init();
			curl_setopt ($ch, CURLOPT_URL,$url );
			curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch,CURLOPT_VERBOSE,false);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($ch,CURLOPT_SSLVERSION,3);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST, FALSE);
			$page=curl_exec($ch);
			//echo curl_error($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if($httpcode>=200 && $httpcode<300) return true;
			else return false;
		}
		
		
		return null;
	}
	
	function generateRandomString($length = 10) {
		$characters = 'abcdefghijklmnopqrstuvwxyz';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}	
	
	
	
	
	
	static function upgrade() {
	
		$settings = get_option('mcs_subdomain_settings');
		$selected_categories = get_option('mcs_categories');
		
		if( MCS_DB_VERSION == $settings['db_version'] ) return;
		
		if( $settings['db_version'] == 1 ) {
			$old_selected_categories = $selected_categories;
			$new_selected_categories = array();
			foreach($old_selected_categories as $cat_id => $theme) {
				if( ! empty($theme) )
					$new_selected_categories[$cat_id] = array('theme' => $theme);
				else
					$new_selected_categories[$cat_id] = array();
			
			}
			
			$settings[ 'multicat' ]                 = 'by_id';
			$settings[ 'mode' ]                     = 1; 
			$settings[ 'single_category_url' ]	  	= 1;						
			$settings[ 'db_version']                = 2;
			
			update_option( 'mcs_categories' , $new_selected_categories ); 
			update_option( 'mcs_subdomain_settings' , $settings); 
		}	
	}
}




?>