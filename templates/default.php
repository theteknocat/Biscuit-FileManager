<!DOCTYPE html>
<html lang="<?php echo $lang ?>">
	<head>
	<?php print $header_tags; ?>

	<?php print $header_includes; ?>

	</head>
	<body id="<?php echo $body_id ?>" class="locale-<?php echo $locale ?>">
		<?php
		if (!empty($user_messages)) {
			?><div id="biscuit-user-messages"><a id="user-messages-close" href="#close">Close</a><?php print $user_messages; ?></div>
			<script type="text/javascript" charset="utf-8">
				$(document).ready(function() {
					var close_timer = setTimeout("$('#biscuit-user-messages').fadeOut('fast',function() { $(this).remove(); });",12000);
					$('#user-messages-close').click(function() {
						clearTimeout(close_timer);
						$('#biscuit-user-messages').fadeOut('fast',function() { $(this).remove(); });
						return false;
					});
				});
			</script><?php
		}
		print $page_content; ?>
	</body>
	<?php
		print $footer;
	?>
</html>
