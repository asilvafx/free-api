<?php

require_once(INTEGRATIONS.'HelloWorld/HelloWorld.php');

global $f3;

$f3->route('GET|POST /api/hello-world', 'HelloWorld->Test');


