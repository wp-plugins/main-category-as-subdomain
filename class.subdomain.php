<?php

class mcs_subdomain {

	/**
	 * Real home url
	 */
	private $home_url;
	
	/**
	 * Whether we are on subdomain or not
	 * if subdomain it will return Category data object
	 */	
	public $subdomain = false;
	
	/**
	 * Whether we need to change theme or not
	 */	
	private $change_theme = false;
	
	/**
	 * Selected Categories have been chosen as Subdomain
	 */
	public $selected_categories = false;
	
	/**
	 * Subdomain Settings
	 */
	public $settings;

	/**
	 * Home Url In Subdomain
	 */	
	private $subdomain_home = '';
	

	public function __construct() {
		$this->home_url = home_url();
		$this->selected_categories 	= get_option( 'mcs_categories' );			
		$this->settings				= get_option( 'mcs_subdomain_settings' );		
		
        $this->decide_subdomain();
    }
	
	private function decide_subdomain() {		
		$url = getenv( 'HTTP_HOST' ) . getenv( 'REQUEST_URI' );

		$subdomains = explode( ".", $url );
		$subdomain	= $subdomains[0];
		
		// return false if not a category https://codex.wordpress.org/Function_Reference/get_category_by_slug
		$category =  get_category_by_slug( $subdomain );
		
		if ( $category && is_array( $this->selected_categories ) ) {
			$category_term_id = $category->term_id;
			
			//if child category is supposed to be subdomain, change to main category id
			if(	$this->settings[ 'child_categories' ] == 'all_subdomain' ) {
				if( $parent_cat = $this->get_first_ancestors_cat( $category_term_id ) ) {				
					$category_term_id  = $parent_cat->term_id;
				}
			}
		
			foreach ( $this->selected_categories as $id => $theme) {			
				if ( $id == $category_term_id ) {
					$this->subdomain = $category;
					
					if ( ! empty( $theme ) ) {
						$my_theme = wp_get_theme( $theme);
						if ( $my_theme->exists() )
							$this->change_theme = $theme;	
					}

					$this->subdomain_home = $this->replace_to_subdomain_link( $category->slug , $this->home_url );
					
					break;
				}
			}
		}	
	
	}	
	
	public function ACTION() {
		add_action( 'wp', array( &$this, 'redirect') );
		
		if ( $this->subdomain ) {			
			/*
			* Set only certain category appears on theme's index.php
			*/
			add_action( 'pre_get_posts', array( &$this, 'my_home_category' ) );
			
			//filter home url so only works in theme
			add_action( 'setup_theme', array( &$this, 'filter_home_url' ) );
		}
	}

	public function FILTER() {
		add_filter( 'category_link' , array( &$this, 'category_link') , 10, 2 );
		
		/* 
		* wp-includes/link-template.php 158 
		* remove %category% value from permalink
		*/
		add_filter( 'pre_post_link', array( &$this, 'remove_category_in_permalink') , 10, 2 );	
		
		add_filter( 'post_link', array( &$this, 'post_link') , 10, 2 );
		
		add_filter( 'allowed_redirect_hosts', array( &$this, 'redirect_after_comment') , 10, 2  );		
		
		if ( $this->subdomain ) {
			/**
			* (did't work in 3.9)
			*/
			//add_filter('rewrite_rules_array', array(&$this, 'rewrite_rules_array'));
			//add_filter( 'post_rewrite_rules', array(&$this,'post_rewrite_rules') );

			/**
			* Wordpress calls rules by get_option
			* We just need to change rules from option without saving in db
			*/	
			add_filter('option_rewrite_rules', array( &$this, 'change_rules_array') );

			/**
			* Replace paged link associated with subdomain 
			*/		
			add_filter( 'get_pagenum_link', array( &$this, 'change_page_link') );
			
			/**
			* Replace Blog Info with describtion in category
			*/
			add_filter( 'bloginfo', array( &$this, 'subdomain_bloginfo'), 10, 2 );				

			/**
			* Switch themplate in Subdomain
			*/			
			add_filter( 'stylesheet', array( &$this, 'changeThemplate') );
			add_filter( 'template', array( &$this, 'changeThemplate') );
			
			
			add_filter( 'widget_categories_args', array( &$this, 'widget_categories_args_filter' ), 10, 1 );
			add_filter( 'widget_posts_args', array( &$this, 'widget_recent_posts_args_filter' ), 10, 1 );	

		}
	}	
	
	public function filter_home_url() {
		/**
		* Replace Home URL into Subdomain URL
		*/
		if ( 1 == $this->settings[ 'home_url' ] ) {				
			add_filter( 'home_url', array( &$this, 'home_url_filter' ), 10, 4 );
			add_filter( 'bloginfo_url', array( &$this, 'bloginfo_url'),10, 2 );	
			//add_filter( 'site_url', array(&$this, 'change_bloginfo_link') , 10, 2 ); 
		}	
	
	}
	
	public function home_url_filter( $url, $path, $orig_scheme, $blog_id ) {
		//print_r($url);print_r($path);print_r($orig_scheme);print_r($blog_id);
		//print_r($path) . '--';
		if ( ( $path ==  '/' || empty($path) ) && stripos( $url ,$this->subdomain_home) === false ) {
			$klsdf = $this->replace_to_subdomain_link( $this->subdomain->slug, $this->home_url );
			return $klsdf;
		} else {
			return $url;
		}
	}

	public function my_home_category( $query ) {
		if ( 1 == $this->settings[ 'using_index' ] && $this->subdomain ) {
			if ( $query->is_home() && $query->is_main_query() ) {
				$query->set( 'cat', $this->subdomain->term_id );
			}	
		}
	}
	
	public function widget_categories_args_filter( $cat_args ) {
		//$cat_args used for wp_list_categories() function; http://codex.wordpress.org/Template_Tags/wp_list_categories
		if( 'main_categories_subdomains' == $this->settings[ 'child_categories' ] && 1 == $this->settings[ 'widget_categories' ]) {
			$cat_args['child_of'] = $this->subdomain->term_id;
		}
		return $cat_args;
	}
	
	public function widget_recent_posts_args_filter( $args ) {
		if( 1 == $this->settings[ 'widget_recent_post' ] ) {
			$args['cat'] = $this->subdomain->term_id;
		}
		return $args;
	}

	public function redirect_after_comment( $wp_host , $lp_host ) {
		/*
		* If the host is not allowed, then the redirect is to wp-admin on the siteurl instead
		* 
		* we have to add subdomain as allowed host
		*/
		if ( is_array( $wp_host) ) {
			$slug = explode( ".", $lp_host );
			$slug	= $slug[0];
			if ( get_category_by_slug( $slug ) )
				$wp_host[] =  $lp_host;
		}
		
		return $wp_host;
	}
	
	public function bloginfo_url($output, $show ) {
		if('url' == $show)
			return $this->$this->subdomain_home;
		
		if( $show == 'rdf_url' || $show == 'rss_url' || $show == 'rss2_url' || $show == 'atom_url') {
			return $this->replace_to_subdomain_link( $this->subdomain->slug, $output );
		}
			
		return $output;
	}
	
    /**
     * Change Rules
     * Replace rewrite rules if subdomain is category
     * @param array $rules wordpress from get_option('rewrite_rules');
     * @return array Final rewrite rules
     */
    public function change_rules_array( $rules ) {
        if ( is_array( $rules ) ) {	
		
			//update rules if we need to remove %category%
			if( 1 == $this->settings['remove_category_permalink'] ) {
				$rules_removed_category = $this->get_rules_array_removed_category_permalink();
				if($rules_removed_category )  
					$rules = $rules_removed_category;
			}
			
			//replace all feed rules
			$rules["feed/(feed|rdf|rss|rss2|atom)/?$"] = "index.php?category_name=" . $this->subdomain->slug . "&feed=\$matches[1]";
			$rules["(feed|rdf|rss|rss2|atom)/?$"]		= "index.php?category_name=" . $this->subdomain->slug . "&feed=\$matches[1]";
			
			if ( 0 == $this->settings[ 'using_index' ] ) {
				$rules2 = array();
				$rules2["$"]							= "index.php?category_name=" . $this->subdomain->slug;
				$rules2["page/?([0-9]{1,})/?$"]			= "index.php?category_name=" . $this->subdomain->slug . "&paged=\$matches[1]";
				$rules = $rules2 + $rules;
			}
				
        }
        
        return $rules;
    }
	
	public function get_rules_array_removed_category_permalink() {
		global $wp_rewrite;
		$real_permalink_structure = get_option('permalink_structure');		
		$mcs_remove_category = get_option('mcs_remove_category');
		$permalink_without_category = $this->remove_category_permalink($real_permalink_structure);
		if ( $mcs_remove_category['permalink'] != $permalink_without_category )  {
		
			/*
			* make sure permalink_structure has %category%
			*/
			if(stripos($real_permalink_structure,'%category%') === false)
				return false;
				
			/*
			* init() always call permalink_structure option
			* we can't edit using class property
			* we have to filter it beforehand
			*/
			add_filter( 'option_permalink_structure',  array( &$this, 'remove_category_permalink') );
			$wp_rewrite->matches = 'matches'; //see wp_rewrite_rules() in rewrite.php
			$wp_rewrite->init();
			$new_rewrite_rules 	=  $wp_rewrite->rewrite_rules();
			update_option('mcs_remove_category', array('permalink'=> $permalink_without_category,'rewrite_rules'=> $new_rewrite_rules) );
			return $new_rewrite_rules;
		}
		
		return $mcs_remove_category['rewrite_rules'];
	
	}
	
	public function change_page_link( $link ) {
		if ( $this->subdomain && stripos( $link, '://' . $this->subdomain->slug . '.') === false ) {
			$link = $this->replace_to_subdomain_link( $this->subdomain->slug, $link );
		}
		
		return $link;
	}
	
	
	public function subdomain_bloginfo( $result='', $show='' ) {
		if ( 1 == $this->settings[ 'bloginfo' ] ) {	
				switch ( $show ) {
					case 'name':
						$result = ucwords( $this->subdomain->name );
						break;
					case 'description':
						$result =  $this->subdomain->category_description;
						break;
					default: 
				}
		}
		
		return $result;
	}
	
	public function remove_category_in_permalink( $permalink ,$post ) {
		if ( $this->settings['remove_category_permalink'] == 1 ) {			
			$category = get_the_category($post->ID);
			if ($category) {
			
				usort($category, '_usort_terms_by_ID'); // order by ID		
				
				if ( ! $category[0]->slug ) return $permalink;
				
				if( 'none' != $this->settings[ 'child_categories' ] ) {
					$parent_cat = $this->get_first_ancestors_cat( $category[0]->term_id );
					if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {							
						return $this->remove_category_permalink($permalink);
					}						
				}
				

				if(@array_key_exists( $category[0]->term_id , $this->selected_categories )) {
					return $this->remove_category_permalink($permalink);
				}

				
			}
		}
		return $permalink;
	}
	
	public function remove_category_permalink($permalink) {
		$permalink = str_replace( '%category%', '', $permalink );
		//$permalink = preg_replace('#/+#','/',$permalink);
		$permalink = str_replace("//","/", $permalink );
		return $permalink ;		
	}
	
    public function post_link( $post_link , $post ) {        
         
        $category = get_the_category($post->ID);
		if ($category) {
		
			usort($category, '_usort_terms_by_ID'); // order by ID	
			
			if ( ! $category[0]->slug ) return $post_link;
			
			if( 'main_categories_subdomains' == $this->settings[ 'child_categories' ] ) {
				$parent_cat = $this->get_first_ancestors_cat( $category[0]->term_id );
				if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {							
					return $this->replace_to_subdomain_link( $parent_cat->slug , $post_link );
				}						
			}
			
			if( 'all_subdomain' == $this->settings[ 'child_categories' ] ) {		
				$parent_cat = $this->get_first_ancestors_cat( $category[0]->term_id );
				if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {							
					return $this->replace_to_subdomain_link( $category[0]->slug , $post_link );
				}				
			}
		   
			if ( @array_key_exists( $category[0]->term_id , $this->selected_categories ) ) {
				return $this->replace_to_subdomain_link( $category[0]->slug , $post_link );
			}
		}

        return $post_link;
    }
	
    public function category_link( $category_link, $term_id ) {	
		$category = get_category( $term_id );
		if (  0 == $category->parent) {
			if ( @array_key_exists( $term_id, $this->selected_categories ) ) {
				$link = $this->replace_to_subdomain_link( $category->slug, $this->home_url  );
				return $link;	
			}	
		}
		
 		if ( 'main_categories_subdomains' == $this->settings[ 'child_categories' ] && $category->parent != 0 ) {
			$parent_cat = $this->get_first_ancestors_cat( $category->term_id );
			if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {							
				return $this->replace_to_subdomain_link( $parent_cat->slug, $this->home_url .'/category/' . $category->slug .  '/' );
			}					
		}
		
 		if ( 'all_subdomain' == $this->settings[ 'child_categories' ] && $category->parent != 0 ) {
			$parent_cat = $this->get_first_ancestors_cat( $category->term_id );
			if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {							
				return $this->replace_to_subdomain_link( $category->slug, $this->home_url  );
			}								
		}
		
        return $category_link;
        
    }
	
    public function redirect() {
		if ( 0 == $this->settings[ 'redirect' ] ) {
			return;
		}
		
		$redirect = false;
		$status = 302;
		if ( ! $this->subdomain && empty($this->subdomain_home) ) {
			//if category in post has been as subdomain, we have to move the post to subdomain
			if( is_single() ) {			
				global $post;
				$category_posts = get_the_category( $post->ID );
				usort($category_posts, '_usort_terms_by_ID'); // order by ID
				
				if ( 0 == $category_posts[0]->parent && @array_key_exists( $category_posts[0]->term_id, $this->selected_categories ) ) {
					//$redirect = 'http' . (empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "") . '://' .  $category_posts[0]->slug  .'.' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
					$redirect = get_permalink($post->ID);
					$status = 301 ;
				}
				
				if ( 'main_categories_subdomains' == $this->settings[ 'child_categories' ] && 0 != $category_posts[0]->parent ) {
					$parent_cat = $this->get_first_ancestors_cat($category_posts[0]->term_id);
					if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {
						//$redirect = 'http' . (empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "") . '://' .  $parent_cat->slug  .'.' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; 
						$redirect = get_permalink($post->ID);
						$status = 301 ;
					}				
				}
				
				if( 'all_subdomain' == $this->settings[ 'child_categories' ] ) {		
					$parent_cat = $this->get_first_ancestors_cat( $category[0]->term_id );
					if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {							
						$redirect = get_permalink($post->ID);
						$status = 301 ;
					}				
				}
				
				
			} elseif ( is_category() ) {
				global $cat;
				$current_cat = get_category( $cat );
				
				if ( 0 == $current_cat->parent ) {					
					if ( @array_key_exists( $cat,  $this->selected_categories ) ) {
						//$redirect = 'http' . (empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "") . '://' .  $current_cat->slug .'.' . $_SERVER['HTTP_HOST']; 
						$redirect = $this->replace_to_subdomain_link( $current_cat->slug, $this->home_url  );
						$status = 301 ;
					}
				}
				
				if ( 'main_categories_subdomains' == $this->settings[ 'child_categories' ] && 0 != $current_cat->parent ) {
					$parent_cat = $this->get_first_ancestors_cat( $current_cat->term_id );
					if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {
						//$redirect = 'http' . (empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "") . '://' .  $parent_cat->slug .'.' . $_SERVER['HTTP_HOST'] . '/category/'. $current_cat->slug; 
						$redirect = $this->replace_to_subdomain_link( $parent_cat->slug, $this->home_url .'/category/' . $current_cat->slug . '/'  );
						$status = 301 ;
					}
				}
				
				if ( 'all_subdomain' == $this->settings[ 'child_categories' ] && $current_cat->parent != 0 ) {
					$parent_cat = $this->get_first_ancestors_cat( $current_cat->term_id );
					if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {							
						$redirect = $this->replace_to_subdomain_link( $current_cat->slug, $this->home_url  );
						$status = 301 ;
					}								
				}							
			
			} 			
		}
		
		
		if ( $redirect ) {
			wp_redirect( $redirect, $status );
			exit();
		}
		 
		 
    }

    public function changeThemplate( $theme ) {
		if ( $this->change_theme ) {
			return $this->change_theme ;
		}
		
		return $theme;
    }
	
	private function get_first_ancestors_cat( $current_cat_id ) {
		$parent_cats = get_ancestors( $current_cat_id,'category');
		if ( ! empty($parent_cats) ) {
			$root = end($parent_cats);
			$parent_cat = get_term_by( 'id', $root, 'category');
			if($parent_cat->parent == 0 )
				return $parent_cat;
		}	
		return false;
	}
	
	private function replace_to_subdomain_link( $subdomain_slug, $link ) {
		$link = str_replace( 'www.' , '' , $link);
		$link = str_replace( 'https://' , 'https://' . $subdomain_slug . '.' , $link);
		$link = str_replace( 'http://' , 'http://' . $subdomain_slug . '.', $link);
		return $link;
	}
}
?>