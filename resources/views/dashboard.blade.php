@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'الرئيسية')

@section('content')

@php
    $user = Auth::user();
    $avatarPath = $user->avatar;
    $timestamp = $user->updated_at ? $user->updated_at->timestamp : time();
    $avatarUrl = ($avatarPath && Storage::disk('public')->exists($avatarPath))
        ? asset('storage/' . $avatarPath . '?v=' . $timestamp)
        : asset('assets/img/default-avatar.jpg');


    $aiRoadmap = \App\Models\AiAdvisory::where('user_id', Auth::id())
        ->where('type', 'roadmap')
        ->latest()
        ->first();

    $aiData = $aiRoadmap ? json_decode($aiRoadmap->ai_response, true) : null;

    $selectedCareerPath = \App\Models\CareerPath::where('user_id', Auth::id())
        ->where('status', 'active')
        ->latest()
        ->first();

    $careerName = $selectedCareerPath->name ?? 'لم يتم اختيار مسار بعد';
    $matchPercentage = $selectedCareerPath->match_percentage ?? 75;
    $matchReason = $selectedCareerPath->reason ?? 'مبني على مهاراتك واهتماماتك الحالية.';
    $requiredSkills = $selectedCareerPath->required_skills ?? [];
    $salaryRange = 'غير محدد حالياً';
    $aiAdvice = $aiData['advice'] ?? 'استمري في تطوير مهاراتك خطوة بخطوة حسب المسار المختار.';

    $userAssessment = Auth::user()->assessment;

    $userSkills = explode(',', $userAssessment->skills ?? '');
    $userSkills = array_filter(array_map('trim', $userSkills));

    $userInterests = explode(',', $userAssessment->interests ?? '');
    $userInterests = array_filter(array_map('trim', $userInterests));

    $skillsCount = count($userSkills);
    $interestsCount = count($userInterests);

    $profileCompletion = 65;

    if ($skillsCount > 0) {
        $profileCompletion += 10;
    }

    if ($interestsCount > 0) {
        $profileCompletion += 10;
    }

    if ($userAssessment && $userAssessment->major) {
        $profileCompletion += 10;
    }

    $profileCompletion = min($profileCompletion, 100);

    // ✅ جلب مهام المستخدم (من جدول user_tasks)
    $userTasks = \App\Models\UserTask::where('user_id', Auth::id())
        ->where('status', 'pending')
        ->orderBy('priority', 'desc')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    $planProgress = $overallProgress ?? 0;
@endphp

<link rel="preload" as="image" href="{{ $avatarUrl }}">

<main class="dashboard-main">

    <section class="dashboard-hero-card glass">
        <div class="dashboard-hero-card__image">
            <img
                src="{{ $avatarUrl }}"
                alt="Student Profile"
                loading="eager"
            >
        </div>

        <div class="dashboard-hero-card__content">
            <span class="dashboard-hero-card__label">لوحة التحكم الذكية</span>

            <h1 class="dashboard-hero-card__title">
                مرحباً {{ Auth::user()->name }}، جاهزة تكملي رحلتك؟
            </h1>

            <p class="dashboard-hero-card__desc">{{ $aiAdvice }}</p>

            <div class="dashboard-hero-card__progress-top">
                <span>
                    <span
                        class="hero-progress-counter"
                        data-target="{{ $planProgress }}"
                    >0%</span>
                    إنجاز الخطة
                </span>

                <span>{{ $careerName }}</span>
            </div>

            <div class="dashboard-hero-card__progress">
                <span
                    class="dashboard-hero-card__progress-fill hero-progress-fill"
                    data-width="{{ $planProgress }}%"
                    style="width: 0%;">
                </span>
            </div>

            <div class="dashboard-hero-card__actions">
                <button
                    class="btn btn--glow btn--lg"
                    type="button"
                    onclick="window.location.href='{{ route('roadmap') }}'"
                >
                    متابعة المسار
                </button>

                <button
                    class="btn btn--soft btn--lg"
                    type="button"
                    onclick="window.location.href='{{ route('monthly.roadmap') }}'"
                >
                    الخطة الشهرية
                </button>
            </div>
        </div>
    </section>

    <section class="dashboard-grid">

        <!-- TASKS CARD - مهام المستخدم -->
        <article class="dashboard-card glass quick-todo">
            <div class="dashboard-card__head">
                <div class="dashboard-card__head-left">
                    <span class="dashboard-card__mini">
                        <span class="material-icons-round">today</span>
                        {{ $userTasks->count() }} مهام متبقية
                    </span>

                    <h2 class="dashboard-card__title">مهامي اليومية</h2>
                </div>

                <div class="dashboard-card__head-right">
                    <button
                        class="btn btn--soft btn--sm"
                        type="button"
                        onclick="openAddTaskModal()"
                    >
                        <span class="material-icons-round">add</span>
                        إضافة مهمة
                    </button>
                </div>
            </div>

            <ul class="quick-todo__list">
                @forelse($userTasks as $task)
                    <li class="todo-item">
                        <div class="todo-icon">
                            <span class="material-icons-round">
                                {{ $task->priority === 'high' ? 'priority_high' : ($task->priority === 'medium' ? 'flag' : 'low_priority') }}
                            </span>
                        </div>

                        <div class="todo-content">
                            <span class="todo-title">{{ $task->title }}</span>

                            <span class="todo-sub">
                                @if($task->due_date)
                                    موعد: {{ \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') }}
                                @else
                                    بدون موعد
                                @endif
                                •
                                {{ ucfirst($task->priority) }}
                            </span>
                        </div>

                        <form
                            action="{{ route('user.tasks.toggle', $task->id) }}"
                            method="POST"
                            class="todo-checkbox-form"
                        >
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="todo-checkbox-btn" title="إنجاز المهمة">
                                <span class="checkmark"></span>
                            </button>
                        </form>
                    </li>
                @empty
                    <li class="todo-item">
                        <div class="todo-icon">
                            <span class="material-icons-round">celebration</span>
                        </div>

                        <div class="todo-content">
                            <span class="todo-title">رائع! لا توجد مهام متبقية</span>
                            <span class="todo-sub">أضف مهامك اليومية لتتابع تقدمك</span>
                        </div>
                    </li>
                @endforelse
            </ul>

            <div class="quick-todo__footer">
                <button
                    class="quick-todo__btn"
                    type="button"
                    onclick="window.location.href='{{ route('user.tasks') }}'"
                >
                    <span>عرض كل المهام</span>
                    <span class="material-icons-round">arrow_back</span>
                </button>

                <span class="quick-todo__note">
                    <span class="material-icons-round">info</span>
                    هذه مهامك اليومية - خطتك الشهرية موجودة في <a href="{{ route('monthly.roadmap') }}">الخطة الشهرية</a>
                </span>
            </div>
        </article>

        <!-- CAREER MATCH CARD -->
        <article class="dashboard-card glass career-match">
            <div class="career-match__head">
                <div class="career-match__badge-group">
                    <span class="career-match__badge">
                        <span class="material-icons-round">stars</span>
                        توافق {{ $matchPercentage >= 80 ? 'ممتاز' : ($matchPercentage >= 60 ? 'جيد' : 'متوسط') }}
                    </span>
                </div>

                <span class="career-match__sub">
                    <span class="material-icons-round">work</span>
                    المستقبل المهني
                </span>
            </div>

            <h2 class="dashboard-card__title">أفضل تطابق مهني لك</h2>

            <div class="career-match__box">
                <div class="career-match__info">
                    <h3>
                        <span class="material-icons-round">developer_mode</span>
                        {{ $careerName }}
                    </h3>

                    <p>{{ $matchReason }}</p>

                    <div class="career-match__salary">
                        <span class="material-icons-round" style="color: #f2b233;">payments</span>
                        متوسط الرواتب: {{ $salaryRange }}
                    </div>
                </div>

                <div class="career-match__circle">
                    <svg viewBox="0 0 120 120">
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#0ea5e9" />
                                <stop offset="100%" stop-color="#8b5cf6" />
                            </linearGradient>
                        </defs>

                        <circle cx="60" cy="60" r="48"></circle>

                        <circle
                            cx="60"
                            cy="60"
                            r="48"
                            class="career-match__circle-progress"
                            stroke-dasharray="301"
                            stroke-dashoffset="{{ 301 - (($matchPercentage / 100) * 301) }}"
                            transform="rotate(-90 60 60)"
                        ></circle>

                    </svg>

                    <span>{{ $matchPercentage }}%</span>
                </div>
            </div>

            <div class="career-match__skills-label">
                المهارات المطلوبة:
            </div>

            <div class="career-match__skills">
                @php
                    $skillNames = [];
                    if (is_array($requiredSkills)) {
                        foreach ($requiredSkills as $skill) {
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

                @foreach(array_slice($skillNames, 0, 3) as $skillName)
                    <span class="skill-tag">{{ $skillName }}</span>
                @endforeach

                @if(count($skillNames) > 3)
                    <span class="skill-tag skill-more">+{{ count($skillNames) - 3 }} مهارات</span>
                @endif
            </div>

            <div class="career-match__actions">
                <button
                    class="career-match__btn"
                    type="button"
                    onclick="window.location.href='{{ route('roadmap') }}'"
                >
                    <span>استكشف المسار</span>
                    <span class="material-icons-round">arrow_back</span>
                </button>

                <button class="career-match__btn-secondary" type="button">
                    <span class="material-icons-round">bookmark_border</span>
                </button>
            </div>
        </article>
    </section>

    <section class="dashboard-stats">
        <article class="dashboard-stat glass dashboard-stat--tip">
            <span class="dashboard-stat__dot"></span>
            <h3>نصيحة ذكية</h3>
            <p>{{ $aiAdvice }}</p>
        </article>

        <article class="dashboard-stat glass dashboard-stat--icon">
            <div class="dashboard-stat__top">
                <span class="dashboard-stat__label">المهارات لديك</span>

                <span class="dashboard-stat__iconWrap orange">
                    <span class="material-icons-round">code</span>
                </span>
            </div>

            <div class="dashboard-stat__value">{{ $skillsCount }}</div>
        </article>

        <article class="dashboard-stat glass dashboard-stat--icon">
            <div class="dashboard-stat__top">
                <span class="dashboard-stat__label">مجالات الاهتمام</span>

                <span class="dashboard-stat__iconWrap purple">
                    <span class="material-icons-round">favorite</span>
                </span>
            </div>

            <div class="dashboard-stat__value">{{ $interestsCount }}</div>
        </article>

        <article class="dashboard-stat glass dashboard-stat--icon">
            <div class="dashboard-stat__top">
                <span class="dashboard-stat__label">نسبة التوافق</span>

                <span class="dashboard-stat__iconWrap teal">
                    <span class="material-icons-round">stars</span>
                </span>
            </div>

            <div class="dashboard-stat__value">{{ $matchPercentage }}%</div>
        </article>
    </section>

</main>

<!-- Modal لإضافة مهمة جديدة -->
<div class="modal-overlay" id="addTaskModal" style="display: none;">
    <div class="modal-content glass">
        <div class="modal-header">
            <h2>إضافة مهمة جديدة</h2>
            <button class="modal-close" onclick="closeAddTaskModal()">
                <span class="material-icons-round">close</span>
            </button>
        </div>

        <form action="{{ route('user.tasks.store') }}" method="POST">
            @csrf

            <div class="form-group">
                <label class="form-label">عنوان المهمة *</label>
                <input type="text" name="title" class="form-input" placeholder="أدخل عنوان المهمة" required>
            </div>

            <div class="form-group">
                <label class="form-label">الوصف (اختياري)</label>
                <textarea name="description" class="form-input" rows="3" placeholder="وصف المهمة"></textarea>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">الأولوية</label>
                    <select name="priority" class="form-select">
                        <option value="low">منخفضة</option>
                        <option value="medium" selected>متوسطة</option>
                        <option value="high">عالية</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">تاريخ الاستحقاق</label>
                    <input type="date" name="due_date" class="form-input">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn--soft" onclick="closeAddTaskModal()">إلغاء</button>
                <button type="submit" class="btn btn--glow">إضافة المهمة</button>
            </div>
        </form>
    </div>
</div>

<style>
    .todo-checkbox-form {
        margin: 0;
    }

    .todo-checkbox-btn {
        width: 26px;
        height: 26px;
        border: none;
        background: transparent;
        padding: 0;
        cursor: pointer;
    }

    .todo-checkbox-btn .checkmark {
        display: block;
        width: 22px;
        height: 22px;
        border: 2px solid rgba(120, 130, 160, 0.35);
        border-radius: 50%;
        transition: 0.25s ease;
    }

    .todo-checkbox-btn:hover .checkmark {
        border-color: #22c55e;
        background: rgba(34, 197, 94, 0.12);
    }

    .todo-checkbox-btn:hover .checkmark::after {
        content: "✓";
        display: block;
        text-align: center;
        line-height: 18px;
        color: #22c55e;
        font-weight: 800;
        font-size: 13px;
    }

    .hero-progress-fill {
        transition: width 1.3s ease;
    }

    /* ✅ Quick Todo Footer */
    .quick-todo__footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }

    .quick-todo__note {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #6b7280;
    }

    .quick-todo__note a {
        color: #6a11cb;
        font-weight: 700;
        text-decoration: none;
    }

    .quick-todo__note a:hover {
        text-decoration: underline;
    }

    .quick-todo__note .material-icons-round {
        font-size: 16px;
        color: #6a11cb;
    }

    /* ✅ Modal Styles */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .modal-content {
        max-width: 500px;
        width: 95%;
        padding: 30px;
        border-radius: 24px;
        background: white;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 800;
        color: #17172f;
    }

    .modal-close {
        background: none;
        border: none;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: 0.2s;
    }

    .modal-close:hover {
        background: rgba(0,0,0,0.05);
    }

    .modal-close .material-icons-round {
        font-size: 24px;
        color: #6b7280;
    }

    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid rgba(0,0,0,0.05);
    }

    .btn--sm {
        padding: 6px 14px;
        font-size: 13px;
        border-radius: 12px;
    }

    @media (max-width: 640px) {
        .quick-todo__footer {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }

        .quick-todo__note {
            justify-content: center;
        }

        .form-row-2 {
            grid-template-columns: 1fr;
        }
    }
    
        .career-match__circle {
            width: 120px;
            height: 120px;
            position: relative;
            flex-shrink: 0;
        }

        .career-match__circle svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .career-match__circle-progress {
            transition: stroke-dashoffset 1.3s ease;
        }
</style>

<script>
    function openAddTaskModal() {
        document.getElementById('addTaskModal').style.display = 'flex';
    }

    function closeAddTaskModal() {
        document.getElementById('addTaskModal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const heroCounter = document.querySelector('.hero-progress-counter');
        const heroProgressFill = document.querySelector('.hero-progress-fill');

        if (heroProgressFill) {
            setTimeout(() => {
                heroProgressFill.style.width = heroProgressFill.getAttribute('data-width') || '0%';
            }, 300);
        }

        if (heroCounter) {
            const target = parseInt(heroCounter.getAttribute('data-target')) || 0;
            let current = 0;
            const increment = Math.max(target / 50, 1);

            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    heroCounter.innerText = Math.min(Math.ceil(current), target) + '%';
                    setTimeout(updateCounter, 20);
                } else {
                    heroCounter.innerText = target + '%';
                }
            };

            updateCounter();
        }

        // إغلاق المودال عند الضغط خارجها
        document.getElementById('addTaskModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddTaskModal();
            }
        });
    });
</script>

@endsection