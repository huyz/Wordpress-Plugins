<?php
/*
 * Plugin Name: Multisite Switcher
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Fast switch between sites administration installed on the same "network".
 * Version: 1.0
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

if(!class_exists('multiSiteSwitcher')):
class multiSiteSwitcher {

	function multiSiteSwitcher() {
		return __construct();
	}
	
	function __construct() {
		
		// Noscript switcher
		if(isset($_POST['multisiteswitcher']))
				add_action('admin_menu',array(&$this,'redirect_to_selected_admin'));

		add_action('in_admin_header', array(&$this, 'add_switcher'));
	}

	function add_switcher() {
	
		// Tester si le multisite est activié
		if(!function_exists('is_multisite') || !is_multisite())
			return false;

		// Blogs de l'utilisateur actuel
		global $current_user;
		$blogs = get_blogs_of_user( $current_user->id , true);

		// Il faut plus d'un blog pour avoir le menu déroulant
		if(count($blogs) <= 1)
			return false;

		$current_blogurl = get_bloginfo('url');
		echo '<form style="float:left;min-width:150px;margin:10px 0 0 40px;" action="" method="post">';
		wp_nonce_field('multisite-switcher');
		echo '<select onchange="location.href=this.value;" id="multisiteswitcher" name="multisiteswitcher">';
		foreach($blogs as $blog)
		{
			echo '<option '.selected($blog->siteurl,$current_blogurl,false).' value="'.$blog->siteurl.'/wp-admin/">'.$blog->blogname.'</option>';
		}
		echo '</select>';
		echo '<noscript> <input class="button-secondary action" type="submit" value="Go"/></noscript>';
		echo '</form>';
	}
	
	function redirect_to_selected_admin() {
		check_admin_referer('multisite-switcher');
		// Comme sécurité basique (et limite inutile), on vérifie que le blog sélectioné existe et est géré par l'utilisteur courant
		global $current_user;
		$blogs = get_blogs_of_user( $current_user->id , true);
		foreach($blogs as $blog)
		{
			if($blog->siteurl.'/wp-admin/' == $_POST['multisiteswitcher'])
			{
				wp_redirect($_POST['multisiteswitcher']);
				exit;
			}	
		}
	}
}
endif;

if(is_admin() && !isset($multiSiteSwitcher))
	$multiSiteSwitcher = new multiSiteSwitcher();
