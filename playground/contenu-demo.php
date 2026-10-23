<?php
/**
 * Contenu de démonstration pour WordPress Playground : pages, articles et
 * réunions d'exemple. Lancé par blueprint.json (local) et playground/demo.json (en ligne).
 * Ne fait pas partie du thème distribué (exclu du zip par .gitattributes).
 */

require '/wordpress/wp-load.php';

$reg = WP_Block_Patterns_Registry::get_instance();
$p = function ( $slug ) use ( $reg ) { $x = $reg->get_registered( 'les-scouts-unite/' . $slug ); return $x ? $x['content'] : ''; };
$page = function ( $title, $slug, $content, $parent = 0, $template = '' ) { $id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content, 'post_parent' => $parent ) ); if ( $template ) { update_post_meta( $id, '_wp_page_template', $template ); } return $id; };
$accueil = $page( 'Accueil', 'accueil', '' );
$actus = $page( 'Actualités', 'actualites', '' );
$sections = $page( 'Les sections', 'sections', $p( 'page-sections' ) );
foreach ( array( 'baladins' => 'Baladins', 'louveteaux' => 'Louveteaux', 'eclaireurs' => 'Éclaireurs', 'pionniers' => 'Pionniers' ) as $s => $n ) { $page( $n, $s, $p( 'page-section-' . $s ), $sections, 'page-pleine-largeur' ); }
$page( 'Inscription', 'inscription', $p( 'page-inscription' ) );
$page( 'Contact', 'contact', $p( 'page-contact' ) );
$page( 'IAmA – Anciens et amis', 'iama', $p( 'page-iama' ) );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $accueil );
update_option( 'page_for_posts', $actus );
wp_delete_post( 1, true );
wp_delete_post( 2, true );
$up = wp_upload_dir();
$src = get_theme_file_path( 'assets/images/photos/coucher-soleil.png' );
$dst = $up['path'] . '/camp-coucher-soleil.png';
copy( $src, $dst );
$att = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'Camp au coucher du soleil', 'post_status' => 'inherit' ), $dst );
require_once ABSPATH . 'wp-admin/includes/image.php';
wp_update_attachment_metadata( $att, wp_generate_attachment_metadata( $att, $dst ) );
update_post_meta( $att, '_wp_attachment_image_alt', 'Une tour de pionniers en bois au coucher du soleil' );
$posts = array(
 array( 'Camps d’été 2027 : les dates sont tombées', '2026-10-18 10:00:00', 'Chaque section a choisi ses dates de camp. Note-les déjà dans ton agenda : les détails pratiques suivront lors de la réunion de parents.', $att ),
 array( 'Week-end d’unité du 13 au 15 novembre', '2026-10-10 09:00:00', 'Toutes les sections partent ensemble pour un week-end de grands jeux. Départ du local le vendredi soir.', 0 ),
 array( 'Rentrée scoute : merci pour cette première journée', '2026-09-20 18:00:00', 'Belle journée de passage au local. Bienvenue aux nouveaux et bravo à ceux qui changent de section.', 0 ),
 array( 'Le local fait peau neuve', '2026-08-30 12:00:00', 'Pendant l’été, parents et animateurs ont repeint les locaux des Baladins et des Louveteaux. Merci à tous les bras.', 0 ),
);
foreach ( $posts as $x ) { $id = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $x[0], 'post_date' => $x[1], 'post_content' => '<!-- wp:paragraph --><p>' . $x[2] . '</p><!-- /wp:paragraph -->', 'post_excerpt' => $x[2] ) ); if ( $x[3] ) { set_post_thumbnail( $id, $x[3] ); } }
$tz = wp_timezone();
$sam = new DateTimeImmutable( 'next saturday', $tz );
$ven = new DateTimeImmutable( 'next friday', $tz );
$plan = array( array( 'baladins', $sam, '14:00', '17:00', 'au local' ), array( 'louveteaux', $sam, '14:00', '17:30', 'au local' ), array( 'eclaireurs', $sam, '14:00', '18:00', 'au local' ), array( 'pionniers', $ven, '19:00', '22:00', 'au local' ) );
foreach ( $plan as $x ) { for ( $i = 0; $i < 6; $i++ ) { wp_insert_post( array( 'post_type' => 'reunion', 'post_status' => 'publish', 'post_title' => 'Réunion', 'post_content' => 0 === $i ? '<!-- wp:paragraph --><p>Uniforme, foulard, bottines et un goûter.</p><!-- /wp:paragraph -->' : '', 'meta_input' => array( 'su_section' => $x[0], 'su_date' => $x[1]->modify( '+' . ( 7 * $i ) . ' days' )->format( 'Y-m-d' ), 'su_debut' => $x[2], 'su_fin' => $x[3], 'su_lieu' => $x[4] ) ) ); } }
wp_insert_post( array( 'post_type' => 'reunion', 'post_status' => 'publish', 'post_title' => 'Hike en Ardenne', 'meta_input' => array( 'su_section' => 'eclaireurs', 'su_date' => $sam->modify( '+14 days' )->format( 'Y-m-d' ), 'su_debut' => '09:00', 'su_fin' => '18:00', 'su_lieu' => 'rendez-vous à la gare de Verviers' ) ) );
wp_insert_post( array( 'post_type' => 'reunion', 'post_status' => 'publish', 'post_title' => 'Week-end d’unité', 'post_content' => '<!-- wp:paragraph --><p>Toutes les sections ensemble. Retour le dimanche à 16 h.</p><!-- /wp:paragraph -->', 'meta_input' => array( 'su_section' => 'unite', 'su_date' => '2026-11-13', 'su_debut' => '18:00', 'su_fin' => '', 'su_lieu' => 'départ du local' ) ) );
wp_insert_post( array( 'post_type' => 'reunion', 'post_status' => 'publish', 'post_title' => 'Souper des anciens', 'post_content' => '<!-- wp:paragraph --><p>Retrouvailles au local autour d’un spaghetti. Inscription auprès du responsable IAmA.</p><!-- /wp:paragraph -->', 'meta_input' => array( 'su_section' => 'iama', 'su_date' => '2026-12-05', 'su_debut' => '19:00', 'su_lieu' => 'au local' ) ) );
flush_rewrite_rules();
