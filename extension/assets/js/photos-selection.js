/**
 * Choix des familles : seules les références sont conservées dans ce navigateur.
 * Aucune image, clé ou coordonnée n'est enregistrée dans localStorage.
 */
( function () {
	'use strict';
	var zone = document.getElementById( 'photos-selection' );
	if ( ! zone ) { return; }
	var cle = 'su_photos_selection_' + zone.getAttribute( 'data-album' );
	var choisies = [];
	var memoireDisponible = true;
	var occupe = false;
	var demandeEnvoyee = false;
	var liste = document.getElementById( 'photos-selection-liste' );
	var retour = document.getElementById( 'photos-selection-retour' );
	var panneau = document.getElementById( 'photos-selection-ouvrir' );
	var formulaire = document.getElementById( 'photos-demande-formulaire' );
	var requeteListe = null;
	var versionListe = 0;
	function lireMemoire() {
		try {
			var valeur = JSON.parse( window.localStorage.getItem( cle ) || '[]' );
			if ( ! Array.isArray( valeur ) || valeur.length > 1000 ) { return []; }
			return valeur.filter( function ( id, index ) { return typeof id === 'number' && id > 0 && Math.floor( id ) === id && valeur.indexOf( id ) === index; } );
		} catch ( erreur ) { memoireDisponible = false; return []; }
	}
	choisies = lireMemoire();
	function actualiser() {
		document.getElementById( 'photos-selection-nombre' ).textContent = choisies.length;
		// Rien à montrer tant qu'aucune photo n'est marquée : la grille reste seule à l'écran.
		zone.classList.toggle( 'photos__selection--vide', ! choisies.length && ! panneau.open );
		document.getElementById( 'photos-selection-zip' ).disabled = ! choisies.length || occupe;
		document.getElementById( 'photos-demande-envoyer' ).disabled = ! choisies.length || occupe || demandeEnvoyee;
		document.getElementById( 'photos-selection-tout' ).disabled = occupe;
		document.getElementById( 'photos-selection-vider' ).disabled = ! choisies.length || occupe;
		Array.prototype.forEach.call( zone.querySelectorAll( 'input[name="selection"]' ), function ( champ ) { champ.value = JSON.stringify( choisies ); } );
		Array.prototype.forEach.call( document.querySelectorAll( '.photos__marquer' ), function ( bouton ) {
			var id = Number( bouton.getAttribute( 'data-photo' ) );
			bouton.hidden = ! id;
			var present = choisies.indexOf( id ) !== -1;
			bouton.setAttribute( 'aria-pressed', present ? 'true' : 'false' );
			bouton.setAttribute( 'aria-label', ( present ? 'Enlever de ma sélection la photo ' : 'Marquer la photo ' ) + id );
			bouton.title = present ? 'Enlever de ma sélection' : 'Ajouter à ma sélection';
		} );
	}
	function enregistrer() {
		try { window.localStorage.setItem( cle, JSON.stringify( choisies ) ); }
		catch ( erreur ) { memoireDisponible = false; }
		actualiser();
		if ( ! memoireDisponible ) { retour.textContent = 'Ce navigateur ne peut pas mémoriser tes choix. Garde cette page ouverte jusqu’à l’export ou l’envoi de ta demande.'; }
	}
	function requete( operation, champs, suite ) {
		var donnees = new FormData();
		donnees.append( '_wpnonce', zone.getAttribute( 'data-nonce' ) );
		donnees.append( 'operation', operation );
		donnees.append( 'selection', JSON.stringify( choisies ) );
		Object.keys( champs || {} ).forEach( function ( nom ) { donnees.append( nom, champs[ nom ] ); } );
		var xhr = new XMLHttpRequest();
		xhr.open( 'POST', zone.getAttribute( 'data-url' ), true );
		xhr.timeout = 60000;
		xhr.onload = function () {
			var resultat;
			try { resultat = JSON.parse( xhr.responseText ); } catch ( erreur ) { resultat = null; }
			if ( xhr.status === 200 && resultat && resultat.success ) { suite( null, resultat.data ); }
			else { suite( resultat && resultat.data ? resultat.data.message : 'La demande n’a pas abouti. Vérifie ta connexion et réessaie.' ); }
		};
		xhr.onerror = xhr.ontimeout = function () { suite( 'La connexion a été interrompue. Tu peux réessayer ; une même demande ne sera pas enregistrée deux fois.' ); };
		xhr.send( donnees );
		return xhr;
	}
	function apercu() {
		var version = ++versionListe;
		if ( requeteListe ) { requeteListe.abort(); requeteListe = null; }
		liste.textContent = '';
		if ( ! choisies.length ) { retour.textContent = 'Aucune photo marquée pour le moment.'; return; }
		retour.textContent = 'Vérification de ta sélection…';
		requeteListe = requete( 'lire', {}, function ( erreur, resultat ) {
			if ( version !== versionListe ) { return; }
			requeteListe = null;
			if ( erreur ) { retour.textContent = erreur; return; }
			var avant = choisies.length;
			choisies = resultat.photos.map( function ( photo ) { return photo.id; } );
			enregistrer();
			retour.textContent = avant > choisies.length ? 'Les photos devenues indisponibles ont été écartées de ta sélection.' : choisies.length + ' photo(s) sélectionnée(s).';
			resultat.photos.forEach( function ( photo ) {
				var ligne = document.createElement( 'li' );
				var lien = document.createElement( 'a' );
				lien.href = photo.voir;
				lien.className = 'photos__ouvrir';
				lien.setAttribute( 'data-photo', photo.id );
				lien.setAttribute( 'data-selection', '1' );
				var miniature = document.createElement( 'img' );
				miniature.setAttribute( 'loading', 'lazy' );
				miniature.src = photo.mini;
				miniature.alt = photo.legende;
				lien.appendChild( miniature );
				var libelle = document.createElement( 'span' );
				libelle.textContent = 'Photo ' + photo.id;
				lien.appendChild( libelle );
				ligne.appendChild( lien );
				var retirer = document.createElement( 'button' );
				retirer.type = 'button';
				retirer.className = 'photos__secondaire';
				retirer.textContent = 'Enlever de ma sélection';
				retirer.addEventListener( 'click', function () { modifier( photo.id ); } );
				ligne.appendChild( retirer );
				liste.appendChild( ligne );
			} );
		} );
	}
	function modifier( id ) {
		var index = choisies.indexOf( id );
		if ( index === -1 ) {
			if ( choisies.length >= 1000 ) { retour.textContent = 'Choisis au plus 1 000 photos dans cette sélection.'; return; }
			choisies.push( id );
		} else { choisies.splice( index, 1 ); }
		enregistrer();
		if ( panneau.open ) { apercu(); }
	}
	document.addEventListener( 'click', function ( evenement ) {
		var bouton = evenement.target;
		while ( bouton && bouton !== document && ( ! bouton.classList || ! bouton.classList.contains( 'photos__marquer' ) ) ) { bouton = bouton.parentNode; }
		if ( bouton && bouton !== document ) {
			evenement.preventDefault();
			var id = Number( bouton.getAttribute( 'data-photo' ) );
			if ( id ) { modifier( id ); }
		}
	} );
	panneau.addEventListener( 'toggle', function () { actualiser(); if ( panneau.open ) { apercu(); } } );
	document.addEventListener( 'scoutsunite-photo-affichee', actualiser );
	window.addEventListener( 'storage', function ( evenement ) { if ( evenement.key === cle ) { choisies = lireMemoire(); actualiser(); if ( panneau.open ) { apercu(); } } } );
	document.getElementById( 'photos-selection-tout' ).addEventListener( 'click', function () {
		occupe = true; actualiser();
		requete( 'tout', {}, function ( erreur, resultat ) {
			occupe = false;
			if ( erreur ) { retour.textContent = erreur; actualiser(); return; }
			choisies = resultat.ids; enregistrer(); apercu();
		} );
	} );
	document.getElementById( 'photos-selection-vider' ).addEventListener( 'click', function () {
		if ( ! window.confirm( 'Effacer tous tes choix pour cet album ? Les photos resteront dans l’album.' ) ) { return; }
		choisies = []; enregistrer(); apercu();
	} );
	formulaire.addEventListener( 'submit', function ( evenement ) {
		evenement.preventDefault();
		if ( occupe || demandeEnvoyee || ! choisies.length ) { return; }
		var champs = {};
		[ 'nom', 'email', 'message', 'reference' ].forEach( function ( nom ) { champs[ nom ] = formulaire.elements[ nom ].value; } );
		occupe = true; actualiser();
		var message = document.getElementById( 'photos-demande-retour' );
		message.textContent = 'Enregistrement de ta demande…';
		requete( 'demander', champs, function ( erreur, resultat ) {
			occupe = false;
			if ( erreur ) { message.textContent = erreur; actualiser(); return; }
			demandeEnvoyee = true;
			message.textContent = resultat.message + ' Référence : ' + resultat.reference + '.';
			document.getElementById( 'photos-demande-envoyer' ).textContent = 'Demande enregistrée';
			actualiser();
		} );
	} );
	zone.hidden = false;
	actualiser();
} )();
