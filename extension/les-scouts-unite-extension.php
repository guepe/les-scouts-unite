<?php
/**
 * Plugin Name: Les Scouts – Unité (extension)
 * Plugin URI: https://github.com/guepe/les-scouts-unite
 * Description: Le cœur du site d’une unité Les Scouts asbl : agenda des réunions, documents, droits des animateurs et du staff d’unité, Réglages → Unité, calendrier du téléphone, feuilles à imprimer et groupes WhatsApp. À utiliser avec le thème Les Scouts – Unité, qui en fait l’apparence.
 * Version: 3.1.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Pierre-Henri Delaval
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: les-scouts-unite
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Fichier principal de l'extension, pour ses adresses et sa version. */
const SCOUTSUNITE_EXTENSION = __FILE__;

/**
 * Version de l'extension (en-tête de ce fichier).
 *
 * @return string
 */
function scoutsunite_extension_numero() {
	static $version = null;
	if ( null === $version ) {
		$version = get_file_data( SCOUTSUNITE_EXTENSION, array( 'Version' => 'Version' ) )['Version'];
	}
	return $version;
}

/**
 * Version d'un fichier de l'extension pour le cache des navigateurs : la version de l'extension,
 * suivie de la date du fichier, pour qu'une correction soit vue même sans changer de version.
 *
 * @param string $fichier Chemin relatif à l'extension.
 * @return string
 */
function scoutsunite_extension_version( $fichier ) {
	$chemin = plugin_dir_path( SCOUTSUNITE_EXTENSION ) . $fichier;
	return scoutsunite_extension_numero() . ( is_readable( $chemin ) ? '.' . filemtime( $chemin ) : '' );
}

/**
 * Adresse d'un fichier de l'extension.
 *
 * @param string $fichier Chemin relatif à l'extension.
 * @return string
 */
function scoutsunite_extension_url( $fichier ) {
	return plugins_url( $fichier, SCOUTSUNITE_EXTENSION );
}

/**
 * Adresse d'un fichier du thème actif (feuille de style des feuilles à imprimer, logos),
 * '' si le thème ne l'a pas : l'apparence reste l'affaire du thème.
 *
 * @param string $fichier Chemin relatif au thème.
 * @return string
 */
function scoutsunite_extension_theme_url( $fichier ) {
	return is_readable( get_theme_file_path( $fichier ) ) ? get_theme_file_uri( $fichier ) : '';
}

/**
 * Charge le cœur, une fois le thème chargé. Un thème Les Scouts – Unité d'avant la 3.0
 * contient encore ce code : l'extension attend alors sa mise à jour, sans rien charger,
 * pour que les deux ne se marchent pas dessus.
 */
function scoutsunite_extension_charger() {
	if ( function_exists( 'scoutsunite_agenda_sections' ) ) {
		add_action( 'admin_notices', 'scoutsunite_extension_avis_theme' );
		return;
	}
	$dossier = plugin_dir_path( SCOUTSUNITE_EXTENSION ) . 'inc/';
	require $dossier . 'agenda.php';
	require $dossier . 'imprimer.php';
	require $dossier . 'calendrier.php';
	require $dossier . 'documents.php';
	require $dossier . 'unite.php';
	require $dossier . 'whatsapp.php';
	require $dossier . 'droits.php';
	require $dossier . 'staff.php';
}
add_action( 'after_setup_theme', 'scoutsunite_extension_charger', 0 );

/**
 * Avis à l'administrateur : le thème d'avant la 3.0 fait encore le travail de l'extension.
 */
function scoutsunite_extension_avis_theme() {
	if ( ! current_user_can( 'update_themes' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>Les Scouts – Unité (extension)</strong> attend la mise à jour du thème Les Scouts – Unité en version 3.0. Tant que le thème n’est pas mis à jour, c’est lui qui fait tourner l’agenda : rien ne change pour le site.</p></div>';
}

/**
 * À l'activation : l'adresse /agenda/ doit exister. Les règles sont recalculées au
 * chargement suivant, une fois le type de contenu « reunion » déclaré.
 */
function scoutsunite_extension_activer() {
	update_option( 'scoutsunite_agenda_flush', 1 );
}
register_activation_hook( __FILE__, 'scoutsunite_extension_activer' );
