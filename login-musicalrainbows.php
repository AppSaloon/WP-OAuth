<?php

// start the user session for maintaining individual user states during the multi-stage authentication flow:
if (!isset($_SESSION)) {
	session_start();
}

include plugin_dir_path(__FILE__) . 'vendor/autoload.php';

# DEFINE THE OAUTH PROVIDER AND SETTINGS TO USE #
$_SESSION['WPOA']['PROVIDER'] = 'Musicalrainbows';
define('HTTP_UTIL', get_option('wpoa_http_util'));
define('CLIENT_ENABLED', get_option('wpoa_musicalrainbows_api_enabled'));
define('CLIENT_ID', get_option('wpoa_musicalrainbows_api_id'));
define('CLIENT_SECRET', get_option('wpoa_musicalrainbows_api_secret'));
define('REDIRECT_URI', rtrim(site_url(), '/') . '/');
define('SCOPE', 'basic');
define('URL_AUTH', "https://musicalrainbows.be/oauth/authorize");
define('URL_TOKEN', "https://musicalrainbows.be/oauth/token?");
define('URL_USER', "https://musicalrainbows.be/oauth/me");
# END OF DEFINE THE OAUTH PROVIDER AND SETTINGS TO USE #

$client = new OAuth2\Client(CLIENT_ID, CLIENT_SECRET);
if (!isset($_GET['code']))
{
	/**
	 * Get authorization code
	 */
	$auth_url = $client->getAuthenticationUrl(URL_AUTH, REDIRECT_URI);
	header('Location: ' . $auth_url);
	die('Redirect');
}
else
{
	/**
	 * Get access token
	 */
	$params = array('code' => $_GET['code'], 'redirect_uri' => REDIRECT_URI);
	$response = $client->getAccessToken(URL_TOKEN, 'authorization_code', $params);

	/**
	 * Get user credentials
	 */
	$url = URL_USER . '?access_token='.$response['result']['access_token'];
	$output = wp_remote_get( $url );

	if( ! is_wp_error( $output ) && $output['response']['code'] === 200 ) {
		$user = json_decode($output['body']);

		$oauth_identity = array();
		$oauth_identity['provider'] = $_SESSION['WPOA']['PROVIDER'];
		$oauth_identity['id'] = $user->ID;
		$oauth_identity['email'] = $user->user_email;

		/**
		 * Sign/login user
		 */
		$this->wpoa_login_user($oauth_identity);
	} else {
		/**
		 * Something went wrong
		 */
		$this->wpoa_end_login('Sorry, we couldn\'t log you in. Please notify the admin or try again later.');
	}
}
?>