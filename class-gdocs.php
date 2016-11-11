<?php
/*
	Copyright 2012 Sorin Iclanzan  (email : sorin@hel.io)

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
 * Google Docs class
 *
 * Implements communication with Google Docs via the Google Documents List v3 API.
 *
 * Currently uploading, resuming and deleting resources is implemented as well as retrieving quotas.
 *
 * @uses  WP_Error for storing error messages.
 */
class GDocs {
	/**
	 * Stores a API client object
	 * @var Google_Client
	 * @access private
	 */
	private $api_client;

	/**
	 * Files are uploadded in chunks of this size in bytes.
	 *
	 * @var integer
	 * @access private
	 */
	private $chunk_size;

	/**
	 * Stores the MIME type of the file that is uploading
	 *
	 * @var string
	 * @access private
	 */
	private $upload_file_type;

	/**
	 * Stores info about the file being uploaded.
	 *
	 * @var array
	 * @access private
	 */
	private $file;

	/**
	 * Stores the number of seconds the upload process is allowed to run
	 *
	 * @var integer
	 * @access private
	 */
	private $time_limit;

	/**
	 * Stores a timer for upload processes
	 *
	 * @var array
	 */
	private $timer;

	/**
	 * Constructor - Sets the access token.
	 *
	 * @param  Google_Client Google API client
	 */
	function __construct( $client ) {
		$this->api_client = $client;
		$this->chunk_size = 524288; // 512 KiB
		$this->max_resume_attempts = 5;
		$this->timer = array(
			'start' => 0,
			'stop'  => 0,
			'delta' => 0,
			'cycle' => 0
		);
		$this->time_limit = @ini_get( 'max_execution_time' );
		if ( ! $this->time_limit && '0' !== $this->time_limit )
			$this->time_limit = 30; // default php max exec time
	}

	/**
	 * Sets an option.
	 *
	 * @access public
	 * @param string $option The option to set.
	 * @param mixed  $value  The value to set the option to.
	 */
	public function set_option( $option, $value ) {
		switch ( $option ) {
			case 'chunk_size':
				if ( floatval($value) >= 0.5 ) {
					$this->chunk_size = floatval($value) * 1024 * 1024; // Transform from MiB to bytes
					return true;
				}
				break;
			case 'max_resume_attempts':
				$this->max_resume_attempts = intval($value);
				return true;
		}
		return false;
	}

	/**
	 * Gets an option.
	 *
	 * @access public
	 * @param string $option The option to get.
	 */
	public function get_option( $option ) {
		switch ( $option ) {
			case 'chunk_size':
				return $this->chunk_size;
			case 'max_resume_attempts':
				return $this->max_resume_attempts;
		}
		return false;
	}

	/**
	 * Deletes a resource from Google Docs.
	 *
	 * @access public
	 * @param  string $id Gdata Id of the resource to be deleted.
	 * @return mixed      Returns TRUE on success, an instance of WP_Error on failure.
	 */
	public function delete_resource( $id ) {
	  try {
	    $service = new Google_Service_Drive($this->api_client);
	    $service->files->delete($id);
	  } catch (Exception $e) {
	    return new WP_Error('invalid_operation',$e->getMessage());
	  }
	}

	/**
	 * Get used quota in bytes.
	 *
	 * @access public
	 * @return mixed  Returns the number of bytes used in Google Docs on success or an instance of WP_Error on failure.
 	 */
	public function get_quota_used() {
	  try {
	    $service = new Google_Service_Drive($this->api_client);
	    return $service->about->get(['fields'=>'storageQuota'])->getStorageQuota()->getUsage();
	  } catch (Exception $e) {
	    return new WP_Error('invalid_operation',$e->getMessage());
	  }
	}

	/**
	 * Get total quota in bytes.
	 *
	 * @access public
	 * @return string|WP_Error Returns the total quota in bytes in Google Docs on success or an instance of WP_Error on failure.
	 */
	public function get_quota_total() {
	  try {
	    $service = new Google_Service_Drive($this->api_client);
	    return $service->about->get(['fields'=>'storageQuota'])->getStorageQuota()->getLimit();
	  } catch (Exception $e) {
	    return new WP_Error('invalid_operation',$e->getMessage());
	  }
	}
	
	/**
	 * Function to prepare a file to be uploaded to Google Docs.
	 *
	 * @uses   wp_check_filetype
	 * @access public
	 *
	 * @param  string  $location Resume URI (or FALSE) if new upload
	 * @param  string  $file   Path to the file that is to be uploaded.
	 * @param  string  $title  Title to be given to the file.
	 * @param  string  $parent ID of the folder in which to upload the file.
	 * @param  string  $type   MIME type of the file to be uploaded. The function tries to identify the type if it is omitted.
	 * @return mixed           Returns the URI where to upload on success, an instance of WP_Error on failure.
	 */
	public function prepare_upload( $file, $title, $parent = '', $type = '' ) {
	  if ( ! @is_readable( $file ) )
	    return new WP_Error( 'not_file', "The path '" . $file . "' does not point to a readable file." );
	  // If a mime type wasn't passed try to guess it from the extension based on the WordPress allowed mime types
	  if ( empty( $type ) ) {
	    $check = wp_check_filetype( $file );
	    $this->upload_file_type = $type = $check['type'];
	  }
	  $size = filesize( $file );
	  
	  $service = new Google_Service_Drive($this->api_client);
	  $this->api_client->setDefer(TRUE);

	  try {
	    $gfh = new Google_Service_Drive_DriveFile();
	    $gfh->name= $title;
	    if (!empty($parent)) {
	      $pp = new Google_Service_Drive_ParentReference();
	      $pp->setId($parent);
	      $gfh->setParents([$pp]);
	    }
	    $request = $service->files->create($gfh);
	    $media = new Google_Http_MediaFileUpload(
		$this->api_client,
		$request,
		$type,
		null,
		true,
		$this->chunk_size
	    );
	    $media->setFileSize($size);
	  
	    $location = $media->getResumeUri();
	  } catch (Exception $e) {
	    return new WP_Error('invalid_operation',$e->getMessage());
	  }
	  
	  $this->file = [
		  'path'      => $file,
		  'size'      => $size,
		  'location'  => $location,
		  'pointer'   => 0,
		  'media' => $media,
	  ];

	  // Open file for reading.
	  if ( !$this->file['handle'] = fopen( $file, "rb" ) )
		  return new WP_Error( 'open_error', "Could not open file '" . $file . "' for reading." );
	  // Start timer
	  $this->timer['start'] = microtime( true );
	  return $location;
	}
	/**
	 * Resume an upload.
	 *
	 * @access public
	 * @param  string $file     Path to the file which needs to be uploaded
	 * @param  string $location URI where to upload the file
	 * @return mixed            Returns the next location URI on success, an instance of WP_Error on failure.
	 */
	public function resume_upload( $file, $location ) {
	  return new WP_Error('unimplemented','Resume functionality does not work');
		if ( ! @is_readable( $file ) )
			return new WP_Error( 'not_file', "The path '" . $this->resume_list[$id]['path'] . "' does not point to a readable file. Upload has been canceled." );
		$size = filesize( $file );
		$headers = array( 'Content-Range' => 'bytes */' . $size );
		//$result = $this->request( $location, 'PUT', $headers );
		if( is_wp_error( $result ) )
			return $result;
		if ( '308' != $result['response']['code'] ) {
			if ( '201' == $result['response']['code'] ) {
				$feed = @simplexml_load_string( $result['body'] );
				if ( $feed === false )
					return new WP_Error( 'invalid_data', "Could not create SimpleXMLElement from '" . $result['body'] . "'." );
				$this->file['id'] = substr( ( string ) $feed->children( "http://schemas.google.com/g/2005" )->resourceId, 5 );
				return true;
			}
			return new WP_Error( 'bad_response', "Received response code '" . $result['response']['code'] . " " . $result['response']['message'] . "' while trying to resume the upload of file '" . $file . "'." );
		}
		if( isset( $result['headers']['location'] ) )
			$location = $result['headers']['location'];
		$pointer = $this->pointer( $result['headers']['range'] );
		$this->file = array(
			'path'      => $file,
			'size'      => $size,
			'location'  => $location,
			'pointer'   => $pointer
		);
		// Open file for reading.
		if ( !$this->file['handle'] = fopen( $file, "rb" ) )
			return new WP_Error( 'open_error', "Could not open file '" . $file . "' for reading." );
		// Start timer
		$this->timer['start'] = microtime( true );
		return $location;
	}
	/**
	 * Uploads a chunk of the file being uploaded.
	 *
	 * @access public
	 * @return mixed   Returns TRUE if the chunk was uploaded successfully;
	 *                 returns Google Docs resource ID if the file upload finished;
	 *                 returns an instance of WP_Error on failure.
	 */
	public function upload_chunk() {
	  if ( !isset( $this->file['handle'] ) )
	    return new WP_Error( "no_upload", "There is no file being uploaded." );

	  $cycle_start = microtime( true );
	  fseek( $this->file['handle'], $this->file['pointer'] );
	  $chunk = @fread( $this->file['handle'], $this->chunk_size );
	  if ( false === $chunk )
	    return new WP_Error( 'read_error', "Failed to read from file '" . $this->file['path'] . "'." );

	  $chunk_size = strlen( $chunk );

	  try {
	    $status = $this->file['media']->nextChunk($chunk);
	    $location = $this->file['media']->getResumeUri();
	  } catch (Exception $e) {
	    return new WP_Error('invalid_operation',$e->getMessage());
	  }
	  
	  if (!$status) {
	    $this->file['pointer'] += $chunk_size;
	    $this->file['location'] = $location;
	    if ( $this->timer['cycle'] )
	      $this->timer['cycle'] = ( microtime( true ) - $cycle_start + $this->timer['cycle'] ) / 2;
	    else
	      $this->timer['cycle'] = microtime(true) - $cycle_start;
	    return $location;
	  }
	  $this->api_client->setDefer(FALSE);
	  fclose( $this->file['handle'] );
	  // Stop timer
	  $this->timer['stop'] = microtime(true);
	  $this->timer['delta'] = $this->timer['stop'] - $this->timer['start'];
	  if ( $this->timer['cycle'] )
	    $this->timer['cycle'] = ( microtime( true ) - $cycle_start + $this->timer['cycle'] ) / 2;
	  else
	    $this->timer['cycle'] = microtime(true) - $cycle_start;

	  $this->file['pointer'] = $this->file['size'];
	  $this->file['id'] = $status->getId();
	  return TRUE;
	}

	/**
	 * Get the resource ID of the most recent uploaded file.
	 *
	 * @access public
	 * @return string The ID of the uploaded file or an empty string.
	 */
	public function get_file_id() {
		if ( isset( $this->file['id'] ) )
			return $this->file['id'];
		return '';
	}

	/**
	 * Get the upload speed recorded on the last upload performed.
	 *
	 * @access public
	 * @return integer  Returns the upload speed in bytes/second or 0.
	 */
	public function get_upload_speed() {
		if ( $this->timer['cycle'] > 0 )
			if ( $this->file['size'] < $this->chunk_size )
				return $this->file['size'] / $this->timer['cycle'];
			else
				return $this->chunk_size / $this->timer['cycle'];
		return 0;
	}

	/**
	 * Get the percentage of the file uploaded.
	 *
	 * @return float Returns a percentage on success, 0 on failure.
	 */
	public function get_upload_percentage() {
		if ( isset( $this->file['path'] ) )
			return $this->file['pointer'] * 100 / $this->file['size'];
		return 0;
	}

	/**
	 * Returns the time taken for an upload to complete.
	 *
	 * @access public
	 * @return float  Returns the number of seconds the last upload took to complete, 0 if there has been no completed upload.
	 */
	public function time_taken() {
		return $this->timer['delta'];
	}
}
