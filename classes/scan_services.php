<?php
namespace WP_VGWORT;

class Scan_Services extends Services {

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
					Db_Pixels::remove_pixel_from_post( $assignedPixel->public_identification_id, $post->ID );
					$this->_log .= "Removing old pixel[" . $assignedPixel->public_identification_id . "] from post '" . $post->post_title . "'.\n";
					if (Db_Pixels::assign_pixel_to_post($pixel->get_public_identification_id(), $post->ID)) {
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
	 * Scans post HTML for pixels. If there are none, does nothing, otherwise iterates through them and ckecks for assignment.
	 *
	 * @param post post
	 *
	 * @return int returns assognment status
	 */
	public function scan_post_for_pixels($post) {
		$this->_log .= "Scanning post '" . $post->post_title . "' for pixels.\n";
		// Get all pixels with correct domain
		$pixels = $this->search_for_pixels_in_content($post->post_content);
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
			'numberposts' => - 1,
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