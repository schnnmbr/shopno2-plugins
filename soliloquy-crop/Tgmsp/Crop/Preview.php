<?php
/**
 * Preview class for the Soliloquy Crop Addon.
 *
 * @since 1.0.0
 *
 * @package	Soliloquy Filters
 * @author	Thomas Griffin
 */
class Tgmsp_Crop_Preview {

	/**
	 * Holds a copy of the object for easy reference.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Constructor. Hooks all interactions to initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
	
		self::$instance = $this;
		
		/** Return early if Soliloquy is not active */
		if ( Tgmsp_Crop::soliloquy_is_not_active() )
			return;
		
		add_action( 'tgmsp_preview_start', array( $this, 'preview_init' ) );
	
	}
	
	/**
	 * Init callback to make sure that filters and hooks are only executed in the Preview
	 * context.
	 *
	 * @since 1.0.0
	 *
	 * @param array $post_var The $_POST data from the Ajax request
	 */
	public function preview_init( $post_var ) {
		
		if ( isset( $post_var['soliloquy-default-size'] ) && 'cropped' == $post_var['soliloquy-default-size'] ) {
			add_filter( 'tgmsp_get_image_data', array( $this, 'get_full_image' ), 10, 5 );
			add_filter( 'tgmsp_image_data', array( $this, 'filter_data' ), 1, 4 );
		}
	
	}
	
	/**
	 * Force a full size image when using the "cropped" size.
	 *
	 * @since 1.0.0
	 *
	 * @param string $image Image HTML string
	 * @param int $id The current slider ID
	 * @param object $attachment The current image attachment
	 * @param string $size The size of image to retrieve
	 * @param array $post_var The $_POST data from the Ajax request
	 * @return string $image Amended image HTML for the full size image
	 */
	public function get_full_image( $image, $id, $attachment, $size, $post_var ) {
			
		return wp_get_attachment_image_src( $attachment->ID, 'full' );
		
	}
	
	/**
	 * Send filter data when Soliloquy grabs image meta.
	 *
	 * @since 1.0.0
	 *
	 * @param array $image Image data Soliloquy uses to send to the current slider
	 * @param object $attachment The current attachment object
	 * @param int $slider_id The current slider ID
	 * @param array $post_var The $_POST data from the Ajax request
	 * @return array $image Amended image data with Crop src (if needed)
	 */
	public function filter_data( $image, $attachment, $slider_id, $post_var ) {
			
		// If we have made it this far, we know we are about to use Crop, so define some constants for Crop.
		if ( ! defined( 'MEMORY_LIMIT' ) ) 			define( 'MEMORY_LIMIT', '128M' );
		if ( ! defined( 'ALLOW_EXTERNAL' ) ) 		define( 'ALLOW_EXTERNAL', false );
		if ( ! defined( 'FILE_CACHE_DIRECTORY' ) ) 	define( 'FILE_CACHE_DIRECTORY', Tgmsp_Crop::soliloquy_uploads_dir() . '/cache' );
		
		// Get Crop crop alignment setting.		
		$args		= apply_filters( 'tgmsp_crop_image_args', array(
			'src' 	=> esc_url( $image['src'] ),
			'a'		=> isset( $post_var['soliloquy-crop-position'] ) ? $post_var['soliloquy-crop-position'] : 'c',
			'w'		=> isset( $post_var['soliloquy-width'] ) ? $post_var['soliloquy-width'] : 600,
			'h'		=> isset( $post_var['soliloquy-height'] ) ? $post_var['soliloquy-height'] : 600,
			'q'		=> 100
		) );
		$image['src'] 	 = add_query_arg( $args, Tgmsp_Crop::get_crop_file_url() );
		$image['width']  = isset( $post_var['soliloquy-width'] ) ? $post_var['soliloquy-width'] : 600;
		$image['height'] = isset( $post_var['soliloquy-height'] ) ? $post_var['soliloquy-height'] : 300;
		
		return apply_filters( 'tgmsp_crop_data', $image, $attachment, $slider_id );
			
	}
	
	/**
	 * Getter method for retrieving the object instance.
	 *
	 * @since 1.0.0
	 */
	public static function get_instance() {
	
		return self::$instance;
	
	}
	
}