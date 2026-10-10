@extends('layouts.app')

@section('title', 'اختر مسارك المهني')

@section('content')
<div class="career-selection-page">
    <div class="career-layout">

        <div class="career-right animate-right">
            @php
                $firstCareerPath = $careerPaths->sortBy('path_order')->first();
            @endphp

            <div class="career-grid">
                @forelse($careerPaths as $index => $career)
                    <div class="career-card animate-card" style="animation-delay: {{ $index * 0.1 }}s">
                        <div class="career-card-content">
                            <div class="career-icon">
                                <span class="material-icons-round">rocket_launch</span>
                            </div>

                            <h3 class="career-name">{{ $career->name }}</h3>

                            <p class="career-description">
                                {{ $career->description }}
                            </p>

                            <div class="career-match">
                                <span class="material-icons-round">track_changes</span>
                                نسبة التوافق: {{ $career->match_percentage ?? 0 }}%
                            </div>

                            <div class="career-status">
                                @if($career->id === optional($firstCareerPath)->id)
                                    <span class="status-badge active">المسار الذي ستبدأين به</span>
                                @else
                                    <span class="status-badge locked">مسار لاحق</span>
                                @endif
                            </div>

                            <div class="career-skills">
                                @php
                                    $requiredSkills = $career->required_skills ?? [];

                                    if (is_string($requiredSkills)) {
                                        $decodedSkills = json_decode($requiredSkills, true);
                                        $requiredSkills = is_array($decodedSkills) ? $decodedSkills : [];
                                    }
                                @endphp

                                @forelse($requiredSkills as $skill)
                                    <span class="skill-tag">
                                        <span class="material-icons-round">check</span>
                                        {{ is_array($skill) ? ($skill['name'] ?? '') : $skill }}
                                    </span>
                                @empty
                                    <span class="skill-tag">
                                        <span class="material-icons-round">info</span>
                                        سيتم تحديد المهارات حسب الخطة
                                    </span>
                                @endforelse

                            </div>
                        </div>
                    </div>
                @empty
                    <div class="no-careers">
                        <p>لا توجد مسارات متاحة حالياً. يرجى المحاولة مرة أخرى.</p>
                    </div>
                @endforelse
            </div>

            @if($firstCareerPath)
                <div class="start-dashboard-wrap animate-btn">
                    <form
                        id="startCareerForm"
                        action="{{ route('career.store') }}"
                        method="POST"
                        style="margin: 0;"
                        data-ofuq-loading
                        data-loading-title="نجهّز اختبارك التشخيصي"
                        data-loading-text="نربط مسارك الأول ونحضّر أسئلة تناسب مستواك وبياناتك."
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="career_path_id"
                            value="{{ $firstCareerPath->id }}"
                        >

                        <button
                            type="submit"
                            class="btn-start-dashboard"
                            id="startCareerBtn"
                        >
                            لنبدأ
                            <span class="material-icons-round">arrow_back</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="career-left animate-left">
            <div class="career-left-image">
                <img
                    src="{{ asset('assets/img/7.jpg') }}"
                    alt="Career Guidance"
                    class="career-guide-image"
                    onerror="this.src='https://placehold.co/500x400/6366f1/white?text=Career+Path'"
                >
            </div>

            <div class="career-left-content">
                <h2>نحو مستقبلك المهني</h2>

                <div class="career-features">
                    <div class="feature-item">
                        <span class="feature-icon">🎓</span>
                        <span>مسار دراسي احترافي</span>
                    </div>

                    <div class="feature-item">
                        <span class="feature-icon">📋</span>
                        <span>خطة تعلم عملية قابلة للتطبيق</span>
                    </div>

                    <div class="feature-item">
                        <span class="feature-icon">✨</span>
                        <span>اختبار تشخيصي ثم خطة مخصصة</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
        /* ============================================
        CAREER SELECTION PAGE STYLES
        ============================================ */
        .career-form {
            margin: 0;
        }
        .btn-start-dashboard {
            border: none;
            cursor: pointer;
        }
        .career-form .career-card {
            width: 100%;
            border: none;
            text-align: inherit;
            font-family: inherit;
        }

        .career-select-hint {
            margin-top: 16px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .career-selection-page {
            min-height: calc(100vh - 80px);
            padding: 40px 0 70px;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #2e1065 100%);
            margin-top: 75px;
        }

        /* layout */
        .career-layout {
            display: flex;
            flex-direction: row-reverse;
            gap: 40px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            align-items: flex-start;
        }

        .career-right {
            flex: 3;  /* زودي الرقم عشان اليمين ياخذ مساحة أكبر */
            min-width: 0;
        }

        .career-left {
            flex: 1;  /* اليسار ياخذ مساحة أقل */
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 28px 24px;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: sticky;
            top: 100px;
            transition: all 0.3s ease;
        }

        .career-left:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.25);
        }


        /* صورة القسم الأيسر */
        .career-left-image {
            margin-top: 0px;
            text-align: center;
            margin-bottom: 10px;
        }

        .career-guide-image {
            max-width: 85%;
            height: auto;
            border-radius: 20px;
            transition: transform 0.3s ease;
        }

        .career-guide-image:hover {
            transform: scale(1.02);
        }

        /* محتوى القسم الأيسر */
        .career-left-content {
            text-align: center;
        }

        .career-left-content h2 {
            font-size: 1.5rem;
            margin-bottom: 12px;
            font-weight: 700;
            color: #ffffff;
        }

        .career-left-desc {
            font-size: 0.9rem;
            line-height: 1.6;
            opacity: 0.85;
            margin-bottom: 24px;
            color: #e2e8f0;
        }

        /* تنسيق العناصر (features) */
        .career-features {
            display: flex;
            flex-direction: column;
            gap: 14px;
            text-align: right;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.08);
            padding: 10px 16px;
            border-radius: 50px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .feature-item:hover {
            background: rgba(99, 102, 241, 0.3);
            transform: translateX(-5px);
        }

        .feature-icon {
            font-size: 1.2rem;
            width: 28px;
            text-align: center;
            margin top: 5px;
        }

        /* header */
        .career-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .career-title {
            font-size: 2.7rem;
            font-weight: 800;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #ffffff, #a5b4fc);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .career-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            color: #cbd5e1;
        }

        /* grid cards */
        .career-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            max-width: 1220px;
            margin: 0 auto;
        }

        .career-card {
            border-radius: 28px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            cursor: pointer;
            position: relative;
            opacity: 0;
            transform: translateY(30px);
            animation: cardFloatIn 0.6s ease forwards;
        }

        .career-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top, rgba(255, 255, 255, 0.1), transparent 55%);
            pointer-events: none;
        }

        .career-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: 0 25px 45px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.12);
        }

        .career-card-content {
            padding: 30px 22px;
            text-align: center;
            color: white;
            position: relative;
            z-index: 1;
        }

        .career-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 22px;
            border-radius: 28px;
            background: rgba(255, 255, 255, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.3s;
        }

        .career-card:hover .career-icon {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.2);
        }

        .career-icon .material-icons-round {
            font-size: 46px;
            color: white;
        }

        .career-name {
            font-size: 1.55rem;
            font-weight: 800;
            margin-bottom: 14px;
            color: #ffffff;
        }

        .career-description {
            font-size: 0.9rem;
            margin-bottom: 22px;
            opacity: 0.85;
            line-height: 1.6;
            min-height: 65px;
            color: #e2e8f0;
        }

        .career-match {
            background: rgba(99, 102, 241, 0.3);
            border-radius: 50px;
            padding: 8px 16px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 16px;
            color: #c7d2fe;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 18px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 700;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(4px);
        }

        .status-badge.active {
            background: rgba(34, 197, 94, 0.35);
            color: #86efac;
        }

        .status-badge.locked {
            background: rgba(255, 255, 255, 0.15);
            color: #94a3b8;
        }

        .status-badge.completed {
            background: rgba(59, 130, 246, 0.35);
            color: #93c5fd;
        }

        .career-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
            margin-top: 12px;
        }

        .skill-tag {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            padding: 5px 12px;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #cbd5e1;
        }

        .skill-tag .material-icons-round {
            font-size: 14px;
            color: #818cf8;
        }

        /* button */
        .start-dashboard-wrap {
            text-align: center;
            margin-top: 10px;
        }

        .btn-start-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 16px 56px;
            border-radius: 60px;
            font-size: 1.15rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.3);
            transition: 0.3s ease;
            letter-spacing: 0.5px;
        }

        .btn-start-dashboard:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, #818cf8, #a78bfa);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.4);
            color: white;
        }

        /* animations */
        @keyframes cardFloatIn {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideRight {
            0% {
                opacity: 0;
                transform: translateX(-35px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideLeft {
            0% {
                opacity: 0;
                transform: translateX(35px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeUp {
            0% {
                opacity: 0;
                transform: translateY(25px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-right {
            animation: slideRight 0.7s ease forwards;
        }

        .animate-left {
            animation: slideLeft 0.7s ease forwards;
        }

        .animate-header {
            animation: fadeUp 0.5s ease forwards;
        }

        .animate-card {
            opacity: 0;
            animation: cardFloatIn 0.6s ease forwards;
        }

        .animate-btn {
            animation: fadeUp 0.7s ease forwards 0.3s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        .no-careers {
            text-align: center;
            color: white;
            font-size: 1.2rem;
            padding: 50px;
        }

        /* responsive */
        @media (max-width: 992px) {
            .career-layout {
                flex-direction: column;
            }
            .career-left {
                position: static;
                width: 100%;
                margin-bottom: 30px;
            }
            .career-title {
                font-size: 2rem;
            }
            .career-subtitle {
                font-size: 1rem;
            }
            .career-grid {
                grid-template-columns: 1fr;
            }
            .btn-start-dashboard {
                width: calc(100% - 40px);
                justify-content: center;
            }
            .career-features {
                align-items: center;
            }
            .feature-item {
                width: 100%;
                justify-content: center;
            }
        }
        .career-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); /* قللي من 320px إلى 280px */
            gap: 25px; /* قللي المسافة بين البطاقات */
            max-width: 1000px; /* حددي عرض أقصى للحاوية */
            margin: 0 auto;
        }

</style>

@endsection