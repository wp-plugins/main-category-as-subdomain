<?php
/*
  Plugin Name: Main Category As Subdomain
  Plugin URI: http://blogbintang.com
  Description: Change your categories as subdomains. Please Set up * (wild card) Subdomain in your host.
  Version: 1.0
  Author: Bintang Taufik
  Author URI: http://blogbintang.com
  
 * LICENSE
  Copyright 2014 Bintang Taufik  (email : bintangtaufik@gmail.com)

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
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
 
 
define( 'MCS_VERSION', 1 );
include_once('class.subdomain.php');
include_once('class.admin.subdomain.php');


if(	is_blog_admin() ) {
	function activation_plugin() {
		$setting = array();
		$settings[ 'bloginfo' ] = 1;	
		$settings[ 'home_url' ] = 0;	
		$settings[ 'redirect' ] = 1;
		$settings[ 'child_categories' ] = 'main_categories_subdomains';
		$settings[ 'recent_post' ] = 0;
		$settings[ 'using_index' ] = 0;
		$settings[ 'widget_recent_post' ] = 0;
		$settings[ 'widget_categories' ] = 0;
		$settings[ 'remove_category_permalink' ] = 0;
		$settings[ 'db_version' ] = 1.0;
		
		add_option( 'mcs_subdomain_settings', $settings );
		add_option( 'mcs_categories', array() );		
	
	}
	register_activation_hook(__file__, 'activation_plugin' );
	
	// Initialize ADMIN AREA
	new mcs_admin();	
	
} else {

	$mcs_execute = new mcs_subdomain();
	$mcs_execute->ACTION();
	$mcs_execute->FILTER();

	function mcs_home_url() {
		global $mcs_execute;
		if ( $mcs_execute->subdomain )
			return $mcs->bloginfo_url();
		else
			return home_url();
	}

}	
?>
