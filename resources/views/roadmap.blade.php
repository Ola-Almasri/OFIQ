@extends('layouts.app')

@section('title', 'الخطة الدراسية - Ofuq AI')
@section('body_class', 'roadmap-page')

@section('content')

@php
    /*
        المتغيرات المتوقعة من RoadmapController:
        $careerPath
        $overview
        $courses
        $aiAdvice
    */

    $stages = [];

    if (isset($overview) && $overview) {
        if (is_array($overview->stages)) {
            $stages = $overview->stages;
        } elseif (is_string($overview->stages)) {
            $decodedStages = json_decode($overview->stages, true);
            $stages = is_array($decodedStages) ? $decodedStages : [];
        }
    }

    $skills = [];

    if (isset($overview) && $overview) {
        if (is_array($overview->skills)) {
            $skills = $overview->skills;
        } elseif (is_string($overview->skills)) {
            $decodedSkills = json_decode($overview->skills, true);
            $skills = is_array($decodedSkills) ? $decodedSkills : [];
        }
    }

    $outcomes = [];

    if (isset($overview) && $overview) {
        if (is_array($overview->outcomes)) {
            $outcomes = $overview->outcomes;
        } elseif (is_string($overview->outcomes)) {
            $decodedOutcomes = json_decode($overview->outcomes, true);
            $outcomes = is_array($decodedOutcomes) ? $decodedOutcomes : [];
        }
    }

    $stageCount = count($stages);

    $stageIcons = [
        'menu_book',
        'code',
        'terminal',
        'palette',
        'psychology',
        'construction',
        'science',
        'rocket_launch',
        'verified',
    ];
@endphp

<div class="roadmap-page-wrapper">

    <section class="roadmap-glass-card roadmap-hero">
        <div class="roadmap-hero-content">
            <span class="roadmap-kicker">
                خارطة التعلم العامة
            </span>

            <h1>
                {{ $overview->title ?? 'خارطة التعلم والتوصيات' }}
            </h1>

            <p>
                {{ $overview->summary ?? 'خطة تعليمية عامة مبنية على مسارك الحالي ونتيجة اختبارك التشخيصي.' }}
            </p>

            <div class="roadmap-current-path">
                <span class="material-icons-round">track_changes</span>
                <span>المسار الحالي:</span>
                <strong>{{ $careerPath->name ?? 'لم يتم اختيار مسار' }}</strong>
            </div>
        </div>

        <div class="roadmap-hero-badge">
            <div class="roadmap-main-circle">
                <span>{{ $stageCount }}</span>
            </div>
            <p>مسارات رئيسية</p>
        </div>
    </section>

    <div class="roadmap-summary-grid">
        <div class="roadmap-glass-card roadmap-summary-card">
            <div class="roadmap-icon-circle roadmap-orange-glow">⏳</div>

            <div class="roadmap-card-content">
                <span class="roadmap-label">المدة المقترحة</span>
                <span class="roadmap-value">
                    {{ $overview->estimated_duration ?? 'لم تحدد بعد' }}
                </span>
            </div>
        </div>

        <div class="roadmap-glass-card roadmap-summary-card">
            <div class="roadmap-icon-circle roadmap-blue-glow">📈</div>

            <div class="roadmap-card-content">
                <span class="roadmap-label">نسبة التوافق</span>
                <span class="roadmap-value">
                    {{ $careerPath->match_percentage ?? 0 }}%
                </span>
            </div>
        </div>

        <div class="roadmap-glass-card roadmap-summary-card">
            <div class="roadmap-icon-circle roadmap-purple-glow">🎯</div>

            <div class="roadmap-card-content">
                <span class="roadmap-label">مستوى الخطة</span>
                <span class="roadmap-value">
                    {{ $overview->level ?? 'حسب نتيجة الاختبار' }}
                </span>
            </div>
        </div>

        <div class="roadmap-glass-card roadmap-summary-card">
            <div class="roadmap-icon-circle roadmap-green-glow">🧩</div>

            <div class="roadmap-card-content">
                <span class="roadmap-label">عدد المسارات</span>
                <span class="roadmap-value">
                    {{ $stageCount }} مسارات
                </span>
            </div>
        </div>
    </div>

    <div class="roadmap-dashboard-grid">

        <div class="roadmap-glass-card roadmap-main-section">
            <div class="roadmap-section-head">
                <div>
                    <h2 class="roadmap-section-title">
                        المسارات التعليمية الرئيسية
                    </h2>

                    <p>
                        هذه الصفحة تعرض العناوين الكبيرة والمحتوى المختصر لكل مسار. التفاصيل اليومية تظهر في الخطة الشهرية.
                    </p>
                </div>

                <a href="{{ route('monthly.roadmap') }}" class="roadmap-monthly-link">
                    عرض الخطة التفصيلية
                    <span class="material-icons-round">arrow_back</span>
                </a>
            </div>

            <div class="roadmap-tracks-list">
                @forelse($stages as $index => $stage)
                    @php
                        $stageNumber = $index + 1;

                        $stageTitle = $stage['title'] ?? ('المسار ' . $stageNumber);

                        $stageGoal = $stage['goal']
                            ?? ($stage['description'] ?? 'هدف هذا المسار سيتم عرضه بعد توليد الخطة.');

                        $stageNodes = $stage['nodes'] ?? [];

                        $stageProject = $stage['final_project']
                            ?? ($stage['project'] ?? null);

                        $stageDuration = $stage['estimated_duration'] ?? 'مدة تقديرية غير محددة';

                        $icon = $stageIcons[$index % count($stageIcons)];
                    @endphp

                    <article class="roadmap-track-card roadmap-track-card--{{ ($index % 4) + 1 }}">
                        <div class="roadmap-track-top">
                            <div class="roadmap-track-number">
                                {{ $stageNumber }}
                            </div>

                            <div class="roadmap-track-title-area">
                                <span class="roadmap-track-label">
                                    المسار {{ $stageNumber }}
                                </span>

                                <h3>
                                    {{ $stageTitle }}
                                </h3>
                            </div>

                            <div class="roadmap-track-icon">
                                <span class="material-icons-round">
                                    {{ $icon }}
                                </span>
                            </div>
                        </div>

                        <div class="roadmap-track-goal">
                            <strong>الهدف:</strong>
                            <span>{{ $stageGoal }}</span>
                        </div>

                        <div class="roadmap-track-meta">
                            <span>
                                <span class="material-icons-round">schedule</span>
                                {{ $stageDuration }}
                            </span>

                            <span>
                                <span class="material-icons-round">account_tree</span>
                                {{ is_array($stageNodes) ? count($stageNodes) : 0 }} عقد تعليمية
                            </span>
                        </div>

                        @if(is_array($stageNodes) && count($stageNodes) > 0)
                            <div class="roadmap-nodes-grid">
                                @foreach($stageNodes as $node)
                                    <div class="roadmap-node-box">
                                        <h4>
                                            {{ $node['title'] ?? 'عقدة تعليمية' }}
                                        </h4>

                                        @if(isset($node['items']) && is_array($node['items']))
                                            <ul>
                                                @foreach($node['items'] as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($stageProject)
                            <div class="roadmap-final-project">
                                <span class="material-icons-round">rocket_launch</span>
                                <div>
                                    <strong>المشروع أو المخرج الختامي:</strong>
                                    <p>{{ $stageProject }}</p>
                                </div>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="roadmap-empty-state">
                        <div class="roadmap-empty-icon">
                            <span class="material-icons-round">hourglass_top</span>
                        </div>

                        <h3>الخطة العامة غير جاهزة بعد</h3>

                        <p>
                            بعد إنهاء الاختبار سيتم توليد المسارات العامة تلقائيًا. إذا كنتِ انتهيتِ من الاختبار، انتظري لحظات ثم أعيدي تحميل الصفحة.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        <aside class="roadmap-sidebar">

            <div class="roadmap-glass-card roadmap-advice-card">
                <div class="roadmap-side-title">
                    <span class="material-icons-round">lightbulb</span>
                    <h2>نصيحة ذكية</h2>
                </div>

                <p>
                    {{ $aiAdvice ?? ($overview->summary ?? 'ابدئي بفهم المسارات العامة أولًا، ثم انتقلي للخطة الشهرية لتنفيذ المهام اليومية بالتدريج.') }}
                </p>
            </div>

            <div class="roadmap-glass-card roadmap-skills-card">
                <div class="roadmap-side-title">
                    <span class="material-icons-round">auto_awesome</span>
                    <h2>المهارات التي ستكتسبينها</h2>
                </div>

                @if(count($skills) > 0)
                    <div class="roadmap-small-tags">
                        @foreach($skills as $skill)
                            <span>{{ $skill }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="roadmap-empty-text">
                        سيتم عرض المهارات بعد توليد الخطة العامة.
                    </p>
                @endif
            </div>

            <div class="roadmap-glass-card roadmap-outcomes-card">
                <div class="roadmap-side-title">
                    <span class="material-icons-round">task_alt</span>
                    <h2>مخرجات الخطة</h2>
                </div>

                @if(count($outcomes) > 0)
                    <div class="roadmap-outcomes-list">
                        @foreach($outcomes as $outcome)
                            <div class="roadmap-outcome-item">
                                <span class="material-icons-round">check_circle</span>
                                <p>{{ $outcome }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="roadmap-empty-text">
                        سيتم عرض المخرجات بعد توليد الخطة العامة.
                    </p>
                @endif
            </div>

            <div class="roadmap-glass-card roadmap-courses-card">
                <div class="roadmap-side-title">
                    <span class="material-icons-round">school</span>
                    <h2>كورسات مقترحة</h2>
                </div>

                <div class="roadmap-courses-list">
                    @if(isset($courses) && $courses->count() > 0)
                        @foreach($courses as $course)
                            <div class="roadmap-course-item">
                                <div class="roadmap-course-meta">
                                    <strong>{{ $course->title }}</strong>

                                    <span>
                                        {{ $course->platform ?? 'منصة تعليمية' }}
                                        •
                                        {{ $course->level ?? 'مناسب للجميع' }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="roadmap-btn-view"
                                    onclick="window.open('{{ $course->url ?: '#' }}', '_blank')">
                                    عرض
                                </button>
                            </div>
                        @endforeach
                    @else
                        <div class="roadmap-course-item">
                            <div class="roadmap-course-meta">
                                <strong>لا توجد كورسات مقترحة بعد</strong>
                                <span>سيتم توليد الكورسات تلقائيًا حسب المسار المختار.</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

        </aside>
    </div>
</div>


@endsection