<?php
/*
 * Plugin Name: Proximus Moblog Sync
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Republie les articles d'un moblog Proximus dans un blog Wordpress.
 * Version: 0.3
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

// La page d'options
function proximusMoblogSync_options() {

    if(isset($_POST['manualSync']))
    {
        include(dirname(__FILE__).'/cron_function.php');
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
    echo '<th scope="row">Tâche planifiée automatique (BETA)</th>
    <td>
        <select name="proximusMoblogSync_activate">
            <option value="0">OFF</option>
            <option ';
            if($activate == 1) echo 'selected="selected" ';
            echo 'value="1">ON</option>
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

    echo '<h3>Tâche planifiée</h3>';

    // Statut du cron via Wordpress
    echo  '<h4>Via Wordpress</h4>';
    echo '<p>';
    $proximoblog_status = wp_get_schedule('proximusMoblogSync_launchCron');
    if(!empty($proximoblog_status))
    {
        echo 'Les articles sont automatiquement récupérés ';
        $proximoblog_tab = wp_get_schedules();
        echo strtolower($proximoblog_tab[$proximoblog_status]['display']).'.';
        echo '<br />En cas de problèmes (doublons, articles mal classés), désactivez la tâche planifiée automatique et utilisez la tâche manuelle.';
    }
    else
        echo 'La récupération automatique des articles est désactivée.<br />Il est recommandé de l\'activer uniquement si vous n\'avez pas accès à une crontab ou équivalent.';
    echo '</p>';

    echo '<h4>Manuel</h4>';
    echo '<p>A ajouter dans votre crontab :</p>';
    echo '<p>';
    $proximoblog_cron_url = WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)).'/cron.php';
    echo 'Chemin physique : '.dirname(__FILE__).'/cron.php<br />';
    echo 'URI : '.$proximoblog_cron_url;
    echo '</p>';

    echo '<form action="options-general.php?page=proximusMoblogSync" method="post">';
    echo '<p class="submit"><input type="submit" name="manualSync" class="button-primary" value="Charger les nouveaux articles maintenant" /></p>';
    echo '</form>';

    echo '</div>';
}
?>