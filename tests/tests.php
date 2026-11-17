<?php
/**
 * Tests du thème et de son extension, dans un WordPress neuf (WordPress Playground) : droits, répétition, annulation,
 * notes protégées, feuilles à imprimer, QR codes et liens. Lancés par tests/lancer.sh et avant chaque publication.
 *
 * Écrit les résultats dans tests/resultats.txt ; une exception arrête le Playground en erreur
 * si un test échoue.
 *
 * @package les-scouts-unite
 */

require '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

$extension = scoutsunite_extension_numero();
$resultats = array( 'PHP ' . PHP_VERSION . ' · WordPress ' . get_bloginfo( 'version' ) . ' · thème ' . wp_get_theme()->get( 'Version' ) . ' · extension ' . $extension, '' );
$echecs    = 0;
$ok        = function ( $condition, $message ) use ( &$resultats, &$echecs ) {
	$resultats[] = ( $condition ? 'OK     ' : 'ÉCHEC  ' ) . $message;
	$echecs     += $condition ? 0 : 1;
};
$reunion   = function ( $titre, $meta, $extra = array() ) {
	return wp_insert_post( array_merge( array( 'post_type' => 'reunion', 'post_status' => 'publish', 'post_title' => $titre, 'meta_input' => $meta ), $extra ) );
};
$dans      = function ( $jours ) {
	return wp_date( 'Y-m-d', strtotime( '+' . $jours . ' days' ) );
};

$admin = get_user_by( 'login', 'admin' )->ID;
wp_set_current_user( $admin );
$anim = wp_create_user( 'anim', 'anim-pass-123', 'anim@example.org' );
( new WP_User( $anim ) )->set_role( 'scoutsunite_animateur' );
update_user_meta( $anim, 'scoutsunite_sections', array( 'baladins' ) );
$staff = wp_create_user( 'staff', 'staff-pass-123', 'staff@example.org' );
( new WP_User( $staff ) )->set_role( 'scoutsunite_staff' );

/* Rôles ------------------------------------------------------------------ */

$ok( SCOUTSUNITE_DROITS_VERSION === (int) get_option( 'scoutsunite_droits_version' ), 'rôles à jour (version ' . SCOUTSUNITE_DROITS_VERSION . ')' );
$ok( ! user_can( $staff, 'unfiltered_html' ), 'staff d’unité sans HTML libre' );
$ok( user_can( $staff, 'manage_unite' ) && user_can( $staff, 'edit_others_pages' ), 'staff d’unité : Réglages → Unité et toutes les pages' );

/* Pages de section --------------------------------------------------------- */

$baladins = get_page_by_path( 'sections/baladins' );
$autre    = get_page_by_path( 'sections/louveteaux' );
$ok( $baladins && $autre, 'contenu de démonstration présent' );
wp_set_current_user( $anim );
$ok( current_user_can( 'edit_post', $baladins->ID ) && ! current_user_can( 'edit_post', $autre->ID ), 'animateur : sa page oui, celle d’une autre section non' );
$ok( ! current_user_can( 'delete_post', $baladins->ID ), 'animateur : ne supprime pas sa page' );
$ok( ! wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Fausse page' ) ), 'animateur : aucune page créée par wp_insert_post' );

$requete = new WP_REST_Request( 'POST', '/wp/v2/pages' );
$requete->set_body_params( array( 'title' => 'Nouvelle', 'status' => 'publish' ) );
$ok( 403 === rest_do_request( $requete )->get_status(), 'animateur : création de page refusée par l’API (403)' );

require_once ABSPATH . WPINC . '/class-IXR.php';
require_once ABSPATH . WPINC . '/class-wp-xmlrpc-server.php';
add_filter(
	'wp_die_handler',
	function () {
		return function ( $message ) {
			throw new RuntimeException( is_wp_error( $message ) ? $message->get_error_message() : wp_strip_all_tags( (string) $message ) );
		};
	}
);
try {
	$xml = ( new wp_xmlrpc_server() )->wp_newPost( array( 1, 'anim', 'anim-pass-123', array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Paiement camp' ) ) );
	$cree = ! ( $xml instanceof IXR_Error );
} catch ( RuntimeException $e ) {
	$cree = false;
}
wp_set_current_user( $anim );
$ok( ! $cree && ! get_posts( array( 'post_type' => 'page', 'title' => 'Paiement camp', 'post_status' => 'any' ) ), 'animateur : création de page refusée par XML-RPC' );

$avant   = get_post( $baladins->ID );
$modele  = get_post_meta( $baladins->ID, '_wp_page_template', true );
$requete = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $baladins->ID );
$requete->set_body_params( array( 'content' => $avant->post_content . '<!-- wp:paragraph --><p>Ajouté</p><!-- /wp:paragraph -->', 'status' => 'pending', 'slug' => 'pirate', 'parent' => 0, 'password' => 'x' ) );
$statut  = rest_do_request( $requete )->get_status();
update_post_meta( $baladins->ID, '_wp_page_template', 'page' );
$apres = get_post( $baladins->ID );
$ok( 200 === $statut && false !== strpos( $apres->post_content, 'Ajouté' ), 'animateur : enregistre le contenu de sa page par l’API' );
$ok( 'publish' === $apres->post_status && 'baladins' === $apres->post_name && $avant->post_parent === $apres->post_parent && '' === $apres->post_password, 'animateur : état, adresse, parent et mot de passe figés' );
$ok( get_post_meta( $baladins->ID, '_wp_page_template', true ) === $modele, 'animateur : modèle de page figé' );

/* Champs des réunions ------------------------------------------------------ */

$r = $reunion( 'Champs', array( 'su_section' => 'louveteaux', 'su_date' => '2026-12-01 xx', 'su_debut' => '9:30', 'su_fin' => '25:00', 'su_jusqua' => '2026-02-30' ) );
$ok( 'baladins' === get_post_meta( $r, 'su_section', true ), 'animateur : la section est forcée sur la sienne' );
$ok( '' === get_post_meta( $r, 'su_date', true ) && '' === get_post_meta( $r, 'su_jusqua', true ), 'dates invalides refusées' );
$ok( '09:30' === get_post_meta( $r, 'su_debut', true ) && '' === get_post_meta( $r, 'su_fin', true ), 'heures : « 9:30 » devient 09:30, « 25:00 » refusé' );

/* Lecture des contenus privés et fichiers des documents --------------------- */

wp_set_current_user( $admin );
$prive_autre = $reunion( 'Privée louveteaux', array( 'su_section' => 'louveteaux', 'su_date' => $dans( 40 ) ), array( 'post_status' => 'private' ) );
$prive_sien  = $reunion( 'Privée baladins', array( 'su_section' => 'baladins', 'su_date' => $dans( 40 ) ), array( 'post_status' => 'private' ) );
$brouillon   = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Brouillon' ) );
$joint_prive = wp_insert_attachment( array( 'post_title' => 'privé', 'post_mime_type' => 'application/pdf', 'post_status' => 'inherit' ), false, $brouillon );
$joint_libre = wp_insert_attachment( array( 'post_title' => 'public', 'post_mime_type' => 'application/pdf', 'post_status' => 'inherit' ), false, 0 );
wp_set_current_user( $anim );
$ok( ! current_user_can( 'read_post', $prive_autre ) && current_user_can( 'read_post', $prive_sien ), 'animateur : lit les réunions privées de sa section seulement' );
$doc = wp_insert_post( array( 'post_type' => 'document', 'post_status' => 'publish', 'post_title' => 'Doc', 'meta_input' => array( 'su_section' => 'baladins', 'su_fichier' => (string) $joint_prive ) ) );
$ok( '' === get_post_meta( $doc, 'su_fichier', true ), 'document : fichier joint à un brouillon refusé' );
update_post_meta( $doc, 'su_fichier', (string) $joint_libre );
$ok( (string) $joint_libre === get_post_meta( $doc, 'su_fichier', true ), 'document : fichier de la médiathèque accepté' );
wp_set_current_user( $admin );

/* Répétition chaque semaine ------------------------------------------------ */

$origine = $reunion( 'Répétée', array( 'su_section' => 'eclaireurs', 'su_date' => '2027-01-02' ) );
$compte  = function () {
	return count( get_posts( array( 'post_type' => 'reunion', 'title' => 'Répétée', 'numberposts' => -1, 'fields' => 'ids' ) ) );
};
$repete  = function ( $jusqua ) use ( $origine ) {
	update_post_meta( $origine, 'su_repeter', $jusqua );
	scoutsunite_agenda_repeter( get_post( $origine ) );
};
$repete( '2027-01-16' );
$ok( 3 === $compte(), 'répétition : jusqu’au 16 janvier, deux copies' );
$repete( '2027-01-30' );
$ok( 5 === $compte(), 'répétition : prolongée, seulement les semaines qui manquent' );
$repete( '2027-01-09' );
$repete( '2027-01-30' );
$ok( 5 === $compte(), 'répétition : raccourcie puis rallongée, rien de plus' );
$repete( '2029-12-31' );
$ok( 61 === $compte(), 'répétition : au plus 60 semaines' );

/* Annulation --------------------------------------------------------------- */

$annulee = $reunion( 'Réunion', array( 'su_section' => 'pionniers', 'su_date' => $dans( 1 ), 'su_debut' => '08:00', 'su_annulee' => '1', 'su_annulee_pourquoi' => 'Examens' ) );
$ics     = scoutsunite_calendrier_evenement( get_post( $annulee ) );
$ok( false !== strpos( $ics, 'STATUS:CANCELLED' ) && false !== strpos( $ics, 'ANNULÉ' ), 'annulation : le calendrier la barre' );
list( $prochaine, $annulees ) = scoutsunite_agenda_prochaine_maintenue( scoutsunite_agenda_a_venir( 'pionniers', 8 ) );
$ok( $prochaine && $prochaine->ID !== $annulee && 1 === count( $annulees ), 'annulation : la prochaine réunion passe à la suivante, avec un rappel' );

/* Notes : boucle et mot de passe ------------------------------------------ */

$boucle = $reunion( 'Boucle', array( 'su_section' => 'baladins', 'su_date' => $dans( 0 ), 'su_debut' => '08:00', 'su_type' => 'camp', 'su_jusqua' => $dans( 3 ) ), array( 'post_content' => '<!-- wp:paragraph --><p>Notes de la boucle</p><!-- /wp:paragraph --><!-- wp:scoutsunite/prochaine-reunion {"section":"baladins"} /-->' ) );
$rendu  = do_blocks( '<!-- wp:scoutsunite/prochaine-reunion {"section":"baladins"} /-->' );
$ok( 1 === substr_count( $rendu, 'Notes de la boucle' ), 'boucle : une réunion n’est pas affichée dans ses propres notes' );
$secret = $reunion( 'Protégée', array( 'su_section' => 'louveteaux', 'su_date' => $dans( 1 ) ), array( 'post_password' => 'secret', 'post_content' => '<!-- wp:paragraph --><p>NOTES-SECRETES</p><!-- /wp:paragraph -->' ) );
$ok( '' === scoutsunite_agenda_notes( get_post( $secret ) ) && false === strpos( scoutsunite_calendrier_evenement( get_post( $secret ) ), 'NOTES-SECRETES' ), 'mot de passe : les notes ne sortent pas' );
$protegee = $reunion( 'Répétée protégée', array( 'su_section' => 'louveteaux', 'su_date' => $dans( 2 ), 'su_repeter' => $dans( 16 ) ), array( 'post_password' => 'secret', 'post_content' => '<!-- wp:paragraph --><p>NOTES-REPETEES</p><!-- /wp:paragraph -->' ) );
scoutsunite_agenda_repeter( get_post( $protegee ) );
$copies = get_posts( array( 'post_type' => 'reunion', 'title' => 'Répétée protégée', 'numberposts' => -1, 'exclude' => array( $protegee ) ) );
$fuite  = array_filter(
	$copies,
	function ( $c ) {
		return 'secret' !== $c->post_password || false !== strpos( scoutsunite_calendrier_evenement( $c ), 'NOTES-REPETEES' );
	}
);
$ok( 2 === count( $copies ) && ! $fuite, 'mot de passe : les copies d’une réunion répétée restent protégées' );

/* Feuilles à imprimer : les camps à retenir ------------------------------- */

$camp    = $reunion( 'Camp d’été', array( 'su_section' => 'eclaireurs', 'su_type' => 'camp', 'su_date' => '2027-07-20', 'su_jusqua' => '2027-08-03' ) );
$reunion( 'Camp annulé', array( 'su_section' => 'eclaireurs', 'su_type' => 'camp', 'su_date' => '2027-07-05', 'su_jusqua' => '2027-07-12', 'su_annulee' => '1' ) );
$rappel  = scoutsunite_imprimer_camps( 'eclaireurs' );
$ok( false !== strpos( $rappel, 'Camp d’été' ) && false !== strpos( $rappel, '2027' ), 'frigo : le camp d’été est à retenir, avec l’année' );
$ok( false === strpos( $rappel, 'Camp annulé' ), 'frigo : un camp annulé n’est pas rappelé' );
$ok( false === strpos( scoutsunite_imprimer_camps( 'eclaireurs', $camp ), 'Camp d’été' ), 'frigo : la convocation d’un camp ne se rappelle pas elle-même' );
$ok( false === strpos( scoutsunite_imprimer_camps( 'louveteaux' ), 'Camp d’été' ), 'frigo : pas le camp d’une autre section' );

/* QR codes et liens -------------------------------------------------------- */

$ok( '' === scoutsunite_qr_svg( 'https://chat.whatsapp.com/' . str_repeat( 'A', 400 ), 'x' ), 'QR code : un lien trop long ne fait pas tomber la page' );
$ok( 0 === strpos( scoutsunite_qr_svg( 'https://chat.whatsapp.com/AbCd', '' ), '<svg' ), 'QR code : un lien normal est dessiné' );
$html = '<a href="/contact/">C</a><a href="//cdn.ex/x">X</a>';
$ok( scoutsunite_liens_racine( $html ) === $html, 'liens : rien ne change sur un site à la racine' );
$sous = function () {
	return 'https://exemple.be/scouts';
};
add_filter( 'home_url', $sous );
$ok( '<a href="/scouts/contact/">C</a><a href="//cdn.ex/x">X</a>' === scoutsunite_liens_racine( $html ), 'liens : le sous-dossier est ajouté' );
$ok( '<a href="/scouts/contact/">C</a>' === scoutsunite_liens_racine( '<a href="/scouts/contact/">C</a>' ), 'liens : jamais deux fois' );
remove_filter( 'home_url', $sous );

/* Fiches du staff et cartes ------------------------------------------------ */

wp_set_current_user( $admin );
update_option( 'scoutsunite_unite', array( 'baladins_chef' => 'Akéla – Marie (santé) 0470 12 34 56', 'baladins_staff' => "Castor – Paul\nLéa" ) );
delete_option( 'scoutsunite_reprise_fiches' );
scoutsunite_reprise_fiches();
$reprises = scoutsunite_staff_fiches( 'baladins' );
$ok( 3 === count( $reprises ) && $reprises[0]['chef'] && 'Akéla' === $reprises[0]['totem'] && 'santé' === $reprises[0]['fonction'] && '0470 12 34 56' === $reprises[0]['gsm'], 'fiches : les lignes du staff de Réglages → Unité deviennent des fiches, chef en tête' );
scoutsunite_reprise_fiches();
$ok( 3 === count( scoutsunite_staff_fiches( 'baladins' ) ), 'fiches : la reprise ne se fait qu’une fois' );

$fiche = function ( $meta ) {
	$id = wp_insert_post( array( 'post_type' => 'animateur', 'post_status' => 'draft', 'meta_input' => $meta ) );
	scoutsunite_staff_titre( $id );
	return $id;
};
$sienne = $fiche( array( 'su_section' => 'louveteaux', 'su_chef' => '1', 'su_genre' => 'f', 'su_prenom' => 'Marie', 'su_nom' => 'NOM-PRIVE', 'su_totem' => 'Castor', 'su_quali' => 'QUALI-PRIVE', 'su_totem_meute' => 'Bagheera', 'su_gsm' => '0470 11 22 33', 'su_compte' => (string) $anim ) );
$autre  = $fiche( array( 'su_section' => 'louveteaux', 'su_prenom' => 'Paul', 'su_totem' => 'Renard' ) );
$ancien = $fiche( array( 'su_section' => 'louveteaux', 'su_prenom' => 'Jean', 'su_totem' => 'ANCIEN-TOTEM', 'su_ancien' => '1' ) );
$ok( 'publish' === get_post_status( $sienne ), 'fiches : toujours publiées (pas de brouillon qui disparaît)' );
$ok( 'Cheffe de meute' === scoutsunite_staff_role( scoutsunite_staff_fiche( $sienne ) ) && 'Animateur' === scoutsunite_staff_role( scoutsunite_staff_fiche( $autre ) ), 'fiches : rôle écrit d’après chef, section et il/elle' );

$type = get_post_type_object( 'animateur' );
$ok( ! $type->public && ! $type->publicly_queryable && ! $type->show_in_rest, 'fiches : ni page publique ni API' );
$ok( 404 === rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/animateur' ) )->get_status(), 'fiches : aucune route dans l’API (404)' );
$ok( user_can( $staff, 'edit_post', $autre ) && user_can( $staff, $type->cap->create_posts ) && user_can( $staff, 'delete_post', $autre ), 'fiches : le staff d’unité les gère toutes' );
$ok( user_can( $anim, 'edit_post', $sienne ) && ! user_can( $anim, 'edit_post', $autre ) && ! user_can( $anim, 'read_post', $autre ), 'fiches : un animateur modifie sa fiche, pas celle des autres' );
$ok( ! user_can( $anim, 'delete_post', $sienne ) && ! user_can( $anim, $type->cap->create_posts ), 'fiches : un animateur n’en ajoute ni n’en supprime' );

// Par XML-RPC, l'animateur relié à sa fiche ne change ni sa section, ni son rôle de chef, ni « ancien », ni l'ordre.
global $wpdb;
$avant_xml = get_current_user_id();
$mid = function ( $cle ) use ( $wpdb, $sienne ) {
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $sienne, $cle ) );
};
try {
	( new wp_xmlrpc_server() )->wp_editPost(
		array(
			1,
			'anim',
			'anim-pass-123',
			$sienne,
			array(
				'post_status'   => 'draft',
				'menu_order'    => 99,
				'custom_fields' => array(
					array( 'id' => $mid( 'su_section' ), 'key' => 'su_section', 'value' => 'pionniers' ),
					array( 'id' => $mid( 'su_chef' ) ),
					array( 'key' => 'su_ancien', 'value' => '1' ),
					array( 'id' => $mid( 'su_gsm' ), 'key' => 'su_gsm', 'value' => '0470 99 99 99' ),
				),
			),
		)
	);
} catch ( RuntimeException $e ) {
	unset( $e );
}
wp_set_current_user( $avant_xml );
$apres = scoutsunite_staff_fiche( $sienne );
$ok( 'louveteaux' === $apres['section'] && $apres['chef'] && ! $apres['ancien'] && 0 === $apres['ordre'] && '0470 99 99 99' === $apres['gsm'], 'fiches : par XML-RPC, un animateur change son GSM, pas sa section, son rôle ni l’ordre' );
$ok( ! user_can( $anim, 'edit_post_meta', $sienne, 'su_photo' ) && user_can( $staff, 'edit_post_meta', $sienne, 'su_chef' ), 'fiches : la photo et les champs réservés, pour le staff d’unité seulement' );

$rendu = do_blocks( '<!-- wp:scoutsunite/staff {"section":"louveteaux"} /-->' );
$ok( (int) strpos( $rendu, 'Bagheera' ) < strpos( $rendu, 'Renard' ) && false !== strpos( $rendu, 'chef de section' ), 'page de section : le staff vient des fiches, chef en tête' );
$ok( false !== strpos( $rendu, 'Bagheera' ) && false === strpos( $rendu, 'Castor' ), 'page de section : chez les Louveteaux, le totem de meute' );
$ok( false === strpos( $rendu, 'NOM-PRIVE' ) && false === strpos( $rendu, 'QUALI-PRIVE' ) && false === strpos( $rendu, 'ANCIEN-TOTEM' ), 'page de section : ni nom, ni quali, ni ancien' );
$ok( false !== strpos( scoutsunite_unite_html( 'louveteaux-chef' ), 'Bagheera – Marie' ) && false !== strpos( scoutsunite_imprimer_staff( 'louveteaux' ), 'Bagheera' ) && false !== strpos( scoutsunite_imprimer_staff( 'louveteaux' ), 'Renard' ), 'page Contact et frigo : lus dans les fiches' );

$planches = scoutsunite_staff_planches( scoutsunite_staff_fiches( 'louveteaux' ) );
$ok( 2 === substr_count( $planches, 'carte--recto' ) && 2 === substr_count( $planches, 'carte--verso' ) && false !== strpos( $planches, 'Bagheera' ) && false !== strpos( $planches, 'Cheffe de meute' ), 'cartes : recto et verso de chaque fiche, totem de meute et rôle' );
$ok( strpos( $planches, 'Animateur' ) < strpos( $planches, 'Cheffe de meute' ), 'cartes : au verso, les colonnes sont inversées' );
$reglages = get_option( 'scoutsunite_unite', array() );
update_option( 'scoutsunite_unite', array( 'formulaire_inscription' => 'https://exemple.be/inscription', 'whatsapp_unite' => 'https://chat.whatsapp.com/AbCd' ) + (array) $reglages );
$verso = scoutsunite_staff_carte_verso( scoutsunite_staff_fiche( $fiche( array( 'su_section' => 'unite', 'su_prenom' => 'Luc', 'su_chef' => '1' ) ) ) );
update_option( 'scoutsunite_unite', $reglages );
$ok( false !== strpos( $verso, 'formulaire d’inscription' ) && false === strpos( $verso, 'WhatsApp' ), 'cartes : au verso du staff d’unité, le QR du formulaire d’inscription' );
$ok( '' === scoutsunite_staff_photo_url( $sienne ) && file_exists( scoutsunite_staff_dossier() . '/.htaccess' ), 'photos : dossier protégé, pas d’adresse sans photo' );
$ok( ! user_can( 0, 'read_post', $sienne ), 'photos : une personne non connectée ne lit pas une fiche (ni sa photo)' );
delete_transient( 'scoutsunite_photos_exposees' );
$etat = scoutsunite_staff_photos_exposees();
$ok( in_array( $etat, array( 'oui', 'non', 'inconnu' ), true ) && ! glob( scoutsunite_staff_dossier() . '/verification-*' ), 'photos : le dossier est vérifié, sans laisser de fichier témoin' );
$avant = get_current_user_id();
set_current_screen( 'edit-animateur' );
wp_set_current_user( $staff );
$avis = function ( $etat ) {
	set_transient( 'scoutsunite_photos_exposees', $etat, 60 );
	ob_start();
	scoutsunite_staff_avis();
	return ob_get_clean();
};
$ok( false !== strpos( $avis( 'oui' ), 'ne sont pas protégées' ) && false === strpos( $avis( 'non' ), 'ne sont pas protégées' ), 'photos : un avis si le serveur montre le dossier' );
delete_transient( 'scoutsunite_photos_exposees' );
$GLOBALS['current_screen'] = null;
$GLOBALS['typenow']        = '';
$GLOBALS['taxnow']         = '';
wp_set_current_user( $avant );

/* Agenda de la famille : plusieurs sections ------------------------------- */

$ok( array( 'baladins', 'louveteaux' ) === scoutsunite_agenda_choix_lire( 'louveteaux,Baladins,inconnue,louveteaux' ) && 'Baladins et Louveteaux' === scoutsunite_agenda_choix_noms( array( 'baladins', 'louveteaux' ) ), 'famille : sections lues dans l’ordre, sans doublon ni inconnue' );

$famille_b = $reunion( 'FAMILLE-BALADINS', array( 'su_section' => 'baladins', 'su_date' => $dans( 12 ), 'su_debut' => '14:00' ) );
$famille_l = $reunion( 'FAMILLE-LOUVETEAUX', array( 'su_section' => 'louveteaux', 'su_date' => $dans( 13 ), 'su_debut' => '14:00' ) );
$famille_e = $reunion( 'FAMILLE-ECLAIREURS', array( 'su_section' => 'eclaireurs', 'su_date' => $dans( 14 ), 'su_debut' => '14:00' ) );
$famille_u = $reunion( 'FAMILLE-UNITE', array( 'su_section' => 'unite', 'su_date' => $dans( 15 ), 'su_debut' => '14:00' ) );

$_GET['section'] = 'louveteaux,baladins';
$agenda          = do_blocks( '<!-- wp:scoutsunite/agenda /-->' );
unset( $_GET['section'] );
$ok( false !== strpos( $agenda, 'FAMILLE-BALADINS' ) && false !== strpos( $agenda, 'FAMILLE-LOUVETEAUX' ) && false === strpos( $agenda, 'FAMILLE-ECLAIREURS' ) && false === strpos( $agenda, 'FAMILLE-UNITE' ), 'famille : l’agenda montre les deux sections, sans les autres ni toute l’unité' );
$ok( false !== strpos( $agenda, 'section=baladins,louveteaux,eclaireurs' ) && preg_match( '#agenda__filtre-baladins" href="[^"]*section=louveteaux"#', $agenda ) && 2 === substr_count( $agenda, 'aria-current="true"' ), 'famille : un bouton ajoute sa section, un bouton allumé la retire' );

$ids = wp_list_pluck( scoutsunite_calendrier_reunions( array( 'baladins', 'louveteaux' ) ), 'ID' );
$ok( in_array( $famille_b, $ids, true ) && in_array( $famille_l, $ids, true ) && in_array( $famille_u, $ids, true ) && ! in_array( $famille_e, $ids, true ), 'famille : un seul calendrier, avec toute l’unité' );
$ok( false !== strpos( scoutsunite_calendrier_url( array( 'louveteaux', 'baladins' ) ), 'section=baladins,louveteaux&ical=1' ) && false !== strpos( scoutsunite_imprimer_url( array( 'louveteaux', 'baladins' ) ), 'section=baladins,louveteaux' ), 'famille : les liens du calendrier et du frigo gardent les deux sections' );

list( , , $programme ) = scoutsunite_imprimer_programme( array( 'baladins', 'louveteaux' ), 3 );
$ok( false !== strpos( $programme, 'FAMILLE-BALADINS' ) && false !== strpos( $programme, 'FAMILLE-UNITE' ) && false === strpos( $programme, 'FAMILLE-ECLAIREURS' ) && false !== strpos( $programme, 'feuille__tag">Louveteaux' ), 'famille : une feuille commune, chaque réunion avec sa section' );

$reglages = get_option( 'scoutsunite_unite', array() );
update_option( 'scoutsunite_unite', array( 'local_rue' => 'Rue du Local 1', 'whatsapp_unite' => 'https://chat.whatsapp.com/AbCd', 'baladins_infos' => 'Goûter : à emporter', 'louveteaux_infos' => 'Uniforme : foulard' ) + (array) $reglages );
$bas = scoutsunite_imprimer_bas( array( 'baladins', 'louveteaux' ) );
update_option( 'scoutsunite_unite', $reglages );
$ok( 1 === substr_count( $bas, 'Rue du Local 1' ) && 1 === substr_count( $bas, '<p>Groupe WhatsApp de l’unité</p>' ) && false !== strpos( $bas, '<h3>Baladins</h3>' ) && false !== strpos( $bas, 'Le staff de la Meute' ), 'famille : Bon à savoir et staff de chaque section, l’unité une seule fois' );

/* Photos de camp : stockage, sections, publication et clés ------------------ */

wp_set_current_user( $admin );
$https_avant = $_SERVER['HTTPS'] ?? null;
$document_root_avant = $_SERVER['DOCUMENT_ROOT'] ?? null;
$_SERVER['HTTPS'] = 'on';
$_SERVER['DOCUMENT_ROOT'] = ABSPATH;
$prive = scoutsunite_photos_dossier();
$ok( ! is_wp_error( $prive ) && ! scoutsunite_photos_dans_dossier( $prive, realpath( ABSPATH ) ), 'albums : stockage hors du dossier public de WordPress' );
$racine_photo_avant = $_SERVER['DOCUMENT_ROOT'] ?? null;
unset( $_SERVER['DOCUMENT_ROOT'] );
$ok( is_wp_error( scoutsunite_photos_dossier() ), 'albums : racine web inconnue, stockage bloqué plutôt que supposé privé' );
$ok( ( function () {
	// WP-CLI ou cron système n'ont pas de DOCUMENT_ROOT : le nettoyage doit pouvoir trouver le dossier déjà validé.
	add_filter( 'wp_doing_cron', '__return_true' );
	$dossier_cron = scoutsunite_photos_dossier();
	remove_filter( 'wp_doing_cron', '__return_true' );
	return ! is_wp_error( $dossier_cron );
} )(), 'albums : le cron sans racine web retrouve le dossier privé, le nettoyage des fichiers peut tourner' );
$_SERVER['DOCUMENT_ROOT'] = ABSPATH;
$ok( scoutsunite_photos_dans_dossier( realpath( WP_CONTENT_DIR ), realpath( ABSPATH ) ), 'albums : un sous-dossier public est reconnu comme tel' );
if ( null === $racine_photo_avant ) { unset( $_SERVER['DOCUMENT_ROOT'] ); } else { $_SERVER['DOCUMENT_ROOT'] = $racine_photo_avant; }
$annee_photo = (int) wp_date( 'Y' );
$cree_b = scoutsunite_photos_executer( 'creer', array( 'section' => 'baladins', 'annee' => $annee_photo, 'titre' => 'Souvenirs <script>interdit</script>' ) );
if ( is_wp_error( $cree_b ) ) {
	throw new Exception( 'Albums : ' . $cree_b->get_error_message() );
}
$album_b = scoutsunite_photos_album( $cree_b['album'] );
$cree_l = scoutsunite_photos_executer( 'creer', array( 'section' => 'louveteaux', 'annee' => $annee_photo, 'titre' => 'Grand camp' ) );
$album_l = scoutsunite_photos_album( $cree_l['album'] );
$cree_ancien = scoutsunite_photos_executer( 'creer', array( 'section' => 'baladins', 'annee' => $annee_photo - 1, 'titre' => 'Camp précédent' ) );
$album_ancien = scoutsunite_photos_album( $cree_ancien['album'] );
$ok( $album_b && $album_b->adresse === scoutsunite_photos_album( $album_b->adresse )->adresse, 'albums : adresse stable propre à chaque album' );
$ok( is_wp_error( scoutsunite_photos_executer( 'creer', array( 'section' => 'baladins', 'annee' => $annee_photo ) ) ), 'albums : un nom est nécessaire pour distinguer les albums' );
$ok( false === strpos( $album_b->titre, '<script>' ) && 'ouvert' === $album_b->etat && 36 === strlen( scoutsunite_photos_cle_partage( $album_b ) ), 'albums : création prête à partager, avec un accès par clé uniquement' );
$cle_initiale = scoutsunite_photos_cle_partage( $album_b );
$qr_initial = scoutsunite_photos_qr( scoutsunite_photos_url( $album_b ) . '#cle=' . $cle_initiale );
$ok( $cle_initiale === scoutsunite_photos_cle_partage( scoutsunite_photos_album( $album_b->id ) ) && $qr_initial === scoutsunite_photos_qr( scoutsunite_photos_url( $album_b ) . '#cle=' . scoutsunite_photos_cle_partage( $album_b ) ), 'albums : même clé et même QR retrouvables après rechargement' );

wp_set_current_user( $anim );
$ok( scoutsunite_photos_peut_gerer( $album_b ) && ! scoutsunite_photos_peut_gerer( $album_l ), 'albums : un animateur gère sa section seulement' );
$ok( is_wp_error( scoutsunite_photos_executer( 'creer', array( 'section' => 'louveteaux', 'annee' => $annee_photo + 1 ) ) ), 'albums : création dans une autre section refusée côté serveur' );
$cle_anim = scoutsunite_photos_executer( 'cle', array( 'album' => $album_b->id, 'version' => $album_b->version ) );
$album_b = scoutsunite_photos_album( $album_b->id );
$ok( ! is_wp_error( $cle_anim ) && $cle_anim['cle'] === scoutsunite_photos_cle_partage( $album_b ), 'albums : un animateur renouvelle et retrouve le lien de sa section' );
$ok( '' === scoutsunite_photos_cle_partage( $album_l ) && is_wp_error( scoutsunite_photos_executer( 'cle', array( 'album' => $album_l->id, 'version' => $album_l->version ) ) ), 'albums : lien et renouvellement refusés pour une autre section' );
$ok( is_wp_error( scoutsunite_photos_executer( 'supprimer_album', array( 'album' => $album_b->id, 'confirmation' => 'SUPPRIMER' ) ) ), 'albums : suppression complète toujours réservée au staff d’unité' );
$ok( is_wp_error( scoutsunite_photos_executer( 'regler', array( 'album' => $album_l->id, 'titre' => 'pirate' ) ) ), 'albums : identifiant d’une autre section forgé, refusé' );
$sections_photo = get_user_meta( $anim, 'scoutsunite_sections', true );
update_user_meta( $anim, 'scoutsunite_sections', array() );
$ok( ! scoutsunite_photos_peut_gerer( $album_b ) && ! scoutsunite_photos_catalogue() && '' === scoutsunite_photos_cle_partage( $album_b ) && is_wp_error( scoutsunite_photos_executer( 'etat', array( 'album' => $album_b->id, 'etat' => 'ouvert' ) ) ), 'albums : retirer sa section coupe aussi le partage et la gestion d’accès' );
update_user_meta( $anim, 'scoutsunite_sections', $sections_photo );

// Un ancien lien aléatoire ne peut pas être reconstitué, mais doit rester valable jusqu'au renouvellement explicite.
$cle_ancienne = bin2hex( random_bytes( 18 ) );
$wpdb->update( scoutsunite_photos_table( 'albums' ), array( 'cle_hash' => scoutsunite_photos_empreinte_cle( $cle_ancienne ) ), array( 'id' => $album_ancien->id ) );
$album_ancien = scoutsunite_photos_album( $album_ancien->id );
$ok( '' === scoutsunite_photos_cle_partage( $album_ancien ) && ! is_wp_error( scoutsunite_photos_ouvrir( $album_ancien, $cle_ancienne ) ), 'albums : les anciens liens restent valables sans rotation automatique' );
$cle_migree = scoutsunite_photos_executer( 'cle', array( 'album' => $album_ancien->id, 'version' => $album_ancien->version ) );
$ok( ! is_wp_error( $cle_migree ) && $cle_migree['cle'] === scoutsunite_photos_cle_partage( scoutsunite_photos_album( $album_ancien->id ) ), 'albums : un seul renouvellement rend le QR d’un ancien album retrouvable' );

wp_set_current_user( $staff );
$cle_photo = scoutsunite_photos_executer( 'cle', array( 'album' => $album_b->id, 'version' => $album_b->version ) );
$album_b = scoutsunite_photos_album( $album_b->id );
$ok( ! is_wp_error( $cle_photo ) && 36 === strlen( $cle_photo['cle'] ) && 64 === strlen( $album_b->cle_hash ) && $album_b->cle_hash !== $cle_photo['cle'], 'albums : clé forte, seule son empreinte est conservée' );
scoutsunite_photos_executer( 'etat', array( 'album' => $album_b->id, 'etat' => 'brouillon' ) );
$album_b = scoutsunite_photos_album( $album_b->id );
$ok( is_wp_error( scoutsunite_photos_ouvrir( $album_b, $cle_photo['cle'] ) ), 'albums : une clé ne rend pas un brouillon accessible' );
scoutsunite_photos_executer( 'etat', array( 'album' => $album_b->id, 'etat' => 'ouvert' ) );
$album_b = scoutsunite_photos_album( $album_b->id );
$ok( is_wp_error( scoutsunite_photos_ouvrir( $album_b, str_repeat( 'a', 36 ) ) ), 'albums : une mauvaise clé est refusée' );
$session_photo = scoutsunite_photos_ouvrir( $album_b, $cle_photo['cle'] );
$ok( ! is_wp_error( $session_photo ) && scoutsunite_photos_session_valide( $album_b, $session_photo['jeton'] ), 'albums : la bonne clé ouvre une session parent de sept jours au plus' );
$ok( ! scoutsunite_photos_session_valide( $album_l, $session_photo['jeton'] ) && ! scoutsunite_photos_session_valide( $album_ancien, $session_photo['jeton'] ), 'albums : une session n’ouvre ni une autre section ni l’année précédente' );
$ok( is_wp_error( scoutsunite_photos_ouvrir( $album_l, $cle_photo['cle'] ) ), 'albums : une clé ne peut pas être présentée à un autre album' );

// Une vraie image avec un marqueur EXIF : le réencodage doit aussi enlever cette donnée.
$source_photo = $prive . '/source-test.jpg';
$image_test = imagecreatetruecolor( 900, 600 );
imagefill( $image_test, 0, 0, imagecolorallocate( $image_test, 40, 90, 130 ) );
imagejpeg( $image_test, $source_photo, 90 );
imagedestroy( $image_test );
$jpeg = file_get_contents( $source_photo );
$exif = "Exif\0\0SCOUTSUNITE-GPS-PRIVE";
file_put_contents( $source_photo, substr( $jpeg, 0, 2 ) . "\xff\xe1" . pack( 'n', strlen( $exif ) + 2 ) . $exif . substr( $jpeg, 2 ) );
$importer_photo = function ( $album, $source ) {
	$verrou = scoutsunite_photos_verrou();
	if ( is_wp_error( $verrou ) ) {
		return $verrou;
	}
	try {
		return scoutsunite_photos_importer( $album, $source, 'Le grand jeu' );
	} finally {
		scoutsunite_photos_deverrouiller( $verrou );
	}
};
wp_set_current_user( $anim );
$recu_photo = $importer_photo( $album_b, $source_photo );
if ( is_wp_error( $recu_photo ) ) {
	throw new Exception( 'Import de photo : ' . $recu_photo->get_error_message() );
}
$image_photo = scoutsunite_photos_image( $recu_photo['photo'] );
$ok( 'publiee' === $image_photo->statut && scoutsunite_photos_chemin( $image_photo ) && scoutsunite_photos_chemin( $image_photo, true ), 'albums : vraie photo publiée directement, avec ses deux variantes privées' );
// Rejouer une mise à jour depuis le schéma 3 avec une photo, une clé et une session existantes.
$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i DROP INDEX section_annee', scoutsunite_photos_table( 'albums' ) ) );
$wpdb->query( $wpdb->prepare( 'ALTER TABLE %i ADD UNIQUE KEY section_annee (section,annee)', scoutsunite_photos_table( 'albums' ) ) );
update_option( 'scoutsunite_photos_schema', 3 );
unregister_block_type( 'scoutsunite/photos' );
unregister_block_type( 'scoutsunite/photos-presentation' );
scoutsunite_photos_initialiser();
scoutsunite_agenda_flush(); // Comme en fin d'init, après la migration rejouée dans ce test.
$album_migre = scoutsunite_photos_album( $album_b->id );
$ok( SCOUTSUNITE_PHOTOS_SCHEMA === (int) get_option( 'scoutsunite_photos_schema' ) && $album_b->adresse === $album_migre->adresse && $album_b->cle_hash === $album_migre->cle_hash && scoutsunite_photos_chemin( $image_photo ) && scoutsunite_photos_session_valide( $album_migre, $session_photo['jeton'] ), 'albums : migration sans changer les photos, liens, clés et sessions existantes' );
$second_b = scoutsunite_photos_executer( 'creer', array( 'section' => 'baladins', 'annee' => $annee_photo, 'titre' => 'Week-end de printemps' ) );
$album_second = is_wp_error( $second_b ) ? null : scoutsunite_photos_album( $second_b['album'] );
$ok( $album_second && $album_second->id !== $album_b->id && $album_second->adresse !== $album_b->adresse && scoutsunite_photos_cle_partage( $album_second ) !== scoutsunite_photos_cle_partage( $album_b ), 'albums : un animateur crée deux albums la même année, avec des clés distinctes après migration' );
$ok( $album_second && ! scoutsunite_photos_session_valide( $album_second, $session_photo['jeton'] ), 'albums : la session d’un album ne donne pas accès au second de la même section et année' );
wp_set_current_user( $admin );
$troisieme_b = scoutsunite_photos_executer( 'creer', array( 'section' => 'baladins', 'annee' => $annee_photo, 'titre' => 'Journée de rentrée' ) );
$ok( ! is_wp_error( $troisieme_b ) && $troisieme_b['album'] !== $album_b->id, 'albums : un administrateur crée aussi plusieurs albums pour la même section et année' );
if ( ! is_wp_error( $troisieme_b ) ) { scoutsunite_photos_executer( 'supprimer_album', array( 'album' => $troisieme_b['album'], 'confirmation' => 'SUPPRIMER' ) ); }
$_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] = $session_photo['jeton'];
wp_set_current_user( 0 );
$ok( scoutsunite_photos_peut_lire( $image_photo, $album_b ), 'albums : le parent voit la photo dès la fin de l’envoi, sans deuxième publication' );
$ok( '' === scoutsunite_photos_cle_partage( $album_b ), 'albums : la session parent ne permet pas de récupérer la clé' );
ob_start();
scoutsunite_photos_page( $album_b, null, true );
$page_parent = ob_get_clean();
$ok( false !== strpos( $page_parent, 'id="photos-acces" hidden' ) && false === strpos( $page_parent, $cle_photo['cle'] ), 'albums : une nouvelle clé peut être soumise depuis la galerie sans conserver la précédente dans la page' );
$ok( false === strpos( $page_parent, 'Demander le retrait' ) && false === strpos( $page_parent, 'Pour le retrait' ) && false === strpos( $page_parent, 'photo_supprimer' ) && strpos( $page_parent, 'class="photos__quitter"' ) > strpos( $page_parent, 'id="photos-visionneuse"' ), 'albums : parent sans demande de retrait ni poubelle, bouton Fermer après la galerie' );
ob_start();
scoutsunite_photos_gestion_formulaire( $album_b );
$ok( '' === ob_get_clean(), 'albums : ni panneau d’accès ni QR de partage pour les parents' );
unset( $_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] );
wp_set_current_user( $anim );
ob_start();
scoutsunite_photos_gestion_formulaire( scoutsunite_photos_album( $album_b->id ) );
$partage_anim = ob_get_clean();
$ok( false !== strpos( $partage_anim, '#cle=' . $cle_photo['cle'] ) && false !== strpos( $partage_anim, 'photos__qr' ) && false !== strpos( $partage_anim, 'Enregistrer l’accès' ), 'albums : partage, QR et commandes d’accès visibles à l’animateur' );
ob_start();
scoutsunite_photos_galerie( $album_b, true );
$galerie_anim = ob_get_clean();
$ok( false !== strpos( $galerie_anim, 'name="photo_supprimer"' ) && false !== strpos( $galerie_anim, 'Supprimer la sélection' ), 'albums : poubelle individuelle et suppression multiple disponibles pour l’animateur' );

// Compatibilité : les images restées en attente avec l'ancienne version restent privées.
$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'statut' => 'attente' ), array( 'id' => $image_photo->id ) );
$ok( array( 'petit-dejeuner', 'diner' ) === array_keys( scoutsunite_photos_tags_lire( 'Petit déjeuner, Dîner; diner' ) ), 'albums : tags normalisés, accents et doublons pris en compte' );
$anim_photo = wp_create_user( 'anim-photo', 'photo-pass-123', 'anim-photo@example.org' );
( new WP_User( $anim_photo ) )->set_role( 'scoutsunite_animateur' );
update_user_meta( $anim_photo, 'scoutsunite_sections', array( 'baladins' ) );
wp_set_current_user( $anim_photo );
$classement_photo = scoutsunite_photos_executer( 'taguer', array( 'album' => $album_b->id, 'photos' => array( $image_photo->id ), 'tags' => 'Petit déjeuner, Dîner' ) );
$ok( ! is_wp_error( $classement_photo ) && 2 === count( scoutsunite_photos_tags_image( scoutsunite_photos_image( $image_photo->id ) ) ), 'albums : plusieurs animateurs de la section classent les photos du même album' );
$ok( ! scoutsunite_photos_tags_album( $album_b ) && 2 === count( scoutsunite_photos_tags_album( $album_b, true ) ), 'albums : les tags des photos à vérifier ne sont pas révélés aux parents' );
$tags_avant_numeriques = scoutsunite_photos_image( $image_photo->id )->tags;
scoutsunite_photos_executer( 'taguer', array( 'album' => $album_b->id, 'photos' => array( $image_photo->id ), 'tags' => '2024, 0' ) );
$tags_numeriques = scoutsunite_photos_tags_image( scoutsunite_photos_image( $image_photo->id ) );
$ok( isset( $tags_numeriques['2024'], $tags_numeriques['0'], $tags_numeriques['diner'] ) && isset( scoutsunite_photos_tags_album( $album_b, true )['2024'] ), 'albums : un tag numérique (« 2024 », « 0 ») garde sa clé et ne perd pas les autres tags' );
$ok( false !== strpos( scoutsunite_photos_image( $image_photo->id )->tags, '"2024":' ) && '{' === substr( scoutsunite_photos_tags_json( array( 0 => '0' ) ), 0, 1 ), 'albums : les tags sont toujours enregistrés comme un objet JSON, retrouvable par le filtre' );
$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'tags' => $tags_avant_numeriques ), array( 'id' => $image_photo->id ) );
wp_set_current_user( $anim );
$ok( false === strpos( file_get_contents( scoutsunite_photos_chemin( $image_photo ) ), 'SCOUTSUNITE-GPS-PRIVE' ) && false === strpos( file_get_contents( scoutsunite_photos_chemin( $image_photo, true ) ), 'SCOUTSUNITE-GPS-PRIVE' ), 'albums : métadonnées privées absentes de la photo et de la miniature' );
$doublon_photo = $importer_photo( $album_b, $source_photo );
$ok( ! empty( $doublon_photo['doublon'] ) && $doublon_photo['photo'] === (int) $image_photo->id, 'albums : nouvel essai d’un envoi reçu sans doublon' );
$ok( 'publiee' === scoutsunite_photos_image( $image_photo->id )->statut, 'albums : renvoyer explicitement une ancienne photo en attente la publie en une fois' );
$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'statut' => 'attente' ), array( 'id' => $image_photo->id ) );
$image_photo = scoutsunite_photos_image( $image_photo->id );
$ok( is_wp_error( $importer_photo( $album_l, $source_photo ) ), 'albums : import dans une autre section refusé' );
$ok( is_wp_error( scoutsunite_photos_executer( 'envoyer', array( 'album' => $album_b->id ), array( 'tmp_name' => $source_photo, 'error' => 0 ) ) ), 'albums : un chemin local ne remplace pas un téléversement PHP authentique' );
$_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] = $session_photo['jeton'];
wp_set_current_user( 0 );
$ok( ! scoutsunite_photos_peut_lire( $image_photo, $album_b ), 'albums : une session parent ne lit pas une photo à vérifier' );
$ok( is_wp_error( scoutsunite_photos_executer( 'publier', array( 'album' => $album_b->id, 'photos' => array( $image_photo->id ) ) ) ), 'albums : une clé parent ne donne aucun droit de publication' );
wp_set_current_user( $anim );
scoutsunite_photos_executer( 'publier', array( 'album' => $album_b->id, 'photos' => array( $image_photo->id ) ) );
$image_photo = scoutsunite_photos_image( $image_photo->id );
wp_set_current_user( 0 );
$ok( scoutsunite_photos_peut_lire( $image_photo, $album_b ) && 1 === count( scoutsunite_photos_catalogue() ), 'albums : photo publiée lisible, catalogue limité à l’album autorisé' );
$ok( 2 === count( scoutsunite_photos_tags_album( $album_b ) ) && is_wp_error( scoutsunite_photos_exporter( $album_b ) ), 'albums : tags publiés consultables, export complet refusé à une session parent' );
$_GET['tag'] = 'diner';
ob_start();
scoutsunite_photos_galerie( $album_b, false );
$galerie_tag = ob_get_clean();
$_GET['tag'] = 'inexistant';
ob_start();
scoutsunite_photos_galerie( $album_b, false );
$galerie_sans_tag = ob_get_clean();
unset( $_GET['tag'] );
$ok( false !== strpos( $galerie_tag, 'class="photos__ouvrir"' ) && false === strpos( $galerie_sans_tag, 'class="photos__ouvrir"' ), 'albums : filtre de tags appliqué côté serveur à la galerie' );
$faux_chemin = clone $image_photo;
$faux_chemin->nom = '../../wp-config';
$ok( '' === scoutsunite_photos_chemin( $faux_chemin ), 'albums : nom de fichier forgé sans traversée de dossier' );
unset( $_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] );
$ok( ! scoutsunite_photos_peut_lire( $image_photo, $album_b ) && ! scoutsunite_photos_catalogue(), 'albums : sans cookie, ni photo publiée ni catalogue privé' );

wp_set_current_user( $staff );
$export_photo = scoutsunite_photos_exporter( $album_b, 'diner' );
if ( class_exists( 'ZipArchive' ) ) {
	$zip_photo = new ZipArchive();
	$zip_ouvert = ! is_wp_error( $export_photo ) && true === $zip_photo->open( $export_photo );
	$index_photo = $zip_ouvert ? json_decode( $zip_photo->getFromName( 'photos.json' ), true ) : array();
	$ok( $zip_ouvert && 2 === $zip_photo->numFiles && 1 === count( $index_photo['photos'] ) && in_array( 'Dîner', $index_photo['photos'][0]['tags'], true ), 'albums : export ZIP privé avec les photos publiées du tag et leur index' );
	if ( $zip_ouvert ) { $zip_photo->close(); }
	if ( ! is_wp_error( $export_photo ) ) { wp_delete_file( $export_photo ); }
} else {
	$ok( is_wp_error( $export_photo ) && 'zip' === $export_photo->get_error_code(), 'albums : absence de PHP ZIP expliquée sans export incomplet' );
}
/* Sélection des familles et demandes privées ------------------------------- */

$_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] = $session_photo['jeton'];
wp_set_current_user( 0 );
$selection_parent = array( (int) $image_photo->id );
$selection_lue = scoutsunite_photos_selection_images( $album_b, $selection_parent );
$ok( ! is_wp_error( $selection_lue ) && 1 === count( $selection_lue ), 'sélection : le parent retrouve les photos publiées de son album' );
$ok( is_wp_error( scoutsunite_photos_selection_images( $album_l, $selection_parent ) ) && is_wp_error( scoutsunite_photos_selection_images( $album_b, array( $image_photo->id, 99999999 ) ) ), 'sélection : autre album et références inconnues refusés sans export partiel' );
$ok( is_wp_error( scoutsunite_photos_selection_ids( '[1, -2]' ) ) && is_wp_error( scoutsunite_photos_selection_ids( array_fill( 0, 1001, 1 ) ) ), 'sélection : références invalides et liste trop longue refusées' );
$nonce_selection = wp_create_nonce( scoutsunite_photos_nonce_action( 'selection', $album_b ) );
$autre_session = scoutsunite_photos_ouvrir( $album_b, $cle_photo['cle'] );
$_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] = $autre_session['jeton'];
$ok( ! wp_verify_nonce( $nonce_selection, scoutsunite_photos_nonce_action( 'selection', $album_b ) ), 'sélection : le nonce d’une famille n’est pas réutilisable par une autre session' );
$_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] = $session_photo['jeton'];
$export_parent = scoutsunite_photos_exporter( $album_b, '', $selection_parent );
if ( class_exists( 'ZipArchive' ) ) {
	$zip_parent = new ZipArchive();
	$ouvert_parent = ! is_wp_error( $export_parent ) && true === $zip_parent->open( $export_parent );
	$index_parent = $ouvert_parent ? json_decode( $zip_parent->getFromName( 'photos.json' ), true ) : array();
	$ok( $ouvert_parent && 2 === $zip_parent->numFiles && (int) $image_photo->id === $index_parent['photos'][0]['reference'], 'sélection : ZIP parent limité à ses références explicites avec leur index' );
	if ( $ouvert_parent ) { $zip_parent->close(); }
	if ( ! is_wp_error( $export_parent ) ) { wp_delete_file( $export_parent ); }
} else {
	$ok( is_wp_error( $export_parent ), 'sélection : absence de PHP ZIP expliquée au parent' );
}
$reference_demande = bin2hex( random_bytes( 16 ) );
$donnees_demande = array( 'selection' => wp_json_encode( $selection_parent ), 'nom' => '<b>Parent exemple</b>', 'email' => 'parent@example.org', 'message' => "Un souvenir du camp.\nMerci au staff !", 'reference' => $reference_demande );
$demande_parent = scoutsunite_photos_demander( $album_b, $donnees_demande );
$demande_doublon = scoutsunite_photos_demander( $album_b, $donnees_demande );
$demandes_album = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE album = %d', scoutsunite_photos_table( 'demandes' ), $album_b->id ) );
$id_demande = $demandes_album ? (int) $demandes_album[0]->id : 0;
$ok( ! is_wp_error( $demande_parent ) && ! is_wp_error( $demande_doublon ) && 1 === count( $demandes_album ) && 'Parent exemple' === $demandes_album[0]->nom, 'demandes : enregistrement privé assaini et nouvel essai sans doublon' );
$ok( false === strpos( wp_json_encode( $demande_parent ), 'parent@example.org' ) && is_wp_error( scoutsunite_photos_demande_gerer( $album_b, array( 'demande' => $id_demande ) ) ), 'demandes : accusé sans coordonnées, aucune gestion accordée au parent' );
ob_start(); scoutsunite_photos_demandes_interface( $album_b );
$ok( '' === ob_get_clean(), 'demandes : les parents ne voient pas la liste ni les coordonnées des familles' );
$invalide_demande = $donnees_demande; $invalide_demande['email'] = 'adresse-invalide'; $invalide_demande['reference'] = bin2hex( random_bytes( 16 ) );
$ok( is_wp_error( scoutsunite_photos_demander( $album_b, $invalide_demande ) ) && is_wp_error( scoutsunite_photos_demander( $album_l, $donnees_demande ) ), 'demandes : coordonnées invalides et album non autorisé refusés' );
$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'statut' => 'retiree' ), array( 'id' => $image_photo->id ) );
$ok( is_wp_error( scoutsunite_photos_selection_images( $album_b, $selection_parent ) ) && ! scoutsunite_photos_selection_images( $album_b, $selection_parent, false ), 'sélection : photo retirée refusée à l’export et écartée de l’aperçu' );
$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'statut' => 'publiee' ), array( 'id' => $image_photo->id ) );
unset( $_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] );
$ok( is_wp_error( scoutsunite_photos_selection_images( $album_b, $selection_parent ) ) && is_wp_error( scoutsunite_photos_demander( $album_b, $donnees_demande ) ), 'sélection : sans session, ni aperçu ni demande privée' );
wp_set_current_user( $anim );
ob_start(); scoutsunite_photos_demandes_interface( $album_b ); $demandes_gestion = ob_get_clean();
$ok( false !== strpos( $demandes_gestion, 'parent@example.org' ) && ! is_wp_error( scoutsunite_photos_demande_gerer( $album_b, array( 'demande' => $id_demande ) ) ), 'demandes : l’animateur de la section consulte et traite une demande' );
$ok( is_wp_error( scoutsunite_photos_demande_gerer( $album_l, array( 'demande' => $id_demande ) ) ), 'demandes : un identifiant forgé ne change pas d’album' );
$_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] = $session_photo['jeton']; wp_set_current_user( 0 );
$donnees_attente = $donnees_demande; $donnees_attente['reference'] = bin2hex( random_bytes( 16 ) );
scoutsunite_photos_demander( $album_b, $donnees_attente );
$wpdb->update( scoutsunite_photos_table( 'demandes' ), array( 'traitee_le' => time() - 91 * DAY_IN_SECONDS ), array( 'id' => $id_demande ) );
scoutsunite_photos_demandes_nettoyer();
$reste_demande = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE album = %d', scoutsunite_photos_table( 'demandes' ), $album_b->id ) );
$ok( $reste_demande && 'nouvelle' === $reste_demande->etat && (int) $reste_demande->id !== $id_demande, 'demandes : nettoyage des demandes traitées depuis 90 jours, sans effacer celles à préparer' );
wp_set_current_user( $anim );
$efface_demande = scoutsunite_photos_demande_gerer( $album_b, array( 'demande' => $reste_demande->id ), true );
$ok( ! is_wp_error( $efface_demande ) && scoutsunite_photos_chemin( $image_photo ), 'demandes : effacer les coordonnées et la demande conserve les photos de l’album' );
wp_set_current_user( 0 ); $donnees_attente['reference'] = bin2hex( random_bytes( 16 ) ); scoutsunite_photos_demander( $album_b, $donnees_attente );
unset( $_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] ); wp_set_current_user( $staff );

// Plus d'une page : les voisines doivent ignorer les photos privées et respecter le tag.
$photos_parcours = array();
for ( $i = 0; $i < 27; $i++ ) {
	$ligne = get_object_vars( $image_photo );
	unset( $ligne['id'] );
	$ligne['nom'] = bin2hex( random_bytes( 16 ) );
	$ligne['empreinte'] = hash( 'sha256', 'photo-parcours-' . $i );
	$ligne['tags'] = '';
	$ligne['statut'] = $i === 25 ? 'attente' : ( $i === 26 ? 'retiree' : 'publiee' );
	$wpdb->insert( scoutsunite_photos_table( 'images' ), $ligne );
	$photos_parcours[] = (int) $wpdb->insert_id;
}
$_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] = $session_photo['jeton'];
wp_set_current_user( 0 );
$vue_fin = scoutsunite_photos_visionner( $album_b, $image_photo->id );
$vue_debut = scoutsunite_photos_visionner( $album_b, $photos_parcours[24] );
$vue_tag = scoutsunite_photos_visionner( $album_b, $image_photo->id, 'diner' );
$ok( ! is_wp_error( $vue_fin ) && 26 === $vue_fin['total'] && 26 === $vue_fin['position'] && ! $vue_fin['suivante'] && $vue_fin['precedente'] && 1 === $vue_debut['position'] && ! $vue_debut['precedente'], 'albums : navigation dans tout l’album au-delà de 24 photos, bornes et compteur corrects' );
$ok( 1 === $vue_tag['total'] && ! $vue_tag['precedente'] && ! $vue_tag['suivante'] && is_wp_error( scoutsunite_photos_visionner( $album_b, $photos_parcours[0], 'diner' ) ), 'albums : la visionneuse reste dans le tag choisi' );
$ok( is_wp_error( scoutsunite_photos_visionner( $album_b, $photos_parcours[25] ) ) && is_wp_error( scoutsunite_photos_visionner( $album_b, $photos_parcours[26] ) ) && is_wp_error( scoutsunite_photos_visionner( $album_l, $image_photo->id ) ), 'albums : visionneuse inaccessible aux photos en attente, retirées ou d’un autre album' );
unset( $_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] );
$ok( is_wp_error( scoutsunite_photos_visionner( $album_b, $image_photo->id ) ), 'albums : aucune métadonnée de navigation sans session parent' );
wp_set_current_user( $anim );
$supprimee = scoutsunite_photos_executer( 'supprimer_photo', array( 'album' => $album_b->id, 'photos' => array( $photos_parcours[0] ) ) );
$ok( ! is_wp_error( $supprimee ) && ! scoutsunite_photos_image( $photos_parcours[0] ) && scoutsunite_photos_image( $photos_parcours[1] ), 'albums : un animateur efface une photo de sa section sans effacer la voisine' );
wp_set_current_user( $staff );
$ancienne_version = $album_b->version;
$nouvelle_cle = scoutsunite_photos_executer( 'cle', array( 'album' => $album_b->id, 'version' => $ancienne_version ) );
$album_b = scoutsunite_photos_album( $album_b->id );
$ok( ! scoutsunite_photos_session_valide( $album_b, $session_photo['jeton'] ) && is_wp_error( scoutsunite_photos_ouvrir( $album_b, $cle_photo['cle'] ) ), 'albums : renouveler la clé coupe l’ancienne clé et les sessions ouvertes' );
$_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] = $session_photo['jeton'];
wp_set_current_user( 0 );
$ok( is_wp_error( scoutsunite_photos_visionner( $album_b, $image_photo->id ) ), 'albums : le renouvellement coupe aussi les appels de la visionneuse' );
$ok( is_wp_error( scoutsunite_photos_demander( $album_b, $donnees_demande ) ) && is_wp_error( scoutsunite_photos_exporter( $album_b, '', $selection_parent ) ), 'sélection : le renouvellement coupe aussi les demandes et exports de l’ancien accès' );
wp_set_current_user( $staff );
$ok( is_wp_error( scoutsunite_photos_executer( 'cle', array( 'album' => $album_b->id, 'version' => $ancienne_version ) ) ), 'albums : recharger une ancienne demande ne renouvelle pas la clé une seconde fois' );
$session_photo = scoutsunite_photos_ouvrir( $album_b, $nouvelle_cle['cle'] );
$_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] = $session_photo['jeton'];
scoutsunite_photos_executer( 'retirer', array( 'album' => $album_b->id, 'photos' => array( $image_photo->id ) ) );
wp_set_current_user( 0 );
$ok( ! scoutsunite_photos_peut_lire( scoutsunite_photos_image( $image_photo->id ), $album_b ), 'albums : retirer une photo coupe aussi sa miniature et son téléchargement' );
wp_set_current_user( $staff );
$retiree_renvoyee = $importer_photo( $album_b, $source_photo );
$ok( is_wp_error( $retiree_renvoyee ) && 'retiree' === $retiree_renvoyee->get_error_code() && 'retiree' === scoutsunite_photos_image( $image_photo->id )->statut, 'albums : renvoyer une photo retirée ne la remet pas en ligne automatiquement' );
scoutsunite_photos_executer( 'etat', array( 'album' => $album_b->id, 'etat' => 'archive' ) );
$album_b = scoutsunite_photos_album( $album_b->id );
wp_set_current_user( $anim );
$ok( is_wp_error( scoutsunite_photos_executer( 'publier', array( 'album' => $album_b->id, 'photos' => array( $image_photo->id ) ) ) ), 'albums : archives en lecture seule pour les animateurs' );
$ok( ! is_wp_error( scoutsunite_photos_executer( 'regler', array( 'album' => $album_b->id, 'titre' => 'Souvenirs du camp', 'expiration' => '' ) ) ), 'albums : un animateur règle le titre et l’échéance de son album' );
scoutsunite_photos_executer( 'etat', array( 'album' => $album_b->id, 'etat' => 'ferme' ) );
$album_ferme = scoutsunite_photos_album( $album_b->id );
$ok( ! scoutsunite_photos_session_valide( $album_ferme, $session_photo['jeton'] ) && is_wp_error( scoutsunite_photos_ouvrir( $album_ferme, $nouvelle_cle['cle'] ) ) && $nouvelle_cle['cle'] === scoutsunite_photos_cle_partage( $album_ferme ), 'albums : l’animateur ferme l’accès, coupe les sessions et garde son QR disponible' );
scoutsunite_photos_executer( 'etat', array( 'album' => $album_b->id, 'etat' => 'ouvert' ) );
$album_b = scoutsunite_photos_album( $album_b->id );
$ok( ! scoutsunite_photos_session_valide( $album_b, $session_photo['jeton'] ) && ! is_wp_error( scoutsunite_photos_ouvrir( $album_b, $nouvelle_cle['cle'] ) ) && $nouvelle_cle['cle'] === scoutsunite_photos_cle_partage( $album_b ), 'albums : rouvrir garde le même lien, mais les anciennes sessions restent invalides' );
wp_set_current_user( $staff );
$cle_expiration = scoutsunite_photos_executer( 'cle', array( 'album' => $album_b->id, 'version' => $album_b->version ) );
scoutsunite_photos_executer( 'regler', array( 'album' => $album_b->id, 'expiration' => wp_date( 'Y-m-d', time() - 2 * DAY_IN_SECONDS ) ) );
$album_b = scoutsunite_photos_album( $album_b->id );
$ok( is_wp_error( scoutsunite_photos_ouvrir( $album_b, $cle_expiration['cle'] ) ), 'albums : date d’expiration contrôlée côté serveur' );
$cle_l = scoutsunite_photos_executer( 'cle', array( 'album' => $album_l->id, 'version' => $album_l->version ) );
scoutsunite_photos_executer( 'etat', array( 'album' => $album_l->id, 'etat' => 'ouvert' ) );
$ok( is_wp_error( scoutsunite_photos_ouvrir( scoutsunite_photos_album( $album_l->id ), $cle_expiration['cle'] ) ), 'albums : les clés sont distinctes entre sections' );
$ok( false === strpos( do_blocks( '<!-- wp:scoutsunite/photos /-->' ), $album_b->adresse ) && false === strpos( do_blocks( '<!-- wp:scoutsunite/photos /-->' ), '<img' ), 'albums : le bloc public n’expose ni album ni miniature' );

// Un quota atteint doit refuser un nouvel envoi et effacer les variantes préparées.
scoutsunite_photos_executer( 'regler', array( 'album' => $album_b->id, 'expiration' => '' ) );
$album_b = scoutsunite_photos_album( $album_b->id );
$poids_reel = $image_photo->taille;
$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'taille' => scoutsunite_photos_quota() ), array( 'id' => $image_photo->id ) );
file_put_contents( $source_photo, $jpeg . 'autre-fichier' );
$avant_fichiers = glob( $prive . '/' . $album_b->id . '/*.jpg' );
$quota_photo = $importer_photo( $album_b, $source_photo );
$ok( is_wp_error( $quota_photo ) && 'quota' === $quota_photo->get_error_code() && $avant_fichiers === glob( $prive . '/' . $album_b->id . '/*.jpg' ), 'albums : quota vérifié, pas de fichiers abandonnés après refus' );
$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'taille' => $poids_reel ), array( 'id' => $image_photo->id ) );
$verrou_photo = scoutsunite_photos_verrou();
$ok( ! is_wp_error( $verrou_photo ) && is_wp_error( scoutsunite_photos_verrou() ), 'albums : les mutations concurrentes sont protégées par un verrou' );
$ok( ! is_wp_error( scoutsunite_photos_ouvrir( $album_b, $cle_expiration['cle'] ) ), 'albums : un parent ouvre son lien même pendant un envoi d’animateur (verrou tenu)' );
scoutsunite_photos_deverrouiller( 'un-autre-jeton' );
$ok( is_wp_error( scoutsunite_photos_verrou() ), 'albums : un autre processus ne peut pas libérer le verrou détenu' );
if ( ! is_wp_error( $verrou_photo ) ) { scoutsunite_photos_deverrouiller( $verrou_photo ); }
$chemin_efface = scoutsunite_photos_chemin( $image_photo );
$ok( is_wp_error( scoutsunite_photos_executer( 'supprimer_album', array( 'album' => $album_b->id ) ) ), 'albums : suppression complète avec confirmation obligatoire' );
scoutsunite_photos_executer( 'supprimer_album', array( 'album' => $album_b->id, 'confirmation' => 'SUPPRIMER' ) );
$ok( ! scoutsunite_photos_album( $album_b->id ) && ! is_file( $chemin_efface ) && ! scoutsunite_photos_image( $image_photo->id ), 'albums : suppression complète des données et fichiers privés' );
$ok( ! $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE album = %d', scoutsunite_photos_table( 'demandes' ), $album_b->id ) ), 'demandes : supprimer l’album efface aussi les demandes et coordonnées associées' );
foreach ( array_filter( array( $album_l, $album_ancien, $album_second ) ) as $album_test ) {
	scoutsunite_photos_executer( 'supprimer_album', array( 'album' => $album_test->id, 'confirmation' => 'SUPPRIMER' ) );
}
wp_delete_file( $source_photo );
unset( $_COOKIE[ scoutsunite_photos_cookie( $album_b->id ) ] );
if ( null === $https_avant ) { unset( $_SERVER['HTTPS'] ); } else { $_SERVER['HTTPS'] = $https_avant; }
if ( null === $document_root_avant ) { unset( $_SERVER['DOCUMENT_ROOT'] ); } else { $_SERVER['DOCUMENT_ROOT'] = $document_root_avant; }
$origine_avant = $_SERVER['HTTP_ORIGIN'] ?? null;
$contexte_avant = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? null;
$_SERVER['HTTP_ORIGIN'] = 'null';
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
$ok( scoutsunite_photos_origine_valide(), 'albums : formulaire natif sans référent accepté dans le contexte du site' );
$_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
$ok( ! scoutsunite_photos_origine_valide(), 'albums : origine opaque depuis un autre site refusée' );
$_SERVER['HTTP_ORIGIN'] = 'https://site-inconnu.example';
$ok( ! scoutsunite_photos_origine_valide(), 'albums : origine explicite d’un autre site refusée' );
if ( null === $origine_avant ) { unset( $_SERVER['HTTP_ORIGIN'] ); } else { $_SERVER['HTTP_ORIGIN'] = $origine_avant; }
if ( null === $contexte_avant ) { unset( $_SERVER['HTTP_SEC_FETCH_SITE'] ); } else { $_SERVER['HTTP_SEC_FETCH_SITE'] = $contexte_avant; }
wp_set_current_user( $admin );

/* Thème et extension ------------------------------------------------------- */

$page_photos = get_post( (int) get_option( 'scoutsunite_photos_presentation_page' ) );
$presentation_photos = scoutsunite_photos_presentation();
$ok( $page_photos && 'publish' === $page_photos->post_status && false !== strpos( $presentation_photos, 'section-louveteaux' ) && false === strpos( $presentation_photos, '#cle=' ) && false === strpos( $presentation_photos, 'parent@example.org' ), 'présentation : page publique publiée avec les sections, sans clé ni coordonnées privées' );
$ancienne_presentation = $page_photos->post_content;
wp_update_post( array( 'ID' => $page_photos->ID, 'post_content' => 'Texte conservé par le staff.' ) );
delete_option( 'scoutsunite_photos_presentation_page' );
scoutsunite_photos_presentation_installer();
$ok( (int) $page_photos->ID === (int) get_option( 'scoutsunite_photos_presentation_page' ) && 'Texte conservé par le staff.' === get_post( $page_photos->ID )->post_content, 'présentation : une page existante est conservée, sans écrasement ni doublon' );
wp_update_post( array( 'ID' => $page_photos->ID, 'post_content' => $ancienne_presentation ) );

$ok( wp_get_theme()->get( 'Version' ) === $extension, 'thème et extension à la même version' );
wp_set_current_user( $admin );
ob_start();
scoutsunite_avis_extension();
$ok( '' === ob_get_clean(), 'thème : pas d’avis « extension manquante » quand elle est active' );
$ok( ! get_option( 'scoutsunite_agenda_flush' ) && isset( get_option( 'rewrite_rules' )['agenda/?$'] ), 'extension : l’adresse /agenda/ existe dès l’activation' );
$ok( get_permalink( get_page_by_path( 'iama' ) ) === scoutsunite_agenda_section_url( 'iama' ) && get_permalink( $baladins ) === scoutsunite_agenda_section_url( 'baladins' ), 'extension : trouve les pages de section et la page IAmA sans le thème' );
$ok( '' !== scoutsunite_extension_theme_url( 'assets/css/imprimer.css' ) && '' !== scoutsunite_extension_theme_url( 'assets/images/' . scoutsunite_imprimer_section( 'louveteaux' )['logo'] ), 'frigo : le style et le logo viennent du thème' );
$ok( '' === scoutsunite_extension_theme_url( 'assets/css/absent.css' ), 'frigo : un thème sans feuille de style ne casse rien' );

$resultats[] = '';
$total       = count( $resultats ) - 3;
$resultats[] = $echecs ? $echecs . ' test(s) en échec sur ' . $total : 'Tous les tests passent (' . $total . ').';
file_put_contents( __DIR__ . '/resultats.txt', implode( "\n", $resultats ) . "\n" );
if ( $echecs ) {
	throw new Exception( $echecs . ' test(s) en échec : voir tests/resultats.txt' );
}
