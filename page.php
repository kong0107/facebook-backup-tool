<?php
	require_once 'fb.php';
	require_once 'db.php';
	
	$page_id = is_numeric($_GET['page_id']) ? $_GET['page_id'] : '';

	function shrinkArr($arr) {
		foreach($arr as $k => $v) {
			if(is_array($v)) {
				if($k == 'attachment') {
					unset($v['url']);
					//continue;
				}
				$v = shrinkArr($v);
				if(!count($v)) unset($arr[$k]);
				else $arr[$k] = $v;
				continue;
			}
			if(is_null($v) || $v === '') unset($arr[$k]);
			else if(is_object($v) && get_class($v))
				$arr[$k] = $v->format(DateTime::ISO8601);
		}
		return $arr;
	}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Facebook Page Timeline Backup</title>
	<style>
		dt { float: left; clear: left; }
		dd {
			clear: right;
			margin: 0 0 1em 16em;
			white-space: pre-wrap;
			font-family: monospace;
		}
	</style>
</head>
<body>
<h1>Facebook Page Timeline Backup</h1>
	<a href="<?=getFBLoginUrl()?>">Login</a>
	<form action="<?=$_SERVER['PHP_SELF']?>">
		Page ID:
		<input name="page_id" placeholder="a numeric string" value="<?=$page_id?>">
		<input type="submit">
	</form>
<?php
	if($page_id) {
		$info = $fb->get("/$page_id?metadata=1")->getDecodedBody();
		printf('<h2>%s</h2>', $info['name']);
		
		/**
		 * Load every permitted field.
		 */
		/*
		$fields = $info['metadata']['fields'];
		$excluded_field_names = array(
			'ad_campaign', 'promotion_eligible', 'owner_business'
		);
		$permitted_fields = array();
		$excluded_fields = array();
		foreach($fields as $field_info) {
			if(in_array($field_info['name'], $excluded_field_names))
				$excluded_fields[$field_info['name']] = $field_info;
			else $permitted_fields[$field_info['name']] = $field_info;
		}
		
		$requestUrl = "/$page_id?fields=" . implode(',', array_keys($permitted_fields));
		$info = $fb->get($requestUrl)->getDecodedBody();
		
		echo '<dl>';
		foreach($info as $field => $data) {
			$field_info = $permitted_fields[$field];
			echo "<dt title=\"{$field_info['description']}\">$field</dt>";
			echo '<dd>';
			var_dump($data);
			echo '</dd>';
		}
		echo '</dl>';
		upsert('page', $info);*/
		
		/**
		 * Save all milestones.
		 */
		/*for($edge = $fb->get("/$page_id/milestones")->getGraphEdge();
			$edge; $edge = $fb->next($edge)
		) {
			foreach($edge->asArray() as $ms) {
				unset($ms['from']);
				upsert("page_{$page_id}_milestone", $ms);
			}
		}*/
		
		
		$requestUrl = empty($_GET['request'])
			? "/$page_id/posts?fields=id,message,message_tags,story,story_tags,with_tags,created_time,updated_time,application,parent_id,place,link,object_id,name,description"
			: urldecode($_GET['request'])
		;
		echo "Requesting <code>$requestUrl</code><br>";
		$posts = $fb->get($requestUrl)->getDecodedBody();
		
		foreach($posts['data'] as $post) {
			upsert("page_{$page_id}_post", $post);
			
			/// what about import comments, too?
			//$cru = "/{$post['id']}/comments?fields=id,message,message_tags,attachment,created_time,comment_count,comments{id,from,message,message_tags,attachment,created_time}";

		}
		$next = $posts['paging']['next'];
		if($next) {
			$requestNext = $_SERVER['PHP_SELF'] 
				. '?page_id=' . $page_id 
				. '&request=' . urlencode(substr($next, 31))
			;
			echo "<a href=\"$requestNext\">Request next page</a><br>";
			echo "<script>setTimeout(function(){location.href = '$requestNext';}, 5000);</script>";
		}
		
		echo '<pre>';
		print_r($posts);
		echo '</pre>';
	}
?>
</body>
</html>
