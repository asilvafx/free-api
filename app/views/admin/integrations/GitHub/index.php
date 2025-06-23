<?php

require_once(INTEGRATIONS.'GitHub/GitHub.php');

global $f3;

$f3->route('GET|POST /auth/github', 'GitHub->Init');


