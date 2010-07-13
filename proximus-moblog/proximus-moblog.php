<?php
/*
 * Plugin Name: Proximus Moblog Sync
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Republie les articles d'un moblog Proximus dans un blog Wordpress.
 * Version: 0.5
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

class proximusMoblog {

	function proximusMoblog() {
		
		if(is_admin())
		{
			// Ajouter un élément dans le menu d'administration
			add_action('admin_menu', array(&$this,'menu') );
			// Register option settings
			add_action('admin_init', array(&$this,'register_settings'));
		}
		
		// Ajouter une action planifiée (cron)
		add_action('proximusMoblogSync_cron', array('proximusMoblog','cron') );

		// Cron Web
		add_action('template_redirect', array(&$this, 'webcron') );
	}

	function menu() {
		add_options_page('Configuration de Proximus Moblog Sync', 'Proximus Moblog Sync', 'install_plugins', 'proximusMoblogSync', array(&$this,'options_page') );
	}
	
	// Register settings
	function register_settings() {
		register_setting('proximusmoblog_settings','proximusmoblog',array(&$this,'filter_options'));
	}
	
	function filter_options($options) {
		return $options;
	}

	// La page d'options
	function options_page() {

		if(isset($_POST['manualSync']) && wp_verify_nonce($_POST['_wpnonce'],'manualSync') )
		{
			$this->cron();
			echo '<div id="setting-error-settings_updated" class="updated settings-error">
					<p><strong>Les articles ont été récupérés.</strong></p></div>';
		}
		
		$options = get_option('proximusmoblog');

		if($options['activate'] == 1)
			wp_schedule_event(time(), 'hourly', 'proximusMoblogSync_cron');
		else
			wp_clear_scheduled_hook('proximusMoblogSync_cron');

		echo '<div class="wrap">';
		echo '<h2>Proximus Moblog Sync</h2>';

		echo '<h3>Configuration</h3>';
		echo '<p>Configuration du moblog Proximus à synchroniser avec Wordpress.</p>';

		echo '<form method="post" action="options.php">';
		settings_fields('proximusmoblog_settings');

		echo '<table class="form-table">';
		
		echo '<tr valign="top">
		<th scope="row"><label for="blogid">Identifiant du moblog</label></th>
		<td><input type="text" id="blogid" name="proximusmoblog[blogid]" value="' .$options['blogid']. '" /></td>

		</tr>';
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="id">ID unique du prochain élément</label></th>
		<td><input type="text" id="id" name="proximusmoblog[id]" value="' . $options['id'] . '" /></td>
		</tr>';

		echo '<tr valign="top">';
		echo '<th scope="row"><label for="activate">Tâche planifiée automatique</label></th>
		<td>
			<select id="activate" name="proximusmoblog[activate]">
				<option value="0">OFF</option>
				<option '.selected(1,$options['activate']).' value="1">ON</option>
			</select>
		</td>
		</tr>';

		echo '</tr>';
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="titre">Titre de l\'article</label></th>
		<td><input type="text" id="titre" name="proximusmoblog[titre]" value="' . $options['titre'] . '" size="60" /> (Template disponible : [id])</td>
		</tr>';

		echo '</tr>';
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="article">Article</label></th>
		<td><textarea id="article" name="proximusmoblog[article]" rows="7" cols="60">' . $options['article'] . '</textarea><br />
		Template disponible : [image]</td>
		</tr>';

		echo '<tr valign="top">';
		echo '<th scope="row"><label for="categorie">Dans la catégorie</label></th>
		<td>';
		wp_dropdown_categories('id=categorie&name=proximusmoblog[categorie]&hide_empty=0&selected='.$options['categorie']);
		echo '</td>
		</tr>';

		echo '<tr valign="top">';
		echo '<th scope="row"><label for="user">Par l\'utilisateur</label></th>
		<td>';
			wp_dropdown_users('id=user&name=proximusmoblog[user]&selected='.$options['user']);
		echo '</td>
		</tr>';

		echo '</table>';

		echo '<p class="submit"><input type="submit" class="button-primary" value="' . __('Save Changes') . '" /></p>';
		echo '</form>';

		echo '<h3>Tâche planifiée</h3>';

		// Statut du cron via Wordpress
		echo  '<h4>Via Wordpress</h4>';
		echo '<p>';
		$proximoblog_status = wp_get_schedule('proximusMoblogSync_cron');
		if(!empty($proximoblog_status))
		{
			echo 'Les articles sont automatiquement récupérés ';
			$proximoblog_tab = wp_get_schedules();
			echo strtolower($proximoblog_tab[$proximoblog_status]['display']).'.';
			echo '<br />En cas de problèmes (doublons, articles mal classés), désactivez la tâche planifiée automatique et utilisez la tâche manuelle.';
		}
		else
			echo 'La récupération automatique des articles est désactivée.';
		echo '</p>';

		echo '<h4>Manuel</h4>';
		echo '<p>Utilisez votre crontab pour exécuter périodiquement le script suivant :</p>';
		echo '<code>';
		echo '#!/bin/sh<br />';
		echo 'wget '.get_bloginfo('url').'/?proximusmoblog=cron >/dev/null 2>&1';
		echo '</code>';

		echo '<form action="options-general.php?page=proximusMoblogSync" method="post">';
		wp_nonce_field('manualSync');
		echo '<p class="submit"><input type="submit" name="manualSync" class="button-primary" value="Charger les nouveaux articles maintenant" /></p>';
		echo '</form>';

		echo '</div>';
	}
	
	function cron($verbose=false) {
		if($verbose)
			echo '<p>Starting cron...</p>';
		global $wpdb;
		
		$options = get_option('proximusmoblog');
		
		if(!is_numeric($options['id'])) $options['id'] = 1; // Si l'id du prochain article est invalide, initialisé à 1

		if (is_numeric($options['blogid'])) { // Identifiant valide

			// Récupération du flux RSS
			require_once (ABSPATH . WPINC . '/class-feed.php');
			$rss = new SimplePie();
			$rss->set_feed_url('http://payandgogeneration.proximus.be/moblogs/rss.cfm?id=' . $options['blogid']);

			// Désactiver le cache
			$rss->enable_cache(false);

			// Désactivation du tri par défaut
			$rss->enable_order_by_date(false);
			$rss->init();
			$rss->handle_content_type();

			$maxitems = $rss->get_item_quantity(); // Nombre d'éléments du flux RSS
			$rss_items = $rss->get_items(0, $maxitems); // Tableau des articles récupérés
			$hackmin = 0; // Hack de décalage d'article pour ne pas avoir plusieurs articles publiés en même temps.

			for ($i = $maxitems - 1; $i>=0; --$i) {

				$item = $rss->get_item($i);

				$guid = $item->get_id();
				$guid = substr($guid, strpos($guid, '=') + 1);
				$count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = 'proximusMoblog_guid' AND meta_value = '$guid'"));
				if($count == 0)
				{
					
					if($verbose)
						echo '<li>'.$guid.'</li>';
				
					$enclosure = $item->get_enclosure();
					$image = $enclosure->get_link();
					
					$image = $this->downloadImage($image,$options['blogid'],$options['id']);
					$title = str_replace('[id]',$options['id'],$options['titre']);
					$contenu = str_replace('[image]',$image,$options['article']);
					$date = date('Y-m-d H:i:s',time()+$hackmin);

					// Create post object
					$my_post = array();
					$my_post['post_title'] = $title;
					$my_post['post_content'] = $contenu;
					$my_post['post_status'] = 'publish';
					$my_post['post_date'] = $date;

					if(!empty($options['user']))
						$my_post['post_author'] = $options['user'];
					if(!empty($options['categorie']))
						$my_post['post_category'] = array($options['categorie']);

					// Insert the post into the database
					$postid = wp_insert_post($my_post);
					add_post_meta($postid,'proximusMoblog_guid',$guid);
					$options['id']++;
					$hackmin += 5;
				}
			}
			update_option('proximusmoblog',$options);
		} // Fin identifiant valide
	}
	
	function downloadImage($image,$moblog,$id) {
		$upload = wp_upload_dir();
		$base_dir = $upload['basedir'].'/moblog-'.$moblog.'/';
		$newimage = $base_dir.$id.'.jpg';
		$newimageurl = $upload['baseurl'].'/moblog-'.$moblog.'/'.$id.'.jpg';
		
		if(!is_dir($base_dir))
			mkdir($base_dir);

		$get = wp_get_http($image,$newimage);
		
		if($get['response'] == 200 && file_exists($newimage))
			return $newimageurl;
		else
			return $image;		
	}
	
	function webcron() {
		if(isset($_GET['proximusmoblog']) && $_GET['proximusmoblog'] == 'cron')
		{
			$this->cron(true);
			exit;
		}
	}

}

if(!isset($proximusmoblog))
$proximusmoblog = new proximusMoblog();
?>