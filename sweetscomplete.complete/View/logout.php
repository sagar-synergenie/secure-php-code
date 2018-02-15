<?php
// unset all $_SESSION data
$_SESSION = array();
// expire the session cookie
if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(	session_name(), '',
				time() - 3600,
				$params["path"],
				$params["domain"],
				$params["secure"],
				$params["httponly"]
	);
}
// destroy session
session_destroy();
// go home
header('Location: ?page=home');
exit;
