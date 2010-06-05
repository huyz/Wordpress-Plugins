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

 // Create the function to output the contents of the widget
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
       'years' => 86400*365,
       'months' => 86400*31,
       'days' => 86400,
       'hours' => 3600,
       'minutes' => 60,
       'secondes' => 1,
   );

   $reading = array();

   foreach($periods as $period => $duration)
   {
       if($time >= $duration)
       {
           $nb = floor($time/$duration);
           $time -= $nb*$duration;
           $reading[] = $nb.' '.PostStats_periodl10n($period,$nb);
       }
   }

    return implode(', ',$reading);
}

function PostStats_periodl10n($period,$nb) {
    switch($period) {
        case 'years':
            return _n('year','years',$nb,POSTSTATS_TEXTDOMAIN);
        break;
        case 'months':
            return _n('month','months',$nb,POSTSTATS_TEXTDOMAIN);
        break;
        case 'days':
            return _n('day','days',$nb,POSTSTATS_TEXTDOMAIN);
        break;
        case 'hours':
            return _n('hour','hours',$nb,POSTSTATS_TEXTDOMAIN);
        break;
        case 'minutes':
            return _n('minute','minutes',$nb,POSTSTATS_TEXTDOMAIN);
        break;
        case 'secondes':
            return _n('seconde','secondes',$nb,POSTSTATS_TEXTDOMAIN);
        break;
    }
}

// Ajoute les statistiques au début de chaque post
function PostStats_postContent($content) {
    $nb_words = str_word_count(strip_tags($content));
    $before_content = '<p class="poststats">';
    $before_content .= sprintf(__('This post has %d words.'),$nb_words);
    $before_content .= ' ';
    $before_content .= sprintf(__('It will take approximately %s for reading it.'),
                                PostStats_format_time($nb_words/POSTSTATS_READINGSPEED*60));
    $before_content .= '</p>';
    return $before_content.$content;
}

// On supprime les 2 premières phrase du excerpt (Ugly fix...)
function PostStats_excerptFix($content) {
    return substr($content,strpos($content,'.',strpos($content,'.')+1)+1);
}

/* Dashboard Widget */
// Create the function use in the action hook
function PostStats_add_dashboard_widgets() {
    if(get_option('poststats_dashboard') == 'on')
        wp_add_dashboard_widget('example_dashboard_widget', __('Posts Statistics',POSTSTATS_TEXTDOMAIN), 'PostStats_widget_function');
}

/* Sidebar Widget */
require_once(dirname(__FILE__).'/PostStats_Widget.php');
add_action('widgets_init', create_function('', 'return register_widget("PostStats_Widget");'));

// Ajoute le nombre de mots + estimation du temps de lecture avant le post
if(get_option('poststats_content') == 'on')
{
    add_filter('the_content', 'PostStats_postContent');
    add_filter('the_excerpt','PostStats_excerptFix',0);
}

if(is_admin()) // Register admin action
{
    // Register dashboard widget
    add_action('wp_dashboard_setup', 'PostStats_add_dashboard_widgets');
    
    // Register admin menu
    add_action('admin_menu', 'PostStats_menu');

    // Register option settings
    add_action('admin_init', 'PostStats_register_settings');
}

function PostStats_register_settings() {
    register_setting('poststats_settings', 'poststats_content');
    register_setting('poststats_settings', 'poststats_dashboard');
}

function PostStats_menu() {
  add_options_page('PostStats', 'PostStats', 'manage_options', 'poststats', 'PostStats_options');
}

function PostStats_options() {
    echo '<div class="wrap">';
    echo '<h2>PostStats</h2>';

    echo '<form method="post" action="options.php">';
    settings_fields('poststats_settings');
    echo '<table class="form-table">';

    if(get_option('poststats_content') == 'on') $value = 'checked="checked"';
    else $value = '';
    
    echo '<tr valign="top">
    <th scope="row">
    <label for="poststats_content">Afficher les statistiques au début de chaque article</label>
    </th><td>
    <input type="checkbox" id="poststats_content" name="poststats_content" '.$value.'" />
    </td></tr>';
    
    if(get_option('poststats_dashboard') == 'on') $value = 'checked="checked"';
    else $value = '';

    echo '<tr valign="top">
    <th scope="row">
    <label for="poststats_dashboard">Afficher le widget sur le dashboard</label>
    </th><td>
    <input type="checkbox" id="poststats_dashboard" name="poststats_dashboard" '.$value.'" />
    </td></tr>';

    echo '</table>';

    echo '<p class="submit">
    <input type="submit" class="button-primary" value="'.__('Save Changes').'" />
    </p>';

    echo '</form>';
    echo '</div>';
}
?>