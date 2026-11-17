<?php
/**
 * Sélections locales des familles, export autorisé et demandes privées au staff.
 * Les images restent privées ; les coordonnées ne sont jamais renvoyées aux autres parents.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Droit de préparer une sélection, contrôlé à chaque requête.
 *
 * @param object|null $album Album frais.
 * @return bool
 */
function scoutsunite_photos_selection_autorisee( $album ) {
	return $album && scoutsunite_photos_transport_sur() && ! is_wp_error( scoutsunite_photos_dossier() ) && ( scoutsunite_photos_peut_gerer( $album ) || scoutsunite_photos_session_valide( $album, scoutsunite_photos_jeton( $album->id ) ) );
}

/**
 * Liste bornée de références, sans autorisation déduite de leur présence.
 *
 * @param mixed $valeur Tableau ou chaîne JSON.
 * @return array|WP_Error
 */
function scoutsunite_photos_selection_ids( $valeur ) {
	$valeur = is_string( $valeur ) ? json_decode( $valeur, true ) : $valeur;
	if ( ! is_array( $valeur ) || count( $valeur ) > 1000 ) {
		return new WP_Error( 'selection', 'Choisis au plus 1 000 photos pour cette sélection.' );
	}
	$ids = array();
	foreach ( $valeur as $id ) {
		if ( ! ( is_int( $id ) || ( is_string( $id ) && ctype_digit( $id ) ) ) || $id < 1 || (float) $id > PHP_INT_MAX ) {
			return new WP_Error( 'selection', 'La sélection contient une référence invalide.' );
		}
		$ids[] = (int) $id;
	}
	return array_values( array_unique( $ids ) );
}

/**
 * Photos publiées de la sélection, dans son ordre, toujours limitées à l'album autorisé.
 *
 * @param object|null $album Album.
 * @param mixed       $selection Références.
 * @param bool        $stricte Refuser toute référence absente plutôt que l'écarter de l'aperçu.
 * @return array|WP_Error
 */
function scoutsunite_photos_selection_images( $album, $selection, $stricte = true ) {
	global $wpdb;
	if ( ! scoutsunite_photos_selection_autorisee( $album ) ) {
		return new WP_Error( 'acces', 'Ton accès à cet album a expiré. Rouvre le lien reçu du staff.' );
	}
	$ids = scoutsunite_photos_selection_ids( $selection );
	if ( is_wp_error( $ids ) || ! $ids ) {
		return is_wp_error( $ids ) ? $ids : new WP_Error( 'selection', 'Marque au moins une photo.' );
	}
	$places = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	// Seuls les emplacements %d sont construits ; toutes les références passent par prepare().
	$sql = $wpdb->prepare( "SELECT * FROM %i WHERE album = %d AND statut = %s AND id IN ($places)", array_merge( array( scoutsunite_photos_table( 'images' ), $album->id, 'publiee' ), $ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- placeholders construits selon la liste bornée, valeurs toutes préparées.
	$recues = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- requête préparée ci-dessus.
	$images = array();
	foreach ( $recues as $image ) {
		if ( scoutsunite_photos_chemin( $image ) && scoutsunite_photos_chemin( $image, true ) ) {
			$images[ (int) $image->id ] = $image;
		}
	}
	if ( $stricte && count( $images ) !== count( $ids ) ) {
		return new WP_Error( 'selection', 'Certaines photos ne sont plus disponibles. Rouvre « Ma sélection » avant de continuer.' );
	}
	return array_values( array_replace( array_fill_keys( array_values( array_intersect( $ids, array_keys( $images ) ) ), null ), $images ) );
}

/**
 * Empreinte de session pour l'anti-doublon et la limitation des demandes, sans clé en clair.
 *
 * @param object $album Album autorisé.
 * @return string
 */
function scoutsunite_photos_selection_parent( $album ) {
	return hash_hmac( 'sha256', get_current_user_id() . ':' . scoutsunite_photos_jeton( $album->id ), wp_salt( 'nonce' ) );
}

/**
 * Enregistre une demande sans paiement. Aucun e-mail ni appel à un imprimeur n'est envoyé.
 *
 * @param object|null $album Album.
 * @param array       $donnees Champs déséchappés.
 * @return array|WP_Error
 */
function scoutsunite_photos_demander( $album, $donnees ) {
	global $wpdb;
	// Pas de verrou global : la clé unique `reference` empêche le doublon et rien n'est modifié ailleurs.
	$album = $album ? scoutsunite_photos_album( $album->id ) : null;
	$images = scoutsunite_photos_selection_images( $album, $donnees['selection'] ?? null );
	if ( is_wp_error( $images ) ) {
		return $images;
	}
	$nom = wp_html_excerpt( scoutsunite_photos_texte( $donnees, 'nom' ), 100, '' );
	$email = scoutsunite_photos_texte( $donnees, 'email' );
	$message = isset( $donnees['message'] ) && is_string( $donnees['message'] ) ? wp_html_excerpt( sanitize_textarea_field( $donnees['message'] ), 2000, '' ) : '';
	$reference = scoutsunite_photos_texte( $donnees, 'reference' );
	if ( '' === $nom || ! is_email( $email ) || strlen( $email ) > 190 || ! preg_match( '/^[a-f0-9]{32}$/D', $reference ) ) {
		return new WP_Error( 'champs', 'Indique ton nom et une adresse e-mail valide. Recharge la page si le problème persiste.' );
	}
	$parent = scoutsunite_photos_selection_parent( $album );
	$existante = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE reference = %s', scoutsunite_photos_table( 'demandes' ), $reference ) );
	if ( $existante ) {
		return (int) $existante->album === (int) $album->id && hash_equals( $existante->parent_hash, $parent )
			? array( 'reference' => strtoupper( substr( $reference, 0, 8 ) ), 'message' => 'Ta demande a déjà été enregistrée pour le staff.' )
			: new WP_Error( 'reference', 'Recharge la page avant de préparer une nouvelle demande.' );
	}
	$limite = 'su_photos_demandes_' . $parent;
	$nombre = (int) get_transient( $limite );
	if ( $nombre >= 5 ) {
		return new WP_Error( 'essais', 'Plusieurs demandes viennent d’être envoyées. Attends dix minutes avant de recommencer.' );
	}
	$ids = array_map( 'intval', wp_list_pluck( $images, 'id' ) );
	if ( ! $wpdb->insert( scoutsunite_photos_table( 'demandes' ), array( 'album' => $album->id, 'reference' => $reference, 'parent_hash' => $parent, 'nom' => $nom, 'email' => sanitize_email( $email ), 'message' => $message, 'photos' => wp_json_encode( $ids ), 'cree_le' => gmdate( 'Y-m-d H:i:s' ) ) ) ) {
		return new WP_Error( 'base', 'La demande n’a pas été enregistrée. Réessaie avec cette même page.' );
	}
	set_transient( $limite, $nombre + 1, 10 * MINUTE_IN_SECONDS );
	scoutsunite_photos_journal( $album->id, 'demander' );
	return array( 'reference' => strtoupper( substr( $reference, 0, 8 ) ), 'message' => 'Ta demande a été enregistrée pour le staff. Il te recontactera pour les détails ; aucun paiement n’a été effectué.' );
}

/**
 * Entrée JSON de la sélection : uniquement en POST avec nonce lié à l'accès de cet album.
 *
 * @param object|null $album Album de l'adresse, jamais choisi par le formulaire.
 */
function scoutsunite_photos_selection_requete( $album ) {
	global $wpdb;
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) {
		wp_send_json_error( array( 'message' => 'Cette action demande un formulaire.' ), 405 );
	}
	$donnees = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce vérifié ci-dessous et champs assainis dans les helpers.
	if ( ! scoutsunite_photos_selection_autorisee( $album ) || ! scoutsunite_photos_origine_valide() || ! wp_verify_nonce( scoutsunite_photos_texte( $donnees, '_wpnonce' ), scoutsunite_photos_nonce_action( 'selection', $album ) ) ) {
		wp_send_json_error( array( 'message' => 'Ton accès a expiré. Rouvre le lien reçu du staff.' ), 403 );
	}
	$operation = scoutsunite_photos_texte( $donnees, 'operation' );
	if ( 'demander' === $operation ) {
		$resultat = scoutsunite_photos_demander( $album, $donnees );
	} elseif ( 'tout' === $operation ) {
		$ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM %i WHERE album = %d AND statut = %s ORDER BY id DESC LIMIT 1001', scoutsunite_photos_table( 'images' ), $album->id, 'publiee' ) ) );
		$resultat = count( $ids ) <= 1000 ? array( 'ids' => $ids ) : new WP_Error( 'limite', 'Cet album dépasse 1 000 photos. Marque celles que tu souhaites retenir.' );
	} elseif ( 'lire' === $operation ) {
		$images = scoutsunite_photos_selection_images( $album, $donnees['selection'] ?? null, false );
		$resultat = $images;
		if ( ! is_wp_error( $images ) ) {
			$url = scoutsunite_photos_url( $album );
			$resultat = array( 'photos' => array_map( function ( $image ) use ( $url ) {
				return array( 'id' => (int) $image->id, 'legende' => $image->legende ?: 'Photo ' . $image->id, 'mini' => add_query_arg( array( 'fichier' => $image->id, 'format' => 'mini' ), $url ), 'voir' => add_query_arg( 'voir', $image->id, $url ) );
			}, $images ) );
		}
	} else {
		$resultat = new WP_Error( 'operation', 'Cette action n’est pas proposée.' );
	}
	if ( is_wp_error( $resultat ) ) {
		wp_send_json_error( array( 'message' => $resultat->get_error_message() ), 400 );
	}
	wp_send_json_success( $resultat );
}

/**
 * Traite ou efface une demande ; aucune autorisation ne découle de la clé parent.
 *
 * @param object|null $album Album.
 * @param array       $donnees Champs déjà déséchappés.
 * @param bool        $supprimer Effacement définitif de la demande seulement.
 * @return array|WP_Error
 */
function scoutsunite_photos_demande_gerer( $album, $donnees, $supprimer = false ) {
	global $wpdb;
	if ( ! scoutsunite_photos_peut_gerer( $album ) ) {
		return new WP_Error( 'interdit', 'La gestion des demandes est réservée aux animateurs autorisés et au staff.' );
	}
	$verrou = scoutsunite_photos_verrou();
	if ( is_wp_error( $verrou ) ) {
		return $verrou;
	}
	try {
		$id = absint( scoutsunite_photos_texte( $donnees, 'demande' ) );
		$demande = $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM %i WHERE id = %d AND album = %d', scoutsunite_photos_table( 'demandes' ), $id, $album->id ) );
		if ( ! $demande ) {
			return new WP_Error( 'interdit', 'Cette demande n’est pas disponible dans cet album.' );
		}
		$resultat = $supprimer ? $wpdb->delete( scoutsunite_photos_table( 'demandes' ), array( 'id' => $id ), array( '%d' ) ) : $wpdb->update( scoutsunite_photos_table( 'demandes' ), array( 'etat' => 'traitee', 'traitee_le' => time() ), array( 'id' => $id ) );
		return false === $resultat ? new WP_Error( 'base', 'La demande n’a pas pu être mise à jour.' ) : array( 'message' => $supprimer ? 'La demande a été effacée. Les photos restent dans l’album.' : 'La demande est marquée comme traitée.' );
	} finally {
		scoutsunite_photos_deverrouiller( $verrou );
	}
}

/** Demandes traitées : effacement des coordonnées et de la sélection après 90 jours. */
function scoutsunite_photos_demandes_nettoyer() {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE etat = %s AND traitee_le > 0 AND traitee_le < %d', scoutsunite_photos_table( 'demandes' ), 'traitee', time() - 90 * DAY_IN_SECONDS ) );
}
add_action( 'scoutsunite_photos_nettoyage', 'scoutsunite_photos_demandes_nettoyer' );

/**
 * Outils parent : seules les références sont mémorisées dans ce navigateur.
 *
 * @param object $album Album déjà autorisé.
 */
function scoutsunite_photos_selection_interface( $album ) {
	if ( ! scoutsunite_photos_selection_autorisee( $album ) ) {
		return;
	}
	?>
	<section id="photos-selection" class="photos__selection" data-album="<?php echo esc_attr( $album->adresse ); ?>" data-url="<?php echo esc_url( add_query_arg( 'su_selection', 1, scoutsunite_photos_url( $album ) ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( scoutsunite_photos_nonce_action( 'selection', $album ) ) ); ?>" hidden>
	<details id="photos-selection-ouvrir"><summary>Ma sélection (<span id="photos-selection-nombre">0</span>)</summary>
	<p>Marque les photos avec le cœur. Tes choix restent sur ce navigateur jusqu’à ce que tu les effaces. Ils sont séparés de ceux des autres parents ; seules les références sont mémorisées, jamais les images.</p>
	<div class="photos__actions"><button type="button" id="photos-selection-tout" class="photos__secondaire">Tout marquer dans l’album</button><button type="button" id="photos-selection-vider" class="photos__secondaire">Vider ma sélection</button></div>
	<p id="photos-selection-retour" role="status"></p><ul id="photos-selection-liste" class="photos__selection-liste"></ul>
	<form method="post" id="photos-selection-export"><?php scoutsunite_photos_champs( 'exporter_selection', $album ); ?><input type="hidden" name="selection" value="[]"><button type="submit" id="photos-selection-zip" disabled>Exporter ma sélection (ZIP)</button></form><p class="photos__aide">Photos optimisées, de 2 560 pixels au plus. Pour de grands tirages, demande les originaux au staff.</p>
	<details id="photos-demande"><summary>Préparer une demande au staff</summary>
	<form method="post" id="photos-demande-formulaire" class="photos__formulaire"><?php scoutsunite_photos_champs( 'demander', $album ); ?><input type="hidden" name="selection" value="[]">
	<label for="photos-demande-nom">Ton nom</label><input id="photos-demande-nom" name="nom" maxlength="100" autocomplete="name" required>
	<label for="photos-demande-email">Ton e-mail</label><input id="photos-demande-email" name="email" type="email" maxlength="190" autocomplete="email" required>
	<label for="photos-demande-message">Un message pour le staff (facultatif)</label><textarea id="photos-demande-message" name="message" maxlength="2000" rows="3" placeholder="Précise le souvenir souhaité et le nombre d’exemplaires ; le staff confirmera les détails."></textarea>
	<input type="hidden" name="reference" value="<?php echo esc_attr( bin2hex( random_bytes( 16 ) ) ); ?>">
	<p class="photos__aide">Ton nom, ton e-mail, ton message et les références choisies seront visibles uniquement par les animateurs autorisés de la section et le staff d’unité. Aucun paiement ni envoi à un imprimeur. Tu peux demander au staff d’effacer ta demande ; elle est effacée automatiquement 90 jours après son traitement.</p>
	<button type="submit" id="photos-demande-envoyer" disabled>Transmettre ma demande au staff</button><p id="photos-demande-retour" role="status"></p>
	</form></details></details></section>
	<noscript><p>Active JavaScript pour marquer des photos et préparer une demande au staff. Le téléchargement individuel reste disponible.</p></noscript>
	<?php
}

/**
 * Demandes de cet album : rendu réservé aux gestionnaires, sans API publique.
 *
 * @param object $album Album.
 */
function scoutsunite_photos_demandes_interface( $album ) {
	global $wpdb;
	if ( ! scoutsunite_photos_peut_gerer( $album ) ) {
		return;
	}
	$demandes = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE album = %d ORDER BY id DESC LIMIT 100', scoutsunite_photos_table( 'demandes' ), $album->id ) );
	?>
	<details class="photos__demandes" id="photos-demandes"><summary>Demandes des parents (<?php echo count( $demandes ); ?>)</summary><p>Le parent propose une sélection ; contacte-le pour confirmer le souvenir, les exemplaires et les modalités. Aucun paiement n’est effectué sur le site.</p>
	<?php if ( ! $demandes ) : ?><p>Aucune demande pour le moment.</p><?php endif; ?>
	<?php foreach ( $demandes as $demande ) : ?>
	<article class="photos__demande"><h3>Demande <?php echo esc_html( strtoupper( substr( $demande->reference, 0, 8 ) ) ); ?> · <?php echo 'traitee' === $demande->etat ? 'Traitée' : 'À préparer'; ?></h3>
	<p><?php echo esc_html( $demande->nom ); ?> · <a href="<?php echo esc_url( 'mailto:' . $demande->email ); ?>"><?php echo esc_html( $demande->email ); ?></a> · <?php echo esc_html( wp_date( 'j F Y', strtotime( $demande->cree_le . ' UTC' ) ) ); ?></p>
	<?php if ( $demande->message ) : ?><p class="photos__demande-message"><?php echo esc_html( $demande->message ); ?></p><?php endif; ?>
	<?php $ids = scoutsunite_photos_selection_ids( $demande->photos ); $disponibles = scoutsunite_photos_selection_images( $album, $ids, false ); ?>
	<p><?php echo is_array( $ids ) ? count( $ids ) : 0; ?> photo(s) demandée(s). <?php if ( is_array( $ids ) ) : foreach ( $ids as $id ) : ?><a href="<?php echo esc_url( add_query_arg( 'voir', $id, scoutsunite_photos_url( $album ) ) ); ?>">Photo <?php echo (int) $id; ?></a> <?php endforeach; endif; ?></p>
	<?php if ( ! is_wp_error( $disponibles ) && $disponibles ) : ?><form method="post"><?php scoutsunite_photos_champs( 'exporter_selection', $album ); ?><input type="hidden" name="selection" value="<?php echo esc_attr( wp_json_encode( array_map( 'intval', wp_list_pluck( $disponibles, 'id' ) ) ) ); ?>"><button type="submit">Exporter les <?php echo count( $disponibles ); ?> photo(s) encore disponible(s)</button></form><?php endif; ?>
	<form method="post" class="photos__actions"><?php scoutsunite_photos_champs( 'demande_traiter', $album ); ?><input type="hidden" name="demande" value="<?php echo (int) $demande->id; ?>"><?php if ( 'traitee' !== $demande->etat ) : ?><button type="submit">Marquer comme traitée</button><?php endif; ?><button type="submit" name="operation" value="supprimer_demande" class="photos__secondaire" data-confirmer="Effacer cette demande et les coordonnées du parent ? Les photos resteront dans l’album.">Effacer la demande</button></form></article>
	<?php endforeach; ?><p class="photos__aide">Les demandes traitées sont effacées après 90 jours. Seules les 100 dernières demandes sont affichées.</p></details>
	<?php
}
