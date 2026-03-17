<?php
namespace WP_VGWORT;

class Assignment_Services extends Services {

	
	public static function assign_pixel_to_post(int $post_id ): int {
		$services = new Assignment_Services();
		return $services->_assign_pixel_to_post($post_id);
	}

	/**
     * Central method for handling all pixel assignments
     * 
     * @param int $post_id
     * @param string|null $manual_pixel_id For manual assignments
     * @return array ['status' => int, 'message' => string]
     */
    public static function handle_pixel_assignment(int $post_id, ?string $manual_pixel_id = null): array {
        $services = new self();
        
        // For manual assignment
        if ($manual_pixel_id) {
            return $services->handle_manual_assignment($post_id, $manual_pixel_id);
        }
        
        // For automatic assignment
        return $services->handle_automatic_assignment($post_id);
    }

    /**
     * Handle manual pixel assignment
     */
    private function handle_manual_assignment(int $post_id, string $public_identification_id): array {
        // Validate pixel format
        if (!Common::is_valid_pixel_id_format($public_identification_id)) {
            return ['status' => Assignment::FAILED, 'message' => 'invalid-format'];
        }

        // Check ownership and validity
        $validity = self::is_valid_and_ownership_check($public_identification_id);
        if ($validity === false) {
            return ['status' => Assignment::FAILED, 'message' => 'api-error'];
        }

        // Handle existing pixel
        $current_pixel = DB_Pixels::get_pixel_by_post_id($post_id);
        if ($current_pixel) {
            if ($current_pixel->public_identification_id === $public_identification_id) {
                return ['status' => Assignment::SKIPPED, 'message' => 'already-assigned'];
            }
            
            if (!DB_Pixels::remove_pixel_from_post($current_pixel->public_identification_id, $post_id)) {
                return ['status' => Assignment::FAILED, 'message' => 'removal-failed'];
            }
        }

        // Insert or update pixel
        if (!$this->ensure_pixel_in_database($public_identification_id)) {
            return ['status' => Assignment::FAILED, 'message' => 'insert-failed'];
        }

        // Assign pixel
        if (!DB_Pixels::assign_pixel_to_post($public_identification_id, $post_id)) {
            return ['status' => Assignment::FAILED, 'message' => 'assign-failed'];
        }

        $posts_count = DB_Pixels::get_assigned_posts_count($public_identification_id);
        return [
            'status' => Assignment::ASSIGNED,
            'message' => $posts_count > 1 ? 'multiple-assignment' : 'success'
        ];
    }

    /**
     * Handle automatic pixel assignment
     */
    private function handle_automatic_assignment(int $post_id): array {
        return ['status' => $this->_assign_pixel_to_post($post_id), 'message' => ''];
    }

	/**
	 * Unassign a pixel from a post.
	 *
	 * @param $post_id
	 *
	 * @return bool return ok or error
	 */
	public static function unassign_pixel_from_post( $post_id ): int | bool {
		
		$pixel = DB_Pixels::get_pixel_by_post_id( $post_id );
		
		if ( $pixel != null ) {
			$assignedCount = DB_Pixels::get_assigned_posts_count( $pixel->public_identification_id );
			if($assignedCount > 1)
				return DB_Pixels::remove_pixel_posts_relation( $pixel->public_identification_id, $post_id );
			return DB_Pixels::set_pixel_activation_status( $pixel->public_identification_id, false, $post_id );
		}

		return false;
	}

    /**
	 * Assigns the next free Pixel to the post and sets active state to true
	 * Also shecks if in Wordpress post already are pixels set. If yes, it will use those pixels instead getting new ones from the TOM
	 *
	 * @param $post_id
	 *
	 * @return bool success status
	 */
	private function _assign_pixel_to_post(int $post_id ): int {
		$post = get_post( $post_id );
		$pixels = $this->search_for_pixels_in_content($post->post_content);

		// If there are pixels in the HTML
		$this->_log .= "Checking found pixels on post '" . $post->post_title . "'.\n";
		foreach ($pixels as $pixel) {
			if($this->_check_html_pixel_validity_and_persist($pixel)) {
				// Method return assignment status or false. If false
				$result = $this->_assign_html_pixel_to_post($pixel, $post);
				if($result) 
					return $result;
			}			
		}

		// No pixels in HTML. Follow classical insert
		if (empty($pixels)) {
			$this->_log .= "No pixels found in post '" . $post->post_title . "'. Assigning new pixel.\n";
		}
		$pixel = DB_Pixels::get_pixel_by_post_id( $post_id );
		/* Only assign if no pixel was found */
		if ( $pixel == null ) {
			$pixel = DB_Pixels::get_next_free_pixel();
			if ( $pixel != null ) {
				$this->_log .= "Assigning next free pixel[" . $pixel->public_identification_id . "].\n";
				if(DB_Pixels::assign_pixel_to_post( $pixel->public_identification_id, $post_id ))
					return Assignment::ASSIGNED;
			}
			$this->_log .= "No free pixels. Ordering new pixels from TOM.\n";
			self::order_pixels_if_needed(3);
			$this->_log .= "Getting next free obtained free pixel.\n";
			$pixel = DB_Pixels::get_next_free_pixel();
			if ( $pixel != null ) {
				$this->_log .= "Assigning next free pixel[" . $pixel->public_identification_id . "].\n";
				if(DB_Pixels::assign_pixel_to_post( $pixel->public_identification_id, $post_id )) {
					return Assignment::ASSIGNED;
				}
			}
			$this->_log .= "Failure by obtaining free pixel.\n";
			$this->_failure++;
			return Assignment::FAILED;
		}
		// Check if pixel is already active
		if($pixel->active) {
			$this->_log .= "Pixel[" . $pixel->public_identification_id . "] on post already active.\n";
			return Assignment::SKIPPED;
		}
		if(DB_Pixels::set_pixel_activation_status( $pixel->public_identification_id, true, $post_id )) {
			$this->_log .= "Changing activation status of pixel[" . $pixel->public_identification_id . "] assigned to post.\n";
			return Assignment::REACTIVATED;
		}
		$this->_log .= "Pixel[" . $pixel->public_identification_id . "] activation to post skipped.\n";
		return Assignment::SKIPPED;
	}

    /**
	 *  Assigns pixel in HTML. Checks if there is any other pixels already assigned to post and if not assignes it.
	 * It there is already pixel on post and is not the same, it unsaignes it and replaces it.
	 *
	 * @param pixel checked pixel
	 * @param post post
	 *
	 * @return int returns assognment status
	 */
	private function _assign_html_pixel_to_post($pixel, $post) {
		// Only VALID pixels which are already in database
		// Check if the pixel is already assigned to the post

		// Get Associated pixel from database
		$existingAssociatedPixel = Db_Pixels::get_pixel_by_post_id($post->ID);

		if($existingAssociatedPixel) {
			// If post has associated pixel that is the same as the pixel in HTML
			if($existingAssociatedPixel->public_identification_id === $pixel->get_public_identification_id()) {

				// Now, we get a pixel that has the same public id, but we must check if it is active or not
				// Check reservation (If the pixel is not active - activate)
				if(!$existingAssociatedPixel->active) {
					$this->_log .= "Changing activation status of pixel[" . $pixel->get_public_identification_id() . "] assigned to post '" . $post->post_title . "'.\n";
					Db_Pixels::set_pixel_activation_status( $pixel->get_public_identification_id(), true, $post->ID );
					return Assignment::REACTIVATED;
				}
				$this->_log .= "Pixel[" . $pixel->get_public_identification_id() . "] on post '" . $post->post_title . "' already active.\n";
				return Assignment::NONE;
			// If the already associated pixel is not the same sa the one in HTML, delete existing pixel connection and add associate new pixel
			} else {
				if(Db_Pixels::remove_pixel_from_post($existingAssociatedPixel->public_identification_id,  $post->ID)) {
					// Now that the existing pixel was successfuly removed, assign new pixel
					if (Db_Pixels::assign_pixel_to_post($pixel->get_public_identification_id(), $post->ID)) {
						// Successfully assigned the first available pixel
						$this->_log .= "New pixel[" . $pixel->get_public_identification_id() . "] assigned to post '" . $post->post_title . "'.\n";
						return Assignment::REASSIGNED; 
					}
					$this->_log .= "Pixel[" . $pixel->get_public_identification_id() . "] could not be reasigned to post '" . $post->post_title . "'.\n";
				}
			}
			$this->_log .= "Failure by assigning pixel[" . $pixel->get_public_identification_id() . "] to post '" . $post->post_title . "'.\n";
			$this->_failure++;
			return false;
		} else {
			// Assign pixel to the post if not already assigned
			if (Db_Pixels::assign_pixel_to_post($pixel->get_public_identification_id(), $post->ID)) {
				// Successfully assigned the first available pixel
				$this->_log .= "New pixel[" . $pixel->get_public_identification_id() . "] assigned to post '" . $post->post_title . "'.\n";
				return Assignment::ASSIGNED; 
			}
			
			$this->_log .= "New pixel[" . $pixel->get_public_identification_id() . "] can not be assigned to post '" . $post->post_title . "'.\n";
			return Assignment::FAILED;
		}
	}

    /**
     * Handles the removal of an existing pixel from a post
     * 
     * @param object $current_pixel The current pixel object
     * @param int $post_id The post ID
     * @return bool True if removal was successful, false otherwise
     */
    private function handle_existing_pixel_removal(object $current_pixel, int $post_id): bool {
        // Get count of posts using this pixel
        $assigned_posts_count = DB_Pixels::get_assigned_posts_count($current_pixel->public_identification_id);
        
        if ($assigned_posts_count > 1) {
            // If pixel is used by multiple posts, just remove the relation
            return DB_Pixels::remove_pixel_posts_relation(
                $current_pixel->public_identification_id, 
                $post_id
            );
        } else {
            // If pixel is used by only one post, check if it's assigned to the current post before deactivating
            if ($assigned_posts_count == 1 && $current_pixel->post_id == $post_id) {
                return DB_Pixels::set_pixel_activation_status(
                    $current_pixel->public_identification_id, 
                    false, 
                    $post_id
                );
            }
            return true;
        }
    }

    /**
     * Ensures a pixel exists in the database and is valid
     * 
     * @param string $public_identification_id The public ID of the pixel
     * @return bool True if pixel exists and is valid in database, false otherwise
     */
    private function ensure_pixel_in_database(string $public_identification_id): bool {
        // First check if pixel already exists in database
        $existing_pixel = DB_Pixels::get_pixel_by_public_identification_id($public_identification_id);
        
        if ($existing_pixel) {
            $this->_log .= "Pixel[" . $public_identification_id . "] already exists in database\n";
            
            // Check if pixel is disabled
            if ($existing_pixel->disabled) {
                $this->_log .= "Pixel[" . $public_identification_id . "] is disabled\n";
                return false;
            }
            
            return true;
        }

        // Pixel doesn't exist, get data from API
        $this->_log .= "Fetching pixel[" . $public_identification_id . "] data from API\n";
        $api_result = Tom_Pixels::check_pixel_state([$public_identification_id]);
        
        if (!$api_result || !count($api_result)) {
            $this->_log .= "API check failed for pixel[" . $public_identification_id . "]\n";
            return false;
        }

        $pixel_data = $api_result[0];
        
        // Create new pixel object
        $new_pixel = new Pixel();
        $new_pixel->set_public_identification_id($public_identification_id);
        $new_pixel->set_source(Common::SOURCE_MANUAL);
        
        // Set private ID if pixel is valid
        if ($pixel_data->state === Common::API_STATE_VALID) {
            $new_pixel->set_private_identification_id($pixel_data->privateUID);
        }
        
        // Set ownership based on API response
        $new_pixel->set_ownership($pixel_data->state === Common::API_STATE_VALID);

        // Insert the pixel
        $result = DB_Pixels::insert_pixels([$new_pixel]);
        
        $this->_log .= $result 
            ? "Successfully inserted pixel[" . $public_identification_id . "] into database\n"
            : "Failed to insert pixel[" . $public_identification_id . "] into database\n";

        return (bool)$result;
    }

}