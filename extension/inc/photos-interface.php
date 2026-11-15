<?php
/**
 * Interface des photos de camp : une page isolée des outils de suivi, adaptée au téléphone.
 * Les parents arrivent par le lien du staff ; les animateurs réutilisent leur connexion WordPress.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Entrée du menu WordPress, réservée aux comptes autorisés. */
function scoutsunite_photos_menu() {
	add_menu_page( 'Photos de camp', 'Photos de camp', 'scoutsunite_photos', 'scoutsunite-photos', 'scoutsunite_photos_menu_page', 'dashicons-format-gallery', 7 );
}
add_action( 'admin_menu', 'scoutsunite_photos_menu' );

/** Repli si une extension empêche la redirection de l'administration. */
function scoutsunite_photos_menu_page() {
	echo '<div class="wrap"><h1>Photos de camp</h1><p><a class="button button-primary" href="' . esc_url( scoutsunite_photos_url() ) . '">Ouvrir les photos de camp</a></p></div>';
}

/** Redirection avant tout envoi HTML dans l'administration. */
function scoutsunite_photos_menu_rediriger() {
	if ( isset( $_GET['page'] ) && 'scoutsunite-photos' === $_GET['page'] && current_user_can( 'scoutsunite_photos' ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- navigation seule.
		wp_safe_redirect( scoutsunite_photos_url() );
		exit;
	}
}
add_action( 'admin_init', 'scoutsunite_photos_menu_rediriger' );

/**
 * Consignes aux caches le plus tôt possible. Les caches placés avant PHP doivent aussi être configurés.
 */
function scoutsunite_photos_pas_de_cache() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	$base = trailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ) . 'photos';
	if ( 0 === strpos( $uri, $base . '/' ) || $uri === $base || isset( $_GET['su_photos'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- identification d'une route, pas d'action.
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
}
add_action( 'init', 'scoutsunite_photos_pas_de_cache', 0 );

/** En-têtes communs aux pages, aux images et aux réponses d'envoi. */
function scoutsunite_photos_entetes() {
	nocache_headers();
	header( 'Cache-Control: private, no-store, max-age=0, must-revalidate' );
	header( 'Referrer-Policy: no-referrer' );
	header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: DENY' );
	header( "Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' blob:; font-src 'self'; connect-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'; object-src 'none'" );
}

/**
 * Refuse un formulaire provenant d'un autre site en complément des nonces.
 *
 * @return bool
 */
function scoutsunite_photos_origine_valide() {
	if ( empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		return true;
	}
	$entete = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) );
	// no-referrer produit Origin: null pour les formulaires natifs de certains navigateurs.
	// Le contexte Fetch Metadata doit alors être same-origin ; le nonce reste exigé dans tous les cas.
	if ( 'null' === $entete ) {
		return isset( $_SERVER['HTTP_SEC_FETCH_SITE'] ) && 'same-origin' === sanitize_text_field( wp_unslash( $_SERVER['HTTP_SEC_FETCH_SITE'] ) );
	}
	$origine = wp_parse_url( sanitize_url( $entete ) );
	$site    = wp_parse_url( home_url() );
	if ( ! $origine || ! $site || ! isset( $origine['scheme'], $origine['host'], $site['scheme'], $site['host'] ) ) {
		return false;
	}
	$port_origine = $origine['port'] ?? ( 'https' === $origine['scheme'] ? 443 : 80 );
	$port_site    = $site['port'] ?? ( 'https' === $site['scheme'] ? 443 : 80 );
	return $origine['scheme'] === $site['scheme'] && strtolower( $origine['host'] ) === strtolower( $site['host'] ) && $port_origine === $port_site;
}

/**
 * Le formulaire de déconnexion parent est lié à sa propre session, même sans compte WordPress.
 *
 * @param string      $operation Action.
 * @param object|null $album Album.
 * @return string Action de nonce.
 */
function scoutsunite_photos_nonce_action( $operation, $album = null ) {
	if ( 'quitter' === $operation && $album ) {
		return 'scoutsunite_photos_quitter_' . hash( 'sha256', scoutsunite_photos_jeton( $album->id ) );
	}
	return 'ouvrir' === $operation ? 'scoutsunite_photos_ouvrir' : 'scoutsunite_photos';
}

/**
 * Pose ou efface le cookie d'album. Le jeton brut n'est jamais conservé en base.
 *
 * @param int    $id Album.
 * @param string $jeton Jeton, vide pour quitter.
 * @param int    $expiration Date UTC.
 */
function scoutsunite_photos_poser_cookie( $id, $jeton, $expiration ) {
	setcookie( scoutsunite_photos_cookie( $id ), $jeton, array( 'expires' => $expiration, 'path' => wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/', 'secure' => is_ssl() || 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ), 'httponly' => true, 'samesite' => 'Lax' ) );
}

/**
 * Envoi mobile : entrée connectée seulement, nonce et droits vérifiés à nouveau par le cœur.
 */
function scoutsunite_photos_ajax() {
	scoutsunite_photos_entetes();
	if ( ! scoutsunite_photos_origine_valide() || ! check_ajax_referer( 'scoutsunite_photos', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Ta connexion a expiré. Recharge la page et réessaie.' ), 403 );
	}
	// Chaque champ et le fichier sont validés dans executer ; ne jamais assainir le contenu binaire comme du texte.
	$donnees = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- champs lus et assainis par scoutsunite_photos_texte().
	$fichier = isset( $_FILES['fichier'] ) ? $_FILES['fichier'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- is_uploaded_file, taille, signature et décodage contrôlés dans executer/importer.
	$resultat = scoutsunite_photos_executer( 'envoyer', $donnees, $fichier );
	if ( is_wp_error( $resultat ) ) {
		$details = $resultat->get_error_data();
		wp_send_json_error( array( 'message' => $resultat->get_error_message() ), isset( $details['status'] ) ? $details['status'] : 400 );
	}
	wp_send_json_success( $resultat );
}
add_action( 'wp_ajax_scoutsunite_photos', 'scoutsunite_photos_ajax' );

/** Route des pages et fichiers ; aucune intégration à wp_head susceptible de charger un suivi externe. */
function scoutsunite_photos_requete() {
	global $wpdb;
	if ( ! get_query_var( 'su_photos' ) ) {
		return;
	}
	scoutsunite_photos_entetes();
	status_header( 200 );
	$adresse  = get_query_var( 'su_album' );
	$album    = is_string( $adresse ) && preg_match( '/^[a-f0-9]{24}$/D', $adresse ) ? scoutsunite_photos_album( $adresse ) : null;
	$resultat = null;
	if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) {
		$donnees = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- chaque valeur est lue par scoutsunite_photos_texte(), les sélections sont validées dans executer().
		$action  = scoutsunite_photos_texte( $donnees, 'operation' );
		$nonce   = scoutsunite_photos_texte( $donnees, '_wpnonce' );
		if ( isset( $donnees['photo_supprimer'] ) ) {
			$action = 'supprimer_photo';
			$donnees['photos'] = array( absint( scoutsunite_photos_texte( $donnees, 'photo_supprimer' ) ) );
		}
		if ( ! scoutsunite_photos_origine_valide() ) {
			$resultat = new WP_Error( 'origine', 'Envoie le formulaire depuis l’adresse du site. Recharge cette page avant de réessayer.' );
		} elseif ( ! wp_verify_nonce( $nonce, scoutsunite_photos_nonce_action( $action, $album ) ) ) {
			$resultat = new WP_Error( 'nonce', 'La page a expiré. Recharge-la avant de réessayer.' );
		} elseif ( 'ouvrir' === $action ) {
			$resultat = scoutsunite_photos_ouvrir( $album, scoutsunite_photos_texte( $donnees, 'cle' ) );
			if ( ! is_wp_error( $resultat ) ) {
				scoutsunite_photos_poser_cookie( $album->id, $resultat['jeton'], $resultat['expiration'] );
				wp_safe_redirect( scoutsunite_photos_url( $album ) );
				exit;
			}
		} elseif ( 'quitter' === $action && $album ) {
			$wpdb->delete( scoutsunite_photos_table( 'sessions' ), array( 'empreinte' => hash( 'sha256', scoutsunite_photos_jeton( $album->id ) ), 'album' => $album->id ), array( '%s', '%d' ) );
			scoutsunite_photos_poser_cookie( $album->id, '', time() - HOUR_IN_SECONDS );
			wp_safe_redirect( scoutsunite_photos_url() );
			exit;
		} elseif ( 'exporter' === $action ) {
			$resultat = scoutsunite_photos_exporter( $album, sanitize_title( scoutsunite_photos_texte( $donnees, 'tag' ) ) );
			if ( ! is_wp_error( $resultat ) ) {
				try {
					header( 'Content-Type: application/zip' );
					header( 'Content-Disposition: attachment; filename="camp-' . sanitize_key( $album->section ) . '-' . absint( $album->annee ) . '.zip"' );
					header( 'Content-Length: ' . filesize( $resultat ) );
					readfile( $resultat ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- archive privée autorisée, effacée après téléchargement.
				} finally {
					wp_delete_file( $resultat );
				}
				exit;
			}
		} else {
			// Le formulaire ne peut pas choisir un album différent de l'adresse affichée.
			$donnees['album'] = $album ? $album->id : 0;
			$resultat = scoutsunite_photos_executer( $action, $donnees );
			if ( ! is_wp_error( $resultat ) && ( 'creer' === $action || 'supprimer_album' === $action ) ) {
				wp_safe_redirect( scoutsunite_photos_url( ! empty( $resultat['album'] ) ? scoutsunite_photos_album( $resultat['album'] ) : null ) );
				exit;
			}
			$album = $album ? scoutsunite_photos_album( $album->id ) : null;
		}
	}
	if ( isset( $_GET['visionneuse'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- lecture soumise au compte ou à la session parent.
		$id  = is_scalar( $_GET['visionneuse'] ) ? absint( $_GET['visionneuse'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- contrôle des droits dans visionner().
		$tag = isset( $_GET['tag'] ) && is_string( $_GET['tag'] ) ? sanitize_title( wp_unslash( $_GET['tag'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- filtre de lecture.
		$vue = scoutsunite_photos_visionner( $album, $id, $tag );
		if ( is_wp_error( $vue ) ) {
			wp_send_json_error( array( 'message' => $vue->get_error_message() ), 403 );
		}
		wp_send_json_success( $vue );
	}
	if ( isset( $_GET['fichier'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- lecture autorisée par session ou compte, pas par nonce.
		$id    = is_scalar( $_GET['fichier'] ) ? absint( $_GET['fichier'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- lecture avec contrôle de droits.
		$image = scoutsunite_photos_image( $id );
		$mini  = isset( $_GET['format'] ) && 'mini' === $_GET['format']; // phpcs:ignore WordPress.Security.NonceVerification -- variante sans effet d'écriture.
		if ( ! scoutsunite_photos_peut_lire( $image, $album ) ) {
			status_header( 403 );
			exit;
		}
		$chemin = scoutsunite_photos_chemin( $image, $mini );
		if ( ! $chemin ) {
			status_header( 404 );
			exit;
		}
		header( 'Content-Type: image/jpeg' );
		header( 'Content-Length: ' . filesize( $chemin ) );
		if ( isset( $_GET['telecharger'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- téléchargement soumis aux mêmes droits que l'image.
			header( 'Content-Disposition: attachment; filename="photo-camp-' . absint( $album->annee ) . '-' . absint( $image->id ) . '.jpg"' );
		}
		readfile( $chemin ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- fichier privé servi après autorisation, sans URL publique.
		exit;
	}
	scoutsunite_photos_page( $album, $resultat, (bool) $adresse );
	exit;
}
add_action( 'template_redirect', 'scoutsunite_photos_requete', -20 );

/**
 * Champs cachés d'un formulaire. Jamais de secret dans les adresses de requêtes.
 *
 * @param string      $operation Action.
 * @param object|null $album Album.
 */
function scoutsunite_photos_champs( $operation, $album = null ) {
	wp_nonce_field( scoutsunite_photos_nonce_action( $operation, $album ), '_wpnonce', false );
	echo '<input type="hidden" name="operation" value="' . esc_attr( $operation ) . '">';
	if ( $album ) {
		echo '<input type="hidden" name="album" value="' . absint( $album->id ) . '">';
	}
}

/**
 * Titre de l'album, uniquement après contrôle d'accès par le rendu appelant.
 *
 * @param object $album Album.
 * @return string
 */
function scoutsunite_photos_titre( $album ) {
	return ( scoutsunite_photos_sections()[ $album->section ] ?? 'Section' ) . ' · ' . ( $album->titre ?: 'Camp' ) . ' · ' . $album->annee;
}

/**
 * Liste des seuls albums autorisés, limitée pour ne pas énumérer l'ensemble du site côté parent.
 *
 * @return array
 */
function scoutsunite_photos_catalogue() {
	global $wpdb;
	if ( current_user_can( 'scoutsunite_photos' ) ) {
		$albums = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY annee DESC, section LIMIT 200', scoutsunite_photos_table( 'albums' ) ) );
		return array_values( array_filter( $albums, 'scoutsunite_photos_peut_gerer' ) );
	}
	$albums = array();
	$prefixe = scoutsunite_photos_cookie( 0 );
	$prefixe = substr( $prefixe, 0, -1 );
	foreach ( array_keys( $_COOKIE ) as $nom ) { // phpcs:ignore WordPress.Security.NonceVerification -- catalogue fondé sur les sessions vérifiées, sans écriture.
		if ( 0 === strpos( $nom, $prefixe ) && ctype_digit( substr( $nom, strlen( $prefixe ) ) ) && count( $albums ) < 24 ) {
			$id    = absint( substr( $nom, strlen( $prefixe ) ) );
			$album = scoutsunite_photos_album( $id );
			if ( scoutsunite_photos_session_valide( $album, scoutsunite_photos_jeton( $id ) ) ) {
				$albums[] = $album;
			}
		}
	}
	return $albums;
}

/**
 * QR de partage sans cache persistant, rendu uniquement pour les animateurs autorisés.
 *
 * @param string $lien Lien complet.
 * @return string SVG construit à partir de la bibliothèque locale.
 */
function scoutsunite_photos_qr( $lien ) {
	if ( strlen( $lien ) > scoutsunite_qr_longueur_max() ) {
		return '';
	}
	$qr = new SU_QRCode();
	$qr->setErrorCorrectLevel( SU_QR_ERROR_CORRECT_LEVEL_M );
	$qr->addData( $lien );
	for ( $version = 1; $version <= SCOUTSUNITE_QR_VERSION_MAX; $version++ ) {
		if ( $qr->getData( 0 )->getLength() <= SU_QRUtil::getMaxLength( $version, SU_QR_MODE_8BIT_BYTE, SU_QR_ERROR_CORRECT_LEVEL_M ) ) {
			$qr->setTypeNumber( $version );
			break;
		}
	}
	$qr->make();
	$n = $qr->getModuleCount();
	$d = '';
	for ( $r = 0; $r < $n; $r++ ) {
		for ( $c = 0; $c < $n; $c++ ) {
			if ( $qr->isDark( $r, $c ) ) {
				$d .= 'M' . ( $c + 4 ) . ' ' . ( $r + 4 ) . 'h1v1h-1z';
			}
		}
	}
	return '<svg class="photos__qr" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . ( $n + 8 ) . ' ' . ( $n + 8 ) . '" role="img" aria-label="QR d’accès à cet album"><rect width="100%" height="100%" fill="white"/><path d="' . $d . '" fill="black"/></svg>';
}

/**
 * Page complète : ressources locales seulement, aucune extension de suivi via wp_head/wp_footer.
 *
 * @param object|null   $album Album demandé.
 * @param array|WP_Error|null $resultat Retour d'action.
 * @param bool          $demande_album Une adresse d'album était demandée, même inconnue.
 */
function scoutsunite_photos_page( $album, $resultat = null, $demande_album = false ) {
	$gestion = scoutsunite_photos_peut_gerer( $album );
	$lecture = $album && ( $gestion || scoutsunite_photos_session_valide( $album, scoutsunite_photos_jeton( $album->id ) ) );
	$stockage = scoutsunite_photos_dossier();
	if ( is_wp_error( $stockage ) ) {
		$lecture = false;
	}
	?>
	<!doctype html>
	<html lang="fr-BE"><head>
	<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow,noarchive">
	<title><?php echo esc_html( 'Photos de camp · ' . get_bloginfo( 'name' ) ); ?></title>
	<?php if ( scoutsunite_extension_theme_url( 'style.css' ) ) : ?>
		<link rel="stylesheet" href="<?php echo esc_url( scoutsunite_extension_theme_url( 'style.css' ) . '?photos=' . rawurlencode( scoutsunite_extension_version( 'assets/js/photos.js' ) ) ); ?>">
	<?php endif; ?>
	<script src="<?php echo esc_url( scoutsunite_extension_url( 'assets/js/photos.js' ) . '?ver=' . rawurlencode( scoutsunite_extension_version( 'assets/js/photos.js' ) ) ); ?>" defer></script>
	</head><body class="photos-page">
	<a class="photos__evitement" href="#contenu-photos">Aller au contenu</a>
	<header class="photos__entete"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a><span>Les souvenirs du camp</span></header>
	<main id="contenu-photos" class="photos__contenu">
	<nav class="photos__navigation" aria-label="Photos"><a href="<?php echo esc_url( scoutsunite_photos_url() ); ?>">Tous mes albums</a>
	<?php if ( current_user_can( 'scoutsunite_photos' ) ) : ?><a href="<?php echo esc_url( admin_url() ); ?>">Administration</a>
	<?php else : ?><a href="<?php echo esc_url( wp_login_url( scoutsunite_photos_url( $album ) ) ); ?>">Accès animateur</a><?php endif; ?></nav>
	<?php if ( $resultat ) : ?>
		<p class="photos__avis" role="status"><?php echo esc_html( is_wp_error( $resultat ) ? $resultat->get_error_message() : ( $resultat['message'] ?? '' ) ); ?></p>
	<?php endif; ?>
	<?php if ( is_wp_error( $stockage ) ) : ?>
		<h1>Photos de camp</h1><p class="photos__avis">L’espace privé des photos n’est pas disponible pour le moment.</p>
		<?php if ( current_user_can( 'scoutsunite_gerer_photos' ) ) : ?><p><?php echo esc_html( $stockage->get_error_message() ); ?></p><?php endif; ?>
	<?php elseif ( $demande_album && ! $lecture ) : ?>
		<h1>Photos de camp</h1><p>Retrouve les souvenirs avec le lien ou la clé reçue du staff.</p>
		<form method="post" class="photos__acces" id="photos-acces">
			<?php scoutsunite_photos_champs( 'ouvrir' ); ?>
			<label for="photos-cle">La clé reçue du staff</label>
			<input id="photos-cle" type="password" name="cle" autocomplete="off" spellcheck="false" required maxlength="36">
			<button type="submit">Ouvrir l’album</button>
		</form><p class="photos__aide">Ce lien est réservé aux familles du camp. Garde-le dans le cercle des parents concernés.</p>
	<?php elseif ( $lecture ) : ?>
		<div class="photos__titre"><p class="photos__section"><?php echo esc_html( ( scoutsunite_photos_sections()[ $album->section ] ?? '' ) . ' · ' . $album->annee ); ?></p><h1><?php echo esc_html( $album->titre ?: 'Souvenirs du camp' ); ?></h1></div>
		<?php if ( $gestion ) : ?>
			<p class="photos__aide">Album <?php echo esc_html( array( 'brouillon' => 'en brouillon', 'ouvert' => 'ouvert', 'archive' => 'archivé', 'ferme' => 'fermé' )[ $album->etat ] ?? '' ); ?> · <?php echo esc_html( size_format( scoutsunite_photos_poids( $album->id ) ) . ' / ' . size_format( scoutsunite_photos_quota() ) ); ?></p>
			<?php scoutsunite_photos_gestion_formulaire( $album ); ?>
			<?php if ( in_array( $album->etat, array( 'brouillon', 'ouvert' ), true ) ) : scoutsunite_photos_envoi_formulaire( $album ); endif; ?>
		<?php else : ?>
			<form method="post" id="photos-acces" hidden><?php scoutsunite_photos_champs( 'ouvrir', $album ); ?><input type="hidden" id="photos-cle" name="cle" value=""></form>
		<?php endif; ?>
		<?php scoutsunite_photos_galerie( $album, $gestion ); ?>
		<?php if ( ! $gestion ) : ?>
			<form method="post" class="photos__quitter"><?php scoutsunite_photos_champs( 'quitter', $album ); ?><button type="submit" class="photos__secondaire">Fermer</button><span class="photos__aide">Pour revenir, rouvre le lien reçu du staff.</span></form>
		<?php endif; ?>
	<?php else : ?>
		<h1>Photos de camp</h1><p>Les souvenirs, rangés par section et par année.</p>
		<?php scoutsunite_photos_catalogue_rendu(); ?>
		<?php if ( scoutsunite_photos_sections_permises() ) : ?>
		<details class="photos__reglages"><summary>Créer un album</summary><form method="post" class="photos__formulaire">
			<?php scoutsunite_photos_champs( 'creer' ); ?>
			<label for="photos-section">Section</label><select id="photos-section" name="section"><?php foreach ( scoutsunite_photos_sections_permises() as $slug => $nom ) : ?><option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $nom ); ?></option><?php endforeach; ?></select>
			<label for="photos-annee">Année du camp</label><input id="photos-annee" name="annee" type="number" min="2000" max="<?php echo (int) wp_date( 'Y' ) + 2; ?>" value="<?php echo (int) wp_date( 'Y' ); ?>" required>
			<label for="photos-titre">Nom de l’album</label><input id="photos-titre" name="titre" maxlength="180" placeholder="Grand camp, Week-end de printemps…" required><p class="photos__aide">Tu peux créer plusieurs albums pour une même section et une même année. Chacun a son propre lien parent.</p>
			<button type="submit">Créer l’album</button>
		</form></details>
		<?php endif; ?>
		<?php if ( current_user_can( 'scoutsunite_gerer_photos' ) ) : ?>
		<p class="photos__aide">Espace total : <?php echo esc_html( size_format( scoutsunite_photos_poids() ) . ' / ' . size_format( scoutsunite_photos_quota( true ) ) ); ?>. Les albums ne sont jamais supprimés automatiquement.</p>
		<?php endif; ?>
	<?php endif; ?>
	</main><footer class="photos__pied">Des souvenirs à partager entre familles.</footer>
	</body></html>
	<?php
}

/** Liste filtrable : les choix sont dérivés des seuls albums autorisés. */
function scoutsunite_photos_catalogue_rendu() {
	$albums = scoutsunite_photos_catalogue();
	if ( ! $albums ) {
		$message = current_user_can( 'scoutsunite_photos' )
			? ( scoutsunite_photos_sections_permises() ? 'Crée ton premier album avec ta section et l’année du camp.' : 'Demande à l’administrateur de rattacher ton compte à ta section.' )
			: 'Aucun album ouvert ici pour le moment. Ouvre le lien reçu du staff pour retrouver les photos de ton camp.';
		echo '<p class="photos__vide">' . esc_html( $message ) . '</p>';
		return;
	}
	$sections = array();
	$annees   = array();
	foreach ( $albums as $album ) {
		$sections[ $album->section ] = scoutsunite_photos_sections()[ $album->section ];
		$annees[ $album->annee ]     = $album->annee;
	}
	krsort( $annees );
	?>
	<div class="photos__filtres" id="photos-filtres"><label>Section <select id="photos-filtre-section"><option value="">Toutes mes sections</option><?php foreach ( $sections as $slug => $nom ) : ?><option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $nom ); ?></option><?php endforeach; ?></select></label>
	<label>Année <select id="photos-filtre-annee"><option value="">Toutes mes années</option><?php foreach ( $annees as $annee ) : ?><option value="<?php echo (int) $annee; ?>"><?php echo (int) $annee; ?></option><?php endforeach; ?></select></label></div>
	<ul class="photos__albums"><?php foreach ( $albums as $album ) : ?><li data-section="<?php echo esc_attr( $album->section ); ?>" data-annee="<?php echo (int) $album->annee; ?>"><a href="<?php echo esc_url( scoutsunite_photos_url( $album ) ); ?>"><strong><?php echo esc_html( scoutsunite_photos_titre( $album ) ); ?></strong><span>Voir les photos →</span></a></li><?php endforeach; ?></ul>
	<?php
}

/**
 * Envoi depuis la pellicule : données locales pour la file JavaScript sans dépendance externe.
 *
 * @param object $album Album autorisé.
 */
function scoutsunite_photos_envoi_formulaire( $album ) {
	$maximum = min( 20 * MB_IN_BYTES, wp_max_upload_size() );
	?>
	<section class="photos__envoi" id="photos-envoi" data-album="<?php echo (int) $album->id; ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'scoutsunite_photos' ) ); ?>" data-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-maximum="<?php echo (int) $maximum; ?>">
	<h2>Publier des photos</h2><p>Choisis les photos de ta pellicule, vérifie ta sélection et publie-la en une fois. Les parents les verront dès leur réception si l’accès à l’album est ouvert.</p>
	<label class="photos__choisir" for="photos-fichiers">Choisir des photos</label><input id="photos-fichiers" type="file" accept="image/jpeg,image/png,image/webp,image/heic,image/heif" multiple>
	<label class="photos__choisir photos__secondaire" for="photos-camera">Prendre une photo</label><input id="photos-camera" type="file" accept="image/*" capture="environment">
	<p class="photos__aide"><?php echo esc_html( size_format( $maximum ) ); ?> au plus par photo · 40 mégapixels et 12 000 pixels de côté au plus · JPEG, PNG, WebP. HEIC si le serveur le permet.</p>
	<label for="photos-legende">Un mot sur ce moment (facultatif)</label><input id="photos-legende" maxlength="180" placeholder="Le grand jeu">
	<label for="photos-tags">Tags de ces photos (facultatif)</label><input id="photos-tags" maxlength="330" list="photos-tags-existants" placeholder="Petit déjeuner, Grand jeu"><p class="photos__aide">Sépare les tags par des virgules. Une photo peut avoir plusieurs tags, jusqu’à huit.</p>
	<datalist id="photos-tags-existants"><?php foreach ( scoutsunite_photos_tags_album( $album, true ) as $tag ) : ?><option value="<?php echo esc_attr( $tag ); ?>"></option><?php endforeach; ?></datalist>
	<ol id="photos-file" class="photos__file" aria-label="Photos sélectionnées"></ol>
	<p id="photos-progression" role="status" aria-live="polite"></p><progress id="photos-progress" max="100" value="0" hidden aria-label="Progression de la photo en cours"></progress>
	<p class="photos__aide">Avant de confirmer, vérifie les autorisations et retire de ta sélection les photos que tu ne veux pas partager.</p>
	<div class="photos__actions"><button type="button" id="photos-envoyer" disabled>Publier les photos</button><button type="button" id="photos-arreter" class="photos__secondaire" hidden>Arrêter après cette photo</button><a id="photos-voir" href="<?php echo esc_url( scoutsunite_photos_url( $album ) ); ?>" hidden>Voir les photos de l’album →</a></div>
	<p class="photos__aide">Garde cette page ouverte pendant l’envoi. Si tu la fermes, les photos reçues sont conservées ; les autres devront être resélectionnées.</p>
	<noscript><p>L’envoi multiple demande JavaScript. Active-le pour choisir et envoyer les photos depuis ton téléphone.</p></noscript>
	</section>
	<?php
}

/**
 * Galerie paginée, relecture et visionneuse accessible avec un repli sans JavaScript.
 *
 * @param object $album Album.
 * @param bool   $gestion Autorisation de relecture.
 */
function scoutsunite_photos_galerie( $album, $gestion ) {
	global $wpdb;
	$modification = $gestion && ( current_user_can( 'scoutsunite_gerer_photos' ) || in_array( $album->etat, array( 'brouillon', 'ouvert' ), true ) );
	$page   = isset( $_GET['p'] ) && is_scalar( $_GET['p'] ) ? max( 1, min( 10000, absint( $_GET['p'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification -- pagination en lecture seule.
	$tag    = isset( $_GET['tag'] ) && is_string( $_GET['tag'] ) ? sanitize_title( wp_unslash( $_GET['tag'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- filtre de lecture, jamais une permission.
	$motif  = '%"' . $wpdb->esc_like( $tag ) . '":%';
	$statut = $gestion ? '' : 'publiee';
	$total  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i WHERE album = %d AND (%s = %s OR statut = %s) AND (%s = %s OR tags LIKE %s)', scoutsunite_photos_table( 'images' ), $album->id, $statut, '', $statut, $tag, '', $motif ) );
	$images = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE album = %d AND (%s = %s OR statut = %s) AND (%s = %s OR tags LIKE %s) ORDER BY id DESC LIMIT 24 OFFSET %d', scoutsunite_photos_table( 'images' ), $album->id, $statut, '', $statut, $tag, '', $motif, ( $page - 1 ) * 24 ) );
	$vue    = isset( $_GET['voir'] ) && is_scalar( $_GET['voir'] ) ? scoutsunite_photos_visionner( $album, absint( $_GET['voir'] ), $tag ) : null; // phpcs:ignore WordPress.Security.NonceVerification -- visionneuse soumise aux mêmes droits que les images.
	if ( $vue && ! is_wp_error( $vue ) ) {
		echo '<section class="photos__vue"><a href="' . esc_url( add_query_arg( array( 'p' => (int) ceil( $vue['position'] / 24 ), 'tag' => $tag ), scoutsunite_photos_url( $album ) ) ) . '">← Fermer la photo</a><p>Photo ' . (int) $vue['position'] . ' sur ' . (int) $vue['total'] . '</p><img src="' . esc_url( $vue['image'] ) . '" alt="' . esc_attr( $vue['legende'] ) . '"><nav class="photos__actions" aria-label="Parcourir les photos">';
		foreach ( array( 'precedente' => 'Précédente', 'suivante' => 'Suivante' ) as $sens => $libelle ) {
			if ( $vue[ $sens ] ) {
				echo '<a href="' . esc_url( add_query_arg( array( 'voir' => $vue[ $sens ], 'tag' => $tag ), scoutsunite_photos_url( $album ) ) ) . '">' . esc_html( $libelle ) . '</a>';
			}
		}
		echo '<a href="' . esc_url( $vue['telecharger'] ) . '">Télécharger cette photo</a></nav></section>';
	}
	?>
	<section class="photos__galerie"><h2><?php echo (int) $total; ?> photo<?php echo 1 === $total ? '' : 's'; ?> du camp</h2>
	<nav class="photos__tags" aria-label="Classer les photos par moment"><a href="<?php echo esc_url( scoutsunite_photos_url( $album ) ); ?>" <?php echo ! $tag ? 'aria-current="true"' : ''; ?>>Tous les moments</a><?php foreach ( scoutsunite_photos_tags_album( $album, $gestion ) as $cle_tag => $nom_tag ) : ?><a href="<?php echo esc_url( add_query_arg( 'tag', $cle_tag, scoutsunite_photos_url( $album ) ) ); ?>" <?php echo $tag === $cle_tag ? 'aria-current="true"' : ''; ?>><?php echo esc_html( $nom_tag ); ?></a><?php endforeach; ?></nav>
	<?php if ( ! $total ) : ?><p class="photos__vide">Les photos arrivent bientôt. Repasse après les nouvelles du staff.</p><?php endif; ?>
	<?php if ( $modification && $images ) : ?><form method="post"><?php wp_nonce_field( 'scoutsunite_photos', '_wpnonce', false ); ?><input type="hidden" name="album" value="<?php echo (int) $album->id; ?>"><label><input type="checkbox" id="photos-tout"> Sélectionner les photos de cette page</label><?php endif; ?>
	<div class="photos__grille"><?php foreach ( $images as $image ) :
		$url = scoutsunite_photos_url( $album );
		?>
		<figure class="photos__photo"><a class="photos__ouvrir" href="<?php echo esc_url( add_query_arg( array( 'voir' => $image->id, 'tag' => $tag ), $url ) ); ?>" data-photo="<?php echo (int) $image->id; ?>" data-image="<?php echo esc_url( add_query_arg( 'fichier', $image->id, $url ) ); ?>">
		<img loading="lazy" width="480" height="360" src="<?php echo esc_url( add_query_arg( array( 'fichier' => $image->id, 'format' => 'mini' ), $url ) ); ?>" alt="<?php echo esc_attr( $image->legende ?: 'Photo du camp, référence ' . $image->id ); ?>"></a>
		<figcaption><?php if ( $gestion ) : ?><div class="photos__photo-outils"><label><?php if ( $modification ) : ?><input type="checkbox" name="photos[]" value="<?php echo (int) $image->id; ?>"><?php endif; ?> Photo <?php echo (int) $image->id; ?> — <?php echo esc_html( array( 'attente' => 'À vérifier', 'publiee' => 'Publiée', 'retiree' => 'Retirée' )[ $image->statut ] ?? '' ); ?></label>
		<?php if ( $modification ) : ?><button type="submit" name="photo_supprimer" value="<?php echo (int) $image->id; ?>" class="photos__poubelle photos__secondaire" aria-label="Supprimer la photo <?php echo (int) $image->id; ?>" title="Supprimer cette photo" data-confirmer="Supprimer définitivement cette photo ?"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></svg></button><?php endif; ?></div><?php endif; ?>
		<?php if ( $image->legende ) : ?><span><?php echo esc_html( $image->legende ); ?></span><?php endif; ?><span><?php echo esc_html( implode( ' · ', array_values( scoutsunite_photos_tags_image( $image ) ) ) ); ?></span><span><?php echo esc_html( wp_date( 'j F Y', strtotime( $image->ajoute_le . ' UTC' ) ) ); ?></span></figcaption></figure>
	<?php endforeach; ?></div>
	<?php if ( $modification && $images ) : ?><div class="photos__actions"><button type="submit" name="operation" value="retirer" class="photos__secondaire">Retirer de l’album parent</button><button type="submit" name="operation" value="supprimer_photo" class="photos__secondaire" data-confirmer="Supprimer définitivement les photos sélectionnées ?">Supprimer la sélection</button></div><details class="photos__classement"><summary>Classer ou remettre en ligne la sélection</summary><label for="photos-tags-selection">Tags à ajouter</label><input id="photos-tags-selection" name="tags" maxlength="330" placeholder="Petit déjeuner, Dîner"><button type="submit" name="operation" value="taguer">Ajouter ces tags</button><button type="submit" name="operation" value="effacer_tags" class="photos__secondaire">Retirer tous les tags de la sélection</button><p>Pour rétablir des photos retirées ou publier d’anciennes photos « À vérifier » :</p><button type="submit" name="operation" value="publier">Remettre la sélection en ligne</button></details></form><?php endif; ?>
	<nav class="photos__pagination" aria-label="Pages de photos"><?php if ( $page > 1 ) : ?><a href="<?php echo esc_url( add_query_arg( array( 'p' => $page - 1, 'tag' => $tag ), scoutsunite_photos_url( $album ) ) ); ?>">← Photos précédentes</a><?php endif; ?><?php if ( $page * 24 < $total ) : ?><a href="<?php echo esc_url( add_query_arg( array( 'p' => $page + 1, 'tag' => $tag ), scoutsunite_photos_url( $album ) ) ); ?>">Photos suivantes →</a><?php endif; ?></nav>
	<?php if ( $gestion ) : ?><details class="photos__classement"><summary>Exporter pour préparer un livre photo</summary><p>Le ZIP contient les photos publiées<?php echo $tag ? ' de ce moment' : ''; ?> et leurs tags. Ce sont les versions optimisées, de 2 560 pixels au plus. Pour de grands tirages, garde les originaux.</p><form method="post"><?php scoutsunite_photos_champs( 'exporter', $album ); ?><input type="hidden" name="tag" value="<?php echo esc_attr( $tag ); ?>"><button type="submit">Exporter les photos publiées (ZIP)</button></form></details><?php endif; ?>
	</section>
	<dialog id="photos-visionneuse" class="photos__dialogue" tabindex="-1" aria-labelledby="photos-vue-titre" data-url="<?php echo esc_url( add_query_arg( 'tag', $tag, scoutsunite_photos_url( $album ) ) ); ?>"><div class="photos__vue-entete"><p id="photos-vue-titre">Photo du camp</p><button type="button" id="photos-fermer" autofocus>Fermer</button></div><img id="photos-vue-image" alt=""><p id="photos-vue-erreur" role="status"></p><div class="photos__actions"><button type="button" id="photos-precedente" disabled>Précédente</button><button type="button" id="photos-suivante" disabled>Suivante</button><a id="photos-telecharger">Télécharger cette photo</a></div></dialog>
	<?php
}

/**
 * Partage et accès de l'album : panneau visible de tous ses animateurs autorisés.
 * Le lien et le QR sont recalculés à chaque visite, après contrôle des droits.
 *
 * @param object $album Album.
 */
function scoutsunite_photos_gestion_formulaire( $album ) {
	if ( ! scoutsunite_photos_peut_gerer( $album ) ) {
		return;
	}
	$cle = scoutsunite_photos_cle_partage( $album );
	?>
	<details class="photos__partage" id="photos-partage"><summary>Partager l’album et gérer l’accès</summary>
	<?php if ( $cle ) :
		$lien = scoutsunite_photos_url( $album ) . '#cle=' . $cle;
		$message = 'Les photos de ' . scoutsunite_photos_titre( $album ) . ' : ' . $lien . "\nCe lien est réservé aux familles du camp. Merci de ne pas le diffuser ailleurs.";
		?>
		<p>Retrouve ici le même lien et son QR à chaque visite. Tu peux copier le message pour le transmettre aux parents.</p>
		<label for="photos-message">Message pour les parents</label><textarea id="photos-message" rows="4" readonly><?php echo esc_textarea( $message ); ?></textarea>
		<div class="photos__partage-outils"><div><button type="button" id="photos-copier">Copier le message pour les parents</button><p id="photos-copie" role="status"></p></div>
		<?php echo scoutsunite_photos_qr( $lien ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG construit localement, uniquement après vérification des droits. ?>
		</div>
	<?php elseif ( $album->cle_hash ) : ?>
		<p class="photos__avis">Ce lien a été créé avec une ancienne version. Pour retrouver ici son QR et le lien complet, renouvelle-le une fois, puis transmets le nouveau message aux parents.</p>
	<?php else : ?>
		<p>Crée le lien des parents ci-dessous. Tu retrouveras ensuite son QR et le message ici à chaque visite.</p>
	<?php endif; ?>
	<?php if ( ! scoutsunite_photos_album_ouvert( $album ) ) : ?><p class="photos__avis">L’accès parent est actuellement fermé ou expiré. Ouvre-le et vérifie sa date d’expiration pour permettre la consultation.</p><?php endif; ?>
	<form method="post" class="photos__formulaire"><?php scoutsunite_photos_champs( 'etat', $album ); ?>
		<label for="photos-etat">Accès à l’album</label><select id="photos-etat" name="etat">
		<?php if ( 'brouillon' === $album->etat ) : ?><option value="brouillon" selected>Fermé — ancien brouillon</option><?php endif; ?>
		<?php foreach ( array( 'ouvert' => 'Ouvert — les parents voient les photos', 'archive' => 'Archivé — lecture parent, envois fermés', 'ferme' => 'Fermé — animateurs et staff seulement' ) as $valeur => $nom ) : ?><option value="<?php echo esc_attr( $valeur ); ?>" <?php selected( $album->etat, $valeur ); ?>><?php echo esc_html( $nom ); ?></option><?php endforeach; ?></select>
		<p class="photos__aide">Fermer coupe les sessions des parents. Le même lien reste utilisable quand tu rouvres l’album.</p><button type="submit">Enregistrer l’accès</button>
	</form>
	<details class="photos__reglages"><summary>Renouveler le lien ou régler l’album</summary>
	<form method="post" class="photos__formulaire"><?php scoutsunite_photos_champs( 'cle', $album ); ?><input type="hidden" name="version" value="<?php echo (int) $album->version; ?>"><p>Renouveler le lien change la clé, désactive l’ancien lien et coupe les sessions déjà ouvertes. Transmets ensuite le nouveau message aux parents.</p><button type="submit" data-confirmer="Renouveler le lien et couper tous les anciens accès parents à cet album ?"><?php echo $album->cle_hash ? 'Renouveler le lien des parents' : 'Créer le lien des parents'; ?></button></form>
	<form method="post" class="photos__formulaire"><?php scoutsunite_photos_champs( 'regler', $album ); ?><label for="photos-titre-modifier">Nom de l’album</label><input id="photos-titre-modifier" name="titre" maxlength="180" required value="<?php echo esc_attr( $album->titre ?: 'Camp ' . $album->annee ); ?>"><label for="photos-expiration">Dernier jour d’accès des parents (facultatif)</label><input id="photos-expiration" name="expiration" type="date" value="<?php echo $album->expiration ? esc_attr( wp_date( 'Y-m-d', (int) $album->expiration - 1 ) ) : ''; ?>"><p class="photos__aide">Une date ferme l’accès après cette journée. Les photos restent conservées jusqu’à leur suppression par le staff.</p><button type="submit">Enregistrer les réglages</button></form>
	</details></details>
	<?php if ( current_user_can( 'scoutsunite_gerer_photos' ) ) : ?>
	<details class="photos__suppression"><summary>Supprimer l’album</summary>
	<form method="post" class="photos__formulaire"><?php scoutsunite_photos_champs( 'supprimer_album', $album ); ?><label for="photos-supprimer">Pour supprimer définitivement cet album et toutes ses photos, écris SUPPRIMER</label><input id="photos-supprimer" name="confirmation" autocomplete="off" required pattern="SUPPRIMER"><button type="submit" data-confirmer="Supprimer définitivement cet album et toutes ses photos ?">Supprimer l’album</button></form>
	</details><?php endif; ?>
	<?php
}
