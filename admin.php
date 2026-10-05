<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>TENVER Admin</title><style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#f4f7f6;color:#14211f}.top{background:#052925;color:#fff;padding:15px;position:sticky;top:0;z-index:5}.top b{font-size:20px}.wrap{max-width:900px;margin:auto;padding:14px}.login{max-width:420px;margin:12vh auto;background:#fff;padding:24px;border-radius:18px;box-shadow:0 8px 30px #0001}.login h1{text-align:center}.input,textarea,select{width:100%;padding:11px;border:1px solid #ccd8d5;border-radius:9px;background:#fff}.btn{border:0;border-radius:9px;padding:11px 14px;font-weight:800;background:#052925;color:#fff;cursor:pointer}.btn.alt{background:#0b665b}.btn.red{background:#a62828}.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.card{background:#fff;border-radius:16px;padding:16px;margin:12px 0;box-shadow:0 3px 15px #0000000a}.card h2{margin:0 0 14px;font-size:18px}.field{margin:9px 0}.field label{display:block;font-size:12px;font-weight:800;margin-bottom:5px}.product{border:1px solid #dfe7e5;border-radius:14px;padding:12px;margin:10px 0}.product-head{display:flex;gap:10px;align-items:center}.product-head img{width:64px;height:64px;border-radius:10px;object-fit:cover;background:#eee}.product-head b{flex:1}.row{display:flex;gap:8px;align-items:center}.row>*{flex:1}.upload{font-size:11px}.savebar{position:sticky;bottom:10px;background:#fff;padding:10px;border-radius:14px;box-shadow:0 5px 25px #0002;display:flex;gap:8px;align-items:center}.status{font-size:12px;color:#347a43}.tabs{display:flex;gap:7px;overflow:auto;margin-bottom:10px}.tabs button{white-space:nowrap;border:1px solid #ccd8d5;background:#fff;border-radius:20px;padding:9px 13px}.hint{font-size:12px;color:#687875}.hidden{display:none}.admin-section{display:none}.admin-section.active{display:block}.bulk-upload{border:2px dashed #ccd8d5;border-radius:12px;padding:14px;margin:10px 0;background:#f8fbfa}.bulk-upload b{display:block;margin-bottom:5px}.product-toolbar{display:flex;justify-content:space-between;gap:10px;align-items:center;position:sticky;top:70px;z-index:3;background:#fff;padding:10px;border:1px solid #e2e9e7;border-radius:12px;margin:8px 0}.product-jumps{display:flex;gap:7px;overflow:auto;padding:6px 0 10px;position:sticky;top:132px;z-index:2;background:#fff}.product-jump{white-space:nowrap;border:1px solid #ccd8d5;background:#fff;border-radius:18px;padding:8px 11px;font-size:11px;font-weight:800}.product .product-head{position:sticky;top:190px;background:#fff;padding:8px 0;z-index:1}.product .btn.red{flex:none}.product{scroll-margin-top:200px}@media(max-width:600px){.grid{grid-template-columns:1fr}.wrap{padding:10px}.card{padding:13px}}
</style></head><body>
<div id="login" class="login"><h1>🛠️ TENVER Admin</h1><p class="hint">Login karke site ko phone se manage karo.</p><input id="pass" class="input" type="password" placeholder="Admin password"><button class="btn" style="width:100%;margin-top:12px" onclick="login()">LOGIN</button><p id="loginMsg" class="hint"></p></div>
<div id="app" class="hidden"><div class="top"><b>⚙️ TENVER Admin Panel</b><button class="btn" style="float:right;padding:7px 10px;background:#fff;color:#052925" onclick="logout()">Logout</button></div><div class="wrap">
<div class="tabs"><button onclick="go('offer')">Offer</button><button onclick="go('theme')">Theme</button><button onclick="go('home')">Home</button><button onclick="go('products')">Products</button><button onclick="go('payment')">Payment</button><button onclick="go('security')">Security</button></div>
<section id="offer" class="card admin-section"><h2>🎁 Offer & Checkout</h2><div class="grid"><div class="field"><label>Buy Products</label><input id="buy" class="input" type="number" min="1"></div><div class="field"><label>Free Products</label><input id="free" class="input" type="number" min="0"></div><div class="field"><label>COD Advance ₹</label><input id="cod" class="input" type="number" min="0"></div><div class="field"><label>Mystery Gift MRP ₹</label><input id="mysteryPrice" class="input" type="number" min="0"></div></div></section>
<section id="theme" class="card admin-section"><h2>🎨 Theme & Logo</h2><div class="field"><label>Main Header Logo (center)</label><input id="logoPath" class="input"><input class="upload" type="file" accept="image/*" onchange="uploadAsset(this,'logo')"></div><div class="field"><label>Right Header Logo (optional)</label><input id="rightLogoPath" class="input"><input class="upload" type="file" accept="image/*" onchange="uploadAsset(this,'rightLogo')"></div><div class="grid"><div class="field"><label>Main Theme</label><input id="primary" class="input" type="color"></div><div class="field"><label>Secondary Theme</label><input id="secondary" class="input" type="color"></div><div class="field"><label>Accent</label><input id="accent" class="input" type="color"></div><div class="field"><label>Logo Color</label><input id="logoColor" class="input" type="color"></div><div class="field"><label>Page Background</label><input id="background" class="input" type="color"></div><div class="field"><label>Text Color</label><input id="text" class="input" type="color"></div></div><p class="hint">Logo image bhi replace kar sakte ho. Neeche upload controls mein site assets update karo.</p></section>
<section id="home" class="card admin-section"><h2>🏠 Home Assets</h2><div class="field"><label>Banner 1</label><input id="hb1" class="input" value="banners/banner1.jpg"><input class="upload" type="file" accept="image/*" onchange="uploadAsset(this,'hb1')"></div><div class="field"><label>Banner 2</label><input id="hb2" class="input" value="banners/banner2.jpg"><input class="upload" type="file" accept="image/*" onchange="uploadAsset(this,'hb2')"></div><div class="field"><label>Product Detail Banner</label><input id="detailBanner" class="input"><input class="upload" type="file" accept="image/*" onchange="uploadAsset(this,'detailBanner')"></div><div class="field"><label>Scrolling Offer Headline</label><input id="offerTicker" class="input" placeholder="Diwali Sale • BUY 1 GET 4 FREE • GET A CHANCE TO WIN iPhone 18 Pro"></div><div class="field"><label>Mystery Box Image</label><input id="mysteryImage" class="input"><input class="upload" type="file" accept="image/*" onchange="uploadAsset(this,'mystery')"></div><div class="field"><label>Payment QR Image</label><input id="paymentQr" class="input"><input class="upload" type="file" accept="image/*" onchange="uploadAsset(this,'qr')"></div></section>
<section id="payment" class="card admin-section"><h2>💳 Payment</h2><p class="hint">QR path upar change kar sakte ho. Real payment verification ke liye gateway/backend alag connect karna hoga.</p><div class="field"><label>Payment QR Path</label><input id="paymentQr2" class="input"></div></section>
<section id="products" class="card admin-section"><h2>🧴 Products</h2><p class="hint">Name, price, MRP, rating, reviews, description, badges aur image paths yahin se change karo. Image upload karke generated path field mein set ho jayega.</p><div id="productsBox"></div></section>
<section id="security" class="card admin-section"><h2>🔐 Admin Password</h2><div class="row"><input id="newPass" class="input" type="password" placeholder="New password (8+ characters)"><button class="btn alt" onclick="changePassword()">Change Password</button></div></section><div class="savebar"><button class="btn" onclick="saveAll()">💾 SAVE ALL CHANGES</button><span id="status" class="status"></span></div>
</div></div>
<script>
let C=null; const $=id=>document.getElementById(id);
async function req(action,opts={}){const r=await fetch('api.php?action='+action,opts);const j=await r.json();if(!r.ok)throw new Error(j.error||'Error');return j}
async function login(){try{await req('login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({password:$('pass').value})});$('login').classList.add('hidden');$('app').classList.remove('hidden');await load()}catch(e){$('loginMsg').textContent=e.message}}
async function load(){C=await req('config');$('buy').value=C.settings.buyProducts;$('free').value=C.settings.freeProducts;$('cod').value=C.settings.codAdvance;$('mysteryPrice').value=C.settings.mysteryGiftPrice;for(const k of ['primary','secondary','accent','logoColor','background','text'])$(k).value=C.theme[k];$('logoPath').value=(C.site&&C.site.logo)||'assets/logo.png';$('leftLogoPath').value=C.navRightLogo||'';$('hb1').value=C.homeBanners[0]||'';$('hb2').value=C.homeBanners[1]||'';$('detailBanner').value=C.detailBanner||'banners/banner2.jpg';$('offerTicker').value=C.offerTicker||'Diwali Sale • BUY 1 GET 4 FREE • GET A CHANCE TO WIN iPhone 18 Pro';$('mysteryImage').value=C.mysteryImage||'';$('paymentQr').value=C.paymentQr||'';$('paymentQr2').value=C.paymentQr||'';renderProducts()}
function renderProducts(){
  const list=Array.isArray(C.products)?C.products:[];
  const quick=list.map((p,i)=>`<button class="product-jump" onclick="jumpProduct(${i})">${i+1}. ${esc(p.name||('Product '+(i+1)))}</button>`).join('');
  $('productsBox').innerHTML=`
    <div class="product-toolbar">
      <div><b>All Products (${list.length})</b><div class="hint">Neeche saare products ek saath hain. Scroll karke edit karo.</div></div>
      <button class="btn red" onclick="deleteProductPrompt()">🗑️ DELETE PRODUCT</button>
    </div>
    <div class="product-jumps">${quick}</div>
    ${list.map((p,i)=>`<div class="product" id="productCard${i}">
      <div class="product-head">
        <img id="prev${i}" src="${esc(p.main||'')}" onerror="this.style.opacity=.35">
        <div style="flex:1"><b>Product ${i+1}: ${esc(p.name||'Unnamed')}</b><div class="hint">ID: ${esc(p.id??i+1)}</div></div>
        <button class="btn red" type="button" onclick="deleteProduct(${i})">🗑️ Delete</button>
      </div>
      <div class="grid">
        <div class="field"><label>Name</label><input class="input" data-p="${i}" data-k="name" value="${esc(p.name)}"></div>
        <div class="field"><label>Badge</label><input class="input" data-p="${i}" data-k="badge" value="${esc(p.badge)}"></div>
        <div class="field"><label>Price ₹</label><input class="input" type="number" data-p="${i}" data-k="price" value="${p.price??0}"></div>
        <div class="field"><label>Old Price ₹</label><input class="input" type="number" data-p="${i}" data-k="oldPrice" value="${p.oldPrice??0}"></div>
        <div class="field"><label>Volume ml</label><input class="input" type="number" data-p="${i}" data-k="volume" value="${p.volume??0}"></div>
        <div class="field"><label>Rating</label><input class="input" type="number" step="0.01" min="0" max="5" data-p="${i}" data-k="rating" value="${p.rating??0}"></div>
        <div class="field"><label>Review Count</label><input class="input" type="number" data-p="${i}" data-k="reviews" value="${p.reviews??0}"></div>
        <div class="field"><label>Main Image Path</label><input class="input" data-p="${i}" data-k="main" value="${esc(p.main)}"><input class="upload" type="file" accept="image/*" onchange="upload(this,${i},'main')"></div>
      </div>
      <div class="field"><label>Description</label><textarea class="input" rows="4" data-p="${i}" data-k="description">${esc(p.description)}</textarea></div>
      <h4>Additional Information</h4>
      <div class="grid">${[0,1,2,3].map(j=>`<div class="field"><label>Info ${j+1} Title</label><input class="input" data-p="${i}" data-info-title="${j}" value="${esc(p.additionalInfo?.[j]?.[0]||'')}"></div><div class="field"><label>Info ${j+1} Value</label><input class="input" data-p="${i}" data-info-value="${j}" value="${esc(p.additionalInfo?.[j]?.[1]||'')}"></div>`).join('')}</div>
      <h4>FAQs</h4>${[0,1,2,3].map(j=>`<div class="field"><label>FAQ ${j+1} Question</label><input class="input" data-p="${i}" data-faq-q="${j}" value="${esc(p.faqs?.[j]?.[0]||'')}"><label>Answer</label><textarea class="input" rows="2" data-p="${i}" data-faq-a="${j}">${esc(p.faqs?.[j]?.[1]||'')}</textarea></div>`).join('')}
      <h4>4 Sub Images</h4>
      <div class="bulk-upload"><b>⚡ Upload all 4 sub images together</b><span class="hint">Exactly 4 images select karo — automatically Sub Image 1–4 me set hongi.</span><input class="upload" type="file" accept="image/*" multiple onchange="uploadSubBulk(this,${i})"></div>
      <div class="grid">${(p.subs||[]).map((x,j)=>`<div class="field"><label>Sub Image ${j+1}</label><input class="input" data-p="${i}" data-sub="${j}" value="${esc(x)}"><input class="upload" type="file" accept="image/*" onchange="upload(this,${i},'sub',${j})"></div>`).join('')}</div>
      <h4>2 Description Banners</h4><div class="grid">${(p.descBanners||[]).map((x,j)=>`<div class="field"><label>Description Banner ${j+1}</label><input class="input" data-p="${i}" data-desc="${j}" value="${esc(x)}"><input class="upload" type="file" accept="image/*" onchange="upload(this,${i},'desc',${j})"></div>`).join('')}</div>
      <h4>Review Distribution</h4><div class="grid">${[5,4,3,2,1].map(n=>`<div class="field"><label>${n}★ Reviews</label><input class="input" type="number" data-p="${i}" data-dist="${n}" value="${p.dist?.[n]||0}"></div>`).join('')}</div>
    </div>`).join('')}`;
}
function jumpProduct(i){const el=$('productCard'+i);if(el)el.scrollIntoView({behavior:'smooth',block:'start'})}
function deleteProduct(i){
  if(!Array.isArray(C.products)||!C.products[i])return;
  const name=C.products[i].name||('Product '+(i+1));
  if(!confirm('Delete '+name+'? This will be applied after SAVE ALL CHANGES.'))return;
  C.products.splice(i,1);
  renderProducts();
  $('status').textContent='Product deleted locally — press SAVE ALL CHANGES to apply.';
}
function deleteProductPrompt(){
  if(!Array.isArray(C.products)||!C.products.length)return alert('No products found.');
  const n=prompt('Kaunsa product delete karna hai? 1 se '+C.products.length+' tak number enter karo:');
  if(n===null)return;
  const i=Number(n)-1;
  if(!Number.isInteger(i)||i<0||i>=C.products.length)return alert('Invalid product number.');
  deleteProduct(i);
}
function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}
async function upload(input,i,type,j){if(!input.files[0])return;try{const fd=new FormData();fd.append('file',input.files[0]);const r=await req('upload',{method:'POST',body:fd});if(type==='main')C.products[i].main=r.url;if(type==='sub')C.products[i].subs[j]=r.url;if(type==='desc')C.products[i].descBanners[j]=r.url;renderProducts();$('status').textContent='Image uploaded — Save All Changes';}catch(e){alert(e.message)}}
async function uploadSubBulk(input,i){
  const files=Array.from(input.files||[]);
  if(!files.length)return;
  if(files.length!==4){alert('Please select exactly 4 sub images.');input.value='';return}
  try{
    for(let j=0;j<4;j++){
      const fd=new FormData();
      fd.append('file',files[j]);
      const r=await req('upload',{method:'POST',body:fd});
      C.products[i].subs[j]=r.url;
      $('status').textContent=`Uploading sub images: ${j+1}/4`;
    }
    renderProducts();
    $('status').textContent='✅ All 4 sub images uploaded — Save All Changes';
  }catch(e){alert(e.message);$('status').textContent='Upload failed';}
  finally{input.value='';}
}
async function uploadAsset(input,type){if(!input.files[0])return;try{const fd=new FormData();fd.append('file',input.files[0]);const r=await req('upload',{method:'POST',body:fd});if(type==='logo')$('logoPath').value=r.url;if(type==='rightLogo')$('rightLogoPath').value=r.url;if(type==='hb1')$('hb1').value=r.url;if(type==='hb2')$('hb2').value=r.url;if(type==='detailBanner')$('detailBanner').value=r.url;if(type==='mystery')$('mysteryImage').value=r.url;if(type==='qr'){$('paymentQr').value=r.url;$('paymentQr2').value=r.url} $('status').textContent='Image uploaded — Save All Changes';}catch(e){alert(e.message)}}
function collect(){C.settings.buyProducts=+$('buy').value;C.settings.freeProducts=+$('free').value;C.settings.codAdvance=+$('cod').value;C.settings.mysteryGiftPrice=+$('mysteryPrice').value;for(const k of ['primary','secondary','accent','logoColor','background','text'])C.theme[k]=$(k).value;C.site=C.site||{};C.site.logo=$('logoPath').value;C.navRightLogo=$('rightLogoPath').value;C.homeBanners=[$('hb1').value,$('hb2').value];C.detailBanner=$('detailBanner').value;C.offerTicker=$('offerTicker').value;C.mysteryImage=$('mysteryImage').value;C.paymentQr=$('paymentQr2').value;document.querySelectorAll('[data-p]').forEach(el=>{const i=+el.dataset.p,p=C.products[i];if(el.dataset.k){let v=el.value;if(['price','oldPrice','volume','rating','reviews'].includes(el.dataset.k))v=+v;p[el.dataset.k]=v}else if(el.dataset.sub!==undefined)p.subs[+el.dataset.sub]=el.value;else if(el.dataset.desc!==undefined)p.descBanners[+el.dataset.desc]=el.value;else if(el.dataset.dist!==undefined)p.dist[+el.dataset.dist]=+el.value;else if(el.dataset.infoTitle!==undefined){p.additionalInfo=p.additionalInfo||[[],[],[],[]];p.additionalInfo[+el.dataset.infoTitle][0]=el.value}else if(el.dataset.infoValue!==undefined){p.additionalInfo=p.additionalInfo||[[],[],[],[]];p.additionalInfo[+el.dataset.infoValue][1]=el.value}else if(el.dataset.faqQ!==undefined){p.faqs=p.faqs||[[],[],[],[]];p.faqs[+el.dataset.faqQ][0]=el.value}else if(el.dataset.faqA!==undefined){p.faqs=p.faqs||[[],[],[],[]];p.faqs[+el.dataset.faqA][1]=el.value}}) }
async function saveAll(){try{collect();const r=await req('save',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(C)});C=r.config;$('status').textContent='✅ Saved. Customer site will use the new settings on refresh.'}catch(e){alert(e.message)}}
async function changePassword(){const v=$('newPass').value;if(v.length<8)return alert('Password must be at least 8 characters.');try{await req('change_password',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({password:v})});$('newPass').value='';$('status').textContent='✅ Password changed.'}catch(e){alert(e.message)}}
function go(id){document.querySelectorAll('.admin-section').forEach(x=>x.classList.remove('active'));const el=$(id);if(el){el.classList.add('active');window.scrollTo({top:0,behavior:'smooth'})}}

go('offer');async function logout(){await req('logout');location.reload()}
</script></body></html>
