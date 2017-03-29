<?php
/**
 * Trip Crawler Class
 *
 * @class    TripCrawlerDecolarCom
 * @author   Fernando Tessmann
 * @since    0.1.0
 * @package  trip-crawler-decolar-com
 */
class TripCrawlerDecolarCom {

	/**
	 * Time now
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $now;

	/**
	 * Flights target price
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $price;

	/**
	 * PhantomJS bin file
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $phantomjs_bin;

	/**
	 * Base email
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $email_from;

	/**
	 * Base email name
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $email_from_name;

	/**
	 * Recipient email
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $email_to;

	/**
	 * Email subject prefix
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $email_subject_prefix;

	/**
	 * Pushover App url API
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $pushover_url_api;

	/**
	 * Pushover App token
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $pushover_token;

	/**
	 * Pushover App user
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $pushover_user;

	/**
	 * Pushover App title
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $pushover_title;

	/**
	 * Pushover App url title
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $pushover_url_title;

	/**
	 * Log file
	 *
	 * @var 	string
	 * @access  private
	 * @since   0.1.0
	 */
	private $log_file;

	/**
	 * Setup class
	 *
	 * @access  public
	 * @since   0.1.0
	 */
	public function __construct( $settings = array() ) {
		require_once 'vendor/autoload.php';

		$this->now           = ( isset( $settings['now'] )           && ! empty( $settings['now'] ) )           ? $settings['now']           : date( 'd/m H:i' );
		$this->price         = ( isset( $settings['price'] )         && ! empty( $settings['price'] ) )         ? $settings['price']         : null;
		$this->phantomjs_bin = ( isset( $settings['phantomjs_bin'] ) && ! empty( $settings['phantomjs_bin'] ) ) ? $settings['phantomjs_bin'] : null;

		$this->email_from           = ( isset( $settings['email_from'] )           && ! empty( $settings['email_from'] ) )           ? $settings['email_from']           : null;
		$this->email_from_name      = ( isset( $settings['email_from_name'] )      && ! empty( $settings['email_from_name'] ) )      ? $settings['email_from_name']      : 'Trip Crawler';
		$this->email_to             = ( isset( $settings['email_to'] )             && ! empty( $settings['email_to'] ) )             ? $settings['email_to']             : null;
		$this->email_subject_prefix = ( isset( $settings['email_subject_prefix'] ) && ! empty( $settings['email_subject_prefix'] ) ) ? $settings['email_subject_prefix'] : '[crawler]';

		$this->pushover_url_api   = ( isset( $settings['pushover_url_api'] )   && ! empty( $settings['pushover_url_api'] ) )   ? $settings['pushover_url_api']   : 'https://api.pushover.net/1/messages.json';
		$this->pushover_token     = ( isset( $settings['pushover_token'] )     && ! empty( $settings['pushover_token'] ) )     ? $settings['pushover_token']     : null;
		$this->pushover_user      = ( isset( $settings['pushover_user'] )      && ! empty( $settings['pushover_user'] ) )      ? $settings['pushover_user']      : null;
		$this->pushover_title     = ( isset( $settings['pushover_title'] )     && ! empty( $settings['pushover_title'] ) )     ? $settings['pushover_title']     : 'Trip Crawler';
		$this->pushover_url_title = ( isset( $settings['pushover_url_title'] ) && ! empty( $settings['pushover_url_title'] ) ) ? $settings['pushover_url_title'] : 'Decolar.com';

		$this->log_file = dirname(__FILE__) . '/log';

		if ( empty( $this->price ) ) {
			die( 'Required setting "price" is missing' );
		} elseif ( empty( $this->phantomjs_bin ) ) {
			die( 'Required setting "phantomjs_bin" is missing' );
		}

		$this->clean_log();
	}

	/**
	 * Check flights from $city
	 *
	 * @access  public
	 * @since   0.1.0
	 */
	public function check_flights( $city, $url, $price = null ) {
		$price = ( ! empty( $price ) ) ? $price : $this->price;
		$promotion = $this->get_promotion( $city, $url, $price );

		if ( $promotion['send_alert'] ) {
			$subject = $promotion['subject'];
			$message = $this->get_html_message( $promotion['desc'] );
			
			$this->send_email( $subject, $message );
			$this->send_push( $subject, $url );
		}

		$this->set_log( $promotion );
	}

	/**
	 * Crawler the HTML looking for promotions
	 *
	 * @access  private
	 * @since   0.1.0
	 */
	private function get_promotion( $city, $url, $target_price ) {
		$message = array(
			'subject'    => $city,
			'desc'       => '',
			'best_price' => '',
			'send_alert' => false,
			'error'      => '',
		);
		
		// Request #1
		$html = $this->get_url_content( $url );

		if ( empty( $html ) ) {
			// Request #2 (if the #1 fail)
			$html = $this->get_url_content( $url );
		}
		
		if ( ! is_numeric( $html ) ) {
			$dom = new DOMDocument();
			$dom->loadHTML( $html );
			$xpath = new DOMXPath( $dom );
			$prices = $xpath->query( '//span[contains(@class,"amount price-amount")]' );

			if ( count( $prices ) > 0 ) {
				foreach ( $prices as $price ) {
					$price = $price->nodeValue;
					$price = str_replace( '.', '', $price );
					$price_int = (int) preg_replace( '/[^0-9]/', '', $price );

					if ( '' === $message['best_price'] || ( $price_int > 0 && $price_int <= $message['best_price'] ) ) {
						$message['best_price'] = $price;
					}

					if ( false === $message['send_alert'] && $price_int > 0 && $price_int <= $target_price ) {
						$message['send_alert'] = true;
					}
				}

				if ( ! empty( $message['best_price'] ) ) {
					$message['subject'] .= ' - US$ ' . $message['best_price'];
					$message['desc'] = 'Date: ' . $this->now;
					$message['desc'] .= '<br>';
					$message['desc'] .= 'Price: US$ ' . $message['best_price'];
					$message['desc'] .= '<br>';
					$message['desc'] .= 'URL: ' . $url;
				} else {
					$message['error'] = 'Empty best price';
				}
			} else {
				$message['error'] = 'Empty prices';
			}
		} else {
			$message['error'] = 'HTTP status code -> ' . $html;
		}
		
		return $message;
	}

	/**
	 * Return description wrapped by HTML
	 *
	 * @access  private
	 * @since   0.1.0
	 */
	private function get_html_message( $desc ) {
		$message = '';
		
		$message .= '<!DOCTYPE html>';
		$message .= '<html>';
		$message .= '<head>';
		$message .= '<meta charset="utf-8">';
		$message .= '</head>';
		$message .= '<body>';
		$message .= $desc;
		$message .= '</body>';
		$message .= '</html>';
		
		return $message;
	}

	/**
	 * Send email
	 *
	 * @access  private
	 * @since   0.1.0
	 */
	private function send_email( $subject, $message ) {
		if ( empty( $this->email_from ) || empty( $this->email_from_name ) || empty( $this->email_to ) || empty( $this->email_subject_prefix ) ) {
			return false;
		}

		$subject = $this->email_subject_prefix . ' ' . $subject;
		
		$headers = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		$headers .= 'From: ' . $this->email_from_name . ' <' . $this->email_from . '>';
		
		return mail( $this->email_to, $subject, $message, $headers, '-f ' . $this->email_from );
	}

	/**
	 * Send push notification to Pushover mobile app
	 *
	 * @access  private
	 * @since   0.1.0
	 */
	private function send_push( $subject, $url ) {
		if ( empty( $this->pushover_url_api ) || empty( $this->pushover_token ) || empty( $this->pushover_user ) || empty( $this->pushover_title ) || empty( $this->pushover_url_title ) ) {
			return false;
		}

		curl_setopt_array(
			$ch = curl_init(),
			array(
				CURLOPT_URL => $this->pushover_url_api,
				CURLOPT_POSTFIELDS => array(
					'token'     => $this->pushover_token,
					'user'      => $this->pushover_user,
					'message'   => $subject,
					'title'     => $this->pushover_title,
					'url'       => $url,
					'url_title' => $this->pushover_url_title,
				),
				CURLOPT_SAFE_UPLOAD => true,
				CURLOPT_RETURNTRANSFER => true,
			)
		);

		$result = curl_exec( $ch );
		curl_close( $ch );

		return true;
	}

	/**
	 * Get HTML content from $url using PhantomJS
	 *
	 * @access  private
	 * @since   0.1.0
	 */
	private function get_url_content( $url ) {
		$client = JonnyW\PhantomJs\Client::getInstance();
		$client->getEngine()->addOption( '--load-images=false' ); // Doesn't load inlined images
		$client->isLazy(); // Tells the client to wait for all resources before rendering
		$client->getEngine()->setPath( $this->phantomjs_bin );
		
		$request = $client->getMessageFactory()->createRequest( $url, 'GET' );
		$request->setTimeout( 15000 ); // Will render page if this timeout is reached and resources haven't finished loading
		
		$response = $client->getMessageFactory()->createResponse();
		$client->send( $request, $response );

		if ( 200 === $response->getStatus() || 302 === $response->getStatus() ) {
			return $response->getContent();
		}

		return $response->getStatus();
	}

	/**
	 * Clear log
	 *
	 * @access  private
	 * @since   0.1.0
	 */
	private function clean_log() {
		$message = '';
		$message .= 'Starting crawler ' . $this->now . PHP_EOL;
		$message .= PHP_EOL;

		file_put_contents( $this->log_file, $message, LOCK_EX );
		echo nl2br( $message );
		flush();
	}

	/**
	 * Save log
	 *
	 * @access  private
	 * @since   0.1.0
	 */
	private function set_log( $promotion ) {
		$message = '';
		$message .= $promotion['subject'] . PHP_EOL;

		if ( ! empty( $promotion['error'] ) ) {
			$message .= 'ERROR: ' . $promotion['error'] . PHP_EOL;
		} elseif ( true === $promotion['send_alert'] ) {
			$message .= 'SENDING ALERTS' . PHP_EOL;
		}

		$message .= PHP_EOL;
		
		file_put_contents( $this->log_file, $message, FILE_APPEND | LOCK_EX );
		echo nl2br( $message );
		flush();
	}

}
