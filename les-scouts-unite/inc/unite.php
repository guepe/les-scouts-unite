<?php
/**
 * Coordonnées de l'unité : encodées une fois dans Réglages → Unité,
 * affichées partout (pied de page, contact, infos pratiques, inscription).
 *
 * Les blocs des compositions sont liés à ces réglages par les liaisons de blocs
 * de WordPress (source « scoutsunite/unite », argument « key »). Un champ vide
 * garde son exemple surligné « à compléter ».
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Champs de la page de réglages, par groupe : clé => [ libellé, type, exemple ].
 *
 * @return array
 */
function scoutsunite_unite_champs() {
	$champs = array(
		'Le local'          => array(
			'local_rue'      => array( 'Rue et numéro', 'text', 'Rue du Local 1' ),
			'local_localite' => array( 'Code postal et localité', 'text', '0000 Localité' ),
		),
		'Le staff d’unité'  => array(
			'ru_nom'       => array( 'Responsable(s) d’unité', 'text', 'Prénom Nom' ),
			'ru_telephone' => array( 'Téléphone', 'tel', '0400 00 00 00' ),
			'staff_email'  => array( 'E-mail du staff d’unité', 'email', 'staff@example.org' ),
		),
		'Les staffs de section' => array(),
		'Les formulaires'   => array(
			'formulaire_inscription' => array( 'Lien du formulaire d’inscription', 'url', 'https://…' ),
			'formulaire_contact'     => array( 'Lien du formulaire de contact', 'url', 'https://…' ),
		),
		'Les inscriptions'  => array(
			'cotisation' => array( 'Cotisation (un tarif par ligne)', 'textarea', "80 € pour le 1er enfant\n70 € par enfant pour 2 enfants dans l’unité\n65 € par enfant, pour plus de 2 enfants" ),
			'compte'     => array( 'Numéro de compte (IBAN)', 'text', 'BE00 0000 0000 0000' ),
			'places'     => array( 'Places disponibles (affiche de l’accueil)', 'text', 'Places disponibles : à préciser par le staff.' ),
		),
	);
	foreach ( scoutsunite_agenda_sections() as $slug => $nom ) {
		if ( 'unite' === $slug ) {
			continue;
		}
		$champs['Les staffs de section'][ $slug . '_chef' ]  = array( $nom . ' : chef ou cheffe de section', 'text', 'Prénom Nom' );
		$champs['Les staffs de section'][ $slug . '_email' ] = array( $nom . ' : e-mail', 'email', $slug . '@example.org' );
	}
	return $champs;
}

/**
 * Valeur brute d'un champ, '' s'il n'est pas rempli.
 *
 * @param string $cle Clé du champ.
 * @return string
 */
function scoutsunite_unite( $cle ) {
	$valeurs = get_option( 'scoutsunite_unite', array() );
	return is_array( $valeurs ) && isset( $valeurs[ $cle ] ) ? trim( (string) $valeurs[ $cle ] ) : '';
}

/**
 * Valeur échappée, ou son exemple surligné « à compléter ».
 *
 * @param string $cle Clé du champ.
 * @return string HTML.
 */
function scoutsunite_unite_texte( $cle ) {
	$valeur = scoutsunite_unite( $cle );
	if ( '' !== $valeur ) {
		return esc_html( $valeur );
	}
	foreach ( scoutsunite_unite_champs() as $groupe ) {
		if ( isset( $groupe[ $cle ] ) ) {
			return '<mark class="a-completer">' . esc_html( $groupe[ $cle ][2] ) . '</mark>';
		}
	}
	return '';
}

/**
 * Téléphone ou e-mail en lien cliquable ; l'exemple reste sans lien.
 *
 * @param string $cle Clé du champ.
 * @param string $schema 'tel' ou 'mailto'.
 * @return string HTML.
 */
function scoutsunite_unite_lien( $cle, $schema ) {
	$valeur = scoutsunite_unite( $cle );
	if ( '' === $valeur ) {
		return scoutsunite_unite_texte( $cle );
	}
	$cible = 'tel' === $schema ? preg_replace( '/[^\d+]/', '', $valeur ) : $valeur;
	return '<a href="' . esc_url( $schema . ':' . $cible, array( $schema ) ) . '">' . esc_html( $valeur ) . '</a>';
}

/**
 * Contenu d'un bloc lié à une clé.
 *
 * @param string $cle Clé de liaison.
 * @return string HTML.
 */
function scoutsunite_unite_html( $cle ) {
	switch ( $cle ) {
		case 'adresse':
			return scoutsunite_unite_texte( 'local_rue' ) . '<br>' . scoutsunite_unite_texte( 'local_localite' );
		case 'itineraire':
			$adresse = trim( scoutsunite_unite( 'local_rue' ) . ', ' . scoutsunite_unite( 'local_localite' ), ', ' );
			if ( '' === $adresse ) {
				return '<mark class="a-completer">Itinéraire : ajoute l’adresse du local dans Réglages → Unité</mark>';
			}
			return '<a href="' . esc_url( 'https://www.openstreetmap.org/search?query=' . rawurlencode( $adresse . ', Belgique' ) ) . '">Itinéraire</a>';
		case 'staff-unite':
			return '<strong>' . scoutsunite_unite_texte( 'ru_nom' ) . '</strong><br>' . scoutsunite_unite_lien( 'ru_telephone', 'tel' ) . '<br>' . scoutsunite_unite_lien( 'staff_email', 'mailto' );
		case 'responsable':
			return '<strong>' . scoutsunite_unite_texte( 'ru_nom' ) . '</strong>, responsable d’unité<br>' . scoutsunite_unite_lien( 'ru_telephone', 'tel' );
		case 'email-staff':
			return scoutsunite_unite_lien( 'staff_email', 'mailto' );
		case 'cotisation':
			$tarifs = array_filter( array_map( 'trim', explode( "\n", scoutsunite_unite( 'cotisation' ) ) ) );
			if ( $tarifs ) {
				// Le montant en tête de ligne passe en gras : « 80 € pour le 1er enfant ».
				$lignes = array_map(
					function ( $ligne ) {
						return preg_replace( '/^([\d\s.,]+\s?€)/u', '<strong>$1</strong>', esc_html( $ligne ) );
					},
					$tarifs
				);
			} else {
				$lignes = array( '<mark class="a-completer">… € par enfant</mark>' );
			}
			return implode( '<br>', $lignes ) . '<br>À verser sur le compte <strong>' . scoutsunite_unite_texte( 'compte' ) . '</strong> avec le nom de l’enfant en communication.';
		case 'places':
			return scoutsunite_unite_texte( 'places' );
		case 'formulaire-inscription':
		case 'formulaire-contact':
			$champ = str_replace( '-', '_', $cle );
			$lien  = scoutsunite_unite( $champ );
			if ( '' === $lien ) {
				return 'formulaire-inscription' === $cle
					? '<mark class="a-completer">Ajoute le lien du formulaire d’inscription dans Réglages → Unité.</mark>'
					: '<mark class="a-completer">Ajoute le lien du formulaire de contact dans Réglages → Unité.</mark>';
			}
			$texte = 'formulaire-inscription' === $cle ? 'Remplir le formulaire d’inscription' : 'Écrire via le formulaire de contact';
			return '<a class="wp-block-button__link wp-element-button" href="' . esc_url( $lien ) . '">' . $texte . '</a>';
	}
	// Staffs de section : « baladins-chef », « baladins-email »…
	if ( preg_match( '/^([a-z]+)-(chef|email)$/', $cle, $m ) ) {
		return 'chef' === $m[2] ? scoutsunite_unite_texte( $m[1] . '_chef' ) : scoutsunite_unite_lien( $m[1] . '_email', 'mailto' );
	}
	return '';
}

/**
 * Toutes les clés de liaison, pour l'aperçu dans l'éditeur.
 *
 * @return array Clé => HTML.
 */
function scoutsunite_unite_toutes() {
	$cles = array( 'adresse', 'itineraire', 'staff-unite', 'responsable', 'email-staff', 'cotisation', 'places', 'formulaire-inscription', 'formulaire-contact' );
	foreach ( scoutsunite_agenda_sections() as $slug => $nom ) {
		if ( 'unite' !== $slug ) {
			$cles[] = $slug . '-chef';
			$cles[] = $slug . '-email';
		}
	}
	$html = array();
	foreach ( $cles as $cle ) {
		$html[ $cle ] = scoutsunite_unite_html( $cle );
	}
	return $html;
}

function scoutsunite_unite_bindings() {
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}
	register_block_bindings_source(
		'scoutsunite/unite',
		array(
			'label'              => 'Coordonnées de l’unité',
			'get_value_callback' => function ( $args ) {
				return scoutsunite_unite_html( $args['key'] ?? '' );
			},
		)
	);
}
add_action( 'init', 'scoutsunite_unite_bindings' );

/**
 * L'éditeur montre les vraies valeurs à la place des exemples.
 */
function scoutsunite_unite_editeur() {
	wp_add_inline_script(
		'scoutsunite-blocs',
		'window.scoutsuniteUnite = ' . wp_json_encode( scoutsunite_unite_toutes() ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'scoutsunite_unite_editeur' );

/* ---------------------------------------------------------------------------
   Réglages → Unité
   ------------------------------------------------------------------------ */

function scoutsunite_unite_reglages() {
	register_setting(
		'scoutsunite_unite',
		'scoutsunite_unite',
		array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => function ( $valeurs ) {
				$propre = array();
				foreach ( scoutsunite_unite_champs() as $groupe ) {
					foreach ( $groupe as $cle => $champ ) {
						$v              = isset( $valeurs[ $cle ] ) ? wp_unslash( $valeurs[ $cle ] ) : '';
						if ( 'email' === $champ[1] ) {
							$propre[ $cle ] = sanitize_email( $v );
						} elseif ( 'url' === $champ[1] ) {
							$propre[ $cle ] = esc_url_raw( $v );
						} elseif ( 'textarea' === $champ[1] ) {
							$propre[ $cle ] = sanitize_textarea_field( $v );
						} else {
							$propre[ $cle ] = sanitize_text_field( $v );
						}
					}
				}
				return $propre;
			},
		)
	);
}
add_action( 'admin_init', 'scoutsunite_unite_reglages' );

function scoutsunite_unite_menu() {
	add_options_page( 'Coordonnées de l’unité', 'Unité', 'manage_options', 'scoutsunite-unite', 'scoutsunite_unite_page' );
}
add_action( 'admin_menu', 'scoutsunite_unite_menu' );

function scoutsunite_unite_page() {
	?>
	<div class="wrap">
		<h1>Coordonnées de l’unité</h1>
		<p>Encode ces informations une seule fois : elles s’affichent d’elles-mêmes dans le pied de page, les infos pratiques de l’accueil, la page Contact et la page Inscription. Les liens des formulaires (Google Forms, extension de formulaire…) deviennent des boutons sur ces deux pages.
			Un champ laissé vide garde son exemple surligné en orange sur le site, pour qu’on voie ce qu’il reste à compléter.</p>
		<p>Le nom de l’unité se règle dans <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">Réglages → Général</a> (titre et slogan du site).</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'scoutsunite_unite' ); ?>
			<?php foreach ( scoutsunite_unite_champs() as $titre => $groupe ) : ?>
				<h2><?php echo esc_html( $titre ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $groupe as $cle => $champ ) : ?>
						<tr>
							<th scope="row"><label for="su-<?php echo esc_attr( $cle ); ?>"><?php echo esc_html( $champ[0] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $champ[1] ) : ?>
									<textarea class="large-text" rows="4" id="su-<?php echo esc_attr( $cle ); ?>" name="scoutsunite_unite[<?php echo esc_attr( $cle ); ?>]" placeholder="<?php echo esc_attr( $champ[2] ); ?>"><?php echo esc_textarea( scoutsunite_unite( $cle ) ); ?></textarea>
								<?php else : ?>
									<input class="regular-text" type="<?php echo esc_attr( $champ[1] ); ?>" id="su-<?php echo esc_attr( $cle ); ?>" name="scoutsunite_unite[<?php echo esc_attr( $cle ); ?>]" value="<?php echo esc_attr( scoutsunite_unite( $cle ) ); ?>" placeholder="<?php echo esc_attr( $champ[2] ); ?>">
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
			<?php submit_button( 'Enregistrer les coordonnées' ); ?>
		</form>
	</div>
	<?php
}
