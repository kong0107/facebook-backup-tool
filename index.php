<?php
	require_once 'fb.inc.php';
?>
<!DOCTYPE HTML>
<html ng-app="myApp">
<head>
	<meta charset="utf-8">
	<title>Login Facebook</title>
	<script src="//code.jquery.com/jquery-2.1.4.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script src="//connect.facebook.net/en_US/sdk.js"></script>
	<script src="js/fbsdk-config.js"></script>
	<script>
	</script>
	<script>
		angular.module("myApp", []).controller("main", function($scope) {
			window.fbAsyncInit = function() {
				FB.init(FBConfig);
				FB.getLoginStatus(function(r) {
					FB.api("/me", function(r) {
						window.document.getElementById("xd").textContent = JSON.stringify(r);
					});
				});
			};
			$.getScript("js/fbsdk-extend.js");
		});
	</script>
	<style></style>
</head>
<body ng-controller="main">
	<h1>Template</h1>
	<?php
		if($_SESSION['facebook_access_token'])
			printf('<a href="%s">Logout</a>', getFBLogoutUrl());
		else printf('<a href="%s">Login</a>', getFBLoginUrl());
	?>
	<pre id="xd"></pre>
</body>
</html>
