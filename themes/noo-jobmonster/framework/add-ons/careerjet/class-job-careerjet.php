<?php

class JM_CareerJet {

	/**
	 * Job_Listings_CareerJet constructor.
	 */
	public function __construct() {

		require_once NOO_FRAMEWORK . '/add-ons/careerjet/Careerjet_API.php';

		add_shortcode( 'job_careerjet', array( $this, 'shortcode' ) );

		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		if ( is_plugin_active( 'js_composer/js_composer.php' ) ) :

			add_action( 'admin_init', array( $this, 'vc_map' ), 20, 0 );

		endif;
	}

	public function shortcode( $atts = array() ) {

		$atts = shortcode_atts( array(
			'keywords'    => '',
			'location'    => '',
			'page'        => 1,
			'number'      => 10,
			'type'        => 'none',
			'search_form' => false,
			'aff_id'      => '8cf0102af68c848437da3f877babe47a',
		), $atts );

		ob_start();

		$page = isset( $_GET[ 'current_page' ] ) ? $_GET[ 'current_page' ] : $atts[ 'page' ];

		$location = isset( $_GET[ 'location' ] ) && ! empty( $_GET[ 'location' ] ) ? $_GET[ 'location' ] : $atts[ 'location' ];
		$keywords = isset( $_GET[ 'keywords' ] ) && ! empty( $_GET[ 'keywords' ] ) ? $_GET[ 'keywords' ] : $atts[ 'keywords' ];
		$job_type = isset( $_GET[ 'type' ] ) ? $_GET[ 'type' ] : $atts[ 'type' ];

		$api = new Careerjet_API( 'en_GB' );

		$result = $api->search( array(
			'keywords'       => $keywords,
			'location'       => $location,
			'contractperiod' => $job_type,
			'pagesize'       => $atts[ 'number' ],
			'page'           => $page,
			'affid'          => $atts[ 'aff_id' ],
		) );

		ob_start();

		if ( $result->type == 'JOBS' ) {
			$total_jobs  = $result->hits;
			$total_pages = $result->pages;

			?>
			<?php if ( $atts[ 'search_form' ] ): ?>

				<form action="" class="search-form job-careerjet-form">

					<input type="text" name="keywords" class="form-control"
					       value="<?php echo $keywords; ?>"
					       placeholder="<?php _e( 'Enter your keyword', 'noo' ); ?>"/>

					<input type="text" name="location" class="form-control"
					       value="<?php echo $location; ?>"
					       placeholder="<?php _e( 'Enter your location', 'noo' ); ?>"/>

					<select name="type" class="form-control">
						<option value="none"><?php _e( 'All Types', 'noo' ); ?></option>
						<option value="f"><?php _e( 'Full Time', 'noo' ); ?></option>
						<option value="p"><?php _e( 'Part Time', 'noo' ); ?></option>
					</select>

					<button type="submit" class="btn btn-primary btn-search">
						<span class="screen-reader-text"><?php _e( 'Search', 'noo' ); ?></span>
					</button>
				</form>
			<?php endif; ?>

			<div class="jobs posts-loop job-careerjet">
				<div class="post-loop-title">
					<h3><?php echo sprintf( _n( '%s Job', '%s Jobs', $total_jobs, 'noo' ), $total_jobs ) ?></h3>
				</div>
				<div class="posts-loop-content">

					<?php

					if ( $total_jobs > 0 ):
						$jobs = $result->jobs;

						foreach ( $jobs as $job ) {
							?>
							<article class="noo_job job-careerjet-item">
								<div class="loop-item-wrap">
									<div class="loop-item-content" style="width: 70% !important;float: left">
										<h2 class="loop-item-title">
											<a target="_blank"
											   href="<?php echo $job->url; ?>"><?php echo $job->title; ?></a>
										</h2>
										<p class="content-meta">
											<span><?php _e( 'Salary:', 'noo' ); ?><?php echo $job->salary; ?></span>
											<span class="job-company"><?php echo $job->company; ?></span>
									<span class="job-location" itemscope="" itemtype="http://schema.org/LocalBusiness">
										<i class="fa fa-map-marker"></i>
										<?php echo $job->locations; ?>
									</span>

									<span class="job-date">
										<time class="entry-date" datetime="<?php echo $job->date; ?>">
											<i class="fa fa-calendar"></i>
											<span itemprop="datePosted">
												<?php echo $job->date; ?>
											</span>
										</time>
									</span>
										</p>
									</div>
									<div class="show-view-more" style="float: right;">
										<a target="_blank" href="<?php echo $job->url; ?>"
										   class="btn btn-primary"><?php _e( 'View more', 'noo' ); ?></a>
									</div>
								</div>

							</article>
							<?php
						}

					else: ?>

						<p><?php _e( 'No results found, please try again later.', 'noo' ); ?></p>

					<?php endif; ?>
				</div>
				<div class="pagination list-center">
					<?php echo $this->pagination( $page, $total_pages ); ?>
				</div>
			</div>
			<?php
		} else {
			?>

			<p><?php _e( 'No results found, please try again later.', 'noo' ); ?></p>

			<?php
		}

		return ob_get_clean();
	}

	public function pagination( $current, $total_pages ) {

		$defaults   = array(
			'base'                   => add_query_arg( 'current_page', '%#%' ),
			'format'                 => '',
			'total'                  => $total_pages,
			'current'                => $current,
			'prev_next'              => true,
			'prev_text'              => '<i class="fa fa-long-arrow-left"></i>',
			'next_text'              => '<i class="fa fa-long-arrow-right"></i>',
			'show_all'               => false,
			'end_size'               => 1,
			'mid_size'               => 1,
			'add_fragment'           => '',
			//			'type'                   => 'list',
			'before'                 => '',
			'after'                  => '',
			'echo'                   => false,
			'use_search_permastruct' => true,
		);
		$page_links = paginate_links( $defaults );

		$page_links = $defaults[ 'before' ] . $page_links . $defaults[ 'after' ];

		return $page_links;
	}

	public function vc_map() {
		if ( defined( 'WPB_VC_VERSION' ) ) :
			vc_map( array(
				'base'                    => 'job_careerjet',
				'name'                    => __( 'Job CareerJet', 'noo' ),
				'weight'                  => 809,
				'class'                   => '',
				'icon'                    => '',
				'category'                => __( 'JobMonster', 'noo' ),
				'description'             => '',
				'show_settings_on_create' => false,
				'params'                  => array(
					array(
						'param_name'  => 'keywords',
						'heading'     => __( 'Keywords', 'noo' ),
						'type'        => 'textfield',
						'holder'      => 'div',
						'admin_label' => true,
						'value'       => '',
					),
					array(
						'param_name'  => 'location',
						'heading'     => __( 'Location', 'noo' ),
						'type'        => 'textfield',
						'holder'      => 'div',
						'admin_label' => true,
						'value'       => '',
						'description' => __( 'Enter location. ex: Ha noi', 'noo' ),
					),
					array(
						'param_name'  => 'type',
						'heading'     => __( 'Job Type', 'noo' ),
						'type'        => 'dropdown',
						'holder'      => 'div',
						'admin_label' => true,
						'value'       => array(
							__( 'All type', 'noo' )  => 'none',
							__( 'Full time', 'noo' ) => 'f',
							__( 'Part time', 'noo' ) => 'p',
						),
					),
					array(
						'param_name'  => 'number',
						'heading'     => __( 'Job Per Page', 'noo' ),
						'type'        => 'textfield',
						'admin_label' => true,
						'holder'      => 'div',
						'value'       => __( '10', 'noo' ),
					),
					array(
						'param_name'  => 'search_form',
						'heading'     => __( 'Show Search Form', 'noo' ),
						'description' => '',
						'admin_label' => true,
						'type'        => 'checkbox',
						'holder'      => 'div',
						'value'       => array( 'Show' => 'true' ),
					),
					array(
						'param_name'  => 'aff_id',
						'heading'     => __( 'Affililate ID', 'noo' ),
						'description' => __( '<a target="_blank" href="http://www.careerjet.vn/partners/?ak=8cf0102af68c848437da3f877babe47a">Become a Careerjet affiliate & get Affililate ID</a> ', 'noo' ),
						'type'        => 'textfield',
						'holder'      => 'div',
						'value'       => '',
					),
				),
			) );
		endif;
	}

}

new JM_CareerJet();