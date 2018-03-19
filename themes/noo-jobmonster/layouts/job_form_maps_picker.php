<?php wp_enqueue_script( 'location-picker' ); ?>

<div id="modalLocationPicker" class="modal fade">

	<input type="hidden" name="job_map_lat" id="noo-map-lat" value="51.165691"/>
	<input type="hidden" name="job_map_lon" id="noo-map-lon" value="10.451526"/>

	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title">
					<i class="fa fa-map-marker" aria-hidden="true"></i> <?php _e( 'Pick an address', 'noo' ); ?>
				</h4>
			</div>
			<div class="modal-body">
				<div class="form-horizontal">

					<div class="form-group row">
						<label for="noo-map-address"
						       class="col-sm-4 control-label"><?php _e( 'Location Name', 'noo' ) ?></label>
						<div class="col-sm-8">
							<input type="text" name="location_name" class="form-control" id="noo-map-address"
							       value="" autocomplete="off"/>
							<button class="btn btn-small btn-default add-new-location-submit" type="button">
								<?php _e( 'Add', 'noo' ) ?>
							</button>
						</div>
					</div>

					<div id="noo-map-picker" style="width: 100%; height: 400px;"></div>
					<div class="clearfix"></div>

				</div>
			</div>

		</div>
	</div>

</div>