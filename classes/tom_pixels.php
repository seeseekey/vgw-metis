<?php

namespace WP_VGWORT;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

/**
 * Class for handling all pixel related db interaction
 *
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Torben Gallob
 * @author      Michael Hillebrand
 *
 * @package     vgw-metis
 */
class Tom_Pixels {
    /**
	 * Orders a fixed number of pixels from T.O.M Rest API
	 *
	 * @param int $number_order
	 *
	 * @return object
	 */
	public static function order_pixels( int $number_order = Common::NUMBER_ORDER_PIXEL ): object {
		$pay_load = json_encode( [ 'count' => $number_order ] );
		$request  = new Request( "POST", "/api/cms/metis/rest/pixel/v1.0/order", [
			'headers' => [
				'Content-Type' => 'application/json',
			]
		], $pay_load );

		$empty                = new \StdClass;
		$empty->pixels        = [];
		$empty->domain        = '';
		$empty->orderDateTime = '';

		try {
			$result = Restclient::$client->send( $request );
		} catch ( \Exception $e ) {
			Restclient::handle_http_error( $e );
		}

		if ( isset( $result ) && $result->getStatusCode() == 200 ) {
			return json_decode( $result->getBody()->getContents() );
		}

		return $empty;
	}

    /**
	 * Checks if the API is alive
	 *
	 * success true  if everthing is OK
	 * success false if something went wrong
	 *
	 * @return bool
	 * @throws GuzzleException
	 */
	public static function health_check(): bool {

		$request = new Request( "GET", "/api/cms/status" );

		try {
			$response = Restclient::$client->send( $request );
			if ( $response->getStatusCode() == 200 ) {
				return true;
			} else {
				return false;
			}

		} catch ( \Exception $e ) {
			return false;
		}
	}

    /**
	 * Checks all given pixels with given publicIdentificationId through API
	 *
	 * API returns pixel with following attributes
	 * state           Metis_Common::API_STATE_{STATE}
	 * privateUID      private ID from given publicID
	 * publicUID       Given publicID
	 * orderDate       order date
	 * countStarted
	 * limitReachedInYear   --> JSON Array with year, state
	 * messageCreatedDate -> only exists if message has been created, then holds message creation date
	 *
	 * IF State is NOT_FOUND, NOT_OWNER no data will be given
	 *
	 * @param array $pixels plain array of pids
	 *
	 * @return bool | array success = array of pixels (can be empty), else false
	 */
	public static function check_pixel_state( array $pixels ): bool|array {

		if ( ! count( $pixels ) ) {
			return [];
		}

		$pay_load = json_encode( array( 'publicUIDs' => $pixels ) );
		$request  = new Request( "POST", "/api/cms/metis/rest/pixel/v1.0/overview", [
			'headers' => [
				'Content-Type' => 'application/json',
			]
		], $pay_load );

		try {		
			$result = Restclient::$client->send( $request );
						
			if ( isset( $result ) && $result->getStatusCode() == 200 ) {
				$result_content = $result->getBody()->getContents();
	
				return json_decode( $result_content )->pixels;
			}

		} catch ( \Exception $e ) {
			Restclient::handle_http_error( $e );
		}

		return false;
	}

    /**
     * Loads Text for Ai-Diclaimer
     *
     * success true  if everthing is OK
     * success false if something went wrong
     *
     * @return bool
     * @throws GuzzleException
     */
    public static function load_ai_disclaimer(): object
    {

        $request = new Request("GET", "/api/cms/ai-disclaimer");

        $empty = new \StdClass;
        $empty->text = '';
        $empty->yesChoice = '';
        $empty->noChoice = '';

		try {
            $response = Restclient::$client->send($request);
			if (isset($response) && $response->getStatusCode() == 200) {
                return json_decode($response->getBody()->getContents());
            } else {
                return $empty;
            }
        } catch (\Exception $e) {
            return $empty;
        }
    }

	public static function should_display_optional_functions($post_id): bool {
		$request = new Request("GET", "/api/cms/publisher-functions");
		try {
            $response = Restclient::$client->send($request);
			if (isset($response) && $response->getStatusCode() == 200) {
                return json_decode($response->getBody()->getContents());
            } else {
                return false;
            }
        } catch (\Exception $e) {
			error_log( 'API Request Error: ' . $e->getMessage() );
            return false;
        }
	}
}