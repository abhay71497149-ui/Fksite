TENVER WEBSITE

MAIN PRODUCT SETTINGS
Open index.html and find: TENVER_PRODUCTS
For each product you can change:
- name
- price
- oldPrice
- volume
- badge
- rating
- reviews
- review distribution: dist
- main image
- 4 sub-images
- 2 description banners
- description text

IMAGE FILES
Product 1:
assets/products/product1.jpg              = main image
assets/products/product1sub1.jpg          = sub image 1
assets/products/product1sub2.jpg          = sub image 2
assets/products/product1sub3.jpg          = sub image 3
assets/products/product1sub4.jpg          = sub image 4
assets/products/product1-desc1.jpg        = description banner 1
assets/products/product1-desc2.jpg        = description banner 2

Repeat the same pattern for product2 through product10.
You can replace the JPG files with your own images, or change the paths in TENVER_PRODUCTS.

OFFER SETTINGS
At the top of the JS settings:
const BUY_PRODUCTS = 1;
const FREE_PRODUCTS = 6;

Change FREE_PRODUCTS to 3, 4, 5, 6 etc. The offer text, free-product selection limit, product-detail offer strip, cart, checkout item count and validation update from this setting.

PRODUCT DETAIL PAGE
Clicking a product image, name or rating/review opens the product detail view.
The main image can be changed by tapping a thumbnail or swiping left/right.
Add to Cart opens the existing cart panel.

CUSTOMER REVIEWS
Change rating, review count and dist values per product. The review bar counts are displayed exactly from dist. Keep 5-star and 4-star counts higher if you want the visual distribution to be mostly positive.

PAYMENT
COD advance is controlled by COD_ADVANCE in index.html.
Replace assets/payment-qr.png with your payment QR. Frontend UTR text is not payment verification; connect a real payment gateway/backend for actual verification.
