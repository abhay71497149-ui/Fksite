
<?php
/*
 * Product importer.
 * Loaded ONLY by api.php for action=import_product (admin session required).
 * Takes a product page URL, returns name / price / description / up to 5 images
 * (images are downloaded into /uploads). Nothing is saved to site.json here.
 */

function imp_fail($msg, $code = 400) {
    out(['ok' => false, 'error' => $msg], $code);
}

/* ---------- URL safety (blocks internal / private addresses) ---------- */

function imp_public_ip($host) {
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $ips = [$host];
    } else {
        $ips = gethostbynamel($host);
        if (!$ips) return null;
    }
    foreach ($ips as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }
    }
    return $ips[0];
}

function imp_check_url($url) {
    $p = parse_url($url);
    if (!$p || empty($p['scheme']) || empty($p['host'])) return null;
    $scheme = strtolower($p['scheme']);
    if ($scheme !== 'http' && $scheme !== 'https') return null;
    $port = isset($p['port']) ? (int)$p['port'] : ($scheme === 'https' ? 443 : 80);
    if ($port !== 80 && $port !== 443) return null;
    $ip = imp_public_ip($p['host']);
    if ($ip === null) return null;
    return ['scheme' => $scheme, 'host' => $p['host'], 'port' => $port, 'ip' => $ip];
}

/* ---------- HTTP GET with manual redirects, size cap, pinned IP ---------- */

function imp_get($url, $maxBytes, $referer = '') {
    if (!function_exists('curl_init')) return null;
    $hops = 0;
    while ($hops < 5) {
        $chk = imp_check_url($url);
        if (!$chk) return null;
        $body = '';
        $tooBig = false;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Mobile Safari/537.36',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/json,image/avif,image/webp,image/*,*/*;q=0.8',
                'Accept-Language: en-IN,en;q=0.9',
            ],
            CURLOPT_RESOLVE => [$chk['host'] . ':' . $chk['port'] . ':' . $chk['ip']],
            CURLOPT_WRITEFUNCTION => function ($c, $data) use (&$body, &$tooBig, $maxBytes) {
                $body .= $data;
                if (strlen($body) > $maxBytes) { $tooBig = true; return 0; }
                return strlen($data);
            },
        ]);
        if ($referer !== '') curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ctype = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $redirect = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        if ($tooBig) return null;
        if ($status >= 300 && $status < 400 && $redirect !== '') {
            $url = $redirect;
            $hops++;
            continue;
        }
        if ($status < 200 || $status >= 300) return null;
        return ['body' => $body, 'ctype' => $ctype, 'url' => $url];
    }
    return null;
}

/* ---------- small helpers ---------- */

function imp_abs($u, $base) {
    $u = trim(html_entity_decode((string)$u, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($u === '' || stripos($u, 'data:') === 0) return '';
    if (strpos($u, '//') === 0) {
        $b = parse_url($base);
        return ($b['scheme'] ?? 'https') . ':' . $u;
    }
    if (preg_match('#^https?://#i', $u)) return $u;
    $b = parse_url($base);
    if (!$b || empty($b['host'])) return '';
    $root = ($b['scheme'] ?? 'https') . '://' . $b['host'] . (isset($b['port']) ? ':' . $b['port'] : '');
    if ($u[0] === '/') return $root . $u;
    $dir = isset($b['path']) ? preg_replace('#/[^/]*$#', '/', $b['path']) : '/';
    return $root . $dir . $u;
}

function imp_clean_text($html) {
    $t = (string)$html;
    $t = preg_replace('#<\s*(br|/p|/div|/li|/h[1-6])\s*/?\s*>#i', "\n", $t);
    $t = preg_replace('#<\s*li[^>]*>#i', '• ', $t);
    $t = strip_tags($t);
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace("/[ \t\xC2\xA0]+/u", ' ', $t);
    $t = preg_replace("/\n\s*\n\s*\n+/", "\n\n", $t);
    $t = trim($t);
    if (function_exists('mb_substr')) $t = mb_substr($t, 0, 2500);
    else $t = substr($t, 0, 2500);
    return $t;
}

function imp_price($v) {
    if ($v === null || $v === '') return 0.0;
    if (is_numeric($v)) return (float)$v;
    $s = preg_replace('/[^0-9.,]/', '', (string)$v);
    if ($s === '') return 0.0;
    if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
        $s = str_replace(',', '', $s);
    } elseif (strpos($s, ',') !== false) {
        $s = preg_match('/,\d{1,2}$/', $s) ? str_replace(',', '.', $s) : str_replace(',', '', $s);
    }
    return (float)$s;
}

function imp_image_list($img, $base) {
    $out = [];
    if (is_string($img)) {
        $img = [$img];
    } elseif (is_array($img) && isset($img['url'])) {
        $img = [$img];
    }
    if (!is_array($img)) return $out;
    foreach ($img as $it) {
        if (is_array($it)) $it = $it['url'] ?? ($it['contentUrl'] ?? '');
        if (!is_string($it)) continue;
        $a = imp_abs($it, $base);
        if ($a !== '') $out[] = $a;
    }
    return $out;
}

/* ---------- extractors ---------- */

function imp_from_shopify($url) {
    $p = parse_url($url);
    if (!$p || empty($p['path']) || !preg_match('#/products/([^/?\#]+)#', $p['path'], $m)) return null;
    $root = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
    $r = imp_get($root . '/products/' . $m[1] . '.js', 1024 * 1024, $url);
    if (!$r) return null;
    $j = json_decode($r['body'], true);
    if (!is_array($j) || empty($j['title'])) return null;
    $imgs = [];
    foreach (($j['images'] ?? []) as $im) {
        if (is_string($im)) $imgs[] = imp_abs($im, $url);
    }
    return [
        'source' => 'shopify',
        'name' => (string)$j['title'],
        'description' => imp_clean_text($j['description'] ?? ''),
        'price' => isset($j['price']) ? ((float)$j['price']) / 100 : 0,
        'oldPrice' => isset($j['compare_at_price']) ? ((float)$j['compare_at_price']) / 100 : 0,
        'currency' => '',
        'images' => $imgs,
    ];
}

function imp_find_product_node($node) {
    if (!is_array($node)) return null;
    $type = $node['@type'] ?? '';
    $types = is_array($type) ? $type : [$type];
    foreach ($types as $t) {
        if (is_string($t) && strcasecmp($t, 'Product') === 0) return $node;
    }
    foreach ($node as $v) {
        if (is_array($v)) {
            $f = imp_find_product_node($v);
            if ($f) return $f;
        }
    }
    return null;
}

function imp_from_html($html, $url) {
    $res = ['source' => 'page', 'name' => '', 'description' => '', 'price' => 0, 'oldPrice' => 0, 'currency' => '', 'images' => []];

    // 1) JSON-LD
    if (preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $mm)) {
        foreach ($mm[1] as $raw) {
            $j = json_decode(trim(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8')), true);
            if (!is_array($j)) continue;
            $prod = imp_find_product_node($j);
            if (!$prod) continue;
            $res['source'] = 'jsonld';
            $res['name'] = (string)($prod['name'] ?? '');
            $res['description'] = imp_clean_text($prod['description'] ?? '');
            $res['images'] = imp_image_list($prod['image'] ?? [], $url);
            $offers = $prod['offers'] ?? null;
            if (is_array($offers)) {
                if (isset($offers[0]) && is_array($offers[0])) $offers = $offers[0];
                $res['price'] = imp_price($offers['price'] ?? ($offers['lowPrice'] ?? 0));
                $res['currency'] = (string)($offers['priceCurrency'] ?? '');
                if (!$res['price'] && isset($offers['priceSpecification']) && is_array($offers['priceSpecification'])) {
                    $ps = $offers['priceSpecification'];
                    if (isset($ps[0]) && is_array($ps[0])) $ps = $ps[0];
                    $res['price'] = imp_price($ps['price'] ?? 0);
                }
            }
            break;
        }
    }

    // 2) Open Graph / meta tags fallback
    $metas = [];
    if (preg_match_all('#<meta\s+[^>]*>#i', $html, $tags)) {
        foreach ($tags[0] as $tag) {
            $key = '';
            if (preg_match('#(?:property|name)=["\']([^"\']+)["\']#i', $tag, $k)) $key = strtolower($k[1]);
            if ($key === '') continue;
            if (preg_match('#content=["\']([^"\']*)["\']#i', $tag, $c)) $metas[$key][] = $c[1];
        }
    }
    if ($res['name'] === '') {
        $res['name'] = html_entity_decode($metas['og:title'][0] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($res['name'] === '' && preg_match('#<title[^>]*>(.*?)</title>#is', $html, $t)) {
            $res['name'] = trim(html_entity_decode(strip_tags($t[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }
    if ($res['description'] === '') {
        $d = $metas['og:description'][0] ?? ($metas['description'][0] ?? '');
        $res['description'] = imp_clean_text($d);
    }
    if (!$res['price']) {
        foreach (['product:price:amount', 'og:price:amount', 'twitter:data1'] as $pk) {
            if (!empty($metas[$pk][0])) {
                $res['price'] = imp_price($metas[$pk][0]);
                if ($res['price']) break;
            }
        }
        if (empty($res['currency'])) {
            $res['currency'] = (string)($metas['product:price:currency'][0] ?? ($metas['og:price:currency'][0] ?? ''));
        }
    }
    if (!$res['oldPrice'] && !empty($metas['product:original_price:amount'][0])) {
        $res['oldPrice'] = imp_price($metas['product:original_price:amount'][0]);
    }
    foreach (['og:image', 'og:image:secure_url', 'twitter:image'] as $ik) {
        foreach (($metas[$ik] ?? []) as $iu) {
            $a = imp_abs($iu, $url);
            if ($a !== '') $res['images'][] = $a;
        }
    }

    // 3) Last resort: plain <img> tags (only if we found very few images)
    if (count(array_unique($res['images'])) < 3 && preg_match_all('#<img\s[^>]*>#i', $html, $imgTags)) {
        foreach ($imgTags[0] as $tag) {
            $src = '';
            foreach (['data-zoom-image', 'data-large-image', 'data-src', 'src'] as $attr) {
                if (preg_match('#\s' . $attr . '=["\']([^"\']+)["\']#i', $tag, $s)) { $src = $s[1]; break; }
            }
            $a = imp_abs($src, $url);
            if ($a === '') continue;
            if (!preg_match('#\.(jpe?g|png|webp)(\?|$)#i', $a)) continue;
            if (preg_match('#logo|icon|sprite|avatar|badge|flag|payment|banner|placeholder|pixel|tracking#i', $a)) continue;
            $res['images'][] = $a;
            if (count($res['images']) >= 12) break;
        }
    }
    return $res;
}

/* ---------- image download into /uploads ---------- */

function imp_save_images($urls, $pageUrl, $uploadDir, $want) {
    $saved = [];
    $seen = [];
    $tries = 0;
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    foreach ($urls as $u) {
        if (count($saved) >= $want || $tries >= 14) break;
        $key = preg_replace('#\?.*$#', '', $u);
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $tries++;
        $r = imp_get($u, 8 * 1024 * 1024, $pageUrl);
        if (!$r || strlen($r['body']) < 4000) continue; // skip tiny pixels / icons
        $tmp = tempnam(sys_get_temp_dir(), 'imp');
        file_put_contents($tmp, $r['body']);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        @unlink($tmp);
        if (!isset($allowed[$mime])) continue;
        $hash = md5($r['body']);
        if (isset($seen[$hash])) continue;
        $seen[$hash] = true;
        $name = 'img_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        if (file_put_contents($uploadDir . '/' . $name, $r['body']) === false) continue;
        $saved[] = 'uploads/' . $name;
    }
    return $saved;
}

/* ---------- handler ---------- */

function imp_handle($UPLOAD) {
    @set_time_limit(120);
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $url = trim((string)($in['url'] ?? ''));
    if ($url === '') imp_fail('Product link daalo.');
    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . $url;
    if (!imp_check_url($url)) imp_fail('Ye link allowed nahi hai (sirf public http/https link chalega).');

    $data = imp_from_shopify($url);
    if (!$data) {
        $page = imp_get($url, 3 * 1024 * 1024);
        if (!$page) imp_fail('Page open nahi hua. Site bot ko block kar rahi hai ya link galat hai.');
        $data = imp_from_html($page['body'], $page['url']);
        $url = $page['url'];
    }

    $name = trim((string)$data['name']);
    if ($name === '' && empty($data['images'])) {
        imp_fail('Is link se product ka data nahi mil paya (site JavaScript se load hoti hai ya block karti hai).');
    }

    $imgs = imp_save_images(array_values(array_unique($data['images'])), $url, $UPLOAD, 5);

    out([
        'ok' => true,
        'source' => $data['source'],
        'name' => $name,
        'description' => $data['description'],
        'price' => $data['price'] ? round((float)$data['price'], 2) : 0,
        'oldPrice' => $data['oldPrice'] ? round((float)$data['oldPrice'], 2) : 0,
        'currency' => $data['currency'],
        'images' => $imgs,
    ]);
}
