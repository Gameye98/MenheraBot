<?php
if (!empty($_SERVER['HTTP_USER_AGENT'])) {
			$userAgents = array(
				'Googlebot',
				'DuckDuckBot',
				'Baiduspider',
				'Exabot',
				'SimplePie',
				'Curl',
				'OkHttp',
				'SiteLockSpider',
				'BLEXBot',
				'ScoutJet',
				'AdsBot Google Mobile',
				'Googlebot Mobile',
				'MJ12bot',
				'Slurp',
				'MSNBot',
				'PycURL',
				'facebookexternalhit',
				'facebot',
				'ia_archiver',
				'crawler',
				'YandexBot',
				'Rambler',
				'Yahoo! Slurp',
				'YahooSeeker',
				'bingbot'
			);
			if (preg_match('/' . implode('|', $userAgents) . '/i', $_SERVER['HTTP_USER_AGENT'])) {
				header('HTTP/1.0 404 Not Found');
				exit();
			}
			unset($userAgents);
}
