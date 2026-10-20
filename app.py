
# -*- coding: utf-8 -*-
"""
Flipkart Admin + Product Importer backend
Run: python app.py
Site: /   Admin: /admin
Default admin password: flipkart123
Set ADMIN_KEY environment variable to change it.
"""
import os
import re
import json
import uuid
import time
import threading
import requests

from collections import defaultdict, deque
from flask import (
    Flask, request, jsonify, send_from_directory, abort,
    session, redirect, url_for
)
from werkzeug.exceptions import HTTPException
from scraper import scrape_product, HEADERS, ScrapeError

BASE = os.path.dirname(os.path.abspath(__file__))
DATA = os.path.join(BASE, "data")
UP = os.path.join(BASE, "uploads")

ADMIN_KEY = os.environ.get("ADMIN_KEY", "flipkart123")
SESSION_SECRET = os.environ.get(
    "SESSION_SECRET",
    "CHANGE-ME-flipkart-session-secret-before-deploy"
)
ALLOWED_EXT = {
    ".png", ".jpg", ".jpeg", ".webp", ".gif", ".mp4", ".svg"
}

os.makedirs(DATA, exist_ok=True)
os.makedirs(UP, exist_ok=True)

app = Flask(__name__, static_folder=None)
app.config["MAX_CONTENT_LENGTH"] = 20 * 1024 * 1024
app.secret_key = SESSION_SECRET
app.config["SESSION_COOKIE_HTTPONLY"] = True
app.config["SESSION_COOKIE_SAMESITE"] = "Lax"
app.config["SESSION_COOKIE_SECURE"] = True
app.config["PERMANENT_SESSION_LIFETIME"] = 60 * 60 * 8

_visits = defaultdict(deque)
_visit_lock = threading.Lock()

BLOCKED_BOTS = re.compile(
    r"(?:HTTrack|wget|curl|python-requests|Scrapy|Go-http-client|"
    r"libwww-perl|masscan|nikto)",
    re.I
)


@app.before_request
def request_guard():
    path = request.path

    if path.startswith("/api/") or path in ("/admin", "/"):
        ip = request.remote_addr or "unknown"
        now = time.monotonic()
        limit = 180 if path == "/" else 100

        with _visit_lock:
            bucket = _visits[
                (ip, "home" if path == "/" else "api")
            ]

            while bucket and now - bucket[0] > 60:
                bucket.popleft()

            if len(bucket) >= limit:
                return jsonify({
                    "error": "Too many requests. Please try again later."
                }), 429

            bucket.append(now)

    if (
        request.method == "GET"
        and BLOCKED_BOTS.search(request.headers.get("User-Agent", ""))
    ):
        if path == "/" or path.startswith(("/api/products", "/uploads/")):
            abort(403)


@app.after_request
def secure_headers(response):
    response.headers["X-Content-Type-Options"] = "nosniff"
    response.headers["Referrer-Policy"] = "strict-origin-when-cross-origin"
    response.headers["X-Frame-Options"] = "SAMEORIGIN"
    response.headers["Permissions-Policy"] = (
        "camera=(), microphone=(), geolocation=()"
    )

    if request.path.startswith("/api/") or request.path == "/admin":
        response.headers["Cache-Control"] = "no-store"

    return response


@app.route("/robots.txt")
def robots():
    return (
        "User-agent: *\n"
        "Disallow: /admin\n"
        "Disallow: /api/\n"
        "Disallow: /uploads/\n",
        200,
        {"Content-Type": "text/plain; charset=utf-8"}
    )


def jload(name, default):
    path = os.path.join(DATA, name)

    if os.path.exists(path):
        try:
            with open(path, encoding="utf-8") as f:
                return json.load(f)
        except Exception:
            pass

    return default


def jsave(name, data):
    path = os.path.join(DATA, name)

    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=1)


def auth():
    if not session.get("admin_authenticated"):
        abort(
            401,
            description="Admin login required. /admin par password se login karein."
        )


@app.errorhandler(HTTPException)
def api_http_error(error):
    if request.path.startswith("/api/"):
        return jsonify({
            "error": error.description or error.name,
            "status": error.code
        }), error.code

    return error


# ---------------- PAGES ----------------

@app.route("/")
def home():
    return send_from_directory(BASE, "index.html")


@app.route("/admin", methods=["GET"])
def admin_page():
    if not session.get("admin_authenticated"):
        return (
            LOGIN_PAGE,
            401,
            {
                "Content-Type": "text/html; charset=utf-8",
                "Cache-Control": "no-store"
            }
        )

    return send_from_directory(BASE, "admin.html", max_age=0)


LOGIN_PAGE = r'''<!doctype html>
<html lang="hi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Flipkart Admin Login</title>
<style>
*{box-sizing:border-box}
body{
margin:0;min-height:100vh;display:grid;place-items:center;
background:linear-gradient(145deg,#1a0536,#6a10b0);
font-family:system-ui,sans-serif;color:#fff;padding:18px
}
.panel{
width:min(100%,390px);padding:26px;border-radius:22px;
background:#17052bef;border:1px solid #ffffff30;
box-shadow:0 20px 60px #0006
}
h1{font-size:23px;margin:0 0 8px}
.sub{color:#d9c2ff;font-size:14px;margin-bottom:22px}
label{display:block;font-size:14px;margin:12px 0 6px}
input{
width:100%;padding:13px;border:0;border-radius:11px;font-size:16px
}
button{
width:100%;margin-top:16px;border:0;border-radius:12px;
padding:13px;font-size:16px;font-weight:800;color:#fff;
background:linear-gradient(135deg,#7b2ff7,#c13bd8);cursor:pointer
}
.err{color:#ffabb5;min-height:22px;margin-top:12px;font-size:14px}
</style>
</head>
<body>
<form class="panel" method="post" action="/admin/login">
<h1>🔒 Flipkart Admin</h1>
<div class="sub">Admin panel kholne ke liye password daalein.</div>
<label for="password">Admin Password</label>
<input id="password" name="password" type="password"
autocomplete="current-password" required autofocus>
<button type="submit">Login Securely</button>
<div class="err">{{ERROR}}</div>
</form>
</body>
</html>'''


@app.route("/admin/login", methods=["POST"])
def admin_login():
    from hmac import compare_digest

    password = request.form.get("password", "")

    if compare_digest(password, ADMIN_KEY):
        session.clear()
        session["admin_authenticated"] = True
        session.permanent = True
        return redirect(url_for("admin_page"), code=303)

    return (
        LOGIN_PAGE.replace(
            "{{ERROR}}", "Password galat hai. Dobara try karein."
        ),
        401,
        {
            "Content-Type": "text/html; charset=utf-8",
            "Cache-Control": "no-store"
        }
    )


@app.route("/admin/logout", methods=["POST", "GET"])
def admin_logout():
    session.clear()
    return redirect(url_for("admin_page"), code=303)


@app.route("/uploads/<path:f>")
def uploads(f):
    return send_from_directory(UP, f)


@app.route("/data/<path:f>")
def data_files(f):
    abort(403)


# ---------------- SETTINGS API ----------------

@app.route("/api/settings", methods=["GET", "POST"])
def settings():
    if request.method == "POST":
        auth()
        current = jload("settings.json", {})
        incoming = request.get_json(force=True) or {}
        current.update(incoming)
        jsave("settings.json", current)
        if "discount_percent" in incoming or "return_hike_percent" in incoming:
            products = jload("products.json", [])
            discount = max(0, min(99, float(current.get("discount_percent", 0) or 0)))
            hike = max(0, min(500, float(current.get("return_hike_percent", 0) or 0)))
            for product in products:
                base_price = product.get("mrp") or product.get("originalPrice") or product.get("p") or 0
                try:
                    base_price = float(base_price)
                    product["originalPrice"] = base_price
                    product["p"] = round(base_price * (1 - discount / 100), 2)
                    product["returnPrice"] = round(product["p"] * (1 + hike / 100), 2)
                except (TypeError, ValueError):
                    continue
            jsave("products.json", products)

    return jsonify(jload("settings.json", {}))


# ---------------- SAVED SCRAPER LINKS ----------------
@app.route("/api/saved-links", methods=["GET", "POST", "DELETE"])
def saved_links():
    auth()
    links = jload("saved_links.json", [])
    if request.method == "GET":
        return jsonify(links)
    if request.method == "DELETE":
        body = request.get_json(silent=True) or {}
        if body.get("clear"):
            links = []
        else:
            target = (body.get("url") or "").strip()
            links = [x for x in links if x != target]
        jsave("saved_links.json", links)
        return jsonify({"ok": True, "links": links})
    body = request.get_json(force=True) or {}
    incoming = body.get("links", [])
    if isinstance(incoming, str):
        incoming = incoming.splitlines()
    seen = set(links)
    for raw in incoming:
        u = str(raw or "").strip()
        if u.startswith(("https://", "http://")) and u not in seen:
            links.append(u)
            seen.add(u)
    jsave("saved_links.json", links)
    return jsonify(links)


# ---------------- PRODUCTS API ----------------

@app.route("/api/products", methods=["GET", "POST"])
def products():
    if request.method == "POST":
        auth()

        items = jload("products.json", [])
        prod = request.get_json(force=True) or {}

        if not prod.get("id"):
            prod["id"] = uuid.uuid4().hex[:10]

        for i, item in enumerate(items):
            if item.get("id") == prod["id"]:
                items[i] = prod
                break
        else:
            items.insert(0, prod)

        jsave("products.json", items)
        return jsonify(prod)

    return jsonify(jload("products.json", []))


@app.route("/api/products/<pid>", methods=["DELETE"])
def del_product(pid):
    auth()

    items = [
        item for item in jload("products.json", [])
        if item.get("id") != pid
    ]

    jsave("products.json", items)

    return jsonify({"ok": True, "count": len(items)})


# ---------------- FILE UPLOAD ----------------

@app.route("/api/upload", methods=["POST"])
def upload():
    auth()

    f = request.files.get("file")

    if not f:
        abort(400, "file missing")

    ext = os.path.splitext(f.filename or "")[1].lower()

    if ext not in ALLOWED_EXT:
        abort(400, "File type allowed nahi: " + ext)

    folder = re.sub(
        r"[^a-zA-Z0-9_\-/]",
        "",
        (request.form.get("folder") or "misc").strip("/")
    ) or "misc"

    dest = os.path.join(UP, folder)
    os.makedirs(dest, exist_ok=True)

    fname = "%d_%s%s" % (
        int(time.time()),
        uuid.uuid4().hex[:6],
        ext
    )

    f.save(os.path.join(dest, fname))

    return jsonify({
        "path": "uploads/%s/%s" % (folder, fname)
    })


def download_image(url, dest_dir, prefix):
    try:
        r = requests.get(
            url,
            headers={
                "User-Agent": HEADERS["User-Agent"],
                "Referer": "https://www.flipkart.com/"
            },
            timeout=30
        )

        if r.status_code != 200 or len(r.content) < 500:
            return None

        ext = ".jpg"
        content_type = r.headers.get("Content-Type", "")

        if "png" in content_type:
            ext = ".png"
        elif "webp" in content_type:
            ext = ".webp"

        fname = "%s%s" % (prefix, ext)

        with open(os.path.join(dest_dir, fname), "wb") as f:
            f.write(r.content)

        rel = os.path.relpath(
            os.path.join(dest_dir, fname), BASE
        ).replace("\\", "/")

        return rel

    except Exception:
        return None


# ---------------- PRODUCT IMPORTER ----------------

@app.route("/api/import", methods=["POST"])
def do_import():
    auth()

    body = request.get_json(force=True) or {}
    url = (body.get("url") or "").strip()

    if not url:
        abort(400, "Pehle product link paste karo")

    from urllib.parse import urlparse

    parsed = urlparse(url)
    host = (parsed.hostname or "").lower()

    allowed = (
        host == "flipkart.com"
        or host.endswith(".flipkart.com")
        or host == "amazon.in"
        or host.endswith(".amazon.in")
        or host == "amazon.com"
        or host.endswith(".amazon.com")
        or host in ("amzn.in", "amzn.to")
    )

    if parsed.scheme not in ("http", "https") or not allowed:
        return jsonify({
            "error": "Valid Flipkart ya Amazon product link paste karo."
        }), 400

    max_reviews = int(body.get("max_reviews") or 10)

    try:
        prod = scrape_product(
            url,
            max_reviews=max_reviews,
            include_specifications=bool(
                body.get("include_specifications", True)
            )
        )

    except ScrapeError as e:
        return jsonify({"error": str(e)}), 502

    except Exception as e:
        return jsonify({"error": "Scrape fail hua: %s" % e}), 502

    if body.get("download_images", True):
        pid = uuid.uuid4().hex[:8]
        prod["id"] = pid

        dest = os.path.join(UP, "products", pid)
        os.makedirs(dest, exist_ok=True)

        local = []

        for ix, image_url in enumerate(prod.get("imgs") or []):
            path = download_image(image_url, dest, "p%d" % (ix + 1))
            if path:
                local.append(path)

        if local:
            prod["imgs"] = local
            prod["img"] = local[0]
        else:
            prod["img"] = (prod.get("imgs") or [""])[0]

        if not prod.get("img"):
            prod["e"] = "\U0001f6cd\ufe0f"

        for review in prod.get("reviews") or []:
            local_review_images = []

            for ix, image_url in enumerate(review.get("imgs") or []):
                path = download_image(
                    image_url,
                    dest,
                    "rv%d_%d" % (len(local_review_images), ix)
                )

                if path:
                    local_review_images.append(path)

            review["imgs"] = local_review_images
            review["ph"] = len(local_review_images)

    return jsonify(prod)


if __name__ == "__main__":
    app.run(
        host="0.0.0.0",
        port=int(os.environ.get("PORT", 5000)),
        debug=False
    )
