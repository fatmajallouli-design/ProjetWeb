<?php
$rawRequest = $_SERVER['REQUEST_URI'];
$request = urldecode(parse_url($rawRequest, PHP_URL_PATH));

if ($request === '/' || $request === '/index.php') {
    include 'html/index.php';
} else if (strpos($request, '/html/') === 0) {
    $file = substr($request, 1);
    if (file_exists($file)) {
        include $file;
    } else {
        http_response_code(404);
        echo '404 Not Found';
    }
} else if (strpos($request, '/php/') === 0) {
    $file = substr($request, 1);
    if (file_exists($file)) {
        include $file;
    } else {
        http_response_code(404);
        echo '404 Not Found';
    }
} else if (preg_match('/^\/(.+\.php)$/', $request, $matches)) {
    $file = 'html/' . $matches[1];
    if (file_exists($file)) {
        include $file;
    } else {
        http_response_code(404);
        echo '404 Not Found';
    }
} else {
    // For css, js, etc.
    $file = substr($request, 1);
    if (file_exists($file)) {
        // Serve the file
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'avif' => 'image/avif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
        ];
        if (isset($mime[$ext])) {
            header('Content-Type: ' . $mime[$ext]);
        } else {
            header('Content-Type: application/octet-stream');
        }
        readfile($file);
    } else {
        http_response_code(404);
        echo '404 Not Found';
    }
}
?>