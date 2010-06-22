<?php
/*
 * Plugin Name: Post Stats
 * Plugin URI: http://blog.boverie.eu/poststats-statistiques-des-articles/
 * Description: Statistics about posts length and reading time on dashboard and more.
 * Version: 1.0
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
// Installation
register_activation_hook(__FILE__,'PostStats::init_settings');
// Désinstallation
register_deactivation_hook(__FILE__,'PostStats::delete_settings');

if(!class_exists('PostStats')):
class PostStats {

	// PHP 4 Constructor
	function PostStats() {
		return __construct();
	}
	
	// Constructor
	function __construct() {
	
		$options = get_option('poststats');
	
		if(is_admin()) // Register admin action
		{
			// Register option settings
			add_action('admin_init', array(&$this,'register_settings'));
			// Register admin menu
			add_action('admin_menu', array(&$this,'menu'));
			// Add Settings link on plugin page
			add_filter('plugin_action_links_'.plugin_basename(__FILE__), array(&$this,'settings_link'));
			
			// Register dashboard widget
			if('on' == $options['dashboard'])
			{
				add_action('wp_dashboard_setup', array(&$this,'add_dashboard_widget'));
			}
		}
		else if('on' == $options['content']) // Not in administration and post stats on
		{
			// Ajoute le nombre de mots + estimation du temps de lecture avant le post
			add_filter('the_content', array(&$this,'postContent'));
			add_filter('the_excerpt',array(&$this,'excerptFix'),0);
		}

		/* Sidebar Widget */
		require_once(dirname(__FILE__).'/PostStats_Widget.php');
		add_action('widgets_init', create_function('', 'return register_widget("PostStats_Widget");'));
	}

	// Create the function to output the contents of the widget
	function diplay_stats() {
		global $wpdb;
		$options = get_option('poststats');
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

			$reading_time = PostStats::format_time($nb_mots_totaux/$options['speed']*60);

			echo '<p>';
			echo __('Total reading time:',POSTSTATS_TEXTDOMAIN).' '.$reading_time.'.';
			echo '</p>';
	}

	// Transforme un temps en secondes en un temps compréhensible par un humain
	function format_time($time) {

		if($time == 0) $time = 1; // Evite le temps de lecture nul

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
			   $reading[] = $nb.' '.PostStats::periodl10n($period,$nb);
		   }
	   }

		return implode(', ',$reading);
	}

	function periodl10n($period,$nb) {
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
	function postContent($content) {
		$options = get_option('poststats');
		$nb_words = str_word_count(strip_tags($content));
		$before_content = '<p class="poststats">';
		$before_content .= sprintf(__('This post has %d words.',POSTSTATS_TEXTDOMAIN),$nb_words);
		$before_content .= ' ';
		$before_content .= sprintf(__('It will take approximately %s for reading it.',POSTSTATS_TEXTDOMAIN),
									$this->format_time($nb_words/$options['speed']*60));
		$before_content .= '</p>';
		return $before_content.$content;
	}

	// On supprime les 2 premières phrase du excerpt (Ugly fix...)
	function excerptFix($content) {
		return substr($content,strpos($content,'.',strpos($content,'.')+1)+1);
	}

	// Setting link on plugin page
	function settings_link($links) {
		$settings_link = '<a href="options-general.php?page=poststats">'.__('Settings').'</a>';
		array_push($links,$settings_link);
		return $links;
	}
	
	/* Dashboard Widget */
	function add_dashboard_widget() {
			wp_add_dashboard_widget('example_dashboard_widget', __('Posts Statistics',POSTSTATS_TEXTDOMAIN), array(&$this,'diplay_stats'));
	}
	
	// Register settings
	function register_settings() {
		register_setting('poststats_settings','poststats',array(&$this,'filter_options'));
	}
	
	function filter_options($options) {
		$options['content'] = ($options['content'] == 'on') ? 'on' : 'off';
		$options['dashboard'] = ($options['dashboard'] == 'on') ? 'on' : 'off';
		$options['speed'] = intval($options['speed']);
		return $options;
	}
	
	// Initialisation des paramètres
	function init_settings() {
		$options = array();
		$options['content'] = 'off';
		$options['dashboard'] = 'on';
		$options['speed'] = '200';
		update_option('poststats',$options);
	}

	// Remove plugin settings
	function delete_settings() {
		delete_option('poststats');
	}

	function menu() {
	  add_options_page('PostStats', 'PostStats', 'manage_options', 'poststats', array('PostStats','options_page'));
	}

	/* Options page */
	function options_page() {
		echo '<div class="wrap">';
		echo '<h2>'.__('Posts Statistics',POSTSTATS_TEXTDOMAIN).'</h2>';

		echo '<form method="post" action="options.php">';
		settings_fields('poststats_settings');
		$options = get_option('poststats');
		echo '<table class="form-table">';
		
		echo '<tr valign="top">
		<th scope="row">
		<label for="poststats_content">Afficher les statistiques au début de chaque article</label>
		</th><td>
		<input type="checkbox" id="poststats_content" name="poststats[content]" '.checked('on',$options['content'],false).'" />
		</td></tr>';

		echo '<tr valign="top">
		<th scope="row">
		<label for="poststats_dashboard">Afficher le widget sur le dashboard</label>
		</th><td>
		<input type="checkbox" id="poststats_dashboard" name="poststats[dashboard]" '.checked('on',$options['dashboard'],false).'" />
		</td></tr>';

		$speed_tab = array(
			100 => __('Very slow reader',POSTSTATS_TEXTDOMAIN),
			150 => __('Slow reader',POSTSTATS_TEXTDOMAIN),
			200 => __('Average reader',POSTSTATS_TEXTDOMAIN),
			300 => __('Good reader',POSTSTATS_TEXTDOMAIN),
			700 => __('Excellent reader',POSTSTATS_TEXTDOMAIN),
		);
		
			echo '<tr valign="top">
		<th scope="row">
		<label for="poststats_speed">Vitesse de lecture</label>
		</th><td>
		<select name="poststats[speed]">';
		foreach($speed_tab as $speed => $label)
		{
			echo '<option '.selected($options['speed'],$speed,false).' value="'.$speed.'">'.$label.' ('.$speed.' '.__('words/minute',POSTSTATS_TEXTDOMAIN).')</option>';
		}
		echo '</select>
		</td></tr>';

		echo '</table>';

		echo '<p class="submit">
		<input type="submit" class="button-primary" value="'.__('Save Changes').'" />
		</p>';

		echo '</form>';
		echo '</div>';
	}
}
endif;

if(!isset($poststats))
$poststats = new PostStats();
?>