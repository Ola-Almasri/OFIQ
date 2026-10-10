<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Assessment;
use App\Models\CareerPath;
use App\Models\RoadmapTask;           
use App\Models\RecommendedCourse;    
use App\Models\RoadmapOverview;       
use App\Http\Controllers\RoadmapController;


class CareerController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $assessment = Assessment::where('user_id', $user->id)->first();

        if (!$assessment) {
            return redirect()->route('assessment')
                ->with('error', 'يرجى إكمال بيانات التقييم أولاً.');
        }

        // لو المستخدم اختار مسار قبل هيك، لا ترجعيه لصفحة المسارات
        if ($assessment->selected_career) {
            $activeCareerPath = CareerPath::where('user_id', $user->id)
                ->where('status', 'active')
                ->first();

            if ($activeCareerPath) {
                return redirect()->route('diagnosticAssessment');
            }

            // إصلاح حالة غير متناسقة: selected_career موجود لكن لا يوجد active path
            $firstCareerPath = CareerPath::where('user_id', $user->id)
                ->orderBy('path_order')
                ->first();

            if ($firstCareerPath) {
                CareerPath::where('user_id', $user->id)->update([
                    'status' => 'locked',
                ]);

                $firstCareerPath->update([
                    'status' => 'active',
                ]);

                $assessment->selected_career = $firstCareerPath->name;
                $assessment->save();

                return redirect()->route('diagnosticAssessment');
            }

            $assessment->selected_career = null;
            $assessment->save();
        }

        $existingPaths = CareerPath::where('user_id', $user->id)
            ->orderBy('path_order')
            ->get();

        if ($existingPaths->count() > 0) {
            return view('career-selection', [
                'careerPaths' => $existingPaths
            ]);
        }

        $aiData = $this->generateCareerPathsFromAI($assessment);
        $paths = $aiData['career_paths'] ?? [];

        if (count($paths) < 3) {
            $paths = $this->fallbackCareerPaths($assessment);
        }

        foreach ($paths as $index => $path) {
            $order = $path['order'] ?? ($index + 1);

            CareerPath::create([
                'user_id' => $user->id,
                'assessment_id' => $assessment->id,
                'name' => $path['name'] ?? 'مسار مهني',
                'description' => $path['description'] ?? null,
                'reason' => $path['reason'] ?? null,
                'match_percentage' => $path['match_percentage'] ?? 70,
                'required_skills' => $path['required_skills'] ?? [],
                'path_order' => $order,
                'status' => $order == 1 ? 'active' : 'locked',
                'progress' => 0,
            ]);
        }

        $careerPaths = CareerPath::where('user_id', $user->id)
            ->orderBy('path_order')
            ->get();

        return view('career-selection', compact('careerPaths'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'career_path_id' => 'required|exists:career_paths,id',
        ]);

        $user = Auth::user();

        $assessment = Assessment::where('user_id', $user->id)->firstOrFail();

        $careerPath = CareerPath::where('id', $request->career_path_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // اقفلي كل المسارات أولًا
        CareerPath::where('user_id', $user->id)->update([
            'status' => 'locked',
        ]);

        // فعّلي المسار المختار
        CareerPath::where('id', $careerPath->id)
            ->where('user_id', $user->id)
            ->update([
                'status' => 'active',
            ]);

        $assessment->selected_career = $careerPath->name;
        $assessment->save();

        // ============================================================
        // ✅ 🔥 إضافة: توليد الخطة الشهرية والكورسات تلقائياً
        // ============================================================
        
        // 1. التحقق من وجود مهام (Roadmap Tasks)
        $tasksExist = RoadmapTask::where('user_id', $user->id)
            ->where('career_path_id', $careerPath->id)
            ->exists();

        if (!$tasksExist) {
            // استدعاء RoadmapController لتوليد الخطة
            $roadmapController = new \App\Http\Controllers\RoadmapController();
            $roadmapController->generateMonthlyRoadmap($careerPath);
        }

        // 2. التحقق من وجود كورسات (Recommended Courses)
        $coursesExist = RecommendedCourse::where('user_id', $user->id)
            ->where('career_path_id', $careerPath->id)
            ->exists();

        if (!$coursesExist) {
            $roadmapController = new \App\Http\Controllers\RoadmapController();
            $roadmapController->generateCourses($careerPath);
        }

        // 3. إنشاء Overview إذا لم يكن موجوداً
        $overviewExists = RoadmapOverview::where('user_id', $user->id)
            ->where('career_path_id', $careerPath->id)
            ->exists();

        if (!$overviewExists) {
            RoadmapOverview::create([
                'user_id' => $user->id,
                'career_path_id' => $careerPath->id,
                'title' => 'خطة ' . $careerPath->name,
                'summary' => 'خطة متكاملة لتطوير مهاراتك في مسار ' . $careerPath->name,
                'estimated_duration' => 'شهر واحد',
                'level' => 'مبتدئ',
            ]);
        }

        return redirect()->route('diagnosticAssessment')
            ->with('success', 'تم اختيار المسار وتجهيز خطتك بنجاح!');
    }

    private function generateCareerPathsFromGroq($prompt)
    {
        $apiKey = env('GROQ_API_KEY');

        if (!$apiKey) {
            throw new \Exception('GROQ_API_KEY is missing');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(60)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'أنت مستشار مهني ذكي. أرجع JSON صالح فقط بدون Markdown.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
        ]);

        if ($response->failed()) {
            throw new \Exception('Groq API failed: ' . $response->body());
        }

        $data = $response->json();
        $text = $data['choices'][0]['message']['content'] ?? '{}';

        return $this->decodeAiJson($text);
    }

    private function generateCareerPathsFromGemini($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            throw new \Exception('GEMINI_API_KEY is missing');
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey,
        ])->timeout(60)->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]
        );

        if ($response->failed()) {
            throw new \Exception('Gemini API failed: ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        return $this->decodeAiJson($text);
    }

    private function generateCareerPathsFromOpenAI($prompt)
    {
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new \Exception('OPENAI_API_KEY is missing');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => env('OPENAI_MODEL', 'gpt-4'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional career advisor. Return valid JSON only. No Markdown.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'temperature' => 0.4,
            'response_format' => [
                'type' => 'json_object',
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI API failed: ' . $response->body());
        }

        $text = $response->json()['choices'][0]['message']['content'] ?? '{}';

        return $this->decodeAiJson($text);
    }


    private function generateCareerPathsFromAI($assessment)
    {
        $prompt = "
        أنت مستشار مهني خبير في تحليل التخصصات الجامعية والمهارات والخبرات.

        **بيانات الطالب:**
        - التخصص: {$assessment->major}
        - المستوى التعليمي: {$assessment->education_level}
        - الجامعة: {$assessment->university}
        - المعدل التراكمي: {$assessment->gpa}
        - المهارات الحالية: {$assessment->skills}
        - الاهتمامات: {$assessment->interests}
        - الخبرات: {$assessment->experience}
        - الهدف المهني: {$assessment->goal}
        - بيئة العمل المفضلة: {$assessment->work_environment}
        - مستوى الإنجليزية: {$assessment->english_level}

        **المطلوب:**
        أنشئ 3 مسارات مهنية متصلة كرحلة تطويرية:
        - المسار الأول: الأقرب لملف الطالب حالياً
        - المسار الثاني: تطور طبيعي بعد المسار الأول
        - المسار الثالث: مسار مستقبلي متقدم

        **قواعد صارمة:**
        1. المسارات مرتبطة بتخصص الطالب فقط
        2. كل مسار يحتوي 4 مهارات تقنية/مهنية محددة
        3. المهارات ليست عامة (ممنوع: تواصل، قيادة، عمل جماعي)
        4. النسب من 55-95، تنازلياً (الفرق 3 درجات على الأقل)
        5. الأوصاف 25-35 كلمة، الأسباب 45-65 كلمة

        **JSON فقط، بدون أي نص خارجي:**

        {
            \"career_paths\": [
                {
                    \"order\": 1,
                    \"name\": \"المسمى الوظيفي الدقيق\",
                    \"match_percentage\": 85,
                    \"description\": \"وصف مختصر 25-35 كلمة\",
                    \"reason\": \"سبب الاختيار 45-65 كلمة يشرح التطابق\",
                    \"required_skills\": [
                        {\"name\": \"مهارة 1\", \"level\": \"Beginner\", \"reason\": \"سبب 12-25 كلمة\"},
                        {\"name\": \"مهارة 2\", \"level\": \"Intermediate\", \"reason\": \"سبب 12-25 كلمة\"},
                        {\"name\": \"مهارة 3\", \"level\": \"Advanced\", \"reason\": \"سبب 12-25 كلمة\"},
                        {\"name\": \"مهارة 4\", \"level\": \"Advanced\", \"reason\": \"سبب 12-25 كلمة\"}
                    ]
                }
            ]
        }
        ";

        try {
            return $this->generateCareerPathsFromOpenAI($prompt);
        } catch (\Exception $e) {
            try {
                return $this->generateCareerPathsFromGroq($prompt);
            } catch (\Exception $e) {
                try {
                    return $this->generateCareerPathsFromGemini($prompt);
                } catch (\Exception $e) {
                    return [
                        'career_paths' => $this->fallbackCareerPaths($assessment)
                    ];
                }
            }
        }
    }
/** 
    private function generateCareerPathsFromGemini($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            throw new \Exception('GEMINI_API_KEY is missing');
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey,
        ])->timeout(30)->post(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]
        );

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        return $this->decodeAiJson($text);
    }

    private function generateCareerPathsFromGroq($prompt)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'أنت مستشار مهني ذكي. أرجع JSON صالح فقط بدون Markdown.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
            ]);

            if ($response->failed()) {
                throw new \Exception($response->body());
            }

            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? '{}';

            return $this->decodeAiJson($text);

        } catch (\Exception $e) {
            // ✅ التعديل هنا
            return [
                'career_paths' => $this->fallbackCareerPaths(null)
            ];
        }
    }
        
    private function generateCareerPathsFromOpenAI($prompt)
    {
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new \Exception('OPENAI_API_KEY is missing');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => env('OPENAI_MODEL', 'gpt-4.1'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional career advisor. Return valid JSON only. No Markdown.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ],
            ],
            'temperature' => 0.4,
            'response_format' => [
                'type' => 'json_object',
            ],
        ]);

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        $text = $response->json()['choices'][0]['message']['content'] ?? '{}';

        return $this->decodeAiJson($text);
    }

*/
    private function decodeAiJson($text)
    {
        $cleanText = $this->cleanJsonText($text);
        $decoded = json_decode($cleanText, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON غير صالح: ' . json_last_error_msg());
        }

        return $decoded;
    }

    private function cleanJsonText($text)
    {
        $text = trim($text);
        $text = preg_replace('/```json|```/', '', $text);

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            throw new \Exception('لم يتم العثور على JSON');
        }

        return substr($text, $start, $end - $start + 1);
    }

    private function fallbackCareerPaths($assessment = null)
    {
        $major = mb_strtolower($assessment->major ?? '');

        if (str_contains($major, 'صيدلة') || str_contains($major, 'pharmacy')) {
            return [
                [
                    'order' => 1,
                    'name' => 'صيدلة المجتمع',
                    'match_percentage' => 82,
                    'description' => 'مسار يركز على صرف الأدوية وتثقيف المرضى وإدارة التعاملات اليومية داخل الصيدلية.',
                    'reason' => 'تم اختياره كبداية واقعية لطالب الصيدلة لأنه يرتبط مباشرة بالممارسة الدوائية والتواصل مع المرضى.',
                    'required_skills' => ['قراءة الوصفات الطبية', 'التثقيف الدوائي', 'إدارة المخزون الدوائي', 'التواصل مع المرضى'],
                ],
                [
                    'order' => 2,
                    'name' => 'الصيدلة السريرية',
                    'match_percentage' => 76,
                    'description' => 'مسار يركز على متابعة العلاج الدوائي وتحليل الحالات والتعاون مع الفريق الصحي.',
                    'reason' => 'يعد تطورًا منطقيًا بعد فهم أساسيات صيدلة المجتمع والتعامل العملي مع الأدوية والمرضى.',
                    'required_skills' => ['تحليل الحالات الدوائية', 'متابعة العلاج الدوائي', 'فهم التداخلات الدوائية', 'كتابة الملاحظات السريرية'],
                ],
                [
                    'order' => 3,
                    'name' => 'التيقظ الدوائي وسلامة الدواء',
                    'match_percentage' => 70,
                    'description' => 'مسار متخصص في رصد الآثار الجانبية وتحليل بلاغات السلامة وتحسين استخدام الدواء.',
                    'reason' => 'يمثل مرحلة أكثر تخصصًا بعد بناء معرفة دوائية جيدة وقدرة على تحليل الحالات والمخاطر العلاجية.',
                    'required_skills' => ['رصد الآثار الجانبية', 'تحليل بلاغات السلامة', 'كتابة تقارير دوائية', 'فهم أنظمة السلامة الدوائية'],
                ],
            ];
        }

        if (
            str_contains($major, 'حاسوب') ||
            str_contains($major, 'برمج') ||
            str_contains($major, 'تقنية') ||
            str_contains($major, 'it') ||
            str_contains($major, 'computer') ||
            str_contains($major, 'software')
        ) {
            return [
                [
                    'order' => 1,
                    'name' => 'تطوير تطبيقات الويب',
                    'match_percentage' => 82,
                    'description' => 'مسار يركز على بناء مواقع وتطبيقات ويب عملية باستخدام أساسيات الواجهة والمنطق البرمجي.',
                    'reason' => 'تم اختياره كبداية مناسبة لأنه يوفر مدخلًا عمليًا واضحًا لمجالات التقنية وتطوير المشاريع.',
                    'required_skills' => ['هيكلة صفحات الويب', 'تنسيق الواجهات', 'أساسيات البرمجة', 'حل المشكلات البرمجية'],
                ],
                [
                    'order' => 2,
                    'name' => 'تطوير تطبيقات متكاملة',
                    'match_percentage' => 76,
                    'description' => 'مسار يربط الواجهة الأمامية بالخلفية وقواعد البيانات لبناء تطبيقات أكثر اكتمالًا.',
                    'reason' => 'يعد تطورًا منطقيًا بعد تعلم أساسيات الويب وبناء واجهات قابلة للتفاعل.',
                    'required_skills' => ['قواعد البيانات', 'بناء واجهات برمجية', 'إدارة المستخدمين', 'تنظيم بنية المشروع'],
                ],
                [
                    'order' => 3,
                    'name' => 'هندسة البرمجيات',
                    'match_percentage' => 70,
                    'description' => 'مسار متقدم يركز على تصميم الأنظمة وتحسين جودة الكود وإدارة دورة حياة البرمجيات.',
                    'reason' => 'يمثل مرحلة أعلى بعد اكتساب خبرة عملية في بناء التطبيقات وفهم مكونات النظام.',
                    'required_skills' => ['تصميم الأنظمة', 'اختبار البرمجيات', 'تحسين جودة الكود', 'إدارة الإصدارات'],
                ],
            ];
        }

        if (
            str_contains($major, 'إدارة') ||
            str_contains($major, 'ادارة') ||
            str_contains($major, 'محاسبة') ||
            str_contains($major, 'تسويق') ||
            str_contains($major, 'مالية') ||
            str_contains($major, 'business') ||
            str_contains($major, 'accounting') ||
            str_contains($major, 'marketing')
        ) {
            return [
                [
                    'order' => 1,
                    'name' => 'تحليل الأعمال',
                    'match_percentage' => 82,
                    'description' => 'مسار يركز على فهم احتياجات المؤسسات وتحليل العمليات وتحويلها إلى حلول عملية.',
                    'reason' => 'تم اختياره كبداية مناسبة لأنه يجمع بين التفكير الإداري والتحليل العملي للمشكلات.',
                    'required_skills' => ['تحليل العمليات', 'كتابة المتطلبات', 'تحليل البيانات الأساسية', 'التواصل مع أصحاب المصلحة'],
                ],
                [
                    'order' => 2,
                    'name' => 'إدارة المشاريع',
                    'match_percentage' => 76,
                    'description' => 'مسار يركز على تخطيط المشاريع وتنظيم الفرق ومتابعة التنفيذ وتحقيق الأهداف.',
                    'reason' => 'يعد تطورًا منطقيًا بعد فهم احتياجات الأعمال وتحليل المشكلات داخل المؤسسات.',
                    'required_skills' => ['تخطيط المشاريع', 'إدارة الوقت', 'متابعة المخاطر', 'تنظيم فرق العمل'],
                ],
                [
                    'order' => 3,
                    'name' => 'استشارات الأعمال',
                    'match_percentage' => 70,
                    'description' => 'مسار متقدم يركز على تشخيص مشكلات المؤسسات واقتراح حلول استراتيجية قابلة للتنفيذ.',
                    'reason' => 'يمثل مرحلة أكثر تقدمًا بعد امتلاك مهارات التحليل وإدارة المشاريع وفهم بيئات العمل.',
                    'required_skills' => ['تحليل السوق', 'بناء التوصيات', 'إعداد التقارير الاستشارية', 'العرض والإقناع'],
                ],
            ];
        }

        return [
            [
                'order' => 1,
                'name' => 'مسار مهني تأسيسي مرتبط بالتخصص',
                'match_percentage' => 78,
                'description' => 'مسار يساعد الطالب على دخول مجال تخصصه تدريجيًا وبناء مهارات عملية أساسية.',
                'reason' => 'تم اختياره كبداية آمنة لأن بيانات الطالب تحتاج تحليلًا أعمق قبل تحديد مسار أكثر تخصصًا.',
                'required_skills' => ['فهم أساسيات التخصص', 'تحليل المشكلات', 'التواصل المهني', 'تنظيم التعلم'],
            ],
            [
                'order' => 2,
                'name' => 'مسار تطبيقي في مجال التخصص',
                'match_percentage' => 72,
                'description' => 'مسار يركز على تحويل المعرفة الأكاديمية إلى تطبيقات عملية ومخرجات قابلة للعرض.',
                'reason' => 'يعد مرحلة لاحقة مناسبة بعد بناء الأساسيات وفهم متطلبات المجال.',
                'required_skills' => ['التطبيق العملي', 'إعداد التقارير', 'استخدام أدوات المجال', 'تقييم النتائج'],
            ],
            [
                'order' => 3,
                'name' => 'مسار تخصصي متقدم',
                'match_percentage' => 66,
                'description' => 'مسار يركز على التعمق في جانب محدد من التخصص وبناء خبرة أكثر احترافية.',
                'reason' => 'يمثل مرحلة متقدمة بعد اكتساب أساس قوي وتجربة تطبيقية داخل المجال.',
                'required_skills' => ['البحث المتخصص', 'حل المشكلات المتقدمة', 'بناء مخرجات احترافية', 'التطوير المستمر'],
            ],
        ];
    }


}