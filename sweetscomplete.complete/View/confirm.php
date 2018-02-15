<?php
// initialize variables
$id     = 0;
$code 	= '';
$result = FALSE;

// get Members table
require './Model/Members.php';
$memberTable = new Members();

// first time confirm is called
if (isset($_GET['id']) && isset($_GET['code'])) {
	$id 	= (int) $_GET['id'];
	$code 	= strip_tags($_GET['code']);
	$result = $memberTable->getDetailsById($id);
}

// after user confirms
if (isset($_POST['confirm'])) {
	$id 	= (isset($_POST['id'])) 	  ? (int) $_POST['id'] : 0;
	$email 	= (isset($_POST['email'])) 	  ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
	$code 	= (isset($_POST['code'])) 	  ? strip_tags($_POST['code']) : '';
	$pwd 	= (isset($_POST['password'])) ? hash('ripemd256', $_POST['password']) : '';
	$result = $memberTable->getDetailsById($id);
	if ($result
		&& $result['email'] === $email
		&& $result['password'] === $pwd
		&& $result['confirm_code'] === $code) {
		if ($memberTable->finishConfirm($id)) {
			$_SESSION['user']  = $result;
			$_SESSION['login'] = TRUE;
			header('Location: ?page=home');
			exit;
		} else {
			$result = FALSE;
		}
	}
}

?>
<div class="content">

<br/>
<div class="product-list">
	<h2>Confirm Membership</h2>
	<?php if ($result) : ?>
	Welcome to the club <?php echo $result['name']; ?>!
	<br />Please confirm your membership:
	<form action="?page=confirm" method="POST">
	<p>
		<label>Email</label>
		<input type="email" name="email" maxlength=255 />
	<p>
	<p>
		<label>Password</label>
		<input type="password" name="password" />
	<p>
	<p>
		<label>Confirmation Code</label>
		<input type="text" name="code" value="<?php echo $code; ?>" />
	<p>
	<p>
		<input type="submit" name="confirm" value="Confirm" class="button marL10"/>
	<p>
	<input type="hidden" name="id" value="<?php echo (int) $id; ?>" />
	</form>
	<?php else : ?>
	Sorry!!!
	<br />Unable to confirm your membership just yet.
	<br />Check in your email inbox for a confirmation message.
	<?php endif; ?>
</div>
<br class="clear-all"/>
</div><!-- content -->
