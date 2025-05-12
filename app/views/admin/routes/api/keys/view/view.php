<?php

$f3->set('PAGE.title', 'Not Found');
$f3->set('PAGE.slug', 'api');
$f3->set('breadcrumbs', [
    [
        "name" => "API",
        "slug" => "api"
    ],
    [
        "name" => "Private Keys",
        "slug" => "api/keys"
    ],
    [
        "name" => "View Key",
        "slug" => "api/keys"
    ]
]);

global $db;

$query = !empty($f3->get('GET.s')) ? htmlspecialchars_decode($f3->get('GET.s')) : null;

if (!empty($query)) {

    try {
        $query = base64_decode($query);

        $api = new DB\SQL\Mapper($db, 'api');
        $api->load(array('api_slug=?', $query));

        if ($api->dry()) {
            $f3->reroute('/' . $f3->get('SITE.uri_backend') . '/api');
            return false;
        } else {
            $f3->set('PAGE.title', 'Key: ' . $query);

            $f3->set('api.slug', $query);
            $f3->set('api.key', $api->api_key);
            $f3->set('api.usage', $api->api_usage);
            $f3->set('api.created_by', $api->created_by);
            $f3->set('api.created_at', $api->created_at);
            $f3->set('api.status', $api->status);

            $allowed_domains = array();
            $allowed_domains = explode(',', $api->api_allowed_domains);
            $f3->set('api.allowed_domains', $allowed_domains);
        }
    } catch (Exception $e) {
        $f3->set('notfound', true);
    }
} else {
    $f3->set('notfound', true);
    $f3->reroute('/' . $f3->get('SITE.uri_backend') . '/api');
    return false;
}
