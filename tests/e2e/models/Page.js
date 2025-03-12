import { Selector, t } from 'testcafe';
import { link } from '../utilities/page-model/helpers/field';
import { admin, baseURL } from '../auth';
import { getQueryParam } from '../utilities/page-model/helpers/search-params';

class Page {
	constructor( name ) {
		this.pageId = '';
		this.pageName = name;
		this.addNewPageLink = link( '.wrap', 'Add New Page' );
		this.trashLink = Selector( 'a' ).withAttribute(
			'aria-label',
			`Move “${ name }” to the Trash`
		);
		this.closePopupButton = Selector( 'button' ).withAttribute(
			'aria-label',
			'Close dialog'
		);
		this.closePopupPattern = Selector( 'button' ).withAttribute(
			'aria-label',
			'Close'
		);
		this.titleField = Selector( '.editor-post-title__input' );
		this.addBlockToolbarIcon = Selector(
			'button.editor-document-tools__inserter-toggle'
		);
		this.searchBlock = Selector( '.block-editor-inserter__search' )
			.find( 'input' )
			.withAttribute( 'type', 'search' );
		this.searchResults = Selector( '.block-editor-block-types-list' );
		this.documentPanel = Selector( 'div.editor-sidebar' );
		this.documentPanelToolbarButton = Selector(
			'button.components-button'
		).withAttribute( 'aria-label', 'Settings' );
		this.documentPanelCloseButton = Selector(
			'button.components-button'
		).withAttribute( 'aria-label', 'Close panel' );
		this.saveToolbarButton = Selector(
			'button.components-button'
		).withText( 'Save' );
		this.publishToolbarButton = Selector(
			'.editor-post-publish-button__button'
		);
		this.confirmPublishButton = Selector(
			'.editor-post-publish-panel__header-publish-button button'
		).withText( 'Publish' );
		this.trashButton = Selector( 'button.editor-post-trash' ).withText(
			'Move to trash'
		);
	}

	/**
	 * Creates a new page on this instance.
	 * Must only be used once per instance to prevent anomalies
	 *
	 * @return {Promise<void>} none
	 */
	async add() {
		await t.click( this.addNewPageLink );

		if ( await this.closePopupButton.exists ) {
			await t.click( this.closePopupButton );
		}

		if ( await this.closePopupPattern.exists ) {
			await t.click( this.closePopupPattern );
		}

		await t.typeText( this.titleField, this.pageName, { paste: true } );
	}

	/**
	 * Deletes the page of this instance
	 *
	 * @return {Promise<void>} none
	 */
	async delete() {
		await t
			.useRole( admin )
			.navigateTo(
				`${ baseURL }/wp-admin/post.php?post=${ this.pageId }&action=edit`
			);

		if ( ! ( await this.documentPanel.exists ) ) {
			await t.click( this.documentPanelToolbarButton );
		}

		await t.click( this.trashButton ).pressKey( 'enter' );
	}

	/**
	 * Inserts a block on this page instance
	 *
	 * @param { string }        block name i.e paragraph, shortcode
	 * @param { {
	 *   target: string
	 *   content: string
	 * }= } node content of the block to be added
	 *
	 * @return {Promise<void>} none
	 */
	async insertBlock( block, node ) {
		await t
			.click( this.addBlockToolbarIcon )
			.typeText( this.searchBlock.filterVisible(), block, {
				paste: true,
			} );

		const resultBlock = this.searchResults.find( 'button' );

		await t.hover( resultBlock ).click( resultBlock );

		if ( node?.content ) {
			await t.typeText( Selector( node.target ), node.content );
		}
	}

	/**
	 * Saves content added to the page
	 *
	 * @return {Promise<void>} none
	 */
	async saveChanges() {
		await t.click( this.saveToolbarButton );
	}

	async publishChanges() {
		await t
			.click( this.publishToolbarButton )
			.click( this.confirmPublishButton );

		this.pageId = await getQueryParam( 'post' );

		await t.click( this.documentPanelCloseButton );
	}
}

export default Page;
