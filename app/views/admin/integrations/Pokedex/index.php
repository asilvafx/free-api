<?php

global $f3;

$f3->route('GET|POST /api/pokemon', 'Pokedex->ScrapePokemon');
$f3->route('GET|POST /api/ability', 'Pokedex->ScrapeAbilities');
$f3->route('GET|POST /api/egg', 'Pokedex->scrapeEggGroups');
$f3->route('GET|POST /api/move', 'Pokedex->scrapeMoves');
$f3->route('GET|POST /api/type', 'Pokedex->scrapeTypes');

