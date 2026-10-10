@extends('layouts.app')

@section('title', 'بؤرة التركيز - Ofuq AI')
@section('header_type', 'auth')
@section('body_class', 'focus-page')

@section('content')
<div class="focus" style="max-width: 95%; margin: 0 auto; padding: 0 20px;">
    <header class="focus-hero">
        <h1>منطقة التركيز الذكية</h1>
        <p style="color: #6b7c93;">ركز، تعلم، وأنتج مع تقنية البومودورو والأصوات الهادئة</p>
    </header>

    <div class="focus-main-layout">
        <!-- القسم الأول: وقت الدراسة -->
        <div class="focus-timer-section">
            <div class="focus-glass-card focus-timer-card">
                <div class="focus-status-label">
                    <span class="material-icons-round">schedule</span>
                    وقت الدراسة
                </div>

                <div class="focus-timer-display">
                    <svg class="focus-progress-ring" width="200" height="200">
                        <circle class="focus-progress-ring__circle" stroke="url(#grad)" stroke-width="8" fill="transparent" r="90" cx="100" cy="100" stroke-dasharray="565" stroke-dashoffset="0"/>
                    </svg>
                    <svg width="0" height="0">
                        <defs>
                            <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#6a11cb" />
                                <stop offset="100%" stop-color="#2575fc" />
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="focus-time-text" id="timerText">45:00</div>
                </div>

                <div class="focus-controls">
                    <button class="focus-btn focus-btn-primary" id="startBtn">
                        <span class="material-icons-round">play_arrow</span>
                        ابدأ
                    </button>
                    <button class="focus-btn focus-btn-secondary" id="pauseBtn">
                        <span class="material-icons-round">pause</span>
                        إيقاف
                    </button>
                    <button class="focus-btn focus-btn-outline" id="resetBtn">
                        <span class="material-icons-round">refresh</span>
                        إعادة
                    </button>
                </div>
            </div>
        </div>

        <!-- القسم الثاني: الأصوات الهادئة -->
        <div class="focus-music-section">
            <div class="focus-glass-card focus-music-card">
                <div class="focus-music-header">
                    <h3>
                        <span class="material-icons-round">headphones</span>
                        الأصوات الهادئة
                    </h3>
                    <span class="material-icons-round">graphic_eq</span>
                </div>

                <div class="focus-sounds-list-container" id="soundsListContainer">
                    <!-- القائمة تظهر هنا -->
                </div>

                <div class="focus-add-sound-area">
                    <input type="file" id="soundFileInput" class="focus-file-input" accept="audio/*">
                    <button class="focus-add-sound-btn" id="addSoundBtn">
                        <span class="material-icons-round">add</span>
                        إضافة صوت
                    </button>
                    <button class="focus-stop-all-btn" id="stopAllSoundsBtn">
                        <span class="material-icons-round">stop_circle</span>
                        إيقاف الكل
                    </button>
                </div>

                <div class="focus-wave-animation" id="waveAnimation">
                    <span></span><span></span><span></span><span></span>
                    <span></span><span></span><span></span><span></span>
                    <span></span><span></span><span></span><span></span>
                </div>
            </div>
        </div>

        <!-- القسم الثالث: إعدادات الوقت + تتبع الجلسات -->
        <div class="focus-tracker-section">
            <!-- مربع إعدادات الوقت -->
            <div class="focus-glass-card focus-settings-card" style="margin-bottom: 20px;">
                <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px; justify-content: center;">
                    <span class="material-icons-round">settings</span>
                    إعدادات الوقت
                </h3>
                <div class="focus-time-selector" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.3); padding: 8px 20px; border-radius: 50px;">
                        <span class="material-icons-round">schedule</span>
                        مدة الدراسة:
                        <select id="studyTimeSelect" class="focus-time-select">
                            <option value="25">25 دقيقة</option>
                            <option value="45" selected>45 دقيقة</option>
                            <option value="60">60 دقيقة</option>
                        </select>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.3); padding: 8px 20px; border-radius: 50px;">
                        <span class="material-icons-round">free_breakfast</span>
                        مدة الراحة:
                        <select id="breakTimeSelect" class="focus-time-select">
                            <option value="5">5 دقائق</option>
                            <option value="10">10 دقائق</option>
                            <option value="15" selected>15 دقيقة</option>
                            <option value="20">20 دقيقة</option>
                        </select>
                    </label>
                </div>
            </div>

            <!-- مربع تتبع الجلسات -->
            <div class="focus-glass-card focus-tracker-card">
                <div class="focus-tracker-header">
                    <h3>
                        <span class="material-icons-round">track_changes</span>
                        تتبع الجلسات
                    </h3>
                    <div class="focus-session-control">
                        <label>
                            <span class="material-icons-round">edit_note</span>
                            العدد:
                        </label>
                        <select class="focus-session-select" id="sessionSelect">
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4" selected>4</option>
                            <option value="6">6</option>
                            <option value="8">8</option>
                        </select>
                    </div>
                </div>

                <div class="focus-sessions-stats">
                    <span>الجلسات المكتملة:</span>
                    <span class="sessions-count" id="sessionsCountDisplay">0</span>
                    <span>/</span>
                    <span class="sessions-total" id="sessionsTotalDisplay">4</span>
                </div>

                <div class="focus-sessions-list" id="sessionsList"></div>
                
                <button class="focus-reset-sessions" id="resetSessionsBtn">
                    <span class="material-icons-round">restart_alt</span>
                    إعادة تعيين
                </button>
            </div>
        </div>
    </div>
</div>
    
@endsection