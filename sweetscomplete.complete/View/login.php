<?php
ini_set('display_errors', 1);
// initialize variables
$error = array();
$valid = TRUE;

// get Members table
require './Model/Members.php';
$memberTable = new Members();

// check to see if login
if (isset($_POST['data'])) {

	// accepting incoming data, providing defaults
	$email 		= (isset($_POST['data']['email'])) 	  ? $_POST['data']['email'] 	: '';
	$password 	= (isset($_POST['data']['password'])) ? $_POST['data']['password']	: '';
	$phrase		= (isset($_POST['data']['phrase']))   ? $_POST['data']['phrase'] 	: '';
	$hash		= (isset($_POST['data']['hash']))  	  ? $_POST['data']['hash'] 		: '';
	
	// filtering incoming data
	$email 	  = filter_var($email, FILTER_SANITIZE_EMAIL);
	$password = strip_tags(trim($password));
	$phrase   = strip_tags(trim($phrase));

	// make sure values exist
	if ($email && $password && $phrase && $hash) {
		// check captcha
		if (!(isset($_SESSION['phrase']) && $_SESSION['phrase'] == $phrase)) {
			$valid = FALSE;
			$error[] = 'Please re-enter CAPTCHA';
		}
		// check hash
		if (!(isset($_SESSION['hash']) && $_SESSION['hash'] == $hash)) {
			$valid = FALSE;
			$error[] = 'Invalid form submission';
		}
		$result = $memberTable->loginByName($email, $password);
		if ($result) {
			// store user info in session
			$_SESSION['user'] = $result;
		} else {
			$valid = FALSE;
			$error[] = 'Unable to login';
		}
	} else {
		$valid = FALSE;
		$error[] = 'Missing information';
	}
	if ($valid) {
		// redirect back home
		$_SESSION['login'] = TRUE;
		header('Location: ?page=home');
		exit;
	} else {
		$_SESSION['login'] = FALSE;
	}
}

// generate CAPTCHA
require 'captcha.php';
$captcha = new MakeCaptcha();
$captcha->purgeCaptchaFiles();
// record any errors
if ($captcha->generateCaptcha()) {
	$_SESSION['phrase'] = $captcha->captchaPhrase;
} else {
	$error[] = $captcha->error;
}

// generate MD5 hash
$newHash = md5(date('YmdHis') . session_id());
$_SESSION['hash'] = $newHash;

?>
<div class="content">
	<br/>
	<div class="product-list">
		
		<h2>Login</h2>
		<br/>
		
		<b>Please enter your information.</b><br/><br/>
		<form action="?page=login" method="POST">
			<p>
				<label>Email: </label>
				<!-- // *** using the HTML5 "email" type instead of "text" -->
				<input type="email" name="data[email]" />
			<p>
			<p>
				<label>Password: </label>
				<!-- // *** using the "password" type instead of "text" -->
				<input type="password" name="data[password]" />
			<p>
			<!-- // CAPTCHA + 1 time hash -->
			<p>
				<label>Enter Text: </label>
				<input type="text" name="data[phrase]" />
				<input type="hidden" name="data[hash]" value="<?php echo $newHash; ?>" />
			<p>
			<p>
				<label>&nbsp;</label>
				<img src="<?php echo $captcha->captchaURL; ?>" />
			<p>
			<p>
				<input type="reset" name="data[clear]" value="Clear" class="button"/>
				<input type="submit" name="data[submit]" value="Submit" class="button marL10"/>
			<p>
			<p>
				<label>&nbsp;</label>
				<?php if (!$valid) : 	?>
					<br /><b style="color:red;">
					<?php echo implode('</b><br /><b style="color:red;">', $error);	?>
					</b>
				<?php endif; 			?>
			<p>
		</form>
	</div><!-- product-list -->
</div>
