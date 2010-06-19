<?php
$questions = array(
	'Je suis ... (mot de 3 lettres)',
	'Que reste-t-il lors de l\'apparition du néant ?',
	'De quelle couleur est le ciel lorsque le coeur est rempli de doutes ?',
	'Je me demande bien ce que cela fait d\'en avoir une.',
	'Quel est l\'âge de Napoléon ?',
	'Une chaine de 26 caractères ?',
	'Où sommes-nous ?',
	'Quel est le mot de passe le plus utilisé ?',
	'Combien de doigts pour 2,8194 mètres ? (arrondir à l\'unité)',
);

$reponses = array(
	'con',
	'rien',
	'noir',
	'vie',
	'240',
	'abcdefghijklmnopqrstuvwxyz',
	'blogornote',
	'password',
	'111',
);

if(count($questions) != count($reponses))
	die('Erreur dans la préparation des questions');
else
	$count = count($questions);


if(isset($_POST['unlock']) AND is_array($_POST['unlock']))
{
	if($count != count($_POST['reponse']))
		die('tentative de triche détectée...');
	else
	{
		if(isset($_POST['nom']))
		{
			$nom = strip_tags(stripslashes($_POST['nom']));
			if(!empty($nom))
			{
				if(restrictAccess::validate($_POST['reponse'],$reponses))
				{
					restrictAccess::autoLogin('visiteur');
					if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
						$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
					elseif(isset($_SERVER['HTTP_CLIENT_IP']))
						$ip = $_SERVER['HTTP_CLIENT_IP'];
					else
						$ip = $_SERVER['REMOTE_ADDR'];

					$infosclient = '<hr /><p>IP : <a href="http://ipgetinfo.com/index.php?ip='.$ip.'">'.$ip.'</a><br />';
					
					if(!empty($_SERVER['GEOIP_COUNTRY_NAME']))
						$infosclient .= 'Localisation : '.utf8_encode($_SERVER['GEOIP_CITY']).', '.$_SERVER['GEOIP_COUNTRY_NAME'].' (<a href="http://maps.google.com/maps?q='.$_SERVER['GEOIP_LATITUDE'].','.$_SERVER['GEOIP_LONGITUDE'].'">carte</a>)<br />';
					
					require_once(dirname(__FILE__).'/libs/useragent.class.php');
					$ua = new UserAgent();
					
					$infosclient .= 'User-agent : '.$ua->getUserAgent().'<br />';
					$infosclient .= 'Navigateur : '.$ua->getBrowser().' '.$ua->getBrowserVersion().'<br />';
					$infosclient .= 'OS : '.$ua->getOS().'</p>';
					
					$content = '<p>L\'énigme vient d\'être résolue par '.$nom.'.</p>'.$infosclient;
					
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
					$headers .= 'From: Blogornote <blogornote@boverie.eu>' . "\r\n";

					wp_mail(get_bloginfo('admin_email'),"Le mystère a été découvert !",$content,$headers);
				}
			}
		}
	}
}

if(!is_user_logged_in()): // Si le visiteur n'est pas connecté
$aleatoire = range(0,$count-1);
shuffle($aleatoire);
header('HTTP/1.1 401 Unauthorized');

if(!is_home()) // Message d'erreur ailleurs que sur la home
{
	get_header();
	echo '<div id="container"><div id="content">';
	echo '<p>Désolé, cette page n\'est pas publique.<br />
			Vous pouvez vous connecter à partir de <a href="'.get_bloginfo('url').'">la page d\'accueil</a>.</p>';
	echo '</div></div>';
	get_footer();
	exit;
}

wp_enqueue_script('jquery');
wp_enqueue_script('md5_script',WP_PLUGIN_URL.'/'.dirname(plugin_basename(__FILE__)).'/files/md5.js',false,'1.0');
get_header();
?>
<div id="container">
<div id="content">
<h2>Identifiez-vous</h2>
<form id="formulaire" action="" method="post">
<p>
<label for="nom">Quel est votre nom : <input type="text" name="nom" id="nom" size="30" value="" /></label>
<br />
<a href="<?php bloginfo('url'); ?>/wp-login.php?redirect_to=<?php bloginfo('url'); ?>">Déjà inscrit ?</a>
</p>

<h2>Déverrouillez l'accès</h2>
<p>
<?php
$i = 1;
foreach($aleatoire as $id)
{
	echo '<label>Clé '.$i.' : ';
	
	if(isset($_POST['reponse'][$id]))
		$rep = $_POST['reponse'][$id];
	else
		$rep = '';
	
	echo '<input autocomplete="off" class="reponses reponse-'.$i.'" name="reponse['.$id.']" type="text" size="30" value="'.$rep.'" />';
	echo '</label> <span class="verif-'.$i.'"></span>';
	echo '<br />';
	echo '<span class="questions question-'.$i.'">'.$questions[$id].'<br /></span>'."\n";

	++$i;
}
?>
</p>
<p>
	<input type="submit" name="unlock[<?php echo rand(0,500); ?>]" id="unlock" value="Déverrouillez" />
	<span id="unlock-status" style="color:red;"></span>
</p>
</form>
<h3 id="helplink" style="cursor:help;">Un peu d'aide ?</h3>
<ul id="help">
	<li><a href="http://www.google.com/search?q=rainbow+table">Lien 1</a></li>
	<li><a href="http://www.google.com/search?q=afficher+le+code+source+firefox">Lien 2</a></li>
	<li><a href="http://www.google.com/search?q=md5">Lien 3</a></li>
</ul>
</div><!-- #content -->
</div><!-- #container -->

<script type="text/javascript">
<!--
jQuery(document).ready(function($) {

$('.questions').hide();
$('#help').hide();
$('#unlock-status').hide();

var reponse = new Array();
<?php
$i = 1;
foreach($aleatoire as $id)
{
	echo 'reponse['.$i.'] = \''.md5($reponses[$id]).'\';'."\n";
	++$i;
}
?>
function validateForm(num) {
	var val = $('.reponse-'+num).val();

	if(md5(val) == reponse[num])
	{
		$('.verif-'+num).html('<span style="color:green;">Bonne réponse</span>');
		return true;
	}
	else
	{
		$('.verif-'+num).html('<span style="color:red;">Mauvaise réponse</span>');
		return false;
	}
}

$('.reponses').focus(function(){
	$('.questions').fadeOut(250);
	
	classe = $(this).attr('class');
	var num = classe.charAt(classe.length-1);
		
	$('.question-'+num).fadeIn(250);
});
$('.reponses').blur(function(){
	$('.questions').fadeOut(250);
});

$('.reponses').keyup(function(){
	var classe = $(this).attr('class').split('-');
	var num = classe[1];
	validateForm(num);
});

$('#formulaire').submit(function(){
	var validation = true;
	
	if($('#nom').val() == '')
	{
		$('#unlock-status').html(' Vous devez entrer un nom !');
		$('#unlock-status').fadeIn(200).delay(800).fadeOut(200);
		return false;
	}
	
	$('.reponses').each(function(){
		var classe = $(this).attr('class').split('-');
		var num = classe[1];
		if(validateForm(num) == false)
			validation = false;
	});

	if(validation == false)
	{
		$('#unlock-status').html(' Accès refusé');
		$('#unlock-status').fadeIn(200).delay(800).fadeOut(200);
	}
	
	return validation;
});

$('#helplink').click(function(){
	$('#help').slideToggle(500);
});
$('#help a').attr('target','_blank');


});
-->
</script>
<?php
get_footer();
exit; // Ne pas afficher le contenu réel de la page
endif; // Fin de si le visiteur n'est pas connecté
?>