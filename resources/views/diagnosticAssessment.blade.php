<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>اختبار تشخيص المستوى</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root{
      --primary:#7c3aed;
      --primary-dark:#5b21b6;
      --primary-soft:#ede9fe;
      --blue:#60a5fa;
      --green:#34d399;
      --orange:#f59e0b;
      --red:#ef4444;
      --text:#172554;
      --muted:#64748b;
      --white:#ffffff;
      --card:rgba(255,255,255,.72);
      --stroke:rgba(124,58,237,.16);
      --shadow:0 25px 70px rgba(81,56,166,.20);
      --radius:30px;
    }

    *{box-sizing:border-box;margin:0;padding:0}
    *{font-family:'Cairo', sans-serif;}

    body{
      font-family:'Cairo', sans-serif;
      direction:rtl;
      color:var(--text);
      background:
        radial-gradient(circle at 10% 8%, rgba(168,85,247,.25), transparent 22%),
        radial-gradient(circle at 90% 3%, rgba(96,165,250,.24), transparent 24%),
        radial-gradient(circle at 92% 87%, rgba(192,132,252,.30), transparent 23%),
        radial-gradient(circle at 12% 85%, rgba(125,211,252,.28), transparent 25%),
        linear-gradient(135deg,#f4ecff 0%,#eff6ff 45%,#fff7fb 100%);
      overflow-x:hidden;
    }

    .page{
      position:relative;
      width:min(1400px,95vw);
      min-height:auto;
      margin:18px auto;
      padding:38px 45px 46px;
      border-radius:26px;
      overflow:hidden;
      background:rgba(255,255,255,.20);
      border:1px solid rgba(255,255,255,.65);
      box-shadow:inset 0 0 0 1px rgba(255,255,255,.35);
    }

    .blob{position:absolute;border-radius:45%;filter:blur(.1px);opacity:.55;z-index:0}
    .blob.one{width:240px;height:180px;background:#c4b5fd;top:-80px;left:30px;transform:rotate(25deg)}
    .blob.two{width:260px;height:160px;background:#bfdbfe;top:-55px;right:70px;transform:rotate(-18deg)}
    .blob.three{width:250px;height:210px;background:#c084fc;bottom:-85px;right:-30px}
    .blob.four{width:260px;height:160px;background:#93c5fd;bottom:-50px;left:-35px}

    .dash-line{
      position:absolute;
      border:3px dashed rgba(124,58,237,.26);
      border-color:transparent transparent rgba(124,58,237,.26) transparent;
      border-radius:50%;
      z-index:0;
    }
    .dash-line.top{width:760px;height:170px;top:-16px;left:230px;transform:rotate(8deg)}
    .dash-line.left{width:250px;height:330px;top:295px;left:-130px;transform:rotate(-18deg)}
    .dash-line.bottom{width:700px;height:130px;bottom:58px;left:240px;transform:rotate(-1deg)}
    .dash-line.right{width:330px;height:240px;right:-210px;bottom:240px;transform:rotate(-25deg)}

    .floating-icon{
      position:absolute;
      width:82px;height:82px;
      display:grid;place-items:center;
      border-radius:50%;
      background:rgba(255,255,255,.52);
      border:1px solid rgba(255,255,255,.7);
      box-shadow:0 18px 35px rgba(99,102,241,.18), inset 0 0 22px rgba(255,255,255,.75);
      backdrop-filter:blur(12px);
      z-index:1;
      font-size:36px;
      animation:float 5s ease-in-out infinite;
    }
    .floating-icon.brain{top:58px;left:88px;background:linear-gradient(145deg,#a78bfa,#fff)}
    .floating-icon.book{top:45px;right:67px;background:linear-gradient(145deg,#93c5fd,#fff);animation-delay:.5s}
    .floating-icon.target{top:40px;left:520px;width:64px;height:64px;font-size:31px;background:linear-gradient(145deg,#fbcfe8,#fff);animation-delay:1s}
    .floating-icon.flask{right:20px;top:340px;width:62px;height:62px;font-size:30px;animation-delay:1.3s}
    .floating-icon.bag{right:30px;top:520px;width:72px;height:72px;font-size:32px;background:linear-gradient(145deg,#c4b5fd,#fff);animation-delay:.9s}
    .star{position:absolute;color:#fbbf24;font-size:32px;filter:drop-shadow(0 7px 12px rgba(245,158,11,.25));z-index:1;animation:twinkle 2.7s infinite ease-in-out}
    .star.s1{left:28px;top:560px}
    .star.s2{right:105px;bottom:108px;font-size:22px}
    .star.s3{left:320px;top:94px;color:white}
    .star.s4{right:370px;top:88px;color:white}

    .layout{
      position:relative;
      z-index:2;
      display:grid;
      grid-template-columns:300px minmax(0,1fr);
      gap:28px;
      align-items:flex-start;
      min-height:720px;
    }

    .side-card{
      position:relative;
      width:300px;
      height:auto;
      min-height:auto;
      align-self:flex-start;
      margin-top:45px;
      padding:26px 18px 22px;
      border-radius:30px;
      background:rgba(255,255,255,.46);
      border:1px solid rgba(255,255,255,.72);
      box-shadow:var(--shadow), inset 0 0 35px rgba(255,255,255,.6);
      backdrop-filter:blur(18px);
      overflow:visible;
    }

    .side-card:before{
      content:"";
      position:absolute;
      inset:0;
      border-radius:30px;
      background:linear-gradient(180deg,rgba(124,58,237,.08),rgba(96,165,250,.08));
      pointer-events:none;
    }

    .robot{
      position:relative;
      width:190px;
      height:200px;
      margin:0 auto 8px;
      display:block;
      z-index:5;
    }

    .robot:before{
      content:"";
      position:absolute;
      width:188px;
      height:188px;
      top:0;
      left:50%;
      transform:translateX(-50%);
      border-radius:50%;
      background:linear-gradient(145deg,#ddd6fe,#f5f3ff);
      z-index:1;
    }

    .head{
      position:absolute;
      top:45px;
      left:50%;
      transform:translateX(-50%);
      width:118px;
      height:88px;
      border-radius:38px 38px 32px 32px;
      background:linear-gradient(145deg,#fff,#dbeafe);
      box-shadow:0 15px 35px rgba(59,130,246,.22);
      border:4px solid #fff;
      z-index:5;
    }

    .face{
      position:absolute;
      inset:24px 16px;
      border-radius:24px;
      background:#101044;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:18px;
      z-index:6;
    }

    .face:before,
    .face:after{
      content:"";
      width:15px;
      height:15px;
      border-radius:50%;
      border-bottom:4px solid #67e8f9;
      display:block;
    }

    .antenna{
      position:absolute;
      top:-30px;
      left:50%;
      width:6px;
      height:34px;
      background:#8b5cf6;
      transform:translateX(-50%);
      border-radius:20px;
      z-index:7;
    }

    .antenna:before{
      content:"";
      position:absolute;
      top:-13px;
      left:50%;
      width:20px;
      height:20px;
      border-radius:50%;
      background:linear-gradient(#a78bfa,#6d28d9);
      transform:translateX(-50%);
    }

    .ear{
      position:absolute;
      top:82px;
      width:26px;
      height:44px;
      border-radius:18px;
      background:linear-gradient(145deg,#8b5cf6,#c4b5fd);
      z-index:4;
    }

    .ear.left{left:31px;}
    .ear.right{right:31px;}

    .body{
      position:absolute;
      top:130px;
      left:50%;
      transform:translateX(-50%);
      width:88px;
      height:68px;
      border-radius:34px 34px 26px 26px;
      background:linear-gradient(145deg,#fff,#dbeafe);
      border:4px solid #fff;
      z-index:4;
    }

    .arm{
      position:absolute;
      top:128px;
      width:24px;
      height:66px;
      border-radius:18px;
      background:linear-gradient(145deg,#fff,#bfdbfe);
      z-index:5;
    }

    .arm.left{
      left:58px;
      transform:rotate(-28deg);
    }

    .arm.right{
      right:55px;
      transform:rotate(138deg);
    }

    .message{
      position:relative;
      margin:0 auto 22px;
      width:220px;
      padding:18px 16px 25px;
      background:rgba(255,255,255,.9);
      border-radius:22px;
      box-shadow:0 20px 40px rgba(15,23,42,.08), inset 0 0 20px rgba(255,255,255,.6);
      text-align:center;
      line-height:1.8;
      font-size:13px;
      color:#334155;
      font-weight:700;
    }

    .message .heart{
      position:absolute;
      bottom:10px;
      left:16px;
      font-size:17px;
      color:#7c3aed;
    }

    .info-list{
      position:relative;
      display:flex;
      flex-direction:column;
      gap:10px;
      margin-top:8px;
    }

    .info-title{
      text-align:center;
      font-weight:900;
      font-size:15px;
      margin-bottom:2px;
      display:flex;
      align-items:center;
      justify-content:center;
      gap:10px;
    }

    .info-title span{
      background:#dbeafe;
      color:#2563eb;
      width:32px;
      height:32px;
      border-radius:11px;
      display:grid;
      place-items:center;
    }

    .info-item{
      display:grid;
      grid-template-columns:46px 1fr;
      gap:10px;
      align-items:center;
      padding:11px 13px;
      border-radius:18px;
      background:rgba(255,255,255,.62);
      box-shadow:0 10px 28px rgba(15,23,42,.06);
      border:1px solid rgba(255,255,255,.7);
    }

    .info-icon{
      width:38px;
      height:38px;
      border-radius:13px;
      display:grid;
      place-items:center;
      font-size:18px;
      color:white;
      box-shadow:0 9px 18px rgba(0,0,0,.11);
    }

    .info-icon i{font-size:16px;}
    .info-icon.purple{background:#8b5cf6}
    .info-icon.green{background:#34d399}
    .info-icon.orange{background:#f59e0b}
    .info-icon.blue{background:#60a5fa}

    .info-item h4{
      font-size:14px;
      margin-bottom:3px;
    }

    .info-item p{
      font-size:13px;
      line-height:1.35;
      font-weight:700;
    }

    .main-card{
      width:90%;
      max-width:950px;
      margin:0 auto;
      height:auto;
      min-height:720px;
      padding:42px 34px 28px;
      border-radius:34px;
      background:rgba(255,255,255,.66);
      border:1px solid rgba(255,255,255,.85);
      box-shadow:var(--shadow), inset 0 0 45px rgba(255,255,255,.75);
      backdrop-filter:blur(20px);
    }

    .title{text-align:center;font-size:35px;font-weight:900;letter-spacing:-.5px}
    .title span{color:var(--primary)}
    .subtitle{text-align:center;margin-top:10px;color:#475569;font-size:16px;font-weight:600}

    .subtitle,
    p{font-weight:500;}

    button{font-weight:700;}

    .progress-wrap{margin:28px 0 24px}
    .progress-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;font-size:15px;font-weight:800;color:#6d28d9;direction:rtl}
    .percent{background:rgba(124,58,237,.10);color:#475569;padding:8px 14px;border-radius:18px;min-width:62px;text-align:center}
    .bar{height:10px;background:#e5e7eb;border-radius:999px;overflow:hidden;box-shadow:inset 0 1px 3px rgba(0,0,0,.08)}
    .bar span{display:block;height:100%;width:0%;background:linear-gradient(90deg,#8b5cf6,#6d28d9);border-radius:999px;box-shadow:0 6px 14px rgba(124,58,237,.35);transition:.35s}

    .question-card{border-radius:24px;background:rgba(255,255,255,.52);border:1px solid rgba(124,58,237,.12);padding:20px 22px;box-shadow:0 12px 35px rgba(15,23,42,.06)}
    .question-meta{display:flex;align-items:center;gap:12px;margin-bottom:14px}
    .number{width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:#f5f3ff;border:2px solid #e9d5ff;color:#7c3aed;font-size:18px;font-weight:900}
    .badge{display:flex;align-items:center;gap:8px;background:#f3efff;color:#6d28d9;border-radius:14px;padding:8px 14px;font-weight:800;font-size:13px}
    .question{display:grid;grid-template-columns:1fr 54px;gap:14px;align-items:center;margin-bottom:18px}
    .question h2{font-size:22px;line-height:1.55;font-weight:900;letter-spacing:-.3px}
    .bulb{width:50px;height:50px;border-radius:15px;background:#ede9fe;display:grid;place-items:center;font-size:25px}
    .bulb i{font-size:22px;color:#7c3aed;}
    .answers{display:flex;flex-direction:column;gap:10px}
    .answer{position:relative;display:grid;grid-template-columns:42px 1fr 50px;gap:12px;align-items:center;min-height:68px;padding:12px 14px;border-radius:18px;background:rgba(255,255,255,.72);border:2px solid rgba(226,232,240,.85);box-shadow:0 8px 22px rgba(15,23,42,.05);cursor:pointer;transition:.25s ease}
    .answer:hover{transform:translateY(-2px);box-shadow:0 14px 28px rgba(124,58,237,.12);border-color:rgba(124,58,237,.35)}
    .letter{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;border:2px solid #e9d5ff;color:#7c3aed;font-size:16px;font-weight:900;background:white}
    .answer p{font-size:17px;font-weight:800;color:#334155;line-height:1.4}
    .answer-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center}
    .answer-icon i{font-size:18px;color:#1e293b}
    .answer:nth-child(1) .answer-icon{background:#dbeafe;}
    .answer:nth-child(2) .answer-icon{background:#ede9fe;}
    .answer:nth-child(3) .answer-icon{background:#d1fae5;}
    .answer:nth-child(4) .answer-icon{background:#ffedd5;}
    .answer.selected{border-color:#8b5cf6;background:rgba(255,255,255,.82);box-shadow:0 14px 30px rgba(124,58,237,.15)}
    .answer.selected .letter{background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:white;border-color:transparent}
    .check{position:absolute;top:50%;left:-11px;transform:translateY(-50%);width:26px;height:26px;border-radius:50%;display:none;place-items:center;background:#7c3aed;color:white;font-size:16px;font-weight:900;box-shadow:0 8px 18px rgba(124,58,237,.35)}
    .answer.selected .check{display:grid}
    .answer.selected .answer-icon{background:#ede9fe;}
    .answer.selected .answer-icon i{color:#6d28d9;}

    .actions{display:grid;grid-template-columns:1fr 1.15fr 1.15fr;gap:16px;margin-top:20px}
    .btn{border:none;border-radius:16px;padding:13px 16px;font-family:inherit;font-size:15px;font-weight:900;cursor:pointer;transition:.25s;box-shadow:0 10px 25px rgba(15,23,42,.06);background:rgba(255,255,255,.65);border:2px solid #e5e7eb;color:#4c1d95}
    .btn:hover{transform:translateY(-2px)}
    .btn.primary{background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:white;border:none;box-shadow:0 14px 30px rgba(124,58,237,.35)}
    .btn.danger{color:#b91c1c}
    .btn .arrow{font-size:17px;margin-inline:6px}
    .btn:disabled{opacity:.55;cursor:not-allowed;transform:none!important}

    .bottom-pills{position:relative;z-index:3;display:flex;justify-content:center;gap:60px;margin-top:16px}
    .pill{min-width:185px;padding:13px 20px;border-radius:22px;background:rgba(255,255,255,.72);border:1px solid rgba(255,255,255,.85);box-shadow:0 14px 34px rgba(99,102,241,.13);display:flex;align-items:center;justify-content:center;gap:10px;font-weight:900;font-size:15px}
    .pill span{font-size:24px}
    .pill.ai span{ color:#7c3aed; }
    .pill.analytics span{ color:#0ea5e9; }
    .pill.plan span{ color:#f59e0b; }

    @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
    @keyframes twinkle{0%,100%{opacity:.45;transform:scale(.85) rotate(0)}50%{opacity:1;transform:scale(1.1) rotate(12deg)}}

    @media(max-width:980px){
      .page{padding:28px 18px}
      .layout{grid-template-columns:1fr}
      .side-card{min-height:auto;order:2;width:100%}
      .main-card{order:1;padding:32px 18px;width:100%}
      .bottom-pills{gap:14px;flex-wrap:wrap}
      .title{font-size:22px}
      .question{grid-template-columns:1fr}
      .question h2{font-size:18px}
      .actions{grid-template-columns:1fr}
      .answer{grid-template-columns:38px 1fr 44px}
      .answer p{font-size:14px}
      .floating-icon{display:none}
    }
    .answer:nth-child(5) .answer-icon {
    background:#fee2e2;
    }

    .answer:nth-child(5) .answer-icon i {
        color:#dc2626;
    }

    .answer:nth-child(5) {
        background: rgba(254, 242, 242, 0.75);
        border-color: rgba(248, 113, 113, 0.35);
    }

    .diagnostic-result-modal {
        position: fixed;
        inset: 0;
        z-index: 1000000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background:
            radial-gradient(circle at 20% 20%, rgba(139, 92, 246, 0.18), transparent 34%),
            radial-gradient(circle at 80% 80%, rgba(96, 165, 250, 0.18), transparent 34%),
            rgba(15, 23, 42, 0.35);
        backdrop-filter: blur(16px);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: 0.25s ease;
    }

    .diagnostic-result-modal.active {
        opacity: 1;
        visibility: visible;
        pointer-events: all;
    }

    .diagnostic-result-card {
        width: min(520px, 94vw);
        padding: 32px 28px;
        border-radius: 32px;
        text-align: center;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(255, 255, 255, 0.85);
        box-shadow: 0 30px 90px rgba(79, 70, 229, 0.22);
        transform: translateY(18px) scale(0.96);
        opacity: 0;
        transition: 0.3s ease;
        direction: rtl;
    }

    .diagnostic-result-modal.active .diagnostic-result-card {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    .diagnostic-result-icon {
        width: 74px;
        height: 74px;
        margin: 0 auto 16px;
        border-radius: 24px;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #8b5cf6, #60a5fa);
        color: white;
        font-size: 30px;
        box-shadow: 0 18px 35px rgba(124, 58, 237, 0.28);
    }

    .diagnostic-result-card h2 {
        margin: 0 0 8px;
        color: #172554;
        font-size: 1.45rem;
        font-weight: 900;
    }

    .diagnostic-result-subtitle {
        margin: 0 auto 22px;
        max-width: 390px;
        color: #64748b;
        font-weight: 700;
        line-height: 1.8;
    }

    .diagnostic-result-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .diagnostic-result-item {
        padding: 14px 10px;
        border-radius: 18px;
        background: rgba(124, 58, 237, 0.08);
        border: 1px solid rgba(124, 58, 237, 0.12);
    }

    .diagnostic-result-item span {
        display: block;
        color: #172554;
        font-size: 1.35rem;
        font-weight: 900;
        margin-bottom: 4px;
    }

    .diagnostic-result-item small {
        color: #64748b;
        font-weight: 800;
        font-size: 0.78rem;
    }

    .diagnostic-result-item.success {
        background: rgba(16, 185, 129, 0.1);
    }

    .diagnostic-result-item.warning {
        background: rgba(245, 158, 11, 0.12);
    }

    .diagnostic-result-item.danger {
        background: rgba(239, 68, 68, 0.1);
    }

    .diagnostic-result-item.unknown {
        background: rgba(100, 116, 139, 0.1);
    }

    .diagnostic-result-item.level {
        background: rgba(96, 165, 250, 0.12);
    }

    .diagnostic-result-percentage {
        margin: 18px 0;
        padding: 16px;
        border-radius: 20px;
        background: rgba(248, 250, 252, 0.8);
    }

    .diagnostic-result-percent-text {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        font-weight: 900;
        color: #334155;
    }

    .diagnostic-result-percent-text strong {
        color: #7c3aed;
        font-size: 1.1rem;
    }

    .diagnostic-result-bar {
        height: 10px;
        border-radius: 999px;
        background: #e5e7eb;
        overflow: hidden;
    }

    .diagnostic-result-bar span {
        display: block;
        width: 0%;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #8b5cf6, #60a5fa);
        transition: width 0.5s ease;
    }

    .diagnostic-generation-note {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 800;
        margin: 16px 0 20px;
    }

    .mini-loader {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid rgba(124, 58, 237, 0.18);
        border-top-color: #7c3aed;
        animation: miniSpin 0.8s linear infinite;
    }

    @keyframes miniSpin {
        to {
            transform: rotate(360deg);
        }
    }

    .diagnostic-result-btn {
        width: 100%;
        border: none;
        border-radius: 18px;
        padding: 14px 18px;
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        color: white;
        font-family: inherit;
        font-size: 1rem;
        font-weight: 900;
        cursor: pointer;
        box-shadow: 0 18px 35px rgba(124, 58, 237, 0.28);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
    }

    @media (max-width: 520px) {
        .diagnostic-result-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

  </style>
  
</head>

<body>
  <main class="page">
    <div class="blob one"></div>
    <div class="blob two"></div>
    <div class="blob three"></div>
    <div class="blob four"></div>

    <div class="dash-line top"></div>
    <div class="dash-line left"></div>
    <div class="dash-line bottom"></div>
    <div class="dash-line right"></div>

    <div class="floating-icon brain">🧠</div>
    <div class="floating-icon target">🎯</div>
    <div class="floating-icon book">📖</div>
    <div class="floating-icon flask">⚗️</div>
    <div class="floating-icon bag">💼</div>

    <div class="star s1">⭐</div>
    <div class="star s2">✨</div>
    <div class="star s3">✦</div>
    <div class="star s4">✧</div>

    <section class="layout">
      <aside class="side-card">
        <div class="robot" aria-hidden="true">
          <div class="head">
            <div class="antenna"></div>
            <div class="face"></div>
          </div>
          <div class="ear left"></div>
          <div class="ear right"></div>
          <div class="body"></div>
          <div class="arm left"></div>
          <div class="arm right"></div>
        </div>

        <div class="message">
          لا تقلقي، هذا الاختبار ليس للدرجات، فقط لفهم مستواك وبناء خطة مناسبة لك.
          <span class="heart">♥</span>
        </div>

        <div class="info-list">
          <div class="info-title">
            معلومات الاختبار
            <span><i class="fa-regular fa-clipboard"></i></span>
          </div>

          <div class="info-item">
            <div class="info-icon purple">
              <i class="fa-solid fa-briefcase"></i>
            </div>
            <div>
              <h4>المسار المختار</h4>
              <p>{{ $careerPath->name ?? 'لم يتم تحديد المسار بعد' }}</p>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon green">
              <i class="fa-solid fa-comment-dots"></i>
            </div>
            <div>
              <h4>عدد الأسئلة</h4>
              <p>{{ count($questions ?? []) }} أسئلة</p>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon orange">
              <i class="fa-regular fa-clock"></i>
            </div>
            <div>
              <h4>الوقت المتوقع</h4>
              <p>5 دقائق</p>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon blue">
              <i class="fa-solid fa-bullseye"></i>
            </div>
            <div>
              <h4>الهدف</h4>
              <p>تحديد مستواك ونقاط القوة والضعف لبناء خطة مخصصة لك</p>
            </div>
          </div>
        </div>
      </aside>

      <section class="main-card">
        <h1 class="title">اختبار <span>تشخيص</span> المستوى</h1>

        <div class="progress-wrap">
          <div class="progress-top">
            <div>
              السؤال
              <span id="currentQuestion">1</span>
              من
              <span id="totalQuestions">{{ count($questions ?? []) }}</span>
            </div>

            <div class="percent" id="percentText">0%</div>
          </div>

          <div class="bar">
            <span id="progressBar" style="width: 0%;"></span>
          </div>
        </div>

        <div class="question-card">
          <div class="question-meta">
            <div class="number" id="questionNumber">1</div>
            <div class="badge" id="questionTypeBadge">اختيار من متعدد ☷</div>
          </div>

          <div class="question">
            <h2 id="questionText">جاري تجهيز السؤال...</h2>

            <div class="bulb">
              <i class="fa-solid fa-lightbulb"></i>
            </div>
          </div>

          <div class="answers" id="answers"></div>

          <div class="actions">
            <button class="btn danger" id="finishBtn" type="button">إنهاء الاختبار 🚩</button>
            <button class="btn primary" id="nextBtn" type="button">التالي <span class="arrow">←</span></button>
            <button class="btn" id="prevBtn" type="button"><span class="arrow">→</span> السابق</button>
          </div>
        </div>
      </section>
    </section>

    <div class="bottom-pills">
      <div class="pill plan">
        <span><i class="fa-solid fa-wand-magic-sparkles"></i></span>
        خطة مخصصة
      </div>

      <div class="pill analytics">
        <span><i class="fa-solid fa-chart-column"></i></span>
        تقييم شخصي
      </div>

      <div class="pill ai">
        <span><i class="fa-solid fa-brain"></i></span>
        ذكاء اصطناعي
      </div>
    </div>
  </main>

  <div id="diagnosticResultModal" class="diagnostic-result-modal" aria-hidden="true">
    <div class="diagnostic-result-card">
        <div class="diagnostic-result-icon">
            <i class="fa-solid fa-chart-simple"></i>
        </div>

        <h2>تم حفظ إجاباتك بنجاح</h2>

        <p class="diagnostic-result-subtitle">
            هذه نتيجة أولية، وسنجهّز لك التحليل والخطة الذكية الآن.
        </p>

        <div class="diagnostic-result-grid">
            <div class="diagnostic-result-item">
                <span id="resultTotal">0</span>
                <small>عدد الأسئلة</small>
            </div>

            <div class="diagnostic-result-item success">
                <span id="resultCorrect">0</span>
                <small>إجابات صحيحة</small>
            </div>

            <div class="diagnostic-result-item warning">
                <span id="resultPartial">0</span>
                <small>إجابات جزئية</small>
            </div>

            <div class="diagnostic-result-item danger">
                <span id="resultWrong">0</span>
                <small>إجابات خاطئة</small>
            </div>

            <div class="diagnostic-result-item unknown">
                <span id="resultUnknown">0</span>
                <small>لا أعرف</small>
            </div>

            <div class="diagnostic-result-item level">
                <span id="resultLevel">-</span>
                <small>المستوى</small>
            </div>
        </div>

        <div class="diagnostic-result-percentage">
            <div class="diagnostic-result-percent-text">
                <span>النسبة</span>
                <strong id="resultPercentage">0%</strong>
            </div>

            <div class="diagnostic-result-bar">
                <span id="resultPercentageBar"></span>
            </div>
        </div>

        <div class="diagnostic-generation-note">
            <span class="mini-loader"></span>
            نجهّز التحليل والخطة التفصيلية بالخلفية...
        </div>

        <button type="button" id="goDashboardBtn" class="diagnostic-result-btn">
            تم، انتقلي للداشبورد
            <i class="fa-solid fa-arrow-left"></i>
        </button>
    </div>
  </div>

<script>
    const rawQuestions = @json($questions ?? []);

    const badAnswerTexts = [
        "استخدام عنصر",
        "عنصر",
        "استخدام",
        "خيار",
        "إجابة",
        "طريقة صحيحة",
        "طريقة خاطئة",
        "صحيح",
        "خطأ",
        "غير صحيح",
        "اختيار صحيح",
        "اختيار خاطئ"
    ];

    const allowedTypes = [
        "اختيار من متعدد",
        "سيناريو عملي",
        "تقييم ذاتي"
    ];

    const allowedDifficulties = [
        "مبتدئ",
        "متوسط",
        "متقدم"
    ];

    function normalizeText(value) {
        return String(value ?? "").trim();
    }

    function normalizeKey(value) {
        return normalizeText(value)
            .toLowerCase()
            .replace(/\s+/g, " ");
    }

    function isBadAnswerText(text) {
        const cleanText = normalizeText(text);

        if (cleanText.length < 4) {
            return true;
        }

        return badAnswerTexts.includes(cleanText);
    }

    function sanitizeIcon(icon) {
        const cleanIcon = normalizeText(icon);

        if (!cleanIcon || cleanIcon.length > 80) {
            return "fa-solid fa-circle";
        }

        return cleanIcon;
    }

    function normalizeAnswers(rawAnswers) {
        if (!Array.isArray(rawAnswers)) {
            return [];
        }

        const cleanAnswers = [];
        const seenAnswers = new Set();

        rawAnswers.forEach((answer) => {
            let answerText = "";
            let score = 0;
            let icon = "fa-solid fa-circle";

            if (answer && typeof answer === "object") {
                answerText = normalizeText(answer.text);
                score = Number(answer.score ?? 0);
                icon = sanitizeIcon(answer.icon);
            } else {
                answerText = normalizeText(answer);
            }

            if (isBadAnswerText(answerText)) {
                return;
            }

            const answerKey = normalizeKey(answerText);

            if (seenAnswers.has(answerKey)) {
                return;
            }

            seenAnswers.add(answerKey);

            if (!Number.isFinite(score) || score < 0 || score > 3) {
                score = 0;
            }

            cleanAnswers.push({
                text: answerText,
                score: score,
                icon: icon
            });
        });

        const unknownIndex = cleanAnswers.findIndex((answer) => {
            return answer.text === "لا أعرف الإجابة";
        });

        let unknownAnswer = {
            text: "لا أعرف الإجابة",
            score: 0,
            icon: "fa-solid fa-circle-question"
        };

        if (unknownIndex !== -1) {
            unknownAnswer = cleanAnswers[unknownIndex];
            cleanAnswers.splice(unknownIndex, 1);
        }

        const firstFourAnswers = cleanAnswers
            .filter((answer) => answer.text !== "لا أعرف الإجابة")
            .slice(0, 4);

        if (firstFourAnswers.length !== 4) {
            return [];
        }

        return [
            ...firstFourAnswers,
            {
                text: "لا أعرف الإجابة",
                score: 0,
                icon: "fa-solid fa-circle-question"
            }
        ];
    }

    function normalizeQuestions(rawQuestions) {
        if (!Array.isArray(rawQuestions)) {
            return [];
        }

        const cleanQuestions = [];
        const seenQuestions = new Set();

        rawQuestions.forEach((q) => {
            if (!q || typeof q !== "object") {
                return;
            }

            const questionText = normalizeText(q.text);

            if (!questionText || questionText.length < 15) {
                return;
            }

            const questionKey = normalizeKey(questionText);

            if (seenQuestions.has(questionKey)) {
                return;
            }

            const answers = normalizeAnswers(q.answers);

            if (answers.length !== 5) {
                console.warn("Question skipped because answers are invalid:", q);
                return;
            }

            seenQuestions.add(questionKey);

            let type = normalizeText(q.type) || "اختيار من متعدد";
            let difficulty = normalizeText(q.difficulty) || "مبتدئ";

            if (!allowedTypes.includes(type)) {
                type = "اختيار من متعدد";
            }

            if (!allowedDifficulties.includes(difficulty)) {
                difficulty = "مبتدئ";
            }

            cleanQuestions.push({
                text: questionText,
                type: type,
                difficulty: difficulty,
                answers: answers
            });
        });

        return cleanQuestions.slice(0, 15);
    }

    const questions = normalizeQuestions(rawQuestions);

    let current = 0;
    let isSubmitting = false;

    const selected = {};
    const total = questions.length;

    const answersBox = document.getElementById("answers");
    const questionText = document.getElementById("questionText");
    const currentQuestion = document.getElementById("currentQuestion");
    const totalQuestions = document.getElementById("totalQuestions");
    const questionNumber = document.getElementById("questionNumber");
    const percentText = document.getElementById("percentText");
    const progressBar = document.getElementById("progressBar");
    const questionTypeBadge = document.getElementById("questionTypeBadge");
    const nextBtn = document.getElementById("nextBtn");
    const prevBtn = document.getElementById("prevBtn");
    const finishBtn = document.getElementById("finishBtn");

    if (totalQuestions) {
        totalQuestions.textContent = total;
    }

    function setText(element, value) {
        if (element) {
            element.textContent = value;
        }
    }

    function setProgress(element, value) {
        if (element) {
            element.style.width = value;
        }
    }

    function createAnswerOption(answer, index) {
        const option = document.createElement("div");
        option.className = "answer" + (selected[current] === index ? " selected" : "");

        const letter = document.createElement("div");
        letter.className = "letter";
        letter.textContent = String.fromCharCode(65 + index);

        const answerText = document.createElement("p");
        answerText.textContent = answer.text;

        const answerIcon = document.createElement("div");
        answerIcon.className = "answer-icon";

        const icon = document.createElement("i");
        icon.className = answer.icon || "fa-solid fa-circle";

        answerIcon.appendChild(icon);

        const check = document.createElement("div");
        check.className = "check";
        check.textContent = "✓";

        option.appendChild(letter);
        option.appendChild(answerText);
        option.appendChild(answerIcon);
        option.appendChild(check);

        option.addEventListener("click", () => {
            selected[current] = index;
            renderQuestion();
        });

        return option;
    }

    function renderQuestion() {
        if (!questions.length) {
            setText(questionText, "لا توجد أسئلة صالحة حاليًا. يرجى الرجوع وتجربة توليد الاختبار مرة أخرى.");
            setText(percentText, "0%");
            setProgress(progressBar, "0%");

            if (answersBox) {
                answersBox.innerHTML = "";
            }

            if (nextBtn) {
                nextBtn.disabled = true;
            }

            if (prevBtn) {
                prevBtn.disabled = true;
            }

            if (finishBtn) {
                finishBtn.disabled = true;
            }

            return;
        }

        const q = questions[current];
        const displayNumber = current + 1;
        const percent = Math.round((displayNumber / total) * 100);

        setText(questionText, q.text);
        setText(currentQuestion, displayNumber);
        setText(questionNumber, displayNumber);
        setText(percentText, percent + "%");
        setProgress(progressBar, percent + "%");

        if (questionTypeBadge) {
            questionTypeBadge.textContent = q.type + " ☷";
        }

        if (answersBox) {
            answersBox.innerHTML = "";

            q.answers.forEach((answer, index) => {
                answersBox.appendChild(createAnswerOption(answer, index));
            });
        }

        if (prevBtn) {
            prevBtn.disabled = current === 0;
        }

        if (nextBtn) {
            if (current === total - 1) {
                nextBtn.textContent = "السؤال الأخير";
            } else {
                nextBtn.innerHTML = 'التالي <span class="arrow">←</span>';
            }
        }
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", () => {
            if (!questions.length) {
                return;
            }

            if (selected[current] === undefined) {
                alert("اختاري إجابة قبل الانتقال للسؤال التالي.");
                return;
            }

            current = Math.min(current + 1, total - 1);
            renderQuestion();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", () => {
            current = Math.max(current - 1, 0);
            renderQuestion();
        });
    }

    if (finishBtn) {
        finishBtn.addEventListener("click", () => {
            if (isSubmitting) {
                return;
            }

            if (!questions.length) {
                alert("لا توجد أسئلة لإرسالها.");
                return;
            }

            if (Object.keys(selected).length < total) {
                alert("يرجى الإجابة على جميع الأسئلة قبل إنهاء الاختبار.");
                return;
            }

            const detailedAnswers = questions.map((question, qIndex) => {
                const answerIndex = selected[qIndex];
                const selectedAnswer = question.answers[answerIndex];

                return {
                    question_index: qIndex,
                    answer_index: answerIndex,
                    question: question.text,
                    question_type: question.type,
                    difficulty: question.difficulty,
                    answer: selectedAnswer,
                    score: selectedAnswer.score ?? 0
                };
            });

            isSubmitting = true;
            finishBtn.disabled = true;
            finishBtn.textContent = "جاري حفظ الإجابات...";

            if (window.showOfuqLoader) {
                window.showOfuqLoader(
                    "نحفظ إجاباتك الآن",
                    "ثواني قليلة ونعرض لك نتيجتك الأولية."
                );
            }

            fetch("{{ route('diagnosticAssessment.submit') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    answers: selected,
                    detailed_answers: detailedAnswers
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error("فشل حفظ الإجابات.");
                }

                return response.json();
            })
            .then(data => {
                if (window.hideOfuqLoader) {
                    window.hideOfuqLoader();
                }

                if (!data.success) {
                    alert(data.message || "حدث خطأ أثناء حفظ الإجابات.");
                    isSubmitting = false;
                    finishBtn.disabled = false;
                    finishBtn.innerHTML = "إنهاء الاختبار 🚩";
                    return;
                }

                showDiagnosticResult(data.result, data.redirect);
                startRoadmapGenerationInBackground();
            })
            .catch((error) => {
                if (window.hideOfuqLoader) {
                    window.hideOfuqLoader();
                }

                console.error(error);
                alert("حدث خطأ أثناء حفظ الإجابات. جرّبي مرة أخرى.");

                isSubmitting = false;
                finishBtn.disabled = false;
                finishBtn.innerHTML = "إنهاء الاختبار 🚩";
            });
        });
    }

    function showDiagnosticResult(result, redirectUrl) {
        const modal = document.getElementById("diagnosticResultModal");

        if (!modal) {
            window.location.href = redirectUrl || "{{ route('dashboard') }}";
            return;
        }

        setText(document.getElementById("resultTotal"), result?.total_questions ?? 0);
        setText(document.getElementById("resultCorrect"), result?.correct_answers ?? 0);
        setText(document.getElementById("resultPartial"), result?.partial_answers ?? 0);
        setText(document.getElementById("resultWrong"), result?.wrong_answers ?? 0);
        setText(document.getElementById("resultUnknown"), result?.unknown_answers ?? 0);
        setText(document.getElementById("resultLevel"), result?.level ?? "-");

        const percentage = result?.percentage ?? 0;

        setText(document.getElementById("resultPercentage"), percentage + "%");
        setProgress(document.getElementById("resultPercentageBar"), percentage + "%");

        modal.classList.add("active");
        modal.setAttribute("aria-hidden", "false");

        const goDashboardBtn = document.getElementById("goDashboardBtn");

        if (goDashboardBtn) {
            goDashboardBtn.onclick = function () {
                window.location.href = redirectUrl || "{{ route('dashboard') }}";
            };
        }
    }

    function startRoadmapGenerationInBackground() {
        fetch("{{ route('roadmap.generate.after.diagnostic') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            console.log("Roadmap generation:", data);
        })
        .catch(error => {
            console.error("Roadmap generation failed:", error);
        });
    }

    renderQuestion();
</script>

@include('layouts.loader')
</body>
</html>