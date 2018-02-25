<?php
if( !function_exists( 'jm_remove_job_transient' ) ) :

	/**
	 * Remove jobs transient
	 */
	function jm_remove_job_transient( $new_status, $old_status, $post ) {
		if( $post->post_type !== 'noo_job' && $old_status == $new_status )
			return;

		do_action( 'jm_delete_job_transient', $post, $new_status, $old_status );
	}
	add_action('transition_post_status', 'jm_remove_job_transient', 10, 3);
endif;

if( !function_exists( 'jm_remove_resume_transient' ) ) :

	/**
	 * Remove resumes transient
	 */
	function jm_remove_resume_transient( $new_status, $old_status, $post ) {
		if( $post->post_type !== 'noo_resume' && $old_status == $new_status )
			return;

		do_action( 'jm_delete_resume_transient', $post, $new_status, $old_status );
	}
	add_action('transition_post_status', 'jm_remove_resume_transient', 10, 3);
endif;
