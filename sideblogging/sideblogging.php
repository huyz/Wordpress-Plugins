<?php
/*
 * Plugin Name: SideBlogging
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Affiche des articles courts dans la sidebar du blog.
 * Version: 0.1
 * Author: Cédric Boverie
 * Author URI: http://www.boverie.eu/
*/
/* Copyright 2010  Cédric Boverie  (email : ced@boverie.eu)
 * this program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * Along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!class_exists('Sideblogging')):
class Sideblogging {

	const domain = 'sideblogging';

	function Sideblogging() {
		
		load_plugin_textdomain(self::domain,false,dirname(plugin_basename(__FILE__)).'/languages/');
	
		// Register custom post type
		add_action('init', array(&$this,'post_type_asides'));
		
		// Register Widget
		add_action('widgets_init', create_function('', 'return register_widget("SideBlogging_Widget");'));
	}
	
	// Add custom post type
	function post_type_asides() {
		register_post_type( 'asides',
			array(
				'label' => __('Asides',self::domain),
				'singular_label' => __('Aside',self::domain),
				'public' => true,
				'menu_position' => 5,
				'show_ui' => true,
				'capability_type' => 'post',
				'hierarchical' => false,
				'labels' => array(
					'add_new_item' => __('Add new aside',self::domain),
					'edit_item' => __('Edit aside',self::domain),
					'not_found' => __('No aside found',self::domain),
					'not_found_in_trash' => __('No aside found in trash',self::domain),
					'search_items' => __('Search asides',self::domain),
				),
				'supports' => array('title','editor'),
				'rewrite' => array('slug' => 'asides'),
			)
		);
	}
}
endif;

include dirname(__FILE__).'/sideblogging_Widget.php';

if(!isset($sideblogging))
	$sideblogging = new Sideblogging();