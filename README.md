# Flipkart - Admin Panel + Flipkart Product Importer

## Kya-kya milta hai
- **Admin Panel** (/admin): Main Flipkart logo, video poster, 3 image posters,
  Assured ke bagal wala chhota logo, "Flipkart Sale Price" text/logo - sab change karo.
- **Products**: add/edit/delete - naam, MRP, sale price, tag, rating, description,
  images (server pe upload), highlights, specifications (Size/Colour/Model).
- **Reviews**: har product pe review add karo - rating, title, full text, reviewer name,
  review images (agar hon to).
- **Product Importer (sabse important)**: Flipkart product link paste karo ->
  naam, MRP, sale price, model, size/colour, description, 5-10 reviews (rating + full text
  + review images), saari product images - sab automatic. Images TUMHARE SERVER pe
  download hoti hain. Image na mile to wo skip, baaki details aa jaati hain.

## Setup
1. Python 3.8+ install karo
2. Folder me terminal kholo:
   pip install -r requirements.txt
   python app.py
3. Site:   http://localhost:5000
4. Admin:  http://localhost:5000/admin   (password is ADMIN_KEY; default only for local testing: flipkart123)
5. Railway par Variables me `ADMIN_KEY` aur `SESSION_SECRET` set karein. Login ke liye `ADMIN_KEY` ki value password hai. HTTPS hosting recommended.

## Admin password / session security
Set `ADMIN_KEY` to a long unique password and `SESSION_SECRET` to a different random secret in hosting environment variables. The admin page and all write/upload/import APIs now require a server-side login session. The default `flipkart123` is only for local testing; never leave it on a public deployment.

## Admin key change karna
   Windows:   set ADMIN_KEY=merinayikey && python app.py
   Mac/Linux: ADMIN_KEY=merinayikey python app.py

## Hosting (ZAROORI)
Scraper server pe chalta hai, isliye Python-supporting hosting chahiye:
Render.com, Railway.app, PythonAnywhere, ya VPS.
Sirf static hosting (GitHub Pages / Netlify free) pe Importer kaam NAHI karega.

## Test karna
Terminal me:  python scraper.py "https://www.flipkart.com/..."   (output JSON me aayega)

## Honest notes
- Flipkart kabhi anti-bot/captcha dikhata hai. Block error aaye to 5-10 min baad try karo.
- Flipkart page structure badal sakta hai; koi field na aaye to scraper.py update karna hoga.
- Amazon abhi support nahi hai (sirf Flipkart), baad me add kar sakte hain.
- Products bechne se pehle apna source/deals verify kar lena.


## Returning-customer price animation
In Admin Panel → Payments, enable “Returning Customer Price Animation”, enter any positive percentage and a duration in seconds, then save. If a visitor reaches the payment screen and returns to the homepage without completing the flow, the homepage product prices animate for the configured duration. The resulting prices are stored per browser and are reused across product details/checkout on that same browser. Clearing that browser's site data resets its local price state. The animation timer controls only the visual transition; the resulting price persists.

For deployment, set `ADMIN_KEY` and `SESSION_SECRET` in your host environment. The payment UI is not a verified payment gateway; configure a real provider before accepting orders.


PhonePe payment button: tapping PAY shows the screenshot-matched “Opening UPI App” loading dialog for about 1.4 seconds, then attempts to open PhonePe using the existing native deeplink and Android intent fallback. Actual app launching depends on the customer device/browser and PhonePe being installed.


## Returning-customer price update
- Admin > Payment settings: enable Returning Customer Price and set only the display duration in seconds.
- Admin > Product Add/Edit: set Original Price, Sale Price, and exact Customer Return Price for each product.
- When a customer returns from the payment page without completing payment, prices animate downward from Original Price to the configured Customer Return Price. That price is used across product/detail/cart/checkout pages until the timer expires, then the normal Sale Price returns.
- The percentage increase setting has been removed.
