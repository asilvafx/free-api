<?php

global $f3;

$stripe = new Stripe;

$f3->route('GET|POST /pay/gateway/stripe', 'Stripe->Init');


