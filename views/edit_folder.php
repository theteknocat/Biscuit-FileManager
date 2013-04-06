<h2><?php echo __('New Folder'); ?></h2>
<?php print Form::header(null, 'new-folder-form'); ?>

	<?php print Form::text('folder_name', 'folder_name', 'Name', '', true, $FileManager->field_is_valid('folder')); ?>

<?php print Form::footer($FileManager,null,false,__('Create')); ?>
<script type="text/javascript">
	$(document).ready(function() {
		Biscuit.Crumbs.Forms.AddValidation('new-folder-form');
	});
</script>
