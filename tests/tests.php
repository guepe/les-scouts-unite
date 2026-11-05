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

/* Thème et extension ------------------------------------------------------- */

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
