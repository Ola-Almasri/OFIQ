<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ofuq AI | تحليل البيانات</title>
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

</head>

<body class="home-page">
  <!-- Background layers -->
  <div class="bg-pattern" aria-hidden="true"></div>
  <div class="bg-glow" aria-hidden="true">
    <div class="glow glow--top"></div>
    <div class="glow glow--bottom"></div>
  </div>

  <!-- NAVBAR -->
    <nav class="nav glass">
    <div class="container nav__inner">
        <div class="nav__brand">
        <img class="nav__logo" src="{{ asset('assets/img/logo.png') }}" alt="Ofuq Logo" onerror="this.style.display='none'" />
        <div class="nav__brandText">
            <span class="nav__title brand-en">Ofuq</span>
            <span class="nav__sub">AI Student Advisor</span>
        </div>
        </div>

        <div class="nav__links">
        <a href="#hero" class="nav__link">الرئيسية</a>
        <a href="#stats" class="nav__link">الإحصائيات</a>
        <a href="#about" class="nav__link">عن أفق</a>
        <a href="#services" class="nav__link">الخدمات</a>
        </div>

        <div class="nav__actions">
        <!-- ✅ زر الوضع الليلي -->
        <button 
            class="dark-mode-toggle" 
            id="darkModeToggle"
            aria-label="تبديل الوضع الليلي"
            style="background: none; border: none; cursor: pointer; padding: 8px; border-radius: 50%; transition: 0.3s ease; display: flex; align-items: center; justify-content: center;"
        >
            <span class="material-icons-round" id="darkModeIcon" style="font-size: 28px; color: #64748b;">dark_mode</span>
        </button>

        <button class="btn btn--ghost" type="button" onclick="window.location.href='{{ url('/login') }}'">تسجيل الدخول</button>
        <button class="btn btn--glow" type="button" onclick="window.location.href='{{ route('assessment.start') }}'">
            ابدأ الآن
        </button>
        <button class="burger" id="burgerBtn" aria-label="القائمة">
            <span></span><span></span><span></span>
        </button>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="container">
        <div class="mobilemenu" id="mobileMenu">
        <a href="#hero" class="m-link">الرئيسية</a>
        <a href="#stats" class="m-link">الإحصائيات</a>
        <a href="#about" class="m-link">عن أفق</a>
        <a href="#services" class="m-link">الخدمات</a>
        <button class="btn btn--glow w-100" type="button" onclick="window.location.href='{{ route('assessment.start') }}'">
            ابدأ الآن
        </button>
        </div>
    </div>
    </nav>
    