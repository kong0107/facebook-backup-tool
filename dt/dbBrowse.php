<?php
	$localhost = array('localhost', '127.0.0.1', '::1');
	if(!in_array($_SERVER['HTTP_HOST'], $localhost)
		|| !in_array($_SERVER['SERVER_NAME'], $localhost)
		|| !in_array($_SERVER['SERVER_ADDR'], $localhost)
		|| !in_array($_SERVER['REMOTE_ADDR'], $localhost)
	) {
		//header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
		exit("Use localhost to link to this page.");
	}
	
	require_once '../db.inc.php';

	$dbs = array();
	foreach($dbCon->listDBs()['databases'] as $dbArr)
		$dbs[] = $dbArr['name'];

	$cols = array();
	if($_GET['db']) {
		$db = $dbCon->selectDB($_GET['db']);
		foreach($db->listCollections() as $colObj)
			$cols[] = $colObj->getName();

		if($_GET['col']) {
			$col = $db->selectCollection($_GET['col']);
			$skip = (int) $_GET['skip'];
			if($skip < 0 || $skip > $col->count()) $skip = 0;
			$limit = (int) $_GET['limit'];
			if($limit <= 0) $limit = 10;

			$docs = iterator_to_array($col->find()->skip($skip)->limit($limit));
			$fields = array();
			foreach($docs as $doc) {
				$fields = array_unique(array_merge($fields, array_keys($doc)));
			}
		}
	}
?>
<!DOCTYPE HTML>
<html>
	<head>
		<meta charset="utf-8">
		<title>MongoDB Browser</title>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.4.7/angular.min.js"></script>
		<script>
			angular.module("myApp", []).controller("main", function($scope) {
				$scope.dbs = <?=json_encode($dbs)?>;
				$scope.cols = <?=json_encode($cols)?>;
				$scope.db = "<?=$_GET['db']?>";

				<?php if($col) { ?>
					$scope.col = "<?=$_GET['col']?>";
					$scope.col_count = <?=$col->count()?>;
					$scope.skip = <?=$skip?>;
					$scope.limit = <?=$limit?>;
					$scope.fields = <?=json_encode($fields)?>;
					$scope.docs = <?=json_encode($docs)?>;
				<?php } ?>
			});
		</script>
		<style>
			input[type=number] { width: 3em; }
			td, article { white-space: pre-wrap; font-family: monospace; }
			div { margin-bottom: 1em; }
			#pagination { position: fixed; top: 0; right: 0; background-color: #ccc; padding: 0.5em; text-align: center; line-height: 150%; }
			label { display: inline-block; border: 1px solid #ccc; margin: .1em .25em; padding: .15em; }
		</style>
	</head>
	<body ng-app="myApp" ng-controller="main">
		<h1>MongoDB Browser</h1>
		<form method="get" onChange="this.submit();">
			<div>
				Select database:<br>
				<label ng-repeat="db in dbs">
					<input name="db" type="radio" value="{{db}}" 
						ng-checked="db=='<?=$_GET['db']?>'" onclick="this.form.submit();"
					>{{db}}
				</label>
			</div>
			<div ng-if="db">
				Select collection:<br>
				<label ng-repeat="col in cols">
					<input name="col" type="radio" value="{{col}}" ng-checked="col=='<?=$_GET['col']?>'">{{col}}
				</label>
			</div>
			<div id="pagination" ng-if="col">
				Skip
				<input name="skip" ng-model="skip" type="number" min="0" ng-attr-max="{{col_count}}">
				and show
				<input name="limit" ng-model="limit" type="number" min="0">
				documents.
				<br>
				<button ng-disabled="skip==0" onclick="var f=this.form;f.skip.value=parseInt(f.skip.value)-parseInt(f.limit.value);f.submit();">Previous page</button>
				<button ng-disabled="skip+limit>=col_count" onclick="var f=this.form;f.skip.value=parseInt(f.skip.value)+parseInt(f.limit.value);f.submit();">Next page</button>
			</div>
		</form>
		<section ng-if="col">
			<h2>Documents</h2>
			There is/are {{col_count | number}} document(s) in the collection.
			<article ng-repeat="doc in docs">{{doc|json}}</article>
			<table border="1">
				<thead>
					<tr><th ng-repeat="field in fields">{{field}}</th></tr>
				</thead>
				<tbody>
					<tr ng-repeat="doc in docs">
						<td ng-repeat="field in fields">{{doc[field]|json}}</td>
					</tr>
				</tbody>
			</table>
		</section>
	</body>
</html>
