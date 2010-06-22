<?php
/*
 * Plugin Name: Multisite Restore Features
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Activate post by mail and custom ping sites in a multisite configuration.
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
add_action('init','MRF_enable_removed_mufeatures');

function MRF_enable_removed_mufeatures() {
	add_filter('enable_update_services_configuration', '__return_true');
	add_filter('whitelist_options','MRF_allow_edit_ping_sites');
	add_filter('enable_post_by_email_configuration', '__return_true');
}

function MRF_allow_edit_ping_sites($whitelist_options) {
	$whitelist_options['writing'][] = 'ping_sites';
	return $whitelist_options;
}
?>