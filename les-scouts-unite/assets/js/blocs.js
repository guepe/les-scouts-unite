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
} )( window.wp );
