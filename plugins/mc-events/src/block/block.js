/**
 * BLOCK: mc-events
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
import './style.scss';
import './editor.scss';

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks

/**
 * Register: aa Gutenberg Block.
 *
 * Registers a new block provided a unique name and an object defining its
 * behavior. Once registered, the block is made editor as an option to any
 * editor interface where blocks are implemented.
 *
 * @link https://wordpress.org/gutenberg/handbook/block-api/
 * @param  {string}   name     Block name.
 * @param  {Object}   settings Block settings.
 * @return {?WPBlock}          The block, if it has been successfully
 *                             registered; otherwise `undefined`.
 */
registerBlockType( 'cgb/block-mc-events', {
	title: __( 'Eventos - Mapas Culturais' ),
	icon: 'shield', 
	category: 'common',
	attributes: {
		view : { type: 'string' },
	},
	keywords: [
		__( 'evetos — mapas culturais' ),
	],

	edit: function( props ) {
		function updateView(event) {
			console.log(event.target);
			props.setAttributes({ view : event.target.value})
		}
		
		return (
			<div className={ props.className }>
				<select name="view" id="view" onChange={updateView}>
					<option value="list">Visualização</option>
					<option value="agenda">Agenda</option>
					<option value="calendar">Calendário</option>
					<option value="list">Lista</option>
					<option value="day">Diário</option>
					<option value="day">Agora</option>
				</select>
			</div>
		);
	},

	save: function( props ) {
		console.log(props);

		return wp.element.createElement(
			"mc-w-" + props.attributes.view
		);
		
	},
} );
