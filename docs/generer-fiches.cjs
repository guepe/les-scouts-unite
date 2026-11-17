/**
 * Régénère les images et les deux PDF depuis les sources locales, sans réseau.
 * Dépendances de développement : Playwright avec Chromium, Python avec pypdf.
 * SCOUTSUNITE_PLAYWRIGHT et SCOUTSUNITE_PYTHON permettent de choisir une installation existante.
 */
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const os = require( 'node:os' );
const { pathToFileURL } = require( 'node:url' );
const { spawnSync } = require( 'node:child_process' );
const { chromium } = require( process.env.SCOUTSUNITE_PLAYWRIGHT || 'playwright' );

const livrets = [
	[ 'fiches-animateurs', [ '00-couverture', '01-ajouter-une-reunion', '02-annuler-une-reunion', '03-toutes-les-semaines', '04-ajouter-un-document', '05-ta-page-de-section', '06-ce-que-voient-les-parents', '07-ca-coince', '08-photos-camp', '09-souvenirs-parents' ] ],
	[ 'fiches-chefs-unite', [ '00-couverture', '01-donner-acces', '02-le-staff', '03-bon-a-savoir-whatsapp', '04-activite-unite', '05-documents-unite', '06-pages-et-menu', '07-reglages-mise-a-jour', '08-ca-coince', '09-photos-camp', '10-souvenirs-parents' ] ]
];

( async () => {
	const temporaire = fs.mkdtempSync( path.join( os.tmpdir(), 'scouts-fiches-' ) );
	const navigateur = await chromium.launch( { headless: true } );
	const page = await navigateur.newPage( { viewport: { width: 540, height: 960 }, deviceScaleFactor: 1 } );
	const erreurs = [];
	page.on( 'pageerror', erreur => erreurs.push( erreur.message ) );
	page.on( 'requestfailed', requete => erreurs.push( requete.url() ) );
	await page.route( /^https?:/, route => route.abort() );
	const assemblages = [];
	try {
		for ( const [ nom, images ] of livrets ) {
			const dossier = path.join( __dirname, nom );
			const plan = JSON.parse( fs.readFileSync( path.join( dossier, 'source/canvas.json' ), 'utf8' ) );
			if ( plan.order.length !== images.length ) { throw new Error( 'Nombre de fiches incohérent : ' + nom ); }
			const pages = [];
			for ( const [ indice, source ] of plan.order.entries() ) {
				await page.goto( pathToFileURL( path.join( dossier, 'source', source ) ).href );
				await page.evaluate( () => document.fonts.ready );
				await page.evaluate( () => Promise.all( Array.from( document.images ).map( image => image.decode() ) ) );
				const taille = await page.evaluate( () => ( { largeur: document.documentElement.scrollWidth, hauteur: document.documentElement.scrollHeight } ) );
				if ( taille.largeur > 540 || taille.hauteur > 960 ) { throw new Error( source + ' déborde : ' + JSON.stringify( taille ) ); }
				await page.screenshot( { path: path.join( dossier, images[ indice ] + '.png' ) } );
				const pdf = path.join( temporaire, nom + '-' + indice + '.pdf' );
				await page.pdf( { path: pdf, width: '540px', height: '960px', printBackground: true, preferCSSPageSize: true, margin: { top: 0, right: 0, bottom: 0, left: 0 } } );
				pages.push( pdf );
			}
			assemblages.push( { pages, sortie: path.join( dossier, nom + '.pdf' ), titre: plan.title } );
		}
		if ( erreurs.length ) { throw new Error( 'Ressources absentes : ' + erreurs.join( ', ' ) ); }
		const resultat = spawnSync( process.env.SCOUTSUNITE_PYTHON || 'python3', [ '-c', [
			'import json, sys',
			'from pypdf import PdfReader, PdfWriter',
			'for livret in json.load(sys.stdin):',
			'    resultat = PdfWriter()',
			'    for fichier in livret["pages"]:',
			'        source = PdfReader(fichier)',
			'        assert len(source.pages) == 1, "Une fiche occupe plusieurs pages : " + fichier',
			'        resultat.add_page(source.pages[0])',
			'    resultat.add_metadata({"/Title": livret["titre"], "/Author": "Les Scouts – Unité"})',
			'    with open(livret["sortie"], "wb") as sortie:',
			'        resultat.write(sortie)',
			'    print(livret["titre"] + " : " + str(len(resultat.pages)) + " pages")'
		].join( '\n' ) ], { input: JSON.stringify( assemblages ), encoding: 'utf8' } );
		if ( resultat.error ) { throw resultat.error; }
		if ( resultat.status !== 0 ) { throw new Error( resultat.stderr ); }
		process.stdout.write( resultat.stdout );
	} finally {
		await navigateur.close();
		fs.rmSync( temporaire, { recursive: true, force: true } );
	}
} )().catch( erreur => { console.error( erreur ); process.exitCode = 1; } );
