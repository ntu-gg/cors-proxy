<?php
header('Access-Control-Allow-Origin: *');

function validate_host ($host) {
  return preg_match('/\.ntu\.edu\.tw$/i', $host) || (ip2long($host) >= ip2long('140.112.0.0') && ip2long($host) <= ip2long('140.112.255.255'));
}

function get_user_agent () {
  return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:53.0) Gecko/20100101 Firefox/53.0';
}

function get_user_ip () {
  return isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
}

function res ($msg, $code = 200) {
  http_response_code($code);
  die($msg);
}

if (!isset($_GET['u'])) res('Usage: https://'.$_SERVER['HTTP_HOST'].'/?u=[URL]');
if (!in_array(parse_url($_GET['u'], PHP_URL_SCHEME), ['http', 'https', 'ftp'])) res('Invalid Scheme', 400);
if (!validate_host(parse_url($_GET['u'], PHP_URL_HOST))) res('Invalid Host', 400);

$opts = [
  'http' => [
    'method' => 'GET',
    'ignore_errors' => true,
    'header' => implode("\r\n", [
      'User-Agent: '.get_user_agent(),
      'X-Forwarded-For: '.get_user_ip()
    ])
  ]
];

$body = file_get_contents($_GET['u'], false, stream_context_create($opts));
$status_code = 502;

if (preg_match('/^HTTP\/1\.[0|1|x] ([0-9]{3})/', $http_response_header[0], $matches)) {
  $status_code = $matches[1];
}

foreach ($http_response_header as $value) {
  if (preg_match('/^Content-Type:/i', $value)) {
    // Return original Content-Type
    header($value, false);
  }
}

if (mb_detect_encoding($body, 'UTF-8, BIG-5') == 'BIG-5' && !isset($_GET['noconvert'])) {
  res(iconv('BIG-5', 'UTF-8', $body), $status_code);
}

res($body, $status_code);