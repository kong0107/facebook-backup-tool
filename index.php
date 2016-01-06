<?php
	require_once 'fb.inc.php';
?>
<!DOCTYPE HTML>
<html ng-app="myApp">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?=$config['site_name']?></title>
	<script src="//code.jquery.com/jquery-2.1.4.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular-route.min.js"></script>
	<script src="//connect.facebook.net/zh_TW/sdk.js" id="facebook-jssdk"></script>
	<script src="js/fbsdk-config.js"></script>
	<script src="js/fbsdk-extend.js"></script>
	<script>
		FB.init(FBConfig);

		angular.module("myApp", ["ngRoute"])
		.config(function($routeProvider) {
			$routeProvider
				.when("/:path", {
					templateUrl: function(params){
						var path = params.path;
						if(["help", "about", "terms", "privacy"].indexOf(path) == -1)
							path = "index";
						return "templates/" + path + ".html";
					},
					controller: function($scope, $routeParams) {
						$scope.config = {
							enable_photo_download: <?=json_encode($config['enable_photo_download'])?>,
							enable_comment_crawl: <?=json_encode($config['enable_comment_crawl'])?>
						};
					}
				})
				.otherwise({
					redirectTo: "/index"
				})
			;
		})
		.controller("header", function($scope) {
			FB.getLoginStatus(function(r) {
				FB.apiwt("/me?fields=id,name,link,picture{url}", function(res) {
					$scope.model = {userInfo: res};
					$scope.$apply();
				})
			});
		});
	</script>
	<link rel="stylesheet" href="styles/std.css">
	<link rel="stylesheet" href="styles/main.css">
</head>
<body>
<header ng-controller="header" ng-include="'templates/header.html'"></header>
<section ng-view style="max-width: 720px;">
<!-- -->
<!-- -->
</section>
<footer ng-include="'templates/footer.html'"></footer>
</body>
</html>
