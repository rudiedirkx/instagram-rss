<?php

use rdx\http\HTTP;

require 'vendor/autoload.php';
require 'inc.functions.php';

header('Content-type: text/xml; charset=utf-8');

$username = (string) @$_GET['user'] ?: 'instagram';

// 1. Overview
$url = 'https://www.instagram.com/' . urlencode($username) . '/';
$request = HTTP::create($url);
$response = $request->request();

// 2. Extract JSON
if ( !preg_match('#>\s*window._sharedData\s*=\s*(\{.+?)</script>#', $response->body, $match) ) {
	header('Content-type: text/plain; charset=utf-8');
	exit("Can't extract any JSON. Wrong URL? $url");
}

$json = trim($match[1], ' ;');
$data = json_decode($json, true);
if ( !isset($data['entry_data']['ProfilePage'][0]['user']) ) {
	header('Content-type: text/plain; charset=utf-8');
	exit("Can't extract profile JSON. Invalid profile?");
}

$profile = $data['entry_data']['ProfilePage'][0]['user'];
if ( !isset($profile['media']['nodes']) ) {
	header('Content-type: text/plain; charset=utf-8');
	exit("Can't extract media JSON. Private profile?");
}

$media = $profile['media']['nodes'];

// 3. Print RSS
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";

?>
<rss version="2.0">
	<channel>
		<title>@<?= html($username) ?></title>
		<link>https://www.instagram.com/<?= html($username) ?>/</link>
		<description>@<?= html($username) ?></description>
		<? foreach ($media as $node):
			$link = $node['display_src'];
			$thumb = $node['thumbnail_src'];
			$title = trim(trim(@$node['caption']) . " \n\n https://www.instagram.com/p/" . $node['code'] . '/', ' -');
			?>
			<item>
				<title><?= html($title) ?></title>
				<link><?= html($link) ?></link>
				<image>
					<url><?= html($thumb) ?></url>
					<link><?= html($link) ?></link>
					<title><?= html($title) ?></title>
				</image>
				<guid isPermaLink="true">https://www.instagram.com/p/<?= html($node['code']) ?>/</guid>
				<description><?= html($title) ?></description>
				<pubDate><?= date('r', $node['date']) ?></pubDate>
				<author><?= html($username) ?>@instagram.com</author>
			</item>
		<? endforeach ?>
	</channel>
</rss>
