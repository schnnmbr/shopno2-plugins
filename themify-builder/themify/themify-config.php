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

/**
 * Admin Only code follows
 ******************************************************/
if( is_admin() ){
	
	/**
 	* Enqueue jQuery and other scripts
 	*******************************************************/
	add_action('admin_enqueue_scripts', 'themify_enqueue_scripts');
	
	/**
 	* Ajaxify admin
 	*******************************************************/
	require_once(THEMIFY_DIR . '/themify-wpajax.php');
}

/**
 * Enqueue JS and CSS for Themify settings page and meta boxes
 * @param String $page
 * @since 1.1.1
 *******************************************************/
function themify_enqueue_scripts($page){
	global $typenow;

	$pagenow = isset($_GET['page'])? $_GET['page'] : '';
	$types = themify_post_types();
	$pages = apply_filters( 'themify_top_pages', array( 'post.php', 'post-new.php', 'toplevel_page_themify', 'nav-menus.php' ) );
	$pagenows = apply_filters( 'themify_pagenow', array( 'themify' ) );
	
	// Custom Write Panel
	if( ($page == 'post.php' || $page == 'post-new.php') && in_array($typenow, $types) ){
		wp_enqueue_script( 'meta-box-tabs', THEMIFY_URI . '/js/meta-box-tabs.js', array('jquery'), '1.0', true );
		wp_enqueue_script( 'media-library-browse', THEMIFY_URI . '/js/media-lib-browse.js', array('jquery'), '1.0', true );
	}

	// Settings Panel 
	if( $page == 'toplevel_page_themify' ){
		wp_enqueue_script( 'jquery-ui-sortable' );
	}
	if( in_array( $page, $pages ) ) {
		//Enqueue styles
		wp_enqueue_style( 'themify-ui',  THEMIFY_URI . '/css/themify-ui.css', array(), false );
		if ( is_rtl() ) {
			wp_enqueue_style( 'themify-ui-rtl',  THEMIFY_URI . '/css/themify-ui-rtl.css', array(), false );
		}
		wp_enqueue_style( 'colorpicker', THEMIFY_URI . '/css/jquery.minicolors.css', array(), false );
		
		//Enqueue scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'json2' );
		wp_enqueue_script( 'plupload-all' );
		wp_enqueue_script( 'validate', THEMIFY_URI . '/js/jquery.validate.pack.js', array('jquery'), false );
		wp_enqueue_script( 'colorpicker-js', THEMIFY_URI . '/js/jquery.minicolors.js', array('jquery'), false );
		if( in_array($typenow, $types) || in_array( $pagenow, $pagenows ) ){
			//Don't include Themify JavaScript if we're not in one of the Themify-managed pages
			wp_enqueue_script( 'themify-scripts', THEMIFY_URI . '/js/scripts.js', array('jquery'), false );
			wp_enqueue_script( 'themify-plupload', THEMIFY_URI . '/js/plupload.js', array('jquery', 'themify-scripts'), false);
			wp_register_script( 'gallery-shortcode', THEMIFY_URI . '/js/gallery-shortcode.js', array('jquery', 'themify-scripts'), false, true );
		}
	}
	//Inject variable values to scripts.js previously enqueued
	wp_localize_script('themify-scripts', 'themify_js_vars', array(
			'themify' 	=> THEMIFY_URI,
			'nonce' 	=> wp_create_nonce('ajax-nonce'),
			'admin_url' => admin_url( 'admin.php?page=themify' ),
			'ajax_url' 	=> admin_url( 'admin-ajax.php' ),
			'app_url'	=> get_template_directory_uri() . '/themify/',
			'theme_url'	=> get_template_directory_uri() . '/',
			'blog_url'	=> site_url() . '/'
		)
	);
	
	// Inject variable for Plupload
	$global_plupload_init = array(
	    'runtimes'				=> 'html5,flash,silverlight,html4',
	    'browse_button'			=> 'plupload-browse-button', // adjusted by uploader
	    'container' 			=> 'plupload-upload-ui', // adjusted by uploader
	    'drop_element' 			=> 'drag-drop-area', // adjusted by uploader
	    'file_data_name' 		=> 'async-upload', // adjusted by uploader
	    'multiple_queues' 		=> true,
	    'max_file_size' 		=> wp_max_upload_size() . 'b',
	    'url' 					=> admin_url('admin-ajax.php'),
	    'flash_swf_url' 		=> includes_url('js/plupload/plupload.flash.swf'),
	    'silverlight_xap_url' 	=> includes_url('js/plupload/plupload.silverlight.xap'),
	    'filters' 				=> array( array(
	    	'title' => __('Allowed Files', 'themify'), 'extensions' => 'jpg,jpeg,gif,png,ico,zip,txt,svg') ),
	    'multipart' 			=> true,
	    'urlstream_upload' 		=> true,
	    'multi_selection' 		=> false, // added by uploader
	     // additional post data to send to our ajax hook
	    'multipart_params' 		=> array(
	        '_ajax_nonce' 		=> '', // added by uploader
	        'action' 			=> 'themify_plupload', // the ajax action name
	        'imgid' 			=> 0 // added by uploader
	    )
	);
	wp_localize_script('themify-scripts', 'global_plupload_init', $global_plupload_init);
	
	wp_localize_script('themify-scripts', 'themify_lang', array(
			'confirm_reset_styling'	=> __('Are you sure you want to reset your theme style?', 'themify'),
			'confirm_reset_settings' => __('Are you sure you want to reset your theme settings?', 'themify'),
			'confirm_refresh_webfonts'	=> __('Are you sure you want to reset the Google WebFonts list? This will also save the current settings.', 'themify'),
			'check_backup' => __('Make sure to backup before upgrading. Files and settings may get lost or changed.', 'themify'),
			'confirm_delete_image' => __('Do you want to delete this image permanently?', 'themify'),
			'invalid_login' => __('Invalid username or password.<br/>Contact <a href="http://themify.me/contact">Themify</a> for login issues.', 'themify'),
			'enable_zip_upload' => sprintf(
				__('Go to your <a href="%s">Network Settings</a> to enable <strong>zip</strong>, <strong>txt</strong> and <strong>svg</strong> extensions in <strong>Upload file types</strong> field.', 'themify'),
				esc_url(network_admin_url('settings.php').'#upload_filetypes')
			),
			'filesize_error' => __('The file you are trying to upload exceeds the maximum file size allowed.', 'themify'),
			'filesize_error_fix' => sprintf(
				__('Go to your <a href="%s">Network Settings</a> and increase the value of the <strong>Max upload file size</strong>.', 'themify'),
				esc_url(network_admin_url('settings.php').'#fileupload_maxk')
			)
		)
	);

	// Add strings to TinyMCE menu button
	wp_localize_script('editor', 'themifyEditor', array(
		'nonce' => wp_create_nonce( 'themify-editor-nonce' ),
		'editor' => array(
			'menuTooltip' => __('Shortcodes', 'themify'),
			'menuName' => __('Shortcodes', 'themify'),
			'button' => __('Button', 'themify'),
			'columns' => __('Columns', 'themify'),
			'half21' => __('2-1 Half', 'themify'),
			'half21first' => __('2-1 Half First', 'themify'),
			'third31' => __('3-1 One-Third', 'themify'),
			'third31first' => __('3-1 One-Third First', 'themify'),
			'quarter41' => __('4-1 Quarter', 'themify'),
			'quarter41first' => __('4-1 Quarter First', 'themify'),
			'image' => __('Image', 'themify'),
			'horizontalRule' => __('Horizontal Rule', 'themify'),
			'quote' => __('Quote', 'themify'),
			'isLoggedIn' => __('Is Logged In', 'themify'),
			'isGuest' => __('Is Guest', 'themify'),
			'map' => __('Map', 'themify'),
			'video' => __('Video', 'themify'),
			'flickr' => __('Flickr Gallery', 'themify'),
			'twitter' => __('Twitter Stream', 'themify'),
			'postSlider' => __('Post Slider', 'themify'),
			'customSlider' => __('Custom Slider', 'themify'),
			'slider' => __('Slider', 'themify'),
			'slide' => __('Slide', 'themify'),
			'listPosts' => __('List Posts', 'themify'),
			'box' => __('Box', 'themify'),
			'authorBox' => __('Author Box', 'themify'),
		)
	));
}