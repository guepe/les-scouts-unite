/**
 * Panneau « Quand et où ? » dans l'éditeur d'une réunion.
 * Les valeurs sont les champs su_* enregistrés par inc/agenda.php.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var useEffect = wp.element.useEffect;
	var C = wp.components;
	var Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || wp.editPost.PluginDocumentSettingPanel;

	var sections = window.scoutsuniteSections || {};
	var options = [ { label: 'Choisir…', value: '' } ].concat(
		Object.keys( sections ).map( function ( slug ) {
			return { label: sections[ slug ], value: slug };
		} )
	);

	function QuandEtOu() {
		var meta = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {};
		}, [] );
		var titre = useSelect( function ( select ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'title' ) || '';
		}, [] );
		var editor = useDispatch( 'core/editor' );

		function set( key ) {
			return function ( value ) {
				var m = {};
				m[ key ] = value;
				editor.editPost( { meta: m } );
			};
		}

		// Le panneau s'ouvre de lui-même : sans lui, la réunion n'a ni date ni section.
		var panneau = 'scoutsunite-reunion/scoutsunite-quand';
		var ouvert = useSelect( function ( select ) {
			return select( 'core/editor' ).isEditorPanelOpened( panneau );
		}, [] );
		var editPost = useDispatch( 'core/edit-post' );
		var montage = wp.element.useRef( Date.now() );
		// Les préférences de l'utilisateur arrivent après le premier affichage et peuvent refermer
		// le panneau : on le rouvre pendant les premières secondes, puis on laisse faire l'utilisateur.
		useEffect(
			function () {
				if ( ! ouvert && editor.toggleEditorPanelOpened && Date.now() - montage.current < 4000 ) {
					editor.toggleEditorPanelOpened( panneau );
				}
			},
			[ ouvert ]
		);
		useEffect( function () {
			// Réunion encore vide : on montre le panneau latéral, même s'il avait été fermé.
			if ( ( ! meta.su_section || ! meta.su_date ) && editPost && editPost.openGeneralSidebar ) {
				editPost.openGeneralSidebar( 'edit-post/document' );
			}
		}, [] );

		var plusieursJours = 'weekend' === meta.su_type || 'camp' === meta.su_type;

		// Pas de publication sans section ni date : la réunion n'apparaîtrait nulle part.
		var incomplet = ! meta.su_section || ! meta.su_date;
		useEffect(
			function () {
				if ( incomplet ) {
					editor.lockPostSaving( 'scoutsunite-reunion' );
				} else {
					editor.unlockPostSaving( 'scoutsunite-reunion' );
					// WordPress ne publie pas un contenu sans titre : « Réunion » par défaut.
					if ( ! titre.trim() ) {
						editor.editPost( { title: 'Réunion' } );
					}
				}
			},
			[ incomplet ]
		);

		return el(
			Panel,
			{ name: 'scoutsunite-quand', title: 'Quand et où ?' },
			el(
				'div',
				{ style: { display: 'grid', gap: '16px' } },
			incomplet &&
				el(
					C.Notice,
					{ status: 'warning', isDismissible: false },
					'Choisis la section et la date pour pouvoir publier.'
				),
			el( C.SelectControl, {
				label: 'Section',
				value: meta.su_section || '',
				options: options,
				onChange: set( 'su_section' ),
				__nextHasNoMarginBottom: true,
			} ),
			el( C.SelectControl, {
				label: 'Type',
				value: meta.su_type || '',
				options: [
					{ label: 'Réunion', value: '' },
					{ label: 'Week-end', value: 'weekend' },
					{ label: 'Camp', value: 'camp' },
					{ label: 'Réunion spéciale', value: 'speciale' },
				],
				onChange: function ( type ) {
					// Seuls un week-end et un camp durent plusieurs jours.
					editor.editPost( { meta: 'weekend' === type || 'camp' === type ? { su_type: type } : { su_type: type, su_jusqua: '' } } );
				},
				help: meta.su_type ? 'S’affiche aussi dans « Les temps forts » sur la page de la section.' : undefined,
				__nextHasNoMarginBottom: true,
			} ),
			el( C.TextControl, {
				label: plusieursJours ? 'Du' : 'Date',
				type: 'date',
				value: meta.su_date || '',
				onChange: set( 'su_date' ),
				__nextHasNoMarginBottom: true,
			} ),
			plusieursJours &&
				el( C.TextControl, {
					label: 'Jusqu’au',
					type: 'date',
					min: meta.su_date || undefined,
					value: meta.su_jusqua || '',
					onChange: set( 'su_jusqua' ),
					__nextHasNoMarginBottom: true,
				} ),
			el(
				C.Flex,
				{ align: 'flex-start' },
				el( C.FlexBlock, null, el( C.TextControl, {
					label: 'Début',
					type: 'time',
					value: meta.su_debut || '',
					onChange: set( 'su_debut' ),
					__nextHasNoMarginBottom: true,
				} ) ),
				el( C.FlexBlock, null, el( C.TextControl, {
					label: 'Fin',
					type: 'time',
					value: meta.su_fin || '',
					onChange: set( 'su_fin' ),
					__nextHasNoMarginBottom: true,
				} ) )
			),
			el( C.TextControl, {
				label: 'Lieu',
				value: meta.su_lieu === undefined ? 'au local' : meta.su_lieu,
				onChange: set( 'su_lieu' ),
				__nextHasNoMarginBottom: true,
			} ),
			el( C.CheckboxControl, {
				label: 'Réunion annulée',
				checked: meta.su_annulee === '1',
				onChange: function ( coche ) {
					editor.editPost( { meta: coche ? { su_annulee: '1' } : { su_annulee: '', su_annulee_pourquoi: '' } } );
				},
				help: 'Elle reste affichée jusqu’à sa date, marquée « Annulée », pour que les parents le voient.',
				__nextHasNoMarginBottom: true,
			} ),
			meta.su_annulee === '1' &&
				el( C.TextControl, {
					label: 'Pourquoi ? (facultatif)',
					value: meta.su_annulee_pourquoi || '',
					onChange: set( 'su_annulee_pourquoi' ),
					placeholder: 'Local indisponible, rendez-vous samedi prochain',
					__nextHasNoMarginBottom: true,
				} ),
			el( C.CheckboxControl, {
				label: 'Mettre en avant sur l’accueil',
				checked: meta.su_avant === '1',
				onChange: function ( coche ) {
					set( 'su_avant' )( coche ? '1' : '' );
				},
				help: 'Pour les grandes activités (week-end d’unité, souper, fête…) : elle s’affiche en grand en haut de l’accueil jusqu’à sa date.',
				__nextHasNoMarginBottom: true,
			} ),
			el( C.TextControl, {
				label: 'Répéter chaque semaine jusqu’au',
				type: 'date',
				value: meta.su_repeter || '',
				onChange: set( 'su_repeter' ),
				help: 'Facultatif. À la publication, une copie est créée chaque semaine jusqu’à cette date. Supprime ensuite les semaines de congé.',
				__nextHasNoMarginBottom: true,
			} )
			)
		);
	}

	wp.plugins.registerPlugin( 'scoutsunite-reunion', { render: QuandEtOu } );
} )( window.wp );
