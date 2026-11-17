/**
 * Photos de camp : pellicule, file d'envoi, reprise des échecs et visionneuse.
 * Sans build ni dépendance. Les photos sélectionnées restent en mémoire, jamais dans localStorage.
 */
( function () {
	'use strict';

	function ouvrirDepuisFragment() {
		var fragment = /^#cle=([a-f0-9]{36})$/.exec( window.location.hash );
		if ( ! fragment ) {
			return;
		}
		var acces = document.getElementById( 'photos-acces' );
		// Retirer le secret avant toute interaction et avant de soumettre le formulaire par POST.
		window.history.replaceState( null, '', window.location.pathname + window.location.search );
		if ( acces ) {
			document.getElementById( 'photos-cle' ).value = fragment[1];
			acces.submit();
		}
		fragment = null;
	}
	window.addEventListener( 'hashchange', ouvrirDepuisFragment );
	ouvrirDepuisFragment();

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
		var sectionDemandee = /[?&]section=([a-z]+)(?:&|$)/.exec( window.location.search );
		if ( sectionDemandee && filtreSection.querySelector( 'option[value="' + sectionDemandee[1] + '"]' ) ) { filtreSection.value = sectionDemandee[1]; filtrer(); }
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
	if ( dialogue ) {
		var natif = typeof dialogue.showModal === 'function';
		if ( ! natif ) {
			dialogue.classList.add( 'photos__dialogue--repli' );
			dialogue.setAttribute( 'role', 'dialog' );
			dialogue.setAttribute( 'aria-modal', 'true' );
			dialogue.showModal = function () { dialogue.setAttribute( 'open', '' ); dialogue.open = true; document.getElementById( 'photos-fermer' ).focus(); };
			dialogue.close = function () {
				dialogue.removeAttribute( 'open' ); dialogue.open = false;
				var evenement = document.createEvent( 'Event' ); evenement.initEvent( 'close', false, false ); dialogue.dispatchEvent( evenement );
			};
		}
		var lienDepart = null;
		var requeteVue = null;
		var chargementVue = 0;
		var occupeVue = false;
		var reserveVue = null;
		var imageEnChargement = null;
		var delaiImage = null;
		var urlVue = dialogue.getAttribute( 'data-url' );
		var photoPrecedente = 0;
		var photoSuivante = 0;
		var cadreVue = document.getElementById( 'photos-vue-cadre' );
		var imageVue = document.getElementById( 'photos-vue-image' );
		var erreurVue = document.getElementById( 'photos-vue-erreur' );
		var precedente = document.getElementById( 'photos-precedente' );
		var suivanteVue = document.getElementById( 'photos-suivante' );
		var telechargerVue = document.getElementById( 'photos-telecharger' );
		var marquerVue = document.getElementById( 'photos-marquer-vue' );
		function viderReserve() {
			if ( reserveVue ) { reserveVue.image.onload = null; reserveVue.image.onerror = null; reserveVue.image.removeAttribute( 'src' ); reserveVue = null; }
		}
		function annulerImage() {
			window.clearTimeout( delaiImage ); delaiImage = null;
			if ( imageEnChargement ) { imageEnChargement.onload = null; imageEnChargement.onerror = null; imageEnChargement.removeAttribute( 'src' ); imageEnChargement = null; }
		}
		function restaurerCommandes() {
			occupeVue = false;
			cadreVue.setAttribute( 'aria-busy', 'false' );
			precedente.disabled = ! photoPrecedente;
			suivanteVue.disabled = ! photoSuivante;
		}
		function erreurVisionneuse( message, retirer ) {
			annulerImage();
			erreurVue.textContent = message || 'Cette photo n’est plus accessible. Recharge l’album : l’accès a peut-être changé ou la photo a été retirée.';
			if ( retirer ) {
				Array.prototype.forEach.call( cadreVue.querySelectorAll( 'img' ), function ( image ) { image.removeAttribute( 'src' ); } );
				telechargerVue.removeAttribute( 'href' );
				marquerVue.hidden = true;
				marquerVue.removeAttribute( 'data-photo' );
				photoPrecedente = 0; photoSuivante = 0; viderReserve();
			}
			restaurerCommandes();
		}
		function adresseImage( id ) { return urlVue + ( urlVue.indexOf( '?' ) === -1 ? '?' : '&' ) + 'fichier=' + encodeURIComponent( id ); }
		function afficher( id, sens ) {
			if ( requeteVue ) { requeteVue.abort(); }
			var numero = ++chargementVue;
			annulerImage();
			var focusAvant = document.activeElement;
			if ( focusAvant === precedente || focusAvant === suivanteVue ) { dialogue.focus(); }
			occupeVue = true;
			precedente.disabled = true; suivanteVue.disabled = true;
			cadreVue.setAttribute( 'aria-busy', 'true' );
			// Conserver l'image et son titre pendant le chargement : aucune zone blanche entre les photos.
			erreurVue.textContent = 'Chargement de la photo…';
			var requete = new XMLHttpRequest();
			requeteVue = requete;
			requete.open( 'GET', urlVue + ( urlVue.indexOf( '?' ) === -1 ? '?' : '&' ) + 'visionneuse=' + encodeURIComponent( id ), true );
			requete.timeout = 30000;
			requete.onload = function () {
				if ( numero !== chargementVue || ! dialogue.open ) { return; }
				requeteVue = null;
				var retour;
				try { retour = JSON.parse( requete.responseText ); } catch ( erreur ) { retour = null; }
				if ( requete.status !== 200 || ! retour || ! retour.success ) {
					erreurVisionneuse( retour && retour.data ? retour.data.message : '', requete.status === 403 ); return;
				}
				var vue = retour.data;
				var nouvelle = reserveVue && reserveVue.id === vue.photo ? reserveVue.image : new Image();
				if ( reserveVue && reserveVue.image === nouvelle ) { reserveVue = null; }
				imageEnChargement = nouvelle;
				var enPreparation = false;
				function poserImage() {
					if ( numero !== chargementVue || ! dialogue.open ) { return; }
					window.clearTimeout( delaiImage ); delaiImage = null;
					nouvelle.onload = null; nouvelle.onerror = null;
					imageEnChargement = null;
					var ancienne = imageVue;
					ancienne.removeAttribute( 'id' ); ancienne.alt = ''; ancienne.setAttribute( 'aria-hidden', 'true' );
					nouvelle.id = 'photos-vue-image'; nouvelle.alt = vue.legende; nouvelle.className = 'photos__image-arrive' + ( sens ? ( sens > 0 ? ' photos__image-arrive--suivante' : ' photos__image-arrive--precedente' ) : '' );
					cadreVue.appendChild( nouvelle ); imageVue = nouvelle;
					window.requestAnimationFrame( function () { nouvelle.className = ''; } );
					window.setTimeout( function () { if ( ancienne.parentNode ) { ancienne.parentNode.removeChild( ancienne ); } ancienne.removeAttribute( 'src' ); }, 160 );
					erreurVue.textContent = '';
					telechargerVue.href = vue.telecharger;
					document.getElementById( 'photos-vue-titre' ).textContent = vue.position + ' sur ' + vue.total;
					document.getElementById( 'photos-vue-legende' ).textContent = vue.legende === 'Souvenir du camp' ? '' : vue.legende;
					photoPrecedente = vue.precedente; photoSuivante = vue.suivante;
					marquerVue.setAttribute( 'data-photo', vue.selectionnable ? vue.photo : '' );
					restaurerCommandes();
					if ( document.activeElement === dialogue && ( focusAvant === precedente || focusAvant === suivanteVue ) ) {
						( focusAvant.disabled ? document.getElementById( 'photos-fermer' ) : focusAvant ).focus();
					}
					var evenement = document.createEvent( 'Event' ); evenement.initEvent( 'scoutsunite-photo-affichee', false, false ); document.dispatchEvent( evenement );
					// Une seule photo d'avance en mémoire. Chaque changement revérifie néanmoins la session côté serveur.
					viderReserve();
					if ( vue.suivante ) {
						var imageSuivante = new Image();
						reserveVue = { id: vue.suivante, image: imageSuivante };
						imageSuivante.src = adresseImage( vue.suivante );
					}
				}
				function imageChargee() {
					if ( enPreparation ) { return; } enPreparation = true;
					if ( typeof nouvelle.decode === 'function' ) { nouvelle.decode().then( poserImage, function () { if ( numero === chargementVue && dialogue.open ) { erreurVisionneuse( '', true ); } } ); }
					else { poserImage(); }
				}
				nouvelle.onload = imageChargee;
				nouvelle.onerror = function () { if ( numero === chargementVue && dialogue.open ) { erreurVisionneuse( '', true ); } };
				delaiImage = window.setTimeout( function () { if ( numero === chargementVue && dialogue.open ) { erreurVisionneuse( 'La photo met trop de temps à arriver. Vérifie ta connexion, puis réessaie.', false ); } }, 30000 );
				if ( nouvelle.complete && nouvelle.naturalWidth ) { imageChargee(); }
				else if ( nouvelle.complete || ! nouvelle.getAttribute( 'src' ) ) { nouvelle.removeAttribute( 'src' ); nouvelle.src = vue.image; }
			};
			requete.onerror = requete.ontimeout = function () {
				if ( numero === chargementVue && dialogue.open ) { requeteVue = null; erreurVisionneuse( 'La photo n’a pas pu être chargée. Vérifie ta connexion et réessaie.', false ); }
			};
			requete.send();
		}
		function ouvrirVue( id, lien, selection ) {
			lienDepart = lien;
			urlVue = dialogue.getAttribute( selection ? 'data-album-url' : 'data-url' );
			document.body.classList.add( 'photos--visionneuse' );
			if ( ! dialogue.open ) { dialogue.showModal(); }
			afficher( id );
		}
		// Délégation : fonctionne aussi pour les photos ajoutées à « Ma sélection » après ouverture de la page.
		document.addEventListener( 'click', function ( evenement ) {
			var lien = evenement.target;
			while ( lien && lien !== document && ( ! lien.classList || ! lien.classList.contains( 'photos__ouvrir' ) ) ) { lien = lien.parentNode; }
			if ( ! lien || lien === document || evenement.ctrlKey || evenement.metaKey || evenement.shiftKey || evenement.altKey ) { return; }
			evenement.preventDefault(); ouvrirVue( lien.getAttribute( 'data-photo' ), lien, lien.getAttribute( 'data-selection' ) === '1' );
		} );
		document.getElementById( 'photos-fermer' ).addEventListener( 'click', function () { dialogue.close(); } );
		dialogue.addEventListener( 'close', function () {
			++chargementVue;
			if ( requeteVue ) { requeteVue.abort(); requeteVue = null; }
			annulerImage();
			viderReserve();
			Array.prototype.forEach.call( cadreVue.querySelectorAll( 'img' ), function ( image ) { image.removeAttribute( 'src' ); } );
			telechargerVue.removeAttribute( 'href' );
			document.body.classList.remove( 'photos--visionneuse' );
			if ( lienDepart ) { lienDepart.focus(); }
		} );
		precedente.addEventListener( 'click', function () { if ( ! occupeVue && photoPrecedente ) { afficher( photoPrecedente, -1 ); } } );
		suivanteVue.addEventListener( 'click', function () { if ( ! occupeVue && photoSuivante ) { afficher( photoSuivante, 1 ); } } );
		dialogue.addEventListener( 'keydown', function ( evenement ) {
			if ( evenement.key === 'ArrowLeft' && ! occupeVue && photoPrecedente ) { evenement.preventDefault(); afficher( photoPrecedente, -1 ); }
			if ( evenement.key === 'ArrowRight' && ! occupeVue && photoSuivante ) { evenement.preventDefault(); afficher( photoSuivante, 1 ); }
			if ( ! natif && evenement.key === 'Escape' ) { evenement.preventDefault(); dialogue.close(); }
			if ( ! natif && evenement.key === 'Tab' ) {
				var controles = Array.prototype.filter.call( dialogue.querySelectorAll( 'button:not([disabled]), a[href]' ), function ( element ) { return ! element.hidden; } );
				var premier = controles[0]; var dernier = controles[ controles.length - 1 ];
				if ( evenement.shiftKey && ( document.activeElement === premier || document.activeElement === dialogue ) ) { evenement.preventDefault(); dernier.focus(); }
				else if ( ! evenement.shiftKey && document.activeElement === dernier ) { evenement.preventDefault(); premier.focus(); }
			}
		} );
		var departToucher = null;
		cadreVue.addEventListener( 'touchstart', function ( evenement ) { departToucher = evenement.touches.length === 1 ? { x: evenement.touches[0].clientX, y: evenement.touches[0].clientY } : null; }, { passive: true } );
		cadreVue.addEventListener( 'touchcancel', function () { departToucher = null; } );
		cadreVue.addEventListener( 'touchend', function ( evenement ) {
			if ( ! departToucher || ! evenement.changedTouches.length ) { return; }
			var dx = evenement.changedTouches[0].clientX - departToucher.x; var dy = evenement.changedTouches[0].clientY - departToucher.y; departToucher = null;
			// Comme dans Photos sur iPhone : glisser vers le bas ferme la photo.
			if ( dy > 90 && dy > Math.abs( dx ) * 1.5 ) { dialogue.close(); return; }
			if ( occupeVue || Math.abs( dx ) < 50 || Math.abs( dx ) < Math.abs( dy ) * 1.3 ) { return; }
			if ( dx < 0 && photoSuivante ) { afficher( photoSuivante, 1 ); }
			if ( dx > 0 && photoPrecedente ) { afficher( photoPrecedente, -1 ); }
		}, { passive: true } );
		var vueServeur = document.querySelector( '.photos__vue[data-photo]' );
		if ( vueServeur ) {
			var idInitial = vueServeur.getAttribute( 'data-photo' );
			vueServeur.hidden = true;
			ouvrirVue( idInitial, document.querySelector( '.photos__ouvrir[data-photo="' + idInitial + '"]' ), false );
		}
	}
	// Défilement continu pour les familles : la page suivante est lue en arrière-plan et ses photos s'ajoutent à la grille.
	// Sans JavaScript ou sans IntersectionObserver, les liens « Photos suivantes » restent utilisables.
	var grilleFamille = document.querySelector( '.photos__galerie--famille .photos__grille' );
	var pagination = document.querySelector( '.photos__galerie--famille .photos__pagination' );
	if ( grilleFamille && pagination && window.IntersectionObserver && window.DOMParser ) {
		var lienSuite = pagination.querySelector( 'a[rel="next"]' );
		var charge = false;
		var sentinelle = document.createElement( 'div' );
		sentinelle.className = 'photos__suite';
		sentinelle.setAttribute( 'aria-hidden', 'true' );
		grilleFamille.parentNode.insertBefore( sentinelle, pagination );
		pagination.hidden = true;
		var observateur = new IntersectionObserver( function ( entrees ) {
			if ( ! entrees[0].isIntersecting || charge || ! lienSuite ) { return; }
			charge = true;
			var xhr = new XMLHttpRequest();
			xhr.open( 'GET', lienSuite.href, true );
			xhr.timeout = 30000;
			function echec() { charge = false; pagination.hidden = false; observateur.disconnect(); }
			xhr.onload = function () {
				if ( xhr.status !== 200 ) { echec(); return; }
				var page = new DOMParser().parseFromString( xhr.responseText, 'text/html' );
				Array.prototype.forEach.call( page.querySelectorAll( '.photos__galerie--famille .photos__grille > figure' ), function ( figure ) {
					grilleFamille.appendChild( document.importNode( figure, true ) );
				} );
				lienSuite = page.querySelector( '.photos__galerie--famille .photos__pagination a[rel="next"]' );
				if ( ! lienSuite ) { observateur.disconnect(); sentinelle.parentNode.removeChild( sentinelle ); }
				charge = false;
				// Les cœurs des nouvelles photos reprennent l'état de la sélection.
				var evenement = document.createEvent( 'Event' ); evenement.initEvent( 'scoutsunite-photo-affichee', false, false ); document.dispatchEvent( evenement );
			};
			xhr.onerror = xhr.ontimeout = echec;
			xhr.send();
		}, { rootMargin: '900px 0px' } );
		if ( lienSuite ) { observateur.observe( sentinelle ); } else { sentinelle.parentNode.removeChild( sentinelle ); }
	}
	window.addEventListener( 'pageshow', function ( evenement ) {
		if ( evenement.persisted ) { window.location.reload(); }
	} );
} )();
