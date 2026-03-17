<?php
namespace WP_VGWORT;

use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignRef;

/**
 * Metis Services holds all service functions for metis
 *
 * All general services for assiging/unassigning Pixel, for ordering and manipulating pixels, handling meta posts.
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Torben Gallob
 * @author      Michael Hillebrandf
 *
 */

class Services {

	// in redirects, we are going to check if the page is allowed
	const ALLOWED_PAGES = [
		'metis-settings',
		'metis-pixel',
		'metis-dashboard',
		'metis-messages',
		'metis-participant',
	];

	protected $_new_assigned_pixels = 0;
    protected $_already_found       = 0;
    protected $_failure             = 0;

	protected $_log             = '';

	public function __construct() {
		$this->_new_assigned_pixels = 0;
		$this->_already_found       = 0;
		$this->_failure             = 0;
		$this->_log					= "";
	}

	/**
	 *  Orders pixels from T.O.M Rest API if needed according to threshold setting
	 *
	 * @param int $min_needed how many new pixels are needed?
	 *
	 * @return int|bool count pixels ordered, 0 on none ordered, false on error
	 */
	public static function order_pixels_if_needed( int $min_needed = Common::NUMBER_ORDER_PIXEL ): int|bool {
		$get_number_free_pixel = DB_Pixels::get_available_pixel_count();
		if ( $get_number_free_pixel < Common::MIN_PIXEL_THRESHOLD + $min_needed ) {
			$number_order = $min_needed + Common::MIN_PIXEL_THRESHOLD - $get_number_free_pixel;
			$order_result = Tom_Pixels::order_pixels( $number_order );
			if ( count( $order_result->pixels ) ) {
				$insert_pixels = Pixel::batch_transform_api_to_db_pixel( $order_result, Common::SOURCE_RESTAPI );
				return DB_Pixels::insert_pixels( $insert_pixels );
			} else {
				return false;
			}
		}
		return $get_number_free_pixel;
	}

	/**
	 *  Check pixel validity and if valid and not in database persists it
	 *
	 * @param pixel checked pixel
	 *
	 * @return int|bool returns if it is valid, false on error
	 */
	protected function _check_html_pixel_validity_and_persist($pixel) {
		// Check if pixel domain is right, if not skip this pixel
		if($this->_check_pixel_domain($pixel)) {
			
			// Check if the pixel is valid ( check validity and status )
			$is_valid = self::is_valid_and_ownership_check($pixel->get_public_identification_id());
			if ($is_valid === 'VALID' || $is_valid === 'NOT_OWNER') {
				
				// Check if pixel exists in database
				if (Db_Pixels::get_pixel_by_public_identification_id($pixel->get_public_identification_id()) == null) {
			
					// If pixel does not exists in the database it needs to be inserted
					$statuses = Tom_Pixels::check_pixel_state( [$pixel->get_public_identification_id()] );

					if (count($statuses) == 1) {
						$pixel->set_source(Common::SOURCE_SCANWORDPRESS);
						if($statuses[0]->state == 'VALID')
							$pixel->set_private_identification_id($statuses[0]->privateUID);
						DB_Pixels::insert_pixels([$pixel]);
						$this->_log .= "Pixel[" . $pixel->get_public_identification_id() . "] inserted in database.\n";
						return true;
					}
					
				} else {
					$this->_log .= "Pixel[" . $pixel->get_public_identification_id() . "] already in database.\n";
					$this->_already_found++;
					return true;
				}
			} else {
				$this->_log .= "Pixel[" . $pixel->get_public_identification_id() . "] validity check failed.\n";
			}
		} else {
			$this->_log .= "Pixel[" . $pixel->get_public_identification_id() . "] domain check failed.\n";
		}
		$this->_failure++;
		return false;
	}

	/**
	 * check pixels with API
	 *
	 * Reads all pixels from database and sends it against T.O.M. API. If API returns success pixels will be updated in
	 * database.
	 *
	 * @return bool success yes / no
	 */
	public static function check_all_pixels(): bool {
		$pixel_for_check = DB_Pixels::get_all_pixels();
		$result = Tom_Pixels::check_pixel_state( array_column( $pixel_for_check, 'public_identification_id' ) );
		if ( count( $result ) ) {
			foreach ( $result as $pixel ) {
				self::update_pixel_data_from_api( $pixel );
			}
			return true;
		}
		return false;
	}

	/**
	 * inserts 1 new pixel, no ownership check but api call to get all data and pixel validity
	 *
	 * @param string $public_identification_id
	 *
	 * @return bool true if we insert it, false on error
	 */
	public static function insert_one_manual_pixel( string $public_identification_id ): bool {
		$payload = [ $public_identification_id ];
		// check if the pixel is valid, get its data and status
		$result = Tom_Pixels::check_pixel_state( $payload );
		// insert if valid (owner or no owner doesn't matter)

		// Insert pixel with ownership
		if ( count( $result ) && $result[0]->state == Common::API_STATE_VALID ) {
			$checked_pixel = $result[0];
			$new_pixel     = new Pixel( $checked_pixel );
			$new_pixel->set_source( Common::SOURCE_MANUAL );

			// insert pixel without ownership
		} else if ( $result[0]->state == Common::API_STATE_NOT_OWNER ) {
			$checked_pixel = $result[0];
			$new_pixel     = new Pixel( $checked_pixel );
			$new_pixel->set_source( Common::SOURCE_MANUAL );
			$new_pixel->set_ownership( false );
		} else {
			return false;
		}

		$payload   = array();
		$payload[] = $new_pixel;

		return (bool) DB_Pixels::insert_pixels( $payload, Common::SOURCE_MANUAL );
	}

	/**
	 * checks first if the pixel format is correct, then call the api to check for ownership
	 *
	 * @param string $public_identification_id
	 *
	 * @return string | bool return the 4 states from Common or false if api error
	 */
	public static function is_valid_and_ownership_check( string $public_identification_id ): string|bool {
		// check pid format
		if ( ! Common::is_valid_pixel_id_format( $public_identification_id ) ) {
			return Common::API_STATE_NOT_VALID;
		}

		$result = Tom_Pixels::check_pixel_state([$public_identification_id]);
        return count($result) ? $result[0]->state : false;
	}

	/**
	 * Updates count_started, min_hits and message_created_at from all given pixels from API to database IF pixel
	 * status is valid
	 *
	 * @param $pixel
	 *
	 * @return void
	 */
	public static function update_pixel_data_from_api( $pixel ): void {
		if ( $pixel->state == Common::API_STATE_VALID ) {
			$DB_Pixels = DB_Pixels::get_pixel_by_public_identification_id( $pixel->publicUID );
			if ( $DB_Pixels != null ) {
				$DB_Pixels->count_started = $pixel->countStarted;
				$DB_Pixels->min_hits      = json_encode( $pixel->limitsInYear );
				if ( property_exists( $pixel, 'messageCreatedDate' ) ) {
					$DB_Pixels->message_created_at = $pixel->messageCreatedDate;
				}

				if ( property_exists( $pixel, 'message_created_at' ) ) {
					$DB_Pixels->message_created_at = $pixel->message_created_at;
				}

				DB_Pixels::update_pixel( $DB_Pixels );
			}
		}
	}


	/**
	 * Search for an assigned pixel with the given post_id
	 *
	 * @param int $post_id      the post id we want the pixel from
	 * @param bool $forceActive return pixel only if set active is set to given param
	 *
	 * @return Pixel|null    pixel which is connected to the post_id - if no pixel to post was found then null
	 */
	public static function get_pixel_for_post( int $post_id, bool $forceActive = true ): null|Pixel {
		$pixelDB = DB_Pixels::get_pixel_by_post_id( $post_id );

		if ( ! $pixelDB ) {
			return null;
		}
		if ( $forceActive && ! $pixelDB->active ) {
			return null;
		}

		return new Pixel( $pixelDB );
	}

	/**
	 * Function calculates the length of the content and sets the post_meta _metis_text_length
	 *
	 * @param int $post_id
	 *
	 * @return void
	 */
	public static function set_text_length( int $post_id ): void {
		$text_length_option = get_post_meta( $post_id, "_metis_text_length" );
		$text_length        = self::calculate_post_text_length( $post_id );

		if ( $text_length_option == null ) {
			add_post_meta( $post_id, "_metis_text_length", (int) $text_length, true );
		} else {
			update_post_meta( $post_id, '_metis_text_length', (int) $text_length );
		}

	}

	/**
	 * Sets the post_meta text type. When new_text_type is not given the default value will be set
	 *
	 * @param int $post_id          the post id to set the text type for
	 * @param string $new_text_type default or lyrik, constant from metis common class
	 *
	 * @return void
	 */
	public static function set_text_type( int $post_id, string $new_text_type = Common::TEXT_TYPE_DEFAULT ): void {
		$text_type = get_post_meta( $post_id, "_metis_text_type" );
		if ( $text_type == null ) {
			add_post_meta( $post_id, "_metis_text_type", $new_text_type, true );
		}/* else {
			update_post_meta( $post_id, '_metis_text_type', $new_text_type );
		}*/
	}

	/**
	 * Function will search for pixel in the given content
	 *
	 * Returns a Pixel if a pixel was found, Returns null if no pixel was found.
	 *
	 * @param string $content html string
	 *
	 * @return Pixel|null    pixel if found, null else
	 */
	public static function search_for_pixels_in_content( string $content ): array {
		libxml_use_internal_errors(true);
		$pixels = [];
		try {
			if ( str_contains( $content, Common::PIXEL_DOMAIN ) ) {
				$html_obj = new \DOMDocument();
				$html_obj->loadHTML( $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
				$length = $html_obj->getElementsByTagName( "img" )->length;
				for ( $index_tags = 0; $index_tags < $length; $index_tags ++ ) {
					$img_src = $html_obj->getElementsByTagName( "img" )->item( $index_tags )->getAttribute( "src" );
					if ( str_contains( $img_src, Common::PIXEL_DOMAIN ) ) {
						$src_parts       = explode( "/", $img_src );
						$domain          = $src_parts[2];
						$public_pixel_id = $src_parts[ count( $src_parts ) - 1 ];
						$pixel           = new Pixel();
						$pixel->set_domain( $domain );
						$pixel->set_public_identification_id( $public_pixel_id );

						$pixels[] = $pixel;
					}
				}
				return $pixels;
			}
		} catch ( \Exception $e ) {
			return $pixels;
		}
		return $pixels;
	}

	/**
	 * Initialize all post-meta for all posts
	 *
	 * Initialize all post and pages from post table and sets text_type to standard, calculate and set text length.
	 *
	 * @return void
	 */
	public static function initialize_all_posts(): void {
		$args  = array();
		$posts = get_posts( $args );
		if ( $posts ) {
			foreach ( $posts as $post ) {
				self::set_text_type( $post->ID );
				self::set_text_length( $post->ID );
			}
		}

		$pages = get_pages( $args );
		if ( $pages ) {
			foreach ( $pages as $page ) {
				self::set_text_type( $page->ID );
				self::set_text_length( $page->ID );
			}
		}

	}

	/**
	 * Initialize participants from wordpress user list
	 *
	 * @return void
	 */
	public static function initialize_participants_from_wp_users(): void {
		$users = get_users( array( 'fields' => array( 'ID', 'user_login' ) ) );
		foreach ( $users as $user ) {
			$roles = get_userdata( $user->ID )->roles;
			if ( $roles != null && count( $roles ) > 0 && $roles[0] == 'subscriber' ) {
				return;
			}

			// check if a participant exists - can't do with ignore if exists in database because
			// participants which are not a wp user have no wp_user attribute
			// if not then insert a participant from wordpress user
			if ( Db_Participants::get_participant_by_wp_username( $user->user_login ) == null ) {
				$first_name       = get_user_meta( $user->ID, 'first_name', true );
				$last_name        = get_user_meta( $user->ID, 'last_name', true );
				$participant_data = (object) array(
					'first_name'  => $first_name,
					'last_name'   => $last_name,
					'file_number' => '',
					'involvement' => Common::INVOLVEMENT_AUTHOR,
					'wp_user'     => $user->user_login
				);
				Db_Participants::upsert_participant( $participant_data );
			}
		}

	}

	/**
	 * Gets log
	 *
	 * @return string | null
	 */
	public function get_log() {
        return $this->_log;
    }

	/**
	 * If a pixel is assigned to exactly ONE post, disable the the pixel, so that it cannot be used anymore
	 * If pixel is assigned to MORE THAN ONCE post, remove the pixel post relation
	 *
	 * @param int $post_id the id of the post which will be deleted
	 *
	 * @return bool
	 */
	public static function disable_pixel_by_post_id( int $post_id ): bool {
		$pixel = DB_Pixels::get_pixel_by_post_id( $post_id );
		if ( $pixel ) {
			if ( ! $pixel->public_identification_id ) {
				return false;
			}
			// Check if there are more than one assignements with this public id.
			// Only disable Pixel if only one assignement exits
			if ( DB_Pixels::get_assigned_posts_count( $pixel->public_identification_id ) <= 1 ) {
				DB_Pixels::disable_pixel( $pixel->public_identification_id );
			}

			DB_Pixels::remove_pixel_posts_relation( $pixel->public_identification_id, $post_id );
		}

		return true;
	}

	/**
	 * Adds the pixel image to the post content
	 *
	 * @param string $content content to add pixel to
	 *
	 * @return string           content with pixel (if any)
	 */
	public static function add_pixel_img_to_post_content( string $content ): string {
		global $post;
		global $posts;

		// Check if parent is a latest-posts
		$is_latest_posts = false;
		if ( isset( $posts[0] ) ) {
			if ( str_contains( $posts[0]->post_content, "wp:latest-posts" ) ) {
				$is_latest_posts = true;
			}
			// if parent is lopped
			if ( isset( $posts[0]->ID ) && isset( $post->ID ) && $posts[0]->ID == $post->ID ) {
				$is_latest_posts = false;
			}
		}

		if ( is_singular() && in_the_loop() && is_main_query() && 'publish' === $post->post_status && ! $is_latest_posts ) {
			// SEARCH IF a manual pixel is set
			$found_content_pixels = self::search_for_pixels_in_content( $content );
			foreach($found_content_pixels as $found_content_pixel) {

				if ( $found_content_pixel == null ) {
					if ( isset( $post ) && isset( $post->ID ) ) {
						$assigned_pixel = self::get_pixel_for_post( $post->ID );
						if ( $assigned_pixel != null && $assigned_pixel->get_active() ) {
							if ( WP_DEBUG ) {
								$content = $content . Common::generate_pixel_html_image_debug( $assigned_pixel->get_domain(), $assigned_pixel->get_public_identification_id(), false );
							}
							$content = $content . Common::generate_pixel_html_image( $assigned_pixel->get_domain(), $assigned_pixel->get_public_identification_id(), false );
						}
					}
				}
			}
		}

		return $content;
	}

	/**
	 * get the raw version of a post - no html tags, no extra white spaces, carriage returns, ...
	 *
	 * @param int $post_id
	 *
	 * @return string|bool
	 */
	public static function get_striped_post_content( int $post_id ): string|bool {
		if ( false === get_post_status( $post_id ) ) {
			return false;
		}

		$content = get_post_field( 'post_content', $post_id );
		$content = apply_filters( 'the_content', $content );
		$content = wp_strip_all_tags( $content );

		return preg_replace( '/\s+/', ' ', $content );
	}

	/**
	 * get the text length of a post
	 *
	 * @param int $post_id
	 *
	 * @return int|bool
	 */
	public static function calculate_post_text_length( int $post_id ): int|bool {

		$stripped_content = self::get_striped_post_content( $post_id );

		if ( $stripped_content === false ) {
			return false;
		}

		return mb_strlen( $stripped_content );
	}

	/**
	 * if there is no text limit change record yet, create one without conditions, if there are some already, check if
	 * the limit changes and save accordingly, also save if only public identification id changes
	 *
	 * @param int $post_id
	 * @param string $public_identification_id
	 * @param int $text_length
	 *
	 * @return bool | int returns false on error, 0 if nothing has changed, 1 if a new record has been created
	 */
	public static function add_text_limit_change_if_needed( int $post_id = 0, string $public_identification_id = '', int $text_length = 0 ): bool|int {
		$latest_limit_change = Db_Text_Limit_Changes::get_latest_text_limit_change_by_post_id( $post_id );

		if ( $latest_limit_change === false ) {
			return false;
		}

		// only check for published posts
		$post_status = get_post_status( $post_id );

		if ( $post_status !== 'publish' ) {
			return 0;
		}

		// no previous text limit change records, so we can add the first one
		if ( $latest_limit_change === null ) {
			return Db_Text_Limit_Changes::add_text_limit_change( $post_id, $public_identification_id, $text_length );
		}

		// save if public identification id changes, even if no limit changes
		if ( $latest_limit_change->public_identification_id !== $public_identification_id ) {
			return Db_Text_Limit_Changes::add_text_limit_change( $post_id, $public_identification_id, $text_length );
		}

		// if new text length is within same boundaries as latest text limit change return 0, meaning no change / no new text limit change record was made
		if ( $latest_limit_change->text_length < Common::DEFAULT_TEXT_LENGTH_MIN && $text_length < Common::DEFAULT_TEXT_LENGTH_MIN ) {
			return 0;
		}

		// if new text length is within same boundaries as latest text limit change return 0, meaning no change / no new text limit change record was made
		if ( $latest_limit_change->text_length >= Common::DEFAULT_TEXT_LENGTH_MIN && $latest_limit_change->text_length < Common::LONG_TEXT_LENGTH_MIN && $text_length >= Common::DEFAULT_TEXT_LENGTH_MIN && $text_length < Common::LONG_TEXT_LENGTH_MIN ) {
			return 0;
		}

		// if new text length is within same boundaries as latest text limit change return 0, meaning no change / no new text limit change record was made
		if ( $latest_limit_change->text_length >= Common::LONG_TEXT_LENGTH_MIN && $text_length >= Common::LONG_TEXT_LENGTH_MIN ) {
			return 0;
		}

		// in all other cases boundaries must have been overstepped, so add new text limit change record
		return Db_Text_Limit_Changes::add_text_limit_change( $post_id, $public_identification_id, $text_length );
	}

	/**
	 * checks if a post has reached the needed text limit for the most recent set pixel / public identification id and
	 * return the data if so
	 *
	 * @param int $post_id
	 * @param string|null $message_date
	 *
	 * @return bool|object
	 */
	public static function post_has_reached_text_limit_with_latest_pid( int $post_id, string|null $message_date = null ): bool|object {
		if ( ! $message_date || Common::is_valid_date( $message_date ) === false ) {
			$message_date = date( 'Y-m-d H:i:s' );
		}

		$periods_data = Db_Text_Limit_Changes::get_text_limit_changes_with_lastest_pid_by_post_id( $post_id );

		if ( $periods_data === false || ! is_array( $periods_data ) ) {
			return false;
		}

		// add current status to periods data
		$pixel = Db_Pixels::get_pixel_by_post_id( $post_id );

		if ( ! $pixel || ! $pixel->public_identification_id || $pixel->public_identification_id != $periods_data[0]['public_identification_id'] ) {
			return false;
		}

		$current_text_length = self::calculate_post_text_length( $post_id );

		$periods_data[] = [
			'changed_at'               => $message_date,
			'text_length'              => $current_text_length,
			'public_identification_id' => $pixel->public_identification_id
		];

		$periods_count = count( $periods_data ) - 1;

		$total_days            = 0;
		$default_text_met_days = 0;
		$long_text_met_days    = 0;

		for ( $i = 0; $i < $periods_count; $i ++ ) {
			$period_start = strtotime( $periods_data[ $i ]['changed_at'] );
			$period_end   = strtotime( $periods_data[ $i + 1 ]['changed_at'] );
			$period_days  = ( $period_end - $period_start ) / 86400;

			$total_days += $period_days;
			if ( $periods_data[ $i ]['text_length'] >= Common::LONG_TEXT_LENGTH_MIN ) {
				$long_text_met_days    += $period_days;
				$default_text_met_days += $period_days;
			} else if ( $periods_data[ $i ]['text_length'] >= Common::DEFAULT_TEXT_LENGTH_MIN ) {
				$default_text_met_days += $period_days;
			}
		}

		$result                             = new \stdClass();
		$result->default_text_length        = [
			'met_days'   => $default_text_met_days,
			'percentage' => $default_text_met_days / $total_days
		];
		$result->long_text_length           = [
			'met_days'   => $long_text_met_days,
			'percentage' => $long_text_met_days / $total_days
		];
		$result->full_period_days           = $total_days;
		$result->default_text_limit_reached = $result->default_text_length['percentage'] >= Common::TEXT_LIMIT_MINIMUM_PERCENTAGE;
		$result->long_text_limit_reached    = $result->long_text_length['percentage'] >= Common::TEXT_LIMIT_MINIMUM_PERCENTAGE;
		$result->post_id                    = $post_id;
		$result->period_start_date          = $periods_data[0]['changed_at'];
		$result->period_end_date            = $message_date;

		return $result;
	}

	/**
	 * if given page (by param or $_REQUEST['page]) is within allowed pages array, redirect to it
	 *
	 * @param string|null $page
	 *
	 * @param string|null $notification_key
	 *
	 * @param string|null $custom_text
	 *
	 * @return void
	 */
	public static function redirect_to_vgw_metis_page( string|null $page = null, string|null $notification_key = null, string|null $custom_text = null ): void {
		$redirect_page = $page ?? sanitize_key( empty( $_REQUEST['page'] ) ? '' : $_REQUEST['page'] );

		if ( in_array( $redirect_page, self::ALLOWED_PAGES ) ) {
			$url = 'admin.php?page=' . $redirect_page;
			if ( $notification_key ) {
				$url .= '&notice=' . $notification_key;
			}
			if ( $custom_text ) {
				$url .= '&custom_text=' . urlencode( $custom_text );
			}

			wp_redirect( admin_url( $url ) );
			exit;
		}
		// if we don't recognize the current page, redirect to the first allowed page (settings)
		$url = 'admin.php?page=' . self::ALLOWED_PAGES[0];
		if ( $notification_key ) {
			$url .= '&notice=' . $notification_key;
		}
		if ( $custom_text ) {
			$url .= '&custom_text=' . urlencode( $custom_text );
		}
		wp_redirect( admin_url( $url ) );
		exit;
	}

	/**
	 * let's check if we have an api key available, else try to redirect to current page
	 *
	 * no api key warning should be shown anyway
	 *
	 * @return void
	 */
	public static function has_api_key_or_redirect(): void {
		// get the api key
		$api_key = get_option( 'wp_metis_api_key' );

		// if we don't have an api key, redirect to current page (if allowed) and show error notification
		if ( ! $api_key ) {
			self::redirect_to_vgw_metis_page();
		}
	}


	/**
	 * checks the pixel and text length for the given post and saves the text length if needed
	 *
	 * @param int $post_id
	 *
	 * @return bool | int returns false on error, 0 if nothing has changed, 1 if a new record has been created
	 */
	public static function check_post_and_save_text_length_change( int $post_id ): bool|int {
		if ( $post_id <= 0 ) {
			return false;
		}

		$pixel = self::get_pixel_for_post( $post_id );

		$pid = '';

		if ( $pixel && Common::is_valid_pixel_id_format( $pixel->public_identification_id ) ) {
			$pid = $pixel->public_identification_id;
		}

		$text_length = get_post_meta( $post_id, '_metis_text_length', true );

		if ( ! $text_length ) {
			$text_length = self::calculate_post_text_length( $post_id );
		}

		if ( $text_length >= 0 ) {
			return self::add_text_limit_change_if_needed( $post_id, $pid, $text_length );
		}

		return false;
	}

	/**
	 * checks if the given post has text limit changes
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public static function post_has_text_limit_changes( int $post_id ): bool {
		$text_limit_changes = Db_Text_Limit_Changes::get_all_text_limit_changes_by_post_id( $post_id );
		if ( empty( $text_limit_changes )) {
			return false;
		}

        return true;
    }

	private function _check_pixel_domain($pixel) {
		if (str_contains($pixel->get_domain(), Common::PIXEL_DOMAIN)) return true;
		return false;
	}

	public static function save_post_context( int $post_id ): void {
		// set text type accordingly
		Services::set_text_type( $post_id );
		// set text length accordingly
		Services::set_text_length( $post_id );

		// check if we need to create a new text limit change record
		$current_text_length = (int) get_post_meta( $post_id, "_metis_text_length", true );
		$pixel               = Db_Pixels::get_pixel_by_post_id( $post_id );
		if ( is_object( $pixel ) ) {
			$public_identification_id = $pixel->public_identification_id;
		} else {
			$public_identification_id = '';
		}

		Services::add_text_limit_change_if_needed( $post_id, $public_identification_id, $current_text_length );
	}
	
}
