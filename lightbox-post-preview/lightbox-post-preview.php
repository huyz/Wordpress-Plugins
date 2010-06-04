<?php
/*
 * Plugin Name: Lightbox Post Preview
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Affiche l'aperçu d'un article dans une lightbox.
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

if(is_admin() && lightbox_admin_active())
{
	wp_enqueue_script('colorbox',WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)).'/colorbox/jquery.colorbox-min.js',array('jquery'),'1.3.6');
	wp_enqueue_style('colorbox',WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)).'/colorbox/theme2/colorbox.css',array(),'1.3.6');

	add_action('admin_head', 'lightbox_admin_inline_js');
	
	
}

function lightbox_admin_inline_js() {
	echo '<script type="text/javascript">
jQuery(document).ready(function($) {
	$(".preview").hide();
	var preview_link = $("#post-preview").attr("href");
	var post_permalink = $("#sample-permalink").html();
	$("#preview-action").html(\'<a class="preview button" href="\'+preview_link+\'">'.__('Preview').'</a>\');
	
	$("a[href$=preview=true],.preview").colorbox({width:"95%", height:"95%", iframe:true});
	$("a[href^="+post_permalink+"]").colorbox({width:"95%", height:"95%", iframe:true});
});
</script>
';
}

function lightbox_admin_active() {
    global $pagenow;
	//$pagenow = basename($_SERVER['SCRIPT_NAME']);
	if($pagenow == 'post-new.php' || $pagenow == 'post.php')
		return true;
	
	return false;
}

?>