<?php
header( 'Content-Type: text/html; charset=utf-8' );
date_default_timezone_set( 'America/Sao_Paulo' );
libxml_use_internal_errors( true );

require_once 'class.TripCrawlerDecolarCom.php';

if ( ! class_exists( 'TripCrawlerDecolarCom' ) ) {
	die( 'Class TripCrawlerDecolarCom does not load' );
}

$settings = array(
	// Required parameters

	'price'          => 350,
	'phantomjs_bin'  => 'vendor/bin/phantomjs',

	// Email alert required parameters

	//'email_from'     => '',
	//'email_to'       => '',

	// Pushover alert required parameters
	
	//'pushover_token' => '',
	//'pushover_user'  => '',

	// Optional parameters

	//'now'                  => '', // default date( 'd/m H:i' )
	//'email_from_name'      => '', // default 'Trip Crawler'
	//'email_subject_prefix' => '', // default '[crawler]'
	//'pushover_url_api'     => '', // default 'https://api.pushover.net/1/messages.json'
	//'pushover_title'       => '', // default 'Trip Crawler'
	//'pushover_url_title'   => '', // default 'Decolar.com'
);

$trip_crawler = new TripCrawlerDecolarCom( $settings );

$trips = array(
	array(
		'city' => 'POA/Orlando',
		'url'  => 'http://www.decolar.com/passagens-aereas/poa/orl/passagens-aereas-para-orlando-saindo-de-porto+alegre',
	),
	array(
		'city' => 'POA/Miami',
		'url'  => 'http://www.decolar.com/passagens-aereas/poa/mia/passagens-aereas-para-miami-saindo-de-porto+alegre',
		'price' => 450,
	),
);

foreach ( $trips as $trip ) {
	if ( isset( $trip['price'] ) ) {
		$trip_crawler->check_flights( $trip['city'], $trip['url'], $trip['price'] );
	} else {
		$trip_crawler->check_flights( $trip['city'], $trip['url'] );
	}
}
