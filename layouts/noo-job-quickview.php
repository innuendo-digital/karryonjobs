<?php

$job = get_post( $job_id );

$job_title = $job->post_title;
$job_content = apply_filters('the_content', $job->post_content );


$content_meta = array();

$settings_fields = get_theme_mod('noo_jobs_list_fields', 'job_type,job_location,job_date,_closing');

$content_meta['fields'] = !is_array( $settings_fields ) ? explode( ',', $settings_fields ) : $settings_fields;


?>

<div id="modalJob" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalJob" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content modal-job">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title text-center" id="memberModalApplicationLabel">
					<?php echo $job_title; ?>
				</h4>
			</div>
			<div class="modal-body">
				<div class="job-quick-view-content">
					<div class="job-quick-view-header">
						<?php echo jm_the_job_meta( $content_meta, $job ); ?>
					</div>
					<div class="job-desc job-quick-view-des">
						<?php echo $job_content; ?>
					</div>
				</div>
			</div>

			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close', 'noo'); ?></button>
				<a href="<?php echo get_permalink($job); ?>" class="btn btn-primary"><?php _e('View more', 'noo'); ?></a>
			</div>

		</div>
	</div>
</div>