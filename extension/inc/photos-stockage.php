<?php
/**
 * Stockage des photos de camp : dossier hors racine web, images réencodées sans métadonnées,
 * quotas vérifiés sous verrou et chemins indépendants des noms envoyés par les téléphones.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Un chemin est-il dans un dossier, après résolution des liens symboliques ?
 *
 * @param string $chemin Chemin canonique.
 * @param string $racine Racine canonique.
 * @return bool
 */
function scoutsunite_photos_dans_dossier( $chemin, $racine ) {
	$chemin = untrailingslashit( wp_normalize_path( $chemin ) );
	$racine = untrailingslashit( wp_normalize_path( $racine ) );
	return $chemin === $racine || 0 === strpos( $chemin . '/', $racine . '/' );
}

/**
 * Dossier privé. Aucun repli silencieux vers uploads ou vers le dossier de l'extension.
 * SCOUTSUNITE_PHOTOS_DOSSIER permet à l'hébergeur de préciser un chemin hors racine web.
 *
 * @return string|WP_Error
 */
function scoutsunite_photos_dossier() {
	static $memo = array();
	$chemin = defined( 'SCOUTSUNITE_PHOTOS_DOSSIER' ) ? SCOUTSUNITE_PHOTOS_DOSSIER : dirname( untrailingslashit( ABSPATH ) ) . '/scoutsunite-prive-' . substr( hash( 'sha256', home_url() ), 0, 12 );
	if ( ! is_string( $chemin ) || ! path_is_absolute( $chemin ) || false !== strpos( $chemin, '://' ) ) {
		return new WP_Error( 'stockage', 'Le dossier privé des photos doit être un chemin absolu sur le serveur.' );
	}
	// Mémorisé pendant la requête : chaque photo demande ce chemin et les contrôles coûtent des accès disque.
	$cle_memo = $chemin . '|' . ( isset( $_SERVER['DOCUMENT_ROOT'] ) ? $_SERVER['DOCUMENT_ROOT'] : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- simple clé de mémoire.
	if ( isset( $memo[ $cle_memo ] ) ) {
		return $memo[ $cle_memo ];
	}
	// Vérifier les ancêtres avant toute création, puis le chemin réel créé : un lien ne contourne pas la règle.
	$ancetre = $chemin;
	while ( ! file_exists( $ancetre ) && dirname( $ancetre ) !== $ancetre ) {
		$ancetre = dirname( $ancetre );
	}
	$racines = array( realpath( ABSPATH ), realpath( WP_CONTENT_DIR ) );
	$racine_web = ! empty( $_SERVER['DOCUMENT_ROOT'] ) ? realpath( sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) ) : false;
	// Hors requête web (WP-CLI, cron système), DOCUMENT_ROOT n'existe pas : le dossier a déjà été validé
	// par les requêtes web, et les racines de WordPress restent contrôlées. Sans cela, le nettoyage ne tournerait jamais.
	$hors_web = ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron();
	if ( ! $racine_web && ! $hors_web ) {
		return new WP_Error( 'stockage_inconnu', 'Le serveur ne permet pas de vérifier la racine web. L’hébergeur doit renseigner DOCUMENT_ROOT avant d’ouvrir l’espace privé.' );
	}
	if ( $racine_web ) {
		$racines[] = $racine_web;
	}
	foreach ( array_filter( $racines ) as $racine ) {
		if ( scoutsunite_photos_dans_dossier( (string) realpath( $ancetre ), $racine ) ) {
			return new WP_Error( 'stockage_public', 'Le dossier des photos est dans un emplacement public. L’administrateur doit configurer SCOUTSUNITE_PHOTOS_DOSSIER hors de la racine web.' );
		}
	}
	$nouveau = ! is_dir( $chemin );
	if ( $nouveau && ! wp_mkdir_p( $chemin ) ) {
		return new WP_Error( 'stockage', 'Le dossier privé ne peut pas être créé. Demande à l’administrateur de configurer SCOUTSUNITE_PHOTOS_DOSSIER hors de la racine web.' );
	}
	$reel = realpath( $chemin );
	foreach ( array_filter( $racines ) as $racine ) {
		if ( scoutsunite_photos_dans_dossier( (string) $reel, $racine ) ) {
			return new WP_Error( 'stockage_public', 'Le stockage privé n’est pas assuré. Les envois et la lecture parent sont bloqués.' );
		}
	}
	if ( ! $reel || ! is_writable( $reel ) ) {
		return new WP_Error( 'stockage', 'Le dossier privé des photos n’est pas accessible en écriture.' );
	}
	if ( $nouveau ) {
		chmod( $reel, 0700 );
	}
	$memo[ $cle_memo ] = $reel;
	return $reel;
}

/**
 * Verrou atomique en base : protège aussi les hôtes où flock n'est pas implémenté.
 * La valeur n'est pas lue via le cache d'options ; l'unicité SQL arbitre les processus concurrents.
 * Un verrou abandonné après arrêt de PHP expire après dix minutes.
 *
 * @return string|WP_Error Jeton propriétaire, à libérer dans un finally.
 */
function scoutsunite_photos_verrou() {
	global $wpdb;
	$dossier = scoutsunite_photos_dossier();
	if ( is_wp_error( $dossier ) ) {
		return $dossier;
	}
	$nom    = 'scoutsunite_photos_verrou';
	$ancien = $wpdb->get_var( $wpdb->prepare( 'SELECT option_value FROM %i WHERE option_name = %s', $wpdb->options, $nom ) );
	if ( $ancien && (int) $ancien < time() - 10 * MINUTE_IN_SECONDS ) {
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE option_name = %s AND option_value = %s', $wpdb->options, $nom, $ancien ) );
	}
	$jeton = time() . ':' . bin2hex( random_bytes( 16 ) );
	$cree  = $wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO %i (option_name, option_value, autoload) VALUES (%s, %s, %s)', $wpdb->options, $nom, $jeton, 'no' ) );
	if ( 1 !== $cree ) {
		return new WP_Error( 'occupe', 'Un envoi est en cours. Réessaie dans quelques secondes.', array( 'status' => 409 ) );
	}
	return $jeton;
}

/**
 * Libère seulement son propre verrou, jamais celui d'un autre envoi.
 *
 * @param string $jeton Jeton reçu de scoutsunite_photos_verrou().
 */
function scoutsunite_photos_deverrouiller( $jeton ) {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE option_name = %s AND option_value = %s', $wpdb->options, 'scoutsunite_photos_verrou', $jeton ) );
}

/**
 * Une image privée par identifiant.
 *
 * @param int $id Identifiant.
 * @return object|null
 */
function scoutsunite_photos_image( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', scoutsunite_photos_table( 'images' ), absint( $id ) ) );
}

/**
 * Chemin vérifié d'une variante ; le navigateur ne choisit jamais un chemin ou un nom.
 *
 * @param object $image Image.
 * @param bool   $mini Miniature.
 * @return string
 */
function scoutsunite_photos_chemin( $image, $mini = false ) {
	$dossier = scoutsunite_photos_dossier();
	if ( is_wp_error( $dossier ) || ! $image || ! preg_match( '/^[a-f0-9]{32}$/D', $image->nom ) ) {
		return '';
	}
	$chemin = $dossier . '/' . absint( $image->album ) . '/' . $image->nom . ( $mini ? '-mini' : '' ) . '.jpg';
	$reel   = realpath( $chemin );
	return $reel && scoutsunite_photos_dans_dossier( $reel, $dossier ) && is_file( $reel ) && is_readable( $reel ) ? $reel : '';
}

/**
 * Somme des deux variantes, pour un album ou pour tout le site.
 *
 * @param int $album Album, 0 pour tout le site.
 * @return int
 */
function scoutsunite_photos_poids( $album = 0 ) {
	global $wpdb;
	if ( $album ) {
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(taille), 0) FROM %i WHERE album = %d', scoutsunite_photos_table( 'images' ), $album ) );
	}
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(taille), 0) FROM %i', scoutsunite_photos_table( 'images' ) ) );
}

/**
 * Quotas administrables dans wp-config.php ; ils ne sont pas les objectifs de volume de l'EPIC.
 *
 * @param bool $global Quota de tout le site.
 * @return int Octets.
 */
function scoutsunite_photos_quota( $global = false ) {
	if ( $global ) {
		return defined( 'SCOUTSUNITE_PHOTOS_QUOTA_TOTAL' ) ? max( 1, (int) SCOUTSUNITE_PHOTOS_QUOTA_TOTAL ) : 5 * GB_IN_BYTES;
	}
	return defined( 'SCOUTSUNITE_PHOTOS_QUOTA_ALBUM' ) ? max( 1, (int) SCOUTSUNITE_PHOTOS_QUOTA_ALBUM ) : GB_IN_BYTES;
}

/**
 * Supprime explicitement EXIF, IPTC, XMP et GPS, même si Imagick les préserve dans WordPress.
 *
 * @param string $chemin JPEG réencodé par l'éditeur.
 * @return bool
 */
function scoutsunite_photos_sans_metadonnees( $chemin ) {
	try {
		if ( class_exists( 'Imagick' ) ) {
			$image = new Imagick( $chemin );
			$image->stripImage();
			$ok = $image->writeImage( $chemin );
			$image->clear();
			return $ok;
		}
		if ( function_exists( 'imagecreatefromjpeg' ) ) {
			$image = imagecreatefromjpeg( $chemin );
			if ( ! $image ) {
				return false;
			}
			$ok = imagejpeg( $image, $chemin, 85 );
			imagedestroy( $image );
			return $ok;
		}
	} catch ( Exception $e ) {
		return false;
	}
	return false;
}

/**
 * Conversion locale d'un HEIC, y compris sous WordPress 6.6 si Imagick/libheif est disponible.
 * Aucun appel à un convertisseur externe.
 *
 * @param string $source Source temporaire PHP.
 * @param string $cible JPEG privé temporaire.
 * @return true|WP_Error
 */
function scoutsunite_photos_convertir_heic( $source, $cible ) {
	if ( ! class_exists( 'Imagick' ) || ! Imagick::queryFormats( 'HEIC' ) ) {
		return new WP_Error( 'heic', 'Ce serveur ne lit pas encore les photos HEIC. Choisis la version JPEG proposée par ton téléphone, ou demande au staff d’activer la conversion HEIC chez l’hébergeur.' );
	}
	try {
		$image = new Imagick();
		$image->setResourceLimit( Imagick::RESOURCETYPE_THREAD, 1 );
		$image->pingImage( $source );
		if ( ! in_array( strtoupper( $image->getImageFormat() ), array( 'HEIC', 'HEIF' ), true ) || $image->getImageWidth() * $image->getImageHeight() > 40000000 ) {
			$image->clear();
			return new WP_Error( 'format', 'Choisis une photo de moins de 40 mégapixels.' );
		}
		$image->clear();
		$image->readImage( $source . '[0]' );
		$image->setIteratorIndex( 0 );
		if ( method_exists( $image, 'autoOrientImage' ) ) {
			$image->autoOrientImage();
		}
		$image->thumbnailImage( 2560, 2560, true, true );
		$image->setImageFormat( 'jpeg' );
		$image->setImageCompressionQuality( 85 );
		$image->stripImage();
		$ok = $image->writeImage( $cible );
		$image->clear();
		return $ok ? true : new WP_Error( 'heic', 'La conversion de cette photo a échoué. Réessaie avec sa version JPEG.' );
	} catch ( Exception $e ) {
		return new WP_Error( 'heic', 'La conversion de cette photo a échoué. Réessaie avec sa version JPEG.' );
	}
}

/**
 * Importe une source locale validée par l'entrée HTTP (ou un fichier de test).
 * Appel sous le verrou de scoutsunite_photos_executer ; cette fonction ne reçoit aucun chemin HTTP.
 *
 * @param object $album Album.
 * @param string $source Fichier local.
 * @param string $legende Libellé facultatif.
 * @param array  $tags Tags assainis.
 * @return array|WP_Error
 */
function scoutsunite_photos_importer( $album, $source, $legende = '', $tags = array() ) {
	global $wpdb;
	if ( ! scoutsunite_photos_peut_gerer( $album ) || ! in_array( $album->etat, array( 'brouillon', 'ouvert' ), true ) ) {
		return new WP_Error( 'interdit', 'Tu ne peux pas ajouter de photo à cet album.' );
	}
	if ( ! is_file( $source ) || filesize( $source ) > min( 20 * MB_IN_BYTES, wp_max_upload_size() ) || 0 === filesize( $source ) ) {
		return new WP_Error( 'taille', 'La photo dépasse la taille permise ou elle est vide.' );
	}
	$empreinte = hash_file( 'sha256', $source );
	$doublon   = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE album = %d AND empreinte = %s', scoutsunite_photos_table( 'images' ), $album->id, $empreinte ) );
	if ( $doublon ) {
		$image = scoutsunite_photos_image( $doublon );
		if ( 'retiree' === $image->statut ) {
			return new WP_Error( 'retiree', 'Cette photo a été retirée de l’album. Elle n’a pas été republiée. Utilise « Remettre la sélection en ligne » si tu souhaites la rétablir.' );
		}
		if ( 'attente' === $image->statut ) {
			$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'statut' => 'publiee' ), array( 'id' => $doublon ), array( '%s' ), array( '%d' ) );
			scoutsunite_photos_journal( $album->id, 'publier', $doublon );
		}
		return array( 'album' => (int) $album->id, 'photo' => (int) $doublon, 'message' => 'Cette photo est déjà dans l’album.', 'doublon' => true );
	}
	$dossier = scoutsunite_photos_dossier();
	if ( is_wp_error( $dossier ) ) {
		return $dossier;
	}
	$dossier .= '/' . absint( $album->id );
	if ( ! is_dir( $dossier ) ) {
		if ( ! wp_mkdir_p( $dossier ) ) {
			return new WP_Error( 'stockage', 'Le dossier de cet album n’est pas accessible.' );
		}
		chmod( $dossier, 0700 );
	}
	if ( is_link( $dossier ) || ! scoutsunite_photos_dans_dossier( (string) realpath( $dossier ), dirname( $dossier ) ) ) {
		return new WP_Error( 'stockage_public', 'Le dossier de cet album n’est pas privé. Contacte l’administrateur.' );
	}
	$nom         = bin2hex( random_bytes( 16 ) );
	$grande      = $dossier . '/' . $nom . '.jpg';
	$mini        = $dossier . '/' . $nom . '-mini.jpg';
	$conversion  = $dossier . '/' . $nom . '-source.jpg';
	$enregistre  = false;
	try {
		$type = wp_get_image_mime( $source );
		if ( in_array( $type, array( 'image/heic', 'image/heif' ), true ) || ( ! $type && false !== strpos( (string) file_get_contents( $source, false, null, 0, 32 ), 'ftyp' ) ) ) {
			$convertie = scoutsunite_photos_convertir_heic( $source, $conversion );
			if ( is_wp_error( $convertie ) ) {
				return $convertie;
			}
			$source = $conversion;
			$type   = 'image/jpeg';
		}
		if ( ! in_array( $type, array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) {
			return new WP_Error( 'format', 'Choisis une photo JPEG, PNG, WebP ou HEIC compatible.' );
		}
		$dimensions = wp_getimagesize( $source );
		if ( ! $dimensions || $dimensions[0] * $dimensions[1] > 40000000 || max( $dimensions[0], $dimensions[1] ) > 12000 ) {
			return new WP_Error( 'dimensions', 'Choisis une photo de moins de 40 mégapixels et 12 000 pixels de côté.' );
		}
		$editeur = wp_get_image_editor( $source );
		if ( is_wp_error( $editeur ) ) {
			return new WP_Error( 'lecture', 'Le serveur ne sait pas lire cette photo. Essaie sa version JPEG.' );
		}
		$rotation = $editeur->maybe_exif_rotate();
		if ( is_wp_error( $rotation ) || ( max( $dimensions[0], $dimensions[1] ) > 2560 && is_wp_error( $editeur->resize( 2560, 2560, false ) ) ) ) {
			return new WP_Error( 'conversion', 'La photo n’a pas pu être préparée.' );
		}
		$editeur->set_quality( 85 );
		$resultat = $editeur->save( $grande, 'image/jpeg' );
		if ( is_wp_error( $resultat ) || ! scoutsunite_photos_sans_metadonnees( $grande ) ) {
			return new WP_Error( 'conversion', 'La photo n’a pas pu être enregistrée sans ses métadonnées.' );
		}
		$editeur = wp_get_image_editor( $grande );
		if ( is_wp_error( $editeur ) || ( max( $resultat['width'], $resultat['height'] ) > 480 && is_wp_error( $editeur->resize( 480, 480, false ) ) ) || is_wp_error( $editeur->save( $mini, 'image/jpeg' ) ) || ! scoutsunite_photos_sans_metadonnees( $mini ) ) {
			return new WP_Error( 'conversion', 'La miniature n’a pas pu être préparée.' );
		}
		$taille = filesize( $grande ) + filesize( $mini );
		chmod( $grande, 0600 );
		chmod( $mini, 0600 );
		if ( scoutsunite_photos_poids( $album->id ) + $taille > scoutsunite_photos_quota() || scoutsunite_photos_poids() + $taille > scoutsunite_photos_quota( true ) ) {
			return new WP_Error( 'quota', 'L’espace prévu pour les photos est plein. Demande au staff de libérer de la place.' );
		}
		$cree = $wpdb->insert( scoutsunite_photos_table( 'images' ), array( 'album' => $album->id, 'nom' => $nom, 'empreinte' => $empreinte, 'statut' => 'publiee', 'legende' => wp_html_excerpt( sanitize_text_field( $legende ), 180, '' ), 'tags' => scoutsunite_photos_tags_json( $tags ), 'taille' => $taille, 'largeur' => $resultat['width'], 'hauteur' => $resultat['height'], 'auteur' => get_current_user_id(), 'ajoute_le' => gmdate( 'Y-m-d H:i:s' ) ), array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s' ) );
		if ( ! $cree ) {
			return new WP_Error( 'base', 'La photo n’a pas été enregistrée. Réessaie.' );
		}
		$id          = (int) $wpdb->insert_id;
		$enregistre  = true;
		scoutsunite_photos_journal( $album->id, 'publier', $id );
		return array( 'album' => (int) $album->id, 'photo' => $id, 'message' => 'Photo publiée dans l’album.' );
	} finally {
		foreach ( $enregistre ? array( $conversion ) : array( $grande, $mini, $conversion ) as $fichier ) {
			if ( is_file( $fichier ) ) {
				wp_delete_file( $fichier );
			}
		}
	}
}

/**
 * Retire d'abord l'image de la lecture parent, puis efface ses variantes.
 *
 * @param object $image Image.
 * @return bool
 */
function scoutsunite_photos_effacer_fichiers( $image ) {
	global $wpdb;
	$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'statut' => 'retiree' ), array( 'id' => $image->id ), array( '%s' ), array( '%d' ) );
	foreach ( array( false, true ) as $mini ) {
		$chemin = scoutsunite_photos_chemin( $image, $mini );
		if ( $chemin ) {
			wp_delete_file( $chemin );
			if ( is_file( $chemin ) ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * Prépare un export privé des photos publiées, réservé aux animateurs de la section et au staff.
 *
 * @param object $album Album.
 * @param string $tag Tag facultatif, identifiant déjà assaini.
 * @param array|null $selection Références explicites pour l'export parent ; null reste réservé au staff.
 * @return string|WP_Error Chemin d'un ZIP temporaire à effacer après le téléchargement.
 */
function scoutsunite_photos_exporter( $album, $tag = '', $selection = null ) {
	global $wpdb;
	if ( null === $selection ? ! scoutsunite_photos_peut_gerer( $album ) : ! scoutsunite_photos_selection_autorisee( $album ) ) {
		return new WP_Error( 'interdit', 'L’export est réservé aux animateurs de la section et au staff.', array( 'status' => 403 ) );
	}
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'zip', 'L’hébergeur doit activer l’extension PHP ZIP pour préparer l’export.' );
	}
	$verrou = scoutsunite_photos_verrou();
	if ( is_wp_error( $verrou ) ) {
		return $verrou;
	}
	$chemin = '';
	$pret   = false;
	try {
		$album = scoutsunite_photos_album( $album->id );
		if ( null === $selection && ! scoutsunite_photos_peut_gerer( $album ) ) {
			return new WP_Error( 'interdit', 'Tu n’as plus accès à cet album.' );
		}
		$images = null === $selection ? $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE album = %d AND statut = %s ORDER BY id', scoutsunite_photos_table( 'images' ), $album->id, 'publiee' ) ) : scoutsunite_photos_selection_images( $album, $selection );
		if ( is_wp_error( $images ) ) {
			return $images;
		}
		if ( $tag && null === $selection ) {
			$images = array_values( array_filter( $images, function ( $image ) use ( $tag ) { return isset( scoutsunite_photos_tags_image( $image )[ $tag ] ); } ) );
		}
		if ( ! $images ) {
			return new WP_Error( 'vide', 'Publie au moins une photo dans cette sélection avant de l’exporter.' );
		}
		if ( null !== $selection && ! scoutsunite_photos_peut_gerer( $album ) ) {
			$limite = 'su_photos_exports_' . scoutsunite_photos_selection_parent( $album );
			$nombre = (int) get_transient( $limite );
			if ( $nombre >= 5 ) {
				return new WP_Error( 'essais', 'Plusieurs exports viennent d’être préparés. Attends dix minutes avant de recommencer.' );
			}
			set_transient( $limite, $nombre + 1, 10 * MINUTE_IN_SECONDS );
		}
		$dossier = scoutsunite_photos_dossier();
		if ( is_wp_error( $dossier ) ) {
			return $dossier;
		}
		$chemin  = $dossier . '/export-' . bin2hex( random_bytes( 16 ) ) . '.zip';
		$archive = new ZipArchive();
		if ( true !== $archive->open( $chemin, ZipArchive::CREATE | ZipArchive::EXCL ) ) {
			return new WP_Error( 'zip', 'L’export n’a pas pu être préparé. Vérifie l’espace disponible.' );
		}
		$index = array();
		foreach ( $images as $image ) {
			$fichier = scoutsunite_photos_chemin( $image );
			$nom = 'photo-' . str_pad( (string) $image->id, 6, '0', STR_PAD_LEFT ) . '.jpg';
			if ( ! $fichier || ! $archive->addFile( $fichier, $nom ) ) {
				$archive->close();
				return new WP_Error( 'zip', 'Une photo n’est plus disponible. Recharge l’album avant de réessayer.' );
			}
			$archive->setCompressionName( $nom, ZipArchive::CM_STORE );
			$index[] = array( 'fichier' => $nom, 'reference' => (int) $image->id, 'legende' => $image->legende, 'tags' => array_values( scoutsunite_photos_tags_image( $image ) ), 'ajoute_le' => $image->ajoute_le );
		}
		$archive->addFromString( 'photos.json', wp_json_encode( array( 'section' => $album->section, 'annee' => (int) $album->annee, 'version' => 'Photos optimisées sans métadonnées, 2560 pixels au plus.', 'photos' => $index ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		if ( ! $archive->close() ) {
			return new WP_Error( 'zip', 'L’export n’a pas pu être terminé. Réessaie.' );
		}
		chmod( $chemin, 0600 );
		$pret = true;
		scoutsunite_photos_journal( $album->id, 'exporter' );
		return $chemin;
	} finally {
		if ( ! $pret && $chemin && is_file( $chemin ) ) {
			wp_delete_file( $chemin );
		}
		scoutsunite_photos_deverrouiller( $verrou );
	}
}

/** Exports et variantes abandonnés après arrêt de PHP : nettoyage différé, toujours hors racine web. */
function scoutsunite_photos_nettoyer_fichiers() {
	global $wpdb;
	$verrou = scoutsunite_photos_verrou();
	if ( is_wp_error( $verrou ) ) {
		return;
	}
	try {
		$dossier = scoutsunite_photos_dossier();
		foreach ( glob( $dossier . '/export-*.zip' ) ?: array() as $fichier ) {
			if ( ! is_link( $fichier ) && filemtime( $fichier ) < time() - DAY_IN_SECONDS ) {
				wp_delete_file( $fichier );
			}
		}
		foreach ( glob( $dossier . '/*', GLOB_ONLYDIR ) ?: array() as $sous_dossier ) {
			if ( ! ctype_digit( basename( $sous_dossier ) ) || is_link( $sous_dossier ) ) {
				continue;
			}
			foreach ( glob( $sous_dossier . '/*.jpg' ) ?: array() as $fichier ) {
				if ( ! is_link( $fichier ) && filemtime( $fichier ) < time() - DAY_IN_SECONDS && preg_match( '/^([a-f0-9]{32})(-mini|-source)?\.jpg$/D', basename( $fichier ), $nom ) ) {
					$existe = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE album = %d AND nom = %s', scoutsunite_photos_table( 'images' ), absint( basename( $sous_dossier ) ), $nom[1] ) );
					if ( ! $existe || ( isset( $nom[2] ) && '-source' === $nom[2] ) ) {
						wp_delete_file( $fichier );
					}
				}
			}
		}
	} finally {
		scoutsunite_photos_deverrouiller( $verrou );
	}
}
