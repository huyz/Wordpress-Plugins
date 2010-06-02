<?php

/*
 * Plugin Name: Plugins Developer
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Aide au développement de plugin pour Wordpress.
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

// Ajouter un élément dans le menu d'administration
add_action('admin_menu', 'plugins_dev_adminMenu');

function plugins_dev_adminMenu() {
    add_options_page('Plugin Developer', 'Plugin Developer', 'install_plugins', 'pluginsDeveloper', 'plugins_dev_mainPage');
}

function plugins_dev_mainPage() {

    define(GITHUB_URL,"http://github.com/cedbv/Wordpress-Plugins/");

    if(isset($_POST['updateReadme']))
    {
        pluginsDev::updateReadme();
        echo '<div id="message" class="updated fade"><p><strong>Fichier README.md mis à jour.</strong></p></div>';
    }
    else if(isset($_POST['generateArchive']))
    {
        pluginsDev::generateArchive();
        echo '<div id="message" class="updated fade"><p><strong>Archives générées.</strong></p></div>';
    }

    echo '<div class="wrap">';
    echo '<div id="icon-plugins" class="icon32"></div><h2>Plugin Developer</h2>';

    echo '<h3>Liste des plugins</h3>';

    echo '<table class="widefat">';
    echo '<thead><tr>';
        echo '<th>Nom</th>';
        echo '<th>Description</th>';
    echo '</tr></thead><tbody>';
    foreach(pluginsDev::pluginsList() as $plugin) {
        echo '<tr>';
            echo '<td>'.$plugin['Name'].' '.$plugin['Version'].'</td>';
            echo '<td>'.$plugin['Description'].'</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';

    /*
    echo '<h3>README.md</h3>';
    require_once dirname(__FILE__).'/libs/markdown.php';
    echo '<blockquote>';
    echo Markdown(file_get_contents(WP_PLUGIN_DIR.'/README.md'));
    echo '</blockquote>';
    */
    
    echo '<h3>Actions sur les plugins</h3>';
    echo '<form action="" method="post">';
    echo '<input type="submit" name="updateReadme" class="button-secondary" value="Mise à jour du Readme" />';
    echo '<br />';
    echo '<input type="submit" name="generateArchive" class="button-secondary" value="Générer toutes les archives" />';
    echo '</form>';


    echo '</div>';
}

class pluginsDev {

    static function pluginsList() {
        // Ignorer les dossiers spécifiés dans .gitignore
        if(file_exists(WP_PLUGIN_DIR.'/.gitignore'))
        {
            $ignore = '#';
            $file = fopen(WP_PLUGIN_DIR.'/.gitignore',"r");
            while(!feof($file))
                $ignore .= trim(fgets($file)).'|';
            fclose($file);
            $ignore = substr($ignore,0,-1);
            $ignore .= '#';
        }

        $plugins = array();

        // Plugin dans un fichier (plugin à la racine non-supporté)
        foreach(glob(WP_PLUGIN_DIR.'/*/*.php') as $file)
        {
            if(!$ignore || !preg_match($ignore,$file))
            {
                $tmp = get_plugin_data($file);
                if(!empty($tmp['Name']))
                {
                    $tmp['DirPath'] = dirname($file);
                    $tmp['Dir'] = dirname(plugin_basename($file));
                    $tmp['Description'] = strip_tags($tmp['Description']);

                    $pos = strrpos($tmp['Description'],".",-2); // Dernier point avant le "Par Auteur"
                    if($pos === FALSE) // S'il y a un point dans la description, on coupe au dernier
                        $pos = strrpos($tmp['Description'],".");
                    
                    $tmp['Description'] = substr($tmp['Description'],0,$pos+1);
                    $tmp['Description'] = html_entity_decode($tmp['Description'],ENT_QUOTES,'UTF-8');
                    
                    unset($tmp['TextDomain']);
                    unset($tmp['Network']);
                    unset($tmp['DomainPath']);
                    unset($tmp['Title']);
                    $plugins[] = $tmp;
                }
            }
        }
        return $plugins;
    }

    static function updateReadme() {
        $content = "Liste des plugins pour Wordpress sur ce dépôt\n";
        $content .= "=============================================\n";
        $content .= "[Télécharger tout](".GITHUB_URL."zipball/master)\n";
        foreach(pluginsDev::pluginsList() as $plugin) {
            $content .= $plugin['Name'].' '.$plugin['Version']."\n";
            $content .= "------------------------\n";
            $content .= $plugin['Description']."  \n";
            $content .= "[Voir le code source](".GITHUB_URL."tree/master/".$plugin['Dir']."/) - ";
            $content .= "[Télécharger](".GITHUB_URL."raw/master/download/".$plugin['Dir'].".zip)\n\n";
        }

        file_put_contents(WP_PLUGIN_DIR.'/README.md',$content);
    }

    static function generateArchive() {
        require_once dirname(__FILE__).'/libs/zip.lib.php';
        foreach(pluginsDev::pluginsList() as $plugin)
        {
            try {
                $sources = new Zip(WP_PLUGIN_DIR.'/download/'.$plugin['Dir'].'.zip', $plugin['Description']);
                $sources->addRecursive($plugin['DirPath'],$plugin['Dir'].'/');
            } catch (Exception $e) {}
        }
    }
}

?>