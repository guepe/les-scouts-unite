<?php
/**
 * Tests du thème, dans un WordPress neuf (WordPress Playground) : droits, répétition, annulation,
 * notes protégées, feuilles à imprimer, QR codes et liens. Lancés par tests/lancer.sh et avant chaque publication.
 *
 * Écrit les résultats dans tests/resultats.txt ; une exception arrête le Playground en erreur
 * si un test échoue.
 *
 * @package les-scouts-unite
 */

require '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

$resultats = array( 'PHP ' . PHP_VERSION . ' · WordPress ' . get_bloginfo( 'version' ) . ' · thème ' . wp_get_theme()->get( 'Version' ), '' );
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

$annulee = $reunion( 'Réunion', array( 'su_section' => 'pionniers', 'su_date' => $dans( 1 ), 'su_debut' => '19:00', 'su_annulee' => '1', 'su_annulee_pourquoi' => 'Examens' ) );
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

$resultats[] = '';
$total       = count( $resultats ) - 3;
$resultats[] = $echecs ? $echecs . ' test(s) en échec sur ' . $total : 'Tous les tests passent (' . $total . ').';
file_put_contents( __DIR__ . '/resultats.txt', implode( "\n", $resultats ) . "\n" );
if ( $echecs ) {
	throw new Exception( $echecs . ' test(s) en échec : voir tests/resultats.txt' );
}
