<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'أفق')</title>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0,1" />
    
    <!-- Stack for additional styles -->
    @stack('styles')
</head>
<body class="@yield('body_class')">
    
    {{-- ============================================================ --}}
    {{-- اختيار الهيدر المناسب - منطق محسّن وأكثر أماناً --}}
    {{-- ============================================================ --}}
    @php
        // متغير لتحديد نوع الهيدر
        $headerType = 'main'; // القيمة الافتراضية
        
        // 1. التحقق من وجود Section 'header_type'
        if (View::hasSection('header_type')) {
            $headerTypeValue = trim($__env->yieldContent('header_type'));
            if (in_array($headerTypeValue, ['simple', 'auth', 'main'])) {
                $headerType = $headerTypeValue;
            }
        }
        
        // 2. إذا كان المستخدم مسجل دخول ولم يتم تحديد نوع آخر
        if (auth()->check() && $headerType === 'main') {
            $headerType = 'auth';
        }
    @endphp

    {{-- عرض الهيدر حسب النوع المحدد --}}
    @if($headerType === 'simple')
        @include('layouts.header-simple')
    @elseif($headerType === 'auth')
        @include('layouts.header-auth')
    @else
        @include('layouts.header-main')
    @endif

    {{-- المحتوى الرئيسي --}}
    <main>
        @yield('content')
    </main>
    
    {{-- الفوتر --}}
    @include('layouts.footer')
    
    {{-- الجافا سكريبت --}}
    <script src="{{ asset('assets/js/main.js') }}"></script>
    
    {{-- لودر --}}
    @include('layouts.loader')
    
    {{-- سكريبتات إضافية --}}
    @stack('scripts')
    @yield('scripts')

</body>
</html>