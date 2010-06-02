<?php
function proximusMoblogSync_cron($verbose=false) {

    //if(get_option('proximusMoblogSync_isrunning') == 1)
       // return 0;

    global $wpdb;

    $blogid = get_option('proximusMoblogSync_blogid');
    $id = get_option('proximusMoblogSync_id');
    $titre = get_option('proximusMoblogSync_titre');
    $article = get_option('proximusMoblogSync_article');
    $categorie = get_option('proximusMoblogSync_categorie');
    $user = get_option('proximusMoblogSync_user');

    if(!ctype_digit($id)) $id = 1; // Si l'id du prochain article est invalide, initialisé à 1

    if (ctype_digit($blogid)) { // Identifiant valide

        //update_option('proximusMoblogSync_isrunning',1);

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

            if($verbose)
            {
			echo '<li>'.$guid.'</li>';
            }
            
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

        //update_option('proximusMoblogSync_isrunning',0);
    } // Fin identifiant valide
}
?>
