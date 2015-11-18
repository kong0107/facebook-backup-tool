<?php
	require 'core.php';
	unset($_SESSION['facebook_access_token']);
    header('Location: ' . $config['site_root'] . '/login.php');
?>
