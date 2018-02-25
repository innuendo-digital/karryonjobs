<?php
if( !class_exists('Noo_Application') ) :
class Noo_Application {

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ), 0 );
		if ( is_admin() ) {
			add_action('admin_init', array($this,'admin_init'));

			// No more settings link
			// add_action( 'admin_menu', array($this,'admin_menu') );

			add_filter('noo_job_settings_tabs_array', array($this,'add_setting_application_tab'), 11);
			add_action('noo_job_setting_application', array($this,'setting_application'));
			add_filter( 'manage_edit-noo_application_columns', array( $this, 'columns' ) );
			add_action( 'manage_noo_application_posts_custom_column', array( $this, 'custom_columns' ), 2 );
			add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 1, 2 );
			add_action( 'restrict_manage_posts', array($this, 'restrict_manage_posts') );
			add_filter( 'parse_query', array($this, 'posts_filter') );
			add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		
			add_action( 'post_edit_form_tag' , array( $this, 'post_edit_form_tag' ) );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
			add_action( 'save_post', array( $this, 'process_application' ), 30 );
			add_filter( 'noo_sanitize_meta__attachment', array( $this, 'process_attachment' ) );
			
			add_filter( 'views_edit-noo_application', array( $this,'modified_views_status' ) );
			foreach ( array( 'post', 'post-new' ) as $hook ) {
				add_action( "admin_footer-{$hook}.php", array( $this,'extend_application_status' ) );
			}
		}
	}

	public function admin_init() {
		register_setting('noo_job_linkedin','noo_job_linkedin');
	}

	public function admin_menu() {
		global $submenu;
		$permalink = jm_setting_page_url('application');
		
		$submenu['edit.php?post_type=noo_application'][] = array( 'Settings', 'edit_theme_options', $permalink );
	}

	public function post_edit_form_tag( $post ) {
		if( isset( $post->post_type ) && $post->post_type === 'noo_application' ) {
			echo ' enctype="multipart/form-data"';
		}
	}
	
	public function add_meta_boxes() {
		$helper = new NOO_Meta_Boxes_Helper( '', array( 'page' => 'noo_application' ) );

		$meta_box = array( 
			'id' => 'job', 
			'title' => __( 'Job', 'noo' ), 
			'page' => 'noo_application', 
			'context' => 'side', 
			'priority' => 'default', 
			'fields' => array( 
				array(
					'id' => 'parent_id',
					'label' => __( 'Apply for Job', 'noo' ),
					'type' => 'app_job',
					'callback' => array( $this, 'meta_box_app_job' )
				),
			) 
		);

		$helper->add_meta_box($meta_box);

		$meta_box = array( 
			'id' => 'candidate', 
			'title' => __( 'Candidate Information', 'noo' ), 
			'page' => 'noo_application', 
			'context' => 'normal', 
			'priority' => 'high', 
			'fields' => array( 
				array(
					'id' => 'post_author_override',
					'label' => __( 'Candidate', 'noo' ),
					'type' => 'applicant',
					'callback' => array( $this, 'meta_box_applicant' ),
				), 
				array(
					'id' => 'post_title',
					'label' => __( 'Candidate Name', 'noo' ),
					'type' => 'post_title'
				),
				array(
					'id' => '_candidate_email',
					'label' => __( 'Contact Email', 'noo' ),
					'type' => 'text'
				),
				array( 'id' => '_resume',
					'label' => __( 'Resume', 'noo' ),
					'type' => 'app_resume',
					'callback' => array( $this, 'meta_box_app_resume' )
				),
			) 
		);

		$helper->add_meta_box($meta_box);

		$meta_box = array( 
			'id' => 'application', 
			'title' => __( 'Application', 'noo' ), 
			'page' => 'noo_application', 
			'context' => 'normal', 
			'priority' => 'high', 
			'fields' => array( 
				array(
					'id' => 'content',
					'label' => __( 'Message', 'noo' ),
					'type' => 'app_message',
					'callback' => array( $this, 'meta_box_app_message' )
				),
				array(
					'id' => '_attachment',
					'label' => __( 'Attachment', 'noo' ),
					'type' => 'app_attachment',
					'callback' => array( $this, 'meta_box_app_attachment' )
				),
			) 
		);

		$helper->add_meta_box($meta_box);

		$meta_box = array( 
			'id' => 'other', 
			'title' => __( 'Other Information', 'noo' ), 
			'page' => 'noo_application', 
			'context' => 'normal', 
			'priority' => 'high', 
			'fields' => array() 
		);

		$fields = jm_get_application_custom_fields();
		if($fields){
			foreach ($fields as $field){
				if( $field['name'] == 'application_message' ) continue;

				$id = jm_application_custom_fields_name($field['name'], $field);

				$new_field = noo_custom_field_to_meta_box( $field, $id );

				$meta_box['fields'][] = $new_field;
			}
		}

		$helper->add_meta_box($meta_box);
	}

	public function process_application( $post_id ) {
		if( get_post_type( $post_id ) == 'noo_application' ) {
			$application = get_post( $post_id );

			remove_action( 'save_post', array( $this, 'process_application' ), 30 );

			if( !empty( $_POST['post_author_override'] ) ) {
				$candidate = get_userdata( $_POST['post_author_override'] );
				wp_update_post( array(
						'ID' => $post_id,
						'post_title' => $candidate->display_name,
					)
				);
				update_post_meta( $post_id, '_candidate_email', $candidate->user_email );
			}

			if( $application->post_status == 'publish' ) {
				//Send email
				self::send_notification(array(
					'job_id'=> $application->post_parent,
					'application_id'=>$application->ID,
					'candidate_name'=>$application->post_title,
					'candidate_email'=>get_post_meta( $application->ID, '_candidate_email', true ),
					'application_message'=>$application->post_content,
				));
			}

			add_action( 'save_post', array( $this, 'process_application' ), 30 );
		}
	}

	public function meta_box_app_job( $post, $id, $type, $meta, $std, $field ) {
		$jobs = get_posts( array( 'post_type' => 'noo_job', 'post_status' => array( 'publish','inactive','expired' ), 'posts_per_page' => -1 ) );
		$chosen_class = !is_rtl() ? 'noo-admin-chosen' : 'noo-admin-chosen chosen-rtl';
		$meta = (int) $post->post_parent;
		?>
			<select id="<?php echo $id; ?>" name="<?php echo $id; ?>" class="<?php echo $chosen_class; ?>">
				<option value=""><?php echo __('Select Job', 'noo'); ?></option>
				<?php foreach ($jobs as $job) : ?>
					<option value="<?php echo $job->ID; ?>" class="candidate_<?php echo $job->post_author; ?>" data-permalink="<?php echo get_permalink($job->ID); ?>" <?php selected( $meta, $job->ID ); ?>><?php echo $job->post_title; ?></option>
				<?php endforeach; ?>
			</select>
		<?php
	}
	
	public function meta_box_applicant( $post, $id, $type, $meta, $std, $field ) {
		$candidates = jm_get_members( Noo_Member::CANDIDATE_ROLE );
		$options = array();
		foreach ($candidates as $candidate) {
			$options[] = array(
					'value' => $candidate->ID,
					'label' => $candidate->display_name,
				);
		}
		$field['options'] = $options;

		$std = '';
		$candidate_email = get_post_meta( $post->ID, '_candidate_email', true );
		$user = !empty( $candidate_email ) ? get_user_by( 'email', $candidate_email ) : false;
		$user_id = !empty( $user ) ? $user->ID : '';
		$meta = $post->post_status == 'auto-draft' ? $std : $user_id;

		$chosen_class = !is_rtl() ? 'noo-admin-chosen' : 'noo-admin-chosen chosen-rtl';
		?>
			<select id="<?php echo $id; ?>" name="<?php echo $id; ?>" class="<?php echo $chosen_class; ?>">
				<option value=""><?php echo __( 'Guest user', 'noo' ); ?></option>
				<?php foreach ($candidates as $candidate) : ?>
					<option value="<?php echo $candidate->ID; ?>" <?php selected( $meta, $candidate->ID ); ?>><?php echo $candidate->display_name; ?></option>
				<?php endforeach; ?>
			</select>
			<script>
	        	jQuery(document).ready(function($) {
	            	var author_el = $('#post_author_override');

	            	author_el.change( function() {
						if( author_el.val() === "" ) {
		            		$(".post_title").show();
		            		$("._candidate_email").show();
		            	} else {
		            		$(".post_title").hide();
		            		$("._candidate_email").hide();
		            	}
					}).change();
		        });
	        </script>
		<?php
	}

	public function meta_box_app_message( $post, $id, $type, $meta, $std, $field ) {
		$configs = array(
			'media_buttons' => false,
			'textarea_name' => $id,
			'textarea_rows' => 15,
			'teeny' => true,
		);

		wp_editor($post->post_content, $id, $configs);
	}

	public function process_attachment( $val ){
		if( isset( $_FILES['_attachment'] ) ) {
			$val = empty( $val ) ? array() : $val;
			$val = !is_array( $val ) ? array( $val ) : $val;

			$attachment = Noo_Form_Handler::upload_file( '_attachment', jm_get_allowed_attach_file_types(), true );

			if( !empty( $attachment ) ) {
				$attachment = !is_array( $attachment ) ? array( $attachment ) : $attachment;
				$val = array_merge( $val, $attachment );
			}
		}
		// error_log('Attachment: ' . implode( $val) );
		return $val;
	}

	public function meta_box_app_attachment( $post, $id, $type, $meta, $std, $field ) {
		?>
		<input type="hidden" name="<?php echo "noo_meta_boxes[{$id}]"; ?>" value="" />
		<?php if( empty( $meta ) ) {
			if( $post->post_status != 'auto-draft' ) {
				echo __( 'No Attachment', 'noo' );
			}
		} elseif( is_string( $meta) && strpos($meta,'linkedin') ) { ?>
			<a href="<?php echo esc_url( $meta ); ?>" title="<?php echo esc_attr__('LinkedIn profile','noo'); ?>" target="_blank"><i class="fa fa-linkedin"></i><?php echo esc_attr__('LinkedIn profile','noo'); ?></a>
		<?php } else {
			$meta = !empty( $meta ) ? noo_json_decode( $meta ) : array();
			foreach ($meta as $atm) : ?>
				<?php $file_name = basename( $atm ); ?>
				<p>
					<a href="#" onclick="this.parentElement.remove(); return false;"><i class="dashicons dashicons-trash"></i></a>
					&nbsp;<strong><?php echo esc_attr( $file_name ) ?></strong>&nbsp;
					<a href="<?php echo esc_url( $atm ); ?>" target="_blank"><i class="dashicons dashicons-download"></i></a>
					<input type="hidden" name="<?php echo "noo_meta_boxes[{$id}][]"; ?>" value="<?php echo esc_url( $atm ); ?>" />
				</p>
			<?php endforeach;
		}
		
		$allowed_exts = jm_get_allowed_attach_file_types();
    	$max_upload_size = wp_max_upload_size();
    	if ( ! $max_upload_size ) {
    		$max_upload_size = 0;
    	}
	    ?>
	    <p><input type="file" name="<?php echo "{$id}[]"; ?>" class="jform-validate-uploadcv" multiple accept="<?php echo '.' . implode(',.', $allowed_exts); ?>">
	    </p>
		<p class="help-block"><?php printf( __( 'Maximum upload file size: %s', 'noo' ), esc_html( size_format( $max_upload_size ) ) ); ?></p>
		<p class="help-block"><?php echo sprintf( __('Allowed file: %s', 'noo'), '.' . implode(', .', $allowed_exts) ); ?></p>

		<?php
	}

	public function meta_box_app_resume( $post, $id, $type, $meta, $std, $field ) {
		$resumes = get_posts( array( 'post_type' => 'noo_resume', 'post_status' => 'publish', 'posts_per_page' => -1 ) );

		$resume_link = !empty( $meta ) ? add_query_arg('application_id', $meta, get_permalink($meta)) : 'javascript:void(0)';
		?>
			<select id="<?php echo $id; ?>" name="noo_meta_boxes[<?php echo $id; ?>]">
				<option value="" class="no-hide"><?php echo __('Select Resume', 'noo'); ?></option>
				<?php foreach ($resumes as $resume) : ?>
					<option value="<?php echo $resume->ID; ?>" class="candidate_<?php echo $resume->post_author; ?>" data-permalink="<?php echo get_permalink($resume->ID); ?>"><?php echo $resume->post_title; ?></option>
				<?php endforeach; ?>
			</select>
			<a class="application-resume" href="<?php echo $resume_link; ?>" target="_blank"><?php echo __('View Resume', 'noo'); ?></a>
	        <script>
	        	jQuery(document).ready(function($) {
		            if( $('#post_author_override') ) {
		            	var author_el = $('#post_author_override');
		            	var parentField = $('.<?php echo esc_attr($id); ?>');
		            	var resumes_el = parentField.find( 'select#<?php echo esc_attr($id); ?>' );

		            	if( author_el.val() === "" ) {
		            		parentField.hide();
		            	} else {
		            		resumes_el.find( 'option' ).hide();
		            		resumes_el.find( 'option.candidate_' + author_el.val() ).show();
		            		parentField.show();
		            	}

		            	author_el.change( function() {
							var $this = $(this);
			            	resumes_el.find( 'option:selected' ).removeAttr("selected");
			            	$this.siblings('a.application-resume').attr('href', 'javascript:void(0)' );

							if( $this.val() === "" ) {
			            		parentField.hide();
			            	} else {
			            		resumes_el.find( 'option:not(.no-hide)' ).hide();
			            		resumes_el.find( 'option.candidate_' + $this.val() ).show();
			            		parentField.show();
			            	}
						});

						resumes_el.change( function() {
							var $this = $(this);
							var selected_opt = $this.find( 'option:selected' );
							$this.siblings('a.application-resume').attr('href', selected_opt.data('permalink') );
						});
		            }
		        });
	        </script>
    	<?php
	}
	
	public function enter_title_here( $text, $post ) {
		if ( $post->post_type == 'noo_application' ) {
			return __( 'Candidate name', 'noo' );
		}
		return $text;
	}
	
	public function post_updated_messages( $messages ) {
		$messages['noo_application'] = array(
			0  => '',
			1  => __( 'Job application updated.', 'noo' ),
			2  => __( 'Custom field updated.', 'noo' ),
			3  => __( 'Custom field deleted.', 'noo' ),
			4  => __( 'Job application updated.', 'noo' ),
			5  => '',
			6  => __( 'Job application published.', 'noo' ),
			7  => __( 'Job application saved.', 'noo' ),
			8  => __( 'Job application submitted.', 'noo' ),
			9  => '',
			10 => __( 'Job application draft updated.', 'noo' )
		);
	
		return $messages;
	}

	public function modified_views_status( $views ) {
		if( isset( $views['publish'] ) )
			$views['publish'] = str_replace( 'Published ', __('Approved', 'noo') . ' ', $views['publish'] );

		return $views;
	}
	
	public function restrict_manage_posts() {
		global $typenow, $wp_query, $wpdb;
	
		if ( 'noo_application' != $typenow ) {
			return;
		}
	
		?>
		<select id="dropdown_noo_job" name="job">
		<option value=""><?php _e( 'All jobs', 'noo' ) ?></option>
			<?php
				$jobs_with_applications = $wpdb->get_col( "SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'noo_application'" );
				$current                = isset( $_GET['job'] ) ? $_GET['job'] : 0;
				foreach ( $jobs_with_applications as $job_id ) {
					if ( ( $title = get_the_title( $job_id ) ) && $job_id ) {
						echo '<option value="' . $job_id . '" ' . selected( $current, $job_id, false ) . '">' . $title . '</option>';
					}
				}
			?>
		</select>
		<?php
		// Candidate
		$candidates = jm_get_members( Noo_Member::CANDIDATE_ROLE );
		?>
		<select name="candidate">
			<option value=""><?php _e('All Candidates', 'noo'); ?></option>
			<?php
			$current_v = isset($_GET['candidate'])? $_GET['candidate']:'';
			foreach ($candidates as $candidate) {
				printf
				(
					'<option value="%s"%s>%s</option>',
					$candidate->ID,
					$candidate->ID == $current_v ? ' selected="selected"':'',
					empty( $candidate->display_name ) ? $candidate->login_name : $candidate->display_name
				);
			}
			?>
		</select>
		<?php
	}

	public function posts_filter( $query ){
		global $pagenow;
		$type = 'post';
		if (isset($_GET['post_type'])) {
			$type = $_GET['post_type'];
		}
		if ( 'noo_application' == $type && is_admin() && $pagenow=='edit.php' ) {
			if( !isset($query->query_vars['post_type']) || $query->query_vars['post_type'] == 'noo_application' ) {
				if( isset($_GET['job']) && $_GET['job'] != '') {
					$job_id = $_GET['job'];

					$query->query_vars['post_parent'] = $job_id;
				}
				if( isset($_GET['candidate']) && $_GET['candidate'] != '') {
					$candidate_id = $_GET['candidate'];
					$candidate_info = get_userdata($candidate_id);

					$query->query_vars['meta_query'][] = array(
						'key' => '_candidate_email',
						'value' => $candidate_info->user_email,
					);
				}
			}
		}
	}
	
	public function columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = array();
		}
	
		unset( $columns['title'], $columns['date'] );
	
		$columns["application_status"]      = __( "Status", 'noo' );
		$columns["candidate"]               = __( "Application", 'noo' );
		$columns["job"]                     = __( "Applied for Job", 'noo' );
		$columns["attachment"]              = __( "CV | Attachment", 'noo' );
		$columns["job_application_posted"]  = __( "Posted", 'noo' );
		$columns['job_application_actions'] = __( "Actions", 'noo' );
		return $columns;
	}
	
	public function custom_columns( $column ) {
		global $post;
	
		switch ( $column ) {
			case "application_status" :
				$status   = $post->post_status;
				$statuses = self::get_application_status();
				if ( isset( $statuses[ $status ] ) ) {
					$status = $statuses[ $status ];
				} else {
					$status = __( 'Inactive', 'noo' );
				}
				echo '<span class="job-application-status job-application-status-'.sanitize_html_class($status).'">';
				echo esc_html($status);
				echo '</span>';
				break;
			case "candidate" :
				echo '<a href="' . admin_url('post.php?post=' . $post->ID . '&action=edit') . '" class="tips candidate_name" data-tip="' . sprintf( __( 'ID: %d', 'noo' ), $post->ID ) . '"><strong>' . $post->post_title . '</strong></a>';
				if ( $email = get_post_meta( $post->ID, '_candidate_email', true ) ) {
					echo '<br/><a href="mailto:' . esc_attr( $email ) . '">' . esc_attr( $email ) . '</a>';
				}
				break;
			case 'job' :
				$job = get_post( $post->post_parent );
	
				if ( $job && $job->post_type === 'noo_job' ) {
					echo '<a href="' . get_permalink( $job->ID ) . '">' . $job->post_title . '</a>';
				} elseif ( $job = get_post_meta( $post->ID, '_job_applied_for', true ) ) {
					echo esc_html( $job );
				} else {
					echo '<span class="na">&ndash;</span>';
				}
				break;
			case 'attachment' :
				$attachment = jm_correct_application_attachment( $post->ID );
				if ( !empty( $attachment ) ) :
					if( is_string( $attachment) && strpos($attachment,'linkedin') ) : ?>
						<a href="<?php echo esc_url( $attachment ); ?>" target="_blank"><i class="dashicons dashicons-external"></i>&nbsp;<?php echo esc_html__('LinkedIn profile','noo'); ?></a><br/>
					<?php else :
						$attachment = !is_array( $attachment ) ? array( $attachment ) : $attachment;
						foreach ($attachment as $atm) : ?>
							<?php $file_name = basename( $atm ); ?>
							<a href="<?php echo esc_url( $atm ); ?>" target="blank"><i class="dashicons dashicons-paperclip"></i>&nbsp;<?php echo esc_html( $file_name ); ?></a>
							<br/>
						<?php endforeach;
					endif;
				endif;

				$resume = noo_get_post_meta( $post->ID, '_resume', '' );
				if ( !empty( $resume ) ) :
					$resume_link = add_query_arg('application_id', $post->ID, get_permalink($resume));
				?>
					<a href="<?php echo esc_url( $resume_link ); ?>" target="_blank"><i class="dashicons dashicons-clipboard"></i>&nbsp;<?php echo esc_html__('View Resume','noo'); ?></a>
				<?php endif;

				if( empty( $attachment ) && empty( $resume ) ) {
					echo ('<span class="na">&ndash;</span>');
				}
				break;
			case "job_application_posted" :
				echo '<span><strong>' . date_i18n( get_option('date_format'), strtotime( $post->post_date ) ) . '</strong><span><br>';
				$email = noo_get_post_meta( $post->ID, '_candidate_email', true );
				$user = get_user_by( 'email', $email );
				echo !$user ? __( 'by a Guest', 'noo' ) : sprintf( __( 'by %s', 'noo' ), '<a href="' . get_edit_user_link( $user->ID ) . '">' . $user->display_name . '</a>' );
				echo '</span>';
				break;
			case "job_application_actions" :
				echo '<div class="actions">';
				$admin_actions           = array();
				if ( $post->post_status !== 'trash' ) {
					// $admin_actions['view']   = array(
					// 	'action'  => 'view',
					// 	'name'    => __( 'View', 'noo' ),
					// 	'url'     => get_edit_post_link( $post->ID ),
					// 	'icon'	  => 'visibility',
					// );
					if ( current_user_can( 'edit_post', $post->ID ) ) {
						$admin_actions['edit'] = array(
							'action' => 'edit',
							'name'   => __( 'Edit', 'noo' ),
							'url'    => get_edit_post_link( $post->ID ),
							'icon'   => 'edit',
						);
					}
					if ( current_user_can( 'delete_post', $post->ID ) ) {
						$admin_actions['delete'] = array(
							'action'  => 'delete',
							'name'    => __( 'Delete', 'noo' ),
							'url'     => get_delete_post_link( $post->ID ),
							'icon'	  => 'trash',
						);
					}
				}
	
				$admin_actions = apply_filters( 'noo_application_manager_admin_actions', $admin_actions, $post );
	
				foreach ( $admin_actions as $action ) {
					printf( '<a class="button tips icon-%1$s" href="%2$s" data-tip="%3$s">%4$s</a>', $action['action'], esc_url( $action['url'] ), esc_attr( $action['name'] ), '<i class="dashicons dashicons-'.$action['icon'].'"></i>' );
				}
	
				echo '</div>';
	
				break;
		}
	}

	public function add_setting_application_tab( $tabs ) {
		$temp1 = array_slice($tabs, 0, 2);
		$temp2 = array_slice($tabs, 2);

		$application_tab = array( 'application'=>__('Job Application','noo') );
		return array_merge($temp1, $application_tab, $temp2);
	}
	
	public function setting_application(){
		$allow_register = Noo_Member::get_setting('allow_register', 'both');
		$custom_apply_link = jm_get_application_setting( 'custom_apply_link' );

		$application_attachment = jm_get_application_setting( 'application_attachment', 'enabled' );
		$allow_multiple_attachment = jm_get_application_setting( 'allow_multiple_attachment','');
		$application_resume = jm_get_application_setting( 'application_resume', 'enabled' );
		$require_attachment = jm_get_application_setting( 'require_attachment', 'yes');

		$use_apply_with_linkedin = jm_get_application_setting('use_apply_with_linkedin');
		$api_key = jm_get_application_setting('api_key');
		$api_secret = jm_get_application_setting('api_secret');
		$cover_letter_field = jm_get_application_setting('cover_letter_field');
		$apply_job_using_captcha = jm_get_application_setting('apply_job_using_captcha');
		?>
		<?php settings_fields('noo_job_linkedin'); ?>
		<h3><?php _e('General Options', 'noo'); ?></h3>
		<table class="form-table" cellpadding="0">
			<tbody>
				<tr>
					<th>
						<?php esc_html_e('Enable custom application link','noo')?>
					</th>
					<td>
						<fieldset>
							<label><input type="radio" <?php checked( $custom_apply_link, '' ); ?> name="noo_job_linkedin[custom_apply_link]" value=""><?php _e('No', 'noo'); ?></label><br/>
							<label><input type="radio" <?php checked( $custom_apply_link, 'admin' ); ?> name="noo_job_linkedin[custom_apply_link]" value="admin"><?php _e('Yes, on the dashboard', 'noo'); ?></label><br/>
							<label><input type="radio" <?php checked( $custom_apply_link, 'employer' ); ?> name="noo_job_linkedin[custom_apply_link]" value="employer"><?php _e('Yes, on both dashboard and frontend', 'noo'); ?></label><br/>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e('Apply with Resume ( for candidates )','noo')?>
					</th>
					<td>
						<fieldset>
							<label><input type="radio" <?php checked( $application_resume, 'enabled' ); ?> name="noo_job_linkedin[application_resume]" value="enabled"><?php _e('Enable', 'noo'); ?></label><br/>
							<label><input type="radio" <?php checked( $application_resume, 'disabled' ); ?> name="noo_job_linkedin[application_resume]" value="disabled"><?php _e('Disable', 'noo'); ?></label><br/>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e('File Attachment','noo')?>
					</th>
					<td>
						<fieldset>
							<label><input type="radio" <?php checked( $application_attachment, 'enabled' ); ?> name="noo_job_linkedin[application_attachment]" value="enabled"><?php _e('Enable', 'noo'); ?></label><br/>
							<label><input type="radio" <?php checked( $application_attachment, 'disabled' ); ?> name="noo_job_linkedin[application_attachment]" value="disabled"><?php _e('Disable', 'noo'); ?></label><br/>
						</fieldset>
					</td>
				</tr>
				<tr class="application_attachment-enabled">
					<th>
						<?php esc_html_e('Allow Multiple Attachment','noo')?>
					</th>
					<td>
						<input type="hidden" name="noo_job_linkedin[allow_multiple_attachment]" value="">
						<input class="allow_multiple_attachment" type="checkbox" <?php checked( $allow_multiple_attachment, 'yes' ); ?> name="noo_job_linkedin[allow_multiple_attachment]" value="yes">
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e('Require attachment or resume','noo')?>
					</th>
					<td>
						<input type="hidden" name="noo_job_linkedin[require_attachment]" value="">
						<input class="require_attachment" type="checkbox" <?php checked( $require_attachment, 'yes' ); ?> name="noo_job_linkedin[require_attachment]" value="yes">
						<small><?php _e('An application requires an attachment or a resume.', 'noo'); ?></small>
					</td>
				</tr>

				<script>
					jQuery(document).ready(function($) {
						$("input[name='noo_job_linkedin[application_attachment]']").click( function() {
							if( $(this).is(':checked') ) {
								if( $(this).val() === 'disabled' ) {
									$('.application_attachment-enabled').hide();
								} else {
									$('.application_attachment-enabled').show();
								}
							}
						} );
						$("input:checked[name='noo_job_linkedin[application_attachment]']").click();
					});
				</script>
				<!-- Using captcha register -->
					<tr>
						<th>
							<?php _e('Enable Captcha Job Application','noo')?>
						</th>
						<td>
							<input type="checkbox" name="noo_job_linkedin[apply_job_using_captcha]" value="yes" <?php checked($apply_job_using_captcha,'yes')?>/>
							<p><small><?php echo sprintf( __('By default, simple Captcha is enabled. To use Google re-Captcha, please go to <a href="%s">APIs setting</a> to add your re-Captcha key', 'noo'), jm_setting_page_url('3rd_api') . '#google-recaptcha-key' ); ?></small></p>
						</td>
					</tr>
				<!-- / Using captcha register -->

				<?php do_action( 'noo_setting_application_general_fields' ); ?>
			</tbody>
		</table>
		<br/><hr/><br/>
		<h3><?php echo __('Apply with LinkedIn','noo')?></h3>
		<table class="form-table" cellspacing="0">
			<tbody>
				<tr>
					<th>
						<?php esc_html_e('Allow apply with LinkedIn','noo')?>
					</th>
					<td>
						<input type="checkbox" name="noo_job_linkedin[use_apply_with_linkedin]" value="yes" <?php checked($use_apply_with_linkedin,'yes')?>>
						<p><small><a href="<?php echo jm_setting_page_url('3rd_api') . '#facebook-app-api'; ?>"><?php _e('Go to 3rd APIs setting', 'noo'); ?></a> <?php _e('to finish configuration with the <b>LinkedIn App API</b>', 'noo'); ?></small></p>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e('Cover letter field','noo')?>
					</th>
					<td>
						<select class="regular-text" name="noo_job_linkedin[cover_letter_field]">
							<option <?php selected($cover_letter_field,'required')?>  value="required"><?php esc_html_e('Required','noo')?></option>
							<option <?php selected($cover_letter_field,'optional')?> value="optional"><?php esc_html_e('Optional','noo')?></option>
							<option <?php selected($cover_letter_field,'hidden')?>  value="hidden"><?php esc_html_e('Hidden','noo')?></option>
						</select>
					</td>
				</tr>

				<?php do_action( 'noo_setting_application_linkedin_fields' ); ?>
			</tbody>
		</table>
		<?php 
	}

	public static function new_job_application( $job_id, $candidate_name, $candidate_email, $application_message, $meta = array() , $send_notification = true){
		$job = get_post( $job_id );
		if ( ! $job || $job->post_type !== 'noo_job' ) {
			return false;
		}
		
		$post_data = array(
			'post_title'     => wp_kses_post( $candidate_name ),
			'post_content'   => wp_kses_post( $application_message ),
			'post_status'    => 'pending',
			'post_type'      => 'noo_application',
			'comment_status' => 'closed',
			'post_author'    => get_current_user_id(),
			'post_parent'    => $job_id
		);
		$application_id = wp_insert_post( $post_data );
		if ( $application_id ) {
			update_post_meta( $application_id, '_job_applied_for', $job->post_title );
			update_post_meta( $application_id, '_candidate_email', $candidate_email );
			$_candidate_user_id = get_current_user_id();
			update_post_meta( $application_id, '_candidate_user_id', $_candidate_user_id );
		
			if ( $meta ) {
				foreach ( $meta as $key => $value ) {
					update_post_meta( $application_id, $key, $value );
				}
			}
			if ( $send_notification ) {
				//Send email
				self::send_notification(array(
					'job_id'=>$job_id,
					'application_id'=>$application_id,
					'candidate_email'=>$candidate_email,
					'candidate_name'=>$candidate_name,
					'application_message'=>$application_message,
				));
			}
			return $application_id;
		}
		return false;
	}
	
	public static function send_notification($args = ''){
		$defaults = array(
			'job_id'=>'',
			'application_id'=>'',
			'candidate_email'=>'',
			'candidate_name'=>'',
			'application_message'=>'',
		);
		
		$p = wp_parse_args($args,$defaults);
		extract($p);

		$emailed = noo_get_post_meta( $application_id, '_new_application_emailed', 0 );
		if( $emailed ) {
			return false;
		}
		
		$job = get_post($job_id);
		
		if ( !$job || $job->post_type !== 'noo_job' )
			return;
		
		
		$blogname = get_bloginfo( 'name' );

		//employer email
		if( jm_et_get_setting( 'employer_job_application_activated' ) ) {

			$notify_email  = get_post_meta($job_id,'_application_email',true);
			$employer = get_userdata( $job->post_author );

			if ( !empty( $notify_email ) && strstr( $notify_email, '@' ) && is_email( $notify_email ) ) {
				$to = $notify_email;
			} elseif ( $job->post_author ) {
				$to = $employer->user_email;
			} else {
				$to = '';
			}

			$attachment = noo_get_post_meta( $application_id, '_attachment', '' );
			$attach_files = array();
			if( !empty( $attachment ) ) {
				if( is_string( $attachment) && strpos($attachment,'linkedin') ) {
					$attachment = esc_url( $attachment );
				} else {
					$email_attachment = (bool) jm_et_get_setting('employer_job_application_attachment', 1);
					if( $email_attachment ) {
						$upload_dir = wp_upload_dir();
						$attachment = !is_array( $attachment ) ? array( $attachment ) : $attachment;
						foreach ($attachment as $atm) {
							if( strpos( $atm, $upload_dir['baseurl'] ) === 0 ) {
								$attach_files[] = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $atm);
							}
						}
					}
					// $attachment = Noo_Member::get_endpoint_url('manage-application');
				}
			}

			$resume = noo_get_post_meta( $application_id, '_resume', '' );
			$resume_url = '';
			if ( !empty( $resume ) ) {
				$resume_url = add_query_arg('application_id', $application_id, get_permalink($resume));
			}

			if ( $to && !empty( $employer ) ) {

				$array_replace = array(
					'[job_title]' => $job->post_title,
					'[job_url]' => get_permalink($job),
					'[job_company]' => $employer->display_name,
					'[candidate_name]' => $candidate_name,
					'[resume_url]' => $resume_url,
					'[application_manage_url]' => Noo_Member::get_endpoint_url('manage-application'),
					'[site_name]' => $blogname,
					'[site_url]' => esc_url(home_url('')),
				);

				$subject = jm_et_get_setting('employer_job_application_subject');
				$subject = str_replace(array_keys($array_replace), $array_replace, $subject);

				$message = jm_et_get_setting('employer_job_application_content');
				$message = str_replace(array_keys($array_replace), $array_replace, $message);

				$subject = jm_et_custom_field('application',$application_id, $subject);
				$message = jm_et_custom_field('application',$application_id, $message);

				noo_mail($to, $subject, $message, array(), 'noo_notify_job_apply_employer', $attach_files);
			}
		}

		//candidate email
		if( jm_et_get_setting( 'candidate_application_activated' ) ) {

			$to = $candidate_email;

			$array_replace = array(
				'[job_title]' => $job->post_title,
				'[job_url]' => get_permalink($job),
				'[job_company]' => $employer->display_name,
				'[candidate_name]' => $candidate_name,
				'[resume_url]' => $resume_url,
				'[application_manage_url]' => Noo_Member::get_endpoint_url('manage-job-applied'),
				'[site_name]' => $blogname,
				'[site_url]' => esc_url(home_url('')),
			);

			$subject = jm_et_get_setting('candidate_application_subject');
			$subject = str_replace(array_keys($array_replace), $array_replace, $subject);

			$message = jm_et_get_setting('candidate_application_content');
			$message = str_replace(array_keys($array_replace), $array_replace, $message);

			$subject = jm_et_custom_field('application',$application_id, $subject);
			$message = jm_et_custom_field('application',$application_id, $message);

			noo_mail($to, $subject, $message , array(), 'noo_notify_job_apply_candidate');
		}

		update_post_meta( $application_id, '_new_application_emailed', 1 );
		
		return;
	}
	
	public function register_post_type() {
		if ( post_type_exists( 'noo_application' ) )
			return;
		
		register_post_type( 
			'noo_application', 
			array( 
				'labels' => array( 
					'name' => __( 'Job Applications', 'noo' ), 
					'singular_name' => __( 'Job Application', 'noo' ), 
					'add_new' => __( 'Add New Application', 'noo' ), 
					'add_new_item' => __( 'Add Job Application', 'noo' ), 
					'edit' => __( 'Edit Job Application', 'noo' ), 
					'edit_item' => __( 'Edit Job Application', 'noo' ), 
					'new_item' => __( 'New Job Application', 'noo' ), 
					'view' => __( 'View Job Application', 'noo' ), 
					'view_item' => __( 'View Job Application', 'noo' ), 
					'search_items' => __( 'Search Job Application', 'noo' ), 
					'not_found' => __( 'No Job Applications found', 'noo' ), 
					'not_found_in_trash' => __( 'No Job Applications found in Trash', 'noo' ), 
					'parent' => __( 'Parent Job Application', 'noo' ) 
				), 
				// 'capabilities' => array(
				// 	'publish_posts'       => 'manage_noo_job',
				// 	'edit_posts'          => 'manage_noo_job',
				// 	'edit_others_posts'   => 'manage_noo_job',
				// 	'delete_posts'        => 'manage_noo_job',
				// 	'delete_others_posts' => 'manage_noo_job',
				// 	'read_private_posts'  => 'manage_noo_job',
				// 	'edit_post'           => 'manage_noo_job',
				// 	'delete_post'         => 'manage_noo_job',
				// 	'read_post'           => 'manage_noo_job',
				// 	'create_posts'        => false // No one should have this cap by default
				// ),
				'description' 		  => __( 'This is the place where you can edit and view job applications.', 'noo' ), 
				'menu_icon' 		  => 'dashicons-pressthis',
				'public'              => false,
				'show_ui'             => true,
				// 'capability_type'     => 'noo_job',
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => false, // array( 'title', 'editor' ),
				'has_archive'         => false,
				'show_in_nav_menus'   => false,
				'delete_with_user'    => true,
			) 
		);
		
		register_post_status( 'rejected', array(
			'label'                     => __( 'Rejected', 'noo' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>', 'noo' ),
		) );
		
		register_post_status( 'inactive', array(
			'label'                     => __( 'Inactive', 'noo' ),
			'public'                    => false,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'noo' ),
		) );
	}
	
	public function extend_application_status(){
		global $post, $post_type;
		if($post_type === 'noo_application'){
			$html = $selected_label = '';
			foreach ((array) self::get_application_status() as $status=>$label){
				$seleced = selected($post->post_status,esc_attr($status),false);
				if($seleced)
					$selected_label = $label;
				$html .= "<option ".$seleced." value='".esc_attr($status)."'>".$label."</option>";
			}
			?>
				<script type="text/javascript">
					jQuery( document ).ready( function($) {
						<?php if ( ! empty( $selected_label ) ) : ?>
							jQuery( '#post-status-display' ).html( '<?php echo esc_js( $selected_label ); ?>' );
						<?php endif; ?>
						var select = jQuery( '#post-status-select' ).find( 'select' );
						jQuery( select ).html( "<?php echo ($html); ?>" );
					} );
				</script>
				<?php
			}
		}
	
	public static function get_application_status(){
		return apply_filters('noo_application_status', array(
			'publish'         => __( 'Approved', 'noo' ),
			'rejected'        => __( 'Rejected', 'noo' ),
			'pending'         => __( 'Pending', 'noo' ),
			'inactive'        => __( 'Inactive', 'noo' ),
		));
	}

	public static function can_edit_application( $user_id = 0, $application_id = 0 ) {
		if( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if( empty( $user_id ) || empty( $application_id ) ) {
			return false;
		}

		if ( get_post_type( $application_id ) !== 'noo_application' ) {
			return false;
		}

		if ( get_post_status( $application_id ) !== 'pending' ) {
			return false;
		}

		if( !Noo_Member::is_employer( $user_id ) ) {
			return false;
		}

		$job_id = get_post_field( 'post_parent', $application_id );

		if( is_wp_error( $job_id ) ) {
			return false;
		}
		$employer_id = get_post_field( 'post_author', $job_id );

		return ( absint($employer_id) === absint($user_id) );
	}

	public static function can_trash_application( $user_id = 0, $application_id = 0 ) {
		if( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if( empty( $user_id ) || empty( $application_id ) ) {
			return false;
		}

		if ( get_post_type( $application_id ) !== 'noo_application' ) {
			return false;
		}

		if( Noo_Member::is_employer( $user_id ) ) {
			$job_id = get_post_field( 'post_parent', $application_id );

			if( is_wp_error( $job_id ) ) {
				return false;
			}
			$employer_id = get_post_field( 'post_author', $job_id );

			return ( absint($employer_id) === absint($user_id) );
		} elseif( Noo_Member::is_candidate( $user_id ) ) {
			$status = get_post_field( 'post_status', $application_id );
			$user = get_userdata( $user_id );
			$email = noo_get_post_meta( $application_id, '_candidate_email' );

			return ( $status === 'pending' && $user && $email == $user->user_email );
		}

		return false;
	}

	public static function can_delete_application( $user_id = 0, $application_id = 0 ) {
		if( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		if( empty( $user_id ) || empty( $application_id ) ) {
			return false;
		}

		if ( get_post_type( $application_id ) !== 'noo_application' ) {
			return false;
		}

		if( !Noo_Member::is_candidate( $user_id ) ) {
			return false;
		}

		$status = get_post_field( 'post_status', $application_id );
		$user = get_userdata( $user_id );
		$email = noo_get_post_meta( $application_id, '_candidate_email' );

		return ( $status === 'inactive' && $user && $email == $user->user_email );
	}

	public static function has_applied( $candidate_id = 0, $job_id = 0 ) {
		if( empty( $candidate_id ) ) {
			$candidate_id = get_current_user_id();
		}
		if( empty( $candidate_id ) || empty( $job_id ) ) {
			return false;
		}

		$candidate = get_userdata( $candidate_id );
		$application_args = array(
			'post_type'=>'noo_application',
			'posts_per_page' => -1,
			'post_status'=>array('publish','pending','rejected'),
			'post_parent'=> absint( $job_id ),
			'meta_query'=>array(
				array(
					'key' => '_candidate_email',
					'value' => $candidate->user_email,
				),
			)
		);
		$application = new WP_Query($application_args);
		if ( $application->post_count ) {
			return true;
		}

		return false;
	}
}
new Noo_Application();
endif;