<?php
/** 
 * La configuration de base de votre WordPress.
 *
 * Ce fichier contient les réglages de configuration suivants : réglages MySQL,
 * préfixe de table, clefs secrètes, langue utilisée, et ABSPATH.
 * Vous pouvez en savoir plus à leur sujet en allant sur 
 * {@link http://codex.wordpress.org/Editing_wp-config.php Modifier
 * wp-config.php} (en anglais). Vous devez obtenir les codes MySQL de votre 
 * hébergeur.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d'installation. Vous n'avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en "wp-config.php" et remplir les
 * valeurs.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define('DB_NAME', 'figaronrhigh');

/** Utilisateur de la base de données MySQL. */
define('DB_USER', 'figaronrhigh');

/** Mot de passe de la base de données MySQL. */
define('DB_PASSWORD', 'frG0Lpcq');

/** Adresse de l'hébergement MySQL. */
define('DB_HOST', 'mysql5-5.bdb');

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define('DB_CHARSET', 'utf8');

/** Type de collation de la base de données. 
  * N'y touchez que si vous savez ce que vous faites. 
  */
define('DB_COLLATE', '');

/**#@+
 * Clefs uniques d'authentification.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant 
 * {@link https://api.wordpress.org/secret-key/1.1/ Le service de clefs secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n'importe quel moment, afin d'invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define( 'AUTH_SALT', 'x0EDuY>[$D#E:qFjwp9kQY>XZv7A)/{`MguW][cLN!$0m7xmLQvE.9UOctrgO/_H' );
define( 'SECURE_AUTH_SALT', '1<;_A`%U,ceL{x2N?sv<vhN0ZtKa;VB6kKR$UbeSTgE2{<qzg-5BQZ|Rd2ftsIH9' );
define( 'LOGGED_IN_SALT', '1w$L_hIH_<GN@x2cS&e>p7%T,J34_{P/1ticx@{^z]/j0[,$cuf=)k==Z8gw3N9O' );
define( 'NONCE_SALT', '|:5(?=eU{Eq2|0R5uov{%b$?3osbf#Mt-YuF?3QijqVEA(CVR9[@,fC^e5lVi882' );
define( 'NONCE_KEY', 'Ly^7Tm50g)rHGEU3bkJ&!pLO' );
define( 'AUTH_KEY', 'EfvY&XDFSsYp^U3$Rwx92$W4' );
define( 'LOGGED_IN_KEY', 'nUQk%%j^Q#ehqGNbJfkF18Jb' );
define( 'SECURE_AUTH_KEY', 'R89I!cs#G7x4up*2AINKa^4t' );

/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique. 
 * N'utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés!
 */
$table_prefix  = 'wp_';

/**
 * Langue de localisation de WordPress, par défaut en Anglais.
 *
 * Modifiez cette valeur pour localiser WordPress. Un fichier MO correspondant
 * au langage choisi doit être installé dans le dossier wp-content/languages.
 * Par exemple, pour mettre en place une traduction française, mettez le fichier
 * fr_FR.mo dans wp-content/languages, et réglez l'option ci-dessous à "fr_FR".
 */
define ('WPLANG', 'fr_FR');

/* C'est tout, ne touchez pas à ce qui suit ! Bon blogging ! */

define('WP_ALLOW_MULTISITE', true) ;
define( 'MULTISITE', true );
define( 'VHOST', 'no' );
$base = '/';
define( 'DOMAIN_CURRENT_SITE', 'blog.figaronron.com' );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );

/** Chemin absolu vers le dossier de WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once(ABSPATH . 'wp-settings.php');