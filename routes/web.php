<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\RoadmapController;
use App\Http\Controllers\DiagnosticAssessmentController;
use App\Http\Controllers\AiAdvisoryController;
use App\Http\Controllers\UserTaskController; 

// ==================== الصفحات العامة ====================
Route::get('/', function () {
    return view('welcome');
})->name('home');

// ==================== صفحات الضيف ====================
Route::middleware('guest')->group(function () {

    Route::get('/login', function () {
        return view('login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (auth()->attempt($credentials, $request->remember)) {
            $request->session()->regenerate();
            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'خطأ في البيانات',
        ])->onlyInput('email');
    })->name('login.post');

});

// ==================== التقييم ====================
Route::view('/assessment/start', 'assessment-start')->name('assessment.start');

Route::get('/assessment', [AssessmentController::class, 'index'])->name('assessment');
Route::post('/assessment', [AssessmentController::class, 'store'])->name('assessment.store');
    
// ============================================================
//  مسار عرض المسارات الجاهزة للتخصص المختار (بدون AI)
// ============================================================
Route::get('/explore-paths', [CareerController::class, 'showExplorePaths'])->name('explore.paths');

Route::post('/check-email', function (Request $request) {
    $exists = \App\Models\User::where('email', $request->email)->exists();
    return response()->json(['exists' => $exists]);
})->name('check.email');

Route::middleware('auth')->group(function () {

    // ==================== Dashboard ====================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ==================== Career Selection ====================
    Route::get('/career-selection', [CareerController::class, 'index'])->name('career.selection');
    Route::post('/career-selection', [CareerController::class, 'store'])->name('career.store');

    Route::get('/loading-to-diagnostic', function () {
        return view('loading-diagnostic');
    })->name('loading.diagnostic')->middleware('auth');

    // ==================== Diagnostic Assessment ====================
    Route::get('/diagnosticAssessment', [DiagnosticAssessmentController::class, 'show'])->name('diagnosticAssessment');
    Route::post('/diagnosticAssessment', [DiagnosticAssessmentController::class, 'submit'])->name('diagnosticAssessment.submit');
    Route::post('/roadmap/generate-after-diagnostic', [DiagnosticAssessmentController::class, 'generateRoadmapAfterDiagnostic'])->name('roadmap.generate.after.diagnostic');
        
    // ==================== Roadmap Pages ====================
    Route::get('/roadmap', [RoadmapController::class, 'index'])->name('roadmap');
    Route::get('/monthly-roadmap', [RoadmapController::class, 'monthly'])->name('monthly.roadmap');
    Route::patch('/roadmap-tasks/{task}/toggle', [RoadmapController::class, 'toggleTask'])->name('roadmap.tasks.toggle');

    // ==================== AI Roadmap ====================
    Route::post('/ai/roadmap/generate', [AiController::class, 'getOrGenerateRoadmap'])->name('ai.roadmap.generate');
    Route::get('/ai/roadmap', [AiController::class, 'getOrGenerateRoadmap'])->name('ai.roadmap');

    // ==================== مسارات AiAdvisoryController (الجديدة) ====================
    Route::post('/ai/analyze-skills', [AiAdvisoryController::class, 'analyzeSkillsWithAI'])->name('ai.analyze.skills');
    Route::post('/ai/generate-roadmap', [AiAdvisoryController::class, 'generateRoadmapWithAI'])->name('ai.generate.roadmap');
    Route::get('/ai/personalized-advice', [AiAdvisoryController::class, 'getPersonalizedAdvice'])->name('ai.personalized.advice');

    // ==================== User Tasks (مهام المستخدم) ====================
    Route::get('/user-tasks', [UserTaskController::class, 'index'])->name('user.tasks');
    Route::post('/user-tasks', [UserTaskController::class, 'store'])->name('user.tasks.store');
    Route::patch('/user-tasks/{task}/toggle', [UserTaskController::class, 'toggle'])->name('user.tasks.toggle');
    Route::delete('/user-tasks/{task}', [UserTaskController::class, 'destroy'])->name('user.tasks.destroy');

    // ==================== Focus ====================
    Route::get('/focus', function () {
        return view('focus');
    })->name('focus');

    // ==================== Profile ====================
    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/update-avatar', [ProfileController::class, 'updateAvatar'])->name('update.avatar');
    Route::post('/update-profile', [ProfileController::class, 'updateProfile'])->name('update.profile');
    
    // ==================== AI Checks ====================
    Route::get('/ai/groq', [AiController::class, 'checkGroq'])->name('ai.groq');
    Route::get('/ai/gemini', [AiController::class, 'checkGemini'])->name('ai.gemini');

    // ==================== Logout ====================
    Route::post('/logout', function (Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

// ==================== fallback ====================
// خليه معلق أثناء التطوير حتى الأخطاء تظهر بوضوح
// Route::fallback(function () {
//     return redirect('/');
// });