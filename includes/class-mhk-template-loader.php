<?php
/**
 * @package MuhikuPlug\Classes
 */

defined( 'ABSPATH' ) || exit;

class MHK_Template_Loader {

	/**
	 * @var integer
	 */
	private static $form_id = 0;

	/**
	 * @var boolean
	 */
	private static $in_content_filter = false;

	public static function init() {
		self::$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;  

		if ( ! is_admin() && isset( $_GET['mhk_preview'] ) ) {  
			add_action( 'pre_get_posts', array( __CLASS__, 'pre_get_posts' ) );
			add_filter( 'edit_post_link', array( __CLASS__, 'edit_form_link' ) );
			add_filter( 'home_template_hierarchy', array( __CLASS__, 'template_include' ) );
			add_filter( 'frontpage_template_hierarchy', array( __CLASS__, 'template_include' ) );
			add_action( 'template_redirect', array( __CLASS__, 'form_preview_init' ) );
		} else {
			add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
		}
	}

	/**
	 * @param WP_Query $q Query instance.
	 */
	public static function pre_get_posts( $q ) {
		if ( $q->is_main_query() ) {
			$q->set( 'posts_per_page', 1 );
		}
	}

	/**
	 * @param string $link Edit post link.
	 */
	public static function edit_form_link( $link ) {
		if ( 0 < self::$form_id ) {
			return '<a href="' . esc_url( admin_url( 'admin.php?page=mhk-builder&tab=fields&form_id=' . self::$form_id ) ) . '" class="post-edit-link">' . esc_html__( 'Edit Form', 'muhiku-plug' ) . '</a>';
		}

		return $link;
	}

	/**
	 * @param array $templates A list of template candidates, in descending order of priority.
	 *
	 * @return array
	 */
	public static function template_include( $templates ) {
		return array( 'page.php', 'single.php', 'index.php' );
	}

	/**
	 * @param string $template Template to load.
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		$default_file = self::get_template_loader_default_file();

		if ( $default_file ) {
			/**
			 * @var array
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template || MHK_TEMPLATE_DEBUG_MODE ) {
				$template = mhk()->plugin_path() . '/templates/' . $default_file;
			}
		}

		return $template;
	}

	/**
	 * @return string
	 */
	private static function get_template_loader_default_file() {
		return '';
	}

	/**
	 * @param  string $default_file The default file name.
	 * @return string[]
	 */
	private static function get_template_loader_files( $default_file ) {
		$search_files   = apply_filters( 'muhiku_forms_template_loader_files', array(), $default_file );
		$search_files[] = 'muhiku-plug.php';

		if ( is_page_template() ) {
			$search_files[] = get_page_template_slug();
		}

		$search_files[] = $default_file;
		$search_files[] = mhk()->template_path() . $default_file;

		return array_unique( $search_files );
	}

	public static function form_preview_init() {
		if ( ! is_user_logged_in() || is_admin() ) {
			return;
		}

		if ( 0 < self::$form_id ) {
			add_filter( 'the_title', array( __CLASS__, 'form_preview_title_filter' ) );
			add_filter( 'the_content', array( __CLASS__, 'form_preview_content_filter' ) );
			add_filter( 'get_the_excerpt', array( __CLASS__, 'form_preview_content_filter' ) );
			add_filter( 'post_thumbnail_html', '__return_empty_string' );
		}
	}

	/**
	 * @param  string $title Existing title.
	 * @return string
	 */
	public static function form_preview_title_filter( $title ) {
		$form = mhk()->form->get(
			self::$form_id,
			array(
				'content_only' => true,
			)
		);

		if ( ! empty( $form['settings']['form_title'] ) && in_the_loop() ) {
			if ( is_customize_preview() ) {
				return esc_html( sanitize_text_field( $form['settings']['form_title'] ) );
			}

			return sprintf( esc_html__( '%s &ndash; Preview', 'muhiku-plug' ), sanitize_text_field( $form['settings']['form_title'] ) );
		}

		return $title;
	}

	/**
	 * @param  string $content Existing post content.
	 * @return string
	 */
	public static function form_preview_content_filter( $content ) {
		if ( ! is_user_logged_in() || ! is_main_query() || ! in_the_loop() ) {
			return $content;
		}

		self::$in_content_filter = true;

		remove_filter( 'the_content', array( __CLASS__, 'form_preview_content_filter' ) );

		if ( current_user_can( 'muhiku_forms_view_forms', self::$form_id ) ) {
			$content = apply_shortcodes( '[muhiku_form id="' . absint( self::$form_id ) . '"]' );
		}

		self::$in_content_filter = false;

		return $content;
	}
}

add_action( 'init', array( 'MHK_Template_Loader', 'init' ) );
