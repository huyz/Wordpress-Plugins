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
register_activation_hook( __FILE__, array('restrictAccess','activation'));

if(!class_exists('restrictAccess')):
class restrictAccess {
	/* Constructeur PHP4 */
	function restrictAccess() {
		return __construct();
	}
	
	/* Constructeur */
	public function __construct() {
		if(!is_admin())
		{
			// Restreindre l'accès au blog
			add_action('template_redirect', array(&$this,'init'),false,'ced');
			// Flux sans contenu
			add_filter('the_excerpt_rss', '__return_false');
			add_filter('the_content_feed', '__return_false');
		}
	}
	
	/* Connexion automatique sur le compte d'un utilisateur */
	static function autoLogin($user_login = 'visiteur')	{
		$user = get_userdatabylogin($user_login);
		$user_id = $user->ID;
		wp_set_current_user($user_id, $user_login);
		wp_set_auth_cookie($user_id);
		do_action('wp_login', $user_login);
	}

	/* Ajout de la page de l'énigme, sauf sur le flux rss */
	public function init() {
		if(!isset($_GET['skip']) && !is_feed())
			require(dirname(__FILE__).'/redirect.php');
	}

	static function activation() {
		restrictAccess::createUserIfNotExist();
	}
	
	static function createUserIfNotExist() {
		require_once(ABSPATH . WPINC . '/registration.php');
		$user_name = 'visiteur';
		$user_id = username_exists($user_name);
		if ( !$user_id )
		{
			$random_password = wp_generate_password(12, true);
			$user_id = wp_create_user($user_name, $random_password, $user_name.'@'.$_SERVER['SERVER_NAME'].'.fake');
		}
		
		if(ctype_digit($user_id))
		{
			$user = new WP_User( $user_id );
			$user->set_role( 'subscriber' );
		}
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