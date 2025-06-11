<?php

class Frontend extends MainController
{

    function SecureDl($f3, $args)
    {
        $slug = empty($args[0]) ? '' : $args[0];
        $key = isset($_GET['key']) && !empty($_GET['key']) ? htmlspecialchars_decode($_GET['key']) : null;
        if(!$key){  $f3->reroute('/'); return false; }

        $slug = str_replace("/secure/dl/","",$slug);

        $file_url = BASE_PATH . '/public/uploads/' . $slug;

        if (!file_exists($file_url)) {
            $f3->reroute('/');
        } else {
            $crypt = new Crypt;
            if($crypt->verify($slug, $key)){
                header('Content-Type: application/octet-stream');
                header("Content-Transfer-Encoding: Binary");
                header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\"");
                readfile($file_url);
            } else {
                $f3->reroute('/');
            }

        }
        exit;
    }

    function Assets($f3, $args)
    {
        $slug = empty($args[0]) ? '' : $args[0];
        $file_url = BASE_PATH . '/' . $f3->get('UI') . $slug;

        if (!file_exists($file_url)) {
            $f3->reroute('/');
        }

        $extension = pathinfo($file_url, PATHINFO_EXTENSION);

        // Set correct MIME type
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'svg' => 'image/svg+xml',
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        $content_type = isset($mime_types[$extension]) ? $mime_types[$extension] : 'application/octet-stream';
        header("Content-Type: $content_type");
        header("Content-Length: " . filesize($file_url));
        readfile($file_url);
        exit;
    }


    function Public($f3, $args)
    {
        $slug = empty($args[0]) ? '' : $args[0];

        $file_url = BASE_PATH . '/' . $slug;
        if (!file_exists($file_url)) {
            $f3->reroute('/');
        } else {
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . basename($file_url) . "\"");
            readfile($file_url);
        }
        exit;
    }

    function Base($f3, $args)
    {

        if ($f3->get('SITE.enable_frontend') !== true) {
            $f3->reroute($f3->get('SITE.uri_auth'));
            return false;
        }

        $slug = empty($args[0]) ? '' : $args[0];
        $landing = '/landing';
        $ui = $f3->get('UI');
        $view = 'routes' . $landing . '/view.htm';
        $php = null;

        // Load user information
        $userClass = new User;
        $userClass->Info($f3);

        if (empty($slug) || $slug == "/") {
            $slug = $landing;
        }

        // Set page slug
        $f3->set('PAGE.slug', $slug);

        if (!empty($slug)) {

            if (file_exists($ui . 'routes' . $slug . '/view.php')) {
                $php = $ui . 'routes' . $slug . '/view.php';
            }
            if (file_exists($ui . 'routes' . $slug . '/view.htm')) {
                $view = 'routes' . $slug . '/view.htm';
            } else {
                if (!is_null($php)) {
                    require_once($php);
                    die;
                }
            }

            if (file_exists($ui.'routes'.$slug.'/view.css')) {
                $f3->set('VIEW_CSS', 'routes'.$slug.'/view.css');
            }

            if (file_exists($ui.'routes'.$slug.'/view.js')) {
                $f3->set('VIEW_JS', 'routes'.$slug.'/view.js');
            }
        }

        // Load main PHP
        if (file_exists($ui . 'index.php')) {
            require_once($ui . 'index.php');
        }

        // Load page PHP
        if (!is_null($php)) {
            require_once($php);
        }

        // Set page container
        $f3->set('CONTENT', $view);
    }
}
