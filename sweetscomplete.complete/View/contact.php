<?php
$message = '';
if (isset($_FILES['request'])) {

	// whitelist of allowed file extensions
	$allowed = array('jpg', 'png', 'gif', 'doc', 'odt', 'docx', 'pdf', 'xls');
	// sanitize filename
	$fn = strip_tags(basename($_FILES['request']['name']));
	$message = "<b style='color: red;'>Unable to upload file " . $fn . "</b>\n";

	// check extension against whitelist
	$fileObj = new SplFileInfo($fn);
	if (in_array($fileObj->getExtension(), $allowed, TRUE)) {
		// make sure there were no errors
		if ($_FILES['request']['error'] == UPLOAD_ERR_OK ) {
			// make sure file uploaded by upload process
			if ( is_uploaded_file ($_FILES['request']['tmp_name'] ) ) {
				// *** Build new path + filename with safety measures in place
				$copyfile = realpath(__DIR__ . '/../uploads') . '/' . $fn;
				// Copy file
				// NOTE: disabled in this demo
//				if (move_uploaded_file($_FILES['request']['tmp_name'], $copyfile) ) {
					$message = "<b style='color: green;'>Successfully uploaded file $fn\n";
//				}
			}
		}
	} else {
		$message = "<b style='color: red;'>Only these file types are supported: " . implode(' | ', $allowed) . "</b>\n";;
	}
}
?>
<div class="content">
	<br/>
	<div class="product-list">

		<h2>Sign Up</h2>
		<br/>

		<b>Please use this form to contact us.</b><br/><br/>
		<form name="contact" method="post" enctype="multipart/form-data">
			<p>
				<label>Name: </label>
				<input type="text" name="name"/>
			<p>
			<p>
				<label>Email Address: </label>
				<input type="text" name="email"/>
			<p>
			<p>
				<label>Comments / Questions: </label>
				<textarea name="comments">I love your products!</textarea>
			<p>
			<p>
				<label>Special Order: </label>
				<input type="file" name="request" />
			<p>
			<p>
				<input type="reset" name="clear" value="Clear" class="button"/>
				<input type="submit" name="submit" value="Submit" class="button marL10"/>
			<p>
		</form>
		<p>
		<?php echo $message; ?>
	</div><!-- product-list -->

<br class="clear-all"/>
</div><!-- content -->

</div><!-- maincontent -->
