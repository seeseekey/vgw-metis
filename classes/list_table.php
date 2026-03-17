<?php

namespace WP_VGWORT;

/**
 * List Table
 *
 * Generates a List Table for displaying an Editing Items
 * Overwrite the method prepare_items to set list table items and columns
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Torben Gallob
 * @author      Michael Hillebrand
 *
 */
class List_Table {

	/**
	 * The current list of items.
	 *
	 * @var array
	 */
	public $items;

	/**
	 * array of all columns to show in table
	 * must be part of items
	 */
	private $columns;

	/**
	 * HTML output for the table
	 *
	 * @var string
	 */
	protected $html_output;


	/**
	 * sets all items which are listed in tables
	 *
	 * @return void
	 */
	public function set_items( $items ): void {
		$this->items = $items;
	}

	/**
	 * sets all definition columns for assigned items
	 *
	 * @return void
	 */
	public function set_columns( $columnns ): void {
		$this->columns = $columnns;
	}

	/**
	 * placeholer must be overwritten in calling calls
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		die( 'class-metis-list-table: function prepare_items() must be overwritten by calling class' );
	}

	/**
	 * renders the metis list table and display it to screen
	 *
	 * @return void
	 */
	public function display(): void {
		$this->html_output = $this->create_table();
		echo( $this->html_output );
	}

	/**
	 * Creates the header for table
	 *
	 * @return string header
	 */
	private function create_table_header(): string {
		$header = '<thead><tr>';
		foreach ( $this->columns as $col ) {
			$header = $header . '<th>';
			$header = $header . $col->label;
			$header = $header . '</th>';
		}
		// Add Action column
		$header = $header . '<th></th>';

		$header = $header . '</tr></thead>';

		return $header;
	}

	/**
	 * creates the body for table and returns html
	 *
	 * @return string body
	 */
	private function create_table_body(): string {
		$body = "";
		$body = $body . '<button class="button button-secondary" style="margin-bottom: 5px" id = "metis_add_row"  >Hinzufügen</button>';

		$body = $body . '<tbody id = "item-list">';
		foreach ( $this->items as $item ) {
			$body = $body . '<tr class="tr_class_' . esc_attr( $item['id'] ) . '">';
			foreach ( $this->columns as $column_key => $column ) {
				if ( $column->edit_type == 'INPUT' ) {
					if ($column->linkid == '')
						$body = $body . '<td class="metis-col-data" metis-col-data-value="' . esc_attr( $item[ $column_key ] ) . '" metis-col-data-key="' . esc_attr( $column_key ) . '">' . esc_html( $item[ $column_key ] ) . '</td>';
					else
						$body = $body . '<td class="metis-col-data" metis-col-data-value="' . esc_attr( $item[ $column_key ] ) . '" metis-col-data-key="' . esc_attr( $column_key ) . '"><a href="' . esc_url( $column->linkattr  . '=' . $item[$column->linkid] ) .'">' . esc_html( $item[ $column_key ] ) . '</a></td>';

				}
				if ( $column->edit_type == "SELECT" ) {
					$item_value = $item[ $column_key ] ?? '';
					$value = isset( $column->select_options[ $item_value ] ) ? $column->select_options[ $item_value ] : $item_value;
					$body  = $body . '<td class="metis-col-data"  metis-col-data-value="' . esc_attr( $item_value ) . '" metis-col-data-key="' . esc_attr( $column_key ) . '">' . esc_html( $value ) . '</td>';
				}

			}

			$body = $body .
					'<td class="metis-col-save">' .
					'<a href = "#" class="metis_row_edit" data-row-id="' . esc_attr( $item['id'] ) . '">bearbeiten |</a> ' .
					'<a href = "#" class="metis_row_save" style="display:none" data-row-id="' . esc_attr( $item['id'] ) . '">speichern |</a> ' .
					'<a href = "#" class="metis_row_delete" data-row-id="' . esc_attr( $item['id'] ) . '"> löschen</a>' .
					'<a href = "#" class="metis_row_cancel" style="display:none" data-row-id="' . esc_attr( $item['id'] ) . '"> abbrechen</a>' .
					'</td>';

			$body = $body . '</tr>';

		}
		$body = $body . '</tbody>';

		return $body;
	}

	/**
	 * creates the footer
	 *
	 * @return string footer
	 */
	private function create_table_footer(): string {
		$footer = '<tfoot><tr>';
		foreach ( $this->columns as $col ) {
			$footer = $footer . '<th>';
			$footer = $footer . $col->label;
			$footer = $footer . '</th>';
		}

		// Add Action column
		$footer = $footer . '<th></th>';

		$footer = $footer . '</tr></tfoot>';

		return $footer;
	}

	/**
	 * creates hole table and returns html
	 *
	 * @return string html table
	 */
	private function create_table(): string {
		return
			'<table class="wp-list-table widefat fixed striped">'
			. $this->create_table_header() . $this->create_table_body() . $this->create_table_footer() .
			'</table>';

	}

	/**
	 * creates a json string from columns definition array
	 *
	 * @return string json
	 */
	public function get_json_columns(): string {
		return json_encode( $this->columns );
	}
}
