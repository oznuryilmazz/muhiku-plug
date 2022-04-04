<?php
/**
 * @package MuhikuPlug\Classes
 */

defined( 'ABSPATH' ) || exit;

class MHK_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_menus' ), 100 );
		add_action( 'muhiku_forms_after_register_post_type', array( __CLASS__, 'maybe_flush_rewrite_rules' ) );
		add_action( 'muhiku_forms_flush_rewrite_rules', array( __CLASS__, 'flush_rewrite_rules' ) );
	}

	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'muhiku_form' ) ) {
			return;
		}

		do_action( 'muhiku_forms_register_post_type' );

		register_post_type(
			'muhiku_form',
			apply_filters(
				'muhiku_forms_register_post_type_product',
				array(
					'labels'              => array(
						'name'                  => __( 'Muhiku Plug', 'muhiku-plug' ),
						'singular_name'         => __( 'Form', 'muhiku-plug' ),
						'all_items'             => __( 'Bütün Öneri Talebi Formları', 'muhiku-plug' ),
						'menu_name'             => _x( 'Forms', 'Admin menu name', 'muhiku-plug' ),
						'add_new'               => __( 'Form Ekle', 'muhiku-plug' ),
						'add_new_item'          => __( 'Yeni Form Ekle', 'muhiku-plug' ),
						'edit'                  => __( 'Düzenle', 'muhiku-plug' ),
						'edit_item'             => __( 'Formu Düzenle', 'muhiku-plug' ),
						'new_item'              => __( 'Yeni Form', 'muhiku-plug' ),
						'view_item'             => __( 'Formu Görüntüle', 'muhiku-plug' ),
						'search_items'          => __( 'Formlarda Ara', 'muhiku-plug' ),
						'not_found'             => __( 'Form Bulunamadı', 'muhiku-plug' ),
						'not_found_in_trash'    => __( 'Çöp Kutusunda Form Bulunamadı', 'muhiku-plug' ),
						'parent'                => __( 'Ana Form', 'muhiku-plug' ),
						'featured_image'        => __( 'Form Resmi', 'muhiku-plug' ),
						'set_featured_image'    => __( 'Form Resmi Ayarla', 'muhiku-plug' ),
						'remove_featured_image' => __( 'Form Resmini Kaldır', 'muhiku-plug' ),
						'use_featured_image'    => __( 'Form Resmi Olarak Kullan', 'muhiku-plug' ),
						'insert_into_item'      => __( 'Forma Ekle', 'muhiku-plug' ),
						'uploaded_to_this_item' => __( 'Formu Güncelle', 'muhiku-plug' ),
						'filter_items_list'     => __( 'Formu Filtrele', 'muhiku-plug' ),
						'items_list_navigation' => __( 'Form Navigasyonu', 'muhiku-plug' ),
						'items_list'            => __( 'Formları Listele', 'muhiku-plug' ),
					),
					'public'              => false,
					'show_ui'             => true,
					'description'         => __( 'This is where you can add new forms.', 'muhiku-plug' ),
					'capability_type'     => 'post',
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'show_in_rest'        => true,
					'show_in_menu'        => false,
					'hierarchical'        => false,
					'rewrite'             => false,
					'query_var'           => false,
					'supports'            => false,
					'show_in_nav_menus'   => false,
					'show_in_admin_bar'   => false,
				)
			)
		);

		do_action( 'muhiku_forms_after_register_post_type' );
	}

	/**
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public static function admin_bar_menus( $wp_admin_bar ) {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_muhiku_forms' ) ) {
			return;
		}

		if ( ! is_user_member_of_blog() && ! is_super_admin() ) {
			return;
		}

		if ( apply_filters( 'muhiku_forms_show_admin_bar_menus', true ) ) {
			$wp_admin_bar->add_node(
				array(
					'parent' => 'new-content',
					'id'     => 'muhiku-plug',
					'title'  => __( 'Muhiku Plug', 'muhiku-plug' ),
					'href'   => admin_url( 'admin.php?page=mhk-builder&create-form=1' ),
				)
			);
		}
	}

	public static function maybe_flush_rewrite_rules() {
		if ( 'yes' === get_option( 'muhiku_forms_queue_flush_rewrite_rules' ) ) {
			update_option( 'muhiku_forms_queue_flush_rewrite_rules', 'no' );
			self::flush_rewrite_rules();
		}
	}

	public static function flush_rewrite_rules() {
		flush_rewrite_rules();
	}
}

MHK_Post_Types::init();
