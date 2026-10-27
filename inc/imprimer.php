<?php
/**
 * Feuilles à imprimer, pour le frigo : le programme d'une section et la convocation
 * d'une réunion. Une page A4 sans en-tête ni pied du site ; le navigateur l'imprime
 * ou l'enregistre en PDF (« Enregistrer au format PDF » dans la fenêtre d'impression).
 *
 * - /agenda/?section=baladins&imprimer : les réunions des prochains mois (?mois=1 à 12) ;
 * - /agenda/?imprimer : tout l'agenda de l'unité ;
 * - /agenda/une-reunion/?imprimer : la convocation d'une réunion, avec ses notes.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Durées proposées pour le programme, en mois.
 *
 * @return array Nombre de mois => libellé.
 */
function scoutsunite_imprimer_durees() {
	return array(
		1  => 'le mois qui vient',
		3  => 'les 3 prochains mois',
		6  => 'les 6 prochains mois',
		12 => 'toute l’année',
	);
}

/**
 * Adresse de la feuille à imprimer.
 *
 * @param string           $section Section du programme, '' pour toute l'unité.
 * @param int|WP_Post|null $post    Réunion, pour sa convocation.
 * @return string URL.
 */
function scoutsunite_imprimer_url( $section = '', $post = null ) {
	if ( $post ) {
		return add_query_arg( 'imprimer', '1', get_permalink( $post ) );
	}
	$args = array( 'imprimer' => '1' );
	if ( $section ) {
		$args = array( 'section' => $section ) + $args;
	}
	return add_query_arg( $args, get_post_type_archive_link( 'reunion' ) );
}

/**
 * Nom, logo et titre du staff d'une section, pour l'en-tête et le pied de la feuille.
 *
 * @param string $section Identifiant de section, '' pour toute l'unité.
 * @return array
 */
function scoutsunite_imprimer_section( $section ) {
	$sections = scoutsunite_sections();
	$noms     = scoutsunite_agenda_sections();
	if ( isset( $sections[ $section ] ) ) {
		return array(
			'nom'   => $sections[ $section ]['nom'],
			'logo'  => 'logos/' . $sections[ $section ]['logo'],
			'staff' => 'Le staff ' . $sections[ $section ]['staff'],
		);
	}
	return array(
		'nom'   => $noms[ $section ] ?? 'Toute l’unité',
		'logo'  => 'logos/logo_Federation_couleur_horizontal.png',
		'staff' => 'iama' === $section ? 'Le clan IAmA' : 'Le staff d’unité',
	);
}

/**
 * Les personnes à contacter et leurs coordonnées. Un champ vide de Réglages → Unité
 * est simplement omis : pas d'exemple « à compléter » sur une feuille imprimée.
 *
 * @param string $section Identifiant de section, '' pour toute l'unité.
 * @return string HTML.
 */
function scoutsunite_imprimer_staff( $section ) {
	$infos    = scoutsunite_imprimer_section( $section );
	$membres  = array();
	$contacts = array();

	if ( isset( scoutsunite_sections()[ $section ] ) ) {
		$chef = scoutsunite_unite( $section . '_chef' );
		if ( '' !== $chef ) {
			$membres[] = array( $chef, 'chef de section' );
		}
		foreach ( array_filter( array_map( 'trim', explode( "\n", scoutsunite_unite( $section . '_staff' ) ) ) ) as $ligne ) {
			$membres[] = array( $ligne, '' );
		}
		$contacts[] = implode( ' · ', array_filter( array( scoutsunite_unite( $section . '_email' ), scoutsunite_unite( $section . '_gsm' ) ) ) );
	} elseif ( 'iama' === $section ) {
		if ( '' !== scoutsunite_unite( 'iama_chef' ) ) {
			$membres[] = array( scoutsunite_unite( 'iama_chef' ), 'responsable IAmA' );
		}
		$contacts[] = scoutsunite_unite( 'iama_email' );
	}
	if ( 'iama' !== $section ) {
		$ru         = scoutsunite_unite( 'ru_nom' );
		$contacts[] = implode( ' · ', array_filter( array( $ru ? 'Responsable d’unité : ' . $ru : '', scoutsunite_unite( 'ru_telephone' ) ) ) );
		$contacts[] = '' === $section || 'unite' === $section ? scoutsunite_unite( 'staff_email' ) : '';
	}
	$local = trim( scoutsunite_unite( 'local_rue' ) . ', ' . scoutsunite_unite( 'local_localite' ), ', ' );
	if ( '' !== $local ) {
		$contacts[] = 'Le local : ' . $local;
	}

	$html = '';
	if ( $membres ) {
		$html .= '<ul class="feuille__membres">';
		foreach ( $membres as $m ) {
			list( $totem, $prenom, $fonction, $gsm ) = scoutsunite_unite_personne( $m[0] );
			$roles = implode( ' · ', array_filter( array( $m[1], $fonction ) ) );
			$html .= '<li><span>' . ( '' !== $totem ? '<strong>' . esc_html( $totem ) . '</strong> ' : '' ) . esc_html( $prenom ) . '</span>';
			$html .= ( $roles ? '<em>' . esc_html( $roles ) . '</em>' : '' ) . ( $gsm ? '<span class="feuille__gsm">' . esc_html( $gsm ) . '</span>' : '' ) . '</li>';
		}
		$html .= '</ul>';
	}
	$contacts = array_filter( $contacts );
	if ( $contacts ) {
		$html .= '<p class="feuille__contact">' . implode( '<br>', array_map( 'esc_html', $contacts ) ) . '</p>';
	}
	$whatsapp = scoutsunite_imprimer_whatsapp( $section );
	if ( '' === $html && '' === $whatsapp ) {
		return '';
	}
	$equipe = '' === $html ? '' : '<div class="feuille__equipe"><h2>' . esc_html( $infos['staff'] ) . '</h2>' . $html . '</div>';
	return '<section class="feuille__staff">' . $equipe . $whatsapp . '</section>';
}

/**
 * QR codes des groupes WhatsApp de la section et de l'unité, à scanner depuis le frigo.
 *
 * @param string $section Identifiant de section, '' pour toute l'unité.
 * @return string HTML.
 */
function scoutsunite_imprimer_whatsapp( $section ) {
	$html = '';
	foreach ( array_unique( array_filter( array( $section, 'unite' ) ) ) as $groupe ) {
		$lien = scoutsunite_whatsapp_lien( $groupe );
		$nom  = 'Groupe WhatsApp ' . scoutsunite_whatsapp_de( $groupe );
		$qr   = '' === $lien ? '' : scoutsunite_qr_svg( $lien, 'QR code du ' . $nom );
		// Sur papier, un groupe sans QR code ne sert à rien.
		if ( '' === $qr ) {
			continue;
		}
		$html .= '<div class="feuille__groupe">' . $qr;
		$html .= '<p>' . esc_html( $nom ) . '</p></div>';
	}
	return $html ? '<div class="feuille__whatsapp">' . $html . '<p class="feuille__scanne">Scanne avec ton téléphone pour rejoindre</p></div>' : '';
}

/**
 * Pastille de date : « sam. 24 oct. », ou « ven.–dim. 13–15 nov. » pour un week-end.
 *
 * @param int $post_id Réunion.
 * @return string HTML.
 */
function scoutsunite_imprimer_pastille( $post_id ) {
	$debut = get_post_meta( $post_id, 'su_date', true );
	$fin   = get_post_meta( $post_id, 'su_jusqua', true );
	if ( ! $fin || $fin <= $debut || ! get_post_meta( $post_id, 'su_type', true ) ) {
		$fin = '';
	}
	$part = function ( $format ) use ( $debut, $fin ) {
		$a = scoutsunite_agenda_jour( $debut, $format );
		$b = $fin ? scoutsunite_agenda_jour( $fin, $format ) : '';
		return $b && $b !== $a ? $a . '–' . $b : $a;
	};
	return '<p class="feuille__pastille"><span>' . esc_html( $part( 'D' ) ) . '</span><strong>' . esc_html( $part( 'j' ) ) . '</strong><span>' . esc_html( $part( 'M' ) ) . '</span></p>';
}

/**
 * Notes d'une réunion, sans les blocs qui n'ont rien à faire sur papier.
 *
 * @param WP_Post $post Réunion.
 * @return string HTML.
 */
function scoutsunite_imprimer_notes( $post ) {
	if ( '' === trim( wp_strip_all_tags( $post->post_content ) ) ) {
		return '';
	}
	$notes = scoutsunite_agenda_notes( $post );
	return '' === trim( $notes ) ? '' : '<div class="feuille__notes">' . $notes . '</div>';
}

/**
 * Programme : les réunions des prochains mois, une ligne par réunion.
 *
 * @param string $section Identifiant de section, '' pour toute l'unité.
 * @param int    $mois    Nombre de mois à couvrir.
 * @return array Titre de la feuille, sous-titre, HTML du corps.
 */
function scoutsunite_imprimer_programme( $section, $mois ) {
	$noms   = scoutsunite_agenda_sections();
	$limite = wp_date( 'Y-m-d', ( new DateTimeImmutable( 'now', wp_timezone() ) )->modify( '+' . $mois . ' months' )->getTimestamp() );
	// Une section voit aussi les activités de toute l'unité, comme sur sa page.
	$qui      = isset( scoutsunite_sections()[ $section ] ) ? array( $section, 'unite' ) : $section;
	$reunions = array_filter(
		scoutsunite_agenda_a_venir( $qui, 200 ),
		function ( $r ) use ( $limite ) {
			return get_post_meta( $r->ID, 'su_date', true ) <= $limite;
		}
	);

	if ( ! $reunions ) {
		return array( 'Programme', '', '<p class="feuille__vide">Pas encore de réunion annoncée pour cette période. Le staff publie les dates sur le site dès qu’elles sont fixées.</p>' );
	}

	// Au-delà de 8 réunions, deux colonnes : le programme de trois mois tient sur une feuille.
	$html = '<ol class="feuille__reunions' . ( count( $reunions ) > 8 ? ' feuille__reunions--colonnes' : '' ) . '">';
	foreach ( $reunions as $r ) {
		$s     = get_post_meta( $r->ID, 'su_section', true );
		$titre = scoutsunite_agenda_titre( $r );
		$type  = scoutsunite_agenda_type( $r->ID );
		$quoi  = $titre ? $titre : ( $type ? $type : 'Réunion' );
		$tags  = array();
		if ( $titre && $type ) {
			$tags[] = $type;
		}
		if ( $s !== $section && isset( $noms[ $s ] ) ) {
			$tags[] = $noms[ $s ];
		}
		$infos = array_filter( array( scoutsunite_agenda_horaire( $r->ID ), get_post_meta( $r->ID, 'su_lieu', true ) ) );

		$annulee = scoutsunite_agenda_annulee( $r->ID );
		$html   .= '<li class="feuille__reunion feuille--' . esc_attr( isset( $noms[ $s ] ) ? $s : 'unite' ) . ( $annulee ? ' feuille__reunion--annulee' : '' ) . '">';
		$html .= scoutsunite_imprimer_pastille( $r->ID );
		$html .= '<div class="feuille__details">';
		$html .= '<p class="feuille__quoi">' . ( $annulee ? '<span class="feuille__annulee">Annulée</span> ' : '' ) . esc_html( $quoi );
		foreach ( $tags as $tag ) {
			$html .= ' <span class="feuille__tag">' . esc_html( $tag ) . '</span>';
		}
		$html .= '</p>';
		if ( $infos ) {
			$html .= '<p class="feuille__infos">' . esc_html( implode( ' · ', $infos ) ) . '</p>';
		}
		if ( scoutsunite_agenda_pourquoi( $r->ID ) ) {
			$html .= '<p class="feuille__infos">' . esc_html( scoutsunite_agenda_pourquoi( $r->ID ) ) . '</p>';
		}
		$html .= ( $annulee ? '' : scoutsunite_imprimer_notes( $r ) ) . '</div></li>';
	}
	$html .= '</ol>';

	$premier = get_post_meta( reset( $reunions )->ID, 'su_date', true );
	$dernier = get_post_meta( end( $reunions )->ID, 'su_date', true );
	if ( substr( $premier, 0, 7 ) === substr( $dernier, 0, 7 ) ) {
		$periode = ucfirst( scoutsunite_agenda_jour( $premier, 'F Y' ) );
	} elseif ( substr( $premier, 0, 4 ) === substr( $dernier, 0, 4 ) ) {
		$periode = ucfirst( scoutsunite_agenda_jour( $premier, 'F' ) ) . ' – ' . scoutsunite_agenda_jour( $dernier, 'F Y' );
	} else {
		$periode = ucfirst( scoutsunite_agenda_jour( $premier, 'F Y' ) ) . ' – ' . scoutsunite_agenda_jour( $dernier, 'F Y' );
	}
	return array( 'Programme', $periode, $html );
}

/**
 * « À retenir » : les camps à venir de la section et de toute l'unité, même au-delà de la période
 * imprimée, pour que la date du camp soit sur le frigo dès le premier programme de l'année.
 *
 * @param string $section Identifiant de section, '' ou 'unite' pour toute l'unité.
 * @param int    $sauf    Réunion à ne pas rappeler (la convocation de ce camp lui-même).
 * @return string HTML, vide s'il n'y a pas de camp à venir.
 */
function scoutsunite_imprimer_camps( $section, $sauf = 0 ) {
	$noms   = scoutsunite_agenda_sections();
	$unite  = '' === $section || 'unite' === $section;
	$camps  = scoutsunite_agenda_a_venir( $unite ? '' : array( $section, 'unite' ), 10, false, array( 'camp' ) );
	$lignes = '';
	foreach ( $camps as $c ) {
		if ( (int) $c->ID === (int) $sauf || scoutsunite_agenda_annulee( $c->ID ) ) {
			continue;
		}
		$s     = get_post_meta( $c->ID, 'su_section', true );
		$titre = scoutsunite_agenda_titre( $c );
		$fin   = get_post_meta( $c->ID, 'su_jusqua', true );
		$annee = substr( $fin ? $fin : get_post_meta( $c->ID, 'su_date', true ), 0, 4 );
		$qui   = $s !== $section && isset( $noms[ $s ] ) ? ' <span class="feuille__tag">' . esc_html( $noms[ $s ] ) . '</span>' : '';

		$lignes .= '<li><strong>' . esc_html( $titre ? $titre : 'Camp' ) . '</strong> ' . esc_html( scoutsunite_agenda_quand( $c->ID ) . ' ' . $annee ) . $qui . '</li>';
	}
	return $lignes ? '<section class="feuille__rappel"><h2>À retenir</h2><ul>' . $lignes . '</ul></section>' : '';
}

/**
 * Convocation : une réunion en grand, avec toutes ses notes.
 *
 * @param WP_Post $post Réunion.
 * @return array Titre de la feuille, sous-titre, HTML du corps.
 */
function scoutsunite_imprimer_convocation( $post ) {
	$titre = scoutsunite_agenda_titre( $post );
	$type  = scoutsunite_agenda_type( $post->ID );
	$lieu  = get_post_meta( $post->ID, 'su_lieu', true );

	$html = '<div class="feuille__convocation">';
	if ( scoutsunite_agenda_annulee( $post->ID ) ) {
		$pourquoi = scoutsunite_agenda_pourquoi( $post->ID );
		$html    .= '<p class="feuille__bandeau-annulee"><strong>Cette activité est annulée</strong>' . ( $pourquoi ? esc_html( $pourquoi ) : '' ) . '</p>';
	}
	$html .= '<p class="feuille__quand">' . esc_html( ucfirst( scoutsunite_agenda_quand( $post->ID ) ) ) . '</p>';
	$html .= '<dl class="feuille__pratique">';
	if ( scoutsunite_agenda_horaire( $post->ID ) ) {
		$html .= '<div><dt>Horaire</dt><dd>' . esc_html( scoutsunite_agenda_horaire( $post->ID ) ) . '</dd></div>';
	}
	if ( $lieu ) {
		$html .= '<div><dt>Où</dt><dd>' . esc_html( $lieu ) . '</dd></div>';
	}
	$html .= '</dl>';
	$html .= scoutsunite_imprimer_notes( $post ) . '</div>';

	return array( $titre ? $titre : ( $type ? $type : 'Convocation' ), $titre && $type ? $type : '', $html );
}

/**
 * Sert la feuille à la place de la page quand l'adresse porte ?imprimer.
 */
function scoutsunite_imprimer() {
	if ( ! isset( $_GET['imprimer'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$sections = scoutsunite_agenda_sections();
	$mois     = 3;

	if ( is_singular( 'reunion' ) ) {
		$post    = get_queried_object();
		$section = get_post_meta( $post->ID, 'su_section', true );
		$section = isset( $sections[ $section ] ) ? $section : 'unite';
		list( $titre, $sous_titre, $corps ) = scoutsunite_imprimer_convocation( $post );
		$retour  = get_permalink( $post );
	} elseif ( is_post_type_archive( 'reunion' ) ) {
		// Lecture seule depuis l'adresse, comme le filtre de l'agenda.
		$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$section = isset( $sections[ $section ] ) ? $section : '';
		$demande = isset( $_GET['mois'] ) ? absint( $_GET['mois'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$mois    = isset( scoutsunite_imprimer_durees()[ $demande ] ) ? $demande : $mois;
		list( $titre, $sous_titre, $corps ) = scoutsunite_imprimer_programme( $section, $mois );
		$retour  = $section ? add_query_arg( 'section', $section, get_post_type_archive_link( 'reunion' ) ) : get_post_type_archive_link( 'reunion' );
	} else {
		return;
	}

	$infos = scoutsunite_imprimer_section( $section );
	$nom   = '' === $section ? 'Agenda de l’unité' : $infos['nom'];
	$theme = scoutsunite_version( 'assets/css/imprimer.css' );
	$site  = preg_replace( '#^https?://(www\.)?#', '', get_post_type_archive_link( 'reunion' ) );

	nocache_headers();
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo esc_html( $titre . ' · ' . $nom . ' · ' . get_bloginfo( 'name' ) ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', $theme, get_theme_file_uri( 'assets/css/imprimer.css' ) ) ); ?>">
</head>
<body>
<nav class="barre" aria-label="Impression">
	<a href="<?php echo esc_url( $retour ); ?>">← Retour au site</a>
	<?php if ( is_post_type_archive( 'reunion' ) ) : ?>
		<form method="get" action="<?php echo esc_url( get_post_type_archive_link( 'reunion' ) ); ?>">
			<input type="hidden" name="imprimer" value="1">
			<?php if ( $section ) : ?>
				<input type="hidden" name="section" value="<?php echo esc_attr( $section ); ?>">
			<?php endif; ?>
			<label>Réunions pour
				<select name="mois" onchange="this.form.submit()">
					<?php foreach ( scoutsunite_imprimer_durees() as $n => $libelle ) : ?>
						<option value="<?php echo esc_attr( $n ); ?>"<?php selected( $mois, $n ); ?>><?php echo esc_html( $libelle ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<noscript><button type="submit">Afficher</button></noscript>
		</form>
	<?php endif; ?>
	<button type="button" class="barre__imprimer" onclick="window.print()">Imprimer ou enregistrer en PDF</button>
	<p class="barre__aide">Pour un PDF, choisis « Enregistrer au format PDF » comme imprimante.</p>
</nav>
<main class="feuille feuille--<?php echo esc_attr( $section ? $section : 'unite' ); ?>">
	<header class="feuille__entete">
		<img class="feuille__logo" src="<?php echo scoutsunite_image( $infos['logo'] ); ?>" alt="">
		<div>
			<p class="feuille__unite"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1><?php echo esc_html( $nom ); ?></h1>
			<p class="feuille__titre"><?php echo esc_html( $titre ); ?><?php echo $sous_titre ? ' <span>' . esc_html( $sous_titre ) . '</span>' : ''; ?></p>
		</div>
	</header>
	<?php echo scoutsunite_imprimer_camps( $section, isset( $post ) ? $post->ID : 0 ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé à la construction. ?>
	<?php echo $corps; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé à la construction. ?>
	<?php $bon = scoutsunite_bon_a_savoir( $section, false ); ?>
	<?php if ( $bon ) : ?>
		<section class="feuille__bon"><h2>Bon à savoir</h2><?php echo $bon; // phpcs:ignore WordPress.Security.EscapeOutput ?></section>
	<?php endif; ?>
	<?php echo scoutsunite_imprimer_staff( $section ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<footer class="feuille__pied">
		À jour au <?php echo esc_html( wp_date( 'j F Y' ) ); ?>. Toujours à jour sur <strong><?php echo esc_html( untrailingslashit( $site ) ); ?></strong>
	</footer>
</main>
</body>
</html>
	<?php
	exit;
}
add_action( 'template_redirect', 'scoutsunite_imprimer' );

/**
 * Liste des réunions dans l'administration : « Convocation à imprimer » sous chaque réunion publiée.
 *
 * @param array   $actions Liens sous le titre.
 * @param WP_Post $post    Réunion.
 * @return array
 */
function scoutsunite_imprimer_action( $actions, $post ) {
	if ( 'reunion' === $post->post_type && 'publish' === $post->post_status ) {
		$actions['imprimer'] = '<a href="' . esc_url( scoutsunite_imprimer_url( '', $post ) ) . '" target="_blank">Convocation à imprimer</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'scoutsunite_imprimer_action', 10, 2 );
