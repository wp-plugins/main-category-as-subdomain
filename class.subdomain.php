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
	

	/**
	 * User permission for Testing the plugin
	 * 0 = disable the features
	 * 1 = testing (only works for admin OR where user visits subdomain)
	 * 2 = run for everyone
	 */		
	private $run = 0;
	

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
				if( $parent_cat = $this->get_the_ancestor( $category ) ) {				
					$category_term_id  = $parent_cat->term_id;
				}
			}
		
			foreach ( $this->selected_categories as $id => $special_setting) {			
				if ( $id == $category_term_id ) {
					$this->subdomain = $category;
					
					if( is_array($special_setting) )
						$this->settings = array_merge($this->settings,$special_setting);
					$this->subdomain_home = $this->replace_to_subdomain_link( $category->slug , $this->home_url );
					
					break;
				}
			}
		} 
		
	
	}	
	
	
	
	public function ACTION() {
		
		/*
		* "current_user_can" using  "wp_get_current_user's function" in pluggable.php
		* and it is not loaded before the plugin loads.
		*/
		add_action( 'plugins_loaded', array( &$this, 'check_permission') );	
	
		add_action( 'wp', array( &$this, 'redirect') );
		
		if ( $this->subdomain ) {			
			/*
			* Set only certain category appears on theme's index.php
			*/
			add_action( 'pre_get_posts', array( &$this, 'pre_get_posts' ) );
			
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

		/* 
		* wp-includes/link-template.php 178 
		* change %category% value from permalink
		*/		
		//add_filter( 'post_link_category', array( &$this, 'change_category_permalink') , 10, 2 );
			
		add_filter( 'post_link', array( &$this, 'post_link') , 10, 2 );
			
		add_filter( 'allowed_redirect_hosts', array( &$this, 'redirect_after_comment') , 10, 2  );		
			
		if ( $this->subdomain ) {

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
				
			add_filter( 'widget_categories_args', array( &$this, 'widget_categories_args_filter' ), 10, 1 );
			add_filter( 'widget_posts_args', array( &$this, 'widget_recent_posts_args_filter' ), 10, 1 );



			/*
			* Special SETTINGs on subdomain
			*/
			
			if( isset( $this->settings['theme']) ) { 
				new mcs_change_theme($this->settings['theme']);
			}
			
			if ( isset( $this->settings['nav_menu']) ) {
				add_filter('theme_mod_nav_menu_locations',array( &$this, 'nav_menu') ,11,1);			
			}
				
				
			if ( isset( $this->settings['fix_relative_css'] ) ) {
				add_filter('template_directory_uri', array( &$this, 'template_directory_uri') , 5, 1  );				
			}
				
			if ( isset( $this->settings['front_page'] ) ) {
			
				/*no need to set up index with one category in home*/
				$this->settings[ 'using_index' ] = 1 ;
				
				add_filter('option_show_on_front', array( &$this, 'change_show_on_front') );				
				add_filter('option_page_on_front', array( &$this, 'change_page_on_front') );							
				
			}
				
			//disable redirect to default permalink
			//add_filter( 'redirect_canonical', array( &$this, 'non_redirect_canocial') );
			
		}
		
		
	}		
	
	public function check_permission() {

		if( $this->settings['mode'] ) {
			$this->run = 2;
			return;
		}
		
		if( current_user_can('activate_plugins') || $this->subdomain   ) {
			$this->run = 1;
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
	
		if ( ( $path ==  '/' || empty($path) ) && stripos( $url ,$this->subdomain_home) === false ) {
			$klsdf = $this->replace_to_subdomain_link( $this->subdomain->slug, $this->home_url );
			return $klsdf;
		} else {
			return $url;
		}
		
	}

	public function pre_get_posts( $query ) {
	
		if ( $this->subdomain ) {
		
			if ( 1 == $this->settings[ 'using_index' ] ) {
				if ( $query->is_home() && $query->is_main_query() ) {
					$query->set( 'cat', $this->subdomain->term_id );								
				}	
			}
			
			if ( isset( $this->settings['posts_per_page'] ) ) {
				$query->set( 'posts_per_page', $this->settings['posts_per_page'] );					
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
	/*
	* If the host is not allowed, then the redirect is to wp-admin on the siteurl instead
	* 
	* we have to add subdomain as allowed host
	*/
	public function redirect_after_comment( $wp_host , $lp_host ) {

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

			if( $this->settings['remove_category_permalink'] ) {
				
				/*
				Now We have to check that current request
				using rules without category or not,
				And the request what we are talking about should be "single" and its descendants (attachment, comment page, etc)
				by checking the real rules 
				*/				
				
				$without_category = true;
				
				if( 2 == $this->settings['remove_category_permalink'] ) {
				
					$request_match = $_SERVER['REQUEST_URI'] ;
					$request_match = trim($request_match,'/');
					$permalink_structure = get_option('permalink_structure'); 
					$permalink_structure = trim($permalink_structure,'/');
					/*
					* only for single post, and there's no way 'slash' count would less than permalink structure
					*/
					if( substr_count($request_match,'/') >= substr_count($permalink_structure,'/') ) {
					
						/*
						*  the code below copied from class.wp.php
						*/
					
						foreach( $rules as $match => $query ) {
							if( stripos( $query, 'category_name' ) !== false ) {
								if( preg_match("#^$match#", $request_match, $matches) || preg_match("#^$match#", urldecode($request_match), $matches) ) {
								
									// Trim the query of everything up to the '?'.
									$query_result = preg_replace("!^.+\?!", '', $query);

									// Substitute the substring matches into the query.
									$query_result = addslashes(WP_MatchesMapRegex::apply($query_result, $matches));

									// Parse the query.
									parse_str($query_result, $perma_query_vars);
						
									if ( ( isset($perma_query_vars['name']) || isset($perma_query_vars['p']) ) && isset( $perma_query_vars['category_name'] ) ) {
										$without_category = false;
										break;
									}
									
								}
							
							}
						}
						
					}
				
				}
				
				
				/*
				*update rules if we need to using rules without %category%
				*/
				if( $without_category ) {
					$rules_removed_category = $this->get_rules_array_removed_category_permalink();
					if($rules_removed_category )  
						$rules = $rules_removed_category;
				}				
				
			}
		

			
			//replace all feed rules
			$rules["feed/(feed|rdf|rss|rss2|atom)/?$"]	= "index.php?category_name=" . $this->subdomain->slug . "&feed=\$matches[1]";
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
	
		if ( $this->run ) {
			$result = $this->decide_subdomain_post($post->ID);
			
			//shorthand
			$r = $this->settings['remove_category_permalink'];
			if ( $r != 0 && $result && $result->subdomain ) {
			
				if($r == 1 || ( $r == 2 && $result->category->parent == 0 ) ) {
					return $this->remove_category_permalink($permalink);
				} elseif ( $r == 2 && $result->category->parent > 0 ) {
				
					$category_permalink = $result->category->slug;				
					if( ! $this->settings[ 'single_category_url' ] ) {				
						$category_permalink = get_category_parents( $result->category->parent , false, '/', true) . $category_permalink;
						$category_permalink = substr($category_permalink ,  strlen($result->subdomain->slug.'/') ) ;
					}
					
					return str_replace( '%category%',$category_permalink, $permalink );
				}
				
			}
		}		
		
		return  $permalink;
	}
	
	public function remove_category_permalink($permalink) {
	
		$permalink = str_replace( '%category%', '', $permalink );
		//$permalink = preg_replace('#/+#','/',$permalink);
		$permalink = str_replace("//","/", $permalink );
		return $permalink ;	
		
	}	
				
    public function post_link( $post_link , $post ) { 
	
		if ( $this->run ) {
			$result = $this->decide_subdomain_post($post->ID);
			
			if( $result->subdomain )		
				return $this->replace_to_subdomain_link( $result->subdomain->slug , $post_link );	
			
		}
		return $post_link;	
			
    }	
	
	public function decide_subdomain_post($post_id) {
	
		$categories = get_the_category($post_id);
		usort($categories, '_usort_terms_by_ID'); // order by ID

		$real_category = null; //real category selected by post
		$subdomain = null;
		if ($categories) {
		
			if ( ! $categories[0]->slug ) return false;	//something goes wrong here		
			
			//we are dealing with multicategories in post
			if( count($categories) > 1 && 'by_find' == $this->settings[ 'multicat' ] ) {

				foreach($categories as $category) {
				
					$parent_cat = $this->get_the_ancestor($category);
					if( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {
						$subdomain = $parent_cat; //$this->settings[ 'child_categories' ] = 'main_categories_subdomains' || 'none';
						$real_category = $category;
						
						$this->update_post_to_single_category( $post_id,$category->term_id );
						
						break;
							
					}
				}
				
			} else { 
				/*
				Either
				Order By ID multicategories $this->settings[ 'multicat' ]  = 'by_id';
				OR
				Only Has one Category
				*/
				$parent_cat = $this->get_the_ancestor( $categories[0] );				
				if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {
					$subdomain = $parent_cat; //$this->settings[ 'child_categories' ] = 'main_categories_subdomains' || 'none';	
					$real_category =  $categories[0];					
				}						
			}
						
		}

		if( 'all_subdomain' == $this->settings[ 'child_categories' ] ) {
			$subdomain = $real_category;
		}

		$result = new stdclass();
		$result->subdomain	= $subdomain;
		$result->category 	= $real_category;	

		return $result;
	
	}
	

	public function update_post_to_single_category( $post_id,$selected_cat ) {
	
		if ( $this->run == 2 && 'by_find' == $this->settings[ 'multicat' ] ) {
			  $my_post = array(
				  'ID'           => $post_id,
				  'post_category' => array($selected_cat)
			  );
			
			 wp_update_post( $my_post );
		
		}
	}
	
    public function category_link( $category_link, $term_id ) {	
	
		if ( $this->run ) {
		
			$category = get_category( $term_id );
			
			if (  0 == $category->parent) {
			
				if ( @array_key_exists( $term_id, $this->selected_categories ) ) {
					$link = $this->replace_to_subdomain_link( $category->slug, $this->home_url  );
					return $link;	
				}
				
			}
			
			if ( 'main_categories_subdomains' == $this->settings[ 'child_categories' ] && $category->parent != 0 ) {
			
				$parent_cat = $this->get_the_ancestor( $category );
				if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {
				
					$category_permalink = $category->slug;					
					if( ! $this->settings[ 'single_category_url' ] ) {
						
						$category_permalink = get_category_parents( $category->parent , false, '/', true) . $category_permalink;
						$category_permalink = substr($category_permalink ,  strlen($parent_cat->slug.'/') ) ;				
		
					}
					
					$category_base = get_option('category_base');
					if( empty($category_base) ) 
						$category_base = 'category';					
					
					return $this->replace_to_subdomain_link( $parent_cat->slug, $this->home_url . '/' . $category_base . '/'  . $category_permalink . '/' );				
				}					
			}
			
			if ( 'all_subdomain' == $this->settings[ 'child_categories' ] && $category->parent != 0 ) {
			
				$parent_cat = $this->get_the_ancestor( $category );
				if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {							
					return $this->replace_to_subdomain_link( $category->slug, $this->home_url  );
				}								
			}
		}
		
        return $category_link;
        
    }
	
	
	
	
    public function redirect() {
	
		if ( 0 == $this->settings[ 'redirect' ] || ! $this->run  ) {
			return;
		}		
		
		$requested_url = 'http' . (empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "") . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ;
		$requested_url = strtolower($requested_url);
		$requested_url = rtrim($requested_url,'/');		
		/*
		$_SERVER['REQUEST_URI'] can contain "?dfd=dfd"
		so better to parse it to get real path
		*/
		
		$parse_requested_url = parse_url( $requested_url );		

		/*
		* check main domain
		*/
		$is_main_domain = false;
		$parse_home_url = parse_url( $this->home_url );
		if( $parse_home_url['host'] == $parse_requested_url['host'] ) 
			$is_main_domain = true;
			
		/*
		* redirect only work for main domain or subdomain created by this plugin
		* so if user has subdomain outside this plugin will not be affected
		*/
		if ( $this->subdomain ||   $is_main_domain ) {	

			/*
			* Begin to verify redirection
			*/
			$redirect = false;		
			$real_url = null;
			$status = 302; //testing plugin		
			if( $this->run == 2 )
				$status = 301;
					
			if( is_single() ) {
			
				if( is_feed() ||  is_trackback() || is_attachment() ) {
					return;
				}
				
				global $post;
				
				$result = $this->decide_subdomain_post($post->ID);
				
				if( $result->subdomain) {
				
								
					$real_url = get_permalink($post->ID);			
					$real_url = rtrim($real_url,'/');
					
					/*
					* wordpress didn't give comment page detection
					* but we can use something like this
					*/
					$cpage = get_query_var( 'cpage' );
					if ( $cpage > 0 ){
						$real_url = $real_url . '/comment-page-' . $cpage;
					}
					
					/*
					* it's for splited post using <!--nextpage-->
					* is_paged() not working here 
					* cause default wp theme using something like this /post/4
					* rather than /post/page/4
					* we go to default
					*/
					$page_post = get_query_var( 'page', 1 ); 					
					if ( $page_post > 1 ) {
						$real_url = $real_url . '/' . $page_post;
					}				
					
				
				}
																									
			} elseif ( is_category() ) {
			
				if ( is_feed() )
					return;
			
				global $cat;
				$current_cat = get_category( $cat );
				$parent_cat = $this->get_the_ancestor( $current_cat );
				$scc = $this->settings[ 'child_categories' ];
					
				if( $scc == 'main_categories_subdomains' || $scc == 'all_subdomain') {
				
					if ( $parent_cat && @array_key_exists( $parent_cat->term_id , $this->selected_categories ) ) {							
							
						//yeah this category should be on subdomain
						$real_url = get_category_link($current_cat->term_id);
						$real_url = rtrim($real_url,'/');	
						
						if ( is_paged() ) {
							$paged = get_query_var( 'paged', 1 ); 
							$real_url = $real_url . '/page/' .  $paged ;
						}
						
					}					
				}
				
			} 			

			
			if( $real_url ) {				
				$parse_real_url = parse_url( $real_url );				
				if( $parse_real_url['host'] != $parse_requested_url['host'] || $parse_real_url['path'] != $parse_requested_url['path'] ) {
					$redirect = $real_url;	
				}			
			}
			
			
			if ( $redirect ) {
				//echo $redirect . ' ' . $status; 
				wp_redirect( $redirect, $status );
				exit();
			}
		
		}
		 
		 
    }	
	
	
	private function get_the_ancestor($category) {
		
		if( $category->parent == 0 ) return $category;
		
		$parent_cats = get_ancestors( $category->term_id,'category');
		if ( ! empty($parent_cats) ) {
			$root = end($parent_cats);
			$parent_cat = get_term_by( 'id', $root, 'category');
			if($parent_cat->parent == 0 )
				return $parent_cat;
		}
		
		return false;	//imposible to happen, but just in case
	}
	
	private function replace_to_subdomain_link( $subdomain_slug, $link ) {
	
		$link = str_replace( '/www.' , '/' , $link);
		$link = str_replace( 'https://' , 'https://' . $subdomain_slug . '.' , $link);
		$link = str_replace( 'http://' , 'http://' . $subdomain_slug . '.', $link);
		return $link;
		
	}
	
	public function change_show_on_front() {
	
		return 'page';
		
	}	
	
	public function change_page_on_front() {
	
		return $this->settings['front_page'] ;
		
	}

	public function template_directory_uri( $template_dir_uri ) {
	
		return $this->replace_to_subdomain_link( $this->subdomain->slug, $template_dir_uri );
		
	}	
	
	public function non_redirect_canocial() {
		return false;
	}
	
	public function nav_menu( $args  ) {
		if ( is_array( $this->settings['nav_menu'] ) )
			return $this->settings['nav_menu'];
			
		return  $args;
	}	

	
}
	
?>