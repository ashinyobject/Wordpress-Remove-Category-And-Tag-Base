<?php

/*
Plugin Name: No Category And Tag Base
Plugin URI: http://silverscreen.in/tech/
Description: Removes /tag/ and /category/ fron Wordpress URLs, enabling URLs like http://example.com/technology. This plugin is based on the WP-No-Tag-Base and WP-No-Category-Base plugins. 
Silverscreen.in, the site for which this plugin was originally developed has over 6000 tags; and using the original plugin without modifications would have led to over 18000 redirect rules.
This plugin avoids this scenario, by adding category redirect rules first, and then adding a single rule for tag rewrite. It makes the assumption that any URL that does not fit a post, or a page or a category must be a tag.
Version: 0.1.0
Author: Silverscreen Media Inc.
Author URI: http://silverscreen.in/
*/

/*  Copyright 2015, Silverscreen Media Inc.
    This program is free software: you can redistribute it and/or modify 
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

class RemoveCategoryAndTagBase 
{
     private static $instance = null;
     public static function get_instance() 
     {
 
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
 
        return self::$instance;
 
    }
   
    private function __construct() 
    {
    	add_action('created_post_tag',array('RemoveCategoryAndTagBase','refresh'));
			add_action('edited_post_tag',array('RemoveCategoryAndTagBase','refresh'));
			add_action('delete_post_tag',array('RemoveCategoryAndTagBase','refresh'));
			add_action('created_category',array('RemoveCategoryAndTagBase','refresh'));
			add_action('edited_category',array('RemoveCategoryAndTagBase','refresh'));
			add_action('delete_category',array('RemoveCategoryAndTagBase','refresh'));
			add_action('init', array('RemoveCategoryAndTagBase','permastruct'));
			add_filter('query_vars', array('RemoveCategoryAndTagBase','addQueryVars'));
			add_filter('category_rewrite_rules', array('RemoveCategoryAndTagBase','categoryRewrite'));
			add_filter('tag_rewrite_rules', array('RemoveCategoryAndTagBase','tagRewrite'));
			add_filter('request',array('RemoveCategoryAndTagBase','redirectRequest')); 
		
    } 
    
    public static function install()
    {
    	self::refresh();
    }
    
    public static function uninstall()
    {
    	remove_filter('tag_rewrite_rules', array( 'RemoveCategoryAndTagBase', 'tagRewrite' ) );
    	remove_filter('category_rewrite_rules', array( 'RemoveCategoryAndTagBase', 'categoryRewrite' ) );
    	self::refresh();
    }
    
    public static function refresh()
    {
    	global $wp_rewrite;
			$wp_rewrite->flush_rules();
    }
    
    public static function addQueryVars($public_query_vars)
    {
    	$public_query_vars[] = 'category_redirect';
    	$public_query_vars[] = 'tag_redirect';
	    return $public_query_vars;
	  }
    	
    public static function permastruct()
    {
    	global $wp_rewrite;
    	$wp_rewrite -> extra_permastructs['category']['struct'] =  $wp_rewrite->front . '%category%';
    	$wp_rewrite -> extra_permastructs['post_tag']['struct'] = $wp_rewrite->front .'%post_tag%';
    }	
    public static function categoryRewrite($category_rewrite)
    {
    	global $wp_rewrite;
    	$category_rewrite = array();
			$categories = get_categories(array('hide_empty' => false));
			foreach ($categories as $category) {
				$category_nicename = $category -> slug;
				if ($category -> parent == $category -> cat_ID)
				{
					// recursive recursion
					$category -> parent = 0;
				}
				elseif ($category -> parent != 0)
				{
					$category_nicename = get_category_parents($category -> parent, false, '/', true) . $category_nicename;
				}
				$category_rewrite['(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
				$category_rewrite['(' . $category_nicename . ')/page/?([0-9]{1,})/?$'] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
				$category_rewrite['(' . $category_nicename . ')/?$'] = 'index.php?category_name=$matches[1]';
			}
			// Redirect support from Old Category Base
			
			$old_category_base = get_option('category_base') ? get_option('category_base') : 'category';
			$old_category_base = trim($old_category_base, '/');
			$category_rewrite[$old_category_base . '/(.*)$'] = 'index.php?category_redirect=$matches[1]';
		
			return $category_rewrite;
		}
		
		function tagRewrite($tag_rewrite) 
		{

			$tag_rewrite=array();
		
			$tags=get_tags(array('hide_empty'=>false, 'orderby'=>'slug','order'=>'DESC'));
		
			foreach($tags as $tag) {
		
				$tag_nicename = $tag->slug;
		 		$tag_rewrite['(\b'.$tag_nicename.'\b)/*.*$'] = 'index.php?tag=$matches[1]';
			}
		
		    // Redirect support from Old Category Base
			global $wp_rewrite;
			$old_tag_base = get_option('tag_base') ? get_option('tag_base') : 'tag';
		  $old_tag_base = trim($old_tag_base, '/');
		  $tag_rewrite[$old_tag_base . '/(.*)$'] = 'index.php?tag_redirect=$matches[1]';
		  return $tag_rewrite;

		}
		public static function redirectRequest($query_vars) 
		{

    if (isset($query_vars['category_redirect'])) 
    {
			$catlink = trailingslashit(get_option('home')) . user_trailingslashit($query_vars['category_redirect'], 'category');
			status_header(301);
			header("Location: $catlink");
			exit();
		}
        
    if (isset($query_vars['tag_redirect'])) 
    {
    	$tag = user_trailingslashit($query_vars['tag_redirect'], 'post_tag');
      $taglink = trailingslashit(get_option( 'home' )) . $tag;
      status_header(301);
			header("Location: $taglink");
			exit();
		}
		return $query_vars;

	}
		
}
$no_category_and_tag_base = RemoveCategoryAndTagBase::get_instance();
register_activation_hook( __FILE__, array( 'RemoveCategoryAndTagBase', 'install' ) );
register_deactivation_hook( __FILE__, array( 'RemoveCategoryAndTagBase', 'uninstall' ) );