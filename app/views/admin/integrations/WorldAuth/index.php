<?php

require_once(INTEGRATIONS.'WorldAuth/WorldAuth.php');

global $f3;

$f3->route('GET|POST /auth/worldId', 'WorldAuth->Init');


