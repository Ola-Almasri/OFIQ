@extends('layouts.app')
@section('title', 'تقييم المهارات')
@section('header_type', 'simple')
@section('content')

@php
    $isExploreMode = request('source') === 'explore' && request()->filled('major');
    $selectedMajor = request('major');
@endphp

<!-- MAIN ASSESSMENT SECTION -->
<section class="assessment">
  <div class="signup-page">

    <!-- القسم الأيسر: العنوان + الخطوات + الصورة -->
    <div class="signup-left">
      
      <!-- العنوان -->
      <div class="signup-left-header">
        <h1>{{ $isExploreMode ? 'إكمال بياناتك' : 'إنشاء حساب جديد' }}</h1>

        <p>
          @if($isExploreMode)
            اخترت تخصص <strong>{{ $selectedMajor }}</strong>، نحتاج بياناتك الأكاديمية ومهاراتك لنبدأ التحليل.
          @else
            أكمل البيانات التالية لبدء رحلتك مع أفق
          @endif
        </p>
      </div>

      <!-- الخطوات -->
      <div class="progress-wrapper">
        <div class="progress-container">
          <div class="progress-steps">
            <div class="step-item active" data-step="0">
              <div class="step-circle">1</div>
              <span class="step-label">معلومات شخصية</span>
            </div>

            <div class="step-item" data-step="1">
              <div class="step-circle">2</div>
              <span class="step-label">معلومات أكاديمية</span>
            </div>

            <div class="step-item" data-step="2">
              <div class="step-circle">3</div>
              <span class="step-label">مهارات تقنية</span>
            </div>

            @if(!$isExploreMode)
              <div class="step-item" data-step="3">
                <div class="step-circle">4</div>
                <span class="step-label">اهتمامات شخصية</span>
              </div>
            @endif
          </div>

          <div class="progress-bar">
            <div class="progress-fill" style="width: {{ $isExploreMode ? '33%' : '25%' }}"></div>
          </div>
        </div>
      </div>
      
      <!-- الصورة تحت الخطوات -->
      <div class="signup-left-image">
        <img src="{{ asset('assets/img/login.png') }}" alt="Signup Illustration" />
      </div>
    </div>

    <!-- القسم الأيمن: الفورم -->
    <div class="signup-right">
      <div class="signup-form-container">

        <form
          id="assessmentForm"
          method="POST"
          action="{{ route('assessment.store') }}"
          data-ofuq-loading
          data-loading-title="نجهّز حسابك الذكي"
          data-loading-text="نحفظ بياناتك ونحلّلها لاختيار أفضل مسارات تناسبك."
        >
          @csrf

          <input type="hidden" name="avatar_base64" id="avatarBase64">

          @if($isExploreMode)
            <input type="hidden" name="assessment_mode" value="explore_major">
          @endif

          <!-- ✅ عرض الأخطاء -->
          @if ($errors->any())
            <div style="background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
              <ul>
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="assessment__content">

            <!-- Step 0: معلومات شخصية -->
            <div class="form-step active" id="step0">
              <div class="form-card">
                <div class="form-header-top">
                  <div class="form-card__header">
                    <span class="material-icons-round form-icon">person</span>
                    <h2>المعلومات الشخصية</h2>
                  </div>

                  <div class="avatar-upload avatar-upload--side">
                    <div class="avatar-preview" id="avatarPreview" onclick="document.getElementById('avatarInput').click()">
                      <span class="material-icons-round">person_add</span>
                    </div>
                    <input type="file" id="avatarInput" name="avatar" class="avatar-input" accept="image/*" />
                    <label for="avatarInput" class="avatar-label">إضافة صورة شخصية</label>
                  </div>
                </div>

                <!-- السطر الأول: الاسم الكامل - البريد الإلكتروني -->
                <div class="form-row-3">
                  <div class="form-group">
                    <label class="form-label">الاسم الكامل *</label>
                    <input type="text" name="name" class="form-input" placeholder="الاسم الثلاثي" required />
                  </div>

                  <div class="form-group">
                    <label class="form-label">البريد الإلكتروني *</label>
                    <input type="email" name="email" class="form-input" placeholder="example@ofuq.com" required />
                  </div>

                  <div class="form-group">
                    <label class="form-label">رقم الجوال</label>
                    <input type="tel" name="phone" class="form-input" placeholder="05XXXXXXXX" />
                  </div>
                </div>

                <!-- السطر الثاني: تاريخ الميلاد - الجنس - الدولة -->
                <div class="form-row-4">
                  <div class="form-group">
                    <label class="form-label">تاريخ الميلاد</label>
                    <input type="date" name="birth_date" class="form-input" />
                  </div>

                  <div class="form-group">
                    <label class="form-label">الجنس</label>
                    <select name="gender" class="form-select">
                      <option disabled selected>اختر</option>
                      <option>ذكر</option>
                      <option>أنثى</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">الدولة</label>
                    <select name="country" class="form-select">
                      <option disabled selected>الدولة</option>
                      <option>فلسطين</option>
                      <option>السعودية</option>
                      <option>الإمارات</option>
                      <option>الكويت</option>
                      <option>قطر</option>
                      <option>عمان</option>
                      <option>البحرين</option>
                      <option>الأردن</option>
                      <option>مصر</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">المدينة</label>
                    <input type="text" name="city" class="form-input" placeholder="المدينة" />
                  </div>
                </div>

                <!-- السطر الثالث: كلمة المرور - تأكيد كلمة المرور -->
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">كلمة المرور *</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required />
                  </div>

                  <div class="form-group">
                    <label class="form-label">تأكيد كلمة المرور *</label>
                    <input type="password" name="password_confirmation" class="form-input" placeholder="••••••••" required />
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 1: معلومات أكاديمية -->
            <div class="form-step" id="step1">
              <div class="form-card">
                <div class="form-card__header">
                  <span class="material-icons-round form-icon">school</span>
                  <h2>المعلومات الأكاديمية</h2>
                </div>

                @if($isExploreMode)
                  <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; font-weight: 700;">
                    التخصص المختار:
                    <strong>{{ $selectedMajor }}</strong>
                  </div>
                @endif

                <div class="form-row-3">
                  <div class="form-group">
                    <label class="form-label">المرحلة الدراسية</label>
                    <select name="education_level" class="form-select">
                      <option disabled selected>اختر</option>
                      <option>طالب ثانوية</option>
                      <option>سنة أولى جامعة</option>
                      <option>سنة ثانية جامعة</option>
                      <option>سنة ثالثة جامعة</option>
                      <option>سنة رابعة جامعة</option>
                      <option>خريج</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">التخصص</label>
                    <input
                      type="text"
                      name="major"
                      class="form-input"
                      placeholder="التخصص"
                      value="{{ old('major', request('major')) }}"
                      @if($isExploreMode) readonly @endif
                    />

                    @if($isExploreMode)
                      <small style="display:block; margin-top:8px; color:#0284c7; font-weight:700;">
                        تم اختيار هذا التخصص من صفحة الاستكشاف
                      </small>
                    @endif
                  </div>

                  <div class="form-group">
                    <label class="form-label">الجامعة</label>
                    <input type="text" name="university" class="form-input" placeholder="اسم الجامعة" />
                  </div>
                </div>

                <div class="form-row-3">
                  <div class="form-group">
                    <label class="form-label">المعدل التراكمي</label>
                    <input type="number" step="0.01" name="gpa" class="form-input" placeholder="3.5" />
                  </div>

                  <div class="form-group">
                    <label class="form-label">نظام التقييم</label>
                    <select name="grading_system" class="form-select">
                      <option>4 نقاط</option>
                      <option>5 نقاط</option>
                      <option>مئوي (100)</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">سنة التخرج</label>
                    <select name="graduation_year" class="form-select">
                      <option disabled selected>اختر السنة</option>
                      <option>2025</option>
                      <option>2026</option>
                      <option>2027</option>
                      <option>2028</option>
                      <option>2029+</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2: مهارات تقنية -->
            <div class="form-step" id="step2">
              <div class="form-card">
                <div class="form-card__header">
                  <span class="material-icons-round form-icon">code</span>
                  <h2>المهارات التقنية</h2>
                </div>

                @if($isExploreMode)
                  <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; font-weight: 700;">
                    التخصص المختار:
                    <strong>{{ $selectedMajor }}</strong>
                  </div>
                @endif

                <div class="form-group">
                  <label class="form-label">اختر مهاراتك الحالية</label>

                  <div class="tags-container" id="skillsContainer">
                    <div class="tag" data-selected="false" data-value="Python">Python</div>
                    <div class="tag" data-selected="false" data-value="JavaScript">JavaScript</div>
                    <div class="tag" data-selected="false" data-value="HTML/CSS">HTML/CSS</div>
                    <div class="tag" data-selected="false" data-value="React">React</div>
                    <div class="tag" data-selected="false" data-value="Node.js">Node.js</div>
                    <div class="tag" data-selected="false" data-value="Java">Java</div>
                    <div class="tag" data-selected="false" data-value="SQL">SQL</div>
                    <div class="tag" data-selected="false" data-value="Figma">Figma</div>
                  </div>

                  <!-- حقل إضافة مهارة مخصصة -->
                  <div class="custom-skill-add" style="margin-top: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="customSkillInput" class="form-input" placeholder="أضف مهارة أخرى..." style="flex: 1; min-width: 150px;">

                    <button type="button" id="addCustomSkillBtn" class="btn btn--soft" style="padding: 8px 20px;">
                      <span class="material-icons-round">add</span>
                      أضف مهارة
                    </button>
                  </div>

                  <input type="hidden" name="skills" id="skillsInput" value="">
                </div>

                <div class="form-group">
                  <label class="form-label">مستوى إجادة اللغات</label>

                  <div class="language-levels">
                    <div class="language-item">
                      <span>الإنجليزية</span>

                      <div class="level-buttons">
                        <button type="button" class="level-btn" data-level="beginner">مبتدئ</button>
                        <button type="button" class="level-btn" data-level="intermediate">متوسط</button>
                        <button type="button" class="level-btn active" data-level="advanced">متقدم</button>
                        <button type="button" class="level-btn" data-level="expert">خبير</button>
                      </div>
                    </div>
                  </div>

                  <input type="hidden" name="english_level" id="englishLevelInput" value="advanced">
                </div>

                <div class="form-group">
                  <label class="form-label">الخبرات السابقة</label>
                  <textarea name="experience" class="form-input" rows="3" placeholder="اذكر خبراتك السابقة في مجال التقنية..."></textarea>
                </div>
              </div>
            </div>

            @if(!$isExploreMode)
              <!-- Step 3: اهتمامات شخصية -->
              <div class="form-step" id="step3">
                <div class="form-card">
                  <div class="form-card__header">
                    <span class="material-icons-round form-icon">favorite</span>
                    <h2>الاهتمامات الشخصية</h2>
                  </div>

                  <div class="form-group">
                    <label class="form-label">المجالات التي تثير شغفك</label>

                    <div class="tags-container" id="interestsContainer">
                      <div class="tag" data-selected="false" data-value="AI">الذكاء الاصطناعي</div>
                      <div class="tag" data-selected="false" data-value="App Development">تطوير التطبيقات</div>
                      <div class="tag" data-selected="false" data-value="Cyber Security">أمن سيبراني</div>
                      <div class="tag" data-selected="false" data-value="Data Analysis">تحليل البيانات</div>
                      <div class="tag" data-selected="false" data-value="UX/UI">تصميم UX/UI</div>
                      <div class="tag" data-selected="false" data-value="Digital Marketing">تسويق إلكتروني</div>
                    </div>

                    <!-- حقل إضافة مجال اهتمام مخصص -->
                    <div class="custom-interest-add" style="margin-top: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                      <input type="text" id="customInterestInput" class="form-input" placeholder="أضف مجال اهتمام آخر..." style="flex: 1; min-width: 150px;">

                      <button type="button" id="addCustomInterestBtn" class="btn btn--soft" style="padding: 8px 20px;">
                        <span class="material-icons-round">add</span>
                        أضف اهتمام
                      </button>
                    </div>

                    <input type="hidden" name="interests" id="interestsInput" value="">
                  </div>

                  <div class="form-group">
                    <label class="form-label">الهدف من الانضمام</label>
                    <textarea name="goal" class="form-input" rows="3" placeholder="ماذا تطمح لتحقيقه من خلال منصة أفق؟"></textarea>
                  </div>

                  <div class="form-group">
                    <label class="form-label">بيئة العمل المفضلة</label>

                    <div class="radio-group">
                      <label class="radio-item">
                        <input type="radio" name="work_environment" value="remote" />
                        عن بُعد (Remote)
                      </label>

                      <label class="radio-item">
                        <input type="radio" name="work_environment" value="onsite" />
                        حضوري (On-site)
                      </label>

                      <label class="radio-item">
                        <input type="radio" name="work_environment" value="hybrid" />
                        هجين (Hybrid)
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            @endif

            <!-- Navigation Buttons -->
            <div class="form-navigation">
              <button type="button" class="btn btn--soft btn--lg" id="prevBtn" disabled>
                <span class="material-icons-round">arrow_forward</span>
                السابق
              </button>

              <button type="button" class="btn btn--glow btn--lg" id="nextBtn">
                التالي
                <span class="material-icons-round">arrow_back</span>
              </button>

              <button
                type="submit"
                class="btn btn--glow btn--lg"
                id="submitBtn"
                onclick="
                  const form = document.getElementById('assessmentForm');
                  if (form && form.checkValidity() && window.showOfuqLoader) {
                    window.showOfuqLoader(
                      'نجهّز حسابك الذكي',
                      'نحفظ بياناتك ونحلّلها لاختيار أفضل مسارات تناسبك.'
                    );
                  }
                "
              >
                {{ $isExploreMode ? 'إنشاء الحساب وتحليل التخصص' : 'إنشاء حساب' }}
                <span class="material-icons-round">person_add</span>
              </button>
            </div>

          </div>

        </form>
        <!-- نهاية الفورم -->

      </div>
    </div>

  </div>
</section>

@section('scripts')

<script>
  document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('assessmentForm');
      const steps = Array.from(document.querySelectorAll('.form-step'));
      const stepItems = Array.from(document.querySelectorAll('.step-item'));
      const progressFill = document.querySelector('.progress-fill');

      const prevBtn = document.getElementById('prevBtn');
      const nextBtn = document.getElementById('nextBtn');
      const submitBtn = document.getElementById('submitBtn');

      let currentStep = 0;

      function showStep(index) {
          if (!steps.length) return;

          currentStep = index;

          steps.forEach((step, i) => {
              step.classList.toggle('active', i === currentStep);
          });

          stepItems.forEach((item, i) => {
              item.classList.toggle('active', i === currentStep);
              item.classList.toggle('completed', i < currentStep);
          });

          if (progressFill) {
              const progress = ((currentStep + 1) / steps.length) * 100;
              progressFill.style.width = progress + '%';
          }

          if (prevBtn) {
              prevBtn.disabled = currentStep === 0;
          }

          const isLastStep = currentStep === steps.length - 1;

          if (nextBtn) {
              nextBtn.style.display = isLastStep ? 'none' : 'inline-flex';
          }

          if (submitBtn) {
              submitBtn.style.display = isLastStep ? 'inline-flex' : 'none';
          }
      }

      function validateCurrentStep() {
          const currentStepElement = steps[currentStep];
          if (!currentStepElement) return true;

          const requiredFields = Array.from(
              currentStepElement.querySelectorAll('input[required], select[required], textarea[required]')
          );

          for (const field of requiredFields) {
              if (!field.checkValidity()) {
                  field.reportValidity();
                  return false;
              }
          }

          return true;
      }

      if (nextBtn) {
          nextBtn.addEventListener('click', function () {
              if (!validateCurrentStep()) return;

              if (currentStep < steps.length - 1) {
                  showStep(currentStep + 1);
              }
          });
      }

      if (prevBtn) {
          prevBtn.addEventListener('click', function () {
              if (currentStep > 0) {
                  showStep(currentStep - 1);
              }
          });
      }

      if (form) {
          form.addEventListener('submit', function () {
              if (window.showOfuqLoader) {
                  window.showOfuqLoader(
                      'نجهّز حسابك الذكي',
                      'نحفظ بياناتك ونحلّلها لاختيار أفضل مسارات تناسبك.'
                  );
              }
          });
      }

      showStep(0);
  });
</script>
@endsection
