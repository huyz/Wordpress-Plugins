<?php
/*
 * Plugin Name: Restrict Access
 * Plugin URI: http://www.boverie.eu/
 * Description: Oblige à résoudre une énigme avant d'autoriser l'accès au blog.
 * Version: 0.1
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
 
function restrictAccess_autoLogin($user_login = 'visiteur') {
	$user = get_userdatabylogin($user_login);
	$user_id = $user->ID;
	wp_set_current_user($user_id, $user_login);
	wp_set_auth_cookie($user_id);
	do_action('wp_login', $user_login);
}


/* Ajout de la page de l'énigme, sauf sur le flux rss */
function restrictAccess_init() {
	if(!isset($_GET['skip']) && !is_feed())
		require(dirname(__FILE__).'/redirect.php');
}
add_action('template_redirect', 'restrictAccess_init');


// Flux sans contenu
function restrictAccess_postrss($content) {
	return '';
}
add_filter('the_excerpt_rss', 'restrictAccess_postrss');
add_filter('the_content_feed', 'restrictAccess_postrss');

