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

function restrictAccess_validate($entrees,$reponses) {
	foreach($entrees as $id => $rep)
	{
		if($rep != $reponses[$id])
			return false;
	}
	return true;
}

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
				if(restrictAccess_validate($_POST['reponse'],$reponses))
				{
					restrictAccess_autoLogin();
					if($_SERVER['HTTP_X_FORWARDED_FOR'])
                                            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
					elseif($_SERVER['HTTP_CLIENT_IP'])
                                            $ip = $_SERVER['HTTP_CLIENT_IP'];
					else
                                            $ip = $_SERVER['REMOTE_ADDR'];

					$infosclient .= '<hr /><p>IP : <a href="http://ipgetinfo.com/index.php?ip='.$ip.'">'.$ip.'</a><br />';
					
					if(!empty($_SERVER['GEOIP_COUNTRY_NAME']))
						$infosclient .= 'Localisation : '.utf8_encode($_SERVER['GEOIP_CITY']).', '.$_SERVER['GEOIP_COUNTRY_NAME'].' (<a href="http://maps.google.com/maps?q='.$_SERVER['GEOIP_LATITUDE'].','.$_SERVER['GEOIP_LONGITUDE'].'">carte</a>)<br />';
					
					require_once(dirname(__FILE__).'/libs/user.class.php');
					$ua = new UserAgent();
					
					$infosclient .= 'User-agent : '.$ua->getUserAgent().' '.$ua->g.'<br />';
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

if(!is_user_logged_in())
{
$aleatoire = range(0,$count-1);
shuffle($aleatoire);
header('HTTP/1.1 401 Unauthorized');
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php bloginfo('language'); ?>" lang="<?php bloginfo('language'); ?>">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
    <title><?php bloginfo('name'); ?> : Accès restreint</title>
<style type="text/css">
body {
background-color:#326B9F;
color:#050A0F;
font-family:"Lucida Grande", "Lucida Sans Unicode", sans-serif;
background-image:url('<?php echo WP_PLUGIN_URL; ?>/restricted-access/files/bg.png');
background-repeat:repeat-x;
}	

h1 {
margin-top:1.5em;
color:white;
text-align:center;
font-size:3em;
font-family:Georgia, serif;
}

h1 a {
color:white;
text-decoration:none;
}

#content {
padding:10px 10px;
background-color:white;
width:50%;
margin:auto;
-moz-border-radius:10px;
-webkit-border-radius:10px;
border-radius:10px;
}

#unlock {
margin-left:<?php echo rand(0,50); ?>%;
}

#helplink {
	cursor:pointer;
}

.vert {color: green;}
.rouge {color: red;}
</style>
<?php
wp_enqueue_script('jquery');
wp_enqueue_script('md5_script',WP_PLUGIN_URL.'/restricted-access/files/md5.js');
wp_head();
?>
</head>
<body>
<h1><a href="<?php bloginfo('url'); ?>"><?php bloginfo('name'); ?></a></h1>

<div id="content">
<h2>Identifiez-vous</h2>
<form id="formulaire" action="" method="post">
<p>
<label for="nom">Quel est votre nom ? <input type="text" name="nom" id="nom" size="30" value="" /></label>
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
	
	echo '<input class="reponses reponse-'.$i.'" name="reponse['.$id.']" type="text" size="30" value="'.$rep.'" />';
	echo '</label> <span class="verif-'.$i.'"></span>';
	echo '<br />';
	echo '<span class="questions question-'.$i.'">'.$questions[$id].'<br /></span>'."\n";

	++$i;
}
?>
</p>
<p><input type="submit" name="unlock[<?php echo rand(0,500); ?>]" id="unlock" value="Déverrouillez" /></p>
</form>
<h3 id="helplink">Un peu d'aide ?</h3>
<p id="help">
<a href="http://www.google.com/search?q=rainbow+table">Lien 1</a><br />
<a href="http://www.google.com/search?q=javascript">Lien 2</a><br />
<a href="http://www.google.com/search?q=md5">Lien 3</a>
</p>
</div>
<script type="text/javascript">
<!--
jQuery(document).ready(function($) {

$('.questions').hide();
$('#help').hide();

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
	var val = md5($('.reponse-'+num).val());
	if(val == reponse[num])
	{
		$('.verif-'+num).html('Bonne réponse');
		$('.verif-'+num).removeClass('rouge');
		$('.verif-'+num).addClass('vert');
		return true;
	}
	else
	{
		$('.verif-'+num).html('Mauvaise réponse');
		$('.verif-'+num).removeClass('vert');
		$('.verif-'+num).addClass('rouge');
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
	classe = $(this).attr('class');
	var num = classe.charAt(classe.length-1);
	validateForm(num);
});

$('#formulaire').submit(function(){
	var validation = 1;
	
	if($('#nom').val() == '')
	{
		validation = 0;
	}
	else
	{
		
	}
	
	$('.reponses').each(function(){
		classe = $(this).attr('class');
		var num = classe.charAt(classe.length-1);
		if(validateForm(num) == false)
			validation = 0;
	});

	if(validation == 1)
		return true;
	else
		return false;
});

$('#helplink').click(function(){
	$('#help').toggle(500);
});
$('#help a').attr('target','_blank');


});
-->
</script>
<?php wp_footer(); ?>
</body>
</html>
<?php
exit;
}
?>