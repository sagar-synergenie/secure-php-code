<?php
// PHP and MySQL Project
// this file is the initial point of entry for the website

// start output buffering and session
ob_start();
session_start();
session_regenerate_id();
date_default_timezone_set('Europe/London');
ini_set('display_errors', 1);

// load init file which defines constants
require './Model/Init.php';

// load ACL class
require './Model/Acl.php';
$acl     = new Acl();

// load View class
require './View/View.php';
$view = new View();

// check to see if logged in
if (isset($_SESSION['login']) && $_SESSION['login']) {
	$userId = $_SESSION['user']['user_id'];
	$name 	= $_SESSION['user']['name'];
} else {
	$userId = 0;
	$name 	= 'Guest';
}

// get page
if (isset($_GET['page'])) {
	// remove tags, trim and make lowercase
	$page = strtolower(trim($_GET['page']));
	// remove non-alpha characters
	$page = preg_replace('/[^a-z]/', '', $page);
	// validate against $view->menus
	if (!(isset($view->menus[$page]) || $acl->hasRightsToPage($page))) {
		$page = 'home';
	}
} else {
	$page = 'home';
}

?>
<!DOCTYPE HTML>
<html>
<head>
<!-- // *** Need to escape user supplied info (i.e. $page)  -->
<title><?php echo $view->companyName; ?> | <?php echo htmlspecialchars(ucfirst($page)); ?></title>
<!-- // *** Set character set to utf-8 -->
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html">
<meta name ="description" content ="Sweets Complete">
<meta name="keywords" content="">
<link rel="stylesheet" href="css/main.css" type="text/css">
<link rel="shortcut icon" href="images/favicon.ico?v=2" type="image/x-icon" />
</head>
<body>
<div id="wrapper">
	<div id="maincontent">

	<div id="header">
		<div id="logo" class="left">
			<a href="index.php"><img src="images/logo.png" alt="SweetsComplete.Com"/></a>
		</div>
		<div class="right marT10">
			<b>
			<?php
			$count 	 = 0;
			$topMenu = '';
			// NOTE: admin menu is now added as an option to the View/View::$top array
			// get menus
			foreach ($view->menus['top'] as $key => $value) {
				if ($acl->hasRightsToPage($key)) {
					if ($key == $page) {
						$active = 'class="active" ';
					} else {
						$active = '';
					}
					$topMenu .='<a href="?page=' . $key . '" ' . $active . '>' . $value . '</a> |';
				}
			}
			echo substr($topMenu, 0, -1);
			?>
			</b>
			<br />
			Welcome <?php echo $name; ?>
		</div>
		<ul class="topmenu">
		<?php
			foreach ($view->menus[$page] as $key => $value) {
				echo '<li><a href="?page=' . $key . '">' . $value . '</a></li>' . PHP_EOL;
			}
		?>
		</ul>
		<br>
		<div class="banner"><p></p></div>
		<br class="clear"/>
	</div> <!-- header -->

	<!-- // *** $page has been filtered, validated and sanitized -->
	<?php include "./View/$page.php"; ?>

	</div><!-- maincontent -->

	<div id="footer">
		<div class="footer">
			Copyright &copy; <?php echo date('Y'); ?> sweetscomplete.com. All rights reserved. <br/>
		<?php
			$footerMenu = '';
			foreach ($view->menus[$page] as $key => $value) {
				$footerMenu .= '<a href="?page=' . $key . '">' . $value . '</a> | ';
			}
			echo substr($footerMenu, 0, -2);
		?>
		<br />
			<span class="contact">Tel: +44-1234567890&nbsp;
			Fax: +44-1234567891&nbsp;
			<!-- // *** should "obscure" email address to avoid harvesting, which leads to SPAM! -->
			Email:sales&#64;sweetscomplete.com</span>
		</div>
	</div><!-- footer -->

</div><!-- wrapper -->

</body>
</html>

