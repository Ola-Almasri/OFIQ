@extends('layouts.app')

@section('title', 'ابدأ رحلتك مع أفق')
@section('header_type', 'simple')

@section('content')

@php
    $popularMajors = [
        [
            'title' => 'علوم الحاسوب',
            'description' => 'مناسب لمن يحب البرمجة، الخوارزميات، بناء الأنظمة، وحل المشكلات التقنية.',
            'icon' => 'computer',
            'tags' => ['برمجة', 'خوارزميات', 'أنظمة']
        ],
        [
            'title' => 'هندسة البرمجيات',
            'description' => 'يركز على تطوير البرامج بشكل منظم، من تحليل المتطلبات حتى الإطلاق والاختبار.',
            'icon' => 'developer_mode',
            'tags' => ['تطوير', 'تحليل', 'اختبار']
        ],
        [
            'title' => 'الذكاء الاصطناعي',
            'description' => 'مناسب لمن يهتم بالنماذج الذكية، تعلم الآلة، تحليل البيانات، والتقنيات المستقبلية.',
            'icon' => 'smart_toy',
            'tags' => ['AI', 'Python', 'نماذج ذكية']
        ],
        [
            'title' => 'علم البيانات',
            'description' => 'يركز على فهم البيانات وتحويلها إلى قرارات، تقارير، وتوقعات تساعد المؤسسات.',
            'icon' => 'query_stats',
            'tags' => ['Data', 'SQL', 'تحليل']
        ],
        [
            'title' => 'الأمن السيبراني',
            'description' => 'يهتم بحماية الأنظمة والشبكات، اكتشاف الثغرات، وفهم طرق الهجمات الرقمية.',
            'icon' => 'security',
            'tags' => ['حماية', 'شبكات', 'ثغرات']
        ],
        [
            'title' => 'نظم المعلومات',
            'description' => 'يجمع بين التقنية والإدارة، ويركز على استخدام الأنظمة لحل مشاكل الأعمال.',
            'icon' => 'hub',
            'tags' => ['إدارة', 'أنظمة', 'أعمال']
        ],
        [
            'title' => 'تصميم تجربة المستخدم UI/UX',
            'description' => 'مناسب لمن يهتم بتصميم واجهات واضحة وجذابة وتحسين تجربة المستخدم.',
            'icon' => 'design_services',
            'tags' => ['تصميم', 'واجهات', 'UX']
        ],
        [
            'title' => 'تطوير تطبيقات الموبايل',
            'description' => 'يركز على بناء تطبيقات Android و iOS وتجارب استخدام عملية للمستخدمين.',
            'icon' => 'phone_iphone',
            'tags' => ['Mobile', 'Apps', 'React Native']
        ],
    ];
@endphp

<section class="ofuq-start-v2">

    <div class="ofuq-start-orb orb-one"></div>
    <div class="ofuq-start-orb orb-two"></div>
    <div class="ofuq-start-orb orb-three"></div>

    <div class="ofuq-start-container">

        {{-- Start Question --}}
        <div class="ofuq-start-hero" id="startCard">

            <div class="ofuq-intro-layout">

                {{-- النص والاختيارات --}}
                <div class="ofuq-intro-content">

                    <h1>خلينا نحدد نقطة البداية</h1>

                    <p>
                        هل عندك تخصص أو مجال واضح بدك تمشي فيه؟
                        ولا لسه بدك تستكشف التخصصات المناسبة قبل إدخال بياناتك؟
                    </p>

                    <div class="ofuq-choice-grid">

                        <a href="{{ route('assessment') }}" class="ofuq-choice-card primary-choice">
                            <div class="choice-icon">
                                <span class="material-icons-round">check_circle</span>
                            </div>

                            <div class="choice-text">
                                <h3>عندي تخصص محدد</h3>
                                <p>
                                    كمّل إدخال بياناتك، وأفق يحلل مهاراتك ويوجهك للمسار الأنسب.
                                </p>
                            </div>

                            <div class="choice-action">
                                المتابعة
                                <span class="material-icons-round">arrow_back</span>
                            </div>
                        </a>

                        <div class="ofuq-choice-card secondary-choice" id="showMajorsBtn" role="button" tabindex="0">
                            <div class="choice-icon">
                                <span class="material-icons-round">travel_explore</span>
                            </div>

                            <div class="choice-text">
                                <h3>لسه بدي أستكشف</h3>
                                <p>
                                    شوف تخصصات رائجة مع وصف مختصر يساعدك تختار الأقرب لك.
                                </p>
                            </div>

                            <div class="choice-action">
                                استكشاف التخصصات
                                <span class="material-icons-round">arrow_back</span>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- صورة مساعد أفق الجانبية --}}
                <div class="ofuq-character-side">
                    <img 
                        src="{{ asset('assets/img/hi.png') }}" 
                        alt="مساعد أفق" 
                        class="ofuq-character-img"
                        loading="lazy"
                    >
                </div>

            </div>

        </div>

        {{-- Majors Explorer --}}
        <div class="ofuq-majors-panel" id="majorsExplorer" style="display: none;">

            <div class="majors-header">
                <button type="button" class="back-pill" id="backStartBtn">
                    <span class="material-icons-round">arrow_forward</span>
                    رجوع
                </button>

                <div>
                    <div class="ofuq-mini-badge">
                        <span class="material-icons-round">school</span>
                        استكشاف التخصصات
                    </div>

                    <h2>اختاري التخصص الأقرب لك</h2>

                    <p>
                        بعد اختيار التخصص، سيتم نقلك لصفحة إدخال البيانات وسيظهر التخصص تلقائيًا في الحقل المناسب.
                    </p>
                </div>
            </div>

            <div class="ofuq-majors-grid">
                @foreach($popularMajors as $major)
                    <a
                        href="{{ route('assessment', ['major' => $major['title'], 'source' => 'explore']) }}"
                        class="ofuq-major-card"
                    >
                        <div class="major-top">
                            <div class="major-icon">
                                <span class="material-icons-round">{{ $major['icon'] }}</span>
                            </div>

                            <span class="major-arrow material-icons-round">arrow_back</span>
                        </div>

                        <h3>{{ $major['title'] }}</h3>

                        <p>{{ $major['description'] }}</p>

                        <div class="major-tags">
                            @foreach($major['tags'] as $tag)
                                <span>{{ $tag }}</span>
                            @endforeach
                        </div>
                    </a>
                @endforeach
            </div>

        </div>

    </div>
</section>


<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startCard = document.getElementById('startCard');
        const majorsExplorer = document.getElementById('majorsExplorer');
        const showMajorsBtn = document.getElementById('showMajorsBtn');
        const backStartBtn = document.getElementById('backStartBtn');

        function openMajors() {
            startCard.style.display = 'none';
            majorsExplorer.style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function backToStart() {
            majorsExplorer.style.display = 'none';
            startCard.style.display = 'block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        if (showMajorsBtn) {
            showMajorsBtn.addEventListener('click', openMajors);

            showMajorsBtn.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openMajors();
                }
            });
        }

        if (backStartBtn) {
            backStartBtn.addEventListener('click', backToStart);
        }
    });
</script>

@endsection
