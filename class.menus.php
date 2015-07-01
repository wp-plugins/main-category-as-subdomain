<?php

class mcs_menus_location {

	private $verify = false;
	private $subdomains = null;
	private $selected_subdomain = 0;
	
	public function __construct() {	
	
		$this->subdomains = get_option( 'mcs_categories' );
		add_action('plugins_loaded', array( &$this, 'verify_request') );	
		add_action('setup_theme', array( &$this, 'setup_theme') );	
		add_action('init', array( &$this, 'update_nav_menu') );			
		add_action('admin_menu', array( &$this, 'add_menus_appearance') );	
    }	
	
	public function add_menus_appearance() 	{
		add_submenu_page('themes.php', 'Customizer', 'Menus Subdomain', 'manage_options', 'mcs_menus', array( &$this, 'menus_form') );
	}

	public function menus_form() {
		$this->subdomain_list_form();
		$this->menu_location_form();
	}
	
	public function setup_theme() {
		if ( isset ( $this->selected_subdomain['theme']  ) && $this->verify )
				new mcs_change_theme($this->selected_subdomain['theme']);	
	}
	
	public function update_nav_menu() {
		if ( $this->verify && isset( $_GET['action']) && $_GET['action'] == 'update' && isset( $_POST['menu-locations'] )  ) {
			$selected_subdomain_id =  $this->selected_subdomain['term_id'];			
			$old_value = $this->subdomains[ $selected_subdomain_id ];
			$old_value['nav_menu'] = $_POST['menu-locations'];
			$this->selected_subdomain['nav_menu'] = $_POST['menu-locations'];			
			$this->subdomains[ $selected_subdomain_id ] = $old_value;
			update_option('mcs_categories', $this->subdomains);					
		} 
	}
	
	public function subdomain_list_form() {
	
		$subdomains = $this->subdomains;
		$selected_subdomain_id =  $this->selected_subdomain['term_id'];	
?>

		<div id="menu-locations-wrap">
			<h3>Manage Menu's Locations For Subdomain</h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'themes.php?page=mcs_menus' )  ); ?>">
					
				<table class="widefat fixed" id="menu-locations-table">
					<thead>

					</thead>
					<tbody class="menu-locations">
						<tr class="menu-locations-row">
							<td class="menu-location-title"><label for="subdomain"><?php _e( 'Select Subdomain' ); ?></label></td>
							<td class="menu-location-menus">
								<select name="menu-subdomain" id="locations">
									<option value="0"><?php printf( '&mdash; %s &mdash;', esc_html__( 'Select a Subdomain' ) ); ?></option>
									<?php foreach ( get_categories() as $category ) : ?>
										<?php $subdomain = isset( $subdomains[ $category->term_id ]  )  ?  $category  : false; ?>
										<?php if($subdomain) : ?>
										<option  value="<?php echo $subdomain->term_id; ?>" <?php selected( $subdomain->term_id, $selected_subdomain_id ); ?>  >
											<?php echo $subdomain->name;?>
										</option>
										<?php endif ; ?>
									<?php endforeach; ?>
								</select>
							</td><!-- .menu-location-menus -->
							<td class="menu-location-title"><label for="subdomain"><?php submit_button( __( 'Select' ), 'primary left', 'nav-menu-locations', false ); ?></label></td>							
						</tr><!-- .menu-locations-row -->
					</tbody>
				</table>	
				<?php wp_nonce_field( 'menu-locations-sudomains' ); ?>
				
				
			</form>	
<?php
	}
	
	
	public function menu_location_form() {
		if ( ! $this->verify ) return; 
		if ( ! $this->selected_subdomain ) return ;		
		if ( ! current_theme_supports( 'menus' ) ) {
			_e( "The Theme is not Support Menus");
			return;
		}

		$locations = get_registered_nav_menus();
		$nav_menus = wp_get_nav_menus()	;
		$name_category = $this->selected_subdomain['name'];
		$theme =  isset( $this->selected_subdomain['theme'])  ?  $this->selected_subdomain['theme'] : wp_get_theme()->get_stylesheet() ;
		$menu_locations = isset($this->selected_subdomain['nav_menu']) ? $this->selected_subdomain['nav_menu'] : null ;
?>		
	<div id="menu-locations-wrap">
		<h3><?php _e( 'Manage Location For Subdomain : ' ); ?> <span style="color:red"><?php echo $name_category ?></span> <?php _e( 'With Theme : ' ); ?><span style="color:red"><?php echo $theme; ?></span>  </h3>
		<form method="post" action="<?php echo esc_url( add_query_arg( array( 'action' => 'update' ) , admin_url( 'themes.php?page=mcs_menus' ) ) ) ; ?>">
			<table class="widefat fixed" id="menu-locations-table">
				<thead>
				<tr>
					<th scope="col" class="manage-column column-locations"><?php _e( 'Theme Location' ); ?></th>
					<th scope="col" class="manage-column column-menus"><?php _e( 'Assigned Menu' ); ?></th>
				</tr>
				</thead>
				<tbody class="menu-locations">
				<?php foreach ( $locations as $_location => $_name ) { ?>
					<tr class="menu-locations-row">
						<td class="menu-location-title"><label for="locations-<?php echo $_location; ?>"><?php echo $_name; ?></label></td>
						<td class="menu-location-menus">
							<select name="menu-locations[<?php echo $_location; ?>]" id="locations-<?php echo $_location; ?>">
								<option value="0"><?php printf( '&mdash; %s &mdash;', esc_html__( 'Select a Menu' ) ); ?></option>
								<?php foreach ( $nav_menus as $menu ) : ?>
									<?php $selected = isset( $menu_locations  ) && $menu_locations [$_location] == $menu->term_id; ?>
									<option <?php if ( $selected ) echo 'data-orig="true"'; ?> <?php selected( $selected ); ?> value="<?php echo $menu->term_id; ?>">
										<?php echo wp_html_excerpt( $menu->name, 40, '&hellip;' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<div class="locations-row-links">
								<?php if ( isset( $menu_locations  ) && 0 != $menu_locations[$_location] ) : ?>
								<span class="locations-edit-menu-link">
									<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => $menu_locations[$_location] ), admin_url( 'nav-menus.php' ) ) ); ?>">
										<span aria-hidden="true"><?php _ex( 'Edit', 'menu' ); ?></span><span class="screen-reader-text"><?php _e( 'Edit selected menu' ); ?></span>
									</a>
								</span>
								<?php endif; ?>
								<span class="locations-add-menu-link">
									<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => 0, 'use-location' => $_location ), admin_url( 'nav-menus.php' ) ) ); ?>">
										<?php _ex( 'Use new menu', 'menu' ); ?>
									</a>
								</span>
							</div><!-- .locations-row-links -->
						</td><!-- .menu-location-menus -->
					</tr><!-- .menu-locations-row -->
				<?php } // foreach ?>
				</tbody>
			</table>
			<p class="button-controls"><?php submit_button( __( 'Save Changes' ), 'primary left', 'nav-menu-locations', false ); ?></p>
			<?php wp_nonce_field( 'menu-locations-sudomains' ); ?>
			<input type="hidden" name="menu-subdomain" id="nav-menu-meta-object-id" value="<?php echo $this->selected_subdomain['term_id'] ?>" />
		</form>
	</div><!-- #menu-locations-wrap -->		
		
<?php	
	}
	
	
	
	
	public function verify_request() {
			
		if ( isset($_POST['_wpnonce']) && wp_verify_nonce( $_POST['_wpnonce'], 'menu-locations-sudomains' ) && current_user_can('edit_theme_options') ) {
		
			$this->verify = true;
			if ( isset($_POST['menu-subdomain']) && $_POST['menu-subdomain'] != 0 ) {
				$id_subdomain = $_POST['menu-subdomain'];
				$cat = get_term( $id_subdomain, 'category' );
				$subdomain = $this->subdomains[$id_subdomain ];
				$this->selected_subdomain = array_merge($subdomain,(array)$cat);				
			}
		
			
		}
		
	}

	

}




?>