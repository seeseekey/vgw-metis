<?php

namespace WP_VGWORT;

/**
 * Participant Page View Class
 *
 * holds all things necessary to set up the pixel list page template
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Torben Gallob
 * @author      Michael Hillebrand
 *
 */
class Page_Participants extends Page {
	/**
	 * @var object instance of the participant table class
	 */
	public object $list_table_participants;

	/**
	 * constructor
	 */
	public function __construct( object $plugin ) {
		parent::__construct( $plugin );

        // add submenu item
		add_action( 'admin_menu', [$this, 'add_participants_submenu'] );

		$this->list_table_participants = new List_Table_Participants();
		$this->list_table_participants->prepare_items();

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_script' ] );
		add_action( 'wp_ajax_participant_save', [ $this, 'participant_save' ] );
		add_action( 'wp_ajax_participant_delete', [ $this, 'participant_delete' ] );

	}

	/**
	 * add the submenu for the participants overview
	 *
	 * @return void
	 */

	public function add_participants_submenu() {
		$page_metis_participants_hook = add_submenu_page( 'metis-dashboard', esc_html__( 'VG WORT METIS Beteiligtenübersicht', 'vgw-metis' ), esc_html__( 'Beteiligte', 'vgw-metis' ), 'manage_options', 'metis-participant', array(
			$this,
			'render'
		), 5 );
	}

	/**
	 * Loads the template of the view > render page
	 *
	 * @return void
	 */
	public function render(): void {
		$this->plugin->notifications->display_notices();
		$this->list_table_participants->read_data();
		require_once 'partials/participants.php';
	}

	/**
	 * load script for metis list table
	 *
	 * @return void
	 */
	public function enqueue_script(): void {

		wp_enqueue_script( 'wp_metis_list_table_script', plugin_dir_url( __FILE__ ) . '../admin/js/list-table.js', [ 'jquery' ] );
		wp_localize_script(
			'wp_metis_list_table_script',
			'wp_metis_list_table_obj',
			[
				'columns'       => $this->list_table_participants->get_json_columns(),
				'upsert_action' => "participant_save",
				'delete_action' => "participant_delete",
				'save_nonce'    => wp_create_nonce( 'participant_save_nonce' ),
				'delete_nonce'  => wp_create_nonce( 'participant_delete_nonce' )
			]
		);

	}

	/**
	 * upsert participant if id is given it will update otherwise will insert
	 *
	 * @return void
	 */
	public function participant_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Permission denied.', 'vgw-metis' ) ], 403 );
		}

		$request_data = wp_unslash( $_POST );
		$nonce        = isset( $request_data['_wpnonce'] ) && is_scalar( $request_data['_wpnonce'] ) ? (string) $request_data['_wpnonce'] : '';
		$data         = isset( $request_data['data'] ) && is_array( $request_data['data'] ) ? $request_data['data'] : [];

		// Security: Verify nonce
		if ( ! wp_verify_nonce( $nonce, 'participant_save_nonce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid request.', 'vgw-metis' ) ], 400 );
		}

		$wp_user          = true;
		$return_value     = false;
		
		// Security: Sanitize all input data
		$participant_data = (object) [
			'id'          => isset( $data['id'] ) && is_scalar( $data['id'] ) ? absint( $data['id'] ) : null,
			'first_name'  => isset( $data['first_name'] ) && is_scalar( $data['first_name'] ) ? wp_kses( (string) $data['first_name'], [] ) : '',
			'last_name'   => isset( $data['last_name'] ) && is_scalar( $data['last_name'] ) ? wp_kses( (string) $data['last_name'], [] ) : '',
			'file_number' => isset( $data['file_number'] ) && is_scalar( $data['file_number'] ) ? sanitize_text_field( (string) $data['file_number'] ) : '',
			'involvement' => isset( $data['involvement'] ) && is_scalar( $data['involvement'] ) ? sanitize_text_field( (string) $data['involvement'] ) : '',
			'wp_user'     => isset( $data['wp_user'] ) && is_scalar( $data['wp_user'] ) ? sanitize_user( (string) $data['wp_user'] ) : ''
		];

		if ( $return_value = Db_Participants::upsert_participant( $participant_data ) ) {
			if ( $participant_data->wp_user != '' ) {
				if ( $wp_user = get_user_by( 'login', $participant_data->wp_user ) ) {
					$wp_user = wp_update_user( [
						'ID'         => $wp_user->ID,
						'first_name' => $participant_data->first_name,
						'last_name'  => $participant_data->last_name
					] );

					if ( is_wp_error( $wp_user ) ) {
						wp_send_json_error( [ 'message' => esc_html__( 'Error while updating WordPress user.', 'vgw-metis' ) ], 500 );
					}
				}
			}
		}

		// Send JSON response
		if ( $return_value && $wp_user ) {
			wp_send_json_success( $return_value );
		} else {
			wp_send_json_error( [ 'message' => esc_html__( 'Error while saving.', 'vgw-metis' ) ] );
		}
	}

	/**
	 * will delete participant with given id
	 *
	 * @return int|null
	 */
	public function participant_delete( bool $force_delete = false ): int|null {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Permission denied.', 'vgw-metis' ) ], 403 );
		}

		$request_data = wp_unslash( $_POST );
		$nonce        = isset( $request_data['_wpnonce'] ) && is_scalar( $request_data['_wpnonce'] ) ? (string) $request_data['_wpnonce'] : '';
		$id           = isset( $request_data['id'] ) && is_scalar( $request_data['id'] ) ? absint( $request_data['id'] ) : 0;

		// Security: Verify nonce
		if ( ! wp_verify_nonce( $nonce, 'participant_delete_nonce' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid request.', 'vgw-metis' ) ], 400 );
		}

		if ( $id === 0 ) {
			return wp_send_json_error( [ 'message' => esc_html__( 'Invalid participant ID.', 'vgw-metis' ) ] );
		}

		if ( $force_delete == false ) {
			$participant = Db_Participants::get_participant_by_id( $id );
			if ( ! $participant ) {
				return wp_send_json_error( [ 'message' => esc_html__( 'Invalid participant ID.', 'vgw-metis' ) ] );
			}
			// Participant which comes from wp user only can be deleted
			// by deleting wordpress user
			if ( $participant->wp_user != '' ) {
				return wp_send_json_error( [ 'message' => esc_html__( 'Beteiligte mit Benutzernamen können nur über Wordpress Benutzer gelöscht werden!', 'vgw-metis' ) ] );
			}
		}

		$deleted = Db_Participants::delete_participant( $id );

		if ( $deleted === false ) {
			return wp_send_json_error( [ 'message' => esc_html__( 'Error while deleting participant.', 'vgw-metis' ) ], 500 );
		}

		if ( $deleted === 0 ) {
			return wp_send_json_error( [ 'message' => esc_html__( 'Participant could not be found.', 'vgw-metis' ) ], 404 );
		}

		return wp_send_json_success( [ 'message' => esc_html__( 'Participant deleted successfully.', 'vgw-metis' ) ] );
	}

}
