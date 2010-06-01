<?php
/*
 * Plugin Name: Post Stats
 * Plugin URI: http://www.boverie.eu/
 * Description: Statistiques sur la longueur des articles sur le tableau de bord.
 * Version: 0.2
 * Author: Cédric Boverie
 * Author URI: http://www.boverie.eu/
 */
/* Copyright 2010  Cédric Boverie  (email : ced@boverie.eu)
 * his program is free software; you can redistribute it and/or modify
 * t under the terms of the GNU General Public License, version 2, as
 * ublished by the Free Software Foundation.
 * his program is distributed in the hope that it will be useful,
 * ut WITHOUT ANY WARRANTY; without even the implied warranty of
 * ERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * NU General Public License for more details.
 * ou should have received a copy of the GNU General Public License
 * long with this program; if not, write to the Free Software
 * oundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
 
define('POSTSTATS_TEXTDOMAIN','post-stats');
load_plugin_textdomain(POSTSTATS_TEXTDOMAIN,false,dirname(plugin_basename(__FILE__)).'/languages/'); 
 
 // Create the function to output the contents of our Dashboard Widget
function PostStats_widget_function() {
	global $wpdb;
	$nb_mots_totaux = $wpdb->get_var("SELECT SUM(LENGTH(post_content) - LENGTH(REPLACE(post_content,' ',''))+1) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post'");
	$nb_mots_max = $wpdb->get_var("SELECT MAX(LENGTH(post_content) - LENGTH(REPLACE(post_content,' ',''))+1) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post'");
	$nb_mots_avg = $wpdb->get_var("SELECT AVG(LENGTH(post_content) - LENGTH(REPLACE(post_content,' ',''))+1) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post'");
	echo '<p>';
	echo __('Sum:',POSTSTATS_TEXTDOMAIN).' '.round($nb_mots_totaux).' '.__('words',POSTSTATS_TEXTDOMAIN).'<br />';
	echo __('Maximum:',POSTSTATS_TEXTDOMAIN).' '.round($nb_mots_max).' '.__('words',POSTSTATS_TEXTDOMAIN).'<br />';
	echo __('Average:',POSTSTATS_TEXTDOMAIN).' '.round($nb_mots_avg).' '.__('words',POSTSTATS_TEXTDOMAIN).'<br />';
	'</p>';
	
	$longest = $wpdb->get_row("
	SELECT ID,post_title,LENGTH(post_content) - LENGTH(REPLACE(post_content,' ',''))+1 AS NB_MOTS 
	FROM $wpdb->posts 
	WHERE post_status = 'publish' AND post_type = 'post' 
	GROUP BY ID
	ORDER BY NB_MOTS DESC LIMIT 1");
	
	$shortest = $wpdb->get_row("
	SELECT ID,post_title,LENGTH(post_content) - LENGTH(REPLACE(post_content,' ',''))+1 AS NB_MOTS 
	FROM $wpdb->posts 
	WHERE post_status = 'publish' AND post_type = 'post' 
	GROUP BY ID
	ORDER BY NB_MOTS LIMIT 1");
		
	
	echo '<p>';
	if(is_object($longest))
	echo __('Longest post:',POSTSTATS_TEXTDOMAIN).' <a href="'.get_permalink($longest->ID).'">'.$longest->post_title.'</a><br />';
	if(is_object($shortest))
	echo __('Shortest post:',POSTSTATS_TEXTDOMAIN).' <a href="'.get_permalink($shortest->ID).'">'.$shortest->post_title.'</a><br />';
	echo '</p>';
}

/* Dashboard Widget */
// Create the function use in the action hook
function PostStats_add_dashboard_widgets() {
	wp_add_dashboard_widget('example_dashboard_widget', __('Posts Statistics',POSTSTATS_TEXTDOMAIN), 'PostStats_widget_function');	
}
// Hook into the 'wp_dashboard_setup' action to register our other functions
add_action('wp_dashboard_setup', 'PostStats_add_dashboard_widgets' );


/* Sidebar Widget */
require_once(dirname(__FILE__).'/PostStats_Widget.php');
add_action('widgets_init', create_function('', 'return register_widget("PostStats_Widget");'));