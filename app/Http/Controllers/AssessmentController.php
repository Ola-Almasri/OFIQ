<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Assessment;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AssessmentController extends Controller
{
    // عرض صفحة التقييم
    public function index()
    {
        return view('assessment');
    }

    public function store(Request $request)
    {
        // 1. فحص كل البيانات مرة واحدة مع رسائل مخصصة
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'major'    => 'nullable|string',
            'gpa'      => 'nullable|numeric',
            'avatar_base64' => 'nullable|string',
        ], [
            'email.unique'   => 'هذا البريد الإلكتروني مسجل لدينا بالفعل، جرب تسجيل الدخول.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email'    => 'يرجى إدخال بريد إلكتروني صحيح.',
            'name.required'  => 'حقل الاسم مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
            'password.confirmed' => 'كلمة المرور غير متطابقة.',
            'password.min'    => 'كلمة المرور يجب أن لا تقل عن 8 محارف.',
        ]);

        // 2. حفظ الصورة إذا وجدت
        $avatarPath = null;
        if ($request->avatar_base64) {
            $avatarPath = $this->saveBase64Image($request->avatar_base64);
        }

        // 3. إنشاء المستخدم مع إضافة حقل avatar
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'avatar'   => $avatarPath,
        ]);

        // 4. إنشاء التقييم
        Assessment::create([
            'user_id'         => $user->id,
            'phone'           => $request->phone ?? null,
            'birth_date'      => $request->birth_date ?? null,
            'gender'          => $request->gender ?? null,
            'country'         => $request->country ?? null,
            'city'            => $request->city ?? null,
            'education_level' => $request->education_level ?? null,
            'major'           => $request->major ?? null,
            'university'      => $request->university ?? null,
            'gpa'             => $request->gpa ?? null,
            'grading_system'  => $request->grading_system ?? null,
            'graduation_year' => $request->graduation_year ?? null,
            'skills'          => $request->skills ?? null,
            'english_level'   => $request->english_level ?? null,
            'experience'      => $request->experience ?? null,
            'interests'       => $request->interests ?? null,
            'goal'            => $request->goal ?? null,
            'work_environment'=> $request->work_environment ?? null,
        ]);

        // 5. تسجيل الدخول والتوجه لصفحة اختيار المسار
        Auth::login($user);

        // ✅ الخطوة 6: توجيه المستخدم لصفحة اختيار المسار
        return redirect()->route('career.selection')->with('success', 'أهلاً بك في أفق! تم تحليل مهاراتك بنجاح.');
    }

    // دالة مساعدة لحفظ الصورة
    private function saveBase64Image($base64)
    {
        // استخراج البيانات من base64
        $imageData = explode(',', $base64);
        if (count($imageData) < 2) {
            return null;
        }
        
        $imageData = base64_decode($imageData[1]);
        $imageName = time() . '_' . uniqid() . '.png';
        $path = 'uploads/avatars/' . $imageName;
        
        // حفظ الملف في مجلد storage/app/public/uploads/avatars
        Storage::disk('public')->put($path, $imageData);
        
        return $path;
    }
}