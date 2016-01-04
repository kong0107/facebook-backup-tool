<?php
	require_once 'config.inc.php';
?>
<!DOCTYPE HTML>
<html ng-app="myApp">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Browse what to export</title>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script>
		angular.module("myApp", []).controller("main", function($scope) {
		});
	</script>
	<link rel="stylesheet" href="styles/std.css">
	<link rel="stylesheet" href="styles/main.css">
</head>
<body ng-controller="main">
<header ng-include="'templates/header.html'"></header>
<section>
<!-- -->
<h1>Help</h1>
to be edited...
<!-- -->
</section>
<footer ng-include="'templates/footer.html'"></footer>
</body>
</html>
