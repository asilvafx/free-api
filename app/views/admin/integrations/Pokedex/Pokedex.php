<?php

class Pokedex
{
    private $client;
    public function __construct()
    {
        global $f3;
    }

    function getJson(string $url): ?array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        return $resp ? json_decode($resp, true) : null;
    }

    function getExistingIds(array $data): array {
        return array_column($data['pokemon'], 'id');
    }

    public function scrapeMoves()
    {
        $file = __DIR__ . '/move.json';
        $url  = "https://pokeapi.co/api/v2/move?limit=3&offset=0";
        $list = $this->getJson($url);

        if (!$list || empty($list['results'])) {
            echo "❌ Unable to fetch move list.\n";
            return;
        }

        $moves = [];
        foreach ($list['results'] as $moveRef) {
            $data = $this->getJson($moveRef['url']);
            if (!$data) continue;

            // pick the English short effect
            $effect = '';
            foreach ($data['effect_entries'] as $e) {
                if ($e['language']['name'] === 'en') {
                    $effect = $e['short_effect'];
                    break;
                }
            }

            $moves[] = [
                'id'           => $data['id'],
                'name'         => $data['name'],
                'type'         => $data['type']['name'] ?? null,
                'damage_class' => $data['damage_class']['name'] ?? null,
                'power'        => $data['power'],
                'pp'           => $data['pp'],
                'accuracy'     => $data['accuracy'],
                'priority'     => $data['priority'],
                'target'       => $data['target']['name'] ?? null,
                'effect'       => $effect,
            ];

            echo "✔ Fetched move #{$data['id']}: {$data['name']}\n";
            usleep(50000);
        }

        // sort by id
        usort($moves, fn($a, $b) => $a['id'] <=> $b['id']);

        file_put_contents($file, json_encode($moves, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo "\n✅ Overwrote move.json with ".count($moves)." moves.\n";
    }

    public function scrapeTypes()
    {
        $file = __DIR__ . '/type.json';
        $url  = "https://pokeapi.co/api/v2/type";
        $list = $this->getJson($url);

        if (!$list || empty($list['results'])) {
            echo "❌ Unable to fetch type list.\n";
            return;
        }

        $types = [];
        foreach ($list['results'] as $typeRef) {
            $data = $this->getJson($typeRef['url']);
            if (!$data) continue;

            // strip URLs from damage_relations
            $cleanDR = [];
            foreach ($data['damage_relations'] as $relation => $entries) {
                $cleanDR[$relation] = array_map(fn($e) => $e['name'], $entries);
            }

            $types[] = [
                'id'               => $data['id'],
                'name'             => $data['name'],
                'damage_relations' => $cleanDR,
                'pokemon'          => array_map(fn($p) => $p['pokemon']['name'], $data['pokemon']),
                'moves'            => array_map(fn($m) => $m['name'], $data['moves']),
                'names'            => array_reduce($data['names'], function($carry, $n) {
                    $carry[$n['language']['name']] = $n['name'];
                    return $carry;
                }, []),
            ];

            echo "✔ Fetched type #{$data['id']}: {$data['name']}\n";
            usleep(50000);
        }

        usort($types, fn($a, $b) => $a['id'] <=> $b['id']);
        file_put_contents($file, json_encode($types, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        echo "\n✅ Overwrote type.json with ".count($types)." types.\n";
    }

    function scrapeEggGroups()
    {
        define('EGG_FILE', __DIR__ . '/egg.json');
        if (!file_exists(EGG_FILE)) file_put_contents(EGG_FILE, json_encode(['eggs' => []], JSON_PRETTY_PRINT));

        $eggs = ['eggs' => []];
        $data = $this->getJson("https://pokeapi.co/api/v2/egg-group/");
        if (!$data || empty($data['results'])) {
            echo "Failed to load egg groups.\n";
            return;
        }

        foreach ($data['results'] as $eggGroup) {
            $eggData = $this->getJson($eggGroup['url']);
            if (!$eggData) {
                echo "Failed to fetch egg data: {$eggGroup['name']}\n";
                continue;
            }

            // Translated names
            $names = [];
            foreach ($eggData['names'] as $n) {
                $lang = $n['language']['name'];
                if (in_array($lang, ['en', 'fr', 'es', 'pt']) && !isset($names[$lang])) {
                    $names[$lang] = $n['name'];
                }
            }

            // Species names
            $species = array_map(fn($s) => $s['name'], $eggData['pokemon_species']);

            $eggs['eggs'][] = [
                'id' => $eggData['id'],
                'name' => $eggData['name'],
                'translations' => $names,
                'species' => $species,
            ];

            echo "Egg Group: {$eggData['name']} (" . count($species) . " species)\n";

            sleep(1);
        }

        usort($eggs['eggs'], fn($a, $b) => $a['id'] <=> $b['id']);
        file_put_contents(EGG_FILE, json_encode($eggs, JSON_PRETTY_PRINT));
    }

    function ScrapeAbilities() {
        $url = "https://pokeapi.co/api/v2/ability?limit=3";
        $data = $this->getJson($url);

        if (!$data || !isset($data['results'])) {
            echo "❌ Failed to fetch abilities list.\n";
            return;
        }

        define('ABILITY_FILE', __DIR__ . '/ability.json');

        // Load existing data or init
        $abilitiesJson = ['abilities' => []];
        if (file_exists(ABILITY_FILE)) {
            $json = file_get_contents(ABILITY_FILE);
            $decoded = json_decode($json, true);
            if (is_array($decoded) && isset($decoded['abilities'])) {
                $abilitiesJson = $decoded;
            }
        }

        $existingIds = array_column($abilitiesJson['abilities'], 'id');

        foreach ($data['results'] as $entry) {
            $abilityData = $this->getJson($entry['url']);
            if (!$abilityData) continue;

            $id = $abilityData['id'];
            if (in_array($id, $existingIds)) continue;

            $name = $abilityData['name'];
            $gen = $abilityData['generation']['name'] ?? 'unknown';
            $region = $this->getRegionFromGeneration($gen);
            $isMainSeries = $abilityData['is_main_series'];

            // Effects
            $effects = [];
            foreach ($abilityData['effect_entries'] as $e) {
                $lang = $e['language']['name'];
                if (in_array($lang, ['en', 'fr', 'es', 'pt'])) {
                    $effects[$lang] = [
                        'effect' => $e['effect'],
                        'short_effect' => $e['short_effect'],
                    ];
                }
            }

            // Flavor text
            $flavorText = [];
            foreach ($abilityData['flavor_text_entries'] as $f) {
                $lang = $f['language']['name'];
                if (!isset($flavorText[$lang]) && in_array($lang, ['en', 'fr', 'es', 'pt'])) {
                    $flavorText[$lang] = $f['flavor_text'];
                }
            }

            // Names
            $names = [];
            foreach ($abilityData['names'] as $n) {
                $lang = $n['language']['name'];
                if (in_array($lang, ['en', 'fr', 'es', 'pt'])) {
                    $names[$lang] = $n['name'];
                }
            }

            // Pokémon using this ability
            $pokemons = [];
            foreach ($abilityData['pokemon'] as $p) {
                $pokemons[] = [
                    'name' => $p['pokemon']['name'],
                    'id' => (int)basename($p['pokemon']['url']),
                    'slot' => $p['slot'],
                    'is_hidden' => $p['is_hidden']
                ];
            }

            $abilitiesJson['abilities'][] = [
                'id' => $id,
                'name' => $name,
                'generation' => $gen,
                'region' => $region,
                'main_series' => $isMainSeries,
                'effects' => $effects,
                'flavor_text' => $flavorText,
                'names' => $names,
                'pokemon' => $pokemons,
            ];

            echo "✅ Loaded ability #$id: $name\n";
            usleep(3000000); // 3s delay
        }

        // Sort & save
        usort($abilitiesJson['abilities'], fn($a, $b) => $a['id'] <=> $b['id']);
        file_put_contents(ABILITY_FILE, json_encode($abilitiesJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "📦 Saved all abilities to ability.json\n<br>";

        print_r($abilitiesJson);
    }

    function calculateRarity(array $pokemon, ?float $spawnChance): int {
        $score = 0;

        // Higher spawn chance = more common
        if ($spawnChance !== null) {
            $score += min(100, $spawnChance * 10);
        } else {
            // fallback to base_experience
            $score += 100 - ($pokemon['base_experience'] ?? 50);
        }

        // Adjust based on stats (higher stats = rarer)
        if (isset($pokemon['stats'])) {
            $totalStats = array_sum(array_map(fn($s) => $s['base_stat'], $pokemon['stats']));
            $score -= ($totalStats - 300) / 6; // average total ~300–600
        }

        return max(1, min(100, round($score)));
    }

    function fetchPokemonData(int $id): ?array {
        $pokemon = $this->getJson("https://pokeapi.co/api/v2/pokemon/$id");
        $species = $pokemon && isset($pokemon['species']['url']) ? $this->getJson($pokemon['species']['url']) : null;

        if (!$pokemon || !$species) return null;

        $num = str_pad($id, 3, '0', STR_PAD_LEFT);
        $type = array_map(fn($t) => $t['type']['name'], $pokemon['types']);
        $height = ($pokemon['height'] / 10);
        $weight = ($pokemon['weight'] / 10);
        $candy = $species['name'] . '-candy';

        // Weaknesses
        $weaknesses = [];
        foreach ($pokemon['types'] as $t) {
            $typeData = $this->getJson($t['type']['url']);
            foreach ($typeData['damage_relations']['double_damage_from'] as $weak) {
                $weaknesses[] = $weak['name'];
            }
        }
        $weaknesses = array_unique($weaknesses);

        // Evolutions
        $evolutions = [];
        if (!empty($species['evolution_chain']['url'])) {
            $chain = $this->getJson($species['evolution_chain']['url']);
            if (!function_exists('parseChain')) {
                function parseChain($node, &$out) {
                    if (!$node) return;
                    // Extract the species ID from the URL
                    preg_match('#/pokemon-species/(\d+)/?$#', $node['species']['url'], $m);
                    $out[] = ['num' => str_pad($m[1], 3, '0', STR_PAD_LEFT), 'name' => $node['species']['name']];
                    foreach ($node['evolves_to'] as $child) {
                        parseChain($child, $out);
                    }
                }
            }
            parseChain($chain['chain'], $evolutions);
        }

        // Spawn
        $spawn = $this->getJson($pokemon['location_area_encounters']);
        $spawnLocations = [];
        $spawnChance = null;
        if (is_array($spawn)) {
            foreach ($spawn as $s) {
                $spawnLocations[] = $s['location_area']['name'];
                if (isset($s['version_details'][0]['encounter_details'][0]['chance'])) {
                    $spawnChance = $s['version_details'][0]['encounter_details'][0]['chance'];
                }
            }
        }

        // Base stats
        $stats = [];
        foreach ($pokemon['stats'] as $stat) {
            $stats[$stat['stat']['name']] = $stat['base_stat'];
        }

        // Abilities
        $abilities = array_map(fn($a) => $a['ability']['name'], $pokemon['abilities']);

        $moves = [];
        foreach ($pokemon['moves'] as $m) {
            // each $m has ['move'] and ['version_group_details']
            $moveName = $m['move']['name'];
            // grab only the learning details you care about
            $learnDetails = array_map(function($d) {
                return [
                    'level_learned_at'  => $d['level_learned_at'],
                    'move_learn_method' => $d['move_learn_method']['name'],
                ];
            }, $m['version_group_details']);

            $moves[] = [
                'name'    => $moveName,
                'learned' => $learnDetails
            ];
        }

        // Sprites & gif
        $sprites = $pokemon['sprites'] ?? [];
        $other = $sprites['other'] ?? [];

        // Growth rate
        $growthRate = $species['growth_rate']['name'] ?? 'unknown';

        // Catch rate
        $catchRateRaw = $species['capture_rate'] ?? null;
        $catchRate = $catchRateRaw !== null ? round(($catchRateRaw / 255) * 100) : null; // convert to 0-100 scale

        return [
            'id' => $id,
            'num' => $num,
            'name' => $pokemon['name'],
            'img' => $sprites['front_default'],
            'artwork' => [
                'default' => $other['official-artwork']['front_default'] ?? null,
                'animated' => $other['showdown']['front_default'] ?? null,
            ],
            'cries' => [
                'latest' => "https://raw.githubusercontent.com/PokeAPI/cries/main/cries/pokemon/latest/{$id}.ogg",
                'legacy' => "https://raw.githubusercontent.com/PokeAPI/cries/main/cries/pokemon/legacy/{$id}.ogg",
            ],
            'abilities' => $abilities,
            'type' => $type,
            'rarity' => $this->calculateRarity($pokemon, $spawnChance),
            'height' => $height,
            'weight' => $weight,
            'candy' => $candy,
            'candy_count' => count($evolutions) > 1 ? 25 : null,
            'egg' => $species['egg_groups'][0]['name'] ?? null,
            'spawn_chance' => $spawnChance,
            'spawn_location' => $spawnLocations,
            'region' => $this->getRegionFromGeneration($species['generation']['name'] ?? 'unknown'),
            'weaknesses' => $weaknesses,
            'base_experience' => $pokemon['base_experience'] ?? null,
            'multiplier' => $pokemon['stats'][0]['base_stat'] ?? null,
            'catch_rate' => $catchRate,
            'growth_rate' => $growthRate,
            'evolutions' => array_slice($evolutions, 1),
            'stats' => $stats,

        ];
    }

    function getRegionFromGeneration(string $generation): string {
        $map = [
            'generation-i' => 'kanto',
            'generation-ii' => 'johto',
            'generation-iii' => 'hoenn',
            'generation-iv' => 'sinnoh',
            'generation-v' => 'unova',
            'generation-vi' => 'kalos',
            'generation-vii' => 'alola',
            'generation-viii' => 'galar',
            'generation-ix' => 'paldea',
        ];

        return $map[$generation] ?? $generation;
    }


    function ScrapePokemon()
    {
        $pokeId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $from = isset($_GET['from']) ? (int)$_GET['from'] : 1;
        if($pokeId){
            $from = $pokeId;
        }
        $to = isset($_GET['to']) ? (int)$_GET['to'] : 1;
        header('Content-Type: application/json');

        define('POKEDEX_FILE', __DIR__ . '/pokemon.json');

        // Load or init
        $pokedex = ['pokemon' => []];
        if (file_exists(POKEDEX_FILE)) {
            $json = file_get_contents(POKEDEX_FILE);
            $decoded = json_decode($json, true);
            if (is_array($decoded) && isset($decoded['pokemon'])) {
                $pokedex = $decoded;
            }
        }
        $existingIds = $this->getExistingIds($pokedex);

        if($from < $to) {

        // Loop from-to
        for ($id = $from; $id <= $to; $id++) {
            if (in_array($id, $existingIds)) continue;

            echo "Fetching Pokémon #$id...\n";
            $data = $this->fetchPokemonData($id);

            if ($data) {
                $pokedex['pokemon'][] = $data;
                usort($pokedex['pokemon'], fn($a, $b) => $a['id'] <=> $b['id']);
                file_put_contents(POKEDEX_FILE, json_encode($pokedex, JSON_PRETTY_PRINT));print_r($data);
            } else {
                echo "Failed to fetch Pokémon ID $id. Skipping.\n";
            }

            sleep(1); // chill a bit, avoid API rage 😅
        }
        }  else {

            echo "Fetching Pokémon...\n";
            $data = $this->fetchPokemonData($from);

            if ($data) {
                $pokedex['pokemon'][] = $data;
                if (!in_array($from, $existingIds)){
                    usort($pokedex['pokemon'], fn($a, $b) => $a['id'] <=> $b['id']);
                    file_put_contents(POKEDEX_FILE, json_encode($pokedex, JSON_PRETTY_PRINT));
                }
                print_r($data);
            } else {
                echo "Failed to fetch Pokémon. Skipping.\n";
            }
        }
    }


}

