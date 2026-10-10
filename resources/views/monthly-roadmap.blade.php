@extends('layouts.app')

@section('title', 'الخطة التعليمية الشهرية - Ofuq AI')
@section('header_type', 'auth')
@section('body_class', 'monthly-roadmap-page')

@php
    $weekStyles = [
        1 => [
            'card' => 'card-purple',
            'text' => 'text-purple',
            'bg' => 'bg-purple',
            'icon' => 'week-purple',
            'emoji' => '⚑',
        ],
        2 => [
            'card' => 'card-blue',
            'text' => 'text-blue',
            'bg' => 'bg-blue',
            'icon' => 'week-blue',
            'emoji' => '⌘',
        ],
        3 => [
            'card' => 'card-green',
            'text' => 'text-green',
            'bg' => 'bg-green',
            'icon' => 'week-green',
            'emoji' => '💻',
        ],
        4 => [
            'card' => 'card-orange',
            'text' => 'text-orange',
            'bg' => 'bg-orange',
            'icon' => 'week-orange',
            'emoji' => '📊',
        ],
    ];

    $rawSkills = $careerPath->required_skills ?? [];

    if (is_string($rawSkills)) {
        $decodedSkills = json_decode($rawSkills, true);
        $skills = is_array($decodedSkills) ? $decodedSkills : [];
    } elseif (is_array($rawSkills)) {
        $skills = $rawSkills;
    } else {
        $skills = [];
    }

    $months = $months ?? collect();

    $totalTasks = $totalTasks ?? 0;
    $completedTasks = $completedTasks ?? 0;
    $remainingTasks = $remainingTasks ?? 0;
    $overallProgress = $overallProgress ?? 0;

    $taskTypeLabels = [
        'lesson' => 'درس',
        'practice' => 'تطبيق',
        'quiz' => 'اختبار',
        'project' => 'مشروع',
        'review' => 'مراجعة',
    ];

    $currentMonthName = 'الخطة الكاملة';
@endphp

@section('content')

<section id="MonthlyLearningPlan" class="MonthlyLearningPlan">

    <div class="mlp-header">
        <div class="mlp-title-side">
            <h1>الخطة التعليمية الشهرية</h1>
            <p>خطة مرتبة حسب الأشهر والأسابيع، وكل أسبوع يحتوي مهام يومية واضحة.</p>
        </div>

        <div class="mlp-header-actions no-export">
            <div class="mlp-month-selector">
                <div class="mlp-month-display">
                    <span>{{ $currentMonthName }}</span>
                </div>
            </div>

            <button class="mlp-export-btn" type="button" id="exportImageBtn">
                تصدير كصورة
            </button>

            <button class="mlp-export-btn mlp-export-btn-pdf" type="button" id="exportPdfBtn">
                تصدير PDF
            </button>
        </div>
    </div>

    <div class="mlp-main-container">
        <div class="mlp-content-area">

            @forelse($months as $monthNumber => $monthWeeks)
                @php
                    $monthTasks = $monthWeeks->flatten();
                    $monthTotal = $monthTasks->count();
                    $monthCompleted = $monthTasks->where('status', 'completed')->count();
                    $monthProgress = $monthTotal > 0 ? round(($monthCompleted / $monthTotal) * 100) : 0;
                @endphp

                <section class="mlp-month-section">
                    <div class="mlp-month-heading">
                        <div>
                            <span class="mlp-month-badge">الشهر {{ $monthNumber }}</span>
                            <h2>خطة الشهر {{ $monthNumber }}</h2>
                            <p>هذا الشهر يحتوي على {{ $monthTotal }} مهمة موزعة على {{ $monthWeeks->count() }} أسابيع.</p>
                        </div>

                        <div class="mlp-month-mini-progress">
                            <strong>{{ $monthProgress }}%</strong>
                            <span>إنجاز الشهر</span>
                        </div>
                    </div>

                    <div class="mlp-month-progress-bg">
                        <div class="mlp-month-progress-fill" style="width: {{ $monthProgress }}%;"></div>
                    </div>

                    <div class="mlp-weekly-grid">
                        @foreach($monthWeeks as $weekNumber => $weekTasks)
                            @php
                                $style = $weekStyles[(($weekNumber - 1) % 4) + 1] ?? $weekStyles[1];

                                $weekTotal = $weekTasks->count();
                                $weekCompleted = $weekTasks->where('status', 'completed')->count();

                                $weekProgress = $weekTotal > 0
                                    ? round(($weekCompleted / $weekTotal) * 100)
                                    : 0;

                                $remainingInWeek = $weekTotal - $weekCompleted;

                                $firstTask = $weekTasks->first();
                                $firstTitle = $firstTask->title ?? '';

                                $weekHeading = 'الأسبوع ' . $weekNumber;

                                if (str_contains($firstTitle, 'HTML')) {
                                    $weekHeading .= ' - HTML';
                                } elseif (str_contains($firstTitle, 'CSS')) {
                                    $weekHeading .= ' - CSS';
                                } elseif (str_contains($firstTitle, 'JavaScript')) {
                                    $weekHeading .= ' - JavaScript';
                                } elseif (str_contains($firstTitle, 'DOM')) {
                                    $weekHeading .= ' - DOM';
                                } elseif (str_contains($firstTitle, 'Git')) {
                                    $weekHeading .= ' - GitHub';
                                } elseif (str_contains($firstTitle, 'Tailwind')) {
                                    $weekHeading .= ' - Tailwind';
                                } elseif (str_contains($firstTitle, 'React')) {
                                    $weekHeading .= ' - React';
                                } elseif (str_contains($firstTitle, 'صيدلة') || str_contains($firstTitle, 'الوصفة')) {
                                    $weekHeading .= ' - أساسيات الصيدلة';
                                } elseif (str_contains($firstTitle, 'الجرعات')) {
                                    $weekHeading .= ' - الجرعات';
                                } elseif (str_contains($firstTitle, 'التداخلات')) {
                                    $weekHeading .= ' - التداخلات الدوائية';
                                } elseif (str_contains($firstTitle, 'إرشاد')) {
                                    $weekHeading .= ' - إرشاد المريض';
                                } elseif (str_contains($firstTitle, 'OTC')) {
                                    $weekHeading .= ' - OTC';
                                } elseif (str_contains($firstTitle, 'Project') || str_contains($firstTitle, 'مشروع')) {
                                    $weekHeading .= ' - مشروع';
                                }
                            @endphp

                            <article class="mlp-card {{ $style['card'] }}">
                                <div class="mlp-card-top">
                                    <span class="card-tag {{ $style['text'] }}">
                                        الشهر {{ $monthNumber }} - الأسبوع {{ $weekNumber }}
                                    </span>

                                    <div class="mlp-icon {{ $style['icon'] }}">
                                        {{ $style['emoji'] }}
                                    </div>
                                </div>

                                <h3 class="card-heading">
                                    {{ $weekHeading }}
                                </h3>

                                <p class="card-date">
                                    {{ $weekTotal }} مهام تعليمية
                                </p>

                                <div class="mlp-progress-container">
                                    <div class="mlp-progress-info">
                                        <span>التقدم</span>
                                        <span class="{{ $style['text'] }} counter" data-target="{{ $weekProgress }}">
                                            0%
                                        </span>
                                    </div>

                                    <div class="mlp-progress-bg">
                                        <div
                                            class="mlp-progress-fill {{ $style['bg'] }}"
                                            data-width="{{ $weekProgress }}%">
                                        </div>
                                    </div>
                                </div>

                                <ul class="mlp-task-list">
                                    @forelse($weekTasks as $task)
                                        @php
                                            $typeLabel = $taskTypeLabels[$task->task_type] ?? $task->task_type;
                                        @endphp

                                        <li class="mlp-task-item {{ $task->status === 'completed' ? 'completed' : '' }}">
                                            <form
                                                action="{{ route('roadmap.tasks.toggle', $task->id) }}"
                                                method="POST"
                                                class="mlp-task-form"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" class="mlp-task-btn">
                                                    <span class="mlp-checkbox"></span>

                                                    <span class="mlp-task-text">
                                                        <strong>
                                                            اليوم {{ $task->day_number ?? '-' }}:
                                                            {{ $task->title }}
                                                        </strong>

                                                        @if(!empty($task->description))
                                                            <small>
                                                                {{ $task->description }}
                                                            </small>
                                                        @endif

                                                        <span class="mlp-task-meta">
                                                            <span>{{ $typeLabel }}</span>
                                                            <span>{{ $task->duration ?? '45 دقيقة' }}</span>
                                                        </span>
                                                    </span>
                                                </button>
                                            </form>
                                        </li>
                                    @empty
                                        <li class="mlp-task-item">
                                            <div class="mlp-checkbox"></div>
                                            <span class="mlp-task-text">
                                                <strong>لا توجد مهام لهذا الأسبوع بعد</strong>
                                            </span>
                                        </li>
                                    @endforelse
                                </ul>

                                <div class="mlp-card-footer">
                                    @if($weekTotal === 0)
                                        <span>⏳</span>
                                        لم يتم إنشاء مهام بعد
                                    @elseif($remainingInWeek === 0)
                                        <span>✅</span>
                                        تم إنجاز الأسبوع
                                    @else
                                        <span>⏱️</span>
                                        متبقي {{ $remainingInWeek }} مهام
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <article class="mlp-empty-card">
                    <div class="mlp-icon week-purple">⏳</div>

                    <h3>لم يتم إنشاء الخطة التفصيلية بعد</h3>

                    <p>
                        بعد إنهاء الاختبار سيتم توليد الخطة التعليمية التفصيلية تلقائيًا، ثم ستظهر هنا مقسمة حسب الأشهر والأسابيع.
                    </p>
                </article>
            @endforelse

        </div>

        <aside class="mlp-sidebar">
            <div class="mlp-side-card">
                <h3 class="side-card-title">ملخص الخطة التعليمية</h3>

                <div class="circular-progress-box">
                    <div class="progress-ring-container">
                        <svg class="progress-ring" width="120" height="120">
                            <circle
                                class="progress-bg"
                                stroke="#f3f4f6"
                                stroke-width="8"
                                fill="transparent"
                                r="50"
                                cx="60"
                                cy="60"
                            />

                            <circle
                                class="progress-ring-circle"
                                stroke="var(--primary)"
                                stroke-width="8"
                                fill="transparent"
                                r="50"
                                cx="60"
                                cy="60"
                                id="mlpCircleProgress"
                            />
                        </svg>

                        <div class="progress-ring-text counter" data-target="{{ $overallProgress ?? 0 }}">
                            0%
                        </div>
                    </div>

                    <p class="mlp-muted-text">
                        التقدم الكلي
                    </p>
                </div>

                <div class="mlp-stats-list">
                    <div class="mlp-stat-row">
                        <span class="stat-label">إجمالي المهام</span>
                        <span class="stat-value">{{ $totalTasks }}</span>
                    </div>

                    <div class="mlp-stat-row">
                        <span class="stat-label">المهام المكتملة</span>
                        <span class="stat-value text-green">{{ $completedTasks }}</span>
                    </div>

                    <div class="mlp-stat-row">
                        <span class="stat-label">المهام المتبقية</span>
                        <span class="stat-value text-orange">{{ $remainingTasks }}</span>
                    </div>
                </div>
            </div>

            <div class="mlp-side-card">
                <h3 class="side-card-title">المسار الحالي</h3>

                <div class="path-info">
                    <p class="path-name">{{ $careerPath->name ?? 'لم يتم اختيار مسار' }}</p>

                    <p class="mlp-muted-small">
                        خطة مبنية على نتيجة اختبارك التشخيصي والمسار النشط.
                    </p>

                    <span class="path-badge">
                        {{ $careerPath->match_percentage ?? 0 }}% ملاءمة
                    </span>
                </div>
            </div>

            <div class="mlp-side-card">
                <h3 class="side-card-title">المهارات الرئيسية</h3>

                <div class="skills-container">
                    @php
                        // تحويل المهارات إلى أسماء فقط
                        $skillNames = [];
                        if (is_array($skills)) {
                            foreach ($skills as $skill) {
                                if (is_array($skill) && isset($skill['name'])) {
                                    $skillNames[] = $skill['name'];
                                } elseif (is_object($skill) && isset($skill->name)) {
                                    $skillNames[] = $skill->name;
                                } elseif (is_string($skill)) {
                                    $skillNames[] = $skill;
                                }
                            }
                        }
                    @endphp

                    @forelse(array_slice($skillNames, 0, 8) as $skillName)
                        <span class="skill-tag">{{ $skillName }}</span>
                    @empty
                        <span class="skill-tag">لا توجد مهارات محددة بعد</span>
                    @endforelse
                </div>
            </div>
            
        </aside>
    </div>

    <div class="mlp-insights-row">
        <div class="mlp-insight-card">
            <div class="insight-icon icon-bg-purple">💡</div>

            <div class="insight-tip">
                <h4>نصيحة ذكية</h4>
                <p>
                    ابدئي بمهمة واحدة يوميًا، وركزي على التطبيق العملي بدل قراءة الخطة كلها مرة واحدة.
                </p>
            </div>
        </div>

        <div class="mlp-insight-card">
            <div class="insight-icon icon-bg-blue">🕒</div>

            <div class="insight-content">
                <h4>متوسط الوقت اليومي</h4>
                <p>45 - 90 دقيقة</p>
            </div>
        </div>

        <div class="mlp-insight-card">
            <div class="insight-icon icon-bg-orange">📅</div>

            <div class="insight-content">
                <h4>المهام المتبقية</h4>
                <p>{{ $remainingTasks }} مهمة</p>
            </div>
        </div>

        <div class="mlp-insight-card">
            <div class="insight-icon icon-bg-green">🎯</div>

            <div class="insight-content">
                <h4>الهدف التعليمي</h4>
                <p>إكمال {{ $totalTasks }} مهمة بتطبيق عملي</p>
            </div>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const progressFills = document.querySelectorAll('.mlp-progress-fill');

        setTimeout(() => {
            progressFills.forEach(fill => {
                fill.style.width = fill.getAttribute('data-width');
            });
        }, 300);

        const circle = document.getElementById('mlpCircleProgress');

        if (circle) {
            const radius = circle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;
            const targetPercent = {{ $overallProgress ?? 0 }};
            const offset = circumference - (targetPercent / 100 * circumference);

            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = circumference;

            setTimeout(() => {
                circle.style.strokeDashoffset = offset;
            }, 500);
        }

        const counters = document.querySelectorAll('.counter');

        const animateCounter = (el) => {
            const target = parseInt(el.getAttribute('data-target')) || 0;
            let current = 0;
            const increment = Math.max(target / 50, 1);

            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    el.innerText = Math.min(Math.ceil(current), target) + '%';
                    setTimeout(updateCounter, 20);
                } else {
                    el.innerText = target + '%';
                }
            };

            updateCounter();
        };

        counters.forEach(counter => {
            animateCounter(counter);
        });

        const planElement = document.getElementById('MonthlyLearningPlan');
        const exportImageBtn = document.getElementById('exportImageBtn');
        const exportPdfBtn = document.getElementById('exportPdfBtn');

        const waitForFonts = async () => {
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }

            await new Promise(resolve => setTimeout(resolve, 800));
        };

        const generatePlanImage = async () => {
            if (!planElement) {
                throw new Error('MonthlyLearningPlan element not found');
            }

            await waitForFonts();

            const originalScrollX = window.scrollX;
            const originalScrollY = window.scrollY;

            window.scrollTo(0, 0);

            await new Promise(resolve => setTimeout(resolve, 300));

            const taskLists = document.querySelectorAll('.mlp-task-list');
            const originalStyles = [];

            taskLists.forEach((list) => {
                originalStyles.push({
                    element: list,
                    maxHeight: list.style.maxHeight,
                    overflowY: list.style.overflowY
                });

                list.style.maxHeight = 'none';
                list.style.overflowY = 'visible';
            });

            const cards = document.querySelectorAll('.mlp-card');
            const originalCardStyles = [];

            cards.forEach((card) => {
                originalCardStyles.push({
                    element: card,
                    maxHeight: card.style.maxHeight
                });

                card.style.maxHeight = 'none';
            });

            const width = Math.max(
                planElement.scrollWidth,
                planElement.offsetWidth,
                planElement.getBoundingClientRect().width
            );

            const height = Math.max(
                planElement.scrollHeight,
                planElement.offsetHeight,
                planElement.getBoundingClientRect().height
            );

            const dataUrl = await htmlToImage.toPng(planElement, {
                cacheBust: true,
                pixelRatio: 2,
                backgroundColor: '#f8f7ff',
                width: width + 80,
                height: height + 80,
                style: {
                    width: width + 'px',
                    minWidth: width + 'px',
                    height: height + 'px',
                    direction: 'rtl',
                    overflow: 'visible',
                    margin: '0',
                    padding: '40px'
                },
                filter: (node) => {
                    return !(
                        node.classList &&
                        node.classList.contains('no-export')
                    );
                }
            });

            originalStyles.forEach((item) => {
                item.element.style.maxHeight = item.maxHeight;
                item.element.style.overflowY = item.overflowY;
            });

            originalCardStyles.forEach((item) => {
                item.element.style.maxHeight = item.maxHeight;
            });

            window.scrollTo(originalScrollX, originalScrollY);

            return {
                dataUrl,
                width: width + 80,
                height: height + 80
            };
        };

        if (exportImageBtn) {
            exportImageBtn.addEventListener('click', async () => {
                try {
                    exportImageBtn.disabled = true;
                    exportImageBtn.innerText = 'جاري التصدير...';

                    const result = await generatePlanImage();

                    const link = document.createElement('a');
                    link.href = result.dataUrl;
                    link.download = 'monthly-learning-plan.png';

                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } catch (error) {
                    alert('حدث خطأ أثناء تصدير الصورة');
                    console.error(error);
                } finally {
                    exportImageBtn.disabled = false;
                    exportImageBtn.innerText = 'تصدير كصورة';
                }
            });
        }

        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', async () => {
                try {
                    exportPdfBtn.disabled = true;
                    exportPdfBtn.innerText = 'جاري التصدير...';

                    const result = await generatePlanImage();

                    const { jsPDF } = window.jspdf;

                    const pdf = new jsPDF({
                        orientation: result.width > result.height ? 'landscape' : 'portrait',
                        unit: 'px',
                        format: [result.width, result.height]
                    });

                    pdf.addImage(result.dataUrl, 'PNG', 0, 0, result.width, result.height);
                    pdf.save('monthly-learning-plan.pdf');
                } catch (error) {
                    alert('حدث خطأ أثناء تصدير PDF');
                    console.error(error);
                } finally {
                    exportPdfBtn.disabled = false;
                    exportPdfBtn.innerText = 'تصدير PDF';
                }
            });
        }
    });
</script>

@endsection