<?php
/*
	Copyright 2012 Sorin Iclanzan  (email : sorin@hel.io)
	Copyright 2016 Alejandro Liu  (alejandro_liu@hotmail.com)

	This file is part of Backup.

	Backup is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	Backup is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Backup. If not, see http://www.gnu.org/licenses/gpl.html.
*/

/**
 * Google OAuth2 class
 *
 * This class handles operations realted to Google's OAuth2 service.
 *
 * @uses  WP_Error for storing error messages.
 */

class GOAuth {
	/**
	 * Stores the Client Id.
	 *
	 * @var string
	 * @access  private
	 */
	private $client_id;

	/**
	 * Stores the Client Secret.
	 *
	 * @var string
	 * @access  private
	 */
	private $client_secret;

	/**
	 * Stores the redirect URI.
	 *
	 * @var string
	 * @access  private
	 */
	private $redirect_uri;

	/**
	 * Stores the refresh token.
	 *
	 * @var string
	 * @access private
	 */
	private $refresh_token;

	/**
	 * Stores the access token.
	 *
	 * @var string
	 * @access private
	 */
	private $access_token;

	/**
	 * Constructor - Assigns values to some properties.
	 *
	 * @param array $args Optional. The list of options and values to set
	 */
	function __construct( $args = array() ) {
		$default_args = array(
			'client_id'       => '',
			'client_secret'   => '',
			'redirect_uri'    => '',
			'refresh_token'   => '',
		);
		$this->set_options( array_merge( $default_args, $args ) );
	}

	/**
	 * Sets multiple options at once.
	 *
	 * @access public
	 * @param  array    $args List of options and values to set
	 * @return boolean        Returns TRUE on success, FALSE on failure.
	 */
	public function set_options( $args ) {
		if ( ! is_array( $args ) )
			return false;
		foreach ( $args as $option => $value )
			$this->set_option( $option, $value );
	}

	/**
	 * Sets an option.
	 *
	 * @access public
	 * @param  string $option The option to set.
	 * @param  mixed  $value  The value to set the option to.
	 * @return boolean        Returns TRUE on success, FALSE on failure.
	 */
	public function set_option( $option, $value ) {
	  $this->$option = ( string ) $value;
	}

	public function new_client() {
	  $client = new Google_Client();
	  $client->setClientId($this->client_id);
	  $client->setClientSecret($this->client_secret);
	  $client->setRedirectUri($this->redirect_uri);
	  return $client;
	}

	/**
	 * Requests authorization from Google's OAuth2 server to access services for a user.
	 *
	 * @access public
	 * @param  array   $scope           Array of API URLs to services where access is wanted.
	 * @param  string  $state           A string that is passed back from Google.
	 * @param  boolean $approval_prompt Indicates whether to force prompting for approval (TRUE) or not (FALSE). Defaults to FALSE.
	 * @return NULL
	 */
	public function request_authorization( $scope = array() , $state = '', $approval_prompt = false ) {
	  $client = $this->new_client();
	  $client->setApplicationName('Backup WP Plugin');
	  $client->setAccessType("offline");
	  $client->setScopes($scope);
	  if (!empty($state)) $client->setState($state);
	  if ($approval_prompt) $client->setApprovalPrompt('force');
	  $authUrl = $client->createAuthUrl();
	  header('Location: ' . $authUrl);
	}

	/**
	 * Requests a refresh token from Google's OAuth2 server.
	 *
	 * @access public
	 * @param  string $code Authorization code received from Google. If empty the method will try to get it from $_GET['code'].
	 * @return mixed        Returns a refresh token on success or an instance of WP_Error on failure.
	 */
	public function request_refresh_token( $code = '' ) {
	  if ( $code == '' ) $code = $_GET['code'];
	  $client = $this->new_client();
	  try {
	    $client->authenticate($code);
	  } catch (Exception $e) {
	    return new WP_Error('invalid_operation',$e->getMessage());
	  }
	  $this->access_token = $client->getAccessToken();
	  $this->refresh_token = $client->getRefreshToken();
	  return $this->refresh_token;
	}

	/**
	 * Requests and returns an access token from Google's OAuth2 server.
	 *
	 * @uses  wp_remote_post
	 * @access private
	 * @return mixed   Returns a new access token on success or an instance of WP_Error on failure.
	 */
	private function request_access_token() {
	  $client = $this->new_client();
	  try {
	    $client->refreshToken($this->refresh_token);
	    $this->access_token = $client->getAccessToken();
	  } catch (Exception $e) {
	    return new WP_Error('invalid_operation',$e->getMessage());
	  }
	  if (!$this->access_token) return new WP_Error('invalid_operation',"Unable to authenticate");
	  return $this->access_token;
	}

	/**
	 * Returns the access token.
	 *
	 * @access public
	 * @return mixed  Returns the access token on success, an instance of WP_Error on failure.
	 */
	public function get_access_token() {
	  if ( empty( $this->access_token ) )
	    if ( empty( $this->refresh_token ) )
	      return new WP_Error( 'invalid_operation', 'You need a refresh token in order to request an access token.' );
	    else
	      return $this->request_access_token();
	  else
	    return $this->access_token;
	}

	/**
	 * Revoke a refresh token.
	 *
	 * @uses  wp_remote_get
	 * @access public
	 * @return mixed Returns TRUE on success, an instance of WP_Error on failure.
	 */
	public function revoke_refresh_token() {
	  if (empty($this->refresh_token)) return new WP_Error( 'invalid_operation', 'There is no refresh token to revoke.' );
	  
	  $client = $this->new_client();
	  if ($client->revokeToken($this->refresh_token)) return TRUE;
	  return new WP_Error('invalid_operation','Error revoking token');
	}

	/**
	 * Checks whether the refresh token is set or not.
	 *
	 * @access public
	 * @return boolean Returns TRUE if the refresh token is set, FALSE otherwise.
	 */
	public function is_authorized() {
		return (
			!empty( $this->refresh_token ) &&
			!empty( $this->client_id ) &&
			!empty( $this->client_secret )
		);
	}
}
