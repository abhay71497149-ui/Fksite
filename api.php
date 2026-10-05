<?php

session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$DATA = __DIR__ . '/data/site.json';
$UPLOAD = __DIR__ . '/uploads';

if (!is_dir($UPLOAD)) {
    mkdir($UPLOAD, 0755, true);
}


/* ================= CONFIG ================= */

function cfg() {
    global $DATA;

    if (!file_exists($DATA)) {
        return [];
    }

    $data = json_decode(
        file_get_contents($DATA),
        true
    );

    return is_array($data) ? $data : [];
}


function savecfg($c) {
    global $DATA;

    file_put_contents(
        $DATA,
        json_encode(
            $c,
            JSON_UNESCAPED_SLASHES |
            JSON_UNESCAPED_UNICODE |
            JSON_PRETTY_PRINT
        ),
        LOCK_EX
    );
}


function out($x, $code = 200) {

    http_response_code($code);

    header('Content-Type: application/json');

    echo json_encode(
        $x,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* ================= ACTION ================= */

$action = $_GET['action'] ?? '';


/* ================= PUBLIC CONFIG ================= */

if ($action === 'config') {

    out(cfg());

}


/* ================= LOGIN ================= */

if ($action === 'login') {

    $in = json_decode(
        file_get_contents('php://input'),
        true
    ) ?: [];

    require __DIR__ . '/admin_config.php';

    if (
        password_verify(
            (string)($in['password'] ?? ''),
            ADMIN_PASSWORD_HASH
        )
    ) {

        $_SESSION['tenver_admin'] = true;

        out([
            'ok' => true
        ]);

    }

    out([
        'ok' => false,
        'error' => 'Invalid password'
    ], 401);

}


/* ================= LOGOUT ================= */

if ($action === 'logout') {

    session_destroy();

    out([
        'ok' => true
    ]);

}


/* ================= ADMIN AUTH ================= */

if (empty($_SESSION['tenver_admin'])) {

    out([
        'error' => 'Unauthorized'
    ], 401);

}


/* ================= CHANGE PASSWORD ================= */

if ($action === 'change_password') {

    $in = json_decode(
        file_get_contents('php://input'),
        true
    ) ?: [];

    $new = (string)($in['password'] ?? '');

    if (strlen($new) < 8) {

        out([
            'error' =>
                'Password must be at least 8 characters'
        ], 400);

    }


    $path = __DIR__ . '/admin_config.php';

    $txt = file_get_contents($path);

    $hash = password_hash(
        $new,
        PASSWORD_DEFAULT
    );


    $txt = preg_replace(
        "/const ADMIN_PASSWORD_HASH = '[^']*';/",
        "const ADMIN_PASSWORD_HASH = '$hash';",
        $txt
    );


    file_put_contents(
        $path,
        $txt,
        LOCK_EX
    );


    out([
        'ok' => true
    ]);

}


/* ================= SAVE CONFIG ================= */

if ($action === 'save') {

    $in = json_decode(
        file_get_contents('php://input'),
        true
    );


    if (!is_array($in)) {

        out([
            'error' => 'Invalid data'
        ], 400);

    }


    $old = cfg();


    /*
     * Existing settings
     */

    $allowed = [

        'settings',
        'theme',

        'homeBanners',
        'detailBanner',

        'offerTicker',
        'cartTicker',

        'mysteryImage',

        'paymentQr',

        'products',

        'site',

        'navRightLogo',
        'navLeftLogo',

        'brandLogo',
        'productBrand',

        /*
         * NEW
         */
        'returningOffer',

        /*
         * NEW
         */
        'paytmStatus'

    ];


    foreach ($allowed as $key) {

        if (array_key_exists($key, $in)) {

            $old[$key] = $in[$key];

        }

    }


    /*
     * Make sure returningOffer
     * always has safe defaults.
     */

    if (!isset($old['returningOffer']) ||
        !is_array($old['returningOffer'])) {

        $old['returningOffer'] = [

            'enabled' => true,

            'percent' => 20,

            'resetSeconds' => 6,

            'message' =>
                'Our Price Increased 20% Due to High Demand.',

            'label' =>
                'Returning Customer Price',

            'animation' => true

        ];

    }


    /*
     * Normalize returning values
     */

    $old['returningOffer']['enabled'] =
        !empty(
            $old['returningOffer']['enabled']
        );


    $old['returningOffer']['percent'] =
        max(
            0,
            (float)(
                $old['returningOffer']['percent'] ?? 20
            )
        );


    $old['returningOffer']['resetSeconds'] =
        max(
            2,
            (int)(
                $old['returningOffer']['resetSeconds'] ?? 6
            )
        );


    $old['returningOffer']['message'] =
        (string)(
            $old['returningOffer']['message'] ??
            'Our Price Increased 20% Due to High Demand.'
        );


    $old['returningOffer']['label'] =
        (string)(
            $old['returningOffer']['label'] ??
            'Returning Customer Price'
        );


    $old['returningOffer']['animation'] =
        !empty(
            $old['returningOffer']['animation']
        );


    /*
     * Paytm default
     */

    if (
        !isset($old['paytmStatus']) ||
        !in_array(
            $old['paytmStatus'],
            ['available', 'unavailable'],
            true
        )
    ) {

        $old['paytmStatus'] =
            'unavailable';

    }


    savecfg($old);


    out([
        'ok' => true,
        'config' => $old
    ]);

}


/* ================= IMAGE UPLOAD ================= */

if (
    $action === 'upload' &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    if (
        empty($_FILES['file']) ||
        $_FILES['file']['error'] !== UPLOAD_ERR_OK
    ) {

        out([
            'error' => 'Upload failed'
        ], 400);

    }


    $f = $_FILES['file'];


    if ($f['size'] > 8 * 1024 * 1024) {

        out([
            'error' => 'Max 8MB'
        ], 400);

    }


    $mime = (
        new finfo(FILEINFO_MIME_TYPE)
    )->file(
        $f['tmp_name']
    );


    $allowed = [

        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif'

    ];


    if (!isset($allowed[$mime])) {

        out([
            'error' =>
                'Only JPG, PNG, WEBP or GIF'
        ], 400);

    }


    $name =
        'img_' .
        bin2hex(random_bytes(8)) .
        '.' .
        $allowed[$mime];


    if (
        !move_uploaded_file(
            $f['tmp_name'],
            $UPLOAD . '/' . $name
        )
    ) {

        out([
            'error' =>
                'Could not save'
        ], 500);

    }


    out([
        'ok' => true,
        'url' => 'uploads/' . $name
    ]);

}


/* ================= UNKNOWN ================= */

out([
    'error' => 'Unknown action'
], 404);

?>
