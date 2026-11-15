/**
 * Photos de camp : pellicule, file d'envoi, reprise des échecs et visionneuse.
 * Sans build ni dépendance. Les photos sélectionnées restent en mémoire, jamais dans localStorage.
 */
( function () {
	'use strict';

	var acces = document.getElementById( 'photos-acces' );
	var fragment = /^#cle=([a-f0-9]{36})$/.exec( window.location.hash );
	if ( fragment ) {
		// Retirer le secret avant toute interaction et avant de soumettre le formulaire par POST.
		window.history.replaceState( null, '', window.location.pathname + window.location.search );
		if ( acces ) {
			document.getElementById( 'photos-cle' ).value = fragment[1];
			acces.submit();
		}
		fragment = null;
	}

	Array.prototype.forEach.call( document.querySelectorAll( '[data-confirmer]' ), function ( bouton ) {
		bouton.addEventListener( 'click', function ( evenement ) {
			if ( ! window.confirm( bouton.getAttribute( 'data-confirmer' ) ) ) {
				evenement.preventDefault();
			}
		} );
	} );

	var filtreSection = document.getElementById( 'photos-filtre-section' );
	var filtreAnnee = document.getElementById( 'photos-filtre-annee' );
	function filtrer() {
		Array.prototype.forEach.call( document.querySelectorAll( '.photos__albums li' ), function ( ligne ) {
			ligne.hidden = ( filtreSection.value && ligne.getAttribute( 'data-section' ) !== filtreSection.value ) || ( filtreAnnee.value && ligne.getAttribute( 'data-annee' ) !== filtreAnnee.value );
		} );
	}
	if ( filtreSection ) {
		filtreSection.addEventListener( 'change', filtrer );
		filtreAnnee.addEventListener( 'change', filtrer );
	}

	var copier = document.getElementById( 'photos-copier' );
	var tout = document.getElementById( 'photos-tout' );
	if ( tout ) {
		tout.addEventListener( 'change', function () {
			Array.prototype.forEach.call( document.querySelectorAll( 'input[name="photos[]"]' ), function ( casePhoto ) { casePhoto.checked = tout.checked; } );
		} );
	}
	if ( copier ) {
		copier.addEventListener( 'click', function () {
			var message = document.getElementById( 'photos-message' );
			var retour = document.getElementById( 'photos-copie' );
			function selectionner() {
				message.focus();
				message.select();
				retour.textContent = 'Le message est sélectionné. Copie-le puis colle-le dans ton groupe de parents.';
			}
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( message.value ).then( function () {
					retour.textContent = 'Message copié. Tu peux le coller dans ton groupe de parents.';
				}, selectionner );
			} else {
				selectionner();
			}
		} );
	}

	var zone = document.getElementById( 'photos-envoi' );
	if ( zone ) {
		var selection = [];
		var actif = false;
		var arret = false;
		var maximum = Number( zone.getAttribute( 'data-maximum' ) );
		var liste = document.getElementById( 'photos-file' );
		var envoyer = document.getElementById( 'photos-envoyer' );
		var arreter = document.getElementById( 'photos-arreter' );
		var progression = document.getElementById( 'photos-progression' );
		var barre = document.getElementById( 'photos-progress' );
		var voir = document.getElementById( 'photos-voir' );
		var fichiers = document.getElementById( 'photos-fichiers' );
		var camera = document.getElementById( 'photos-camera' );

		function actualiser() {
			var recus = selection.filter( function ( element ) { return element.etat === 'recu'; } ).length;
			var restants = selection.filter( function ( element ) { return element.etat !== 'recu'; } ).length;
			envoyer.disabled = actif || ! restants;
			envoyer.textContent = recus || selection.some( function ( element ) { return element.etat === 'erreur'; } ) ? 'Publier les photos restantes' : 'Publier les photos';
			arreter.hidden = ! actif;
			voir.hidden = ! recus;
			fichiers.disabled = actif;
			camera.disabled = actif;
			document.getElementById( 'photos-tags' ).disabled = actif;
			document.getElementById( 'photos-legende' ).disabled = actif;
			if ( ! actif ) { arreter.textContent = 'Arrêter après cette photo'; }
			if ( ! actif ) {
				progression.textContent = selection.length ? recus + ' sur ' + selection.length + ' photos publiées · ' + restants + ' restante(s).' : '';
			}
		}

		function ajouter( entree ) {
			Array.prototype.forEach.call( entree.files || [], function ( fichier ) {
				var identifiant = fichier.name + ':' + fichier.size + ':' + fichier.lastModified;
				if ( selection.some( function ( element ) { return element.identifiant === identifiant; } ) ) {
					return;
				}
				if ( selection.length >= 50 ) {
					progression.textContent = 'Choisis au plus 50 photos à la fois. Tu pourras en ajouter ensuite.';
					return;
				}
				var ligne = document.createElement( 'li' );
				var texte = document.createElement( 'span' );
				var statut = document.createElement( 'span' );
				var retirer = document.createElement( 'button' );
				var element = { fichier: fichier, identifiant: identifiant, etat: 'attente', ligne: ligne, statut: statut, apercu: '' };
				texte.textContent = fichier.name;
				statut.textContent = fichier.size > maximum ? 'Trop lourde : choisis une photo plus légère.' : 'Prête à publier';
				if ( fichier.size > maximum ) {
					element.etat = 'erreur';
				}
				if ( window.URL && /^image\/(jpeg|png|webp)$/.test( fichier.type ) ) {
					var image = document.createElement( 'img' );
					element.apercu = window.URL.createObjectURL( fichier );
					image.src = element.apercu;
					image.alt = '';
					ligne.appendChild( image );
				}
				retirer.type = 'button';
				retirer.textContent = 'Retirer';
				retirer.setAttribute( 'aria-label', 'Retirer ' + fichier.name );
				retirer.addEventListener( 'click', function () {
					if ( actif ) {
						return;
					}
					if ( element.apercu ) {
						window.URL.revokeObjectURL( element.apercu );
					}
					selection.splice( selection.indexOf( element ), 1 );
					ligne.parentNode.removeChild( ligne );
					actualiser();
				} );
				ligne.appendChild( texte );
				ligne.appendChild( statut );
				ligne.appendChild( retirer );
				liste.appendChild( ligne );
				selection.push( element );
			} );
			entree.value = '';
			actualiser();
		}
		fichiers.addEventListener( 'change', function () { ajouter( fichiers ); } );
		camera.addEventListener( 'change', function () { ajouter( camera ); } );

		function terminer() {
			actif = false;
			barre.hidden = true;
			actualiser();
		}
		function envoyerPhoto( element, suite, tentative ) {
			var requete = new XMLHttpRequest();
			var donnees = new FormData();
			donnees.append( 'action', 'scoutsunite_photos' );
			donnees.append( 'nonce', zone.getAttribute( 'data-nonce' ) );
			donnees.append( 'album', zone.getAttribute( 'data-album' ) );
			donnees.append( 'legende', document.getElementById( 'photos-legende' ).value );
			donnees.append( 'tags', document.getElementById( 'photos-tags' ).value );
			donnees.append( 'fichier', element.fichier );
			requete.open( 'POST', zone.getAttribute( 'data-url' ), true );
			requete.timeout = 120000;
			element.statut.textContent = 'Publication en cours…';
			barre.value = 0;
			barre.hidden = false;
			requete.upload.onprogress = function ( evenement ) {
				if ( evenement.lengthComputable ) {
					barre.value = Math.round( 100 * evenement.loaded / evenement.total );
				}
			};
			function echec( message ) {
				element.etat = 'erreur';
				element.statut.textContent = message;
				suite();
			}
			requete.onload = function () {
				var retour;
				try { retour = JSON.parse( requete.responseText ); } catch ( erreur ) { retour = null; }
				if ( requete.status === 409 && tentative < 3 && ! arret ) {
					element.statut.textContent = 'Un autre envoi est en cours. Nouvel essai…';
					window.setTimeout( function () { envoyerPhoto( element, suite, tentative + 1 ); }, 1000 * ( tentative + 1 ) );
					return;
				}
				if ( retour && retour.success ) {
					element.etat = 'recu';
					element.statut.textContent = retour.data.doublon ? 'Déjà publiée dans cet album' : 'Publiée dans l’album';
					suite();
				} else {
					if ( requete.status === 403 || requete.responseText === '0' ) {
						arret = true;
					}
					echec( retour && retour.data && retour.data.message ? retour.data.message : 'Envoi non confirmé. Vérifie ta connexion ou recharge la page.' );
				}
			};
			requete.onerror = function () { arret = true; echec( 'Connexion interrompue. Tu peux réessayer cette photo.' ); };
			requete.ontimeout = function () { arret = true; echec( 'L’envoi prend trop de temps. Réessaie : une photo déjà reçue ne sera pas ajoutée deux fois.' ); };
			requete.send( donnees );
		}
		envoyer.addEventListener( 'click', function () {
			var file = selection.filter( function ( element ) { return element.etat !== 'recu' && element.fichier.size <= maximum; } );
			var position = 0;
			if ( ! file.length ) {
				progression.textContent = 'Retire les photos trop lourdes et choisis une version plus légère.';
				return;
			}
			actif = true;
			arret = false;
			actualiser();
			function suivante() {
				if ( arret || position >= file.length ) {
					terminer();
					return;
				}
				progression.textContent = 'Photo ' + ( position + 1 ) + ' sur ' + file.length + ' en cours de publication…';
				envoyerPhoto( file[ position++ ], suivante, 0 );
			}
			suivante();
		} );
		arreter.addEventListener( 'click', function () { arret = true; arreter.textContent = 'Arrêt après la photo en cours…'; } );
		window.addEventListener( 'beforeunload', function ( evenement ) {
			if ( actif ) {
				evenement.preventDefault();
				evenement.returnValue = '';
			}
		} );
		window.addEventListener( 'pagehide', function () {
			selection.forEach( function ( element ) { if ( element.apercu ) { window.URL.revokeObjectURL( element.apercu ); } } );
		} );
	}

	var dialogue = document.getElementById( 'photos-visionneuse' );
	if ( dialogue && typeof dialogue.showModal === 'function' ) {
		var liens = Array.prototype.slice.call( document.querySelectorAll( '.photos__ouvrir' ) );
		var positionVue = 0;
		var imageVue = document.getElementById( 'photos-vue-image' );
		var erreurVue = document.getElementById( 'photos-vue-erreur' );
		var precedente = document.getElementById( 'photos-precedente' );
		var suivanteVue = document.getElementById( 'photos-suivante' );
		function afficher( position ) {
			positionVue = position;
			var lien = liens[ position ];
			erreurVue.textContent = '';
			imageVue.alt = lien.getAttribute( 'data-legende' );
			imageVue.src = lien.getAttribute( 'data-image' );
			document.getElementById( 'photos-vue-titre' ).textContent = 'Photo ' + ( position + 1 ) + ' sur ' + liens.length + ' de cette page';
			document.getElementById( 'photos-telecharger' ).href = lien.getAttribute( 'data-telecharger' );
			document.getElementById( 'photos-retrait' ).href = lien.getAttribute( 'data-retrait' );
			precedente.disabled = position === 0;
			suivanteVue.disabled = position === liens.length - 1;
		}
		imageVue.addEventListener( 'error', function () {
			erreurVue.textContent = 'Cette photo n’est plus accessible. Recharge l’album : l’accès a peut-être changé ou la photo a été retirée.';
			imageVue.removeAttribute( 'src' );
		} );
		liens.forEach( function ( lien, position ) {
			lien.addEventListener( 'click', function ( evenement ) {
				evenement.preventDefault();
				afficher( position );
				dialogue.showModal();
			} );
		} );
		document.getElementById( 'photos-fermer' ).addEventListener( 'click', function () { dialogue.close(); } );
		dialogue.addEventListener( 'close', function () {
			imageVue.removeAttribute( 'src' );
			if ( liens[ positionVue ] ) { liens[ positionVue ].focus(); }
		} );
		precedente.addEventListener( 'click', function () { if ( positionVue > 0 ) { afficher( positionVue - 1 ); } } );
		suivanteVue.addEventListener( 'click', function () { if ( positionVue + 1 < liens.length ) { afficher( positionVue + 1 ); } } );
		dialogue.addEventListener( 'keydown', function ( evenement ) {
			if ( evenement.key === 'ArrowLeft' && positionVue > 0 ) { afficher( positionVue - 1 ); }
			if ( evenement.key === 'ArrowRight' && positionVue + 1 < liens.length ) { afficher( positionVue + 1 ); }
		} );
	}
	window.addEventListener( 'pageshow', function ( evenement ) {
		if ( evenement.persisted ) { window.location.reload(); }
	} );
} )();
