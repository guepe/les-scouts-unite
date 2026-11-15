<?php
/**
 * Photos de camp : albums par section et année, droits, clés renouvelables et sessions parents.
 * Les données vivent dans des tables privées : ni pièces jointes, ni routes REST publiques.
 * Le stockage et l'interface sont séparés pour rendre leurs contrôles lisibles.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/photos-stockage.php';
require_once __DIR__ . '/photos-interface.php';

/** Version du schéma, indépendante de celle des rôles. */
const SCOUTSUNITE_PHOTOS_SCHEMA = 3;

/**
 * Nom d'une table du module. Aucune valeur reçue du navigateur ne construit un identifiant SQL.
 *
 * @param string $nom Albums, images, sessions ou journal.
 * @return string
 */
function scoutsunite_photos_table( $nom ) {
	global $wpdb;
	return $wpdb->prefix . 'scoutsunite_photos_' . $nom;
}

/** Installe les tables et l'adresse, y compris sur un site déjà en service. */
function scoutsunite_photos_initialiser() {
	global $wpdb;
	add_rewrite_rule( '^photos/album/([a-f0-9]{24})/?$', 'index.php?su_photos=1&su_album=$matches[1]', 'top' );
	add_rewrite_rule( '^photos/?$', 'index.php?su_photos=1', 'top' );
	if ( SCOUTSUNITE_PHOTOS_SCHEMA !== (int) get_option( 'scoutsunite_photos_schema' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$collation = $wpdb->get_charset_collate();
		$albums    = scoutsunite_photos_table( 'albums' );
		$images    = scoutsunite_photos_table( 'images' );
		$sessions  = scoutsunite_photos_table( 'sessions' );
		$journal   = scoutsunite_photos_table( 'journal' );
		dbDelta( "CREATE TABLE $albums (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			adresse char(24) NOT NULL,
			section varchar(24) NOT NULL,
			annee smallint(5) unsigned NOT NULL,
			titre varchar(180) NOT NULL DEFAULT '',
			etat varchar(12) NOT NULL DEFAULT 'ouvert',
			cle_hash char(64) NOT NULL DEFAULT '',
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			expiration bigint(20) unsigned NOT NULL DEFAULT 0,
			reunion bigint(20) unsigned NOT NULL DEFAULT 0,
			auteur bigint(20) unsigned NOT NULL,
			cree_le datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY adresse (adresse),
			UNIQUE KEY section_annee (section,annee)
		) $collation;" );
		dbDelta( "CREATE TABLE $images (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			album bigint(20) unsigned NOT NULL,
			nom char(32) NOT NULL,
			empreinte char(64) NOT NULL,
			statut varchar(12) NOT NULL DEFAULT 'publiee',
			legende varchar(180) NOT NULL DEFAULT '',
			tags text,
			taille bigint(20) unsigned NOT NULL,
			largeur int(10) unsigned NOT NULL,
			hauteur int(10) unsigned NOT NULL,
			auteur bigint(20) unsigned NOT NULL,
			ajoute_le datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY album_empreinte (album,empreinte),
			KEY album_statut (album,statut,id)
		) $collation;" );
		dbDelta( "CREATE TABLE $sessions (
			empreinte char(64) NOT NULL,
			album bigint(20) unsigned NOT NULL,
			version bigint(20) unsigned NOT NULL,
			expiration bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (empreinte),
			KEY album (album),
			KEY expiration (expiration)
		) $collation;" );
		dbDelta( "CREATE TABLE $journal (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			album bigint(20) unsigned NOT NULL,
			photo bigint(20) unsigned NOT NULL DEFAULT 0,
			auteur bigint(20) unsigned NOT NULL,
			operation varchar(24) NOT NULL,
			date_action datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY album (album)
		) $collation;" );
		update_option( 'scoutsunite_photos_schema', SCOUTSUNITE_PHOTOS_SCHEMA, false );
		update_option( 'scoutsunite_agenda_flush', 1 );
	}
	if ( ! wp_next_scheduled( 'scoutsunite_photos_nettoyage' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'scoutsunite_photos_nettoyage' );
	}
	register_block_type( 'scoutsunite/photos', array( 'api_version' => 3, 'editor_script' => 'scoutsunite-blocs', 'render_callback' => 'scoutsunite_photos_bloc' ) );
}
add_action( 'init', 'scoutsunite_photos_initialiser', 5 );
add_filter( 'query_vars', function ( $variables ) {
	return array_merge( $variables, array( 'su_photos', 'su_album' ) );
} );

/**
 * Sections d'âge : la source reste celle de l'extension.
 *
 * @return array
 */
function scoutsunite_photos_sections() {
	return array_diff_key( scoutsunite_agenda_sections(), array( 'unite' => '', 'iama' => '' ) );
}

/**
 * Sections que la personne connectée peut gérer.
 *
 * @return array
 */
function scoutsunite_photos_sections_permises() {
	if ( ! current_user_can( 'scoutsunite_photos' ) ) {
		return array();
	}
	$sections = scoutsunite_photos_sections();
	return current_user_can( 'scoutsunite_gerer_photos' ) ? $sections : array_intersect_key( $sections, array_flip( scoutsunite_droits_sections() ) );
}

/**
 * Lit un album privé par identifiant ou adresse publique (qui n'est pas un secret).
 *
 * @param int|string $identifiant Identifiant numérique ou adresse de 24 caractères.
 * @return object|null
 */
function scoutsunite_photos_album( $identifiant ) {
	global $wpdb;
	if ( is_string( $identifiant ) && preg_match( '/^[a-f0-9]{24}$/D', $identifiant ) ) {
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE adresse = %s', scoutsunite_photos_table( 'albums' ), $identifiant ) );
	}
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', scoutsunite_photos_table( 'albums' ), absint( $identifiant ) ) );
}

/**
 * Droit de gestion d'un album ; indépendant de la clé parent et de upload_files.
 *
 * @param object|null $album Album.
 * @return bool
 */
function scoutsunite_photos_peut_gerer( $album ) {
	return $album && isset( scoutsunite_photos_sections_permises()[ $album->section ] );
}

/**
 * Clé de 144 bits dérivée d'un secret WordPress et de la version d'accès de cet album.
 * On peut ainsi retrouver le même QR sans enregistrer de clé en clair ni ajouter de bibliothèque.
 * Le domaine sépare cet usage des autres HMAC du site. Renouveler incrémente la version.
 *
 * @param string $adresse Adresse unique de l'album.
 * @param int    $version Version de sa clé.
 * @return string
 */
function scoutsunite_photos_generer_cle( $adresse, $version ) {
	return substr( hash_hmac( 'sha256', 'scoutsunite/photos/acces/v1/' . $adresse . '/' . (int) $version, wp_salt( 'secure_auth' ) ), 0, 36 );
}

/**
 * Clé partageable, uniquement pour les animateurs autorisés et le staff.
 * Une ancienne clé aléatoire reste valide, mais son empreinte seule ne permet pas de la retrouver.
 *
 * @param object|null $album Album.
 * @return string Clé, ou '' si non autorisé ou ancien format.
 */
function scoutsunite_photos_cle_partage( $album ) {
	if ( ! scoutsunite_photos_peut_gerer( $album ) ) {
		return '';
	}
	$cle = scoutsunite_photos_generer_cle( $album->adresse, $album->version );
	return hash_equals( $album->cle_hash, scoutsunite_photos_empreinte_cle( $cle ) ) ? $cle : '';
}

/**
 * Adresse stable de l'espace ou d'un album ; fonctionne aussi sans permaliens.
 *
 * @param object|null $album Album facultatif.
 * @return string
 */
function scoutsunite_photos_url( $album = null ) {
	if ( ! get_option( 'permalink_structure' ) ) {
		return add_query_arg( array_filter( array( 'su_photos' => '1', 'su_album' => $album ? $album->adresse : '' ) ), home_url( '/' ) );
	}
	return home_url( $album ? '/photos/album/' . $album->adresse . '/' : '/photos/' );
}

/**
 * Lecture d'une valeur textuelle : les tableaux forgés sont refusés.
 *
 * @param array  $donnees Données déjà déséchappées.
 * @param string $cle Nom.
 * @return string
 */
function scoutsunite_photos_texte( $donnees, $cle ) {
	return isset( $donnees[ $cle ] ) && is_scalar( $donnees[ $cle ] ) ? sanitize_text_field( (string) $donnees[ $cle ] ) : '';
}

/**
 * Tags libres d'un envoi ou d'une sélection ; même clé pour « Dîner » et « diner ».
 *
 * @param string $texte Noms séparés par des virgules, points-virgules ou retours à la ligne.
 * @return array Identifiant => libellé, huit tags au plus.
 */
function scoutsunite_photos_tags_lire( $texte ) {
	$tags = array();
	foreach ( preg_split( '/[,;\r\n]+/u', $texte ) as $nom ) {
		$nom = wp_html_excerpt( sanitize_text_field( trim( $nom ) ), 40, '' );
		$cle = sanitize_title( $nom );
		if ( $nom && $cle ) {
			$tags[ $cle ] = $nom;
		}
		if ( count( $tags ) >= 8 ) {
			break;
		}
	}
	return $tags;
}

/**
 * Tags d'une image privée.
 *
 * @param object $image Image.
 * @return array
 */
function scoutsunite_photos_tags_image( $image ) {
	$tags = json_decode( (string) ( $image->tags ?? '' ), true );
	return is_array( $tags ) ? $tags : array();
}

/**
 * Tags disponibles dans l'album : les tags des photos non publiées restent invisibles aux parents.
 *
 * @param object $album Album autorisé.
 * @param bool   $gestion Accès animateur ou staff.
 * @return array
 */
function scoutsunite_photos_tags_album( $album, $gestion = false ) {
	global $wpdb;
	$statut = $gestion ? '' : 'publiee';
	$lignes = $wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT tags FROM %i WHERE album = %d AND (%s = %s OR statut = %s)', scoutsunite_photos_table( 'images' ), $album->id, $statut, '', $statut ) );
	$tags   = array();
	foreach ( $lignes as $ligne ) {
		$valeurs = json_decode( (string) $ligne, true );
		if ( is_array( $valeurs ) ) {
			$tags = array_merge( $tags, $valeurs );
		}
	}
	natcasesort( $tags );
	return $tags;
}

/**
 * Journal interne sans clés, noms de fichiers ou données des parents.
 *
 * @param int    $album Album.
 * @param string $operation Action.
 * @param int    $photo Photo éventuelle.
 */
function scoutsunite_photos_journal( $album, $operation, $photo = 0 ) {
	global $wpdb;
	$wpdb->insert( scoutsunite_photos_table( 'journal' ), array( 'album' => $album, 'photo' => $photo, 'auteur' => get_current_user_id(), 'operation' => $operation, 'date_action' => gmdate( 'Y-m-d H:i:s' ) ), array( '%d', '%d', '%d', '%s', '%s' ) );
}

/**
 * Mutations communes aux formulaires et à l'envoi mobile. Le nonce est contrôlé par les deux entrées HTTP.
 * Un verrou partagé au stockage sérialise aussi clés, états, suppressions et quotas.
 *
 * @param string $operation Action.
 * @param array  $donnees Champs déjà déséchappés.
 * @param array  $fichier Fichier PHP éventuel, jamais un chemin fourni dans les champs.
 * @return array|WP_Error
 */
function scoutsunite_photos_executer( $operation, $donnees, $fichier = array() ) {
	global $wpdb;
	if ( ! current_user_can( 'scoutsunite_photos' ) ) {
		return new WP_Error( 'interdit', 'Tu n’as pas accès à cet album.', array( 'status' => 403 ) );
	}
	if ( ! scoutsunite_photos_transport_sur() ) {
		return new WP_Error( 'https', 'Les photos demandent une connexion HTTPS. Contacte l’administrateur du site.' );
	}
	$verrou = scoutsunite_photos_verrou();
	if ( is_wp_error( $verrou ) ) {
		return $verrou;
	}
	try {
		if ( 'creer' === $operation ) {
			$section = scoutsunite_photos_texte( $donnees, 'section' );
			$annee   = absint( scoutsunite_photos_texte( $donnees, 'annee' ) );
			if ( ! isset( scoutsunite_photos_sections_permises()[ $section ] ) || $annee < 2000 || $annee > (int) wp_date( 'Y' ) + 2 ) {
				return new WP_Error( 'champs', 'Choisis ta section et une année de camp valide.' );
			}
			$existant = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE section = %s AND annee = %d', scoutsunite_photos_table( 'albums' ), $section, $annee ) );
			if ( $existant ) {
				return array( 'album' => (int) $existant, 'message' => 'Cet album existe déjà. Tu peux le compléter.' );
			}
			$adresse = bin2hex( random_bytes( 12 ) );
			$cle     = scoutsunite_photos_generer_cle( $adresse, 1 );
			$cree = $wpdb->insert( scoutsunite_photos_table( 'albums' ), array( 'adresse' => $adresse, 'section' => $section, 'annee' => $annee, 'titre' => wp_html_excerpt( scoutsunite_photos_texte( $donnees, 'titre' ), 180, '' ), 'etat' => 'ouvert', 'cle_hash' => scoutsunite_photos_empreinte_cle( $cle ), 'auteur' => get_current_user_id(), 'cree_le' => gmdate( 'Y-m-d H:i:s' ) ), array( '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s' ) );
			if ( ! $cree ) {
				return new WP_Error( 'base', 'L’album n’a pas été créé. Réessaie.' );
			}
			$id = (int) $wpdb->insert_id;
			scoutsunite_photos_journal( $id, 'creer' );
			return array( 'album' => $id, 'message' => 'Album créé. Tu peux publier des photos et partager son lien avec les parents.' );
		}
		$album = scoutsunite_photos_album( absint( scoutsunite_photos_texte( $donnees, 'album' ) ) );
		if ( ! scoutsunite_photos_peut_gerer( $album ) ) {
			return new WP_Error( 'interdit', 'Tu n’as pas accès à cet album.', array( 'status' => 403 ) );
		}
		$id    = (int) $album->id;
		$staff = current_user_can( 'scoutsunite_gerer_photos' );
		if ( 'supprimer_album' === $operation && ! $staff ) {
			return new WP_Error( 'interdit', 'La suppression complète d’un album est réservée au staff d’unité.', array( 'status' => 403 ) );
		}
		if ( 'cle' === $operation ) {
			if ( (int) scoutsunite_photos_texte( $donnees, 'version' ) !== (int) $album->version ) {
				return new WP_Error( 'version', 'L’accès a déjà changé. Recharge l’album avant de créer une nouvelle clé.' );
			}
			$cle = scoutsunite_photos_generer_cle( $album->adresse, (int) $album->version + 1 );
			if ( false === $wpdb->update( scoutsunite_photos_table( 'albums' ), array( 'cle_hash' => scoutsunite_photos_empreinte_cle( $cle ), 'version' => (int) $album->version + 1 ), array( 'id' => $id ), array( '%s', '%d' ), array( '%d' ) ) ) {
				return new WP_Error( 'base', 'La clé n’a pas été modifiée. Réessaie.' );
			}
			$wpdb->delete( scoutsunite_photos_table( 'sessions' ), array( 'album' => $id ), array( '%d' ) );
			scoutsunite_photos_journal( $id, 'cle' );
			return array( 'album' => $id, 'cle' => $cle, 'message' => 'Le lien a été renouvelé. L’ancien ne fonctionne plus. Tu retrouveras le nouveau lien et son QR dans « Partager l’album ».' );
		}
		if ( 'etat' === $operation ) {
			$etat = scoutsunite_photos_texte( $donnees, 'etat' );
			if ( ! in_array( $etat, array( 'brouillon', 'ouvert', 'archive', 'ferme' ), true ) ) {
				return new WP_Error( 'etat', 'Choisis un état proposé.' );
			}
			// Une fermeture supprime les sessions, mais garde le lien pour une réouverture explicite.
			$fermeture = in_array( $etat, array( 'ferme', 'brouillon' ), true );
			$champs    = array( 'etat' => $etat );
			if ( false === $wpdb->update( scoutsunite_photos_table( 'albums' ), $champs, array( 'id' => $id ) ) ) {
				return new WP_Error( 'base', 'L’état n’a pas été enregistré.' );
			}
			if ( $fermeture ) {
				$wpdb->delete( scoutsunite_photos_table( 'sessions' ), array( 'album' => $id ), array( '%d' ) );
			}
			scoutsunite_photos_journal( $id, $etat );
			return array( 'album' => $id, 'message' => $fermeture ? 'Accès parent fermé. Le même lien pourra être réutilisé à la réouverture.' : 'État de l’album enregistré.' );
		}
		if ( 'regler' === $operation ) {
			$date       = scoutsunite_photos_texte( $donnees, 'expiration' );
			$expiration = '';
			if ( $date ) {
				$expiration = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
				if ( ! $expiration || $expiration->format( 'Y-m-d' ) !== $date ) {
					return new WP_Error( 'date', 'Choisis une date d’expiration valide.' );
				}
			}
			$wpdb->update( scoutsunite_photos_table( 'albums' ), array( 'titre' => wp_html_excerpt( scoutsunite_photos_texte( $donnees, 'titre' ), 180, '' ), 'expiration' => $expiration ? $expiration->modify( '+1 day' )->getTimestamp() : 0 ), array( 'id' => $id ) );
			scoutsunite_photos_journal( $id, 'regler' );
			return array( 'album' => $id, 'message' => 'Réglages enregistrés. L’expiration ferme la lecture parent, sans effacer les photos.' );
		}
		if ( 'supprimer_album' === $operation ) {
			if ( 'SUPPRIMER' !== scoutsunite_photos_texte( $donnees, 'confirmation' ) ) {
				return new WP_Error( 'confirmation', 'Écris SUPPRIMER pour effacer cet album et ses photos.' );
			}
			$images = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE album = %d', scoutsunite_photos_table( 'images' ), $id ) );
			foreach ( $images as $image ) {
				if ( ! scoutsunite_photos_effacer_fichiers( $image ) ) {
					return new WP_Error( 'disque', 'Certains fichiers n’ont pas pu être effacés. Réessaie ou contacte l’administrateur.' );
				}
			}
			$wpdb->delete( scoutsunite_photos_table( 'images' ), array( 'album' => $id ), array( '%d' ) );
			$wpdb->delete( scoutsunite_photos_table( 'sessions' ), array( 'album' => $id ), array( '%d' ) );
			$wpdb->delete( scoutsunite_photos_table( 'albums' ), array( 'id' => $id ), array( '%d' ) );
			$wpdb->delete( scoutsunite_photos_table( 'journal' ), array( 'album' => $id ), array( '%d' ) );
			return array( 'album' => 0, 'message' => 'L’album et ses photos ont été supprimés.' );
		}
		if ( ! $staff && ! in_array( $album->etat, array( 'brouillon', 'ouvert' ), true ) ) {
			return new WP_Error( 'archive', 'Cet album n’accepte plus de modifications. Contacte le staff d’unité.' );
		}
		if ( 'envoyer' === $operation ) {
			if ( ! in_array( $album->etat, array( 'brouillon', 'ouvert' ), true ) ) {
				return new WP_Error( 'archive', 'Rouvre l’album avant d’ajouter des photos.' );
			}
			if ( empty( $fichier['tmp_name'] ) || ! is_string( $fichier['tmp_name'] ) || ! is_uploaded_file( $fichier['tmp_name'] ) || ! isset( $fichier['error'] ) || ! is_scalar( $fichier['error'] ) || UPLOAD_ERR_OK !== (int) $fichier['error'] ) {
				return new WP_Error( 'envoi', 'La photo n’a pas été reçue. Vérifie sa taille et réessaie.' );
			}
			return scoutsunite_photos_importer( $album, $fichier['tmp_name'], scoutsunite_photos_texte( $donnees, 'legende' ), scoutsunite_photos_tags_lire( scoutsunite_photos_texte( $donnees, 'tags' ) ) );
		}
		if ( in_array( $operation, array( 'publier', 'retirer', 'supprimer_photo', 'taguer', 'effacer_tags' ), true ) ) {
			$ids = isset( $donnees['photos'] ) && is_array( $donnees['photos'] ) ? array_slice( array_unique( array_map( 'absint', $donnees['photos'] ) ), 0, 24 ) : array();
			if ( ! $ids ) {
				return new WP_Error( 'selection', 'Sélectionne au moins une photo.' );
			}
			$tags = scoutsunite_photos_tags_lire( scoutsunite_photos_texte( $donnees, 'tags' ) );
			if ( 'taguer' === $operation && ! $tags ) {
				return new WP_Error( 'tags', 'Indique au moins un tag pour classer la sélection.' );
			}
			foreach ( $ids as $photo ) {
				$image = scoutsunite_photos_image( $photo );
				if ( ! $image || (int) $image->album !== $id ) {
					return new WP_Error( 'interdit', 'La sélection contient une photo d’un autre album.', array( 'status' => 403 ) );
				}
			}
			foreach ( $ids as $photo ) {
				if ( 'supprimer_photo' === $operation ) {
					if ( ! scoutsunite_photos_effacer_fichiers( scoutsunite_photos_image( $photo ) ) ) {
						return new WP_Error( 'disque', 'La photo n’a pas pu être effacée. Réessaie.' );
					}
					$wpdb->delete( scoutsunite_photos_table( 'images' ), array( 'id' => $photo ), array( '%d' ) );
				} elseif ( in_array( $operation, array( 'taguer', 'effacer_tags' ), true ) ) {
					$actuels = scoutsunite_photos_tags_image( scoutsunite_photos_image( $photo ) );
					$nouveaux = 'effacer_tags' === $operation ? array() : array_slice( array_merge( $actuels, $tags ), 0, 8, true );
					$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'tags' => wp_json_encode( $nouveaux ) ), array( 'id' => $photo ), array( '%s' ), array( '%d' ) );
				} else {
					$wpdb->update( scoutsunite_photos_table( 'images' ), array( 'statut' => 'publier' === $operation ? 'publiee' : 'retiree' ), array( 'id' => $photo ), array( '%s' ), array( '%d' ) );
				}
				scoutsunite_photos_journal( $id, $operation, $photo );
			}
			return array( 'album' => $id, 'message' => 'La sélection a été mise à jour.' );
		}
		return new WP_Error( 'operation', 'Cette action n’est pas proposée.' );
	} finally {
		scoutsunite_photos_deverrouiller( $verrou );
	}
}

/**
 * Empreinte secrète d'une clé forte. La clé en clair n'est jamais enregistrée.
 *
 * @param string $cle Clé.
 * @return string
 */
function scoutsunite_photos_empreinte_cle( $cle ) {
	return hash_hmac( 'sha256', $cle, wp_salt( 'auth' ) );
}

/**
 * HTTP est réservé au site local. En production, les clés et cookies exigent HTTPS.
 *
 * @return bool
 */
function scoutsunite_photos_transport_sur() {
	return is_ssl() || in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1', '::1' ), true );
}

/**
 * Nom d'un cookie d'album, distinct pour chaque installation WordPress.
 *
 * @param int $id Album.
 * @return string
 */
function scoutsunite_photos_cookie( $id ) {
	return 'su_photos_' . substr( hash( 'sha256', home_url() ), 0, 8 ) . '_' . absint( $id );
}

/**
 * Album ouvert aux parents, indépendamment d'une session particulière.
 *
 * @param object|null $album Album frais.
 * @return bool
 */
function scoutsunite_photos_album_ouvert( $album ) {
	return $album && in_array( $album->etat, array( 'ouvert', 'archive' ), true ) && '' !== $album->cle_hash && ( ! $album->expiration || (int) $album->expiration > time() );
}

/**
 * Vérifie une session à chaque lecture. Aucun cookie ne suffit sans sa ligne serveur et sa version.
 *
 * @param object|null $album Album.
 * @param string     $jeton Jeton du cookie.
 * @return bool
 */
function scoutsunite_photos_session_valide( $album, $jeton ) {
	global $wpdb;
	if ( ! scoutsunite_photos_album_ouvert( $album ) || ! preg_match( '/^[a-f0-9]{64}$/D', $jeton ) || ! scoutsunite_photos_transport_sur() ) {
		return false;
	}
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT album FROM %i WHERE empreinte = %s AND album = %d AND version = %d AND expiration > %d', scoutsunite_photos_table( 'sessions' ), hash( 'sha256', $jeton ), $album->id, $album->version, time() ) );
}

/**
 * Ouvre une session après validation de la clé. Limite les échecs sans conserver l'IP en clair.
 *
 * @param object|null $album Album demandé.
 * @param string     $cle Clé présentée.
 * @return array|WP_Error Jeton à placer dans un cookie par l'entrée HTTP.
 */
function scoutsunite_photos_ouvrir( $album, $cle ) {
	global $wpdb;
	if ( ! scoutsunite_photos_transport_sur() ) {
		return new WP_Error( 'https', 'L’accès aux photos nécessite une connexion HTTPS. Contacte le staff.' );
	}
	// REMOTE_ADDR vient du serveur ; ne pas faire confiance à X-Forwarded-For fourni par le client.
	$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$limite = 'su_photos_essais_' . hash_hmac( 'sha256', $ip . '|' . ( $album ? $album->id : 0 ), wp_salt( 'nonce' ) );
	$essais = (int) get_transient( $limite );
	if ( $essais >= 50 ) {
		return new WP_Error( 'essais', 'Trop de tentatives. Attends quinze minutes avant de réessayer.' );
	}
	$verrou = scoutsunite_photos_verrou();
	if ( is_wp_error( $verrou ) ) {
		return $verrou;
	}
	try {
		$album = $album ? scoutsunite_photos_album( $album->id ) : null;
		if ( ! scoutsunite_photos_album_ouvert( $album ) || ! preg_match( '/^[a-f0-9]{36}$/D', $cle ) || ! hash_equals( $album->cle_hash, scoutsunite_photos_empreinte_cle( $cle ) ) ) {
			set_transient( $limite, $essais + 1, 15 * MINUTE_IN_SECONDS );
			return new WP_Error( 'acces', 'Ce lien n’ouvre plus l’album. Demande l’accès au staff de ta section.' );
		}
		$jeton      = bin2hex( random_bytes( 32 ) );
		$expiration = min( time() + 7 * DAY_IN_SECONDS, $album->expiration ? (int) $album->expiration : PHP_INT_MAX );
		if ( ! $wpdb->insert( scoutsunite_photos_table( 'sessions' ), array( 'empreinte' => hash( 'sha256', $jeton ), 'album' => $album->id, 'version' => $album->version, 'expiration' => $expiration ), array( '%s', '%d', '%d', '%d' ) ) ) {
			return new WP_Error( 'base', 'L’accès n’a pas pu être ouvert. Réessaie.' );
		}
		delete_transient( $limite );
		return array( 'jeton' => $jeton, 'expiration' => $expiration );
	} finally {
		scoutsunite_photos_deverrouiller( $verrou );
	}
}

/**
 * Cookie lu uniquement pour vérifier une session ; jamais utilisé comme autorisation sans contrôle.
 *
 * @param int $id Album.
 * @return string
 */
function scoutsunite_photos_jeton( $id ) {
	$nom = scoutsunite_photos_cookie( $id );
	return isset( $_COOKIE[ $nom ] ) && is_string( $_COOKIE[ $nom ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $nom ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- lecture d'une session vérifiée côté serveur.
}

/**
 * Autorisation de lecture d'une image, reprise aussi pour son téléchargement.
 *
 * @param object|null $image Photo.
 * @param object|null $album Album.
 * @return bool
 */
function scoutsunite_photos_peut_lire( $image, $album ) {
	return $image && $album && (int) $image->album === (int) $album->id && ( scoutsunite_photos_peut_gerer( $album ) || ( 'publiee' === $image->statut && scoutsunite_photos_session_valide( $album, scoutsunite_photos_jeton( $album->id ) ) ) );
}

/** Sessions expirées et journal de gestion : 90 jours. Aucun effacement automatique d'album. */
function scoutsunite_photos_nettoyer() {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE expiration < %d', scoutsunite_photos_table( 'sessions' ), time() ) );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE date_action < %s', scoutsunite_photos_table( 'journal' ), gmdate( 'Y-m-d H:i:s', time() - 90 * DAY_IN_SECONDS ) ) );
	scoutsunite_photos_nettoyer_fichiers();
}
add_action( 'scoutsunite_photos_nettoyage', 'scoutsunite_photos_nettoyer' );

/**
 * Bloc public : le lien d'entrée seulement, jamais une couverture ou une liste d'albums.
 *
 * @return string
 */
function scoutsunite_photos_bloc() {
	return '<p class="photos-entree"><a href="' . esc_url( scoutsunite_photos_url() ) . '">Les photos de camp</a> — Ouvre le lien reçu du staff pour voir ton album.</p>';
}
