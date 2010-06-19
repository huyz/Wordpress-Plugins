<?php
/*
 * Plugin Name: Multisite Switcher
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Fast switch between sites administration installed on the same "network".
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

if(!class_exists('multiSiteSwitcher')):
class multiSiteSwitcher {

	function multiSiteSwitcher() {
		return __construct();
	}
	
	function __construct() {
		add_action('in_admin_header', array(&$this, 'add_menu'));
	}

	function add_menu() {
	
		if(!function_exists('is_multisite') || !is_multisite())
			return false;

		global $current_user;
		$blogs = get_blogs_of_user( $current_user->id );

		if(count($blogs) <= 1)
			return false;

		$current_blogurl = get_bloginfo('url');
		
		echo '<select onchange="location.href=this.value;" style="float:left;min-width:150px;margin:10px 0 0 30px;" id="multisiteswitcher" name="multisiteswitcher">';
		foreach($blogs as $blog)
		{
			echo '<option '.selected($blog->siteurl,$current_blogurl).' value="'.$blog->siteurl.'/wp-admin/">'.$blog->blogname.'</option>';
		}
		echo '</select>';		
	}
}
endif;

if(!isset($multiSiteSwitcher))
	$multiSiteSwitcher = new multiSiteSwitcher();
