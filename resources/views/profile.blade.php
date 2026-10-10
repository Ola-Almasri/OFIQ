@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;

$user = Auth::user();
$assessment = $user->assessment;

$avatarPath = $user->avatar;
$timestamp = $user->updated_at ? $user->updated_at->timestamp : time();
$avatarUrl = ($avatarPath && Storage::disk('public')->exists($avatarPath))
    ? asset('storage/' . $avatarPath . '?v=' . $timestamp)
    : asset('assets/img/default-avatar.jpg');

$skills = $assessment->skills ?? '';
$skillsArray = array_filter(array_map('trim', explode(',', $skills)));

$interests = $assessment->interests ?? '';
$interestsArray = array_filter(array_map('trim', explode(',', $interests)));

$careerPaths = \App\Models\CareerPath::where('user_id', $user->id)
    ->orderBy('path_order')
    ->get();

$activeCareerPath = $careerPaths->firstWhere('status', 'active');

$activePathTasksCount = 0;
$activePathCompletedCount = 0;
$activePathProgress = 0;
$activePathSkillsCount = 0;

if ($activeCareerPath) {
    $activePathTasksCount = \App\Models\RoadmapTask::where('user_id', $user->id)
        ->where('career_path_id', $activeCareerPath->id)
        ->count();

    $activePathCompletedCount = \App\Models\RoadmapTask::where('user_id', $user->id)
        ->where('career_path_id', $activeCareerPath->id)
        ->where('status', 'completed')
        ->count();

    $activePathProgress = $activePathTasksCount > 0
        ? round(($activePathCompletedCount / $activePathTasksCount) * 100)
        : 0;

    $activePathSkills = is_array($activeCareerPath->required_skills)
        ? $activeCareerPath->required_skills
        : [];

    $activePathSkillsCount = count($activePathSkills);
}

$totalTasks = \App\Models\RoadmapTask::where('user_id', $user->id)->count();

$completedTasks = \App\Models\RoadmapTask::where('user_id', $user->id)
    ->where('status', 'completed')
    ->count();

$learningProgress = $activePathProgress;
@endphp

@section('title', 'الملف الشخصي - Ofuq AI')
@section('header_type', 'auth')
@section('body_class', 'profile-page')

@section('content')

<main class="page-content">
  <div class="container">

    <!-- Hero Section -->
    <section class="hero-profile">
      <div class="hero-card glass">
        <div class="hero-top">
          <button class="edit-btn" id="editProfileBtn" type="button">
            <span class="material-icons-round">edit</span>
            تعديل الملف
          </button>
        </div>

        <div class="profile-main">
          <div class="profile-image-wrap">
            <div class="profile-image" id="profileImageBox">
              <img id="studentImage" src="{{ $avatarUrl }}" alt="صورة الطالب">
            </div>

            <button class="camera-btn" type="button" id="uploadImageBtn">
              <span class="material-icons-round">camera_alt</span>
            </button>

            <input type="file" id="studentImageInput" accept="image/*" hidden>
          </div>

          <div class="profile-content">
            <span class="profile-label">الملف الشخصي</span>

            <h1 id="studentName">{{ $user->name }}</h1>

            <div class="profile-tags" id="studentTags">
              @forelse($skillsArray as $skill)
                <span class="tag">#{{ $skill }}</span>
              @empty
                <span class="tag">#لم تتم إضافة مهارات بعد</span>
              @endforelse
            </div>

            <p class="profile-desc" id="studentBio">
              {{ $assessment->bio ?? 'طالب في ' . ($assessment->major ?? 'مجال أكاديمي') . '، يسعى لتطوير مهاراته وبناء مساره المهني خطوة بخطوة.' }}
            </p>

            <div class="profile-goal">
              <span class="goal-dot"></span>
              <strong>الهدف الوظيفي:</strong>
              <span id="studentGoal">{{ $assessment->goal ?? 'تطوير المهارات وبناء مسار مهني واضح' }}</span>
            </div>

            @if($activeCareerPath)
              <div class="profile-goal" style="margin-top: 12px;">
                <span class="goal-dot"></span>
                <strong>المسار الحالي:</strong>
                <span>{{ $activeCareerPath->name }}</span>
              </div>
            @endif
          </div>
        </div>

        <div class="hero-stats">
          <div class="stat-box">
            <div class="stat-icon mint">
              <span class="material-icons-round">verified</span>
            </div>
            <div>
              <p>إنجاز المسار الحالي</p>
              <h3>{{ $activePathProgress }}%</h3>
            </div>
          </div>

          <div class="stat-box">
            <div class="stat-icon purple">
              <span class="material-icons-round">bolt</span>
            </div>
            <div>
              <p>مهارات المسار</p>
              <h3>{{ $activePathSkillsCount > 0 ? $activePathSkillsCount : count($skillsArray) }} مهارة</h3>
            </div>
          </div>

          <div class="stat-box">
            <div class="stat-icon orange">
              <span class="material-icons-round">local_fire_department</span>
            </div>
            <div>
              <p>المهام المنجزة</p>
              <h3>{{ $activePathCompletedCount }} / {{ $activePathTasksCount }}</h3>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Cards Grid (History + Badges) -->
    <section class="cards-grid">
      <div class="content-card glass">
        <div class="card-header">
          <h2>سجل المسارات</h2>
          <span class="material-icons-round card-icon">timeline</span>
        </div>

        <div class="history-list" id="historyList">
          @forelse($careerPaths as $path)
            @php
              $pathTasksCount = \App\Models\RoadmapTask::where('user_id', $user->id)
                  ->where('career_path_id', $path->id)
                  ->count();

              $pathCompletedCount = \App\Models\RoadmapTask::where('user_id', $user->id)
                  ->where('career_path_id', $path->id)
                  ->where('status', 'completed')
                  ->count();

              $pathProgress = $pathTasksCount > 0
                  ? round(($pathCompletedCount / $pathTasksCount) * 100)
                  : 0;

              $progressClass = $path->status === 'active'
                  ? 'teal'
                  : ($path->status === 'completed' ? 'purple' : 'blue');

              $pathStatusText = $path->status === 'active'
                  ? 'المسار الحالي'
                  : ($path->status === 'completed' ? 'مسار مكتمل' : 'مسار لاحق');
            @endphp

            <div class="history-item">
              <div class="history-progress">
                <span>{{ $path->name }}</span>

                <div class="progress-bar">
                  <div
                    class="progress-fill {{ $progressClass }}"
                    style="width: {{ $pathProgress }}% !important;">
                  </div>
                </div>
              </div>

              <div class="history-meta">
                <h3>{{ $pathStatusText }} - {{ $pathProgress }}%</h3>
                <p>نسبة التوافق: {{ $path->match_percentage ?? 0 }}%</p>
              </div>
            </div>
          @empty
            <div class="history-item">
              <div class="history-meta">
                <h3>لا توجد مسارات بعد</h3>
                <p>بعد إكمال التقييم ستظهر المسارات المقترحة هنا.</p>
              </div>
            </div>
          @endforelse
        </div>

        <button class="outline-btn" id="viewFullHistoryBtn" type="button">
          عرض السجل الكامل
        </button>
      </div>

      <div class="content-card glass">
        <div class="card-header">
          <h2>الأوسمة والإنجازات</h2>
          <span class="material-icons-round card-icon purple-text">workspace_premium</span>
        </div>

        <div class="badges-grid" id="badgesGrid">
          <div class="badge-card {{ $completedTasks >= 5 ? '' : 'disabled' }}">
            <div class="badge-icon">{{ $completedTasks >= 5 ? '⭐' : '🔒' }}</div>
            <p>متعلم نشط</p>
          </div>

          <div class="badge-card {{ $completedTasks >= 15 ? '' : 'disabled' }}">
            <div class="badge-icon">{{ $completedTasks >= 15 ? '🏆' : '🔒' }}</div>
            <p>بطل الالتزام</p>
          </div>

          <div class="badge-card {{ $learningProgress >= 100 ? '' : 'disabled' }}">
            <div class="badge-icon">{{ $learningProgress >= 100 ? '🎓' : '🔒' }}</div>
            <p>أنجزت الخطة</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Settings -->
    <section class="settings-card glass">
        <div class="settings-title">
            <h2>الإعدادات</h2>
            <p style="color: var(--muted); font-size: 14px; margin-top: 4px;">قم بتحديث معلوماتك الشخصية والأكاديمية</p>
        </div>

        <!-- ===== ترتيب عامودي ===== -->
        <div class="settings-vertical">

            <!-- 1️⃣ المهارات والاهتمامات (في الأعلى) -->
            <div class="setting-block">
                <h3>
                    <span class="material-icons-round">interests</span>
                    المهارات والاهتمامات
                </h3>

                <div class="tags-list" id="skillsList">
                    @forelse($skillsArray as $skill)
                        <span class="skill-tag">{{ $skill }}</span>
                    @empty
                        <span class="skill-tag" style="opacity: 0.6;">لا توجد مهارات بعد</span>
                    @endforelse

                    @foreach($interestsArray as $interest)
                        <span class="skill-tag">{{ $interest }}</span>
                    @endforeach

                    <button type="button" class="skill-tag add" id="addSkillBtn">
                        + إضافة
                    </button>
                </div>

                <p style="color: var(--muted); font-size: 12px; margin-top: 8px;">
                    <span class="material-icons-round" style="font-size: 14px; vertical-align: middle;">info</span>
                    أضف مهاراتك لإظهارها في ملفك الشخصي
                </p>
            </div>

            <!-- ===== الفاصل ===== -->
            <hr class="settings-divider-horizontal">

            <!-- 2️⃣ معلومات الحساب -->
            <div class="setting-block">
                <h3>
                    <span class="material-icons-round">person</span>
                    معلومات الحساب
                </h3>

                <div class="field-group">
                    <label>الاسم الكامل</label>
                    <input type="text" id="fullNameInput" value="{{ $user->name }}" placeholder="أدخل اسمك الكامل">
                </div>

                <div class="field-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" id="emailInput" value="{{ $user->email }}" readonly 
                          style="background: #f1f5f9; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0;">
                    <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">
                        <span class="material-icons-round" style="font-size: 14px; vertical-align: middle;">lock</span>
                        البريد الإلكتروني غير قابل للتعديل
                    </small>
                </div>
            </div>

            <!-- ===== الفاصل ===== -->
            <hr class="settings-divider-horizontal">

            <!-- 3️⃣ البيانات الأكاديمية -->
            <div class="setting-block">
                <h3>
                    <span class="material-icons-round">school</span>
                    البيانات الأكاديمية
                </h3>

                <div class="field-group">
                    <label>الجامعة</label>
                    <input type="text" id="universityInput" value="{{ $assessment->university ?? '' }}" placeholder="مثال: الجامعة الإسلامية">
                </div>

                <div class="field-group">
                    <label>التخصص</label>
                    <input type="text" id="majorInput" value="{{ $assessment->major ?? '' }}" placeholder="مثال: علوم الحاسوب">
                </div>

                <div class="field-group">
                    <label>المعدل التراكمي</label>
                    <input type="text" id="gpaInput" value="{{ $assessment->gpa ?? '' }}" placeholder="مثال: 3.5 من 4">
                </div>
            </div>

        </div>

        <!-- ✅ زر الحفظ -->
        <button class="save-btn" id="saveSettingsBtn" type="button">
            <span class="material-icons-round">save</span>
            حفظ التغييرات
        </button>
    </section>

  </div>
</main>

<style>
.profile-page .progress-bar {
  width: 100%;
  height: 9px;
  background: rgba(0, 0, 0, 0.08);
  border-radius: 999px;
  overflow: hidden;
}

.profile-page .progress-fill {
  display: block;
  height: 100%;
  border-radius: 999px;
  transition: width 0.35s ease;
  min-width: 0 !important;
}

.profile-page .progress-fill.teal {
  background: linear-gradient(90deg, #14b8a6, #2dd4bf);
}

.profile-page .progress-fill.purple {
  background: linear-gradient(90deg, #8b5cf6, #c084fc);
}

.profile-page .progress-fill.blue {
  background: linear-gradient(90deg, #3b82f6, #60a5fa);
}
</style>

@endsection