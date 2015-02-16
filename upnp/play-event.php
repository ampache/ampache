<?php
  require_once '../lib/init.php';

  debug_event('play-event', '1', '5');

  $headers = getallheaders();
  $requestRaw = file_get_contents('php://input');

  debug_event('play-event', ' headers: ' . print_r($headers, true) . '\n request: ' . print_r($requestRaw, true), '5');
?>