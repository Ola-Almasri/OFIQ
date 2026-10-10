@extends('layouts.app')
@section('title', 'تسجيل الدخول')
@section('header_type', 'simple')
@section('content')

<!-- LOGIN CONTENT -->
<div class="login-wrapper">
    <!-- القسم الأيسر -->
    <div class="login-visual">
        <div class="half-circle-bg"></div>
        <div class="half-circle-decoration"></div>
        <div class="absolute top-[-20%] right-[-20%] w-[400px] h-[400px] bg-[#8b5cf6]/20 rounded-full blur-[100px] z-0"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[300px] h-[300px] bg-[#0ea5e9]/20 rounded-full blur-[80px] z-0"></div>
        <div class="glow-circle"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[250px] h-[250px] bg-[#f59e0b]/15 rounded-full blur-[50px] z-0"></div>
        
        <div class="login-visual-content">
            <img src="assets/img/login1.png" alt="Login Visual" />
        </div>
    </div>

    <!-- القسم الأيمن: الفورم -->
    <div class="login-form-section">
        <div class="login-form-container">
            <h1 class="login-title">تسجيل الدخول</h1>
            <p class="login-subtitle">مرحباً بك مجدداً في أفق</p>
            
            <div class="login-form-card">
                {{-- ✅ عرض الأخطاء إن وجدت --}}
                @if($errors->any())
                    <div class="error-messages" style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                        @foreach($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </div>
                @endif

                {{-- ✅ الفورم لازم يرسل لـ route('login') --}}
                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    
                    <div class="form-group">
                        <label class="form-label">البريد الإلكتروني</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">mail</span>
                            <input type="email" name="email" class="form-input" placeholder="example@ofuq.com" value="{{ old('email') }}" required autofocus />
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">كلمة المرور</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">lock</span>
                            <input type="password" name="password" class="form-input" placeholder="••••••••" required />
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" /> تذكرني
                        </label>
                        <a href="{{ route('password.request') }}" class="forgot-link">نسيت كلمة المرور؟</a>
                    </div>
                    
                    <button type="submit" class="login-submit-btn">
                        <span>دخول إلى حسابي</span>
                        <span class="material-symbols-outlined">arrow_back</span>
                    </button>
                </form>
                            
                <div class="social-buttons">
                    <a href="#" class="social-btn"><i class="fab fa-google"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-twitter"></i></a>
                </div>
                
                <div class="signup-link">
                    ليس لديك حساب؟ <a href="{{ route('register') }}">إنشاء حساب جديد</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection