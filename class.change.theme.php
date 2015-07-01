<?php

class mcs_change_theme {

	private $change_theme = false;

	public function __construct( $theme ) {
	
		if ( ! empty( $theme ) ) {
				$my_theme = wp_get_theme( $theme);
				if ( $my_theme->exists() )
						$this->change_theme = $my_theme;	
		}
					
		/**
		* The Theme filter
		*/			
		add_filter( 'stylesheet', array( &$this, 'stylesheet') );
		add_filter( 'template', array( &$this, 'template') );

    }
	
	
    public function stylesheet( $theme ) {

		if ( $this->change_theme) {
			return $this->change_theme->get_stylesheet() ;
		}
		
		return $theme;
    }
	
    public function template( $theme ) {
	
		if ( $this->change_theme ) {
			/*
			return parent theme if we are on child theme.
			*/
			return $this->change_theme->get_template() ;
		}
		
		return $theme;
    }	
}

?>