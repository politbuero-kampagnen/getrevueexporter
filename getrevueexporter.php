<?php
/*
 * Plugin Name: GetRevue Exporter
 * Plugin URI: https://politbuero-kampagnen.ch/getrevueexporter
 * Description: Exports newsletters from a GetRevue account and allows the user to choose which newsletters to export and whether they should be saved as draft posts or published posts.
 * Author: politbuero-kampagnen.ch
 * Author URI: https://politbuero-kampagnen.ch
 * Version: 1.0.0
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: getrevueexporter
 * Domain Path: /languages
 */

// Make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
	exit;
}

/**
 * Class GetRevueExporter
 */
class GetRevueExporter {

	/**
	 * GetRevueExporter constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
	}

	/**
	 * Add plugin menu item
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'GetRevue Exporter', 'getrevueexporter' ),
			__( 'GetRevue Exporter', 'getrevueexporter' ),
			'manage_options',
			'getrevueexporter',
			array( $this, 'settings_page' ),
			'dashicons-upload',
			80
		);
	}

	/**
	 * Initialize settings
	 */
	public function settings_init() {
		register_setting( 'getrevueexporter', 'getrevueexporter_settings' );

		add_settings_section(
			'getrevueexporter_section',
			__( 'GetRevue Exporter Settings', 'getrevueexporter' ),
			array( $this, 'settings_section_callback' ),
			'getrevueexporter'
		);

		add_settings_field(
			'getrevueexporter_api_key',
			__( 'API Key', 'getrevueexporter' ),
			array( $this, 'api_key_render' ),
			'getrevueexporter',
			'getrevueexporter_section'
		);

		add_settings_field(
			'getrevueexporter_export_type',
			__( 'Export Type', 'getrevueexporter' ),
			array( $this, 'export_type_render' ),
			'getrevueexporter',
			'getrevueexporter_section'
		
		);
	}

	/**
	 * Settings section callback
	 */
	public function settings_section_callback() {
		echo '<p>' . __( 'To use this plugin, you need to create an API key for your GetRevue account. Follow the instructions below to create an API key:', 'getrevueexporter' ) . '</p>';
		echo '<ol>';
		echo '<li>' . __( 'Go to your GetRevue account settings.', 'getrevueexporter' ) . '</li>';
		echo '<li>' . __( 'Click on the "API" tab.', 'getrevueexporter' ) . '</li>';
		echo '<li>' . __( 'Click on the "Generate API key" button.', 'getrevueexporter' ) . '</li>';
		echo '<li>' . __( 'Copy the API key and paste it into the field below.', 'getrevueexporter' ) . '</li>';
		echo '</ol>';
	}

	/**
	 * API key render
	 */
	public function api_key_render() {
		$options = get_option( 'getrevueexporter_settings' );
		$api_key = isset( $options['getrevueexporter_api_key'] ) ? $options['getrevueexporter_api_key'] : '';
		echo '<input type="text" name="getrevueexporter_settings[getrevueexporter_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
	}

	/**
	 * Export type render
	 */
	public function export_type_render() {
		$options = get_option( 'getrevueexporter_settings' );
		$export_type = isset( $options['getrevueexporter_export_type'] ) ? $options['getrevueexporter_export_type'] : 'draft';
		echo '<select name="getrevueexporter_settings[getrevueexporter_export_type]">';
		echo '<option value="draft" ' . selected( 'draft', $export_type, false ) . '>' . __( 'Draft', 'getrevueexporter' ) . '</option>';
		echo '<option value="publish" ' . selected( 'publish', $export_type, false ) . '>' . __( 'Publish', 'getrevueexporter' ) . '</option>';
		echo '</select>';
	}

	/**
	 * Settings page
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'getrevueexporter' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . __( 'GetRevue Exporter', 'getrevueexporter' ) . '</h1>
		<form action="options.php" method="post">';
		settings_fields( 'getrevueexporter' );
		do_settings_sections( 'getrevueexporter' );
		submit_button();
		echo '</form>';
		echo '</div>';

		$options = get_option( 'getrevueexporter_settings' );
		$api_key = isset( $options['getrevueexporter_api_key'] ) ? $options['getrevueexporter_api_key'] : '';
		$export_type = isset( $options['getrevueexporter_export_type'] ) ? $options['getrevueexporter_export_type'] : 'draft';

		if ( ! empty( $api_key ) ) {
			echo '<h2>' . __( 'Newsletters', 'getrevueexporter' ) . '</h2>';
			echo '<form action="" method="post">';
			echo '<input type="hidden" name="action" value="export_newsletters">';
			echo '<input type="hidden" name="export_type" value="' . esc_attr( $export_type ) . '">';

			$newsletters = $this->get_newsletters( $api_key );

			if ( ! empty( $newsletters ) ) {
				echo '<table class="widefat">';
				echo '<thead>';
				echo '<tr>';
				echo '<th>' . __( 'Export', 'getrevueexporter' ) . '</th>';
				echo '<th>' . __( 'Title', 'getrevueexporter' ) . '</th>';
				echo '<th>' . __( 'Date', 'getrevueexporter' ) . '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				foreach ( $newsletters as $newsletter ) {
					echo '<tr>';
					echo '<td><input type="checkbox" name="newsletters[]" value="' . esc_attr( $newsletter['id'] ) . '"></td>';
					echo '<td>' . esc_html( $newsletter['title'] ) . '</td>';
					echo '<td>' . esc_html( $newsletter['date'] ) . '</td>';
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';
				submit_button( __( 'Export Selected', 'getrevueexporter' ) );
			} else {
				echo '<p>' . __( 'No newsletters found.', 'getrevueexporter' ) . '</p>';
			}

			echo '</
			echo '</form>';
		}
	}

	/**
	 * Get newsletters
	 *
	 * @param string $api_key GetRevue API key.
	 *
	 * @return array
	 */
	public function get_newsletters( $api_key ) {
		$url      = 'https://www.getrevue.co/api/v2/newsletters.json';
		$response = wp_remote_get( $url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
			),
		) );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['newsletters'] ) ) {
			return array();
		}

		$newsletters = array();
		foreach ( $body['newsletters'] as $newsletter ) {
			$newsletters[] = array(
				'id'    => $newsletter['id'],
				'title' => $newsletter['title'],
				'date'  => $newsletter['published_at'],
			);
		}

		return $newsletters;
	}

	/**
	 * Export newsletters
	 */
	public function export_newsletters() {
		if ( ! isset( $_POST['newsletters'] ) || ! is_array( $_POST['newsletters'] ) ) {
			return;
		}

		$options = get_option( 'getrevueexporter_settings' );
		$api_key = isset( $options['getrevueexporter_api_key'] ) ? $options['getrevueexporter_api_key'] : '';
		$export_type = isset( $_POST['export_type'] ) ? sanitize_text_field( $_POST['export_type'] ) : 'draft';

		if ( empty( $api_key ) ) {
			return;
		}

		foreach ( $_POST['newsletters'] as $newsletter_id ) {
			$url      = 'https://www.getrevue.co/api/v2/newsletters/' . $newsletter_id . '.json';
			$response = wp_remote_get( $url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
			) );

			if ( is_wp_error( $response ) ) {
				continue;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $body['newsletter'] ) ) {
				continue;
			}

			$newsletter = $body['newsletter'];

			$post_id = wp_insert_post( array(
				'post_title'  => $newsletter['title'],
				'post_content'=> $newsletter['html'],
				'post_status' => $export_type,
				'post_type'   => 'post',
			) );

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			add_post_meta( $post_id, '_getrevue_newsletter_id', $newsletter['id'] );
		}
	}
}

new GetRevueExporter();
