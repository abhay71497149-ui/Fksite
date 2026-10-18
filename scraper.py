# -*- coding: utf-8 -*-
"""
Flipkart Product Importer - Flipkart Scraper
Paste a Flipkart product link -> get name, price, mrp, images, specs, reviews.
Note: Flipkart kabhi-kabhi anti-bot check dikhata hai. Thodi der baad try karein.
"""
import re, json, html
import requests
from urllib.parse import urlparse, parse_qs, urlencode

HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
                  "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
    "Accept-Language": "en-US,en;q=0.9,hi;q=0.8",
    "Referer": "https://www.google.com/",
    "Upgrade-Insecure-Requests": "1",
}
TIMEOUT = 30


class ScrapeError(Exception):
    pass


def fetch(url):
    try:
        r = requests.get(url, headers=HEADERS, timeout=TIMEOUT, allow_redirects=True)
    except requests.RequestException as e:
        raise ScrapeError("Page load nahi hua (network error): %s" % e)
    if r.status_code in (403, 419, 429, 503):
        raise ScrapeError("Source website ne request block ki (HTTP %s). Thodi der baad dobara try karein." % r.status_code)
    if r.status_code != 200:
        raise ScrapeError("Page load nahi hua (HTTP %s)." % r.status_code)
    low = r.text.lower()
    if "robot or human" in low or ("unusual traffic" in low):
        raise ScrapeError("Website ne CAPTCHA/anti-bot check dikhaya. Thodi der baad dobara try karein.")
    return r


def meta_content(txt, prop):
    m = re.search(r'<meta[^>]+(?:property|name)=["\']%s["\'][^>]+content=["\']([^"\']*)["\']' % re.escape(prop), txt, re.I)
    if not m:
        m = re.search(r'<meta[^>]+content=["\']([^"\']*)["\'][^>]+(?:property|name)=["\']%s["\']' % re.escape(prop), txt, re.I)
    return m.group(1) if m else None


def json_ld_items(txt):
    out = []
    for m in re.finditer(r'<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>', txt, re.S | re.I):
        raw = m.group(1).strip()
        try:
            data = json.loads(raw)
        except Exception:
            continue
        if isinstance(data, dict):
            if isinstance(data.get("@graph"), list):
                out.extend([x for x in data["@graph"] if isinstance(x, dict)])
            else:
                out.append(data)
        elif isinstance(data, list):
            out.extend([x for x in data if isinstance(x, dict)])
    return out


def initial_state(txt):
    """Parse Flipkart's initial state, tolerating JS undefined tokens."""
    # Flipkart pages have used both INITIAL_STATE spellings.
    for pattern in (r'window\.__INITIAL_STATE__\s*=\s*', r'window\.INITIAL_STATE\s*=\s*'):
        m = re.search(pattern, txt)
        if not m:
            continue
        i, depth, instr, esc = m.end(), 0, None, False
        while i < len(txt):
            ch = txt[i]
            if instr:
                if esc:
                    esc = False
                elif ch == "\\":
                    esc = True
                elif ch == instr:
                    instr = None
            else:
                if ch in "\"'":
                    instr = ch
                elif ch == "{":
                    depth += 1
                elif ch == "}":
                    depth -= 1
                    if depth == 0:
                        raw = re.sub(r'\bundefined\b', 'null', txt[m.end():i + 1])
                        try:
                            return json.loads(raw)
                        except Exception:
                            break
            i += 1

    # Fallback to Next.js page payload.
    m2 = re.search(r'<script[^>]+id=["\'](?:__NEXT_DATA__|NEXT_DATA)["\'][^>]*>(.*?)</script>', txt, re.S | re.I)
    if m2:
        try:
            raw = re.sub(r'\bundefined\b', 'null', m2.group(1))
            return json.loads(raw)
        except Exception:
            return None
    return None


def walk(obj):
    if isinstance(obj, dict):
        yield obj
        for v in obj.values():
            yield from walk(v)
    elif isinstance(obj, list):
        for v in obj:
            yield from walk(v)


def as_num(v):
    if isinstance(v, (int, float)) and not isinstance(v, bool):
        return float(v)
    if isinstance(v, str):
        try: return float(v.replace(",", "").replace("\u20b9", "").strip())
        except Exception: return None
    return None


def clean_name(nm):
    if not nm: return nm
    for sep in (" - Buy", " \u2013 Buy", " | Flipkart", " - Flipkart"):
        ix = nm.find(sep)
        if 5 < ix: nm = nm[:ix]
    return re.sub(r"\s+", " ", nm).strip()


def find_prices(state, jsonld_price):
    mrp = price = None
    for d in walk(state):
        for k, v in d.items():
            lk = k.lower()
            if lk in ("mrp", "maximumretailprice"):
                n = as_num(v if not isinstance(v, dict) else (v.get("value") or v.get("amount")))
                if n and n > 10: mrp = n
            if lk in ("finalprice", "sellingprice", "offerprice"):
                n = as_num(v if not isinstance(v, dict) else (v.get("value") or v.get("amount")))
                if n and n > 0: price = n
    if jsonld_price: price = jsonld_price
    if price and mrp and mrp < price: mrp = None
    return mrp, price


def _deep_images(obj, depth=0):
    """Nested dict/list ke andar se bhi image URLs nikaalo."""
    urls = []
    if depth > 3:
        return urls
    if isinstance(obj, str):
        if obj.startswith("http") and re.search(r'\.(jpg|jpeg|png|webp)|rukminim|flixcart', obj, re.I):
            urls.append(obj)
    elif isinstance(obj, dict):
        for key in ("url", "src", "imageUrl", "image"):
            u = obj.get(key)
            if isinstance(u, str) and u.startswith("http"):
                urls.append(u)
        for v in obj.values():
            urls.extend(_deep_images(v, depth + 1))
    elif isinstance(obj, list):
        for v in obj:
            urls.extend(_deep_images(v, depth + 1))
    out = []
    for u in urls:
        if u not in out:
            out.append(u)
    return out


def find_reviews(state, limit=12):
    """Extract review-like records from supported Flipkart state shapes."""
    seen, out = set(), []
    TEXT_KEYS = ("text", "reviewText", "comment", "feedback", "reviewDescription", "content", "body")
    TITLE_KEYS = ("title", "reviewTitle", "heading", "summary", "headline")
    NAME_KEYS = ("userName", "reviewer", "reviewerName", "customerName", "postedBy", "authorName")
    IMG_KEYS = ("images", "imageUrl", "imageUrls", "photos", "reviewImages", "attachments", "media", "imagesData", "reviewMedia")

    def pick_text(d):
        text, title = "", ""
        for k in TEXT_KEYS:
            v = d.get(k)
            if isinstance(v, str) and len(v.strip()) > len(text.strip()):
                text = v.strip()
        for k in TITLE_KEYS:
            v = d.get(k)
            if isinstance(v, str) and len(v.strip()) > len(title.strip()):
                title = v.strip()
        return text, title

    def pick_rating(d):
        r = as_num(d.get("rating"))
        if r is None and isinstance(d.get("rating"), dict):
            r = as_num(d["rating"].get("value") or d["rating"].get("ratingValue"))
        if r is None:
            for key in ("reviewRating", "ratingValue", "rate"):
                r = as_num(d.get(key))
                if r is not None:
                    break
        return r

    def pick_name(d):
        author = d.get("author")
        if isinstance(author, str) and author.strip():
            return author.strip()
        if isinstance(author, dict):
            n = author.get("name") or author.get("displayName")
            if isinstance(n, str) and n.strip():
                return n.strip()
        for k in NAME_KEYS:
            v = d.get(k)
            if isinstance(v, str) and v.strip():
                return v.strip()
            if isinstance(v, dict) and isinstance(v.get("name"), str) and v["name"].strip():
                return v["name"].strip()
        return ""

    def has_likes(d):
        for k in ("likes", "dislikes", "helpful", "helpfulCount"):
            v = d.get(k)
            if isinstance(v, (int, float)) and not isinstance(v, bool):
                return True
        return False

    for d in walk(state):
        rating = pick_rating(d)
        text, title = pick_text(d)
        name = pick_name(d)
        likes_flag = has_likes(d)
        # Flipkart kabhi rating parent dict pe aur text nested dict me rakhta hai:
        # {"rating": 5, "review": {"text": "...", ...}}
        if len(text) < 10 and rating is not None and 1 <= rating <= 5:
            for v in d.values():
                if isinstance(v, dict):
                    t2, ti2 = pick_text(v)
                    if len(t2) >= 10:
                        text = t2
                        if not title:
                            title = ti2
                        if not name:
                            name = pick_name(v)
                        if not likes_flag:
                            likes_flag = has_likes(v)
                        break
        if len(text) < 10:
            continue
        # review tabhi maano jab rating/author/likes me se kuch ho
        if rating is None and not name and not likes_flag:
            continue
        imgs = []
        for k in IMG_KEYS:
            v = d.get(k)
            if v is None:
                continue
            for u in _deep_images(v):
                if u not in imgs:
                    imgs.append(u)
        key = (title[:60].lower(), text[:60].lower())
        if key in seen:
            continue
        seen.add(key)
        likes = d.get("likes") or d.get("helpful") or d.get("helpfulCount") or 0
        out.append({"r": int(round(rating)) if rating is not None and 1 <= rating <= 5 else 4,
                    "t": title[:140], "x": text[:2000], "n": str(name)[:60] or "Verified Buyer",
                    "h": int(as_num(likes) or 0), "d": int(as_num(d.get("days") or d.get("age") or 0) or 0),
                    "ph": len(imgs), "imgs": imgs[:4]})
        if len(out) >= limit:
            break
    return out


def json_script_states(txt):
    """Read JSON payloads embedded in script tags, not only __INITIAL_STATE__."""
    states = []
    for m in re.finditer(r'<script\b([^>]*)>(.*?)</script\s*>', txt, re.S | re.I):
        attrs, raw = m.group(1), m.group(2).strip()
        if not raw or len(raw) > 15_000_000: continue
        typ = re.search(r'type=["\']([^"\']+)', attrs, re.I)
        ident = re.search(r'id=["\']([^"\']+)', attrs, re.I)
        candidate = (typ and ('json' in typ.group(1).lower())) or (ident and ident.group(1) in ('__NEXT_DATA__', '__NUXT_DATA__'))
        if not candidate and not raw.startswith(('{', '[')): continue
        try:
            data = json.loads(raw)
            if isinstance(data, (dict, list)): states.append(data)
        except Exception:
            continue
    return states


def html_reviews(txt, limit=12):
    """Conservative fallback for review text exposed in rendered HTML."""
    out, seen = [], set()
    # Keep this deliberately narrow so product descriptions aren't mislabelled as reviews.
    for m in re.finditer(r'(?is)(?:reviewText|review-title|reviewTitle)["\'\s:=<>]+([^<]{8,1200})', txt):
        text = html.unescape(re.sub(r'\\u[0-9a-fA-F]{4}', ' ', m.group(1))).strip()
        text = re.sub(r'\\s+', ' ', text)
        key = text[:100].lower()
        if key in seen or len(text) < 8: continue
        seen.add(key)
        out.append({"r": 5, "t": "", "x": text, "n": "Verified Buyer", "h": 0, "d": 0, "ph": 0, "imgs": []})
        if len(out) >= limit: break
    return out


def _flatten_spec_value(v):
    """Flipkart spec values string/list/dict kisi bhi form me ho sakte hain."""
    if v is None:
        return ""
    if isinstance(v, bool):
        return ""
    if isinstance(v, (str, int, float)):
        return str(v).strip()[:600]
    if isinstance(v, list):
        parts = []
        for it in v:
            t = _flatten_spec_value(it)
            if t:
                parts.append(t)
        return ", ".join(parts)[:600]
    if isinstance(v, dict):
        for k in ("text", "value", "name", "label"):
            t = _flatten_spec_value(v.get(k))
            if t:
                return t
        return _flatten_spec_value(v.get("values") or v.get("options"))
    return ""


def _spec_text(v):
    """Flatten Flipkart specification values without losing nested value arrays."""
    if v is None or isinstance(v, bool):
        return ""
    if isinstance(v, (str, int, float)):
        return re.sub(r"\s+", " ", str(v)).strip()[:1200]
    if isinstance(v, list):
        parts = []
        for item in v:
            t = _spec_text(item)
            if t and t not in parts:
                parts.append(t)
        return ", ".join(parts)[:1200]
    if isinstance(v, dict):
        # Affiliate/API schema sometimes wraps each value as {key:'', value:[...]}.
        if "value" in v:
            t = _spec_text(v.get("value"))
            if t:
                return t
        for k in ("text", "name", "label", "displayValue", "values", "options"):
            if k in v:
                t = _spec_text(v.get(k))
                if t:
                    return t
        return ""
    return ""


def _dedupe_spec_add(out, seen, key, value, limit=100):
    key = re.sub(r"\s+", " ", str(key or "")).strip(" :\t\r\n")
    value = _spec_text(value)
    if not key or not value or key.lower() in {"specifications", "product specifications", "general", "features", "key features", "highlights"}:
        return
    if re.fullmatch(r"#[0-9a-fA-F]{3,8}", value.strip()) and key.lower() in {"brand", "model", "color", "colour", "model name", "model number"}:
        return
    if len(key) > 120 or len(value) > 1200:
        return
    # Do not accept style/internal tokens as product specifications.
    if key.lower() in {"class", "style", "color token", "foreground", "background"}:
        return
    token = (key.lower(), value.lower())
    if token not in seen and len(out) < limit:
        out.append([key, value])
        seen.add(token)


def find_highlights_specs(state, full=False):
    """Read actual structured Flipkart spec groups first, then conservative key/value fallbacks."""
    hl, spec = [], []
    max_specs = 100 if full else 15
    seen = set()
    nodes = list(walk(state))

    # Key highlights are usually an explicit list, not arbitrary strings in page state.
    for node in nodes:
        h = node.get("highlights") or node.get("keySpecs") or node.get("keySpecsStr")
        hlist = h if isinstance(h, list) else (h.get("list") if isinstance(h, dict) else None)
        if isinstance(h, str):
            hlist = [x.strip() for x in re.split(r"[\n•|]", h) if x.strip()]
        if isinstance(hlist, list):
            for item in hlist:
                text = _spec_text(item)
                if text and 5 < len(text) < 500 and text not in hl:
                    hl.append(text)
                if len(hl) >= 20:
                    break

    # Flipkart commonly nests specificationList -> section -> values -> {key,value}.
    # Also handle "specifications"/"productSpecifications" schemas used by different page versions.
    list_names = {
        "specificationlist", "specifications", "productspecifications",
        "detailedspecs", "detailedspecifications", "attributes",
        "technicalspecifications", "specificationgroups", "productdetails"
    }
    for node in nodes:
        for name, value in node.items():
            if str(name).lower() not in list_names:
                continue
            if isinstance(value, dict):
                # Some payloads wrap the real list under items/data/values.
                candidate = next((value.get(k) for k in ("items", "data", "values", "list", "groups", "specifications") if isinstance(value.get(k), (list, dict))), value)
                entries = candidate if isinstance(candidate, list) else [candidate]
            elif isinstance(value, list):
                entries = value
            else:
                continue
            for entry in entries:
                if not isinstance(entry, dict):
                    continue
                section = entry.get("key") or entry.get("title") or entry.get("name") or entry.get("heading") or ""
                children = entry.get("values") or entry.get("specifications") or entry.get("items") or entry.get("attributes")
                # Leaf key/value row.
                leaf_key = entry.get("key") or entry.get("name") or entry.get("label") or entry.get("title")
                leaf_value = entry.get("value") or entry.get("displayValue") or entry.get("values")
                if leaf_key and leaf_value is not None and not isinstance(leaf_value, (dict, list)):
                    _dedupe_spec_add(spec, seen, leaf_key, leaf_value, max_specs)
                elif leaf_key and isinstance(leaf_value, (dict, list)) and not children:
                    # Leaf rows may wrap values as [{key:'', value:['...']}]; group headings are skipped.
                    flattened = _spec_text(leaf_value)
                    if flattened and str(leaf_key).lower() not in {"general", "specifications", "product details"}:
                        _dedupe_spec_add(spec, seen, leaf_key, flattened, max_specs)
                if isinstance(children, dict):
                    children = list(children.values())
                if isinstance(children, list):
                    for child in children:
                        if not isinstance(child, dict):
                            continue
                        ck = child.get("key") or child.get("name") or child.get("label") or child.get("title")
                        cv = child.get("value")
                        if cv is None:
                            cv = child.get("values") or child.get("displayValue")
                        if ck and cv is not None:
                            _dedupe_spec_add(spec, seen, ck, cv, max_specs)
                        # One more nested level is used by some Flipkart payloads.
                        if isinstance(cv, list):
                            for sub in cv:
                                if isinstance(sub, dict):
                                    sk = sub.get("key") or sub.get("name") or sub.get("label")
                                    sv = sub.get("value") or sub.get("values")
                                    if sk and sv is not None:
                                        _dedupe_spec_add(spec, seen, sk, sv, max_specs)

    # Fallback: only accept key/value objects if they resemble real attribute rows.
    # Avoid indiscriminately collecting arbitrary UI state keys such as color tokens.
    if len(spec) < (max_specs if full else 15):
        for node in nodes:
            key = node.get("key") or node.get("name") or node.get("label")
            if not isinstance(key, str) or not (1 < len(key.strip()) < 100):
                continue
            if "value" not in node and "values" not in node:
                continue
            value = node.get("value", node.get("values"))
            # Do not treat a specification section heading with a list of child
            # rows as a specification itself (e.g. "Display Features: No, OLED...").
            # The individual child rows are parsed above and are more useful.
            if isinstance(value, list) and value and all(
                isinstance(item, dict) and any(k in item for k in ("key", "name", "label", "title"))
                for item in value
            ):
                continue
            text = _spec_text(value)
            if not text:
                continue
            # Ignore obvious internal color tokens / hex values pretending to be a brand.
            if re.fullmatch(r"#[0-9a-fA-F]{3,8}", text) and key.strip().lower() in {"brand", "model", "color", "colour"}:
                continue
            _dedupe_spec_add(spec, seen, key, text, max_specs)

    return hl[:20], spec[:max_specs]

def find_attr(state, names):
    for d in walk(state):
        for n in names:
            v = d.get(n)
            if isinstance(v, str) and 2 < len(v) < 60:
                if re.fullmatch(r"#[0-9a-fA-F]{3,8}", v.strip()):
                    continue
                if n.lower() in ("brand", "brandname") and v.strip().lower() in ("brand", "unknown", "null"):
                    continue
                return v
            if isinstance(v, dict):
                s = v.get("value") or v.get("name")
                if isinstance(s, str) and 2 < len(s) < 60 and not re.fullmatch(r"#[0-9a-fA-F]{3,8}", s.strip()):
                    return s
    return ""


def build_reviews_url(final_url):
    u = urlparse(final_url)
    if "/p/" not in u.path or "/product-reviews/" in u.path: return None
    qs = parse_qs(u.query)
    q = {"marketplace": "FLIPKART"}
    if qs.get("pid"): q["pid"] = qs["pid"][0]
    if qs.get("lid"): q["lid"] = qs["lid"][0]
    if not q.get("pid"): return None
    slug = u.path.split("/p/")[-1]
    return "%s://%s/product-reviews/%s?%s" % (u.scheme, u.netloc, slug, urlencode(q))



def _valid_image_url(value):
    if not isinstance(value, str):
        return False
    return value.startswith(('https://', 'http://')) and bool(re.search(r'\.(?:jpg|jpeg|png|webp)(?:[?#]|$)', value, re.I))


def _variant_price(obj, keys):
    """Read prices from direct or nested Flipkart variant price structures."""
    if not isinstance(obj, dict):
        return 0
    wanted = {str(k).lower() for k in keys}
    # Prefer explicit price fields anywhere inside this variant object, including
    # structures such as pricing.finalPrice.value or price.value.
    for node in walk(obj):
        for key, value in node.items():
            if str(key).lower() not in wanted:
                continue
            if isinstance(value, dict):
                value = value.get('value') or value.get('amount') or value.get('price') or value.get('min')
            if isinstance(value, dict):
                value = value.get('value') or value.get('amount')
            number = as_num(value)
            if number is not None and number > 0:
                return int(number)
    return 0


def find_variants(state):
    """Extract only clearly structured variant records; never treat arbitrary state labels as sizes."""
    found, seen = [], set()
    variant_list_keys = {'variants', 'sizevariants', 'colorvariants', 'colourvariants', 'productfamily', 'variantoptions', 'availablevariants'}
    price_keys = ('sellingPrice', 'finalPrice', 'offerPrice', 'price', 'currentPrice', 'specialPrice', 'effectivePrice', 'dealPrice', 'salePrice')
    mrp_keys = ('mrp', 'maximumRetailPrice', 'maximumSellingPrice', 'listPrice', 'originalPrice', 'markedPrice')
    label_keys = ('variantName', 'displayName', 'productTitle', 'title', 'name', 'size', 'storage', 'value', 'label')
    for parent in walk(state):
        for key, value in parent.items():
            if key.lower() not in variant_list_keys or not isinstance(value, list):
                continue
            for obj in value:
                if not isinstance(obj, dict):
                    continue
                label = ''
                for lk in label_keys:
                    candidate = obj.get(lk)
                    if isinstance(candidate, str) and 1 <= len(candidate.strip()) <= 100:
                        candidate = re.sub(r'\s+', ' ', candidate).strip()
                        if candidate and candidate.lower() not in ('s', 'm', 'l', 'xl', 'color', 'colour', 'size', 'variant'):
                            label = candidate
                            break
                # Only accept records that look like actual purchasable variants or explicit sizes.
                price = _variant_price(obj, price_keys)
                mrp = _variant_price(obj, mrp_keys)
                has_product_link = any(isinstance(obj.get(k), str) and ('/p/' in obj.get(k) or 'pid=' in obj.get(k)) for k in ('productUrl', 'url', 'href', 'canonicalUrl'))
                has_variant_id = any(obj.get(k) for k in ('productId', 'pid', 'listingId', 'variantId', 'skuId'))
                has_size = any(isinstance(obj.get(k), str) and re.search(r'\b(?:\d+\s?(?:GB|TB|MB|inch|cm)|XS|S|M|L|XL|XXL|XXXL|Free Size)\b', obj.get(k), re.I) for k in label_keys)
                if not label or not (price > 0 or has_product_link or has_variant_id or has_size):
                    continue
                low = label.lower()
                if low in seen:
                    continue
                seen.add(low)
                found.append({'l': label, 'p': price, 'mrp': mrp or price, 'available': bool(price > 0)})
                if len(found) >= 16:
                    return found
    return found



def find_size_chart(state):
    """Flipkart state me sizeChart table dhondo aur rows nikaalo."""
    out = []
    for d in walk(state):
        sc = d.get("sizeChart") or d.get("size_chart") or d.get("sizeChartData")
        if not isinstance(sc, (dict, list)):
            continue
        rows = sc.get("rows") if isinstance(sc, dict) else sc
        if not isinstance(rows, list):
            continue
        for row in rows:
            if isinstance(row, dict):
                vals = [str(v) for v in row.values() if v is not None]
            elif isinstance(row, list):
                vals = [str(v) for v in row]
            else:
                continue
            vals = [v for v in vals if v and v.lower() != "none"]
            if len(vals) >= 2 and vals not in out:
                out.append(vals)
        if out:
            break
    return out[:12]

def find_colors(state):
    """Extract only plausible named colors with their own image; reject CSS/internal tokens."""
    out, seen = [], set()
    color_keys = ('colorName', 'colourName', 'color', 'colour', 'displayColor', 'colorValue', 'colourValue')
    image_keys = ('imageUrl', 'image', 'thumbnail', 'swatchImage', 'imageSrc', 'src')
    bad_tokens = {'foreground.success.highlight', 'foreground', 'success', 'highlight', 'primary', 'secondary', 'default', 'inherit', 'transparent', 'currentcolor', 'color'}
    color_words = re.compile(r'\b(black|white|blue|red|green|pink|purple|silver|grey|gray|gold|glacier|midnight|natural|titanium|beige|brown|cream|yellow|orange|lavender|teal|starlight|space black|deep purple|graphite|rose gold|burgundy|frost|obsidian|porcelain|jade|peony|ultramarine)\b', re.I)
    for obj in walk(state):
        label = ''
        for key in color_keys:
            value = obj.get(key)
            if isinstance(value, str) and 1 <= len(value.strip()) <= 40:
                candidate = re.sub(r'\s+', ' ', value.strip())
                if candidate.lower() not in bad_tokens and color_words.search(candidate):
                    label = candidate
                    break
        if not label:
            continue
        image = ''
        for key in image_keys:
            value = obj.get(key)
            if _valid_image_url(value):
                image = value
                break
            if isinstance(value, dict):
                for subkey in ('url', 'src', 'imageUrl', 'value'):
                    if _valid_image_url(value.get(subkey)):
                        image = value[subkey]
                        break
            if image:
                break
        # Do not show color chips that have no matching image: using the main image duplicates colors.
        if not image:
            continue
        price = _variant_price(obj, ('sellingPrice', 'finalPrice', 'price', 'offerPrice'))
        low = label.lower()
        if low not in seen:
            out.append({'l': label, 'img': image, 'p': price})
            seen.add(low)
        if len(out) >= 12:
            break
    return out

def _html_to_text(fragment):
    fragment = re.sub(r'(?is)<(script|style|noscript).*?>.*?</\1>', ' ', fragment or '')
    fragment = re.sub(r'(?i)<br\s*/?>|</(?:td|th|tr|li|p|div|h[1-6])\s*>', ' | ', fragment)
    fragment = re.sub(r'<[^>]+>', ' ', fragment)
    fragment = html.unescape(fragment)
    fragment = re.sub(r'\s+', ' ', fragment).strip(' |\t\r\n')
    return fragment


def _html_table_specs(txt, limit=100):
    """Extract key/value specs from Flipkart HTML tables and common label/value layouts."""
    out, seen = [], set()
    source = txt or ""
    # Standard table rows.
    for row in re.findall(r"(?is)<tr\b[^>]*>(.*?)</tr>", source):
        cells = re.findall(r"(?is)<(?:td|th)\b[^>]*>(.*?)</(?:td|th)>", row)
        vals = [_html_to_text(c) for c in cells]
        vals = [v for v in vals if v]
        if len(vals) >= 2:
            _dedupe_spec_add(out, seen, vals[0], " | ".join(vals[1:]), limit)
    # Label/value sibling elements used by product-detail layouts.
    pattern = r"(?is)<(?P<tag>div|span|th|dt)\b(?P<attrs>[^>]*(?:class|id)=['\"][^'\"]*(?:label|attribute|specification|detail|key|row|name)[^'\"]*['\"][^>]*)>(?P<key>.*?)</(?P=tag)>\s*<(?:div|span|td|dd)\b[^>]*>(?P<value>.*?)</(?:div|span|td|dd)>"
    for m in re.finditer(pattern, source):
        key, value = _html_to_text(m.group("key")), _html_to_text(m.group("value"))
        if 1 < len(key) < 120 and 1 < len(value) < 1200:
            _dedupe_spec_add(out, seen, key, value, limit)
    # Some layouts place multiple cells inside a classed row.
    row_pattern = r"(?is)<div\b[^>]*(?:class|data-testid)=['\"][^'\"]*(?:specification|attribute|detail|row)[^'\"]*['\"][^>]*>(.*?)</div\s*>"
    for row in re.findall(row_pattern, source):
        cells = re.findall(r"(?is)<(?:div|span|td|th)\b[^>]*>(.*?)</(?:div|span|td|th)>", row)
        vals = [_html_to_text(c) for c in cells]
        vals = [v for v in vals if 1 < len(v) < 1200]
        if len(vals) >= 2 and len(vals[0]) < 120:
            _dedupe_spec_add(out, seen, vals[0], vals[1], limit)
    return out[:limit]


def _amazon_product(url, max_reviews=10, include_specifications=True):
    """Amazon.in/.com best-effort parser. Uses public page HTML/JSON-LD; does not bypass CAPTCHA."""
    r = fetch(url)
    final_url, txt = r.url, r.text
    low = txt.lower()
    if 'robot check' in low or 'captcha' in low and 'producttitle' not in low:
        raise ScrapeError('Amazon ne CAPTCHA/Robot Check dikhaya; is request par product data available nahi hua.')
    json_products = []
    for item in json_ld_items(txt):
        t = item.get('@type'); types = t if isinstance(t, list) else [t]
        if any(str(x).lower() == 'product' for x in types):
            json_products.append(item)
    product = json_products[0] if json_products else {}
    name = product.get('name') or meta_content(txt, 'og:title')
    if not name:
        m = re.search(r'(?is)<span[^>]+id=["\']productTitle["\'][^>]*>(.*?)</span>', txt)
        name = _html_to_text(m.group(1)) if m else None
    name = clean_name(html.unescape(name or '')) if name else ''
    if not name:
        raise ScrapeError('Amazon page se product ka naam nahi mila. Link check karo; Amazon CAPTCHA bhi ho sakta hai.')

    images = []
    img = product.get('image')
    for item in ([img] if isinstance(img, str) else img if isinstance(img, list) else []):
        u = item if isinstance(item, str) else (item or {}).get('url')
        if isinstance(u, str) and u.startswith('http') and u not in images: images.append(u)
    for pattern in (r'(?is)<img[^>]+id=["\']landingImage["\'][^>]+(?:data-old-hires|src)=["\']([^"\']+)', r'(?is)<img[^>]+(?:data-old-hires|id=["\']main-image["\'])[^>]+src=["\']([^"\']+)'):
        for u in re.findall(pattern, txt):
            u = html.unescape(u)
            if u.startswith('http') and u not in images: images.append(u)
    if not images:
        og = meta_content(txt, 'og:image')
        if og: images.append(html.unescape(og))

    price = mrp = rating = None; rc = 0
    offers = product.get('offers')
    for offer in ([offers] if isinstance(offers, dict) else offers if isinstance(offers, list) else []):
        if not isinstance(offer, dict): continue
        price = price or as_num(offer.get('price') or offer.get('lowPrice'))
        mrp = mrp or as_num(offer.get('highPrice'))
    if not price:
        for pattern in (r'(?is)id=["\']priceblock_ourprice["\'][^>]*>(.*?)</', r'(?is)id=["\']priceblock_dealprice["\'][^>]*>(.*?)</', r'(?is)class=["\'][^"\']*a-price-whole[^"\']*["\'][^>]*>(.*?)</'):
            m = re.search(pattern, txt)
            if m:
                price = as_num(re.sub(r'[^0-9.]', '', _html_to_text(m.group(1))))
                if price: break
    if not price:
        for key in ('price', 'product:price:amount'):
            val = meta_content(txt, key)
            if val and as_num(val): price = as_num(val); break
    ar = product.get('aggregateRating') or {}
    if isinstance(ar, dict):
        rating = as_num(ar.get('ratingValue'))
        rc = int(as_num(ar.get('reviewCount') or ar.get('ratingCount')) or 0)
    if not rating:
        m = re.search(r'(?is)id=["\']acrPopover["\'][^>]+title=["\']([0-9.]+) out of', txt)
        if m: rating = as_num(m.group(1))
    if not rc:
        m = re.search(r'(?is)id=["\']acrCustomerReviewText["\'][^>]*>(.*?)</span>', txt)
        if m:
            digits = re.sub(r'\D', '', _html_to_text(m.group(1)))
            rc = int(digits) if digits else 0

    desc = product.get('description') or meta_content(txt, 'og:description') or meta_content(txt, 'description') or ''
    spec, seen = [], set()
    if include_specifications:
        # Schema.org PropertyValue is the cleanest source when present.
        props = product.get('additionalProperty') or product.get('additionalProperties') or []
        if isinstance(props, dict): props = [props]
        for prop in props if isinstance(props, list) else []:
            if isinstance(prop, dict):
                key = prop.get('name') or prop.get('propertyID') or prop.get('description')
                value = prop.get('value') or prop.get('valueReference')
                if key and value is not None: _dedupe_spec_add(spec, seen, key, value, 100)
        # Amazon technical details tables, feature/detail rows and key-value pairs.
        for key, value in _html_table_specs(txt, 100):
            _dedupe_spec_add(spec, seen, key, value, 100)
        # Additional property-like JSON embedded outside JSON-LD.
        for key, value in re.findall(r'(?is)["\'](?:name|label)["\']\s*:\s*["\']([^"\']{2,100})["\']\s*,\s*["\'](?:value|displayValue)["\']\s*:\s*["\']([^"\']{1,500})["\']', txt):
            _dedupe_spec_add(spec, seen, key, value, 100)
        # Meta/structured basics are useful even if the spec table is sparse.
        for key, value in (('Brand', product.get('brand')), ('Model', product.get('model')), ('SKU', product.get('sku'))):
            if isinstance(value, dict): value = value.get('name') or value.get('value')
            if value: _dedupe_spec_add(spec, seen, key, value, 100)
    brand = product.get('brand') or ''
    if isinstance(brand, dict): brand = brand.get('name') or ''
    model = product.get('model') or ''
    if isinstance(model, dict): model = model.get('name') or ''
    color = product.get('color') or ''
    if isinstance(color, dict): color = color.get('name') or ''
    hl = []
    for m in re.findall(r'(?is)<li[^>]+class=["\'][^"\']*(?:a-spacing-mini|a-size-base|feature)[^"\']*["\'][^>]*>(.*?)</li>', txt):
        val = _html_to_text(m)
        if val and 8 < len(val) < 500 and val not in hl: hl.append(val)
        if len(hl) >= 20: break
    reviews = []
    # Preserve product schema values expected by existing admin UI.
    return {
        'n': name[:150], 'brand': str(brand or ''), 'model': str(model or ''), 'color': str(color or ''),
        'variants': [], 'colors': [], 'chart': [], 'mrp': int(mrp or price or 0), 'p': int(price or 0),
        'r': round(rating, 1) if rating else 4.3, 'rc': rc, 'd': _html_to_text(desc)[:2000],
        'img': images[0] if images else '', 'imgs': images[:10], 'hl': hl[:20], 'spec': spec[:100],
        'tag': 'Imported', 'reviews': reviews[:max_reviews], 'src': final_url
    }


def scrape_product(url, max_reviews=10, include_specifications=True):
    host = (urlparse(url).hostname or '').lower()
    if host == 'amazon.in' or host.endswith('.amazon.in') or host == 'amazon.com' or host.endswith('.amazon.com') or host in ('amzn.in', 'amzn.to'):
        return _amazon_product(url, max_reviews=max_reviews, include_specifications=include_specifications)
    if not ('flipkart.com' in host or host.endswith('flipkart.net')):
        raise ScrapeError('Sirf Flipkart aur Amazon product links supported hain.')
    r = fetch(url)
    final_url, txt = r.url, r.text
    state = initial_state(txt)
    embedded_states = json_script_states(txt)
    if state is not None:
        embedded_states.insert(0, state)

    name = images = price = mrp = rating = rc = desc = None
    jsonld_brand = ""

    for item in json_ld_items(txt):
        t = item.get("@type"); types = t if isinstance(t, list) else [t]
        if "Product" not in types: continue
        name = name or item.get("name")
        raw_brand = item.get("brand")
        if isinstance(raw_brand, dict):
            raw_brand = raw_brand.get("name") or raw_brand.get("@name")
        if isinstance(raw_brand, str) and 1 < len(raw_brand.strip()) < 80:
            jsonld_brand = jsonld_brand or raw_brand.strip()
        img = item.get("image")
        for im in ([img] if isinstance(img, str) else (img or []) if isinstance(img, list) else []):
            u = im if isinstance(im, str) else (im or {}).get("url")
            if isinstance(u, str) and u.startswith("http") and u not in (images or []):
                images = images or []; images.append(u)
        offers = item.get("offers")
        for o in ([offers] if isinstance(offers, dict) else (offers or [])):
            if not isinstance(o, dict): continue
            p = as_num(o.get("price"))
            if p and not price: price = p
            hp = as_num(o.get("highPrice"))
            if hp and not mrp: mrp = hp
        ar = item.get("aggregateRating")
        if isinstance(ar, dict):
            rv = as_num(ar.get("ratingValue"))
            if rv and not rating: rating = rv
            c = as_num(ar.get("reviewCount") or ar.get("ratingCount"))
            if c and not rc: rc = int(c)
        d = item.get("description")
        if isinstance(d, str) and len(d) > 40: desc = desc or d

    # --- fallbacks: meta tags ---
    og_title = meta_content(txt, "og:title")
    if not name and og_title: name = og_title
    if not desc: desc = meta_content(txt, "og:description") or meta_content(txt, "description")
    if not images:
        og_img = meta_content(txt, "og:image")
        if og_img: images = [og_img]

    if not name:
        raise ScrapeError("Product ka naam nahi mila. Link sahi Flipkart product link hai? Dobara try karein.")

    # --- prices from embedded state ---
    if state is not None:
        s_mrp, s_price = find_prices(state, price)
        mrp = mrp or s_mrp
        price = s_price or price
        if not rating:
            rv = find_attr(state, ["averageRating", "ratingValue"])
            try: rating = float(rv) if rv else None
            except Exception: pass
        if not rc:
            c = find_attr(state, ["totalRating", "ratingCount", "reviewCount"])
            try: rc = int(float(c)) if c else None
            except Exception: pass

    if not desc and state is not None:
        for d in walk(state):
            for k in ("productDescription", "description", "about"):
                v = d.get(k)
                if isinstance(v, str) and len(v) > 80:
                    desc = v; break
            if desc: break

    hl, spec = ([], [])
    brand = model = color = ""
    primary_spec = []
    variants = []
    colors = []
    reviews = []
    if state is not None:
        hl, spec = find_highlights_specs(state, full=include_specifications)
        primary_spec = list(spec)
        brand = find_attr(state, ["productBrand", "brand", "brandName"])
        model = find_attr(state, ["model", "modelName", "modelNumber"])
        color = find_attr(state, ["color", "colour", "colorCode"])
        variants = find_variants(state)
        colors = find_colors(state)
    # Search every embedded JSON payload; Flipkart's specification keys vary by page.
    if include_specifications:
        seen_specs = {str(x[0]).strip().lower() + '|' + str(x[1]).strip().lower() for x in spec}
        for payload in embedded_states:
            _, extra_specs = find_highlights_specs(payload, full=True)
            for pair in extra_specs:
                key = str(pair[0]).strip().lower() + '|' + str(pair[1]).strip().lower()
                if key not in seen_specs:
                    spec.append(pair); seen_specs.add(key)
                if len(spec) >= 100: break
            if len(spec) >= 100: break
    else:
        spec = spec[:15]
    # Search all embedded JSON payloads because Flipkart's page state key changes over time.
    for payload in embedded_states:
        extra = find_reviews(payload, limit=max_reviews + 8)
        seen = {(x["t"][:60].lower(), x["x"][:100].lower()) for x in reviews}
        for x in extra:
            key = (x["t"][:60].lower(), x["x"][:100].lower())
            if key not in seen:
                reviews.append(x); seen.add(key)
            if len(reviews) >= max_reviews + 8: break
        if len(reviews) >= max_reviews + 8: break

    # --- reviews listing page (5-10 reviews ke liye) ---
    ru = build_reviews_url(final_url)
    if ru:
        try:
            rr = fetch(ru)
            review_states = json_script_states(rr.text)
            rstate = initial_state(rr.text)
            if rstate is not None: review_states.insert(0, rstate)
            extra = []
            for payload in review_states:
                extra.extend(find_reviews(payload, limit=max_reviews + 8))
                if len(extra) >= max_reviews + 8: break
            if not extra: extra = html_reviews(rr.text, limit=max_reviews + 8)
            seen = {(x["t"][:60].lower(), x["x"][:100].lower()) for x in reviews}
            for x in extra:
                key = (x["t"][:60].lower(), x["x"][:100].lower())
                if key not in seen:
                    reviews.append(x); seen.add(key)
                if len(reviews) >= max_reviews + 8: break
        except ScrapeError:
            pass
        except Exception:
            pass

    # HTML-rendered specification tables fallback: some Flipkart responses omit specs from JS state.
    if include_specifications:
        seen_specs = {str(x[0]).strip().lower() + '|' + str(x[1]).strip().lower() for x in spec}
        for pair in _html_table_specs(txt, 100):
            key = str(pair[0]).strip().lower() + '|' + str(pair[1]).strip().lower()
            if key not in seen_specs:
                spec.append(pair); seen_specs.add(key)
            if len(spec) >= 100: break

    # Brand extraction: use the main product payload only. Do not read Brand from the
    # merged specs list because embedded JSON can contain recommendation/carousel products
    # (e.g. a camera page accidentally receiving "Pizza Maker" from another product).
    primary_spec_brand = next((v for k, v in primary_spec
                               if str(k).strip().lower() in ("brand", "brand name", "brandname") and v), "")
    brand = ""
    for candidate in (jsonld_brand, find_attr(state, ["productBrand", "brand", "brandName"]) if state is not None else "", primary_spec_brand):
        candidate = re.sub(r"\s+", " ", str(candidate or "")).strip()
        if not candidate or candidate.casefold() == str(name or "").casefold():
            continue
        # Reject category/product-type phrases mistakenly picked as the manufacturer.
        if candidate.casefold() in {"pizza maker", "camera", "digital camera", "webcam", "mobile phone", "smartphone", "laptop", "monitor", "television", "tv", "refrigerator", "washing machine", "air conditioner", "microwave oven", "mixer grinder", "pressure cooker", "electric kettle", "headphones", "earphones", "speaker", "watch", "printer", "projector"}:
            continue
        if re.search(r"\b(online|buy|price|offer|sale|best)\b", candidate, re.I):
            continue
        brand = candidate
        break
    # Keep every unique detailed specification, up to 100 rows.
    spec_keys = {str(k).strip().lower() for k, v in spec}
    for label, val in (("Brand", brand), ("Model", model), ("Color", color)):
        if val and label.lower() not in spec_keys and len(spec) < (100 if include_specifications else 15):
            spec.append([label, val]); spec_keys.add(label.lower())

    imgs = []
    for u in (images or [])[:10]:
        if u not in imgs: imgs.append(u)

    # Best-effort discovery of related product links embedded in the source page.
    related_urls = []
    source_host = "www.flipkart.com" if "flipkart" in final_url.lower() else "www.amazon.in"
    for href in re.findall(r'''(?:href|data-href)=[\"']([^\"']+)[\"']''', txt, re.I):
        candidate = html.unescape(href).split("?")[0]
        if candidate.startswith("//"):
            candidate = "https:" + candidate
        elif candidate.startswith("/"):
            candidate = "https://" + source_host + candidate
        low_url = candidate.lower()
        is_product = (("flipkart.com/" in low_url and "/p/" in low_url) or
                      ("amazon." in low_url and ("/dp/" in low_url or "/gp/product/" in low_url)))
        if is_product and candidate not in related_urls and candidate.rstrip("/") != final_url.rstrip("/"):
            related_urls.append(candidate)
        if len(related_urls) >= 20:
            break

    prod = {
        "related_urls": related_urls,
        "n": clean_name(name)[:150],
        "brand": brand, "model": model, "color": color,
        "variants": variants, "colors": colors,
        "chart": find_size_chart(state) if state is not None else [],
        "mrp": int(mrp) if mrp else (int(price) if price else 0),
        "p": int(price) if price else 0,
        "r": round(rating, 1) if rating else 4.3,
        "rc": rc or 0,
        "d": (desc or "")[:2000],
        "img": imgs[0] if imgs else "",
        "imgs": imgs,
        "hl": hl, "spec": spec,
        "tag": "Imported",
        "reviews": reviews[:max_reviews],
        "src": final_url,
    }
    if not prod["img"]:
        prod["e"] = "\U0001f4e6"
    return prod


if __name__ == "__main__":
    import sys
    u = sys.argv[1] if len(sys.argv) > 1 else input("Flipkart product link: ").strip()
    print(json.dumps(scrape_product(u), ensure_ascii=False, indent=1))
