<?php
/*
 * Plugin Name: Skyrock Blog Importer
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Importe un Skyrock Blog dans Wordpress.
 * Version: 0.1
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
if ( !defined('WP_LOAD_IMPORTERS') )
	return;
	
// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';
register_importer('skyrockblog', 'Skyrock Blog', 'Import <strong>posts, comments, images and videos</strong> from a Skyrock Blog.', 'skyrock_importer');

function skyrock_importer() {
	
	echo '<div class="wrap">';
	echo '<h2>Skyrock Blog Importer</h2>';
		
	if(isset($_GET['blog']))
	{
		$blog = esc_attr($_GET['blog']);
		$page = is_numeric($_GET['p']) ? intval($_GET['p']) : 1;
		if(skyrock_importPage($blog,$page) > 0)
		{
			echo '<p>Blog : <strong>'.$blog.'</strong><br />';
			echo '<p>Page : <strong>'.$page.'</strong></p>';
			echo '<form id="importform" action="admin.php" method="get">';
			echo '<p><input type="hidden" name="import" value="skyrockblog" /><input type="hidden" name="p" value="'.($page+1).'" />';
			echo '<input type="hidden" name="blog" value="'.$blog.'" /><input type="hidden" id="pause" value="0" /></p>';
			echo '<p><input type="submit" value="Importer la page suivante" /></p>';
			echo '</form>';
			if($page % 10 == 0) // Laissez une pause toute les 10 pages
				$timeout = 2000;
			else
				$timeout = 0;

			echo '<script type="text/javascript">
			function submitform() {
			document.getElementById(\'importform\').submit();
			}
			setTimeout("submitform()",'.$timeout.');
			</script>';
		}
		else
		{
			echo '<p>Importation terminée. <br /><a href="'.get_bloginfo('url').'">Aller à l\'accueil</a></p>';
		}
	}
	else
	{
		echo '<form action="admin.php" method="get">';
		echo '<p><input type="hidden" name="import" value="skyrockblog" /><label for="blog">Blog</label> ';
		echo '<input type="text" name="blog" id="blog" /></p>';
		echo '<p><input type="submit" value="Importer" /></p>';
		echo '</form>';
	}
}

function skyrock_importPage($blog,$page='') {
	if(empty($page))
		$page = 1;
		
	$page = intval($page);
	$url = 'http://'.$blog.'.skyrock.com/'.$page.'.html';
	$result = wp_remote_get($url);
	
	if(wp_remote_retrieve_response_code($result) != 200)
		return -1;

	$nbpost = 0;
	
	$body = utf8_encode(wp_remote_retrieve_body($result));

	// ID des articles
	preg_match_all('#<div id="a-([0-9]+)" class="bloc">#',$body, $ids);
	$ids = $ids[1];

	// Titres
	preg_match_all('#<a class="plink".*>(.*)</a>#',$body,$titre);
	$titre = $titre[1];

	// Date
	preg_match_all('#Posté le (.*)</p>#',$body, $date);
	$date = $date[1];
	array_walk($date,'skyrock_parseDate');
	
	// Date Modif
	/*preg_match_all('#Modifié le (.*)</p>#',$body, $date_modif);
	$date_modif = $date_modif[1];
	array_walk($date_modif,'skyrock_parseDate');*/

	// Contenu
	preg_match_all('/<div[\s]*class=\"post\">(.*)<div class=\"commentaires\">/smiU',$body,$content);
	$content = $content[1];

	foreach($ids as $i => $id)
	{
		/* Recherche de contenu additionnel */
		$multimedia = '';
		// Image
		preg_match('#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im',$content[$i],$image);
		if(!empty($image[2]))
		{
			if(skyrock_downloadImage($image[2]) !== false)
				$image = skyrock_downloadImage($image[2]);
				if(strpos($image,'_small') !== false)
					$multimedia = '<p><a href="'.str_replace('_small','',$image).'"><img class="aligncenter" src="'.$image.'" alt="" /></a></p>';
				else
				$multimedia = '<p><img class="aligncenter" src="'.$image.'" alt="" /></p>';
		}
		else
		{
			// Embed
			preg_match('/<object(.*)<\/object>/smiU',$content[$i],$object);
			if(!empty($object[0]))
			{
				$multimedia = '<div style="text-align:center">'.$object[0].'</div>';
			}
		}
		
		$post['post_title'] = $titre[$i];
		$post['post_date'] = date('Y-m-d H:i:s',$date[$i]);
		$post['post_content'] = skyrock_cleanHtml($content[$i]).$multimedia;
		$post['post_status'] = 'publish';
		$post['post_author'] = 1;
		$post['post_type'] = 'post';
		$post['filter'] = true;
		
		// Insérer le post ici
		$inserted_id = wp_insert_post($post);
		skyrock_importComments($blog,$id,$inserted_id);
		$nbpost++;
	}
	return $nbpost;
}

function skyrock_importComments($blog,$articleid,$wordpressid) {
	$page = 1;
	$nbcomment = 0;
	$url = 'http://'.$blog.'.skyrock.com/'.$articleid.'_comment_'.$page.'.html';
	$result = wp_remote_get($url);
	while(wp_remote_retrieve_response_code($result) == 200)
	{
		$body = utf8_encode(wp_remote_retrieve_body($result));
		
		
		// ID des articles
		preg_match_all('#id="c-([0-9]+)">#',$body, $ids);
		$ids = $ids[1];
		
		// Auteurs
		preg_match_all('/<strong>(.*)<\/strong>/smiU',$body,$auteurs_temp);
		$auteurs_temp = $auteurs_temp[1];
		$i = 0;
		foreach($auteurs_temp as $a)
		{
			if($a != 'Posté le ')
			{
				preg_match('#<a href="([a-zA-Z0-9-_\./:]+)"#',$a,$url);
				$auteurs[$i]['pseudo'] = strip_tags($a);
				if(trim($auteurs[$i]['pseudo']) == '') $auteurs[$i]['pseudo'] = 'Anonyme';
				$auteurs[$i]['url'] = $url[1];
				$i++;
			}
		}
		
		// Commentaires
		preg_match_all('/<strong>Posté le <\/strong>.*\n(.*)<\/div>/smiU',$body,$comments);
		$comments = $comments[1];

		// Date
		preg_match_all('#<strong>Posté le </strong>(.*)#',$body, $date);
		$date = $date[1];
		array_walk($date,'skyrock_parseDate');
		
		foreach($ids as $i => $id)
		{
			$comment['comment_post_ID'] = $wordpressid;
			$comment['comment_author'] = $auteurs[$i]['pseudo'];
			$comment['comment_author_url'] = $auteurs[$i]['url'];
			$comment['comment_author_email'] = sanitize_title($auteurs[$i]['pseudo']).'@fakeskyrock.com';
			$comment['comment_date'] = date('Y-m-d H:i:s',$date[$i]);
			$comment['comment_content'] = $comments[$i];
			$comment['comment_approved'] = 1;
			wp_insert_comment($comment);
			$nbcomment++;
		}
		$page++;
		$url = 'http://'.$blog.'.skyrock.com/'.$articleid.'_comment_'.$page.'.html';
		$result = wp_remote_get($url);
	}
	return $nbcomment;
}

function skyrock_downloadImage($image) {
	$upload_dir = wp_upload_dir();
	$upload_basedir = $upload_dir['basedir'].'/skyrock/';
	if(!is_dir($upload_basedir))
		mkdir($upload_basedir);
	
	$filepath = $upload_basedir.basename($image);
	
	if(file_exists($filepath))
		return $upload_dir['baseurl'].'/skyrock/'.basename($image);
		
	$get = wp_get_http($image,$filepath);
	
	if($get['response'] == 200)
	{
		$image_orig = str_replace('_small','',$image,$count);
		if($count == 1)
			skyrock_downloadImage($image_orig);
		return $upload_dir['baseurl'].'/skyrock/'.basename($image);
	}
	else
		return false;	
}

function skyrock_parseDate(&$date) {
	$elem = explode(' ',$date);
	
	list($heure,$minute) = explode(':',$elem[4]);
	
	switch($elem[2]) {
		case 'janvier': $elem[2] = 1; break;
		case 'février': $elem[2] = 2; break;
		case 'mars': $elem[2] = 3; break;
		case 'avril': $elem[2] = 4; break;
		case 'mai': $elem[2] = 5; break;
		case 'juin': $elem[2] = 6; break;
		case 'juillet': $elem[2] = 7; break;
		case 'août': $elem[2] = 8; break;
		case 'septembre': $elem[2] = 9; break;
		case 'octobre': $elem[2] = 10; break;
		case 'novembre': $elem[2] = 11; break;
		case 'décembre': $elem[2] = 12; break;
		default: $elem[2] = 0; break;
	}
	$date = mktime($heure,$minute,0,$elem[2],$elem[1],$elem[3]);
}

function skyrock_cleanHtml($html) {
	$allowed_html = array(
		'br' => array(),
		'a' => array(
			'href' => array(),
			'title' => array()),
		'span' => array(
			'style' => array()),
		//'div' => array(
		//	'style' => array()),
		'b' => array(),
		'em' => array(),
		'i' => array(),
		'strike' => array(),
		'strong' => array(),
	);
	$html = preg_replace('#\t#', ' ', $html);
	$html = preg_replace('# {1,}#', ' ', $html);
	$html = wpautop(wp_kses($html, $allowed_html));
	$html = preg_replace('#<p> <a href="http://www.skyrock.com/m/blog/share-widget.php\?idp=[0-9]+&amp;idm=[-0-9]+">Ajouter .* à mon blog</a></p>#','',$html);

	return $html;
}