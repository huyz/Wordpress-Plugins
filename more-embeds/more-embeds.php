<?php
/*
 * Plugin Name: More Embeds
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Add provider for "Auto-Embed" feature (currently only Wat.tv).
 * Version: 0.1
 * Author: Cédric Boverie
 * Author URI: http://www.boverie.eu/
 */
/* Copyright 2010  Cédric Boverie  (email : ced@boverie.eu)
 * this program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * this program is distributed in the hope that it will be useful,
 * put WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * you should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Pour les sites qui n'utilise pas oEmbed

/* 
function moreEmbeds_wattv( $matches, $attr, $url, $rawattr ) {

	$embed = '<div style="text-align:center;">
	<object width="480" height="270" type="" data="http://www.wat.tv/swf2/191076nIc0K114406207">
	<param name="movie" value="http://www.wat.tv/swf2/191076nIc0K114406207" />
	<param name="allowScriptAccess" value="always" />
	<param name="allowFullScreen" value="true" />
	</object></div>';

	return apply_filters( 'embed_wat', $embed, $matches, $attr, $url, $rawattr );
}

function moreEmbeds_register_handlers() {
	wp_embed_register_handler( 'wattv', '#http://www.wat.tv/video/(.+?).html#i', 'moreEmbeds_wattv' );
}

add_action('wp', 'moreEmbeds_register_handlers');
*/

// oEmbed émulations

// Wat.tv
wp_oembed_add_provider('#http://(www\.)?wat.tv/video/.*#i', WP_PLUGIN_URL.'/more-embeds/provider/providers.php?provider=Wat', true);
?>