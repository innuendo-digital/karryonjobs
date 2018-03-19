<?php

/* -------------------------------------------------------
 * Create functions jm_request_live_search
 * ------------------------------------------------------- */

if ( ! function_exists( 'jm_request_live_search' ) ) :
	
	function jm_request_live_search() {

		check_ajax_referer('noo-advanced-live-search', 'live-search-nonce');

		$post_type = esc_html( $_GET['post_type'] );
		if( !in_array( $post_type, array( 'noo_job', 'noo_resume' ) ) ) {
			wp_die();
		} elseif( $post_type == 'noo_resume' && !jm_can_view_resume(null,true) ) {
			wp_die();
		}

		unset( $_GET['post_type'] );

		$args = array(
			'post_type'   => $post_type,
			'post_status' => 'publish',
			's' => esc_html( $_GET['s'] ),
			'paged' => isset( $_GET['paged'] ) ? $_GET['paged'] : 1
		);

		unset( $_GET['s'] );
		unset( $_GET['action'] );
		unset( $_GET['live-search-nonce'] );

		add_filter( 'paginate_links', 'jm_ajax_live_search_paginate' );

		if ( $args['post_type'] == 'noo_resume' ) :
			$args = jm_resume_query_from_request( $args, $_GET );

			$transient_name = jm_get_search_transient_key( $args['post_type'], $args );

			if ( empty( $transient_name ) || false === ( $query = get_transient( $transient_name ) ) ) {
				$query = new WP_Query( $args );

				if( !empty( $transient_name ) ) {
					set_transient( $transient_name, $query, HOUR_IN_SECONDS );
					jm_save_search_transient_key( $args['post_type'], $transient_name );
				}

				// if( defined( 'WP_DEBUG' ) && WP_DEBUG )
				// 	error_log( 'Search Transient key: ' . $transient_name );
			}

			$loop_args = apply_filters( 'noo_resume_search_args', array(
					'query'    => $query,
					'live_search' => true
				), $args );
			jm_resume_loop( $loop_args );

		elseif ( $args['post_type'] == 'noo_job' ) :
			$args = jm_job_query_from_request( $args, $_GET );

			$transient_name = jm_get_search_transient_key( $args['post_type'], $args );

			if ( empty( $transient_name ) || false === ( $query = get_transient( $transient_name ) ) ) {
				$query = new WP_Query( $args );

				if( !empty( $transient_name ) ) {
					set_transient( $transient_name, $query, HOUR_IN_SECONDS );
					jm_save_search_transient_key( $args['post_type'], $transient_name );
				}

				// if( defined( 'WP_DEBUG' ) && WP_DEBUG )
				// 	error_log( 'Search Transient key: ' . $transient_name );
			}

			$loop_args = apply_filters( 'noo_job_search_args', array(
					'query'    => $query,
//					'paginate' =>'loadmore',
					'title'    =>''
				), $args );
			jm_job_loop( $loop_args );

		endif;

		remove_filter( 'paginate_links', 'jm_ajax_live_search_paginate' );

		wp_die();

	}

	add_action( 'wp_ajax_nopriv_live_search', 'jm_request_live_search' );
	add_action( 'wp_ajax_live_search', 'jm_request_live_search' );

endif;

/** ====== END jm_request_live_search ====== **/

if ( ! function_exists( 'jm_ajax_live_search_paginate' ) ) :
	
	function jm_ajax_live_search_paginate( $link ) {

		if (defined('DOING_AJAX') && DOING_AJAX ) {
			$post_type = get_query_var( 'post_type' );
			if( $post_type == 'noo_job' || $post_type == 'noo_resume' ) {
				$link_request = explode( '?', $link );
				$link_query = isset( $link_request[1] ) ? $link_request[1] : '';
				wp_parse_str( $link_query, $link_args );

				$link = isset( $link_args['_wp_http_referer'] ) ? $link_args['_wp_http_referer'] : home_url('/');
				unset( $link_args['action'] );
				unset( $link_args['live-search-nonce'] );
				unset( $link_args['_wp_http_referer'] );
				foreach ($link_args as $key => $value) {
					if( $question_mark = strpos($key, '?') ) {
						$key = substr($key, $question_mark);
					}
					$link = esc_url_raw( add_query_arg( $key, $value, $link ) );
				}
			}
		}

		return $link;
	}

endif;

if ( ! function_exists( 'jm_get_search_transient_key' ) ) :
	
	function jm_get_search_transient_key( $post_type = 'noo_job', $args = array() ) {
		unset( $args['post_type'] );
		unset( $args['post_status'] );

		$key = "{$post_type}";

		if( isset( $args['s'] ) && !empty( $args['s'] ) ) {
			$key .= "_s:{$args['s']}";
			unset( $args['s'] );
		}

		if( isset( $args['meta_query'] ) && !empty( $args['meta_query'] ) ) {
			foreach ($args['meta_query'] as $meta_query) {
				if( is_array( $meta_query ) && isset( $meta_query['key'] ) ) {
					$k = str_replace("_{$post_type}_field_", '', $meta_query['key'] );
					$v = $meta_query['value'];
					if( is_array( $v ) ) {
						$v = json_encode( $v, 256 );
					}
					$key .= "_{$k}:{$v}";
				} elseif( isset( $meta_query['relation'] ) ) {
					unset($meta_query['relation']);
					$k = '';
					$v = array();
					foreach ($meta_query as $sub_mq) {
						if( is_array( $sub_mq ) && isset( $sub_mq['key'] ) ) {
							$k = $sub_mq['key'];
							$v[] = trim( $sub_mq['value'], '"' );
						}
					}
					if( !empty( $k ) && !empty( $v ) ) {
						$k = str_replace("_{$post_type}_field_", '', $k );
						if( is_array( $v ) ) {
							$v = json_encode( $v, 256 );
						}
						$key .= "_{$k}:{$v}";
					}
				}
			}
			unset( $args['meta_query'] );
		}

		if( isset( $args['tax_query'] ) && !empty( $args['tax_query'] ) ) {
			unset( $args['tax_query']['relation'] );
			foreach ($args['tax_query'] as $tq) {
				if( isset( $tq['taxonomy'] ) ) {
					$k = $tq['taxonomy'];
					$v = $tq['terms'];
					if( is_array( $v ) ) {
						$v = json_encode( $v, 256 );
					}
					$key .= "_{$k}:{$v}";
				}
			}
			unset( $args['tax_query'] );
		}

		if( isset( $args['date_query'] ) && !empty( $args['date_query'] ) ) {
			if( isset( $args['date_query']['after'] ) ) {
				$k = 'after';
				$v = $args['date_query']['after'];
				$key .= "_{$k}:{$v}";
			}
			if( isset( $args['date_query']['before'] ) ) {
				$k = 'before';
				$v = $args['date_query']['before'];
				$key .= "_{$k}:{$v}";
			}
			unset( $args['date_query'] );
		}

		if( isset( $args['paged'] ) && !empty( $args['paged'] ) ) {
			$key .= "_paged:{$args['paged']}";
			unset( $args['paged'] );
		}

		foreach ($args as $k => $v) {
			if( is_array( $v ) ) {
				$v = json_encode( $v, 256 );
			}
			$key .= "_{$k}:{$v}";
		}

		if( count( $key ) <= 190 ) {
			return $key;
		}

		return '';
	}

endif;

if ( ! function_exists( 'jm_save_search_transient_key' ) ) :
	
	function jm_save_search_transient_key( $post_type = 'noo_job', $key = '' ) {
		$option_key = "jm_{$post_type}_saved_search_keys";
		$keys = get_option( $option_key, array() );

		if( !in_array( $key, $keys ) && !empty( $key ) ) {
			$keys[] = $key;
			update_option( $option_key, $keys );
		}
	}

endif;

if ( ! function_exists( 'jm_search_delete_job_search_transients' ) ) :
	
	function jm_search_delete_job_search_transients( $job, $new_status, $old_status ) {
		if( $new_status == 'publish' || $old_status == 'publish' ) {
			$option_key = 'jm_noo_job_saved_search_keys';
			$keys = get_option( $option_key, array() );

			if( !empty( $keys ) && is_array( $keys ) ) {
				foreach ($keys as $key) {
					delete_transient( $key );
				}
			}
		}
	}
	add_action( 'jm_delete_job_transient', 'jm_search_delete_job_search_transients', 10, 3 );

endif;

if ( ! function_exists( 'jm_search_delete_resume_search_transients' ) ) :
	
	function jm_search_delete_resume_search_transients( $resume, $new_status, $old_status ) {
		if( $new_status == 'publish' || $old_status == 'publish' ) {
			$option_key = 'jm_noo_resume_saved_search_keys';
			$keys = get_option( $option_key, array() );

			if( !empty( $keys ) && is_array( $keys ) ) {
				foreach ($keys as $key) {
					delete_transient( $key );
				}
			}
		}
	}
	add_action( 'jm_delete_resume_transient', 'jm_search_delete_resume_search_transients', 10, 3 );

endif;
