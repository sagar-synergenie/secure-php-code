<?php
// avoiding misconfiguration: redirects in case somebody browses to this folder
require_once __DIR__ . '/../../../../Model/Init.php';
header('Location: ' . HOME_URL);
exit;