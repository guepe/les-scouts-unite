<?php
/**
 * L'agenda dans le calendrier du téléphone : un flux iCalendar auquel les parents
 * s'abonnent (Google Agenda, Apple Calendrier, Outlook), et un fichier .ics par réunion.
 *
 * - /agenda/?section=baladins&ical : les réunions de la section (et de toute l'unité),
 *   à venir et des 30 derniers jours ; /agenda/?ical : tout l'agenda ;
 * - /agenda/une-reunion/?ical : cette seule réunion, à ajouter une fois.
 *
 * Aussi : les liens « à mettre sur le frigo » et « Ajouter à mon calendrier » sous les blocs de l'agenda.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adresse du flux d'une section, '' pour toute l'unité.
 *
 * @param string $section Identifiant de section.
 * @return string URL.
 */
function scoutsunite_calendrier_url( $section = '' ) {
	$args = $section ? array( 'section' => $section, 'ical' => '1' ) : array( 'ical' => '1' );
	return add_query_arg( $args, get_post_type_archive_link( 'reunion' ) );
}

/**
 * Texte d'une propriété iCalendar : barre oblique inverse, point-virgule, virgule et retours à la ligne échappés.
 *
 * @param string $texte Texte brut.
 * @return string
 */
function scoutsunite_calendrier_texte( $texte ) {
	// Les titres WordPress arrivent avec des entités (« d&rsquo;automne ») : le calendrier veut le texte.
	$texte = html_entity_decode( $texte, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$texte = str_replace( array( '\\', ';', ',' ), array( '\\\\', '\\;', '\\,' ), $texte );
	return preg_replace( '/\R/u', '\\n', trim( $texte ) );
}

/**
 * Une ligne de contenu, pliée à 75 octets sans couper un caractère (RFC 5545, 3.1).
 *
 * @param string $ligne Propriété complète, « NOM:valeur ».
 * @return string Ligne terminée par CRLF.
 */
function scoutsunite_calendrier_ligne( $ligne ) {
	$plie   = '';
	$limite = 75;
	while ( strlen( $ligne ) > $limite ) {
		$morceau = mb_strcut( $ligne, 0, $limite, 'UTF-8' );
		$plie   .= $morceau . "\r\n ";
		$ligne   = substr( $ligne, strlen( $morceau ) );
		$limite  = 74; // La suite commence par une espace.
	}
	return $plie . $ligne . "\r\n";
}

/**
 * Fuseau des heures de réunion : celui du site, ou Bruxelles si le site est resté en UTC
 * (le réglage par défaut de WordPress) : les heures encodées sont toujours belges.
 *
 * @return DateTimeZone
 */
function scoutsunite_calendrier_fuseau() {
	return scoutsunite_calendrier_fuseau_utc() ? new DateTimeZone( 'Europe/Brussels' ) : wp_timezone();
}

/**
 * Le site est-il resté sur UTC, sans fuseau choisi ?
 *
 * @return bool
 */
function scoutsunite_calendrier_fuseau_utc() {
	return in_array( wp_timezone_string(), array( 'UTC', '+00:00' ), true );
}

/**
 * Date et heure locales d'une réunion en UTC : « 20261031T130000Z ».
 *
 * @param string $date     Jour, Y-m-d.
 * @param string $heure    Heure, H:i.
 * @param bool   $lendemain Le lendemain (une fin à minuit ou après minuit).
 * @return string
 */
function scoutsunite_calendrier_utc( $date, $heure, $lendemain = false ) {
	$moment = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' ' . substr( $heure, 0, 5 ), scoutsunite_calendrier_fuseau() );
	if ( $moment && $lendemain ) {
		$moment = $moment->modify( '+1 day' );
	}
	return $moment ? $moment->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Ymd\THis\Z' ) : '';
}

/**
 * Un administrateur est prévenu si le site est resté en UTC : les dates de WordPress
 * et le « aujourd'hui » de l'agenda seraient décalés.
 */
function scoutsunite_calendrier_avis_fuseau() {
	if ( current_user_can( 'manage_options' ) && scoutsunite_calendrier_fuseau_utc() ) {
		echo '<div class="notice notice-warning"><p>Le fuseau horaire du site est UTC. Choisis <strong>Bruxelles</strong> dans <a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">Réglages → Général → Fuseau horaire</a>, pour que les heures et les dates soient justes partout.</p></div>';
	}
}
add_action( 'admin_notices', 'scoutsunite_calendrier_avis_fuseau' );

/**
 * Un événement par réunion. Un week-end ou un camp occupe ses jours entiers, l'horaire
 * passe dans la description ; une réunion sans heure occupe sa journée.
 *
 * @param WP_Post $r Réunion.
 * @return string Lignes de l'événement.
 */
function scoutsunite_calendrier_evenement( $r ) {
	$sections = scoutsunite_agenda_sections();
	$section  = get_post_meta( $r->ID, 'su_section', true );
	// Relues avec les mêmes contrôles qu'à l'enregistrement : une réunion encodée avant la 2.10.3 ne fait pas tomber le flux.
	$date     = scoutsunite_agenda_date_valide( get_post_meta( $r->ID, 'su_date', true ) );
	$fin_jour = scoutsunite_agenda_date_valide( get_post_meta( $r->ID, 'su_jusqua', true ) );
	$debut    = scoutsunite_agenda_heure_valide( get_post_meta( $r->ID, 'su_debut', true ) );
	$fin      = scoutsunite_agenda_heure_valide( get_post_meta( $r->ID, 'su_fin', true ) );
	if ( '' === $date ) {
		return '';
	}
	$titre    = scoutsunite_agenda_titre( $r );
	$type     = scoutsunite_agenda_type( $r->ID );
	$quoi     = $titre ? $titre : ( $type ? $type : 'Réunion' );

	$lignes = array(
		'BEGIN:VEVENT',
		'UID:reunion-' . $r->ID . '@' . wp_parse_url( home_url(), PHP_URL_HOST ),
		'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
		'LAST-MODIFIED:' . mysql2date( 'Ymd\THis\Z', $r->post_modified_gmt, false ),
		'SUMMARY:' . scoutsunite_calendrier_texte( ( scoutsunite_agenda_annulee( $r->ID ) ? 'ANNULÉ – ' : '' ) . ( isset( $sections[ $section ] ) ? $sections[ $section ] . ' · ' : '' ) . $quoi ),
		// Une réunion annulée reste dans le flux : le calendrier du parent la barre ou la retire.
		'STATUS:' . ( scoutsunite_agenda_annulee( $r->ID ) ? 'CANCELLED' : 'CONFIRMED' ),
	);

	$plusieurs_jours = $type && $fin_jour > $date && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fin_jour );
	if ( $plusieurs_jours || ! preg_match( '/^\d{1,2}:\d{2}/', $debut ) ) {
		$dernier  = $plusieurs_jours ? $fin_jour : $date;
		$lignes[] = 'DTSTART;VALUE=DATE:' . str_replace( '-', '', $date );
		// La fin d'un événement d'un jour entier est le lendemain du dernier jour.
		$lignes[] = 'DTEND;VALUE=DATE:' . ( new DateTimeImmutable( $dernier ) )->modify( '+1 day' )->format( 'Ymd' );
	} else {
		$lignes[] = 'DTSTART:' . scoutsunite_calendrier_utc( $date, $debut );
		if ( preg_match( '/^\d{1,2}:\d{2}/', $fin ) ) {
			// Une fin à minuit (ou plus tôt que le début) tombe le lendemain : « 20 h – minuit ».
			$lignes[] = 'DTEND:' . scoutsunite_calendrier_utc( $date, $fin, $fin <= $debut );
		}
	}

	// « au local » devient l'adresse du local, pour que le téléphone propose l'itinéraire.
	$lieu  = get_post_meta( $r->ID, 'su_lieu', true );
	$local = trim( scoutsunite_unite( 'local_rue' ) . ', ' . scoutsunite_unite( 'local_localite' ), ', ' );
	if ( $local && preg_match( '/\blocal\b/iu', $lieu ) ) {
		$lieu .= ' (' . $local . ')';
	}
	if ( $lieu ) {
		$lignes[] = 'LOCATION:' . scoutsunite_calendrier_texte( $lieu );
	}

	// Un week-end ou un camp : « Départ vendredi 20 novembre à 18 h, retour dimanche 22 novembre à 16 h. »
	$horaire = '';
	if ( $plusieurs_jours ) {
		$depart  = scoutsunite_agenda_heure( $debut );
		$retour  = scoutsunite_agenda_heure( $fin );
		$horaire = implode(
			', ',
			array_filter(
				array(
					$depart ? 'Départ ' . scoutsunite_agenda_jour( $date ) . ' à ' . $depart : '',
					$retour ? ( $depart ? 'retour ' : 'Retour ' ) . scoutsunite_agenda_jour( $fin_jour ) . ' à ' . $retour : '',
				)
			)
		);
		$horaire = $horaire ? $horaire . '.' : '';
	}
	$pourquoi    = scoutsunite_agenda_pourquoi( $r->ID );
	$description = array_filter(
		array(
			scoutsunite_agenda_annulee( $r->ID ) ? 'Réunion annulée' . ( $pourquoi ? ' : ' . $pourquoi : '.' ) : '',
			$horaire,
			scoutsunite_agenda_notes_visibles( $r ) ? trim( html_entity_decode( wp_strip_all_tags( $r->post_content ), ENT_QUOTES, 'UTF-8' ) ) : '',
			'Plus d’infos : ' . get_permalink( $r ),
		)
	);
	$lignes[] = 'DESCRIPTION:' . scoutsunite_calendrier_texte( implode( "\n\n", $description ) );
	$lignes[] = 'URL:' . get_permalink( $r );
	$lignes[] = 'END:VEVENT';

	return implode( '', array_map( 'scoutsunite_calendrier_ligne', $lignes ) );
}

/**
 * Réunions du flux : celles de la section (et de toute l'unité pour une section d'âge),
 * à venir ou terminées depuis moins de 30 jours.
 *
 * @param string $section Identifiant de section, '' pour tout l'agenda.
 * @return WP_Post[]
 */
function scoutsunite_calendrier_reunions( $section ) {
	$depuis     = wp_date( 'Y-m-d', time() - 30 * DAY_IN_SECONDS );
	$meta_query = array(
		array(
			'relation' => 'OR',
			array(
				'key'     => 'su_date',
				'value'   => $depuis,
				'compare' => '>=',
				'type'    => 'DATE',
			),
			array(
				'key'     => 'su_jusqua',
				'value'   => $depuis,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	);
	if ( $section ) {
		$meta_query[] = array(
			'key'     => 'su_section',
			'value'   => isset( scoutsunite_sections()[ $section ] ) ? array( $section, 'unite' ) : array( $section ),
			'compare' => 'IN',
		);
	}
	return get_posts(
		array(
			'post_type'      => 'reunion',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
}

/**
 * Sert le calendrier à la place de la page quand l'adresse porte ?ical.
 */
function scoutsunite_calendrier() {
	if ( ! isset( $_GET['ical'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$sections = scoutsunite_agenda_sections();
	$unite    = get_bloginfo( 'name' );

	if ( is_singular( 'reunion' ) ) {
		$reunions = array( get_queried_object() );
		$nom      = $unite;
		$fichier  = 'reunion-' . get_post_meta( $reunions[0]->ID, 'su_date', true ) . '.ics';
	} elseif ( is_post_type_archive( 'reunion' ) ) {
		$section  = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$section  = isset( $sections[ $section ] ) ? $section : '';
		$reunions = scoutsunite_calendrier_reunions( $section );
		$nom      = $unite . ( $section ? ' · ' . $sections[ $section ] : '' );
		$fichier  = '';
	} else {
		return;
	}

	$entete = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//Les Scouts – Unité//les-scouts-unite ' . wp_get_theme()->get( 'Version' ) . '//FR',
		'CALSCALE:GREGORIAN',
		'METHOD:PUBLISH',
		'X-WR-CALNAME:' . scoutsunite_calendrier_texte( $nom ),
		'X-WR-TIMEZONE:' . scoutsunite_calendrier_fuseau()->getName(),
		// Les clients qui le lisent reviennent chercher les changements deux fois par jour.
		'REFRESH-INTERVAL;VALUE=DURATION:PT12H',
		'X-PUBLISHED-TTL:PT12H',
	);
	$ics = implode( '', array_map( 'scoutsunite_calendrier_ligne', $entete ) );
	foreach ( $reunions as $r ) {
		$ics .= scoutsunite_calendrier_evenement( $r );
	}
	$ics .= scoutsunite_calendrier_ligne( 'END:VCALENDAR' );

	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	if ( $fichier ) {
		header( 'Content-Disposition: attachment; filename="' . $fichier . '"' );
	}
	echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput -- texte iCalendar, échappé propriété par propriété.
	exit;
}
add_action( 'template_redirect', 'scoutsunite_calendrier' );

/* ---------------------------------------------------------------------------
   Liens sous les blocs de l'agenda
   ------------------------------------------------------------------------ */

/**
 * Icône d'un lien pratique, au trait : le frigo (avec sa feuille épinglée) ou le calendrier.
 *
 * @param string $nom 'frigo' ou 'calendrier'.
 * @return string SVG.
 */
function scoutsunite_liens_icone( $nom ) {
	$traits = array(
		'frigo'      => '<rect x="4" y="1.5" width="16" height="21"/><path d="M4 8.5h16M7 4v2M7 11v3"/><g transform="rotate(7 14 16)"><rect class="liens-pratiques__feuille" x="10" y="11" width="8" height="9.5"/><path d="M12 15h4M12 17.5h3" stroke-width="1.25"/></g><circle class="liens-pratiques__punaise" cx="14.2" cy="11" r="1.6"/>',
		'calendrier' => '<rect x="3" y="5" width="18" height="16"/><path d="M3 10h18M8 3v4M16 3v4"/><path d="M7 14h2M11 14h2M15 14h2M7 17h2M11 17h2"/>',
	);
	return '<svg class="liens-pratiques__icone" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="square">' . $traits[ $nom ] . '</svg>';
}

/**
 * « Le programme à mettre sur le frigo » et « Ajouter à mon calendrier ».
 *
 * @param string $section Section du programme, '' pour tout l'agenda.
 * @param int    $post_id Réunion : sa convocation et son fichier .ics.
 * @return string HTML.
 */
function scoutsunite_liens_pratiques( $section = '', $post_id = 0 ) {
	if ( $post_id ) {
		$frigo = '<a href="' . esc_url( scoutsunite_imprimer_url( '', $post_id ) ) . '">' . scoutsunite_liens_icone( 'frigo' ) . 'La convocation à mettre sur le frigo</a>';
		$cal   = '<a href="' . esc_url( add_query_arg( 'ical', '1', get_permalink( $post_id ) ) ) . '">' . scoutsunite_liens_icone( 'calendrier' ) . 'Ajouter à mon calendrier</a>';
		return '<div class="liens-pratiques">' . $frigo . $cal . '</div>';
	}

	$frigo = '<a href="' . esc_url( scoutsunite_imprimer_url( $section ) ) . '">' . scoutsunite_liens_icone( 'frigo' ) . ( $section ? 'Le programme à mettre sur le frigo' : 'L’agenda à mettre sur le frigo' ) . '</a>';

	// Un abonnement : webcal:// pour Apple et Outlook, le même lien confié à Google Agenda, ou à copier.
	$flux   = scoutsunite_calendrier_url( $section );
	$webcal = preg_replace( '#^https?://#', 'webcal://', $flux );
	$google = 'https://calendar.google.com/calendar/render?cid=' . rawurlencode( $webcal );
	$cal    = '<details class="liens-pratiques__calendrier"><summary>' . scoutsunite_liens_icone( 'calendrier' ) . 'Ajouter à mon calendrier</summary><div>';
	$cal   .= '<p>L’agenda se met à jour tout seul dans ton téléphone : une réunion ajoutée ou déplacée apparaît d’elle-même (Google peut mettre quelques heures).</p>';
	$cal   .= '<p><a href="' . esc_url( $webcal, array( 'webcal' ) ) . '">iPhone, Mac ou Outlook</a> · <a href="' . esc_url( $google ) . '" target="_blank" rel="noopener">Google Agenda</a></p>';
	$cal   .= '<p><label>Ou copie ce lien dans ton application de calendrier :<input type="text" readonly value="' . esc_attr( $flux ) . '" onclick="this.select()"></label></p>';
	$cal   .= '</div></details>';

	return '<div class="liens-pratiques">' . $frigo . $cal . '</div>';
}
