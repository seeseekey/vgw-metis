<?php
namespace WP_VGWORT;

class Scan_Services extends Services {

	private const FRONTEND_SCAN_TIMEOUT_IN_SECONDS = 3;

    /**
	 * Scans post HTML for pixel. Checks if there is any other pixels already assigned to post and if not assignes it.
	 * It there is already pixel on post and is not the same, it unsaignes it and replaces it.
	 *
	 * @param pixel checked pixel
	 * @param post post
	 *
	 * @return int returns assognment status
	 */
	private function _scan_post_for_pixel($pixel, $post) {
		if($this->_check_html_pixel_validity_and_persist($pixel)) {
			Services::save_post_context($post->ID);
			// Assign to post
			$assignedPixel = Db_Pixels::get_pixel_by_post_id($post->ID);
			// Check if the pixel is the same as in HTML
			if ($assignedPixel != null) {
				if($assignedPixel->public_identification_id != $pixel->get_public_identification_id()) {
					if (Db_Pixels::replace_pixel_for_post($pixel->get_public_identification_id(), $post->ID)) {
						$this->_log .= "New pixel[" . $pixel->get_public_identification_id() . "] reassigned to post '" . $post->post_title . "'.\n";
						$this->_new_assigned_pixels++;
						return Assignment::REASSIGNED;
					} else {
						$this->_log .= "Pixel[" . $pixel->get_public_identification_id() . "] could not be assigned to post '" . $post->post_title . "'.\n";
						$this->_failure++;
						return Assignment::FAILED;
					}
				} else {
					$this->_log .= "Pixel[" . $pixel->get_public_identification_id() . "] already assigned to post '" . $post->post_title . "'.\n";
					$this->_already_found++;
					return Assignment::SKIPPED;
				}
			} else {
				if (Db_Pixels::assign_pixel_to_post($pixel->get_public_identification_id(), $post->ID)) {
					$this->_log .= "New pixel[" . $pixel->get_public_identification_id() . "] assigned to post '" . $post->post_title . "'.\n";
					$this->_new_assigned_pixels++;
					return Assignment::ASSIGNED;
				} else {
					$this->_log .= "Pixel[" . $pixel->get_public_identification_id() . "] could not be assigned to post '" . $post->post_title . "'.\n";
					$this->_failure++;
					return Assignment::FAILED;
				}
			}
		}
		return Assignment::FAILED;
	}

	/**
	 * Fetches the rendered frontend HTML for a post, if available.
	 *
	 * @param post $post
	 *
	 * @return string|null
	 */
	private function get_rendered_post_html($post): ?string {

		$permalink = get_permalink($post);

		if (empty($permalink)) {
			$this->_log .= "No permalink found for post '" . $post->post_title . "'.\n";
			return null;
		}

		$response = wp_remote_get(
			$permalink,
			[
				'timeout' => self::FRONTEND_SCAN_TIMEOUT_IN_SECONDS,
			]
		);

		if (is_wp_error($response)) {
			$this->_log .= "Rendered HTML for post '" . $post->post_title . "' could not be fetched: " . $response->get_error_message() . "\n";
			return null;
		}

		$response_code = wp_remote_retrieve_response_code($response);

		if ($response_code < 200 || $response_code >= 300) {
			$this->_log .= "Rendered HTML for post '" . $post->post_title . "' returned HTTP status " . $response_code . ".\n";
			return null;
		}

		$body = wp_remote_retrieve_body($response);

		if (empty($body)) {
			$this->_log .= "Rendered HTML for post '" . $post->post_title . "' was empty.\n";
			return null;
		}

		return $body;
	}

	/**
	 * Removes duplicate pixels from multiple scan sources.
	 *
	 * @param Pixel[] $pixels
	 *
	 * @return Pixel[]
	 */
	private function deduplicate_pixels(array $pixels): array {

		$deduplicated_pixel_ids = [];
		$deduplicated_pixels    = [];

		foreach ($pixels as $pixel) {

			$public_identification_id = $pixel->get_public_identification_id();
			$public_identification_id_key = strtolower($public_identification_id);

			if (isset($deduplicated_pixel_ids[$public_identification_id_key])) {
				continue;
			}

			$deduplicated_pixel_ids[$public_identification_id_key] = true;
			$deduplicated_pixels[]                                 = $pixel;
		}

		return $deduplicated_pixels;
	}

	/**
	 * Scans post HTML for pixels. If there are none, does nothing, otherwise iterates through them and ckecks for assignment.
	 *
	 * @param post post
	 *
	 * @return int returns assognment status
	 */
	public function scan_post_for_pixels($post) {
		$this->_log .= "Scanning post '" . $post->post_title . "' for pixels.\n";
		$pixels = $this->search_for_pixels_in_content($post->post_content);

		if (empty($pixels)) {
			$rendered_html = $this->get_rendered_post_html($post);

			if ($rendered_html !== null) {
				$pixels = $this->search_for_pixels_in_content($rendered_html);
			}
		}

		$pixels = $this->deduplicate_pixels($pixels);
		if (empty($pixels)) {
			$this->_log .= "No pixels found in post '" . $post->post_title . "'.\n";
			return Assignment::NONE;
		}
		
		$this->_log .= count($pixels) . " pixels found.\n";
		foreach ($pixels as $pixel) {
			$this->_log .= "Checking pixel[" . $pixel->get_public_identification_id() . "] ...\n";
			switch($this->_scan_post_for_pixel($pixel, $post)) {
				case Assignment::ASSIGNED: {
					$this->_log .= "*** Pixel[" . $pixel->get_public_identification_id() . "] in post '" . $post->post_title . "' assigned.\n";
					return Assignment::ASSIGNED;
				}
				case Assignment::REASSIGNED: {
					$this->_log .= "*** Pixel[" . $pixel->get_public_identification_id() . "] in post '" . $post->post_title . "' reassigned.\n";
					return Assignment::REASSIGNED;
				}
				case Assignment::REACTIVATED: {
					$this->_log .= "*** Pixel[" . $pixel->get_public_identification_id() . "] in post '" . $post->post_title . "' reactivated.\n";
					return Assignment::REACTIVATED;
				}
				default: {
					break;
				}
			}
		}
		return Assignment::NONE;
	}

    /**
	 * Scan all post contents for directly inserted pixels in content and try to assign it
	 *
	 * Function reads all posts from DB and searches the content for pixel image. When a pixel is found and the pixel
	 * exists in table it will be assigned.
	 *
	 * @return string | null
	 */
	public static function scan_posts_for_pixels(): null|string {
		$services = new Scan_Services();
		$args  = array(
			'post_type'   => array( 'page', 'post' ),
			'post_status' => 'publish',
			'numberposts' => - 1,
			'orderby'     => 'ID',
			'order'       => 'ASC',
		);
		$posts = get_posts( $args );
		try {
			foreach ( $posts as $post ) {
				$services->scan_post_for_pixels($post);
			}
		} catch ( \Exception $e ) {
			return null;
		}

		$stat = $services->get_stat();
		// create return msg
		return esc_html__( " Neue Zuweisungen: ", 'vgw-metis' ) .
			   $stat['new_assigned_pixels'] .
		       ". " .
		       esc_html__( " Bereits vorhanden: ", 'vgw-metis' ) .
		       $stat['already_found'] .
		       "." .
		       esc_html__( " Fehlerhaft: ", 'vgw-metis' ) .
		       $stat['failure'];
	}

	/**
	 * Count all published posts and pages that can be scanned.
	 *
	 * @return int
	 */
	public static function get_scan_posts_count(): int {
		$count = 0;
		foreach ( array( 'page', 'post' ) as $post_type ) {
			$post_count = wp_count_posts( $post_type );
			if ( isset( $post_count->publish ) ) {
				$count += (int) $post_count->publish;
			}
		}

		return $count;
	}

	/**
	 * Scan a limited batch of published posts and pages.
	 *
	 * @param int $offset Batch offset.
	 * @param int $limit  Batch size.
	 *
	 * @return array
	 */
	public static function scan_posts_for_pixels_batch( int $offset, int $limit ): array {
		$offset   = max( 0, $offset );
		$limit    = max( 1, $limit );
		$total    = self::get_scan_posts_count();
		$services = new Scan_Services();
		$args     = array(
			'post_type'   => array( 'page', 'post' ),
			'post_status' => 'publish',
			'numberposts' => $limit,
			'offset'      => $offset,
			'orderby'     => 'ID',
			'order'       => 'ASC',
		);
		$posts    = get_posts( $args );

		foreach ( $posts as $post ) {
			$services->scan_post_for_pixels( $post );
		}

		$processed   = count( $posts );
		$next_offset = min( $total, $offset + $processed );
		$stat        = $services->get_stat();

		return array(
			'processed'           => $processed,
			'total'               => $total,
			'next_offset'         => $next_offset,
			'done'                => $next_offset >= $total || $processed === 0,
			'new_assigned_pixels' => $stat['new_assigned_pixels'],
			'already_found'       => $stat['already_found'],
			'failure'             => $stat['failure'],
		);
	}

    /**
	 * Gets counter statistics and log
	 *
	 * @return string | null
	 */
	public function get_stat() {
        return [
            'new_assigned_pixels' => $this->_new_assigned_pixels,
            'already_found' => $this->_already_found,
            'failure' => $this->_failure,
			'log' => $this->_log
        ];
    }

}
