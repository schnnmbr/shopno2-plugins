<?php
/***************************************************************************
 *
 * 	----------------------------------------------------------------------
 * 						DO NOT EDIT THIS FILE
 *	----------------------------------------------------------------------
 * 
 *  				     Copyright (C) Themify
 * 
 *	----------------------------------------------------------------------
 *
 ***************************************************************************/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Initialize actions
add_action('delete_attachment', 'themify_delete_attachment');
$themify_ajax_actions = array(
	'plupload',
	'delete_preset',
	'remove_post_image',
	'save',
	'reset_styling',
	'reset_setting',
	'pull',
	'add_link_field',
	'media_lib_browse',
	'refresh_webfonts'
);
foreach($themify_ajax_actions as $action){
	add_action('wp_ajax_themify_' . $action, 'themify_' . $action);
}
add_action('added_post_meta', 'themify_after_post_meta', 10, 4);
add_action('updated_post_meta', 'themify_after_post_meta', 10, 4);
add_action('deleted_post_meta', 'themify_deleted_post_meta', 10, 4);

/**
 * AJAX - Plupload execution routines
 * @since 1.2.2
 * @package themify
 */
function themify_plupload() {
    $imgid = $_POST['imgid'];
    check_ajax_referer($imgid . 'themify-plupload');
	/** Check whether this image should be set as a preset. @var String */
	$haspreset = isset( $_POST['haspreset'] )? $_POST['haspreset'] : '';
	/** Decide whether to send this image to Media. @var String */
	$add_to_media_library = isset( $_POST['tomedia'] ) ? $_POST['tomedia'] : false;
	/** If post ID is set, uploaded image will be attached to it. @var String */
	$postid = isset( $_POST['topost'] )? $_POST['topost'] : '';
 
    /** Handle file upload storing file|url|type. @var Array */
    $file = wp_handle_upload($_FILES[$imgid . 'async-upload'], array('test_form' => true, 'action' => 'themify_plupload'));
	//let's see if it's an image, a zip file or something else
	$ext = explode('/', $file['type']);
	
	// Import routines
	if( 'zip' == $ext[1] || 'rar' == $ext[1] || 'plain' == $ext[1] ){
		
		$url = wp_nonce_url('admin.php?page=themify');
		$upload_dir = wp_upload_dir();
		
		if (false === ($creds = request_filesystem_credentials($url) ) ) {
			return true;
		}
		if ( ! WP_Filesystem($creds) ) {
			request_filesystem_credentials($url, '', true);
			return true;
		}
		
		global $wp_filesystem, $ThemifyDataMigrate;
		
		if( 'zip' == $ext[1] || 'rar' == $ext[1] ) {
			unzip_file($file['file'], THEMIFY_DIR);
			if( $wp_filesystem->exists( THEMIFY_DIR . '/data_export.txt' ) ){
				$data = $wp_filesystem->get_contents( THEMIFY_DIR . '/data_export.txt' );
				$new_data = $ThemifyDataMigrate->convert_data( unserialize( $data ) );
				themify_set_data($new_data);
				$wp_filesystem->delete(THEMIFY_DIR . '/data_export.txt');
				$wp_filesystem->delete($file['file']);
			} else {
				_e('Data could not be loaded', 'themify');
			}
		} else {
			if( $wp_filesystem->exists( $file['file'] ) ){
				$data = $wp_filesystem->get_contents( $file['file'] );
				$new_data = $ThemifyDataMigrate->convert_data( unserialize( $data ) );
				themify_set_data($new_data);
				$wp_filesystem->delete($file['file']);
			} else {
				_e('Data could not be loaded', 'themify');
			}
		}
		
	} else {
		//Image Upload routines
		if( 'tomedia' == $add_to_media_library ){
			
			// Insert into Media Library
			// Set up options array to add this file as an attachment
	        $attachment = array(
	            'post_mime_type' => sanitize_mime_type($file['type']),
	            'post_title' => str_replace('-', ' ', sanitize_file_name(pathinfo($file['file'], PATHINFO_FILENAME))),
	            'post_status' => 'inherit'
	        );
			
			if( $postid ){
				$attach_id = wp_insert_attachment( $attachment, $file['file'], $postid );
			} else {
				$attach_id = wp_insert_attachment( $attachment, $file['file'] );
			}

			// Common attachment procedures
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		    $attach_data = wp_generate_attachment_metadata( $attach_id, $file['file'] );
		    wp_update_attachment_metadata($attach_id, $attach_data);
			
			if( $postid ) {
				
				$full = wp_get_attachment_image_src( $attach_id, 'full' );
				
				if( isset( $_POST['featured'] ) ){
					//Set the featured image for the post
					set_post_thumbnail($postid, $attach_id);
				}
				update_post_meta($postid, $_POST['fields'], $full[0]);
				update_post_meta($postid, '_'.$_POST['fields'] . '_attach_id', $attach_id);
				
				$thumb = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
				
				//Return URL for the image field in meta box
				$file['thumb'] = $thumb[0];
				
			}
		}
		/**
		 * Presets like backgrounds and such
		 */
		if( 'haspreset' == $haspreset ){
			// For the sake of predictability, we're not adding this to Media.
			$presets = get_option('themify_background_presets');
			$presets[ $file['file'] ] = $file['url'];
			update_option('themify_background_presets', $presets);
			
			/*$presets_attach_id = get_option('themify_background_presets_attach_id');
			$presets_attach_id[ $file['file'] ] = $attach_id;
			update_option('themify_background_presets_attach_id', $presets_attach_id);*/
		}
		
	}
	$file['type'] = $ext[1];
	// send the uploaded file url in response
	echo json_encode($file);
    exit;
}

/**
 * Sync post thumbnail and post_image field
 */
function themify_after_post_meta( $meta_id, $post_id, $meta_key, $meta_value ) {
    if ( '_thumbnail_id' == $meta_key ) {
        $attach_id = get_post_thumbnail_id($post_id);
		$full = wp_get_attachment_image_src( $attach_id, 'full' );
		//set_post_thumbnail($post_id, $attach_id);
		update_post_meta($post_id, 'post_image', $full[0]);
    }
}
/**
 * Delete post meta if post thumbnail was deleted
 */
function themify_deleted_post_meta( $deleted_meta_ids, $post_id, $meta_key, $only_delete_these_meta_values ){
    if ( '_thumbnail_id' == $meta_key ) {
    	delete_post_meta($post_id, 'post_image');
    }
}

function themify_save_post_image( $post_id ) {
	if ( !wp_is_post_revision( $post_id ) ) {
		if( '' != ($attach_id = get_post_meta($post_id, '_thumbnail_id', true)) ){
			$full = wp_get_attachment_image_src( $attach_id, 'full' );
			update_post_meta($post_id, 'post_image', $full[0]);
		}
	}
}
add_action( 'save_post', 'themify_save_post_image', 18 );

/**
 * AJAX - Delete preset image
 * @since 1.2.2
 * @package themify
 */
function themify_delete_preset(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	
	if( isset($_POST['file']) ){
		$file = $_POST['file'];
		$presets = get_option('themify_background_presets');
		
		if(file_exists(THEMIFY_DIR . '/uploads/bg/' . $file)){
			// It's one of the presets budled with the theme
			unlink(THEMIFY_DIR . '/uploads/bg/' . $file);
			echo 'Deleted ' . THEMIFY_DIR . '/uploads/bg/' . $file;
		} else {
			// It's one of the presets uploaded by user to media
			$presets_attach_id = get_option('themify_background_presets_attach_id');
			//wp_delete_attachment($presets_attach_id[stripslashes($file)], true);
			@ unlink(stripslashes($file));
			unset($presets_attach_id[stripslashes($file)]);
			update_option('themify_background_presets_attach_id', $presets_attach_id);
		}
		unset($presets[ stripslashes($file) ]);
		update_option('themify_background_presets', $presets);
	}
	die();
}

/**
 * When user deletes image from gallery, it will delete the post_image custom field.
 * @since 1.2.2
 * @package themify
 */
function themify_delete_attachment($attach_id){
	$attdata = get_post( $attach_id );
	if ( isset( $attdata->post_parent ) && ! empty( $attdata->post_parent ) ) {
		delete_post_meta( $attdata->post_parent, 'post_image' );
	}
}

/**
 * AJAX - Remove image assigned in Themify custom panel. Clears post_image and _thumbnail_id field.
 * @since 1.1.5
 * @package themify
 */
function themify_remove_post_image(){
	check_ajax_referer( 'themify-custom-panel', 'nonce' );
	$attach_id = (isset($_POST['attach_id'])) ? $_POST['attach_id'] : get_post_thumbnail_id($_POST['postid']);
	$is_post_thumbnail = (isset($_POST['attach_id'])) ? false : true ;
	
	if( isset($_POST['postid']) && isset($_POST['customfield'])){
		// Un attach image from custom field
		delete_post_meta($_POST['postid'], '_'.$_POST['customfield'].'_attach_id');
		
		// Clear Themify custom field for post image
		update_post_meta($_POST['postid'], $_POST['customfield'], '');
		
		if( $is_post_thumbnail ) {
			// Clear hidden custom field
			update_post_meta($_POST['postid'], '_thumbnail_id', array());
		}

	} else {
		_e('Missing vars: post ID and custom field.', 'themify');
	}
	die();
}

/**
 * AJAX - Save user settings
 * @since 1.1.3
 * @package themify
 */
function themify_save(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	$data = explode("&", $_POST['data']);
	$temp = array();
	foreach($data as $a){
		$v = explode("=", $a);
		$temp[$v[0]] = urldecode( str_replace("+"," ",preg_replace('/%([0-9a-f]{2})/ie', "chr(hexdec('\\1'))", urlencode($v[1]))) );
	}
	themify_set_data($temp);
	_e('Your settings were saved', 'themify');
	die();
}

/**
 * AJAX - Reset Styling
 * @since 1.1.3
 * @package themify
 */
function themify_reset_styling(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	$data = explode("&", $_POST['data']);
	$temp_data = array();
	foreach($data as $a){
		$v = explode("=", $a);
		$temp_data[$v[0]] = str_replace("+"," ",preg_replace('/%([0-9a-f]{2})/ie', "chr(hexdec('\\1'))", $v[1]));
	}
	$temp = array();
	foreach($temp_data as $key => $val){
		if(strpos($key, 'styling') === false){
			$temp[$key] = $val;
		}
	}
	print_r(themify_set_data($temp));
	die();
}

/**
 * AJAX - Reset Settings
 * @since 1.1.3
 * @package themify
 */
function themify_reset_setting(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	$data = explode("&", $_POST['data']);
	$temp_data = array();
	foreach($data as $a){
		$v = explode("=", $a);
		$temp_data[$v[0]] = str_replace("+"," ",preg_replace('/%([0-9a-f]{2})/ie', "chr(hexdec('\\1'))", $v[1]));
	
	}
	$temp = array();
	foreach($temp_data as $key => $val){
		// Don't reset if it's not a setting or the # of social links or a social link
		if(strpos($key, 'setting') === false || strpos($key, 'link_field_ids') || strpos($key, 'themify-link') || strpos($key, 'twitter_settings') || strpos($key, 'custom_css')){
			$temp[$key] = $val;
		}
	}
	print_r(themify_set_data($temp));
	die();
}

/**
 * Export Settings to zip file and prompt to download
 * NOTE: This function is not called through AJAX but it is kept here for consistency. 
 * @since 1.1.3
 * @package themify
 */
function themify_export() {
	if ( isset($_GET['export']) ) {
		check_admin_referer( 'themify_export_nonce' );
		$theme = wp_get_theme();
		$theme_name = $theme->display('Name');
		if(class_exists('ZipArchive')){
			$theme_name_lc = strtolower($theme_name);
			$datafile = 'data_export.txt';
			$handler = @fopen($datafile, 'w');
			@fwrite($handler,serialize(themify_get_data()));
			@fclose($handler);
			$files_to_zip = array(
				'../wp-content/themes/' . $theme_name_lc . '/custom-modules.php',
				'../wp-content/themes/' . $theme_name_lc . '/custom-functions.php',
				'../wp-content/themes/' . $theme_name_lc . '/custom-config.php',
				'../wp-content/themes/' . $theme_name_lc . '/custom_style.css',
				$datafile
			);
			//print_r($files_to_zip);
			$file = $theme_name . '_themify_export_' . date('Y_m_d') . '.zip';
			$result = themify_create_zip( $files_to_zip, $file, true );
		}
		if(isset($result) && $result){
			if((isset($file))&&(file_exists($file))){
				ob_start();
				header('Pragma: public');
				header('Expires: 0');
				header("Content-type: application/force-download");
				header('Content-Disposition: attachment; filename="' . $file . '"');
				header("Content-Transfer-Encoding: Binary"); 
				header("Content-length: ".filesize($file));
				header('Connection: close');
				ob_clean();
				flush(); 
				readfile($file);
				unlink($datafile);
				unlink($file);
				exit();
			} else {
				return false;
			}
		} else {
			if(ini_get('zlib.output_compression')) {
				ini_set('zlib.output_compression', 'Off');
			}
			ob_start();
			header('Content-Type: application/force-download');
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private',false);
			header('Content-Disposition: attachment; filename="'.$theme_name.'_themify_export_'.date("Y_m_d").'.txt"');
			header('Content-Transfer-Encoding: binary');
			ob_clean();
			flush();
			echo serialize(themify_get_data());
			exit();
		}
	}
	return;
}
add_action('after_setup_theme', 'themify_export', 10);

/**
 * Export Settings Old data to zip file and prompt to download
 * NOTE: This function is not called through AJAX but it is kept here for consistency. 
 * @since 1.1.3
 * @package themify
 */
function themify_export_old_data() {
	if ( isset($_GET['export_old']) ) {
		check_admin_referer( 'themify_export_nonce' );
		$theme = wp_get_theme();
		$theme_name = $theme->display('Name');
		if(class_exists('ZipArchive')){
			$theme_name_lc = strtolower($theme_name);
			$datafile = 'data_export.txt';
			$handler = fopen($datafile, 'w');
			fwrite($handler,serialize(themify_get_old_data()));
			fclose($handler);
			$files_to_zip = array(
				'../wp-content/themes/' . $theme_name_lc . '/custom-modules.php',
				'../wp-content/themes/' . $theme_name_lc . '/custom-functions.php',
				'../wp-content/themes/' . $theme_name_lc . '/custom-config.php',
				'../wp-content/themes/' . $theme_name_lc . '/custom_style.css',
				$datafile
			);
			//print_r($files_to_zip);
			$file = $theme_name . '_themify_export_' . date('Y_m_d') . '.zip';
			$result = themify_create_zip( $files_to_zip, $file, true );
			if($result){
				if((isset($file))&&(file_exists($file))){
					ob_start();
					header('Pragma: public');
					header('Expires: 0');
					header("Content-type: application/force-download");
					header('Content-Disposition: attachment; filename="' . $file . '"');
					header("Content-Transfer-Encoding: Binary"); 
					header("Content-length: ".filesize($file));
					header('Connection: close');
					ob_clean();
					flush(); 
					readfile($file);
					unlink($datafile);
					unlink($file);
					exit();
				} else {
					return false;
				}
			}
		} else {
			if(ini_get('zlib.output_compression')) {
				ini_set('zlib.output_compression', 'Off');
			}
			ob_start();
			header('Content-Type: application/force-download');
			header('Pragma: public');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Cache-Control: private',false);
			header('Content-Disposition: attachment; filename="'.$theme_name.'_themify_export_'.date("Y_m_d").'.txt"');
			header('Content-Transfer-Encoding: binary');
			ob_clean();
			flush();
			echo serialize(themify_get_old_data());
			exit();
		}
	}
	return;
}
add_action('after_setup_theme', 'themify_export_old_data', 10);

/**
 * Pull data for inspection
 * @since 1.1.3
 * @package themify
 */
function themify_pull(){
	print_r(themify_get_data());
	die();
}

function themify_add_link_field(){
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	
	if( isset($_POST['fid']) ) {
		$hash = $_POST['fid'];
		$type = isset( $_POST['type'] )? $_POST['type'] : 'image-icon';
		$field = themify_add_link_template( 'themify-link-'.$hash, array(), true, $type);
		echo $field;
		exit();
	}
}

/**
 * Set image from wp library
 * @since 1.2.9
 * @package themify
 */
function themify_media_lib_browse() {
	if ( ! wp_verify_nonce( $_POST['media_lib_nonce'], 'media_lib_nonce' ) ) die(-1);

	$file = array();
	$postid = $_POST['post_id'];
	$attach_id = $_POST['attach_id'];

	$full = wp_get_attachment_image_src( $attach_id, 'full' );
	if( $_POST['featured'] ){
		//Set the featured image for the post
		set_post_thumbnail($postid, $attach_id);
	}
	update_post_meta($postid, $_POST['field_name'], $full[0]);
	update_post_meta($postid, '_'.$_POST['field_name'] . '_attach_id', $attach_id);

	$thumb = wp_get_attachment_image_src( $attach_id, 'thumbnail' );
				
	//Return URL for the image field in meta box
	$file['thumb'] = $thumb[0];

	echo json_encode($file);

	exit();
}

/**
 * Delete WebFonts cache
 * @since 1.3.9
 */
function themify_refresh_webfonts() {
	check_ajax_referer( 'ajax-nonce', 'nonce' );
	delete_transient( 'themify_google_fonts_transient' );
	echo 'WebFonts refreshed.';
	die();
}