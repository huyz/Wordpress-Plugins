<?php
/*
 * Plugin Name: Restrict Access
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Oblige à résoudre une énigme avant d'autoriser l'accès au blog.
 * Version: 0.2
 * Author: Cédric Boverie
 * Author URI: http://www.boverie.eu/
*/
/* Copyright 2010  Cédric Boverie  (email : ced@boverie.eu)
 * this program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * this program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * Along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
// Installation
register_activation_hook( __FILE__, array('restrictAccess','activation'));
// Désinstallation
register_deactivation_hook( __FILE__, array('restrictAccess','deactivation'));

if(!class_exists('restrictAccess')):
class restrictAccess {

	/* Constructeur */
	public function __construct() {
		if(is_admin())
		{
			// Register option settings
			add_action('admin_init', array(&$this,'register_settings'));
			// Register admin menu
			add_action('admin_menu', array(&$this,'menu'));
		}
		else
		{
			// Restreindre l'accès au blog
			add_action('template_redirect', array(&$this,'init'));
			// Et au flux
			add_action('do_feed', array(&$this,'init'), 1);
		}
	}
	
	// Register settings
	function register_settings() {
		register_setting('restrictAccess_settings','restrictAccess',array(&$this,'filter_options'));
	}
	
	function filter_options($opts) {
		$options['user'] = intval($opts['user']);
		$options['nbquestions'] = intval($opts['nbquestions']);
		$options['feed'] = (in_array($opts['feed'], array('on','off','onlytitle'))) ? $opts['feed'] : 'off';
		for($i = 0 ; $i < $options['nbquestions'] ; $i++)
		{
			$options['questions'][$i] = $opts['questions'][$i];
			$options['reponses'][$i] = $opts['reponses'][$i];
		}
		return $options;
	}

	// Options page
	function menu() {
	  add_options_page('Restrict Access', 'Restrict Access', 'manage_options', 'restrictaccess', array(&$this,'options_page'));
	}
	
	function options_page() {
		echo '<div class="wrap">';
		echo '<div id="icon-users" class="icon32"></div><h2>Restrict Access</h2>';

		echo '<h3>Configuration générale</h3>';
		echo '<form method="post" action="options.php">';
		settings_fields('restrictAccess_settings');
		$options = get_option('restrictAccess');
		//print_r($options);
		
		echo '<table class="form-table">';
		echo '<tr valign="top">
		<th scope="row">
		<label for="restrictAccess_user">Utilisateur utilisé pour la connexion anonyme</label>
		</th><td>';
		wp_dropdown_users('id=restrictAccess_user&name=restrictAccess[user]&selected='.$options['user']);
		echo '</td></tr>';
		
		$values = array('on' => 'Normal','onlytitle' => 'Seulement les titres','off' => 'Désactiver');
		echo '<tr valign="top">
		<th scope="row">
		<label for="restrictAccess_feed">Flux RSS</label>
		</th><td>
		<select id="restrictAccess_feed" name="restrictAccess[feed]">';
		foreach($values as $value => $label)
			echo '<option value="'.$value.'" '.selected($value,$options['feed']).'>'.$label.'</option>';
		echo '</select>';
		echo '</td></tr>';
		echo '</table>';
		
		echo '<h3>Questions/Réponses</h3>';
		
		echo '<table class="form-table">';
		echo '<tr valign="top">
		<th scope="row">
		<label for="restrictAccess_nbquestions">Nombre de questions</label>
		</th><td>';
		echo '<select name="restrictAccess[nbquestions]" id="restrictAccess_nbquestions">';
		foreach(range(1,20) as $r)
			echo '<option value="'.$r.'" '.selected($r,$options['nbquestions']).'>'.$r.'</option>';
		echo '</select>';
		echo '</td></tr>';
		echo '</table>';

		echo '<table class="widefat">
		<thead><tr>
			<th>#</th>
			<th>Questions</th>
			<th>Réponses</th>
		</tr></thead><tbody>';

		for($i = 0 ; $i < $options['nbquestions'] ; $i++)
		{
			echo '<tr>';
			echo '<td>'.($i+1).'</td>';
			echo '<td><input type="text" name="restrictAccess[questions]['.$i.']" value="'.$options['questions'][$i].'" size="80%" /></td>';
			echo '<td><input type="text" name="restrictAccess[reponses]['.$i.']" value="'.$options['reponses'][$i].'" size="80%" /></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
		
		echo '<p class="submit">
		<input type="submit" class="button-primary" value="'.__('Save Changes').'" />
		</p>';

		echo '</form>';
		echo '</div>';
	}
	
	/* Connexion automatique sur le compte d'un utilisateur */
	static function autoLogin()	{
		$options = get_option('restrictAccess');
		$user_info = get_userdata($options['user']);
		wp_set_current_user($user_info->ID, $user_info->user_login);
		wp_set_auth_cookie($user_info->ID);
		do_action('wp_login', $user_info->user_login);
	}

	/* Ajout de la page de l'énigme, sauf sur le flux rss */
	public function init() {
		$options = get_option('restrictAccess');
		// Flux RSS
		if(is_feed())
		{
			if($options['feed'] == 'onlytitle')
			{
				add_filter('the_excerpt_rss', '__return_false');
				add_filter('the_content_feed', '__return_false');
			}
			else if($options['feed'] == 'off')
				wp_die('Aucun flux disponible, merci d\'utiliser <a href="'. get_bloginfo('url') .'">la page d\'accueil</a> !');
		}
		else // Le reste
			require dirname(__FILE__).'/redirect.php';
	}

	static function deactivation() {
		$options = get_option('restrictAccess');
		wp_delete_user($options['user']);
		delete_option('restrictAccess');
	}
	
	// Initialisation des paramètres
	static function activation() {
		$options = array();
		$options['user'] = restrictAccess::createUser();
		$options['feed'] = 'on';
		$options['nbquestions'] = 10;
		$options['questions'] = array();
		$options['reponses'] = array();
		add_option('restrictAccess',$options);
	}
	
	static function createUser() {
		require_once(ABSPATH . WPINC . '/registration.php');
		$user_name = 'visiteur_'.rand(1,1000);
		$user_id = username_exists($user_name);
		if ( !$user_id )
		{
			$random_password = wp_generate_password(12, true);
			$user_id = wp_create_user($user_name, $random_password, $user_name.'@'.$_SERVER['SERVER_NAME'].'.fake');
		}

		if(ctype_digit($user_id))
		{
			$user = new WP_User($user_id);
			$user->set_role('subscriber');
		}
		return $user_id;
	}

	static function validate($entrees,$reponses) {
		foreach($entrees as $id => $rep)
		{
			if($rep != $reponses[$id])
				return false;
		}
		return true;
	}
}
endif;

if(!isset($restrictAccess))
	$restrictAccess = new restrictAccess();