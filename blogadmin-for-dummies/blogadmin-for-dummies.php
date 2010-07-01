<?php
/*
 * Plugin Name: BlogAdmin for dummies
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: L'administration d'un blog pour les nuls (ou quand l'administrateur du blog est un nul).
 * Version: 0.0.1
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

// Installation
register_activation_hook(__FILE__,'AdminDummies::activation');
// Désinstallation
register_deactivation_hook(__FILE__,'AdminDummies::deactivation');

if(!class_exists('AdminDummies')):
class AdminDummies {

	// ID des utilisateurs (conçu pour les administrateurs) à modérer
	var $dummies_admin = array(1);

	function __construct() {
		// Définir l'action que exécute la tâche planifiée
		add_action('admindummies_cron', array(&$this,'cron'));
		
		if(is_admin())
		{
			add_action('admin_init',array(&$this,'simplify_admin'));
		}
	}
	
	function activation() {
		wp_schedule_event(time(), 'daily', 'admindummies_cron');
	}
	
	function deactivation() {
		wp_clear_scheduled_hook('admindummies_cron');
	}
	
	function simplify_admin() {
		global $user_ID;
		global $pagenow;
		if(in_array($user_ID,$this->dummies_admin))
		{
			add_filter( 'pre_site_transient_update_core', create_function( '$a', "return null;" ) );
			add_action('admin_head', array(&$this,'remove_menu'));
			
			if(in_array($pagenow, array('update.php','ms-users.php','ms-themes.php','ms-options.php','ms-upgrade-network.php','update-core.php','plugins.php','plugin-install.php','plugin-editor.php','theme-editor.php','theme-install.php')))
			{
				WP_die('Cette fonction a été désactivée.');
			}
			
		}
	}
	
	function remove_menu() {
		global $menu;
		global $submenu;
		unset($menu[65]); // Remove Plugins
		unset($submenu['themes.php'][13]); // Remove Theme Editor
		unset($submenu['index.php'][10]); // Remove Update Core
		unset($submenu['ms-admin.php'][10]); // Remove MS admin Utilisateurs
		unset($submenu['ms-admin.php'][20]); // Remove MS admin Themes
		unset($submenu['ms-admin.php'][25]); // Remove MS admin Options
		unset($submenu['ms-admin.php'][30]); // Remove MS admin Update
		//echo '<pre>'.print_r($menu,true).'</pre>';
		//echo '<pre>'.print_r($submenu,true).'</pre>';
	}

	function cron() {
		global $wpdb;
		// Efface les révisions
		$wpdb->query("DELETE FROM $wpdb->postmeta JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->postmeta.post_id WHERE $wpdb->posts.post_type = 'revision'");
		$wpdb->query("DELETE FROM $wpdb->term_relationships JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->term_relationships.object_id = $wpdb->posts.post_id WHERE $wpdb->posts.post_type = 'revision'");
		$wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'revision'");
		// Optimise les tables
		$all_tables = $wpdb->get_results('SHOW TABLES',ARRAY_N);
		foreach ($all_tables as $table){
			$wpdb->query('OPTIMIZE TABLE '.$table[0]);
		}
	}
}
endif;

if(!isset($admindummies))
	$admindummies = new AdminDummies();