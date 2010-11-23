<?php
require_once('AsyncQueue.php');

$aq = new AsyncQueue();

$i = 1;
$aq->put(array($i++));
$aq->put(array($i++));
$aq->put($i++);
$aq->put($i++);
print_r($aq->status());
print_r($aq->get());
print_r($aq->status());
print_r($aq->get());
print_r($aq->get());
