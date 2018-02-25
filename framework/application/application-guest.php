<?php

if ( ! function_exists( 'jm_ga_check_auth' ) ) :

	function jm_ga_check_auth( $company_id, $secret_key ) {

		if ( empty( $company_id ) or empty( $secret_key ) ) {
			return false;
		}

		$key_from_meta = noo_get_post_meta( $company_id, '_company_secret_key', '' );

		if ( $secret_key = $key_from_meta ) {
			return true;
		}

		return false;
	}

endif;

if ( ! function_exists( 'jm_ga_check_logged' ) ) :

	function jm_ga_check_logged() {

		$company_id = $_COOKIE[ 'jm_ga_company_id' ];
		$secret_key = $_COOKIE[ 'jm_ga_company_key' ];

		if ( empty( $secret_key ) or empty( $company_id ) ) {
			return false;
		} else {
			return jm_ga_check_auth( $company_id, $secret_key );
		}
	}

endif;

if ( ! function_exists( 'jm_ga_auth_action' ) ) :

	function jm_ga_auth_action() {

		if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) ) {
			return;
		}

		if ( empty( $_POST[ 'action' ] ) || 'jm_ga_auth' !== $_POST[ 'action' ] || empty( $_POST[ '_wpnonce' ] ) || ! wp_verify_nonce( $_POST[ '_wpnonce' ], 'guest-manage-application' ) ) {
			return;
		}

		$url = isset( $_POST[ 'url' ] ) ? $_POST[ 'url' ] : '';

		$company_id         = isset( $_POST[ 'company_id' ] ) ? $_POST[ 'company_id' ] : '';
		$company_secret_key = isset( $_POST[ 'company_secret_key' ] ) ? $_POST[ 'company_secret_key' ] : '';

		if ( empty( $company_id ) or empty( $company_secret_key ) ) {

			noo_message_add( __( 'Please enter Company ID and Secret code', 'noo' ), 'error' );
		} else {

			if ( jm_ga_check_auth( $company_id, $company_secret_key ) ) {

				setcookie( 'jm_ga_company_id', $company_id, null, COOKIEPATH, COOKIE_DOMAIN );
				setcookie( 'jm_ga_company_key', $company_secret_key, null, COOKIEPATH, COOKIE_DOMAIN );

				noo_message_add( __( 'You have successfully authenticated.', 'noo' ));

			} else {
				noo_message_add( __( 'Company ID or secret code is incorrect. Please try again.', 'noo' ), 'error' );
			}
		}

		wp_safe_redirect( $url );

		exit;
	}

	add_action( 'init', 'jm_ga_auth_action' );

endif;