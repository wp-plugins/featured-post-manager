<?php
	/*
	Plugin Name: Featured Post Manager
	Plugin URI: http://wordpress.org/extend/plugins/featured-post-manager/
	Description: Let's you select a specific category for featured posts, and manage those selections on the WordPress dashboard.
	Version: 1.0
	Author: Stephen Coley
	Author URI: http://coley.co

	Copyright 2010  Stephen Coley  (email : stephen@srcoley.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/
	
	/**
	 * Adds the fpmngr_option option to the wp_options table upon activation 
	 */
	function fpmngr_install() {
		if(!get_option("fpmngr_option")) {
			add_option("fpmngr_option", "");
		}
	}
	
	/**
	 * Handles form data sent by fpmngr
	 * select_cat mode: updates the fpmngr_option
	 * unfeature mode: deletes a row from wp_term_relationships
	 * feature mode: adds a row to wp_term_relationships
	 * embeds the style sheet.
	 */
	function fpmngr_head_init() {	
		$featured_category = get_option("fpmngr_option");
		if(isset($_GET['fpmngr_mode']) && $_GET['fpmngr_mode'] != "") {
			$mode = $_GET['fpmngr_mode'];
		}
		if($mode == "select_cat") {
			if(isset($_GET['fpmngr_cat']) && $_GET['fpmngr_cat'] != "" && is_numeric($_GET['fpmngr_cat'])) {
				update_option("fpmngr_option", $_GET['fpmngr_cat']);
			}
		} elseif($mode == "unfeature") {
			if(isset($_GET['fpmngr_id']) && $_GET['fpmngr_id'] != "" && is_numeric($_GET['fpmngr_id'])) {
				$post_id = $_GET['fpmngr_id'];
				global $wpdb;
				$wpdb->query("DELETE FROM $wpdb->term_relationships WHERE object_id = $post_id AND term_taxonomy_id = $featured_category;");
			}
		} elseif($mode == "feature") {
			if(isset($_GET['fpmngr_id']) && $_GET['fpmngr_id'] != "" && is_numeric($_GET['fpmngr_id'])) {
				$post_id = $_GET['fpmngr_id'];
				global $wpdb;
				$wpdb->query("INSERT INTO $wpdb->term_relationships VALUES ($post_id, $featured_category, 0);");
			}
		}
		
		echo '<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/featured-post-manager/fpmngr.css" />';
	}
	
	
	/**
	 * Embeds the fpmngr.js file
	 */
	function fpmngr_footer_init() {
		echo '<script type="text/javascript" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/featured-post-manager/fpmngr.js"></script>';
	}
	
	/**
	 * Creates the HTML to be added to the WP Dashboard
	 */
	function add_content_to_dashboard() {
		global $wpdb;
		$cats = $wpdb->get_results("SELECT $wpdb->terms.term_id, name FROM $wpdb->terms, $wpdb->term_taxonomy WHERE $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id AND $wpdb->term_taxonomy.taxonomy = 'category' ORDER BY name;");
		$cats_as_options = "";
		$featured = get_option("fpmngr_option");
		foreach($cats as $cat) {
			if($featured == $cat->term_id) {
				$cats_as_options .= "<option value=\"$cat->term_id\" selected='selected'>$cat->name</option>/n/r";
			} else {
				$cats_as_options .= "<option value=\"$cat->term_id\">$cat->name</option>/n/r";
			}
		}
		if($featured == "") {
			$dialog =<<< end_of_dialog
				<div id="fpmngr_div">
					<div id="dashboard_fpmngr" class="postbox ">
						<div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle"><span>Featured Post Manager</span></h3>
						<div class="inside">
							Select a Category to Manage: <select name="category">
								$cats_as_options
							</select><button id="select_category">Manage!</button>
						</div>
					</div>			
				</div>
end_of_dialog;
			echo $dialog;
		} else {
			$fpmngr_option = get_option("fpmngr_option");
			$cat = get_category($fpmngr_option);
			$featured_posts = $wpdb->get_results("SELECT object_id, post_title, ID FROM $wpdb->posts, $wpdb->term_relationships WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND term_taxonomy_id = ". $fpmngr_option ." AND post_type = 'post' ORDER BY wp_posts.post_date DESC;");
			$post_list = "";
			$fpm_ids = array();
			foreach($featured_posts as $post) {
				$post_list .= "<li>$post->post_title<span title='$post->ID'>Remove</span></li>";
				$fpm_ids[] = $post->ID;
			}
			$fpm_recent = $wpdb->get_results("SELECT post_title, ID FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' GROUP BY ID ORDER BY post_date DESC;");
			$fpm_recent_list = "";
			foreach($fpm_recent as $recent) {
				if(!in_array($recent->ID, $fpm_ids)) {
					$fpm_recent_list .= "<option value='$recent->ID'>$recent->post_title</option>\n\r";
				}
			}
			$dialog =<<< end_of_dialog
				<div id="fpmngr_div">
					<div id="dashboard_fpmngr" class="postbox ">
						<div class="handlediv" title="Click to toggle"><br></div><h3 class="hndle"><span>Featured Post Manager</span></h3>
						<div class="inside">
							Managed Category: <select name="category">
								$cats_as_options
							</select><button id="select_category" class="button">Manage!</button>
							<h4>Featured Posts</h4>
							<p>(hover to remove)</p>
							<ul id="fpmngr_list">
								$post_list
							</ul>
							<h4>Feature A Post</h4>
							<select name="fpm_post">
								$fpm_recent_list
							</select><button id="fpm_feature_post" class="button">Feature!</button>
						</div>
					</div>			
				</div>
end_of_dialog;
			echo $dialog;
		}
		
	}
	
	register_activation_hook(__FILE__, 'fpmngr_install');
	add_action('admin_head', 'fpmngr_head_init');
	add_action('admin_footer', 'fpmngr_footer_init');
	add_action('activity_box_end', 'add_content_to_dashboard');
	
?>
