<?php 

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

delete_option( 'mcs_subdomain_settings' );
delete_option( 'mcs_categories' );
delete_option( 'mcs_remove_category' );


?>