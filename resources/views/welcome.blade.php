@extends('layouts.app')
@section('title', 'Ofuq AI')
@section('body_class', 'home-page')
{{-- ما نحتاج نحدد نوع لأنه الـ default هو header-main --}}
@section('content')

<!-- MAIN ASSESSMENT SECTION -->
<section class="assessment">

    <!-- HERO -->
    <section class="hero" id="hero">
        <div class="container hero__grid">
            <!-- Text -->
            <div class="hero__text">
                <div class="chip">
                <span class="material-icons-round chip__icon">auto_awesome</span>
                <span class="chip__txt">مستقبلك يبدأ هنا</span>
                </div>
                <h1 class="hero__title"> مستقبلك المهني
                    <span class="hero__line grad-text">بذكاء اصطناعي فائق</span>
                </h1>
                <p class="hero__desc">
                نحلل مهاراتك، نرسم مسارك، ونبني خطتك الدراسية في مكان واحد. اكتشف الإمكانات اللامحدودة لمسارك المهني مع أدق التحليلات الذكية.
                </p>
                <div class="hero__buttons">
                    <a href="{{ route('assessment.start') }}" class="btn btn--glow btn--lg">
                            ابدأ رحلتك الآن
                        <span class="material-icons-round">arrow_back</span>
                    </a>

                    <button class="btn btn--soft btn--lg" type="button">
                        <span class="material-icons-round icon-purple">play_circle</span>
                        شاهد كيف نعمل
                    </button>
                </div>
            </div>

            <!-- Image -->
            <div class="hero__media">
            <div class="hero__blob" aria-hidden="true"></div>
            <div class="float">
                <!-- بدّلها لصورة البنت تبعتك -->
                <img class="hero__img" src="./assets/img/logo.png" alt="AI Hero"
                    onerror="this.style.opacity='0.15'; this.alt='ضع صورة hero.png في assets/img'" />
            </div>
            </div>
        </div>
    </section>

    <!-- STATS -->
    <section class="stats" id="stats">
        <div class="container">
        <div class="stats__card glass">
            <div class="stat">
            <div class="stat__icon teal">
                <span class="material-icons-round">query_stats</span>
            </div>
            <div class="stat__num stat-number" data-target="500" data-suffix="+">0</div>
            <div class="stat__label">تحليل دقيق ومدعوم</div>
            </div>

            <div class="divider"></div>

            <div class="stat">
            <div class="stat__icon purple">
                <span class="material-icons-round">school</span>
            </div>
            <div class="stat__num stat-number" data-target="1000" data-suffix="+">0</div>
            <div class="stat__label">كورس تعليمي مختار</div>
            </div>

            <div class="divider"></div>

            <div class="stat">
            <div class="stat__icon orange">
                <span class="material-icons-round">work_outline</span>
            </div>
            <div class="stat__num stat-number" data-target="95" data-suffix="%">0</div>
            <div class="stat__label">مسار مهني متاح</div>
            </div>
        </div>
        </div>
    </section>

    <!-- ABOUT -->
    <section class="about" id="about">
        <div class="container">
        <div class="about__box glass">
            <div class="about__grid">

            <div class="about__text">
                <div class="badge">خوارزميات متطورة</div>

                <h2 class="h2">
                ثورة الذكاء الاصطناعي في
                <span class="title-line primary">التخطيط الدراسي</span>
                </h2>
                
                <p class="p">
                نستخدم أقوى خوارزميات الذكاء الاصطناعي لربط مهاراتك الحالية بمتطلبات سوق العمل العالمية.
                منصتنا لا تقترح المسارات فحسب، بل تتعمق في تحليل قدراتك لتقديم خارطة طريق مخصصة تضمن لك التميز في سوق عمل تنافسي.
                </p>

            <ul class="list">
                <li>
                <span class="material-icons-round ok">check_circle</span>
                <span>توجيه مهني مخصص لكل طالب</span>
                </li>
                <li>
                <span class="material-icons-round ok">check_circle</span>
                <span>تحديثات مستمرة لسوق العمل</span>
                </li>
                <li>
                <span class="material-icons-round ok">check_circle</span>
                <span>ربط المهارات بالكورسات المناسبة</span>
                </li>
            </ul>
            
            </div>

            <div class="about__media">
                <div class="orbit">
                <div class="orbit__ring ring1"></div>
                <div class="orbit__ring ring2"></div>
                <img class="about__img" src="./assets/img/ai_engine.png" alt="AI Engine">
                </div>
            </div>

            </div>
        </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section class="services"id="services" >
        <div class="container center">
        <h2 class="h2">كل ما تحتاجه للنجاح</h2>
        <p class="sub">من تحديد المسار إلى إتقان المهارات، أفق يرافقك في كل خطوة</p>

        <div class="cards">
            <article class="card glass">
            <div class="card__icon teal">
                <span class="material-icons-round">psychology</span>
            </div>
            <h3>تحليل المهارات</h3>
            <p>اختبارات ذكاء اصطناعي عميقة لتقييم نقاط قوتك واكتشاف مواهبك الدفينة.</p>
            </article>

            <article class="card glass">
            <div class="card__icon purple">
                <span class="material-icons-round">map</span>
            </div>
            <h3>خرائط طريق ذكية</h3>
            <p>خطة دراسية ومهنية متكاملة خطوة بخطوة للوصول إلى الوظيفة التي تحلم بها.</p>
            </article>

            <article class="card glass">
            <div class="card__icon orange">
                <span class="material-icons-round">trending_up</span>
            </div>
            <h3>توقعات السوق</h3>
            <p>احصل على رؤى حول الطلب المستقبلي على المهن المختلفة والرواتب المتوقعة.</p>
            </article>
        </div>
        </div>
    </section>

</section>


@endsection
