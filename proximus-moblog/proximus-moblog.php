<?php
/*
 * Plugin Name: Proximus Moblog Sync
 * Plugin URI: http://www.boverie.eu/
 * Description: Importe les articles d'un moblog Proximus dans Wordpress.
 * Version: 0.2.1
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


// Ajouter un élément dans le menu d'administration
add_action('admin_menu', 'proximusMoblogSync');

function proximusMoblogSync() {
    add_options_page('Configuration de Proximus Moblog Sync', 'Proximus Moblog Sync', 'install_plugins', 'proximusMoblogSync', 'proximusMoblogSync_options');
}

// Ajouter une action planifiée (cron)
add_action('proximusMoblogSync_launchCron', 'proximusMoblogSync_cron');


// Activer la tâche planifiée
function proximusMoblogSync_activateCron() {
    wp_schedule_event(time(), 'hourly', 'proximusMoblogSync_launchCron');
}

// Désactiver la tache planifiée
function proximusMoblogSync_desactivateCron() {
    wp_clear_scheduled_hook('proximusMoblogSync_launchCron');
}

// La tache planifiée
function proximusMoblogSync_cron() {

    if(get_option('proximusMoblogSync_isrunning') == 1)
        return 0;

    global $wpdb;

    $blogid = get_option('proximusMoblogSync_blogid');
    $id = get_option('proximusMoblogSync_id');
    $titre = get_option('proximusMoblogSync_titre');
    $article = get_option('proximusMoblogSync_article');
    $categorie = get_option('proximusMoblogSync_categorie');
    $user = get_option('proximusMoblogSync_user');

    if(!ctype_digit($id)) $id = 1; // Si l'id du prochain article est invalide, initialisé à 1

    if (ctype_digit($blogid)) { // Identifiant valide

        update_option('proximusMoblogSync_isrunning',1);

        // Récupération du flux RSS
        require_once (ABSPATH . WPINC . '/class-feed.php');
        $rss = new SimplePie();
        $rss->set_feed_url('http://payandgogeneration.proximus.be/moblogs/rss.cfm?id=' . $blogid);
       
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

			//echo '<li>'.$guid.'</li>';
			
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = 'proximusMoblog_guid' AND meta_value = '$guid'"));
            if($count == 0)
            {
                $enclosure = $item->get_enclosure();
                $image = $enclosure->get_link();
                $title = str_replace('[id]',$id,$titre);
                $contenu = str_replace('[image]',$image,$article);
                $date = date('Y-m-d H:i:s',$item->get_date('U')+$hackmin);

                // Create post object
                $my_post = array();
                $my_post['post_title'] = $title;
                $my_post['post_content'] = $contenu;
                $my_post['post_status'] = 'publish';
                $my_post['post_date'] = $date;

                if(!empty($user))
                    $my_post['post_author'] = $user;
                if(!empty($categorie))
                    $my_post['post_category'] = array($categorie);

                // Insert the post into the database
                $postid = wp_insert_post($my_post);
                add_post_meta($postid,'proximusMoblog_guid',$guid);
                $id++;
                $hackmin += 75;
            }
        }
        update_option('proximusMoblogSync_id',$id);

        update_option('proximusMoblogSync_isrunning',0);
    } // Fin identifiant valide
}

// La page d'options
function proximusMoblogSync_options() {

    if(isset($_POST['manualSync']))
    {
        proximusMoblogSync_cron();
        echo '<div id="setting-error-settings_updated" class="updated settings-error">
                <p><strong>Les articles ont été récupérés.</strong></p></div>';
    }

    register_deactivation_hook(__FILE__, 'proximusMoblogSync_desactivateCron');
    register_activation_hook(__FILE__, 'proximusMoblogSync_activateCron');

    $blogid = get_option('proximusMoblogSync_blogid');
    $id = get_option('proximusMoblogSync_id');
    $activate = get_option('proximusMoblogSync_activate');
    $titre = get_option('proximusMoblogSync_titre');
    $article = get_option('proximusMoblogSync_article');
    $categorie = get_option('proximusMoblogSync_categorie');
    $user = get_option('proximusMoblogSync_user');

    if($activate == 1)
        proximusMoblogSync_activateCron();
    else
        proximusMoblogSync_desactivateCron();


    echo '<div class="wrap">';
    echo '<h2>Proximus Moblog Sync</h2>';

    echo '<h3>Configuration</h3>';
    echo '<p>Configuration du moblog Proximus à synchroniser avec Wordpress.</p>';

    echo '<form method="post" action="options.php">';
    wp_nonce_field('update-options');

    echo '<table class="form-table">';
    
    echo '<tr valign="top">
    <th scope="row">Identifiant du moblog</th>
    <td><input type="text" name="proximusMoblogSync_blogid" value="' . $blogid . '" /></td>

    </tr>';
    echo '<tr valign="top">';
    echo '<th scope="row">ID unique du prochain élément</th>
    <td><input type="text" name="proximusMoblogSync_id" value="' . $id . '" /></td>
    </tr>';

    echo '<tr valign="top">';
    echo '<th scope="row">Tâche planifiée</th>
    <td>
        <select name="proximusMoblogSync_activate">
            <option value="0">Désactiver</option>
            <option ';
            if($activate == 1) echo 'selected="selected" ';
            echo 'value="1">Activer</option>
        </select>
    </td>
    </tr>';

    echo '</tr>';
    echo '<tr valign="top">';
    echo '<th scope="row">Titre de l\'article</th>
    <td><input type="text" name="proximusMoblogSync_titre" value="' . $titre . '" size="60" /> (Template disponible : [id])</td>
    </tr>';


    echo '</tr>';
    echo '<tr valign="top">';
    echo '<th scope="row">Article</th>
    <td><textarea name="proximusMoblogSync_article" rows="7" cols="60">' . $article . '</textarea><br />
    Template disponible : [image]   </td>
    </tr>';


    echo '<tr valign="top">';
    echo '<th scope="row">Dans la catégorie</th>
    <td>';
    wp_dropdown_categories('name=proximusMoblogSync_categorie&hide_empty=0&selected='.$categorie);
    echo '</td>
    </tr>';

    echo '<tr valign="top">';
    echo '<th scope="row">Par l\'utilisateur</th>
    <td>';
        wp_dropdown_users('name=proximusMoblogSync_user&selected='.$user);
    echo '</td>
    </tr>';


    echo '</table>';

    echo '<input type="hidden" name="action" value="update" />';
    echo '<input type="hidden" name="page_options" value="proximusMoblogSync_id,proximusMoblogSync_blogid,proximusMoblogSync_activate,proximusMoblogSync_titre,proximusMoblogSync_article,proximusMoblogSync_categorie,proximusMoblogSync_user" />';
    echo '<p class="submit"><input type="submit" class="button-primary" value="' . __('Save Changes') . '" /></p>';
    echo '</form>';

    // Confirmation du bon fonctionnement
    echo  '<h3>Status</h3>';
    echo '<p>';
    $status = wp_get_schedule('proximusMoblogSync_launchCron');
    if(!empty($status))
    {
        echo 'Les articles sont automatiquement récupérés ';
        $tab = wp_get_schedules();
        echo strtolower($tab[$status]['display']).'.';
    }
    else
        echo 'La récupération automatique des articles est désactivées.';
    echo '</p>';
    
    echo '<form action="options-general.php?page=proximusMoblogSync" method="post">';
    echo '<p class="submit"><input type="submit" name="manualSync" class="button-primary" value="Charger les nouveaux articles manuellement" /></p>';
    echo '</form>';


    echo '</div>';
}
?>