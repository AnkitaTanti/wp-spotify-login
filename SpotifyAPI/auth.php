<?php
require '../../phpMyAdmin/vendor/autoload.php';
require 'Request.php';
require 'SpotifyWebAPI.php';
require 'Session.php';
require 'SpotifyWebAPIException.php';
require 'SpotifyWebAPIAuthException.php';

$session = new SpotifyWebAPI\Session(
	'6127f379a7b0419eba158d9e4480b2c1',
	'9017419d4d5c41ceb04f07db1110e358',
	'http://localhost/practical/zuveria'
);

$options = [
	'scope' => [
		'playlist-read-private',
		'user-read-private',
	],
];

header('Location: ' . $session->getAuthorizeUrl($options));
die();