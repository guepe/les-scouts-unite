/**
 * Blocs de l'agenda dans l'éditeur : un aperçu rendu par le serveur.
 * Le contenu vient des réunions encodées dans Agenda → Ajouter.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ServerSideRender = wp.serverSideRender;

	function apercu( name ) {
		return function ( props ) {
			return el(
				'div',
				useBlockProps(),
				el( ServerSideRender, { block: name, attributes: props.attributes } )
			);
		};
	}

	var commun = {
		apiVersion: 3,
		category: 'widgets',
		icon: 'calendar-alt',
		supports: { html: false },
		save: function () {
			return null;
		},
	};

	wp.blocks.registerBlockType( 'scoutsunite/prochaines-reunions', Object.assign( {}, commun, {
		title: 'Prochaines réunions',
		description: 'La prochaine réunion de chaque section, mise à jour automatiquement depuis l’agenda.',
		edit: apercu( 'scoutsunite/prochaines-reunions' ),
	} ) );

	wp.blocks.registerBlockType( 'scoutsunite/a-la-une', Object.assign( {}, commun, {
		title: 'À la une',
		description: 'La prochaine activité mise en avant dans l’agenda, ou à défaut la dernière actualité.',
		edit: apercu( 'scoutsunite/a-la-une' ),
	} ) );

	wp.blocks.registerBlockType( 'scoutsunite/agenda', Object.assign( {}, commun, {
		title: 'Agenda complet',
		description: 'Toutes les réunions à venir, mois par mois, avec un filtre par section.',
		edit: apercu( 'scoutsunite/agenda' ),
	} ) );

	wp.blocks.registerBlockType( 'scoutsunite/reunion', Object.assign( {}, commun, {
		title: 'Détails de la réunion',
		description: 'Date, horaire, lieu et section de la réunion affichée.',
		usesContext: [ 'postId' ],
		edit: function () {
			return el( 'p', useBlockProps(), 'Date, horaire, lieu et section de la réunion.' );
		},
	} ) );

	wp.blocks.registerBlockType( 'scoutsunite/prochaine-reunion', Object.assign( {}, commun, {
		title: 'Prochaine réunion d’une section',
		description: 'La prochaine réunion d’une section, en grand, mise à jour automatiquement.',
		attributes: { section: { type: 'string', default: 'baladins' } },
		edit: function ( props ) {
			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Section' },
						el( SelectControl, {
							label: 'Section',
							value: props.attributes.section,
							options: [
								{ label: 'Baladins', value: 'baladins' },
								{ label: 'Louveteaux', value: 'louveteaux' },
								{ label: 'Éclaireurs', value: 'eclaireurs' },
								{ label: 'Pionniers', value: 'pionniers' },
								{ label: 'Toute l’unité', value: 'unite' },
							],
							onChange: function ( section ) {
								props.setAttributes( { section: section } );
							},
						} )
					)
				),
				apercu( 'scoutsunite/prochaine-reunion' )( props )
			);
		},
	} ) );

	wp.blocks.registerBlockType( 'scoutsunite/camps', Object.assign( {}, commun, {
		title: 'Temps forts de la section',
		description: 'Les week-ends, le camp et les réunions spéciales à venir de la section (et de toute l’unité), depuis l’agenda.',
		icon: 'palmtree',
		attributes: { section: { type: 'string', default: 'baladins' } },
		edit: function ( props ) {
			var sections = window.scoutsuniteSectionsBlocs || {};
			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Section' },
						el( SelectControl, {
							label: 'Section',
							value: props.attributes.section,
							options: Object.keys( sections ).map( function ( slug ) {
								return { label: sections[ slug ], value: slug };
							} ),
							onChange: function ( section ) {
								props.setAttributes( { section: section } );
							},
						} )
					)
				),
				apercu( 'scoutsunite/camps' )( props )
			);
		},
	} ) );

	wp.blocks.registerBlockType( 'scoutsunite/iama', Object.assign( {}, commun, {
		title: 'Activités IAmA',
		description: 'Les prochaines activités du clan IAmA, depuis l’agenda (section « IAmA »).',
		icon: 'heart',
		edit: apercu( 'scoutsunite/iama' ),
	} ) );

	wp.blocks.registerBlockType( 'scoutsunite/staff', Object.assign( {}, commun, {
		title: 'Staff de la section',
		description: 'Le chef de section et les animateurs encodés dans Réglages → Unité.',
		icon: 'groups',
		attributes: { section: { type: 'string', default: 'baladins' } },
		edit: function ( props ) {
			var sections = window.scoutsuniteSectionsBlocs || {};
			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Section' },
						el( SelectControl, {
							label: 'Section',
							value: props.attributes.section,
							options: Object.keys( sections ).map( function ( slug ) {
								return { label: sections[ slug ], value: slug };
							} ),
							help: 'Les noms se modifient dans Réglages → Unité.',
							onChange: function ( section ) {
								props.setAttributes( { section: section } );
							},
						} )
					)
				),
				apercu( 'scoutsunite/staff' )( props )
			);
		},
	} ) );

	wp.blocks.registerBlockType( 'scoutsunite/whatsapp', Object.assign( {}, commun, {
		title: 'Groupes WhatsApp',
		description: 'Les groupes WhatsApp encodés dans Réglages → Unité, chacun avec son QR code.',
		icon: 'format-chat',
		attributes: { section: { type: 'string', default: '' } },
		edit: function ( props ) {
			var sections = window.scoutsuniteGroupes || {};
			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Groupes' },
						el( SelectControl, {
							label: 'Groupes affichés',
							value: props.attributes.section,
							options: [ { label: 'Tous les groupes', value: '' } ].concat(
								Object.keys( sections ).map( function ( slug ) {
									return { label: 'unite' === slug ? sections[ slug ] : sections[ slug ] + ' (et l’unité)', value: slug };
								} )
							),
							help: 'Les liens des groupes se modifient dans Réglages → Unité.',
							onChange: function ( section ) {
								props.setAttributes( { section: section } );
							},
						} )
					)
				),
				apercu( 'scoutsunite/whatsapp' )( props )
			);
		},
	} ) );

	// Coordonnées de l'unité (Réglages → Unité) : l'éditeur montre les vraies valeurs.
	if ( wp.blocks.registerBlockBindingsSource ) {
		wp.blocks.registerBlockBindingsSource( {
			name: 'scoutsunite/unite',
			label: 'Coordonnées de l’unité',
			getValues: function ( args ) {
				var valeurs = {};
				Object.keys( args.bindings ).forEach( function ( attribut ) {
					var cle = ( args.bindings[ attribut ].args || {} ).key;
					valeurs[ attribut ] = ( window.scoutsuniteUnite || {} )[ cle ] || '';
				} );
				return valeurs;
			},
			canUserEditValue: function () {
				return false;
			},
		} );
	}
} )( window.wp );
