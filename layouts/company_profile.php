<?php 
$company_id = jm_get_employer_company();
$company = ( !empty( $company_id ) ? get_post($company_id)  : '' );
$company_name = ( !empty( $company_id ) ? $company->post_title  : '' );
$content = !empty( $company_id ) ? $company->post_content : '';
?>

<div class="company-profile-form">
	<div class="form-group row required-field">
		<label for="company_name" class="col-sm-3 control-label"><?php _e('Company Name','noo')?></label>
		<div class="col-sm-9">
	    	<input type="text" class="form-control" autofocus id="company_name" required value="<?php echo $company_name;?>"  name="company_name" placeholder="<?php echo esc_attr__('Enter your company name','noo')?>">
	    </div>
	</div>
	<div class="form-group row">
	    <label for="company_desc" class="col-sm-3 control-label"><?php _e('Company Description','noo')?></label>
	    <div class="col-sm-9">
			<?php
			noo_wp_editor($content, 'company_desc');
			?>
	    </div>
	</div>
	<?php
		$fields = jm_get_company_custom_fields();
		if( !empty( $fields ) ) {
			foreach ($fields as $field) {
				jm_company_render_form_field( $field, $company_id );
			}
		}
	?>
	<?php $socials = jm_get_company_socials();
		if(!empty($socials)) {
			foreach ($socials as $social) {
				jm_company_render_social_field( $social, $company_id );
			}
		}
	?>
</div>