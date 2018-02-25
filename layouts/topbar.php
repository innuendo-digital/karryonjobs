<div class="noo-topbar">
	<div class="container-boxed max">
		<?php
		$topbar_layout = noo_get_option( 'noo_top_bar_layout', 'right' );
		$topbar_text   = noo_get_option( 'noo_top_bar_text', '' );
		if ( ! empty( $topbar_text ) ): ?>
			<div
				class="noo-topbar-text pull-<?php echo( $topbar_layout == 'right' ? 'left' : 'right' ); ?>"><?php echo $topbar_text; ?></div>
		<?php endif; ?>
		<div class="pull-<?php echo $topbar_layout; ?>">
			<?php if ( noo_get_option( 'noo_top_bar_social', 1 ) ): ?>
				<div class="noo-topbar-social">
					<?php
					$facebook  = noo_get_option( 'noo_header_top_facebook_url', '' );
					$google    = noo_get_option( 'noo_header_top_google_plus_url', '' );
					$twitter   = noo_get_option( 'noo_header_top_twitter_url', '' );
					$linked    = noo_get_option( 'noo_header_top_linked_url', '' );
					$instagram = noo_get_option( 'noo_header_top_instagram_url', '' );
					$pinterest = noo_get_option( 'noo_header_top_pinterest_url', '' );
					$youtube   = noo_get_option( 'noo_header_top_youtube_url', '' );
					?>
					<ul>

						<?php if ( ! empty( $facebook ) ) : ?>
							<li>
								<a href="<?php echo esc_url( $facebook ); ?>" target="blank_"
								   class="fa fa-facebook"></a>
							</li>
						<?php endif; ?>

						<?php if ( ! empty( $google ) ) : ?>
							<li>
								<a href="<?php echo esc_url( $google ); ?>" target="blank_"
								   class="fa fa-google-plus"></a>
							</li>
						<?php endif; ?>

						<?php if ( ! empty( $twitter ) ) : ?>
							<li>
								<a href="<?php echo esc_url( $twitter ); ?>" target="blank_" class="fa fa-twitter"></a>
							</li>
						<?php endif; ?>

						<?php if ( ! empty( $linked ) ) : ?>
							<li>
								<a href="<?php echo esc_url( $linked ); ?>" target="blank_" class="fa fa-linkedin"></a>
							</li>
						<?php endif; ?>
						<?php if ( ! empty( $instagram ) ) : ?>
							<li>
								<a href="<?php echo esc_url( $instagram ); ?>" target="blank_"
								   class="fa fa-instagram"></a>
							</li>
						<?php endif; ?>

						<?php if ( ! empty( $pinterest ) ) : ?>
							<li>
								<a href="<?php echo esc_url( $pinterest ); ?>" target="blank_"
								   class="fa fa-pinterest"></a>
							</li>
						<?php endif; ?>
						<?php if ( ! empty( $youtube ) ) : ?>
							<li>
								<a href="<?php echo esc_url( $youtube ); ?>" target="blank_" class="fa fa-youtube"></a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( noo_get_option( 'noo_header_top_bar_login_link', 1 ) ): ?>

				<?php if ( is_user_logged_in() ): ?>

					<div class="noo-topbar-user">
						<a id="thumb-info" href="<?php echo Noo_Member::get_member_page_url(); ?>">
							<span class="profile-name"><?php echo Noo_Member::get_display_name(); ?></span>
							<span class="profile-avatar"><?php echo noo_get_avatar( get_current_user_id(), 36 ); ?></span>
							<?php echo user_notifications_number(); ?>
						</a>
						<ul class="sub-menu">
							<?php if(Noo_Member::is_employer()):?>
								<li class="menu-item" ><a href="<?php echo Noo_Member::get_post_job_url()?>"><i class="fa fa-edit"></i> <?php _e('Post a Job','noo')?></a></li>
								<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-job')?>"><i class="fa fa-file-text-o"></i> <?php _e('Manage Jobs','noo')?></a></li>
								<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-application')?>" style="white-space: nowrap;"><i class="fa fa-newspaper-o"></i> <?php _e('Manage Applications','noo')?></a></li>
								<?php do_action( 'noo-member-employer-menu' ); ?>
								<li class="divider" role="presentation"></li>
								<?php //if(jm_is_woo_job_posting()) : ?>
								<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-plan')?>"><i class="fa fa-file-text-o"></i> <?php _e('Manage Plan','noo')?></a></li>
								<?php //endif; ?>
								<li class="menu-item" ><a href="<?php echo Noo_Member::get_company_profile_url()?>"><i class="fa fa-users"></i> <?php _e('Company Profile','noo')?></a></li>
							<?php elseif(Noo_Member::is_candidate()):?>
								<?php if( jm_resume_enabled() ) : ?>
									<li class="menu-item" ><a href="<?php echo Noo_Member::get_post_resume_url()?>"><i class="fa fa-edit"></i> <?php _e('Post a Resume','noo')?></a></li>
									<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-resume')?>" style="white-space: nowrap;"><i class="fa fa-file-text-o"></i> <?php _e('Manage Resumes','noo')?></a></li>
								<?php endif; ?>
								<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-job-applied')?>" style="white-space: nowrap;"><i class="fa fa-newspaper-o"></i> <?php _e('Manage Applications','noo')?></a></li>
								<?php if( Noo_Job_Alert::enable_job_alert() ) : ?>
									<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('job-alert')?>"><i class="fa fa-bell-o"></i> <?php _e('Jobs Alert','noo')?></a></li>
								<?php endif; ?>
								<?php do_action( 'noo-member-candidate-menu' ); ?>
								<li class="divider" role="presentation"></li>
								<?php if(jm_is_woo_resume_posting()) : ?>
									<li class="menu-item" ><a href="<?php echo Noo_Member::get_endpoint_url('manage-plan')?>"><i class="fa fa-file-text-o"></i> <?php _e('Manage Plan','noo')?></a></li>
								<?php endif; ?>
								<li class="menu-item" ><a href="<?php echo Noo_Member::get_candidate_profile_url()?>"><i class="fa fa-user"></i> <?php _e('My Profile','noo')?></a></li>
							<?php endif; ?>
							<li class="menu-item" ><a href="<?php echo Noo_Member::get_logout_url() ?>"><i class="fa fa-sign-out"></i> <?php _e('Sign Out','noo')?></a></li>
						</ul>
					</div>

				<?php else: ?>

					<ul class="noo-topbar-login-link">
						<li>
							<a href="<?php echo Noo_Member::get_login_url(); ?>" class="member-links member-login-link"><i
									class="fa fa-sign-in"></i>&nbsp;<?php _e( 'Login', 'noo' ) ?></a>
						</li>
						<li>
							<a href="<?php echo Noo_Member::get_register_url(); ?>"
							   class="member-links member-register-link"><i
									class="fa fa-key"></i>&nbsp;<?php _e( 'Register', 'noo' ) ?></a>
						</li>
					</ul>

				<?php endif; ?>

			<?php endif; ?>
		</div>
		<div class="clearfix"></div>
	</div>
</div>