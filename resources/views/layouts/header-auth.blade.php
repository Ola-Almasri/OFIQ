<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ofuq AI | لوحة التحكم</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">

</head>

<body class="dashboard-page">
  <div class="bg-pattern" aria-hidden="true"></div>
  <div class="bg-glow" aria-hidden="true">
    <div class="glow glow--top"></div>
    <div class="glow glow--bottom"></div>
  </div>

  <!-- Background layers -->
  <div class="bg-pattern" aria-hidden="true"></div>
  <div class="bg-glow" aria-hidden="true">
    <div class="glow glow--top"></div>
    <div class="glow glow--bottom"></div>
  </div>

  <!-- NAVBAR -->
  <nav class="nav glass">
      <div class="container nav__inner">
          <!-- القسم الأيسر: الشعار -->
          <a href="{{ route('home') }}" class="nav__brand" style="text-decoration: none;">
              <img class="nav__logo" src="{{ asset('assets/img/logo.png') }}" alt="Ofuq Logo" onerror="this.style.display='none'" />
              <div class="nav__brandText">
                  <span class="nav__title brand-en">Ofuq</span>
                  <span class="nav__sub">AI Student Advisor</span>
              </div>
          </a>

          <!-- الروابط الرئيسية -->
          <div class="nav__links">
              <a href="{{ route('dashboard') }}" class="nav__link {{ request()->routeIs('dashboard') ? 'active' : '' }}">الرئيسية</a>
              <a href="{{ route('roadmap') }}" class="nav__link {{ request()->routeIs('roadmap') ? 'active' : '' }}">المسار التعليمي</a>
              <a href="{{ route('monthly.roadmap') }}" class="nav__link {{ request()->routeIs('monthly.roadmap') ? 'active' : '' }}">الخطة الشهرية</a>
              <a href="{{ route('focus') }}" class="nav__link {{ request()->routeIs('focus') ? 'active' : '' }}">منطقة التركيز</a>
              <a href="{{ route('profile') }}" class="nav__link {{ request()->routeIs('profile') ? 'active' : '' }}">الملف الشخصي</a>
          </div>

          <!-- الأزرار -->
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

              <!-- ✅ زر تسجيل الخروج -->
              <form method="POST" action="{{ route('logout') }}" style="display: inline; margin: 0;">
                  @csrf
                  <button type="submit" class="btn btn--glow" style="padding: 8px 16px; font-size: 14px;">تسجيل خروج</button>
              </form>

              <!-- ✅ زر القائمة (للجوال) -->
              <button class="burger" id="burgerBtn" aria-label="القائمة">
                  <span></span><span></span><span></span>
              </button>
          </div>
      </div>

      <!-- القائمة المتنقلة (للجوال) -->
      <div class="container">
          <div class="mobilemenu" id="mobileMenu">
              <a href="{{ route('dashboard') }}" class="nav__link">الرئيسية</a>
              <a href="{{ route('roadmap') }}" class="nav__link">المسار التعليمي</a>
              <a href="{{ route('monthly.roadmap') }}" class="nav__link">الخطة الشهرية</a>
              <a href="{{ route('focus') }}" class="nav__link">منطقة التركيز</a>
              <a href="{{ route('profile') }}" class="nav__link">الملف الشخصي</a>
              
              <form method="POST" action="{{ route('logout') }}" style="display: inline; margin: 0;">
                  @csrf
                  <button type="submit" class="btn btn--glow w-100" style="margin-top: 10px;">تسجيل خروج</button>
              </form>
          </div>
      </div>
  </nav>

  <!-- الأشكال الهندسية في الخلفية -->
  <div class="dashboard-bg-shapes">
    <!-- مثلثات (10 مثلثات) -->
    <div class="triangle triangle-1"></div>
    <div class="triangle triangle-2"></div>
    <div class="triangle triangle-3"></div>
    <div class="triangle triangle-4"></div>
    <div class="triangle triangle-5"></div>
    <div class="triangle triangle-6"></div>
    <div class="triangle triangle-7"></div>
    <div class="triangle triangle-8"></div>
    <div class="triangle triangle-9"></div>
    <div class="triangle triangle-10"></div>
        
    <!-- سداسيات (3) -->
    <div class="hexagon hexagon-1"></div>
    <div class="hexagon hexagon-2"></div>
    <div class="hexagon hexagon-3"></div>
        
    <!-- متوازيات أضلاع (2) -->
    <div class="parallelogram parallelogram-1"></div>
    <div class="parallelogram parallelogram-2"></div>
    
    <!-- مستطيلات (3) -->
    <div class="rectangle rectangle-1"></div>
    <div class="rectangle rectangle-2"></div>
    <div class="rectangle rectangle-3"></div>
    
    <!-- دوائر عملاقة (2) -->
    <div class="giant-circle giant-circle-1"></div>
    <div class="giant-circle giant-circle-2"></div>
    
    <!-- خطوط متقاطعة (3) -->
    <div class="cross-line cross-line-1"></div>
    <div class="cross-line cross-line-2"></div>
    <div class="cross-line cross-line-3"></div>
  </div>