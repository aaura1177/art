@extends('layouts.app')

@section('content')
<style>
    :root{
      --bg:#ffffff;
      --text:#111111;
      --muted:#444444;
      --border:#e6e6e6;
      --blue:#1060ff;
      --blue-weak:#e8f0ff;
      --radius:14px;
      --shadow:0 6px 24px rgba(0,0,0,.06);
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family: Inter, "Noto Sans Devanagari", system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji";
      background:var(--bg);
      color:var(--text);
      line-height:1.6;
    }
    .wrap{max-width:1100px;margin:40px auto;padding:0 20px}
    .card{
      background:#fff;
      border:1px solid var(--border);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
    }
    header{padding:22px 24px;border-bottom:1px solid var(--border);}
    header h1{margin:0 0 6px 0;font-size:clamp(22px,2.6vw,30px)}
    header p{margin:0;color:var(--muted)}
    .steps{display:grid;grid-template-columns:1fr;gap:0}
    .step{padding:18px 18px 24px;border-bottom:1px dashed var(--border)}
    .step:last-child{border-bottom:none}
    .title{margin:0 0 6px 0;font-size:clamp(16px,1.6vw,20px)}
    .text{margin:0;color:var(--muted);font-size:16px}
    figure{margin:12px 0 0 0;border:1px solid var(--border);border-radius:10px;overflow:hidden;background:#fff}
    figure img{width:100%;height:auto;display:block}
    figcaption{padding:8px 10px;font-size:13px;color:#666;background:#fafafa;border-top:1px solid var(--border)}
    .badge{
      display:inline-grid;place-items:center;
      min-width:30px;height:30px;margin-right:8px;
      background:var(--blue-weak);color:var(--blue);
      border:1px solid #d6e2ff;border-radius:9px;
      font-weight:700;
    }
    .btn{
      appearance:none;border:none;cursor:pointer;
      background:var(--blue);color:#fff;border-radius:10px;
      padding:10px 14px;font-weight:600;font-size:14px;
      box-shadow:0 6px 16px rgba(16,96,255,.22);
    }
    .footer{padding:16px 18px;background:#fafafa;border-top:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap}
    .tip{margin:16px 18px;padding:12px 14px;border-radius:10px;background:#f5f9ff;border:1px solid #dbe7ff;color:#1a3a7a}
    .inline{display:flex;align-items:center;gap:8px;margin:0 0 6px 0}
    @media(min-width:860px){.steps{grid-template-columns:1fr 1fr} .step{border-bottom:1px dashed var(--border)}}
</style>

@php
  // Hardcoded CDN base for instruction images
  $CDN = 'https://artisanstocks-net.s3.ap-south-1.amazonaws.com/stock/instructions/';
@endphp

<div class="card mb-3">
  <div class="wrap">
    <div class="card">
      <header>
        <h1>PO से सप्लायर इनवॉइस बनाना — आसान भाषा में समझें</h1>
        <p>नीचे दिए चरण बिल्कुल साधारण हैं। किसी तकनीकी जानकारी की ज़रूरत नहीं है। बस तस्वीर को देखें और उसी तरह करें।</p>
      </header>

      <section class="steps">
        <article class="step">
          <div class="inline"><span class="badge">1</span><h3 class="title">सबसे पहले <b>Purchase Orders</b> टैब खोलें</h3></div>
          <p class="text">यहाँ आपकी सभी खरीद ऑर्डर (PO) दिखेंगे।</p>
          <figure>
            <img src="{{ $CDN }}purchaseordertab.png" alt="Purchase Orders पेज">
            <figcaption>Purchase Orders सूची</figcaption>
          </figure>
        </article>

        <article class="step">
          <div class="inline"><span class="badge">2</span><h3 class="title"><b>Approve</b> बटन दबाकर PO को मंज़ूर करें</h3></div>
          <p class="text">हरे टिक (✓) वाले बटन पर क्लिक करने से PO “Approved” हो जाता है।</p>
          <figure>
            <img src="{{ $CDN }}approvePo.png" alt="Approve PO बटन">
            <figcaption>Approve (✓) बटन</figcaption>
          </figure>
        </article>

        <article class="step">
          <div class="inline"><span class="badge">3</span><h3 class="title"><b>Accepted Purchase Orders</b> टैब खोलें</h3></div>
          <p class="text">मंज़ूर किए गए POs यहाँ दिखेंगे।</p>
          <figure>
            <img src="{{ $CDN }}acceptedPos.png" alt="Accepted Purchase Orders टैब">
            <figcaption>Accepted Purchase Orders</figcaption>
          </figure>
        </article>

        <article class="step">
          <div class="inline"><span class="badge">4</span><h3 class="title">जिस PO/POs का इनवॉइस बनाना है, उसे <b>चेक</b> करें</h3></div>
          <p class="text">आप एक या एक से ज़्यादा PO चुन सकते हैं।</p>
          <figure>
            <img src="{{ $CDN }}selectPos.png" alt="कई POs चुनना">
            <figcaption>PO चुनें</figcaption>
          </figure>
        </article>

        <article class="step">
          <div class="inline"><span class="badge">5</span><h3 class="title"><b>Raise Invoice for Selected POs</b> बटन पर क्लिक करें</h3></div>
          <p class="text">ऊपर दायीं तरफ नीले बटन से इनवॉइस बनाना शुरू करें।</p>
          <figure>
            <img src="{{ $CDN }}raiseinvoiceforselectedPos.png" alt="Raise Invoice for Selected POs बटन">
            <figcaption>Raise Invoice बटन</figcaption>
          </figure>
        </article>

        <article class="step">
          <div class="inline"><span class="badge">6</span><h3 class="title">इनवॉइस स्क्रीन में <b>+ Add / Remove PO</b> से PO जोड़ें/हटाएँ</h3></div>
          <p class="text">अगर बदलाव नहीं करना है, तो बस नीचे दी जानकारी भरें: सप्लायर इनवॉइस नंबर, E-Way Bill, दिनांक आदि और फिर सबमिट करें।</p>
          <figure>
            <img src="{{ $CDN }}toaddorremovemorePos.png" alt="इनवॉइस फॉर्म में Add/Remove PO बटन">
            <figcaption>इनवॉइस फॉर्म — Add / Remove PO</figcaption>
          </figure>
        </article>

        <article class="step">
          <div class="inline"><span class="badge">7</span><h3 class="title"><b>Invoices Multiple</b> टैब में मल्टी-PO इनवॉइस देखें</h3></div>
          <p class="text">यहाँ वे इनवॉइस दिखते हैं जिनमें एक से ज़्यादा PO शामिल हैं।</p>
          <figure>
            <img src="{{ $CDN }}multipleinvoicespage.png" alt="Invoices Multiple पेज">
            <figcaption>Invoices Multiple</figcaption>
          </figure>
        </article>

        <article class="step">
          <div class="inline"><span class="badge">8</span><h3 class="title">किसी इनवॉइस को बदलना हो तो <b>Edit</b> (पीला) बटन दबाएँ</h3></div>
          <p class="text">एडिट मोड में आप सभी डिटेल्स अपडेट कर सकते हैं।</p>
          <figure>
            <img src="{{ $CDN }}editmultiplePosinvoice.png" alt="Edit इनवॉइस बटन">
            <figcaption>Edit (पीला) बटन</figcaption>
          </figure>
        </article>

        <article class="step">
          <div class="inline"><span class="badge">9</span><h3 class="title">एडिट करते समय भी <b>+ Add / Remove PO</b> से PO जोड़ें/हटाएँ</h3></div>
          <p class="text">सूची से PO चुनें/हटाएँ और सेव कर दें।</p>
          <figure>
            <img src="{{ $CDN }}selectPostoaddorremove.png" alt="एडिट में Add/Remove PO">
            <figcaption>एडिट स्क्रीन का Add / Remove PO मोडल</figcaption>
          </figure>
        </article>
      </section>

      <div class="tip"><b>टिप:</b> अगर आपको सिर्फ एक PO का इनवॉइस चाहिए तो चरण 4 में केवल एक ही PO चुनें। बाद में भी Add/Remove से बदलाव कर सकते हैं।</div>
    </div>
  </div>
</div>
@endsection
