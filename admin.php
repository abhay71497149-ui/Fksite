<!doctype html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Panel</title>

<style>
*{box-sizing:border-box}
body{
  margin:0;
  font-family:Arial,sans-serif;
  background:#f4f7f6;
  color:#14211f
}
.top{
  background:#052925;
  color:#fff;
  padding:15px;
  position:sticky;
  top:0;
  z-index:20
}
.top b{font-size:19px}
.wrap{
  max-width:900px;
  margin:auto;
  padding:14px
}
.login{
  max-width:420px;
  margin:12vh auto;
  background:#fff;
  padding:24px;
  border-radius:18px;
  box-shadow:0 8px 30px #0001
}
.login h1{text-align:center}
.input,textarea,select{
  width:100%;
  padding:11px;
  border:1px solid #ccd8d5;
  border-radius:9px;
  background:#fff
}
.btn{
  border:0;
  border-radius:9px;
  padding:11px 14px;
  font-weight:800;
  background:#052925;
  color:#fff;
  cursor:pointer
}
.btn.alt{background:#0b665b}
.btn.red{background:#a62828}
.btn.gray{
  background:#e8eeee;
  color:#14211f
}
.grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:12px
}
.card{
  background:#fff;
  border-radius:16px;
  padding:16px;
  margin:12px 0;
  box-shadow:0 3px 15px #0000000a
}
.card h2{
  margin:0 0 14px;
  font-size:18px
}
.field{margin:10px 0}
.field label{
  display:block;
  font-size:12px;
  font-weight:800;
  margin-bottom:5px
}
.hint{
  font-size:12px;
  color:#687875
}
.hidden{display:none!important}
.section-grid{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:12px
}
.section-btn{
  border:0;
  background:#fff;
  padding:20px 15px;
  border-radius:16px;
  box-shadow:0 3px 15px #0001;
  text-align:left;
  cursor:pointer;
  font-size:15px;
  font-weight:800
}
.section-btn span{
  display:block;
  font-size:28px;
  margin-bottom:8px
}
.section-btn small{
  display:block;
  color:#687875;
  font-size:11px;
  font-weight:400;
  margin-top:5px
}
.back{
  margin-bottom:12px
}
.product-list{
  display:grid;
  grid-template-columns:repeat(2,1fr);
  gap:12px
}
.product-card{
  background:#fff;
  border:1px solid #dfe7e5;
  border-radius:15px;
  padding:12px;
  cursor:pointer
}
.product-card img{
  width:100%;
  height:130px;
  object-fit:cover;
  border-radius:11px;
  background:#eee
}
.product-card b{
  display:block;
  margin-top:8px
}
.product-card small{
  color:#687875
}
.product-head{
  display:flex;
  gap:12px;
  align-items:center;
  margin-bottom:15px
}
.product-head img{
  width:75px;
  height:75px;
  object-fit:cover;
  border-radius:12px;
  background:#eee
}
.sub-box{
  border:1px solid #dfe7e5;
  border-radius:13px;
  padding:12px;
  margin-top:12px
}
.bulk{
  border:2px dashed #b9c8c4;
  padding:15px;
  border-radius:12px;
  background:#f8fbfa;
  margin-top:10px
}
.bulk strong{
  display:block;
  margin-bottom:5px
}
.preview-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:8px;
  margin-top:10px
}
.preview-grid img{
  width:100%;
  aspect-ratio:1;
  object-fit:cover;
  border-radius:8px;
  background:#eee
}
.preview-grid.two{
  grid-template-columns:repeat(2,1fr)
}
.savebar{
  position:sticky;
  bottom:10px;
  background:#fff;
  padding:10px;
  border-radius:14px;
  box-shadow:0 5px 25px #0002;
  display:flex;
  gap:8px;
  align-items:center;
  z-index:15
}
.status{
  font-size:12px;
  color:#347a43
}
.switch{
  display:flex;
  align-items:center;
  gap:9px;
  padding:10px 0
}
.switch input{
  width:20px;
  height:20px
}
.notice{
  background:#fff8df;
  border:1px solid #ecd98d;
  padding:12px;
  border-radius:11px;
  font-size:12px;
  margin:10px 0
}
.danger-box{
  background:#fff1f1;
  border:1px solid #e6aaaa;
  padding:12px;
  border-radius:12px
}
@media(max-width:600px){
  .grid,
  .section-grid,
  .product-list{
    grid-template-columns:1fr
  }
  .wrap{padding:10px}
  .card{padding:13px}
  .preview-grid{grid-template-columns:repeat(2,1fr)}
}
</style>
</head>

<body>

<!-- LOGIN -->
<div id="login" class="login">
  <h1>🛠️ Admin Panel</h1>
  <p class="hint">
    Login karke website manage karo.
  </p>

  <input
    id="pass"
    class="input"
    type="password"
    placeholder="Admin password"
  >

  <button
    class="btn"
    style="width:100%;margin-top:12px"
    onclick="login()"
  >
    LOGIN
  </button>

  <p id="loginMsg" class="hint"></p>
</div>


<!-- APP -->
<div id="app" class="hidden">

<div class="top">
  <b id="adminTitle">⚙️ Admin Panel</b>

  <button
    class="btn"
    style="float:right;padding:7px 10px;background:#fff;color:#052925"
    onclick="logout()"
  >
    Logout
  </button>
</div>


<div class="wrap">

<!-- ================= MAIN MENU ================= -->

<div id="homeMenu">

  <div class="card">
    <h2>⚙️ Website Management</h2>
    <p class="hint">
      Neeche kisi section par click karke sirf usi section ki settings open karo.
    </p>
  </div>

  <div class="section-grid">

    <button class="section-btn" onclick="openSection('offer')">
      <span>🎁</span>
      Offer & Checkout
      <small>Buy / Free / COD / Mystery Gift</small>
    </button>

    <button class="section-btn" onclick="openSection('returning')">
      <span>🔁</span>
      Returning Customer Offer
      <small>Returning price & message</small>
    </button>

    <button class="section-btn" onclick="openSection('brand')">
      <span>🏷️</span>
      Brand & Theme
      <small>Brand name, logo & colors</small>
    </button>

    <button class="section-btn" onclick="openSection('home')">
      <span>🏠</span>
      Home
      <small>Banners, ticker & mystery image</small>
    </button>

    <button class="section-btn" onclick="openSection('products')">
      <span>🧴</span>
      Products
      <small>Product 1–10 & images</small>
    </button>

    <button class="section-btn" onclick="openSection('payment')">
      <span>💳</span>
      Payment
      <small>QR & payment settings</small>
    </button>

    <button class="section-btn" onclick="openSection('security')">
      <span>🔐</span>
      Security
      <small>Admin password</small>
    </button>

  </div>
</div>


<!-- ================= OFFER ================= -->

<section id="section-offer" class="hidden">

  <button class="btn gray back" onclick="backHome()">← Back</button>

  <div class="card">

    <h2>🎁 Offer & Checkout</h2>

    <div class="grid">

      <div class="field">
        <label>Buy Products</label>
        <input id="buy" class="input" type="number" min="1">
      </div>

      <div class="field">
        <label>Free Products</label>
        <input id="free" class="input" type="number" min="0">
      </div>

      <div class="field">
        <label>COD Advance ₹</label>
        <input id="cod" class="input" type="number" min="0">
      </div>

      <div class="field">
        <label>Mystery Gift MRP ₹</label>
        <input id="mysteryPrice" class="input" type="number" min="0">
      </div>

    </div>

  </div>

  <div class="savebar">
    <button class="btn" onclick="saveSettings()">
      💾 SAVE OFFER
    </button>
    <span id="offerStatus" class="status"></span>
  </div>

</section>


<!-- ================= RETURNING OFFER ================= -->

<section id="section-returning" class="hidden">

  <button class="btn gray back" onclick="backHome()">← Back</button>

  <div class="card">

    <h2>🔁 Returning Customer Offer</h2>

    <div class="notice">
      Ye offer customer ko clearly <b>Returning Customer Offer</b>
      ke naam se dikhaya jayega. Original product price mutate nahi hogi.
    </div>

    <div class="switch">
      <input id="returningEnabled" type="checkbox">
      <label>
        <b>Returning Customer Offer ON</b>
      </label>
    </div>

    <div class="grid">

      <div class="field">
        <label>Returning Price Increase %</label>
        <input
          id="returningPercent"
          class="input"
          type="number"
          min="0"
          step="1"
          placeholder="20"
        >
      </div>

      <div class="field">
        <label>Offer Duration / Reset Seconds</label>
        <input
          id="returningResetSeconds"
          class="input"
          type="number"
          min="2"
          step="1"
          placeholder="6"
        >
      </div>

    </div>

    <div class="field">
      <label>Returning Customer Message</label>
      <textarea
        id="returningMessage"
        class="input"
        rows="3"
        placeholder="Our Price Increased 20% Due to High Demand."
      ></textarea>
    </div>

    <div class="field">
      <label>Offer Label</label>
      <input
        id="returningLabel"
        class="input"
        placeholder="Returning Customer Price"
      >
    </div>

    <div class="switch">
      <input id="returningAnimation" type="checkbox">
      <label>
        <b>Live Price Drop Animation</b>
      </label>
    </div>

    <div class="hint">
      Example: Sale Price ₹699 + 20% = Returning Customer Price ₹838.80.
      Customer ko final amount clearly show hoga.
    </div>

  </div>

  <div class="savebar">
    <button class="btn" onclick="saveReturning()">
      💾 SAVE RETURNING OFFER
    </button>
    <span id="returningStatus" class="status"></span>
  </div>

</section>


<!-- ================= BRAND & THEME ================= -->

<section id="section-brand" class="hidden">

  <button class="btn gray back" onclick="backHome()">← Back</button>

  <div class="card">

    <h2>🏷️ Brand & Theme</h2>

    <div class="field">
      <label>Global Brand Name</label>

      <input
        id="brandName"
        class="input"
        placeholder="TENVER"
      >

      <p class="hint">
        Is naam ko customer website ke header, product details,
        cart, checkout, payment, footer, popup aur page title mein
        global brand ke roop mein use kiya jayega.
      </p>
    </div>

    <div class="field">
      <label>Main Header Logo</label>
      <input id="logoPath" class="input">
      <input
        type="file"
        accept="image/*"
        onchange="uploadAsset(this,'logo')"
      >
    </div>

    <div class="field">
      <label>Right Header Logo</label>
      <input id="rightLogoPath" class="input">
      <input
        type="file"
        accept="image/*"
        onchange="uploadAsset(this,'rightLogo')"
      >
    </div>

    <div class="grid">

      <div class="field">
        <label>Primary Theme</label>
        <input id="primary" class="input" type="color">
      </div>

      <div class="field">
        <label>Secondary Theme</label>
        <input id="secondary" class="input" type="color">
      </div>

      <div class="field">
        <label>Accent</label>
        <input id="accent" class="input" type="color">
      </div>

      <div class="field">
        <label>Logo Color</label>
        <input id="logoColor" class="input" type="color">
      </div>

      <div class="field">
        <label>Background</label>
        <input id="background" class="input" type="color">
      </div>

      <div class="field">
        <label>Text Color</label>
        <input id="text" class="input" type="color">
      </div>

    </div>

  </div>

  <div class="savebar">
    <button class="btn" onclick="saveBrand()">
      💾 SAVE BRAND & THEME
    </button>

    <span id="brandStatus" class="status"></span>
  </div>

</section>


<!-- ================= HOME ================= -->

<section id="section-home" class="hidden">

  <button class="btn gray back" onclick="backHome()">← Back</button>

  <div class="card">

    <h2>🏠 Home Assets</h2>

    <div class="field">
      <label>Banner 1</label>
      <input id="hb1" class="input">
      <input
        type="file"
        accept="image/*"
        onchange="uploadAsset(this,'hb1')"
      >
    </div>

    <div class="field">
      <label>Banner 2</label>
      <input id="hb2" class="input">
      <input
        type="file"
        accept="image/*"
        onchange="uploadAsset(this,'hb2')"
      >
    </div>

    <div class="field">
      <label>Product Detail Banner</label>
      <input id="detailBanner" class="input">
      <input
        type="file"
        accept="image/*"
        onchange="uploadAsset(this,'detailBanner')"
      >
    </div>

    <div class="field">
      <label>Scrolling Offer Headline</label>
      <input
        id="offerTicker"
        class="input"
        placeholder="Sale • BUY 1 GET 6 FREE"
      >
    </div>

    <div class="field">
      <label>Mystery Box Image</label>
      <input id="mysteryImage" class="input">
      <input
        type="file"
        accept="image/*"
        onchange="uploadAsset(this,'mystery')"
      >
    </div>

  </div>

  <div class="savebar">
    <button class="btn" onclick="saveHome()">
      💾 SAVE HOME
    </button>

    <span id="homeStatus" class="status"></span>
  </div>

</section>


<!-- ================= PRODUCTS LIST ================= -->

<section id="section-products" class="hidden">

  <button class="btn gray back" onclick="backHome()">← Back</button>

  <div class="card">

    <h2>🧴 Products</h2>

    <p class="hint">
      Product select karo. Sirf usi product ki complete settings open hongi.
    </p>

    <div id="productsList" class="product-list"></div>

  </div>

</section>


<!-- ================= SINGLE PRODUCT ================= -->

<section id="section-product-edit" class="hidden">

  <button class="btn gray back" onclick="openSection('products')">
    ← Back to Products
  </button>

  <div id="productEditor"></div>

</section>


<!-- ================= PAYMENT ================= -->

<section id="section-payment" class="hidden">

  <button class="btn gray back" onclick="backHome()">← Back</button>

  <div class="card">

    <h2>💳 Payment</h2>

    <div class="notice">
      PhonePe mechanism ko admin panel se modify nahi kiya ja raha.
      Existing PhonePe code same rahega.
    </div>

    <div class="field">
      <label>Payment QR Image</label>

      <input id="paymentQr" class="input">

      <input
        type="file"
        accept="image/*"
        onchange="uploadAsset(this,'qr')"
      >
    </div>

    <div class="field">
      <label>UPI / QR Path</label>
      <input id="paymentQr2" class="input">
    </div>

    <div class="field">
      <label>Paytm Status</label>

      <select id="paytmStatus" class="input">
        <option value="unavailable">
          Temporarily Unavailable
        </option>
        <option value="available">
          Available
        </option>
      </select>

      <p class="hint">
        Current requirement: Paytm Temporarily Unavailable.
      </p>
    </div>

  </div>

  <div class="savebar">
    <button class="btn" onclick="savePayment()">
      💾 SAVE PAYMENT
    </button>

    <span id="paymentStatus" class="status"></span>
  </div>

</section>


<!-- ================= SECURITY ================= -->

<section id="section-security" class="hidden">

  <button class="btn gray back" onclick="backHome()">← Back</button>

  <div class="card">

    <h2>🔐 Admin Security</h2>

    <div class="field">
      <label>New Admin Password</label>

      <input
        id="newPass"
        class="input"
        type="password"
        placeholder="Minimum 8 characters"
      >
    </div>

    <button class="btn alt" onclick="changePassword()">
      Change Password
    </button>

    <p id="securityStatus" class="status"></p>

  </div>

</section>


</div>
</div>


<script>

let C=null;
let currentProductIndex=null;

const $=id=>document.getElementById(id);


/* ================= API ================= */

async function req(action,opts={}){

  const r=await fetch(
    'api.php?action='+action,
    opts
  );

  const j=await r.json();

  if(!r.ok){
    throw new Error(j.error||'Error');
  }

  return j;
}


/* ================= LOGIN ================= */

async function login(){

  try{

    await req('login',{
      method:'POST',
      headers:{
        'Content-Type':'application/json'
      },
      body:JSON.stringify({
        password:$('pass').value
      })
    });

    $('login').classList.add('hidden');
    $('app').classList.remove('hidden');

    await load();

  }catch(e){

    $('loginMsg').textContent=e.message;

  }

}


/* ================= LOAD ================= */

async function load(){

  C=await req('config');

  C.settings=C.settings||{};
  C.theme=C.theme||{};
  C.site=C.site||{};
  C.products=C.products||[];

  $('adminTitle').textContent=
    '⚙️ '+(C.site.brand||'Admin')+' Admin Panel';

  /* OFFER */

  $('buy').value=
    C.settings.buyProducts ?? 1;

  $('free').value=
    C.settings.freeProducts ?? 0;

  $('cod').value=
    C.settings.codAdvance ?? 0;

  $('mysteryPrice').value=
    C.settings.mysteryGiftPrice ?? 0;


  /* RETURNING OFFER */

  const ro=C.returningOffer||{};

  $('returningEnabled').checked=
    ro.enabled!==false;

  $('returningPercent').value=
    ro.percent ?? 20;

  $('returningResetSeconds').value=
    ro.resetSeconds ?? 6;

  $('returningMessage').value=
    ro.message ||
    'Our Price Increased 20% Due to High Demand.';

  $('returningLabel').value=
    ro.label ||
    'Returning Customer Price';

  $('returningAnimation').checked=
    ro.animation!==false;


  /* BRAND */

  $('brandName').value=
    C.site.brand || 'TENVER';

  $('logoPath').value=
    C.site.logo || 'assets/logo.png';

  $('rightLogoPath').value=
    C.navRightLogo || '';


  /* COLORS */

  $('primary').value=
    C.theme.primary || '#052925';

  $('secondary').value=
    C.theme.secondary || '#0b665b';

  $('accent').value=
    C.theme.accent || '#28a88e';

  $('logoColor').value=
    C.theme.logoColor || '#d5b06b';

  $('background').value=
    C.theme.background || '#f8f9f7';

  $('text').value=
    C.theme.text || '#111111';


  /* HOME */

  $('hb1').value=
    C.homeBanners?.[0] || '';

  $('hb2').value=
    C.homeBanners?.[1] || '';

  $('detailBanner').value=
    C.detailBanner || '';

  $('offerTicker').value=
    C.offerTicker || '';

  $('mysteryImage').value=
    C.mysteryImage || '';


  /* PAYMENT */

  $('paymentQr').value=
    C.paymentQr || '';

  $('paymentQr2').value=
    C.paymentQr || '';

  $('paytmStatus').value=
    C.paytmStatus || 'unavailable';


  renderProductsList();

}


/* ================= SECTION NAVIGATION ================= */

const sections=[
  'homeMenu',
  'section-offer',
  'section-returning',
  'section-brand',
  'section-home',
  'section-products',
  'section-product-edit',
  'section-payment',
  'section-security'
];


function hideAll(){

  sections.forEach(id=>{
    const el=$(id);

    if(el){
      el.classList.add('hidden');
    }
  });

}


function openSection(name){

  hideAll();

  const el=$('section-'+name);

  if(el){
    el.classList.remove('hidden');
  }

  window.scrollTo({
    top:0,
    behavior:'smooth'
  });

}


function backHome(){

  hideAll();

  $('homeMenu').classList.remove('hidden');

  window.scrollTo({
    top:0,
    behavior:'smooth'
  });

}


/* ================= PRODUCT LIST ================= */

function renderProductsList(){

  const box=$('productsList');

  if(!C.products.length){

    box.innerHTML=
      '<p class="hint">No products found.</p>';

    return;
  }

  box.innerHTML=C.products.map((p,i)=>{

    return `
      <div class="product-card"
           onclick="editProduct(${i})">

        <img
          src="${escAttr(p.main||'')}"
          onerror="this.style.display='none'"
        >

        <b>
          Product ${p.id||i+1}
        </b>

        <small>
          ${esc(p.name||'Unnamed Product')}
        </small>

        <div style="margin-top:8px">
          ₹${Number(p.price||0)}
          &nbsp;
          <del style="color:#999">
            ₹${Number(p.oldPrice||0)}
          </del>
        </div>

        <button
          class="btn"
          style="margin-top:9px;width:100%"
          onclick="event.stopPropagation();editProduct(${i})"
        >
          EDIT PRODUCT
        </button>

      </div>
    `;

  }).join('');

}


/* ================= EDIT PRODUCT ================= */

function editProduct(i){

  currentProductIndex=i;

  renderProductEditor();

  hideAll();

  $('section-product-edit').classList.remove('hidden');

  window.scrollTo({
    top:0,
    behavior:'smooth'
  });

}


function renderProductEditor(){

  const i=currentProductIndex;

  const p=C.products[i];

  if(!p)return;


  p.subs=p.subs||['','','',''];
  p.descBanners=p.descBanners||['',''];
  p.additionalInfo=p.additionalInfo||[[],[],[],[]];
  p.faqs=p.faqs||[[],[],[],[]];
  p.dist=p.dist||{};


  while(p.subs.length<4)p.subs.push('');
  while(p.descBanners.length<2)p.descBanners.push('');


  $('productEditor').innerHTML=`

    <div class="card">

      <div class="product-head">

        <img
          src="${escAttr(p.main||'')}"
          onerror="this.style.display='none'"
        >

        <div>
          <h2 style="margin:0">
            Product ${p.id||i+1}
          </h2>

          <div class="hint">
            ${esc(p.name||'')}
          </div>
        </div>

      </div>


      <div class="grid">

        <div class="field">
          <label>Product Name</label>
          <input
            id="p_name"
            class="input"
            value="${escAttr(p.name||'')}"
          >
        </div>

        <div class="field">
          <label>Badge</label>
          <input
            id="p_badge"
            class="input"
            value="${escAttr(p.badge||'')}"
          >
        </div>

        <div class="field">
          <label>Sale Price ₹</label>
          <input
            id="p_price"
            class="input"
            type="number"
            value="${Number(p.price||0)}"
          >
        </div>

        <div class="field">
          <label>Old / MRP Price ₹</label>
          <input
            id="p_oldPrice"
            class="input"
            type="number"
            value="${Number(p.oldPrice||0)}"
          >
        </div>

        <div class="field">
          <label>Volume ml</label>
          <input
            id="p_volume"
            class="input"
            type="number"
            value="${Number(p.volume||0)}"
          >
        </div>

        <div class="field">
          <label>Rating</label>
          <input
            id="p_rating"
            class="input"
            type="number"
            min="0"
            max="5"
            step="0.01"
            value="${Number(p.rating||0)}"
          >
        </div>

        <div class="field">
          <label>Review Count</label>
          <input
            id="p_reviews"
            class="input"
            type="number"
            value="${Number(p.reviews||0)}"
          >
        </div>

        <div class="field">
          <label>Main Image Path</label>

          <input
            id="p_main"
            class="input"
            value="${escAttr(p.main||'')}"
          >

          <input
            type="file"
            accept="image/*"
            onchange="uploadProductImage(this,'main')"
          >
        </div>

      </div>


      <div class="field">

        <label>Description</label>

        <textarea
          id="p_description"
          class="input"
          rows="5"
        >${esc(p.description||'')}</textarea>

      </div>


      <!-- ADDITIONAL INFO -->

      <div class="sub-box">

        <h3>Additional Information</h3>

        ${[0,1,2,3].map(j=>`

          <div class="grid">

            <div class="field">

              <label>
                Info ${j+1} Title
              </label>

              <input
                id="info_title_${j}"
                class="input"
                value="${escAttr(
                  p.additionalInfo[j]?.[0]||''
                )}"
              >

            </div>

            <div class="field">

              <label>
                Info ${j+1} Value
              </label>

              <input
                id="info_value_${j}"
                class="input"
                value="${escAttr(
                  p.additionalInfo[j]?.[1]||''
                )}"
              >

            </div>

          </div>

        `).join('')}

      </div>


      <!-- FAQ -->

      <div class="sub-box">

        <h3>FAQs</h3>

        ${[0,1,2,3].map(j=>`

          <div class="field">

            <label>
              FAQ ${j+1} Question
            </label>

            <input
              id="faq_q_${j}"
              class="input"
              value="${escAttr(
                p.faqs[j]?.[0]||''
              )}"
            >

            <label>
              Answer
            </label>

            <textarea
              id="faq_a_${j}"
              class="input"
              rows="2"
            >${esc(
              p.faqs[j]?.[1]||''
            )}</textarea>

          </div>

        `).join('')}

      </div>


      <!-- 4 SUB IMAGES -->

      <div class="sub-box">

        <h3>🖼️ 4 Sub Images</h3>

        <p class="hint">
          Neeche ek hi baar mein exactly 4 images select karo.
          Automatically Sub Image 1–4 mein set ho jayengi.
        </p>

        <div class="bulk">

          <strong>
            Choose 4 Images Together
          </strong>

          <input
            id="bulkSubs"
            type="file"
            accept="image/*"
            multiple
            onchange="bulkSubUpload(this)"
          >

          <div
            id="subPreview"
            class="preview-grid"
          >
          </div>

        </div>

        <div class="grid">

          ${[0,1,2,3].map(j=>`

            <div class="field">

              <label>
                Sub Image ${j+1}
              </label>

              <input
                id="sub_${j}"
                class="input"
                value="${escAttr(p.subs[j]||'')}"
              >

              <input
                type="file"
                accept="image/*"
                onchange="uploadProductImage(this,'sub',${j})"
              >

            </div>

          `).join('')}

        </div>

      </div>


      <!-- 2 DESCRIPTION BANNERS -->

      <div class="sub-box">

        <h3>🖼️ 2 Description Banners</h3>

        <p class="hint">
          Ek hi baar mein exactly 2 images select karo.
          Automatically Description Banner 1 aur 2 mein set ho jayengi.
        </p>

        <div class="bulk">

          <strong>
            Choose 2 Images Together
          </strong>

          <input
            id="bulkDesc"
            type="file"
            accept="image/*"
            multiple
            onchange="bulkDescUpload(this)"
          >

          <div
            id="descPreview"
            class="preview-grid two"
          >
          </div>

        </div>

        <div class="grid">

          ${[0,1].map(j=>`

            <div class="field">

              <label>
                Description Banner ${j+1}
              </label>

              <input
                id="desc_${j}"
                class="input"
                value="${escAttr(
                  p.descBanners[j]||''
                )}"
              >

              <input
                type="file"
                accept="image/*"
                onchange="uploadProductImage(this,'desc',${j})"
              >

            </div>

          `).join('')}

        </div>

      </div>


      <!-- REVIEW DISTRIBUTION -->

      <div class="sub-box">

        <h3>⭐ Review Distribution</h3>

        <div class="grid">

          ${[5,4,3,2,1].map(n=>`

            <div class="field">

              <label>
                ${n}★ Reviews
              </label>

              <input
                id="dist_${n}"
                class="input"
                type="number"
                value="${Number(p.dist[n]||0)}"
              >

            </div>

          `).join('')}

        </div>

      </div>

    </div>


    <!-- PRODUCT SAVE -->

    <div class="savebar">

      <button
        class="btn"
        onclick="saveProduct()"
      >
        💾 SAVE PRODUCT
      </button>

      <button
        class="btn red"
        onclick="deleteProduct()"
      >
        🗑️ DELETE
      </button>

      <span
        id="productStatus"
        class="status"
      ></span>

    </div>

  `;

  updateSubPreview();
  updateDescPreview();

}


/* ================= PRODUCT COLLECT ================= */

function collectCurrentProduct(){

  const i=currentProductIndex;

  const p=C.products[i];

  p.name=$('p_name').value;
  p.badge=$('p_badge').value;
  p.price=Number($('p_price').value||0);
  p.oldPrice=Number($('p_oldPrice').value||0);
  p.volume=Number($('p_volume').value||0);
  p.rating=Number($('p_rating').value||0);
  p.reviews=Number($('p_reviews').value||0);
  p.main=$('p_main').value;
  p.description=$('p_description').value;


  p.additionalInfo=[];

  for(let j=0;j<4;j++){

    p.additionalInfo.push([
      $('info_title_'+j).value,
      $('info_value_'+j).value
    ]);

  }


  p.faqs=[];

  for(let j=0;j<4;j++){

    p.faqs.push([
      $('faq_q_'+j).value,
      $('faq_a_'+j).value
    ]);

  }


  p.subs=[];

  for(let j=0;j<4;j++){

    p.subs.push(
      $('sub_'+j).value
    );

  }


  p.descBanners=[];

  for(let j=0;j<2;j++){

    p.descBanners.push(
      $('desc_'+j).value
    );

  }


  p.dist={};

  [5,4,3,2,1].forEach(n=>{

    p.dist[n]=Number(
      $('dist_'+n).value||0
    );

  });

}


/* ================= SAVE PRODUCT ================= */

async function saveProduct(){

  try{

    collectCurrentProduct();

    const r=await req('save',{
      method:'POST',
      headers:{
        'Content-Type':'application/json'
      },
      body:JSON.stringify(C)
    });

    C=r.config;

    $('productStatus').textContent=
      '✅ Product saved';

    setTimeout(()=>{
      renderProductsList();
      openSection('products');
    },700);

  }catch(e){

    alert(e.message);

  }

}


/* ================= DELETE PRODUCT ================= */

async function deleteProduct(){

  const i=currentProductIndex;

  const p=C.products[i];

  if(!p)return;


  const ok=confirm(
    'Product "'+
    (p.name||'')+
    '" delete karna hai?'
  );

  if(!ok)return;


  C.products.splice(i,1);


  try{

    const r=await req('save',{
      method:'POST',
      headers:{
        'Content-Type':'application/json'
      },
      body:JSON.stringify(C)
    });

    C=r.config;

    renderProductsList();

    openSection('products');

  }catch(e){

    alert(e.message);

  }

}


/* ================= SINGLE IMAGE UPLOAD ================= */

async function uploadProductImage(input,type,index){

  if(!input.files[0])return;

  try{

    const fd=new FormData();

    fd.append(
      'file',
      input.files[0]
    );

    const r=await req('upload',{
      method:'POST',
      body:fd
    });


    if(type==='main'){

      $('p_main').value=r.url;

    }

    if(type==='sub'){

      $('sub_'+index).value=r.url;

    }

    if(type==='desc'){

      $('desc_'+index).value=r.url;

    }


    updateSubPreview();
    updateDescPreview();

  }catch(e){

    alert(e.message);

  }

}


/* ================= BULK SUB UPLOAD ================= */

async function bulkSubUpload(input){

  const files=[
    ...input.files
  ];

  if(!files.length)return;


  if(files.length!==4){

    alert(
      'Exactly 4 images select karo.'
    );

    input.value='';

    return;

  }


  try{

    for(let i=0;i<4;i++){

      const fd=new FormData();

      fd.append(
        'file',
        files[i]
      );

      const r=await req('upload',{
        method:'POST',
        body:fd
      });

      $('sub_'+i).value=r.url;

    }


    updateSubPreview();

    $('productStatus').textContent=
      '✅ 4 Sub Images uploaded';

  }catch(e){

    alert(e.message);

  }

}


/* ================= BULK DESCRIPTION BANNER ================= */

async function bulkDescUpload(input){

  const files=[
    ...input.files
  ];

  if(!files.length)return;


  if(files.length!==2){

    alert(
      'Exactly 2 images select karo.'
    );

    input.value='';

    return;

  }


  try{

    for(let i=0;i<2;i++){

      const fd=new FormData();

      fd.append(
        'file',
        files[i]
      );

      const r=await req('upload',{
        method:'POST',
        body:fd
      });

      $('desc_'+i).value=r.url;

    }


    updateDescPreview();

    $('productStatus').textContent=
      '✅ 2 Description Banners uploaded';

  }catch(e){

    alert(e.message);

  }

}


/* ================= PREVIEWS ================= */

function updateSubPreview(){

  const box=$('subPreview');

  if(!box)return;

  box.innerHTML='';


  for(let i=0;i<4;i++){

    const input=$('sub_'+i);

    if(!input)continue;

    const img=document.createElement('img');

    img.src=input.value||'';

    box.appendChild(img);

  }

}


function updateDescPreview(){

  const box=$('descPreview');

  if(!box)return;

  box.innerHTML='';


  for(let i=0;i<2;i++){

    const input=$('desc_'+i);

    if(!input)continue;

    const img=document.createElement('img');

    img.src=input.value||'';

    box.appendChild(img);

  }

}


/* ================= ASSET UPLOAD ================= */

async function uploadAsset(input,type){

  if(!input.files[0])return;


  try{

    const fd=new FormData();

    fd.append(
      'file',
      input.files[0]
    );

    const r=await req('upload',{
      method:'POST',
      body:fd
    });


    if(type==='logo'){

      $('logoPath').value=r.url;

    }

    if(type==='rightLogo'){

      $('rightLogoPath').value=r.url;

    }

    if(type==='hb1'){

      $('hb1').value=r.url;

    }

    if(type==='hb2'){

      $('hb2').value=r.url;

    }

    if(type==='detailBanner'){

      $('detailBanner').value=r.url;

    }

    if(type==='mystery'){

      $('mysteryImage').value=r.url;

    }

    if(type==='qr'){

      $('paymentQr').value=r.url;

      $('paymentQr2').value=r.url;

    }

  }catch(e){

    alert(e.message);

  }

}


/* ================= SAVE OFFER ================= */

async function saveSettings(){

  try{

    C.settings.buyProducts=
      Number($('buy').value||1);

    C.settings.freeProducts=
      Number($('free').value||0);

    C.settings.codAdvance=
      Number($('cod').value||0);

    C.settings.mysteryGiftPrice=
      Number($('mysteryPrice').value||0);


    const r=await req('save',{
      method:'POST',
      headers:{
        'Content-Type':'application/json'
      },
      body:JSON.stringify(C)
    });

    C=r.config;

    $('offerStatus').textContent=
      '✅ Offer saved';

  }catch(e){

    alert(e.message);

  }

}


/* ================= SAVE RETURNING ================= */

async function saveReturning(){

  try{

    C.returningOffer={

      enabled:
        $('returningEnabled').checked,

      percent:
        Number(
          $('returningPercent').value||0
        ),

      resetSeconds:
        Number(
          $('returningResetSeconds').value||6
        ),

      message:
        $('returningMessage').value,

      label:
        $('returningLabel').value,

      animation:
        $('returningAnimation').checked

    };


    const r=await req('save',{
      method:'POST',
      headers:{
        'Content-Type':'application/json'
      },
      body:JSON.stringify(C)
    });

    C=r.config;

    $('returningStatus').textContent=
      '✅ Returning offer saved';

  }catch(e){

    alert(e.message);

  }

}


/* ================= SAVE BRAND ================= */

async function saveBrand(){

  try{

    C.site=C.site||{};

    C.site.brand=
      $('brandName').value.trim();

    C.site.logo=
      $('logoPath').value;

    C.navRightLogo=
      $('rightLogoPath').value;


    C.theme.primary=
      $('primary').value;

    C.theme.secondary=
      $('secondary').value;

    C.theme.accent=
      $('accent').value;

    C.theme.logoColor=
      $('logoColor').value;

    C.theme.background=
      $('background').value;

    C.theme.text=
      $('text').value;


    const r=await req('save',{
      method:'POST',
      headers:{
        'Content-Type':'application/json'
      },
      body:JSON.stringify(C)
    });

    C=r.config;

    $('brandStatus').textContent=
      '✅ Brand & Theme saved';

    $('adminTitle').textContent=
      '⚙️ '+C.site.brand+' Admin Panel';

  }catch(e){

    alert(e.message);

  }

}


/* ================= SAVE HOME ================= */

async function saveHome(){

  try{

    C.homeBanners=[
      $('hb1').value,
      $('hb2').value
    ];

    C.detailBanner=
      $('detailBanner').value;

    C.offerTicker=
      $('offerTicker').value;

    C.mysteryImage=
      $('mysteryImage').value;


    const r=await req('save',{
      method:'POST',
      headers:{
        'Content-Type':'application/json'
      },
      body:JSON.stringify(C)
    });

    C=r.config;

    $('homeStatus').textContent=
      '✅ Home saved';

  }catch(e){

    alert(e.message);

  }

}


/* ================= SAVE PAYMENT ================= */

async function savePayment(){

  try{

    C.paymentQr=
      $('paymentQr2').value;

    C.paytmStatus=
      $('paytmStatus').value;


    const r=await req('save',{
      method:'POST',
      headers:{
        'Content-Type':'application/json'
      },
      body:JSON.stringify(C)
    });

    C=r.config;

    $('paymentStatus').textContent=
      '✅ Payment settings saved';

  }catch(e){

    alert(e.message);

  }

}


/* ================= PASSWORD ================= */

async function changePassword(){

  const v=$('newPass').value;


  if(v.length<8){

    alert(
      'Password minimum 8 characters hona chahiye.'
    );

    return;

  }


  try{

    await req('change_password',{
      method:'POST',
      headers:{
        'Content-Type':'application/json'
      },
      body:JSON.stringify({
        password:v
      })
    });


    $('newPass').value='';

    $('securityStatus').textContent=
      '✅ Password changed successfully';

  }catch(e){

    alert(e.message);

  }

}


/* ================= LOGOUT ================= */

async function logout(){

  try{
    await req('logout');
  }catch(e){}

  location.reload();

}


/* ================= ESCAPE ================= */

function esc(value){

  return String(value??'')
    .replace(/[&<>\"']/g,c=>({

      '&':'&amp;',
      '<':'&lt;',
      '>':'&gt;',
      '"':'&quot;',
      "'":'&#039;'

    }[c]));

}


function escAttr(value){

  return esc(value);

}

</script>

</body>
</html>
