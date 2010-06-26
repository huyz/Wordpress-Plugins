<?php
/*
 * Plugin Name: SideBlogging
 * Plugin URI: http://github.com/cedbv/Wordpress-Plugins
 * Description: Affiche des articles courts dans la sidebar du blog.
 * Version: 0.5
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

if(!class_exists('Sideblogging')):
class Sideblogging {

	const domain = 'sideblogging';

	function Sideblogging() {
		define('SIDEBLOGGING_URL', WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)));		
		define('SIDEBLOGGING_OAUTH_CALLBACK', get_bloginfo('url').'/wp-admin/options-general.php?page=sideblogging');
		load_plugin_textdomain(self::domain,false,dirname(plugin_basename(__FILE__)).'/languages/');
	
		// Register custom post type
		add_action('init', array(&$this,'post_type_asides'));
				
		// Register Widget
		include dirname(__FILE__).'/sideblogging_Widget.php';
		add_action('widgets_init', create_function('', 'return register_widget("SideBlogging_Widget");'));
		
		if(is_admin())
		{
			// Register option settings
			add_action('admin_init', array(&$this,'register_settings'));
			// Register admin menu
			add_action('admin_menu', array(&$this,'menu'));
			// Header redirection
			add_action('init', array(&$this,'connect_to_oauth'));
			// Publish asides to Twitter
			add_action('publish_asides', array(&$this,'send_to_social'));
			
			// Dashboard Widget
			add_action('wp_dashboard_setup', array(&$this,'add_dashboard_widget'));
			add_action('admin_head-index.php', array(&$this,'dashboard_admin_js'));
			add_action('wp_ajax_sideblogging_widget_post', array(&$this,'ajax_action'));
		}
	}

	// Options page and help
	function menu() {
		$screen = add_options_page('SideBlogging', ' SideBlogging', 'manage_options', 'sideblogging', array(&$this,'options_page'));
		
		$text = '<h5>Aide de Sideblogging</h5>';
		$text .= '<p><a target="_blank" href="http://dev.twitter.com/apps/new">Créer une application Twitter</a><br />';
		$text .= '<a target="_blank" href="http://www.facebook.com/developers/apps.php">Créer une application Facebook</a></p>';
		
		$text .= '<h5>Détails spécifiques à Facebook</h5>';
		$text .= '<p>Pour Facebook, il peut être nécessaire de mofier le paramètre URL Connect.<br />
				Pour ce faire :</p>
				<ul>
				<li>Modifier les paramètres de l\'application.</li>
				<li>Allez dans la rubrique <em>Connexion</em>.</li>
				<li>Metttez <strong>'.get_bloginfo('url').' </strong>dans le champ URL Connect.</li>
				</ul>';
		add_contextual_help($screen,$text);
	}
	
	function add_dashboard_widget() {
		wp_add_dashboard_widget('sideblogging_dashboard_widget', __('Asides',self::domain), array(&$this,'dashboard_widget'));
	}	
	
	function dashboard_widget() {
		echo '<form id="sideblogging_dashboard_form" action="" method="post" class="dashboard-widget-control-form">';
		wp_nonce_field('sideblogging-quickpost');
		echo '<p style=height:20px;display:none;" id="sideblogging-status"></p>';
		//echo '<p><label for="sideblogging-title">Statut</label><br />';
		echo '<textarea name="sideblogging-title" id="sideblogging-title" style="width:100%"></textarea><br />';
		echo '<span id="sideblogging-count">140</span> caractères restants.<br />';
		echo '<input type="checkbox" name="sideblogging-draft" id="sideblogging-draft" />
		<label for="sideblogging-draft">Ajouter plus d\'informations.</label>';
		echo '</p>';
		echo '<p class="submit"><input type="submit" class="button-primary" value="' . esc_attr__( 'Publish' ) . '" /> 
		<img style="display:none;" src="'.SIDEBLOGGING_URL.'/images/loading.gif" alt="Loading..." id="sideblogging-loading" /></p>';
		echo '</form>';
	}
	
	function dashboard_admin_js() {
		?>
		<script type="text/javascript" >
		jQuery(document).ready(function($) {
			$('#sideblogging_dashboard_form').submit(function() {
				$('#sideblogging-loading').show();
				var data = {
					action: 'sideblogging_widget_post',
					_ajax_nonce: '<?php echo wp_create_nonce('sideblogging-quickpost'); ?>',
					form: $(this).serialize()
				};
				$.post(ajaxurl, data, function(response) {
					$('#sideblogging-loading').hide();
					if(response == 'ok')
					{
						$('#sideblogging-title').val('');
						$('#sideblogging-status').html('<strong>Brève publiée.</strong>')
						.addClass('updated').removeClass('error')
						.show(200);
					}
					else if(!isNaN(response) && response != 0)
					{
						location.href = 'post.php?action=edit&post='+response;
					}
					else
					{
						$('#sideblogging-status').html('<strong>Une erreur est survenue.</strong>')
						.addClass('error').removeClass('updated')
						.show(200);
					}
				});
				return false;
			});
			
			$('#sideblogging-title').keyup(function(e) {
				$('#sideblogging-title').val($('#sideblogging-title').val().substring(0, 140));
				var count = $('#sideblogging-title').val().length;
				var restant = 140 - count;
				$('#sideblogging-count').html(restant);
			});
			
		});
		</script>
		<?php
	}
	
	function ajax_action() {
		parse_str($_POST['form'], $form);
		check_ajax_referer('sideblogging-quickpost');
		
		$post['post_title'] = strip_tags(stripslashes($form['sideblogging-title']));
		$post['post_status'] = (isset($form['sideblogging-draft'])) ? 'draft' : 'publish';
		$post['post_type'] = 'asides';

		$id = wp_insert_post($post);
		
		if($post['post_status'] == 'draft' && $id != 0) // Brouillon OK, donc redirection
			echo $id;
		else if($id  != 0) // Publication immédiate OK
			echo 'ok';
		else // Echeck publication
			echo '0';
		exit;
	}

	/* Gère la redirection vers les pages de demande de connexion Oauth */
	function connect_to_oauth() {
		session_start();
		if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'],'connect_to_twitter')) // Twitter redirection
		{
			require_once('twitteroauth/twitteroauth.php');
			$options = get_option('sideblogging');
			$connection = new TwitterOAuth($options['twitter_consumer_key'], $options['twitter_consumer_secret']);
			$request_token = @$connection->getRequestToken(SIDEBLOGGING_OAUTH_CALLBACK); // Génère des notices en cas d'erreur de connexion
			if(200 == $connection->http_code)
			{
				$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
				$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
				$url = $connection->getAuthorizeURL($token,false);
				wp_redirect($url.'&oauth_access_type=write');
			}
			else
				wp_die('Twitter est indisponible pour le moment. Veuillez vérifier vos clés ou ré-essayer plus tard.');
		}
		else if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'],'connect_to_facebook')) // Facebook redirection
		{
			$options = get_option('sideblogging');
			$url = 'https://graph.facebook.com/oauth/authorize?client_id='.$options['facebook_consumer_key'].'&redirect_uri='.SIDEBLOGGING_OAUTH_CALLBACK.'&scope=publish_stream,offline_access';
			wp_redirect($url);
		}
	}
	
	/* Publie les nouvelles brèves sur les réseaux sociaux */
	function send_to_social($post_ID) {
		$post = get_post($post_ID);

		if($post->post_date == $post->post_modified)
		{
			$shortlink = get_bloginfo('url').'/?p='.$post_ID;
			$options = get_option('sideblogging');
			
			if(isset($options['twitter_token'])) // Twitter est configuré
			{
				require_once('twitteroauth/twitteroauth.php');
				$content = $post->post_title;
				
				if(strlen($post->post_content) > 0)
					$content .= ' '.$shortlink;

				$connection = new TwitterOAuth($options['twitter_consumer_key'], $options['twitter_consumer_secret'],
							$options['twitter_token']['oauth_token'],$options['twitter_token']['oauth_token_secret']);
				$connection->post('statuses/update', array('status' => $content));
			}
			
			if(isset($options['facebook_token']))
			{
				if( !class_exists( 'WP_Http' ) )
					include_once( ABSPATH . WPINC. '/class-http.php' );			
				$request = new WP_Http;
				$body = $options['facebook_token']['access_token'].'&message='.$post->post_title;
				
				if(strlen($post->post_content) > 0)
					$body .= '&link='.$shortlink;
					
				$result = $request->request('https://graph.facebook.com/me/feed', array('body' => $body, 'sslverify' => false, 'method' => 'POST'));
				//$body = json_decode($result['body'],true);
			}
			
		}
		return $post_ID;
	}
	
	/* Page de configuration */
	function options_page() {
		echo '<div class="wrap">';
		echo '<h2>SideBlogging</h2>';
		
		if(isset($_GET['oauth_verifier'])) // Twitter vérification finale
		{
			$options = get_option('sideblogging');
			require_once('twitteroauth/twitteroauth.php');
			$connection = new TwitterOAuth($options['twitter_consumer_key'], $options['twitter_consumer_secret'], $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
			$access_token = $connection->getAccessToken($_GET['oauth_verifier']);
			unset($_SESSION['oauth_token']);
			unset($_SESSION['oauth_token_secret']);
			if (200 == $connection->http_code)
			{
				$options['twitter_token'] = $access_token;
				update_option('sideblogging',$options);
				echo '<div class="updated"><p><strong>Compte Twitter enregistré.</strong></p></div>';
			}
			else
				echo '<div class="error"><p><strong>Erreur lors de la liaison avec Twitter.</strong></p></div>';
		}
		else if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'disconnect_from_twitter')) // Déconnexion de Twitter
		{
			$options = get_option('sideblogging');
			$options['twitter_token'] = '';
			update_option('sideblogging',$options);
		}
		else if(isset($_GET['code'])) // Facebook vérification finale
		{
			if( !class_exists( 'WP_Http' ) )
				include_once( ABSPATH . WPINC. '/class-http.php' );
				
			$options = get_option('sideblogging');
			
			$request = new WP_Http;
			$url = 'https://graph.facebook.com/oauth/access_token?client_id='.$options['facebook_consumer_key'].'&redirect_uri='.SIDEBLOGGING_OAUTH_CALLBACK.'&client_secret='.$options['facebook_consumer_secret'].'&code='.esc_attr($_GET['code']);
			$result = $request->request($url, array('sslverify' => false));
			$token = $result['body']; 
			
			$result = $request->request('https://graph.facebook.com/me?'.$token, array('sslverify' => false));
			$me = json_decode($result['body'],true);
			
			if(is_array($me))
			{
				$options['facebook_token']['access_token'] = $token;
				$options['facebook_token']['name'] = $me['name'];
				$options['facebook_token']['link'] = $me['link'];
				update_option('sideblogging',$options);
				echo '<div class="updated"><p><strong>Compte Facebook enregistré.</strong></p></div>';
			}
			else
				echo '<div class="error"><p><strong>Erreur lors de la liaison avec Facebook.</strong></p></div>';
		}
		else if(isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'disconnect_from_facebook')) // Déconnexion de Facebook
		{
			$options = get_option('sideblogging');
			$options['facebook_token'] = '';
			update_option('sideblogging',$options);
		}
		
		$options = get_option('sideblogging');
		
		echo '<h3>Paramètres des applications</h3>';
		
		echo '<form action="options.php" method="post">';
		settings_fields('sideblogging_settings');
		
		echo '<table class="form-table">';
		
		echo '<tr valign="top">
		<th scope="row">
		<label for="sideblogging_twitter_consumer_key">Twitter Consumer Key</label>
		</th><td>';
		echo '<input type="text" class="regular-text" value="'.$options['twitter_consumer_key'].'" name="sideblogging[twitter_consumer_key]" id="sideblogging_twitter_consumer_key" />';
		echo '</td></tr>';
		
		echo '<tr valign="top">
		<th scope="row">
		<label for="sideblogging_twitter_consumer_secret">Twitter Consumer Secret</label>
		</th><td>';
		echo '<input type="text" class="regular-text" value="'.$options['twitter_consumer_secret'].'" name="sideblogging[twitter_consumer_secret]" id="sideblogging_twitter_consumer_secret" />';
		echo '</td></tr>';
		
		echo '<tr valign="top">
		<th scope="row">
		<label for="sideblogging_facebook_consumer_key">Facebook APP ID</label>
		</th><td>';
		echo '<input type="text" class="regular-text" value="'.$options['facebook_consumer_key'].'" name="sideblogging[facebook_consumer_key]" id="sideblogging_facebook_consumer_key" />';
		echo '</td></tr>';
		
		echo '<tr valign="top">
		<th scope="row">
		<label for="sideblogging_facebook_consumer_secret">Facebook Secret Key</label>
		</th><td>';
		echo '<input type="text" class="regular-text" value="'.$options['facebook_consumer_secret'].'" name="sideblogging[facebook_consumer_secret]" id="sideblogging_facebook_consumer_secret" />';
		echo '</td></tr>';
		
		echo '</table>';
		
		echo '<p>N\'oubliez pas de consulter l\'aide contextuelle (en haut à droite de la page) pour plus d\'informations sur ces clés.</p>';

		echo '<p class="submit"><input type="submit" class="button-primary" value="'.__('Save Changes').'" /></p>';

		
		echo '<h3>Republier sur Twitter</h3>';
	
		if(empty($options['twitter_consumer_key']) || empty($options['twitter_consumer_secret']))
		{
			echo '<p>Vous devez configurer l\'application Twitter pour pouvoir vous connecter.</p>';
		}
		else if(empty($options['twitter_token']))
		{
			echo '<p>Pour publier automatiquement vos brèves sur Twitter, connectez-vous ci-desous :</p>';
			echo '<p><a href="'.wp_nonce_url('options-general.php?page=sideblogging','connect_to_twitter').'">
					<img src="'.SIDEBLOGGING_URL.'/images/twitter.png" alt="Connection à Twitter" />
				</a></p>';
		}
		else
		{
			echo '<p>Vous êtes connectés à Twitter en tant que <strong>@'.$options['twitter_token']['screen_name'].'</strong>. ';
			echo '<a href="'.wp_nonce_url('options-general.php?page=sideblogging','disconnect_from_twitter').'">Changer de compte ou désactiver</a>.</p>';
		}
		
		
		echo '<h3>Republier sur Facebook</h3>';
		
		if(empty($options['facebook_consumer_key']) || empty($options['facebook_consumer_secret']))
		{
			echo '<p>Vous devez configurer l\'application Facebook pour pouvoir vous connecter.</p>';
		}
		else if(empty($options['facebook_token']))
		{
			echo '<p>Pour publier automatiquement vos brèves sur Facebook, connectez-vous ci-desous :</p>';
			echo '<p><a href="'.wp_nonce_url('options-general.php?page=sideblogging','connect_to_facebook').'">
						<img src="'.SIDEBLOGGING_URL.'/images/facebook.gif" alt="Connection à Facebook" />
				</a></p>';
		}
		else
		{
			echo '<p>Vous êtes connectés à Facebook en tant que '.$options['facebook_token']['name'].'.</strong> ';
			echo '<a href="'.wp_nonce_url('options-general.php?page=sideblogging','disconnect_from_facebook').'">Changer de compte ou désactiver</a>.</p>';
		}
		echo '</div>';
	}
	
	// Register settings
	function register_settings() {
		register_setting('sideblogging_settings','sideblogging',array(&$this,'filter_options'));
	}
	
	function filter_options($options) {
		
		return $options;
	}
	
	// Add custom post type
	function post_type_asides() {
		register_post_type( 'asides',
			array(
				'label' => __('Asides',self::domain),
				'singular_label' => __('Aside',self::domain),
				'public' => true,
				'menu_position' => 5,
				'show_ui' => true,
				'capability_type' => 'post',
				'hierarchical' => false,
				'labels' => array(
					'add_new_item' => __('Add new aside',self::domain),
					'edit_item' => __('Edit aside',self::domain),
					'not_found' => __('No aside found',self::domain),
					'not_found_in_trash' => __('No aside found in trash',self::domain),
					'search_items' => __('Search asides',self::domain),
				),
				'supports' => array('title','editor'),
				'rewrite' => array('slug' => 'asides'),
			)
		);
	}
}
endif;

if(!isset($sideblogging))
	$sideblogging = new Sideblogging();