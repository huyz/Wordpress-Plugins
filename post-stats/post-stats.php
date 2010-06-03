<?php
/*
 * Plugin Name: Post Stats
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
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
define('POSTSTATS_READINGSPEED',200);

load_plugin_textdomain(POSTSTATS_TEXTDOMAIN,false,dirname(plugin_basename(__FILE__)).'/languages/'); 
 
 // Create the function to output the contents of our Dashboard Widget
function PostStats_widget_function() {
	global $wpdb;
	$nb_mots_totaux = $wpdb->get_var("SELECT SUM(LENGTH(post_content) - LENGTH(REPLACE(post_content,' ',''))+1) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post'");
	$nb_mots_avg = $wpdb->get_var("SELECT AVG(LENGTH(post_content) - LENGTH(REPLACE(post_content,' ',''))+1) FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post'");
	
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
	echo __('Sum:',POSTSTATS_TEXTDOMAIN).' '.round($nb_mots_totaux).' '.__('words',POSTSTATS_TEXTDOMAIN).'<br />';
	echo __('Minimum:',POSTSTATS_TEXTDOMAIN).' '.round($shortest->NB_MOTS).' '.__('words',POSTSTATS_TEXTDOMAIN).'<br />';
	echo __('Maximum:',POSTSTATS_TEXTDOMAIN).' '.round($longest->NB_MOTS).' '.__('words',POSTSTATS_TEXTDOMAIN).'<br />';
	echo __('Average:',POSTSTATS_TEXTDOMAIN).' '.round($nb_mots_avg).' '.__('words',POSTSTATS_TEXTDOMAIN).'<br />';
	'</p>';

	echo '<p>';
	if(is_object($longest))
	echo __('Longest post:',POSTSTATS_TEXTDOMAIN).' <a href="'.get_permalink($longest->ID).'">'.$longest->post_title.'</a><br />';
	if(is_object($shortest))
	echo __('Shortest post:',POSTSTATS_TEXTDOMAIN).' <a href="'.get_permalink($shortest->ID).'">'.$shortest->post_title.'</a><br />';
	echo '</p>';

        $reading_time = PostStats_format_time($nb_mots_totaux/POSTSTATS_READINGSPEED*60);
        //$reading_time = PostStats_format_time(86400*1000+1);

        echo '<p>';
        echo __('Total reading time: ',POSTSTATS_TEXTDOMAIN).$reading_time;
        echo '</p>';

}
// Transforme un temps en secondes en un temps compréhensible par un humain
function PostStats_format_time($time) {

   $periods = array(
       __('years',POSTSTATS_TEXTDOMAIN) => 86400*365,
       __('months',POSTSTATS_TEXTDOMAIN) => 86400*31,
       __('days',POSTSTATS_TEXTDOMAIN) => 86400,
       __('hours',POSTSTATS_TEXTDOMAIN) => 3600,
       __('minutes',POSTSTATS_TEXTDOMAIN) => 60,
       __('secondes',POSTSTATS_TEXTDOMAIN) => 1,
   );

   $reading = array();

   foreach($periods as $period => $duration)
   {
       if($time >= $duration)
       {
           $nb = floor($time/$duration);
           $time -= $nb*$duration;
           $reading[] = $nb.' '.$period;
       }
   }

    return implode(', ',$reading);
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