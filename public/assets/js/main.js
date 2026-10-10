// ============================================
// OFUQ AI - MAIN JAVASCRIPT FILE
// ============================================

console.log("🚀 JS WORKING - OFUQ AI");

document.addEventListener("DOMContentLoaded", function() {
    console.log("✅ DOM ready");
    
    // ===== YEAR =====
    const yearEl = document.getElementById("year");
    if (yearEl) yearEl.textContent = new Date().getFullYear();
    
    // ===== BURGER MENU =====
    const burgerBtn = document.getElementById("burgerBtn");
    const mobileMenu = document.getElementById("mobileMenu");
    if (burgerBtn && mobileMenu) {
        burgerBtn.addEventListener("click", function() {
            mobileMenu.classList.toggle("open");
            console.log("🍔 Burger toggled");
        });
    }
    
    // ===== SMOOTH SCROLL =====
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const navHeight = document.querySelector('.nav')?.offsetHeight || 0;
                const targetPosition = targetElement.offsetTop - navHeight;
                window.scrollTo({ top: targetPosition, behavior: 'smooth' });
            }
        });
    });
    
    // ===== ANIMATED COUNTERS =====
    const counters = document.querySelectorAll(".stat-number");
    if (counters.length) {
        counters.forEach(counter => {
            const target = Number(counter.dataset.target) || 0;
            const suffix = counter.dataset.suffix || "";
            let current = 0;
            const step = Math.ceil(target / 500);
            function update() {
                current += step;
                if (current < target) {
                    counter.textContent = current + suffix;
                    requestAnimationFrame(update);
                } else {
                    counter.textContent = target + suffix;
                }
            }
            update();
        });
    }
    
    // ============================================================
    // ✅ PROFILE PAGE FUNCTIONS (مدمجة بالكامل)
    // ============================================================

    // دالة ضغط الصورة
    function compressImage(file, callback) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const maxWidth = 400;
                const maxHeight = 400;
                let width = img.width;
                let height = img.height;
                
                if (width > height) {
                    if (width > maxWidth) {
                        height = (height * maxWidth) / width;
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = (width * maxHeight) / height;
                        height = maxHeight;
                    }
                }
                
                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);
                callback(canvas.toDataURL('image/jpeg', 0.85));
            };
        };
    }

    // ===== UPDATE AVATAR =====
    const uploadImageBtn = document.getElementById('uploadImageBtn');
    const studentImageInput = document.getElementById('studentImageInput');
    const studentImageEl = document.getElementById('studentImage');

    if (uploadImageBtn && studentImageInput) {
        uploadImageBtn.addEventListener('click', () => {
            studentImageInput.click();
        });
        
        studentImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('❌ يرجى اختيار ملف صورة فقط');
                    return;
                }
                
                compressImage(file, function(compressedBase64) {
                    // عرض الصورة مؤقتاً
                    if (studentImageEl) {
                        studentImageEl.src = compressedBase64;
                    }
                    
                    fetch('/update-avatar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ avatar: compressedBase64 })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // ✅ تحديث الصورة بالمسار الجديد من الخادم
                            if (studentImageEl && data.avatar) {
                                studentImageEl.src = data.avatar;
                                console.log('✅ تم تحديث الصورة:', data.avatar);
                            }
                            
                            // ✅ عرض رسالة نجاح
                            alert('✅ تم تحديث الصورة بنجاح!');
                            console.log('✅ تم تحديث الصورة بنجاح');
                        } else {
                            alert('❌ ' + (data.message || 'حدث خطأ'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('❌ حدث خطأ في الاتصال بالخادم');
                    });
                });
            }
        });
    }

    // ===== ADD SKILL =====
    const addSkillBtn = document.getElementById('addSkillBtn');
    if (addSkillBtn) {
        addSkillBtn.addEventListener('click', () => {
            const newSkill = prompt('أدخل اسم المهارة الجديدة:');
            if (newSkill && newSkill.trim()) {
                const skillSpan = document.createElement('span');
                skillSpan.className = 'skill-tag';
                skillSpan.innerText = newSkill;
                const skillsList = document.getElementById('skillsList');
                if (skillsList) skillsList.insertBefore(skillSpan, addSkillBtn);
            }
        });
    }

    // ===== VIEW FULL HISTORY =====
    const viewFullHistoryBtn = document.getElementById('viewFullHistoryBtn');
    if (viewFullHistoryBtn) {
        viewFullHistoryBtn.addEventListener('click', () => {
            alert('📋 سجل المسارات الكامل:\n\n1. مسار تطوير الويب - 75%\n2. مسار تحليل البيانات - 45%\n3. مسار الذكاء الاصطناعي - 30%\n4. مسار الأمن السيبراني - 0% (لم يبدأ)');
        });
    }

    // ============================================================
    // ✅ PROFILE - حفظ البيانات مباشرة
    // ============================================================

    const fullNameInput = document.getElementById('fullNameInput');
    const emailInput = document.getElementById('emailInput');
    const universityInput = document.getElementById('universityInput');
    const majorInput = document.getElementById('majorInput');
    const gpaInput = document.getElementById('gpaInput');
    const studentName = document.getElementById('studentName');
    const saveBtn = document.getElementById('saveSettingsBtn');

    // ===== ✅ البريد الإلكتروني يبقى readonly دائماً =====
    if (emailInput) {
        emailInput.readOnly = true;
        emailInput.style.background = '#f1f5f9';
        emailInput.style.color = '#94a3b8';
        emailInput.style.cursor = 'not-allowed';
        emailInput.style.borderColor = '#e2e8f0';
    }

    // ===== ✅ زر الحفظ - يحفظ البيانات مباشرة =====
    if (saveBtn) {
        saveBtn.style.display = 'flex';
        
        saveBtn.addEventListener('click', function() {
            // تعطيل الزر أثناء الحفظ
            this.disabled = true;
            this.innerHTML = '<span class="material-icons-round">hourglass_top</span> جاري الحفظ...';
            
            // تحديث الاسم في الواجهة
            if (fullNameInput && studentName) {
                studentName.innerText = fullNameInput.value;
            }
            
            // تجهيز البيانات
            const data = {
                name: fullNameInput?.value || '',
                university: universityInput?.value || '',
                major: majorInput?.value || '',
                gpa: gpaInput?.value || ''
            };
            
            console.log('📝 حفظ البيانات:', data);
            
            // ✅ إرسال إلى الخادم
            fetch('/update-profile', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                console.log('📥 رد الخادم:', result);
                
                if (result.success) {
                    // ✅ نجاح - تحديث الواجهة
                    alert('✅ تم حفظ التغييرات بنجاح!');
                    
                    // ✅ تحديث الاسم في الواجهة
                    if (fullNameInput && studentName) {
                        studentName.innerText = fullNameInput.value;
                    }
                    
                    // ✅ تحديث الصورة من الخادم
                    if (result.data?.avatar) {
                        const studentImage = document.getElementById('studentImage');
                        if (studentImage) {
                            studentImage.src = result.data.avatar + '?v=' + Date.now();
                        }
                    }
                    
                    // إعادة تفعيل الزر
                    this.disabled = false;
                    this.innerHTML = '<span class="material-icons-round">save</span> حفظ التغييرات';
                } else {
                    // ❌ فشل
                    alert('❌ ' + (result.message || 'حدث خطأ أثناء الحفظ'));
                    this.disabled = false;
                    this.innerHTML = '<span class="material-icons-round">save</span> حفظ التغييرات';
                }
            })
            .catch(error => {
                console.error('❌ خطأ:', error);
                alert('❌ حدث خطأ في الاتصال بالخادم');
                this.disabled = false;
                this.innerHTML = '<span class="material-icons-round">save</span> حفظ التغييرات';
            });
        });
    }

    // ===== ✅ إخفاء زر التعديل =====
    const editProfileBtn = document.getElementById('editProfileBtn');
    if (editProfileBtn) {
        editProfileBtn.style.display = 'none';
    }

    // ===== ASSESSMENT PAGE FUNCTIONS =====
    const steps = document.querySelectorAll('.step-item');
    const formSteps = document.querySelectorAll('.form-step');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const progressFill = document.querySelector('.progress-fill');
    
    let currentStep = 0;
    const totalSteps = 4;
    
    function showError(inputElement, message) {
        let existingError = inputElement.parentElement.querySelector('.error-message');
        if (existingError) existingError.remove();
        let errorSpan = document.createElement('span');
        errorSpan.className = 'error-message';
        errorSpan.style.cssText = 'color:#dc2626;font-size:12px;display:block;margin-top:5px';
        errorSpan.innerText = message;
        inputElement.style.borderColor = '#dc2626';
        inputElement.style.borderWidth = '2px';
        inputElement.parentElement.appendChild(errorSpan);
    }
    
    function clearError(inputElement) {
        let existingError = inputElement.parentElement.querySelector('.error-message');
        if (existingError) existingError.remove();
        inputElement.style.borderColor = '';
        inputElement.style.borderWidth = '';
    }
    
    function clearAllErrors() {
        document.querySelectorAll('.error-message').forEach(err => err.remove());
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.style.borderColor = '';
            field.style.borderWidth = '';
        });
    }
    
    function checkEmailExists(email, callback) {
        if (window.location.pathname === '/login') {
            callback(false);
            return;
        }
        
        fetch('/check-email', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => callback(data.exists))
        .catch(error => { console.error('Error:', error); callback(false); });
    }

    const emailInputAssessment = document.querySelector('input[name="email"]');
    let emailValid = false;
    if (emailInputAssessment) {
        emailInputAssessment.addEventListener('blur', function() {
            const email = this.value.trim();
            if (email) {
                checkEmailExists(email, (exists) => {
                    const existingError = this.parentElement.querySelector('.email-error-message');
                    if (existingError) existingError.remove();
                    if (exists) {
                        let errorSpan = document.createElement('span');
                        errorSpan.className = 'email-error-message error-message';
                        errorSpan.style.cssText = 'color:#dc2626;font-size:12px;display:block;margin-top:5px';
                        errorSpan.innerHTML = '⚠️ هذا البريد الإلكتروني مسجل بالفعل!';
                        this.parentElement.appendChild(errorSpan);
                        this.style.borderColor = '#dc2626';
                        emailValid = false;
                    } else {
                        this.style.borderColor = '#22c55e';
                        emailValid = true;
                    }
                });
            }
        });
    }
    
    function validateCurrentStep() {
        const currentStepDiv = document.getElementById(`step${currentStep}`);
        const requiredFields = currentStepDiv.querySelectorAll('input[required], select[required], textarea[required]');
        let allValid = true;
        requiredFields.forEach(field => clearError(field));
        for (let field of requiredFields) {
            if (!field.value.trim()) {
                const fieldName = field.previousElementSibling?.innerText || field.placeholder || 'هذا الحقل';
                showError(field, `✏️ ${fieldName} مطلوب`);
                allValid = false;
            }
        }
        if (currentStep === 0) {
            const emailField = document.querySelector('input[name="email"]');
            if (emailField && emailField.value.trim() && !emailValid) {
                showError(emailField, '⚠️ هذا البريد الإلكتروني مسجل بالفعل!');
                allValid = false;
            }
            const pwdField = document.querySelector('input[name="password"]');
            const pwdConfirmField = document.querySelector('input[name="password_confirmation"]');
            if (pwdField && pwdConfirmField && pwdField.value.trim() && pwdConfirmField.value.trim()) {
                if (pwdField.value !== pwdConfirmField.value) {
                    showError(pwdConfirmField, '🔒 كلمة المرور غير متطابقة!');
                    allValid = false;
                }
            }
        }
        return allValid;
    }
    
    const pwdField = document.querySelector('input[name="password"]');
    const pwdConfirmField = document.querySelector('input[name="password_confirmation"]');
    if (pwdField && pwdConfirmField) {
        function checkPasswordMatch() {
            if (pwdConfirmField.value.trim() !== '') {
                if (pwdField.value !== pwdConfirmField.value) {
                    showError(pwdConfirmField, '🔒 كلمة المرور غير متطابقة!');
                } else {
                    clearError(pwdConfirmField);
                }
            } else {
                clearError(pwdConfirmField);
            }
        }
        pwdField.addEventListener('input', checkPasswordMatch);
        pwdConfirmField.addEventListener('input', checkPasswordMatch);
    }
    
    function updateSteps() {
        steps.forEach((step, index) => {
            step.classList.remove('active', 'completed');
            if (index === currentStep) step.classList.add('active');
            else if (index < currentStep) step.classList.add('completed');
        });
        formSteps.forEach(step => step.classList.remove('active'));
        const activeStep = document.getElementById(`step${currentStep}`);
        if (activeStep) activeStep.classList.add('active');
        if (progressFill) progressFill.style.width = `${((currentStep + 1) / totalSteps) * 100}%`;
        if (prevBtn) prevBtn.disabled = currentStep === 0;
        if (nextBtn && submitBtn) {
            if (currentStep === totalSteps - 1) {
                nextBtn.style.display = 'none';
                submitBtn.style.display = 'inline-flex';
            } else {
                nextBtn.style.display = 'inline-flex';
                submitBtn.style.display = 'none';
            }
        }
    }
    
    if (steps.length) {
        steps.forEach((step, index) => {
            step.addEventListener('click', () => {
                if (index <= currentStep) {
                    currentStep = index;
                    updateSteps();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    alert('⚠️ يرجى إكمال الخطوات السابقة أولاً!');
                }
            });
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            clearAllErrors();
            if (!validateCurrentStep()) return;
            if (currentStep < totalSteps - 1) {
                currentStep++;
                updateSteps();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            if (currentStep > 0) {
                currentStep--;
                updateSteps();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }
    
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearAllErrors();
            if (!validateCurrentStep()) return;
            
            const avatarFile = document.getElementById('avatarInput')?.files[0];
            const hiddenInput = document.getElementById('avatarBase64');
            
            function submitForm() {
                let skills = [];
                document.querySelectorAll('.tag[data-selected="true"]').forEach(tag => {
                    skills.push(tag.textContent.trim());
                });
                const skillsInput = document.querySelector('input[name="skills"]');
                if (skillsInput) skillsInput.value = skills.join(', ');
                
                let interests = [];
                document.querySelectorAll('#interestsContainer .tag[data-selected="true"]').forEach(tag => {
                    interests.push(tag.textContent.trim());
                });
                const interestsInput = document.getElementById('interestsInput');
                if (interestsInput) interestsInput.value = interests.join(', ');
                
                const activeLevel = document.querySelector('.level-btn.active');
                if (activeLevel) {
                    const englishLevelInput = document.getElementById('englishLevelInput');
                    if (englishLevelInput) englishLevelInput.value = activeLevel.getAttribute('data-level') || activeLevel.textContent;
                }
                
                const form = submitBtn.closest('form');
                if (form) form.submit();
            }
            
            if (avatarFile && (!hiddenInput || !hiddenInput.value)) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    if (hiddenInput) hiddenInput.value = event.target.result;
                    submitForm();
                };
                reader.readAsDataURL(avatarFile);
            } else {
                submitForm();
            }
        });
    }
    
    document.addEventListener('input', function(e) {
        if (e.target.matches('input, select, textarea')) clearError(e.target);
    });
    
    updateSteps();
    
    // Avatar upload preview
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    avatarPreview.innerHTML = `<img src="${event.target.result}" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">`;
                    const hiddenInput = document.getElementById('avatarBase64');
                    if (hiddenInput) hiddenInput.value = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // ===== TAGS SYSTEM =====
    const tags = document.querySelectorAll('.tag');
    tags.forEach(tag => {
        tag.addEventListener('click', function() {
            const isSelected = this.getAttribute('data-selected') === 'true';
            this.setAttribute('data-selected', !isSelected);
            if (!isSelected) {
                this.style.background = 'var(--primary)';
                this.style.color = 'white';
            } else {
                this.style.background = '#f1f5f9';
                this.style.color = '#64748b';
            }
            this.style.transform = 'scale(0.95)';
            setTimeout(() => this.style.transform = 'scale(1)', 100);
        });
    });

    // ===== إضافة مهارة مخصصة =====
    const addCustomSkillBtn = document.getElementById('addCustomSkillBtn');
    const customSkillInput = document.getElementById('customSkillInput');

    if (addCustomSkillBtn && customSkillInput) {
        addCustomSkillBtn.addEventListener('click', function() {
            const newSkill = customSkillInput.value.trim();
            
            if (newSkill === '') {
                alert('❌ يرجى إدخال اسم المهارة');
                return;
            }
            
            const existingTags = document.querySelectorAll('#skillsContainer .tag');
            let skillExists = false;
            existingTags.forEach(tag => {
                if (tag.textContent.trim().toLowerCase() === newSkill.toLowerCase()) {
                    skillExists = true;
                }
            });
            
            if (skillExists) {
                alert('⚠️ هذه المهارة موجودة بالفعل في القائمة');
                return;
            }
            
            const newTag = document.createElement('div');
            newTag.className = 'tag';
            newTag.setAttribute('data-selected', 'true');
            newTag.setAttribute('data-value', newSkill);
            newTag.textContent = newSkill;
            newTag.style.background = 'var(--primary)';
            newTag.style.color = 'white';
            
            newTag.addEventListener('click', function() {
                const isSelected = this.getAttribute('data-selected') === 'true';
                this.setAttribute('data-selected', !isSelected);
                if (!isSelected) {
                    this.style.background = 'var(--primary)';
                    this.style.color = 'white';
                } else {
                    this.style.background = '#f1f5f9';
                    this.style.color = '#64748b';
                }
                this.style.transform = 'scale(0.95)';
                setTimeout(() => { this.style.transform = 'scale(1)'; }, 100);
            });
            
            const skillsContainer = document.getElementById('skillsContainer');
            skillsContainer.appendChild(newTag);
            newTag.click();
            customSkillInput.value = '';
            console.log(`✅ تم إضافة المهارة: ${newSkill}`);
        });
        
        customSkillInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCustomSkillBtn.click();
            }
        });
    }

    // ===== إضافة اهتمام مخصص =====
    const addCustomInterestBtn = document.getElementById('addCustomInterestBtn');
    const customInterestInput = document.getElementById('customInterestInput');

    if (addCustomInterestBtn && customInterestInput) {
        addCustomInterestBtn.addEventListener('click', function() {
            const newInterest = customInterestInput.value.trim();
            
            if (newInterest === '') {
                alert('❌ يرجى إدخال مجال الاهتمام');
                return;
            }
            
            const existingTags = document.querySelectorAll('#interestsContainer .tag');
            let interestExists = false;
            existingTags.forEach(tag => {
                if (tag.textContent.trim().toLowerCase() === newInterest.toLowerCase()) {
                    interestExists = true;
                }
            });
            
            if (interestExists) {
                alert('⚠️ هذا المجال موجود بالفعل في القائمة');
                return;
            }
            
            const newTag = document.createElement('div');
            newTag.className = 'tag';
            newTag.setAttribute('data-selected', 'true');
            newTag.setAttribute('data-value', newInterest);
            newTag.textContent = newInterest;
            newTag.style.background = 'var(--primary)';
            newTag.style.color = 'white';
            
            newTag.addEventListener('click', function() {
                const isSelected = this.getAttribute('data-selected') === 'true';
                this.setAttribute('data-selected', !isSelected);
                if (!isSelected) {
                    this.style.background = 'var(--primary)';
                    this.style.color = 'white';
                } else {
                    this.style.background = '#f1f5f9';
                    this.style.color = '#64748b';
                }
                this.style.transform = 'scale(0.95)';
                setTimeout(() => { this.style.transform = 'scale(1)'; }, 100);
            });
            
            const interestsContainer = document.getElementById('interestsContainer');
            interestsContainer.appendChild(newTag);
            newTag.click();
            customInterestInput.value = '';
            console.log(`✅ تم إضافة مجال الاهتمام: ${newInterest}`);
        });
        
        customInterestInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCustomInterestBtn.click();
            }
        });
    }
    
    // ===== LANGUAGE LEVEL BUTTONS =====
    const levelBtns = document.querySelectorAll('.level-btn');
    levelBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const parent = this.closest('.level-buttons');
            if (parent) {
                parent.querySelectorAll('.level-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // ===== FOCUS PAGE TIMER AND SOUNDS =====

    const defaultSounds = [
        { id: 'rain', name: 'صوت المطر', url: 'assets/audio/1.mp3' },
        { id: 'quran', name: 'تلاوة القرآن', url: 'assets/audio/2.mp3' }
    ];

    let studyTimeMinutes = 45;
    let breakTimeMinutes = 15;
    let timeLeft = studyTimeMinutes * 60;
    let timerId = null;
    let isRunning = false;
    let isBreak = false;
    let audioElement = null;
    let currentSoundId = null;

    const focusTimerText = document.getElementById('timerText');
    const focusStartBtn = document.getElementById('startBtn');
    const focusPauseBtn = document.getElementById('pauseBtn');
    const focusResetBtn = document.getElementById('resetBtn');
    const focusSoundsContainer = document.getElementById('soundsListContainer');
    const focusWaveAnimation = document.getElementById('waveAnimation');
    const focusStopAllBtn = document.getElementById('stopAllSoundsBtn');
    const focusStatusLabel = document.querySelector('.focus-status-label');

    const focusSessionSelect = document.getElementById('sessionSelect');
    const focusSessionsList = document.getElementById('sessionsList');
    const focusSessionsCountDisplay = document.getElementById('sessionsCountDisplay');
    const focusSessionsTotalDisplay = document.getElementById('sessionsTotalDisplay');
    const focusResetSessionsBtn = document.getElementById('resetSessionsBtn');

    const studyTimeSelect = document.getElementById('studyTimeSelect');
    const breakTimeSelect = document.getElementById('breakTimeSelect');

    let focusTotalSessions = parseInt(focusSessionSelect?.value) || 4;
    let focusCompletedSessions = parseInt(localStorage.getItem('focus_sessions_count')) || 0;

    function updateStudyTime() {
        if (studyTimeSelect) {
            studyTimeMinutes = parseInt(studyTimeSelect.value);
        }
    }

    function updateBreakTime() {
        if (breakTimeSelect) {
            breakTimeMinutes = parseInt(breakTimeSelect.value);
        }
    }

    function getCurrentTime() {
        return isBreak ? breakTimeMinutes * 60 : studyTimeMinutes * 60;
    }

    function resetTimeByMode() {
        timeLeft = getCurrentTime();
        updateTimerDisplay();
    }

    function updateStatusLabel() {
        if (!focusStatusLabel) return;
        if (isBreak) {
            focusStatusLabel.innerHTML = '<span class="material-icons-round">free_breakfast</span> وقت الراحة';
            focusStatusLabel.style.color = '#22c55e';
        } else {
            focusStatusLabel.innerHTML = '<span class="material-icons-round">schedule</span> وقت الدراسة';
            focusStatusLabel.style.color = '';
        }
    }

    function renderSessions() {
        if (!focusSessionsList) return;
        
        focusSessionsList.innerHTML = '';
        focusTotalSessions = parseInt(focusSessionSelect.value);
        
        if (focusSessionsCountDisplay) focusSessionsCountDisplay.textContent = focusCompletedSessions;
        if (focusSessionsTotalDisplay) focusSessionsTotalDisplay.textContent = focusTotalSessions;
        
        for (let i = 0; i < focusTotalSessions; i++) {
            const dot = document.createElement('div');
            dot.className = `focus-session-dot ${i < focusCompletedSessions ? 'active' : ''}`;
            focusSessionsList.appendChild(dot);
        }
    }

    function playSound(sound) {
        if (audioElement) {
            audioElement.pause();
            audioElement.currentTime = 0;
        }
        if (currentSoundId === sound.id) {
            currentSoundId = null;
            if (focusWaveAnimation) focusWaveAnimation.style.opacity = '0';
            renderSoundsList();
            return;
        }
        audioElement = new Audio(sound.url);
        audioElement.loop = true;
        audioElement.play().then(() => {
            currentSoundId = sound.id;
            if (focusWaveAnimation) focusWaveAnimation.style.opacity = '1';
            renderSoundsList();
        }).catch(err => console.log("خطأ في الصوت"));
    }

    function renderSoundsList() {
        if (!focusSoundsContainer) return;
        focusSoundsContainer.innerHTML = '';
        
        defaultSounds.forEach(sound => {
            const isActive = currentSoundId === sound.id;
            const item = document.createElement('div');
            item.className = `focus-sound-item ${isActive ? 'active' : ''}`;
            
            item.innerHTML = `
                <div class="focus-sound-info">
                    <span class="material-icons-round" style="color: #6a11cb; margin-left: 10px;">
                        ${sound.id === 'rain' ? 'water_drop' : 'auto_stories'}
                    </span>
                    <span class="focus-sound-name">${sound.name}</span>
                </div>
                <button class="focus-play-btn ${isActive ? 'playing' : ''}" type="button">
                    <span class="material-icons-round">${isActive ? 'pause' : 'play_arrow'}</span>
                </button>
            `;
            
            item.onclick = (e) => {
                e.preventDefault();
                playSound(sound);
            };
            focusSoundsContainer.appendChild(item);
        });
    }

    function updateTimerDisplay() {
        if (!focusTimerText) return;
        const mins = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        focusTimerText.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        
        const circle = document.querySelector('.focus-progress-ring__circle');
        if (circle) {
            let total = getCurrentTime();
            const offset = 565 - (timeLeft / total) * 565;
            circle.style.strokeDashoffset = offset;
        }
    }

    function startBreak() {
        isBreak = true;
        timeLeft = breakTimeMinutes * 60;
        updateTimerDisplay();
        updateStatusLabel();
        
        const circle = document.querySelector('.focus-progress-ring__circle');
        if (circle) {
            circle.setAttribute('stroke', '#22c55e');
        }
    }

    function startStudy() {
        isBreak = false;
        timeLeft = studyTimeMinutes * 60;
        updateTimerDisplay();
        updateStatusLabel();
        
        const circle = document.querySelector('.focus-progress-ring__circle');
        if (circle) {
            circle.setAttribute('stroke', 'url(#grad)');
        }
    }

    function timerComplete() {
        clearInterval(timerId);
        isRunning = false;
        
        if (isBreak) {
            alert("☕ انتهت الراحة! حان وقت الدراسة.");
            startStudy();
        } else {
            focusCompletedSessions++;
            localStorage.setItem('focus_sessions_count', focusCompletedSessions);
            renderSessions();
            
            if (focusCompletedSessions >= focusTotalSessions) {
                alert("🎉 مبروك! أكملت جميع الجلسات!");
                startStudy();
            } else {
                alert(`✅ انتهت الجلسة! حان وقت الراحة (${breakTimeMinutes} دقيقة).`);
                startBreak();
            }
        }
    }

    if (focusStartBtn) {
        focusStartBtn.onclick = function(e) {
            e.preventDefault();
            if (isRunning) return;
            
            isRunning = true;
            timerId = setInterval(() => {
                if (timeLeft > 0) {
                    timeLeft--;
                    updateTimerDisplay();
                } else {
                    timerComplete();
                }
            }, 1000);
        };
    }

    if (focusPauseBtn) {
        focusPauseBtn.onclick = (e) => { 
            e.preventDefault(); 
            clearInterval(timerId); 
            isRunning = false; 
        };
    }

    if (focusResetBtn) {
        focusResetBtn.onclick = (e) => { 
            e.preventDefault(); 
            clearInterval(timerId); 
            isRunning = false; 
            startStudy();
        };
    }

    if (focusStopAllBtn) {
        focusStopAllBtn.onclick = (e) => {
            e.preventDefault();
            if (audioElement) { 
                audioElement.pause(); 
                audioElement.currentTime = 0; 
            }
            currentSoundId = null;
            if (focusWaveAnimation) focusWaveAnimation.style.opacity = '0';
            renderSoundsList();
        };
    }

    if (focusSessionSelect) {
        focusSessionSelect.addEventListener('change', renderSessions);
    }

    if (focusResetSessionsBtn) {
        focusResetSessionsBtn.onclick = (e) => {
            e.preventDefault();
            focusCompletedSessions = 0;
            localStorage.setItem('focus_sessions_count', 0);
            renderSessions();
            startStudy();
        };
    }

    if (studyTimeSelect) {
        studyTimeSelect.addEventListener('change', function() {
            updateStudyTime();
            if (!isRunning && !isBreak) {
                timeLeft = studyTimeMinutes * 60;
                updateTimerDisplay();
            }
        });
    }

    if (breakTimeSelect) {
        breakTimeSelect.addEventListener('change', function() {
            updateBreakTime();
            if (!isRunning && isBreak) {
                timeLeft = breakTimeMinutes * 60;
                updateTimerDisplay();
            }
        });
    }

    updateStudyTime();
    updateBreakTime();
    startStudy();
    renderSoundsList();
    renderSessions();
    updateStatusLabel();

    // ============================================
    // ===== ROADMAP GENERATOR =====
    // ============================================
    
    const roadmapStepsContainer = document.getElementById('roadmapStepsContainer');
    if (roadmapStepsContainer) {
        fetch('/ai/roadmap')
            .then(response => response.json())
            .then(data => {
                console.log('Ofuq AI Roadmap Debug:', data);
                
                if (data.status === 'Success' || data.status === 'Fetched from Database' || data.status === 'Fallback') {
                    const roadmapData = data.data;
                    
                    const welcomeEl = document.getElementById('aiWelcomeMessage');
                    if (welcomeEl && roadmapData.welcome) {
                        welcomeEl.innerHTML = roadmapData.welcome;
                    }
                    
                    const adviceEl = document.getElementById('aiAdvice');
                    if (adviceEl && roadmapData.advice) {
                        adviceEl.innerHTML = roadmapData.advice;
                    }
                    
                    if (roadmapData.roadmap && Array.isArray(roadmapData.roadmap)) {
                        roadmapStepsContainer.innerHTML = '';
                        
                        roadmapData.roadmap.forEach((step, index) => {
                            const isObject = typeof step === 'object' && step !== null;
                            const title = isObject ? (step.title || "خطوة جديدة") : step;
                            const description = isObject ? (step.description || "خطوة مهمة في مسارك المهني") : "استمر في التقدم لتحقيق هدفك.";
                            const tags = isObject && Array.isArray(step.tags) ? step.tags : ["مهارة", "تطوير"];

                            const stepItem = document.createElement('div');
                            stepItem.className = 'roadmap-timeline-item';
                            
                            stepItem.innerHTML = `
                                <div class="roadmap-node roadmap-node--${(index % 4) + 1}">
                                    <span class="step-number">${index + 1}</span>
                                </div>
                                <div class="roadmap-glass-card roadmap-step-card">
                                    <div class="roadmap-step-header">
                                        <div class="roadmap-step-info">
                                            <h3>${title}</h3>
                                            <p>${description}</p>
                                        </div>
                                    </div>
                                    <div class="roadmap-tags">
                                        ${tags.map(tag => `<span class="roadmap-tag">${tag}</span>`).join('')}
                                    </div>
                                </div>
                            `;
                            roadmapStepsContainer.appendChild(stepItem);
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Ofuq AI Roadmap Error:', error);
                const adviceEl = document.getElementById('aiAdvice');
                if (adviceEl) {
                    adviceEl.innerHTML = "عذراً، واجهنا مشكلة في تحميل خطتك. يرجى المحاولة مرة أخرى لاحقاً.";
                }
            });
    }

    // ===== INTERSECTION OBSERVER =====
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.services .card, .about__box').forEach(el => {
        observer.observe(el);
    });

    // ===== FADE IN ELEMENTS =====
    const elements = [
        document.querySelector('.login-title'),
        document.querySelector('.login-subtitle'),
        document.querySelector('.login-form-card')
    ];

    elements.forEach((el, index) => {
        if (el) {
            setTimeout(() => {
                el.classList.add('fade-in-element');
            }, index * 200);
        }
    });

    // ===== DARK MODE TOGGLE =====
    const toggle = document.getElementById('darkModeToggle');
    const icon = document.getElementById('darkModeIcon');

    if (toggle && icon) {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            icon.textContent = 'light_mode';
        }
        
        toggle.addEventListener('click', function() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            
            if (currentTheme === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                icon.textContent = 'dark_mode';
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                icon.textContent = 'light_mode';
            }
        });
    }
});