/**
 * BLOCK: mc-events
 *
 * Registering a basic block with Gutenberg.
 * Simple block, renders and saves the same content without any interactivity.
 */

//  Import CSS.
import './style.scss';
import './editor.scss';

import apiFetch from '@wordpress/api-fetch';

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const { Component } = wp.element;

class EditEvent extends Component {
	static getInitialState(view, filters, agent, space) {
		return {
			view, 
			filters, 
			agent, 
			space
		};
	}

	constructor() {
		super(...arguments);
		let { view, filters, agent, space } = this.props.attributes;

		this.state = this.constructor.getInitialState( view, filters, agent, space );
		
		apiFetch({ path: `/wp-json/wp/v2/space` }).then(spaces => {
			this.props.setAttributes({ spaces }) 
			this.setState({ spaces }) 
		});
	
		apiFetch({ path: `/wp-json/wp/v2/agent` }).then(agents => {
			this.props.setAttributes({ agents }) 
			this.setState({ agents }) 
		});
	}

	updateView(event) {
		this.props.setAttributes({ view : event.target.value})
		this.setState({ view : event.target.value})
	}

	updateFilters(event) {
		this.props.setAttributes({ filters : event.target.value})
		this.setState({ filters : event.target.value})
	}		

	updateSpace(event) {
		this.props.setAttributes({ space : event.target.value})
		this.setState({ space : event.target.value})
	}		

	updateAgent(event) {
		this.props.setAttributes({ agent : event.target.value})
		this.setState({ agent : event.target.value})
	}		

	render(){

		let spacesList, agentsList = <option value="">Carregando...</option>

		if(this.state.spaces){
			spacesList = [
				<option value="">Nenhum</option>
				, this.state.spaces.map(space => 
				<option selected={this.state.space == space['MAPAS:entity_id'] } value={ space['MAPAS:entity_id'] } key={space['MAPAS:entity_id']}> { space.title.rendered } </option> 	
				)
			]
		}

		if(this.state.agents){
			agentsList = [
				<option value="">Nenhum</option>
				, this.state.agents.map(agent => 
				<option selected={this.state.agent == agent['MAPAS:entity_id'] } value={ agent['MAPAS:entity_id'] } key={agent['MAPAS:entity_id']}> { agent.title.rendered } </option> 	
				)
			]
		}
		
		return (
			<div className={ this.props.className }>
				<div className="form-field">
					<label htmlFor="view">Visualização</label>
					<select name="view" id="view" onChange={(e) => { this.updateView(e) } }>
						<option selected={this.state.view == 'list' } value="list">Lista</option>
						<option selected={this.state.view == 'agenda' } value="agenda">Agenda</option>
						<option selected={this.state.view == 'calendar' } value="calendar">Calendário</option>
						<option selected={this.state.view == 'day' } value="day">Diário</option>
						<option selected={this.state.view == 'day' } value="day">Agora</option>
					</select>
				</div>

				<div className="form-field">
					<label htmlFor="filters">Exibir filtros</label>
					<select name="filters" id="filters" onChange={ (e) => { this.updateFilters(e)} }>
						<option selected={this.state.filters == 'true' } value="true">Sim</option>
						<option selected={this.state.filters == 'false' } value="false">Não</option>
					</select>
				</div>

				<div className="form-field">
					<label htmlFor="spaces">Filtrar por Espaço <small>(opcional)</small></label>
					<select name="spaces" id="spaces"  onChange={(e) => { this.updateSpace(e) } }>
						{ spacesList }
					</select>
				</div>

				<div className="form-field">
					<label htmlFor="agents">Filtrar por Agente <small>(opcional)</small></label>
					<select name="agents" id="agents" onChange={(e) => { this.updateAgent(e) } }>
						{ agentsList }
					</select>
				</div>
				
			</div>
		);
	}
}

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
		filters : { type: 'string' },
		agent : { type: 'string' },
		space : { type: 'string' },
	},
	keywords: [
		__( 'evetos — mapas culturais' ),
	],

	edit: EditEvent,
	save: function( props ) {
		let attr = {  };
		console.log(props)
		if(props.attributes.filters){
			attr[':show-filters'] = props.attributes.filters;
		}
		if(props.attributes.agent){
			attr.agents = props.attributes.agent;
		}
		if(props.attributes.space){
			attr.spaces = props.attributes.space;
		}

		return wp.element.createElement(
			"mc-w-" + props.attributes.view,
			attr
		);
		
	},
} );
