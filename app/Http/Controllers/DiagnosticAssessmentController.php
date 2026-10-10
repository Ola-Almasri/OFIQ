<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Assessment;
use App\Models\CareerPath;
use App\Models\DiagnosticResult;
use App\Models\RoadmapOverview;
use App\Models\RoadmapTask;

class DiagnosticAssessmentController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        $assessment = Assessment::where('user_id', $user->id)->first();

        if (!$assessment) {
            return redirect()->route('assessment')
                ->with('error', 'يرجى إكمال بيانات التقييم أولاً.');
        }

        $careerPath = CareerPath::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$careerPath) {
            $careerPath = CareerPath::where('user_id', $user->id)
                ->orderBy('path_order')
                ->first();

            if (!$careerPath) {
                return redirect()->route('career.selection')
                    ->with('error', 'يرجى اختيار مسار مهني أولاً.');
            }

            CareerPath::where('user_id', $user->id)->update([
                'status' => 'locked',
            ]);

            $careerPath->update([
                'status' => 'active',
            ]);

            $assessment->selected_career = $careerPath->name;
            $assessment->save();
        }

        /*
            مهم:
            لا نولّد الأسئلة من AI هنا، لأن show() لازم تفتح الصفحة بسرعة.
            توليد AI داخل GET request يسبب بطء و Maximum execution time.
        */

            $questions = $this->generateDiagnosticQuestions($assessment, $careerPath);

        return view('diagnosticAssessment', [
            'assessment' => $assessment,
            'careerPath' => $careerPath,
            'questions' => $questions,
        ]);
    }

    public function submit(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'detailed_answers' => 'nullable|array',
        ]);

        $user = Auth::user();

        $assessment = Assessment::where('user_id', $user->id)->first();

        if (!$assessment) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى إكمال بيانات التقييم أولاً.',
            ], 422);
        }

        $careerPath = CareerPath::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$careerPath) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على المسار النشط.',
            ], 422);
        }

        $answers = $request->input('answers', []);
        $detailedAnswers = $request->input('detailed_answers', []);

        $totalScore = 0;
        $maxScore = 0;

        $unknownAnswersCount = 0;
        $correctAnswersCount = 0;
        $partialAnswersCount = 0;
        $wrongAnswersCount = 0;

        $weaknesses = [];
        $strengths = [];

        foreach ($detailedAnswers as $item) {
            $score = (int)($item['score'] ?? 0);
            $questionText = $item['question'] ?? 'سؤال غير محدد';

            $answer = $item['answer'] ?? null;

            if (is_array($answer)) {
                $answerText = $answer['text'] ?? '';
            } else {
                $answerText = $answer ?? '';
            }

            $totalScore += $score;
            $maxScore += 3;

            if ($answerText === 'لا أعرف الإجابة') {
                $unknownAnswersCount++;
                $weaknesses[] = $questionText;
                continue;
            }

            if ($score === 3) {
                $correctAnswersCount++;
                $strengths[] = $questionText;
            } elseif ($score === 2 || $score === 1) {
                $partialAnswersCount++;

                if ($score === 2) {
                    $strengths[] = $questionText;
                } else {
                    $weaknesses[] = $questionText;
                }
            } else {
                $wrongAnswersCount++;
                $weaknesses[] = $questionText;
            }
        }

        if ($maxScore === 0) {
            $maxScore = count($answers) * 3;
        }

        $percentage = $maxScore > 0
            ? round(($totalScore / $maxScore) * 100)
            : 0;

        if ($percentage < 45) {
            $level = 'مبتدئ';
        } elseif ($percentage < 75) {
            $level = 'متوسط';
        } else {
            $level = 'متقدم';
        }

        $diagnosticResult = DiagnosticResult::updateOrCreate(
            [
                'user_id' => $user->id,
                'career_path_id' => $careerPath->id,
            ],
            [
                'answers' => [
                    'selected' => $answers,
                    'detailed' => $detailedAnswers,
                ],
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'level' => $level,
                'unknown_answers_count' => $unknownAnswersCount,
                'strengths' => array_slice($strengths, 0, 5),
                'weaknesses' => array_slice($weaknesses, 0, 5),

                // نخليهم null بالبداية عشان الحفظ يكون سريع
                // وبعدين يتعبّوا من برومبت التحليل بالخلفية
                'analysis' => null,
                'recommendation' => null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ إجاباتك بنجاح.',
            'result' => [
                'diagnostic_result_id' => $diagnosticResult->id,
                'total_questions' => count($detailedAnswers),
                'correct_answers' => $correctAnswersCount,
                'partial_answers' => $partialAnswersCount,
                'wrong_answers' => $wrongAnswersCount,
                'unknown_answers' => $unknownAnswersCount,
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'percentage' => $percentage,
                'level' => $level,
            ],
            'redirect' => route('dashboard'),
        ]);
    }

    private function askAI($prompt)
    {
        // ✅ 1. حاول OpenAI أولاً
        try {
            $result = $this->askOpenAI($prompt);
            if ($result) return $result;
        } catch (\Exception $e) {
            \Log::warning('OpenAI failed: ' . $e->getMessage());
        }
        
        // ✅ 2. إذا فشل OpenAI، جرب Gemini
        try {
            $result = $this->askGemini($prompt);
            if ($result) return $result;
        } catch (\Exception $e) {
            \Log::warning('Gemini failed: ' . $e->getMessage());
        }
        
        // ✅ 3. إذا فشل Gemini، جرب Groq
        try {
            $result = $this->askGroq($prompt);
            if ($result) return $result;
        } catch (\Exception $e) {
            \Log::warning('Groq failed: ' . $e->getMessage());
        }
        
        // ✅ 4. إذا فشل الكل، ارجع null عشان يستخدم Fallback
        return null;
    }

    public function generateRoadmapAfterDiagnostic()
    {
        $user = Auth::user();

        $assessment = Assessment::where('user_id', $user->id)->first();

        if (!$assessment) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد تقييم للطالب.',
            ], 422);
        }

        $careerPath = CareerPath::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$careerPath) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد مسار نشط.',
            ], 422);
        }

        $diagnosticResult = DiagnosticResult::where('user_id', $user->id)
            ->where('career_path_id', $careerPath->id)
            ->latest()
            ->first();

        if (!$diagnosticResult) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد نتيجة اختبار.',
            ], 422);
        }

        try {
            // 1. توليد تحليل النتيجة إذا كان غير موجود
            if (!$diagnosticResult->analysis || !$diagnosticResult->recommendation) {
                $analysis = $this->generateDiagnosticAnalysis(
                    $assessment,
                    $careerPath,
                    $diagnosticResult->percentage,
                    $diagnosticResult->level,
                    $diagnosticResult->unknown_answers_count,
                    $diagnosticResult->strengths ?? [],
                    $diagnosticResult->weaknesses ?? []
                );

                $diagnosticResult->update([
                    'analysis' => $analysis['analysis'] ?? null,
                    'recommendation' => $analysis['recommendation'] ?? null,
                ]);

                $diagnosticResult->refresh();
            }

            // 2. توليد صفحة roadmap العامة
            $this->generateRoadmapOverview($assessment, $careerPath, $diagnosticResult);

            // 3. توليد صفحة monthly-roadmap التفصيلية
            $this->generateDetailedRoadmapTasks($assessment, $careerPath, $diagnosticResult);

            return response()->json([
                'success' => true,
                'message' => 'تم توليد التحليل والخطة بنجاح.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Background roadmap generation failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل توليد الخطة.',
            ], 500);
        }
    }
    private function fallbackOverviewStagesByPath($careerPath)
    {
        $careerText = mb_strtolower(
            ($careerPath->name ?? '') . ' ' .
            ($careerPath->description ?? '') . ' ' .
            (is_array($careerPath->required_skills)
                ? implode(' ', $careerPath->required_skills)
                : ($careerPath->required_skills ?? '')
            )
        );

        if (
            str_contains($careerText, 'front') ||
            str_contains($careerText, 'front-end') ||
            str_contains($careerText, 'frontend') ||
            str_contains($careerText, 'واجهة') ||
            str_contains($careerText, 'واجهات') ||
            str_contains($careerText, 'html') ||
            str_contains($careerText, 'css') ||
            str_contains($careerText, 'javascript') ||
            str_contains($careerText, 'react')
        ) {
            return [
                [
                    'title' => 'المسار الأول – مراجعة الأساسيات HTML, CSS, JavaScript',
                    'goal' => 'تثبيت أساسيات بناء صفحات الويب قبل الانتقال إلى الأدوات المتقدمة.',
                    'nodes' => [
                        [
                            'title' => 'العقدة 1: HTML',
                            'items' => [
                                'بنية الصفحة والوسوم الأساسية',
                                'الروابط والصور والقوائم',
                                'الجداول والنماذج',
                                'الوسوم الدلالية Semantic HTML',
                            ],
                        ],
                        [
                            'title' => 'العقدة 2: CSS',
                            'items' => [
                                'المحددات والألوان والخطوط',
                                'Box Model و Position',
                                'Flexbox و Grid',
                                'Media Queries والتجاوب',
                            ],
                        ],
                        [
                            'title' => 'العقدة 3: JavaScript',
                            'items' => [
                                'المتغيرات وأنواع البيانات',
                                'الشروط والحلقات والدوال',
                                'DOM و Events',
                                'LocalStorage و fetch API',
                            ],
                        ],
                    ],
                    'final_project' => 'To-Do List بإضافة وحذف وتمييز المهام وحفظها في LocalStorage.',
                    'estimated_duration' => '3 إلى 4 أسابيع',
                ],
                [
                    'title' => 'المسار الثاني – Git, GitHub و CSS Frameworks',
                    'goal' => 'تنظيم المشاريع ورفعها على GitHub وبناء واجهات أسرع باستخدام Tailwind.',
                    'nodes' => [
                        [
                            'title' => 'العقدة 1: Git و GitHub',
                            'items' => [
                                'git init, add, commit',
                                'push و pull',
                                'branch و merge',
                                'كتابة README احترافي',
                            ],
                        ],
                        [
                            'title' => 'العقدة 2: CSS متقدم',
                            'items' => [
                                'Flexbox متقدم',
                                'Grid متقدم',
                                'Responsive Layouts',
                            ],
                        ],
                        [
                            'title' => 'العقدة 3: Tailwind CSS',
                            'items' => [
                                'Utility classes',
                                'Responsive prefixes',
                                'Cards و Navbar',
                                'Landing Page',
                            ],
                        ],
                    ],
                    'final_project' => 'موقع متجاوب كامل باستخدام Tailwind ورفعه على GitHub.',
                    'estimated_duration' => '2 إلى 3 أسابيع',
                ],
                [
                    'title' => 'المسار الثالث – React.js',
                    'goal' => 'بناء تطبيقات Front-End ديناميكية وقابلة للتوسع.',
                    'nodes' => [
                        [
                            'title' => 'العقدة 1: أساسيات React',
                            'items' => [
                                'JSX',
                                'Components',
                                'Props',
                                'State و useState',
                            ],
                        ],
                        [
                            'title' => 'العقدة 2: React المتوسط',
                            'items' => [
                                'useEffect',
                                'Conditional Rendering',
                                'Forms',
                                'Lists',
                            ],
                        ],
                        [
                            'title' => 'العقدة 3: Router و API',
                            'items' => [
                                'React Router',
                                'fetch أو axios',
                                'صفحات متعددة',
                                'عرض بيانات من API',
                            ],
                        ],
                    ],
                    'final_project' => 'موقع متجر صغير أو تطبيق طقس باستخدام React و API.',
                    'estimated_duration' => '4 إلى 6 أسابيع',
                ],
                [
                    'title' => 'المسار الرابع – أدوات متقدمة وجودة الكود',
                    'goal' => 'رفع مستوى الكود والأداء وتجهيز الطالب لمشاريع احترافية.',
                    'nodes' => [
                        [
                            'title' => 'العقدة 1: أدوات المشروع',
                            'items' => [
                                'Vite',
                                'ESLint',
                                'Prettier',
                            ],
                        ],
                        [
                            'title' => 'العقدة 2: Testing و Performance',
                            'items' => [
                                'Jest Basics',
                                'React Testing Library',
                                'Lazy Loading',
                                'Code Splitting',
                            ],
                        ],
                        [
                            'title' => 'العقدة 3: TypeScript و Next.js',
                            'items' => [
                                'TypeScript Basics',
                                'Typing Props',
                                'Next.js Routing',
                                'SSR vs CSR',
                            ],
                        ],
                    ],
                    'final_project' => 'تطبيق كامل باستخدام React + TypeScript + Vite.',
                    'estimated_duration' => '3 إلى 4 أسابيع',
                ],
            ];
        }

        if (
            str_contains($careerText, 'صيدلة') ||
            str_contains($careerText, 'صيدلي') ||
            str_contains($careerText, 'pharmacy')
        ) {
            return [
                [
                    'title' => 'المسار الأول – أساسيات الممارسة الصيدلانية',
                    'goal' => 'بناء فهم قوي لدور الصيدلي وطريقة التعامل مع الأدوية والوصفات.',
                    'nodes' => [
                        [
                            'title' => 'العقدة 1: المصطلحات والأشكال الدوائية',
                            'items' => [
                                'الأقراص والكبسولات والشرابات',
                                'الجرعات وتركيز الدواء',
                                'طريقة قراءة اسم الدواء',
                            ],
                        ],
                        [
                            'title' => 'العقدة 2: قراءة الوصفات',
                            'items' => [
                                'اسم المريض والدواء',
                                'الجرعة والتكرار',
                                'مدة العلاج',
                            ],
                        ],
                    ],
                    'final_project' => 'تحليل وصفة طبية بسيطة وشرحها للمريض.',
                    'estimated_duration' => '2 إلى 3 أسابيع',
                ],
                [
                    'title' => 'المسار الثاني – الجرعات والتداخلات الدوائية',
                    'goal' => 'فهم الجرعات الشائعة والتنبيه للتداخلات والتحذيرات المهمة.',
                    'nodes' => [
                        [
                            'title' => 'العقدة 1: الجرعات',
                            'items' => [
                                'حساب الجرعة',
                                'تكرار الجرعة',
                                'الجرعات للأطفال وكبار السن',
                            ],
                        ],
                        [
                            'title' => 'العقدة 2: التداخلات والتحذيرات',
                            'items' => [
                                'التداخلات بين الأدوية',
                                'الحساسية الدوائية',
                                'الأعراض الجانبية',
                            ],
                        ],
                    ],
                    'final_project' => 'مراجعة حالة مريض لديه أكثر من دواء وتحديد التحذيرات.',
                    'estimated_duration' => '3 إلى 4 أسابيع',
                ],
                [
                    'title' => 'المسار الثالث – الإرشاد الدوائي والتواصل مع المرضى',
                    'goal' => 'تدريب الطالب على شرح الدواء للمريض بطريقة واضحة وآمنة.',
                    'nodes' => [
                        [
                            'title' => 'العقدة 1: مهارات الإرشاد',
                            'items' => [
                                'شرح طريقة الاستخدام',
                                'شرح وقت تناول الدواء',
                                'التعامل مع نسيان الجرعة',
                            ],
                        ],
                        [
                            'title' => 'العقدة 2: حالات صيدلية شائعة',
                            'items' => [
                                'الزكام والحساسية',
                                'السكري والضغط',
                                'الربو والمضادات الحيوية',
                            ],
                        ],
                    ],
                    'final_project' => 'محاكاة حوار صيدلي مع مريض حول دواء جديد.',
                    'estimated_duration' => '3 إلى 5 أسابيع',
                ],
            ];
        }

        return [
            [
                'title' => 'المسار الأول – أساسيات التخصص',
                'goal' => 'فهم المفاهيم الأساسية المطلوبة للبدء في المسار.',
                'nodes' => [
                    [
                        'title' => 'العقدة 1: المفاهيم الأساسية',
                        'items' => [
                            'مصطلحات المجال',
                            'الأدوات المستخدمة',
                            'أول تطبيق عملي بسيط',
                        ],
                    ],
                ],
                'final_project' => 'مخرج بسيط يثبت فهم الأساسيات.',
                'estimated_duration' => '2 إلى 4 أسابيع',
            ],
        ];
    }

    private function generateRoadmapOverview($assessment, $careerPath, $diagnosticResult)
    {
        $skills = is_array($careerPath->required_skills)
            ? implode(', ', $careerPath->required_skills)
            : $careerPath->required_skills;

        $strengths = is_array($diagnosticResult->strengths)
            ? implode(' | ', $diagnosticResult->strengths)
            : $diagnosticResult->strengths;

        $weaknesses = is_array($diagnosticResult->weaknesses)
            ? implode(' | ', $diagnosticResult->weaknesses)
            : $diagnosticResult->weaknesses;

        $prompt = "
        أنت مستشار تعليمي محترف لمنصة Ofuq.

        مهمتك إنشاء خريطة تعلم عامة فقط للمسار المهني النشط.
        هذه الخطة ستظهر في صفحة roadmap.blade.php.

        مهم جدًا:
        - لا تكتب خطة يومية.
        - لا تكتب أيام تدريب.
        - لا تكتب تفاصيل طويلة.
        - لا تكتب مهام يومية.
        - اكتب مسارات رئيسية فقط.
        - تحت كل مسار اكتب الهدف والعقد التعليمية باختصار.
        - كل عقدة تحتوي نقاط مختصرة.
        - اجعل الخطة احترافية ومناسبة للتخصص.
        - لا تستخدم عبارات عامة مثل: تعلم المجال، تطوير المهارات، تطبيق عملي.
        - اكتب بالعربية فقط، مع السماح بالمصطلحات التقنية الإنجليزية.

        بيانات الطالب:
        - التخصص الجامعي: {$assessment->major}
        - المستوى التعليمي: {$assessment->education_level}
        - المهارات الحالية: {$assessment->skills}
        - الاهتمامات: {$assessment->interests}
        - الهدف المهني: {$assessment->goal}
        - مستوى اللغة الإنجليزية: {$assessment->english_level}

        المسار النشط:
        - الاسم: {$careerPath->name}
        - الوصف: {$careerPath->description}
        - المهارات المطلوبة: {$skills}

        نتيجة الاختبار:
        - المستوى: {$diagnosticResult->level}
        - النسبة: {$diagnosticResult->percentage}%
        - عدد إجابات لا أعرف: {$diagnosticResult->unknown_answers_count}
        - نقاط القوة: {$strengths}
        - نقاط الضعف: {$weaknesses}

        إذا كان المسار Front-End Development:
        أنشئ 3 إلى 4 مسارات مثل:
        1. مراجعة الأساسيات HTML, CSS, JavaScript
        2. Git, GitHub و CSS Frameworks
        3. React.js
        4. أدوات متقدمة مثل Vite, ESLint, Testing, TypeScript, Next.js

        إذا كان المسار Pharmacy:
        أنشئ 4 إلى 5 مسارات مثل:
        1. أساسيات الممارسة الصيدلانية
        2. قراءة الوصفات والجرعات والتداخلات
        3. إرشاد المريض والتواصل الصيدلاني
        4. الأمراض الشائعة وأدوية OTC
        5. السلامة الدوائية والتدريب العملي

        إذا كان المسار Back-End:
        أنشئ 4 إلى 5 مسارات مثل:
        1. أساسيات الخوادم و HTTP
        2. قواعد البيانات و SQL
        3. APIs و Authentication
        4. Security و Validation
        5. مشروع Back-End تطبيقي

        صيغة الإخراج JSON فقط بدون Markdown:
        {
            \"title\": \"عنوان الخطة العامة\",
            \"summary\": \"ملخص مختصر وواضح\",
            \"estimated_duration\": \"مدة تقديرية واقعية\",
            \"level\": \"مستوى الطالب\",
            \"stages\": [
                {
                    \"title\": \"المسار الأول – اسم المسار\",
                    \"goal\": \"هدف المسار بجملة قصيرة\",
                    \"nodes\": [
                        {
                            \"title\": \"العقدة 1: اسم العقدة\",
                            \"items\": [
                                \"نقطة مختصرة\",
                                \"نقطة مختصرة\",
                                \"نقطة مختصرة\"
                            ]
                        }
                    ],
                    \"final_project\": \"مشروع أو مخرج ختامي مختصر\",
                    \"estimated_duration\": \"مدة المسار\"
                }
            ],
            \"skills\": [
                \"مهارة 1\",
                \"مهارة 2\",
                \"مهارة 3\"
            ],
            \"outcomes\": [
                \"مخرج 1\",
                \"مخرج 2\",
                \"مخرج 3\"
            ]
        }
        ";

        try {
            $data = $this->askAI($prompt);

            RoadmapOverview::updateOrCreate(
                [
                    'user_id' => $assessment->user_id,
                    'career_path_id' => $careerPath->id,
                ],
                [
                    'title' => $data['title'] ?? 'الخطة العامة للمسار',
                    'summary' => $data['summary'] ?? null,
                    'stages' => $data['stages'] ?? [],
                    'skills' => $data['skills'] ?? [],
                    'outcomes' => $data['outcomes'] ?? [],
                    'estimated_duration' => $data['estimated_duration'] ?? null,
                    'level' => $data['level'] ?? $diagnosticResult->level,
                ]
            );

            return true;
        } catch (\Exception $e) {
            \Log::error('Roadmap overview generation failed', [
                'error' => $e->getMessage(),
            ]);

            RoadmapOverview::updateOrCreate(
                [
                    'user_id' => $assessment->user_id,
                    'career_path_id' => $careerPath->id,
                ],
                [
                    'title' => 'الخطة العامة للمسار',
                    'summary' => 'خطة عامة مقسمة إلى مسارات رئيسية، يبدأ الطالب فيها من الأساسيات ثم ينتقل تدريجيًا إلى التطبيق والمشاريع.',
                    'stages' => $this->fallbackOverviewStagesByPath($careerPath),
                    'skills' => ['فهم الأساسيات', 'التطبيق العملي', 'حل المشكلات', 'بناء مشروع تطبيقي'],
                    'outcomes' => ['خريطة تعلم واضحة', 'مهارات عملية منظمة', 'مشروع أو مخرج نهائي'],
                    'estimated_duration' => '8 إلى 16 أسبوع',
                    'level' => $diagnosticResult->level,
                ]
            );

            return false;
        }
    }
    private function generateDiagnosticAnalysis($assessment, $careerPath, $percentage, $level, $unknownAnswersCount, $strengths, $weaknesses)
    {
        $strengthsText = implode(' | ', array_slice($strengths, 0, 5));
        $weaknessesText = implode(' | ', array_slice($weaknesses, 0, 5));

        $prompt = "
            You are an expert Arabic learning advisor for Ofuq AI.

            Analyze the student's diagnostic test result and return a short, useful Arabic analysis.

            Student profile:
            - Major: {$assessment->major}
            - Education level: {$assessment->education_level}
            - Current skills: {$assessment->skills}
            - Interests: {$assessment->interests}
            - Career goal: {$assessment->goal}
            - English level: {$assessment->english_level}

            Active career path:
            - Name: {$careerPath->name}
            - Description: {$careerPath->description}

            Diagnostic result:
            - Percentage: {$percentage}%
            - Level: {$level}
            - Unknown answers count: {$unknownAnswersCount}
            - Strength indicators: {$strengthsText}
            - Weakness indicators: {$weaknessesText}

            Requirements:
            - Write in Arabic only.
            - Be supportive, realistic, and specific.
            - Do not exaggerate.
            - Do not mention that this is generated by AI.
            - analysis should explain the student's current level.
            - recommendation should explain what the learning plan should focus on first.
            - Keep each field concise.

            Return valid JSON only:
            {
                \"analysis\": \"تحليل مختصر لمستوى الطالب\",
                \"recommendation\": \"توصية تعليمية واضحة لبداية الخطة\"
            }
        ";

        try {
            return $this->askAI($prompt);
        } catch (\Exception $e) {
            return [
                'analysis' => "نتيجتك تشير إلى أنك في مستوى {$level} في مسار {$careerPath->name}. سنبني الخطة القادمة حسب إجاباتك ونقاط الضعف الظاهرة.",
                'recommendation' => 'ابدأ بتقوية الأساسيات ثم انتقل تدريجيًا إلى التطبيقات العملية والمشاريع الصغيرة.',
            ];
        }
    }

    private function generateDetailedRoadmapTasks($assessment, $careerPath, $diagnosticResult)
    {
        $skills = is_array($careerPath->required_skills)
            ? implode(', ', $careerPath->required_skills)
            : $careerPath->required_skills;

        $strengths = is_array($diagnosticResult->strengths)
            ? implode(' | ', $diagnosticResult->strengths)
            : $diagnosticResult->strengths;

        $weaknesses = is_array($diagnosticResult->weaknesses)
            ? implode(' | ', $diagnosticResult->weaknesses)
            : $diagnosticResult->weaknesses;

        $careerText = mb_strtolower(
            ($careerPath->name ?? '') . ' ' .
            ($careerPath->description ?? '') . ' ' .
            ($skills ?? '')
        );

        $isPharmacy = str_contains($careerText, 'صيدلة') ||
            str_contains($careerText, 'صيدلي') ||
            str_contains($careerText, 'pharmacy') ||
            str_contains($careerText, 'دواء') ||
            str_contains($careerText, 'أدوية');

        $isFrontEnd = str_contains($careerText, 'front') ||
            str_contains($careerText, 'frontend') ||
            str_contains($careerText, 'front-end') ||
            str_contains($careerText, 'واجهة') ||
            str_contains($careerText, 'واجهات') ||
            str_contains($careerText, 'html') ||
            str_contains($careerText, 'css') ||
            str_contains($careerText, 'javascript') ||
            str_contains($careerText, 'react');

        $prompt = "
        أنت مصمم مناهج تعليمية محترف لمنصة Ofuq.

        مهمتك إنشاء خطة تعليمية تفصيلية يومية للمسار المهني النشط.
        هذه الخطة ستظهر في monthly-roadmap.blade.php.

        مهم جدًا:
        - هذه ليست صفحة المسارات العامة.
        - هذه خطة تفصيلية يومية.
        - لا تلتزم بعدد 4 أسابيع دائمًا.
        - حدد عدد الأشهر والأسابيع حسب التخصص ومستوى الطالب.
        - كل أسبوع له تركيز واضح.
        - كل أسبوع يحتوي 4 إلى 6 مهام.
        - اترك أيام راحة بعدم إنشاء مهمة لكل يوم.
        - كل مهمة يجب أن تحتوي موضوعًا واضحًا وتمرينًا صغيرًا.
        - لا تستخدم عبارات عامة مثل: تعلم المجال، تعرف على، اقرأ مقالات، استخدم محركات البحث.
        - task_type يجب أن يكون واحدًا فقط من:
        lesson, practice, quiz, project, review
        - duration يجب أن يكون واحدًا فقط من:
        30 دقيقة, 45 دقيقة, ساعة واحدة, ساعتان
        - اكتب بالعربية فقط، مع السماح بالمصطلحات التقنية.

        بيانات الطالب:
        - التخصص الجامعي: {$assessment->major}
        - المستوى التعليمي: {$assessment->education_level}
        - المهارات الحالية: {$assessment->skills}
        - الاهتمامات: {$assessment->interests}
        - الهدف المهني: {$assessment->goal}
        - مستوى اللغة الإنجليزية: {$assessment->english_level}

        المسار النشط:
        - الاسم: {$careerPath->name}
        - الوصف: {$careerPath->description}
        - المهارات المطلوبة: {$skills}

        نتيجة الاختبار:
        - المستوى: {$diagnosticResult->level}
        - النسبة: {$diagnosticResult->percentage}%
        - إجابات لا أعرف: {$diagnosticResult->unknown_answers_count}
        - نقاط القوة: {$strengths}
        - نقاط الضعف: {$weaknesses}

        إذا كان المسار Front-End:
        قسّم الخطة هكذا:
        - HTML بالتفصيل
        - CSS بالتفصيل
        - JavaScript بالتفصيل
        - DOM و Events
        - LocalStorage و API
        - Git و GitHub
        - Tailwind
        - React
        - مشروع نهائي

        إذا كان المسار Pharmacy:
        قسّم الخطة هكذا:
        - أساسيات الممارسة الصيدلانية
        - قراءة الوصفات
        - الجرعات وأشكال الدواء
        - التداخلات الدوائية
        - الأعراض الجانبية والتحذيرات
        - إرشاد المريض
        - OTC medications
        - السكري والضغط والربو
        - المضادات الحيوية
        - السلامة الدوائية
        - حالات عملية في الصيدلية
        كل مهمة صيدلة يجب أن تتضمن تمرينًا مثل:
        تحليل وصفة، حساب جرعة، اكتشاف تداخل، صياغة نصيحة للمريض، أو سيناريو صيدلي.

        صيغة الإخراج JSON فقط:
        {
            \"tasks\": [
                {
                    \"month_number\": 1,
                    \"week_number\": 1,
                    \"day_number\": 1,
                    \"title\": \"عنوان تفصيلي واضح\",
                    \"description\": \"شرح مختصر لما سيتعلمه الطالب وما سيطبقه في تمرين صغير\",
                    \"task_type\": \"lesson\",
                    \"duration\": \"45 دقيقة\"
                }
            ]
        }
        ";
        try {
            $data = $this->askAI($prompt);
            $tasks = $data['tasks'] ?? [];

            if (!is_array($tasks) || count($tasks) === 0) {
                $tasks = $this->fallbackRoadmapTasksByPath($careerPath, $diagnosticResult);
            }

            $allowedTypes = ['lesson', 'practice', 'quiz', 'project', 'review'];

            $allowedDurations = [
                '30 دقيقة',
                '45 دقيقة',
                'ساعة واحدة',
                'ساعتان',
            ];

            $badWords = [
                'اقرأ مقالات',
                'قراءة مقالات',
                'ويكيبيديا',
                'استخدم محركات البحث',
                'محركات البحث',
                'تعرف على المجال',
                'تعرفي على المجال',
                'تطوير المهارات',
                'تعلم المجال',
                'تطبيق ما تم تعلمه',
                'مراجعة تقدم',
                'مراجعة進قدم',
                '進',
            ];

            if ($isPharmacy) {
                $badWords = array_merge($badWords, [
                    'HTML',
                    'CSS',
                    'JavaScript',
                    'React',
                    'Tailwind',
                    'GitHub',
                    'DOM',
                    'Front-End',
                ]);
            }

            foreach ($tasks as $task) {
                $title = trim($task['title'] ?? '');
                $description = trim($task['description'] ?? '');
                $taskType = trim($task['task_type'] ?? '');
                $duration = trim($task['duration'] ?? '');

                if ($title === '' || $description === '') {
                    \Log::warning('Generated roadmap has empty title or description, using fallback', [
                        'task' => $task,
                    ]);

                    $tasks = $this->fallbackRoadmapTasksByPath($careerPath, $diagnosticResult);
                    break;
                }

                if (!in_array($taskType, $allowedTypes, true)) {
                    \Log::warning('Generated roadmap has invalid task_type, using fallback', [
                        'title' => $title,
                        'task_type' => $taskType,
                    ]);

                    $tasks = $this->fallbackRoadmapTasksByPath($careerPath, $diagnosticResult);
                    break;
                }

                if ($duration !== '' && !in_array($duration, $allowedDurations, true)) {
                    \Log::warning('Generated roadmap has invalid duration, using fallback', [
                        'title' => $title,
                        'duration' => $duration,
                    ]);

                    $tasks = $this->fallbackRoadmapTasksByPath($careerPath, $diagnosticResult);
                    break;
                }

                foreach ($badWords as $badWord) {
                    if (
                        str_contains($title, $badWord) ||
                        str_contains($description, $badWord)
                    ) {
                        \Log::warning('Generated roadmap contains bad wording, using fallback', [
                            'bad_word' => $badWord,
                            'title' => $title,
                            'description' => $description,
                        ]);

                        $tasks = $this->fallbackRoadmapTasksByPath($careerPath, $diagnosticResult);
                        break 2;
                    }
                }
            }

            if (
                $diagnosticResult->level === 'مبتدئ' &&
                count($tasks) < 40
            ) {
                \Log::warning('Generated beginner roadmap is too short, using fallback', [
                    'count' => count($tasks),
                ]);

                $tasks = $this->fallbackRoadmapTasksByPath($careerPath, $diagnosticResult);
            }
        } catch (\Exception $e) {
            \Log::error('Detailed roadmap tasks generation failed', [
                'error' => $e->getMessage(),
            ]);

            $tasks = $this->fallbackRoadmapTasksByPath($careerPath, $diagnosticResult);
        }

        RoadmapTask::where('user_id', $assessment->user_id)
            ->where('career_path_id', $careerPath->id)
            ->delete();

        foreach ($tasks as $task) {
            $taskType = $task['task_type'] ?? 'lesson';

            if (!in_array($taskType, ['lesson', 'practice', 'quiz', 'project', 'review'], true)) {
                $taskType = 'lesson';
            }

            $duration = $task['duration'] ?? '45 دقيقة';

            if (!in_array($duration, ['30 دقيقة', '45 دقيقة', 'ساعة واحدة', 'ساعتان'], true)) {
                $duration = '45 دقيقة';
            }

            RoadmapTask::create([
                'user_id' => $assessment->user_id,
                'career_path_id' => $careerPath->id,
                'month_number' => (int)($task['month_number'] ?? 1),
                'week_number' => (int)($task['week_number'] ?? 1),
                'day_number' => isset($task['day_number']) ? (int)$task['day_number'] : null,
                'title' => $task['title'] ?? 'مهمة تعليمية',
                'description' => $task['description'] ?? null,
                'task_type' => $taskType,
                'duration' => $duration,
                'status' => 'pending',
            ]);
        }

        return true;
    }

    private function fallbackRoadmapTasks($careerPath, $diagnosticResult)
    {
        return [
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 1,
                'title' => 'HTML: إنشاء بنية صفحة باستخدام html و head و body',
                'description' => 'تعرّفي على الهيكل الأساسي لأي صفحة ويب، وافهمي وظيفة html و head و body من خلال ملف بسيط.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 2,
                'title' => 'HTML: استخدام العناوين والفقرات داخل الصفحة',
                'description' => 'تدرّبي على استخدام h1 إلى h6 و p لبناء محتوى نصي مرتب داخل صفحة تعريفية.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 3,
                'title' => 'HTML: إضافة الروابط والصور',
                'description' => 'تعلمي استخدام a للروابط و img للصور، وطبقي صفحة تحتوي على صورة شخصية وروابط مهمة.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 4,
                'title' => 'HTML: بناء القوائم المرتبة وغير المرتبة',
                'description' => 'استخدمي ul و ol و li لعرض المهارات أو الخطوات داخل صفحة منظمة.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 5,
                'title' => 'Practice: بناء صفحة تعريفية بسيطة باستخدام HTML',
                'description' => 'ابني صفحة تحتوي على عنوان وفقرة وصورة وروابط وقائمة مهارات بدون CSS.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 6,
                'title' => 'Quiz: اختبار قصير على بنية HTML والعناصر الأساسية',
                'description' => 'راجعي وظيفة أهم عناصر HTML واختبري قدرتك على اختيار العنصر المناسب لكل حالة.',
                'task_type' => 'quiz',
                'duration' => '30 دقيقة',
            ],

            [
                'month_number' => 1,
                'week_number' => 2,
                'day_number' => 1,
                'title' => 'HTML: إنشاء الجداول باستخدام table و tr و td',
                'description' => 'تعلمي بناء جدول بسيط لعرض بيانات مثل جدول مواد أو قائمة أسعار.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 1,
                'week_number' => 2,
                'day_number' => 2,
                'title' => 'HTML: إنشاء نموذج باستخدام form',
                'description' => 'افهمي دور form في جمع البيانات، وجهزي نموذج تواصل بسيط.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 1,
                'week_number' => 2,
                'day_number' => 3,
                'title' => 'HTML: استخدام input و label بطريقة صحيحة',
                'description' => 'طبقي حقول إدخال للاسم والبريد وكلمة المرور مع labels واضحة.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 1,
                'week_number' => 2,
                'day_number' => 4,
                'title' => 'HTML: إضافة الأزرار وأنواع حقول الإدخال',
                'description' => 'تعرّفي على button و input types مثل email و password و date و checkbox.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 1,
                'week_number' => 2,
                'day_number' => 5,
                'title' => 'Project: بناء نموذج تسجيل بسيط باستخدام HTML',
                'description' => 'ابني نموذج تسجيل يحتوي على الاسم والبريد وكلمة المرور وزر إرسال.',
                'task_type' => 'project',
                'duration' => 'ساعتان',
            ],

            [
                'month_number' => 2,
                'week_number' => 1,
                'day_number' => 1,
                'title' => 'CSS: ربط ملف CSS بصفحة HTML',
                'description' => 'تعلمي طريقة إنشاء ملف CSS خارجي وربطه بالصفحة باستخدام link داخل head.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 2,
                'week_number' => 1,
                'day_number' => 2,
                'title' => 'CSS: استخدام selectors لتحديد العناصر',
                'description' => 'تدرّبي على تحديد العناصر باستخدام اسم الوسم والكلاس والآي دي.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 2,
                'week_number' => 1,
                'day_number' => 3,
                'title' => 'CSS: تنسيق الألوان والخلفيات',
                'description' => 'طبقي ألوان النصوص والخلفيات على صفحة HTML سابقة.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 2,
                'week_number' => 1,
                'day_number' => 4,
                'title' => 'CSS: تنسيق الخطوط وأحجام النصوص',
                'description' => 'تعلمي font-size و font-weight و line-height لتحسين قراءة الصفحة.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 2,
                'week_number' => 1,
                'day_number' => 5,
                'title' => 'Practice: تحسين شكل صفحة HTML باستخدام CSS',
                'description' => 'أضيفي ألوان وخطوط وتنسيق بسيط لصفحة التعريف الشخصية.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],

            [
                'month_number' => 2,
                'week_number' => 2,
                'day_number' => 1,
                'title' => 'CSS: فهم Box Model',
                'description' => 'افهمي الفرق بين content و padding و border و margin من خلال أمثلة مرئية.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 2,
                'week_number' => 2,
                'day_number' => 2,
                'title' => 'CSS: تطبيق margin و padding',
                'description' => 'طبقي المسافات الداخلية والخارجية بين البطاقات والعناصر.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 2,
                'week_number' => 2,
                'day_number' => 3,
                'title' => 'CSS: إنشاء بطاقات بتنسيق border و border-radius',
                'description' => 'ابني بطاقة بسيطة لها حدود وحواف دائرية وظل بسيط.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 2,
                'week_number' => 2,
                'day_number' => 4,
                'title' => 'CSS: بداية Flexbox وترتيب العناصر أفقيًا',
                'description' => 'استخدمي display flex لترتيب عناصر القائمة أو البطاقات بجانب بعضها.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 2,
                'week_number' => 2,
                'day_number' => 5,
                'title' => 'Project: بناء قسم بطاقات باستخدام Flexbox',
                'description' => 'ابني 3 بطاقات متجاورة تحتوي على أيقونة وعنوان ووصف قصير.',
                'task_type' => 'project',
                'duration' => 'ساعتان',
            ],

            [
                'month_number' => 3,
                'week_number' => 1,
                'day_number' => 1,
                'title' => 'JavaScript: إنشاء المتغيرات باستخدام let و const',
                'description' => 'تعلمي الفرق بين let و const واستخدميهما لتخزين نصوص وأرقام.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 3,
                'week_number' => 1,
                'day_number' => 2,
                'title' => 'JavaScript: فهم أنواع البيانات الأساسية',
                'description' => 'تعرفي على string و number و boolean واستخدميها في أمثلة بسيطة.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 3,
                'week_number' => 1,
                'day_number' => 3,
                'title' => 'JavaScript: استخدام الشروط if و else',
                'description' => 'طبقي شرطًا بسيطًا يعرض رسالة مختلفة حسب قيمة معينة.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 3,
                'week_number' => 1,
                'day_number' => 4,
                'title' => 'JavaScript: كتابة الدوال Functions',
                'description' => 'أنشئي دالة تستقبل قيمة وتعيد نتيجة، مثل حساب درجة أو رسالة ترحيب.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 3,
                'week_number' => 1,
                'day_number' => 5,
                'title' => 'Quiz: اختبار قصير على أساسيات JavaScript',
                'description' => 'راجعي المتغيرات وأنواع البيانات والشروط والدوال من خلال أسئلة قصيرة.',
                'task_type' => 'quiz',
                'duration' => '30 دقيقة',
            ],

            [
                'month_number' => 4,
                'week_number' => 1,
                'day_number' => 1,
                'title' => 'DOM: اختيار عنصر باستخدام querySelector',
                'description' => 'تعلمي اختيار عنوان أو زر من الصفحة باستخدام JavaScript.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 4,
                'week_number' => 1,
                'day_number' => 2,
                'title' => 'DOM: تغيير النص داخل عنصر',
                'description' => 'طبقي تغيير نص عنوان أو فقرة عند تنفيذ كود JavaScript.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 4,
                'week_number' => 1,
                'day_number' => 3,
                'title' => 'Events: ربط زر بحدث click',
                'description' => 'أنشئي زرًا يغير نصًا أو لونًا عند الضغط عليه.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 4,
                'week_number' => 1,
                'day_number' => 4,
                'title' => 'Forms: قراءة قيمة input باستخدام JavaScript',
                'description' => 'اقرئي قيمة حقل الاسم واعرضي رسالة ترحيب للمستخدم.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 4,
                'week_number' => 1,
                'day_number' => 5,
                'title' => 'Project: بناء To-Do List بسيطة',
                'description' => 'ابني قائمة مهام تسمح بإضافة مهمة جديدة وعرضها داخل الصفحة.',
                'task_type' => 'project',
                'duration' => 'ساعتان',
            ],
        ];
    }
    
    private function generateDiagnosticQuestions($assessment, $careerPath)
    {
        $skillNames = $this->extractSkillNames($careerPath->required_skills ?? []);
        $skills = !empty($skillNames) ? implode(', ', $skillNames) : 'المهارات الأساسية';


        $prompt = "
            You are an expert field-specific diagnostic assessment designer for Ofuq, an Arabic AI student guidance platform.

            Your task is to generate a HIGHLY SPECIALIZED diagnostic test for the student's ACTIVE CAREER PATH.

            This test must NOT be a general learning test.
            This test must NOT ask about motivation, study habits, self-development, or generic problem solving.
            Every question must feel custom-made for this exact career path.

            Student profile:
            - Major: {$assessment->major}
            - Education level: {$assessment->education_level}
            - GPA: {$assessment->gpa}
            - Current skills: {$assessment->skills}
            - Interests: {$assessment->interests}
            - Previous experience: {$assessment->experience}
            - Career goal: {$assessment->goal}
            - Preferred work environment: {$assessment->work_environment}
            - English level: {$assessment->english_level}

            Active career path:
            - Name: {$careerPath->name}
            - Description: {$careerPath->description}
            - Required skills: {$skills}

            Main goal:
            Diagnose the student's actual readiness for this specific career path by testing:
            - field-specific concepts
            - practical workflow
            - tools or methods used in the path
            - beginner foundations
            - common mistakes in this path
            - real scenarios related to the required skills

            Question count rules:
            - The number of questions must be between 8 and 15.
            - Use 8 questions for simple beginner diagnosis.
            - Use 10 to 12 questions for normal diagnostic depth.
            - Use 13 to 15 questions only if the path has several required skills or needs deeper checking.

            Strict question design rules:
            - Every question must be directly related to the active career path.
            - Every question must test at least one required skill from this list: {$skills}.
            - Do NOT ask generic questions that could apply to any field.
            - Do NOT ask questions like:
            كيف تطور نفسك؟
            ما أفضل طريقة للتعلم؟
            ماذا تفعل عند مواجهة صعوبة؟
            كيف تستمر في التعلم؟
            ما أفضل خطة تعلم؟
            - Do NOT ask personality, motivation, study habits, or self-development questions.
            - Questions must test concrete knowledge or practical decisions inside the field.
            - At least 70% of the questions must be practical scenarios.
            - Beginner questions must still be field-specific.
            - Each question text must mention a specific concept, tool, task, or scenario from the path.

            Field-specific examples:
            - If the path is Front-End, questions must be about HTML, CSS, JavaScript, DOM, forms, responsive design, layout, components, UI behavior, Bootstrap, React, browser workflow, or debugging front-end issues.
            - If the path is Back-End, questions must be about APIs, databases, authentication, server logic, requests, validation, security, or backend architecture.
            - If the path is Pharmacy, questions must be about prescriptions, drug interactions, patient counseling, dosage, pharmacovigilance, community pharmacy, hospital pharmacy, or drug safety.
            - If the path is Education, questions must be about lesson planning, classroom scenarios, student assessment, curriculum, teaching strategies, learning outcomes, or classroom management.
            - If the path is Business, questions must be about stakeholders, operations, business analysis, project management, marketing, finance, reporting, or decision-making in organizations.
            - If the path is Design, questions must be about UX/UI, layout, color, typography, user flow, wireframes, usability, or design tools.

            Answer rules:
            - Each question must have exactly 5 answer choices.
            - Every answer text must be complete and meaningful.
            - Do NOT return vague answers like:
            استخدام عنصر
            عنصر
            خيار
            إجابة
            طريقة صحيحة
            طريقة خاطئة
            - The answer choices must not be duplicated.
            - The fifth answer must always be exactly:
            text: \"لا أعرف الإجابة\"
            score: 0
            icon: \"fa-solid fa-circle-question\"
            - Each answer must have a score from 0 to 3.
            - The best answer should usually score 3.
            - Partial answers should score 1 or 2.
            - Wrong answers should score 0 or 1.
            - The answer \"لا أعرف الإجابة\" must always score 0.
            - Make wrong answers realistic, not silly.
            - Use Arabic for all visible text.
            - Keep JSON keys in English.

            Output rules:
            - Return valid JSON only.
            - No Markdown.
            - No code fences.
            - No text before or after JSON.
            - questions must contain between 8 and 15 items.
            - Each question must contain exactly 5 answers.
            - Generic questions are invalid.
            - type must be one of these Arabic values:
            اختيار من متعدد، سيناريو عملي، تقييم ذاتي
            - difficulty must be one of:
            مبتدئ، متوسط، متقدم

            Return exactly this JSON structure:
            {
                \"questions\": [
                    {
                        \"text\": \"سؤال تخصصي واضح مرتبط بالمسار\",
                        \"type\": \"سيناريو عملي\",
                        \"difficulty\": \"مبتدئ\",
                        \"answers\": [
                            {
                                \"text\": \"إجابة صحيحة أو قوية مكتوبة بجملة واضحة\",
                                \"score\": 3,
                                \"icon\": \"fa-solid fa-check\"
                            },
                            {
                                \"text\": \"إجابة جزئية مكتوبة بجملة واضحة\",
                                \"score\": 2,
                                \"icon\": \"fa-solid fa-lightbulb\"
                            },
                            {
                                \"text\": \"إجابة ضعيفة مكتوبة بجملة واضحة\",
                                \"score\": 1,
                                \"icon\": \"fa-solid fa-circle-question\"
                            },
                            {
                                \"text\": \"إجابة خاطئة مكتوبة بجملة واضحة\",
                                \"score\": 0,
                                \"icon\": \"fa-solid fa-xmark\"
                            },
                            {
                                \"text\": \"لا أعرف الإجابة\",
                                \"score\": 0,
                                \"icon\": \"fa-solid fa-circle-question\"
                            }
                        ]
                    }
                ]
            }
        ";

        try {
            $data = $this->askAI($prompt);

            $questions = $data['questions'] ?? [];

            if (!is_array($questions)) {
                \Log::warning('Diagnostic questions response is not an array', [
                    'questions' => $questions,
                ]);

                return $this->fallbackQuestions($careerPath);
            }

            $questionsCount = count($questions);

            if ($questionsCount < 8 || $questionsCount > 15) {
                \Log::warning('Diagnostic questions invalid count', [
                    'count' => $questionsCount,
                ]);

                return $this->fallbackQuestions($careerPath);
            }

            foreach ($questions as $question) {
                if (
                    !isset($question['answers']) ||
                    !is_array($question['answers']) ||
                    count($question['answers']) !== 5
                ) {
                    \Log::warning('Diagnostic question invalid answers structure', [
                        'question' => $question,
                    ]);

                    return $this->fallbackQuestions($careerPath);
                }

                if (!isset($question['text']) || mb_strlen(trim($question['text'])) < 15) {
                    \Log::warning('Diagnostic question text invalid', [
                        'question' => $question,
                    ]);

                    return $this->fallbackQuestions($careerPath);
                }
            }

            return $this->normalizeDiagnosticQuestions($questions, $careerPath);
        } catch (\Exception $e) {
            \Log::error('Diagnostic questions generation failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackQuestions($careerPath);
        }
    }
    private function extractSkillNames($skills)
    {
        $skillNames = [];
        
        if (is_string($skills)) {
            $decoded = json_decode($skills, true);
            $skills = is_array($decoded) ? $decoded : [];
        }
        
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
        
        return $skillNames;
    }

    private function normalizeDiagnosticQuestions($questions, $careerPath)
    {
        $cleanQuestions = [];
        $seenQuestions = [];

        $allowedTypes = [
            'اختيار من متعدد',
            'سيناريو عملي',
            'تقييم ذاتي',
        ];

        $allowedDifficulties = [
            'مبتدئ',
            'متوسط',
            'متقدم',
        ];

        $badAnswerTexts = [
            'استخدام عنصر',
            'عنصر',
            'استخدام',
            'خيار',
            'إجابة',
            'طريقة صحيحة',
            'طريقة خاطئة',
            'صحيح',
            'خطأ',
            'غير صحيح',
        ];

        foreach ($questions as $question) {
            $questionText = trim($question['text'] ?? '');

            if ($questionText === '' || mb_strlen($questionText) < 15) {
                continue;
            }

            $questionKey = mb_strtolower($questionText);

            if (in_array($questionKey, $seenQuestions, true)) {
                continue;
            }

            $seenQuestions[] = $questionKey;

            $answers = $question['answers'] ?? [];

            if (!is_array($answers) || count($answers) !== 5) {
                continue;
            }

            $cleanAnswers = [];
            $seenAnswers = [];

            foreach ($answers as $answer) {
                if (is_array($answer)) {
                    $answerText = trim($answer['text'] ?? '');
                    $score = (int)($answer['score'] ?? 0);
                    $icon = $answer['icon'] ?? 'fa-solid fa-circle';
                } else {
                    $answerText = trim((string) $answer);
                    $score = 0;
                    $icon = 'fa-solid fa-circle';
                }

                if (
                    $answerText === '' ||
                    mb_strlen($answerText) < 4 ||
                    in_array($answerText, $badAnswerTexts, true)
                ) {
                    continue;
                }

                $answerKey = mb_strtolower($answerText);

                if (in_array($answerKey, $seenAnswers, true)) {
                    continue;
                }

                $seenAnswers[] = $answerKey;

                if ($score < 0 || $score > 3) {
                    $score = 0;
                }

                $cleanAnswers[] = [
                    'text' => $answerText,
                    'score' => $score,
                    'icon' => $icon,
                ];
            }

            if (count($cleanAnswers) !== 5) {
                \Log::warning('Diagnostic question removed because answers are duplicated or incomplete', [
                    'question' => $questionText,
                    'original_answers' => $answers,
                    'clean_answers' => $cleanAnswers,
                ]);

                continue;
            }

            $unknownAnswerIndex = null;

            foreach ($cleanAnswers as $index => $answer) {
                if (($answer['text'] ?? '') === 'لا أعرف الإجابة') {
                    $unknownAnswerIndex = $index;
                    break;
                }
            }

            if ($unknownAnswerIndex !== null) {
                $unknownAnswer = $cleanAnswers[$unknownAnswerIndex];
                unset($cleanAnswers[$unknownAnswerIndex]);
                $cleanAnswers = array_values($cleanAnswers);
                $cleanAnswers[] = $unknownAnswer;
            }

            $cleanAnswers[4] = [
                'text' => 'لا أعرف الإجابة',
                'score' => 0,
                'icon' => 'fa-solid fa-circle-question',
            ];

            $type = $question['type'] ?? 'اختيار من متعدد';

            if (!in_array($type, $allowedTypes, true)) {
                $type = 'اختيار من متعدد';
            }

            $difficulty = $question['difficulty'] ?? 'مبتدئ';

            if (!in_array($difficulty, $allowedDifficulties, true)) {
                $difficulty = 'مبتدئ';
            }

            $cleanQuestions[] = [
                'text' => $questionText,
                'type' => $type,
                'difficulty' => $difficulty,
                'answers' => $cleanAnswers,
            ];
        }

        if (count($cleanQuestions) < 8) {
            \Log::warning('AI diagnostic questions are not enough after normalization, using fallback', [
                'count' => count($cleanQuestions),
            ]);

            return $this->fallbackQuestions($careerPath);
        }

        return array_slice($cleanQuestions, 0, 15);
    }

    private function askOpenAI($prompt)
    {
        $apiKey = env('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new \Exception('OPENAI_API_KEY is missing');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])
        ->connectTimeout(5)
        ->timeout(60)
        ->post('https://api.openai.com/v1/chat/completions', [
            'model' => env('OPENAI_MODEL', 'gpt-4'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Return valid JSON only. No Markdown. No code fences.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
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

    private function askGemini($prompt)
    {
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            throw new \Exception('GEMINI_API_KEY is missing');
        }

        $model = env('GEMINI_MODEL', 'gemini-2.0-flash');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey,
        ])
        ->connectTimeout(5)
        ->timeout(60)
        ->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'responseMimeType' => 'application/json',
                ],
            ]
        );

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

        return $this->decodeAiJson($text);
    }

    private function askGroq($prompt)
    {
        $apiKey = env('GROQ_API_KEY');

        if (!$apiKey) {
            throw new \Exception('GROQ_API_KEY is missing');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->connectTimeout(5)
        ->timeout(60)
        ->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Return valid JSON only. No Markdown. No code fences.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
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

        $data = $response->json();
        $text = $data['choices'][0]['message']['content'] ?? '{}';

        return $this->decodeAiJson($text);
    }

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

    private function fallbackQuestions($careerPath)
    {
        $careerName = $careerPath->name ?? 'المسار المهني';

        $skillNames = $this->extractSkillNames($careerPath->required_skills ?? []);
        $skillsText = !empty($skillNames) ? implode(' ', $skillNames) : '';

        $careerText = mb_strtolower(
            ($careerPath->name ?? '') . ' ' .
            ($careerPath->description ?? '') . ' ' .
            $skillsText
        );

        if (
            str_contains($careerText, 'front') ||
            str_contains($careerText, 'frontend') ||
            str_contains($careerText, 'front-end') ||
            str_contains($careerText, 'واجهة') ||
            str_contains($careerText, 'واجهات') ||
            str_contains($careerText, 'html') ||
            str_contains($careerText, 'css') ||
            str_contains($careerText, 'javascript') ||
            str_contains($careerText, 'js') ||
            str_contains($careerText, 'react') ||
            str_contains($careerText, 'bootstrap') ||
            str_contains($careerText, 'ويب')
        ) {
            return [
                [
                    'text' => 'عند بناء صفحة ويب بسيطة، ما الدور الأساسي لوسوم HTML؟',
                    'type' => 'اختيار من متعدد',
                    'difficulty' => 'مبتدئ',
                    'answers' => [
                        ['text' => 'بناء هيكل الصفحة وتحديد عناصرها مثل العناوين والفقرات والنماذج', 'score' => 3, 'icon' => 'fa-solid fa-code'],
                        ['text' => 'إضافة الحركة والتفاعل فقط', 'score' => 1, 'icon' => 'fa-solid fa-bolt'],
                        ['text' => 'تخزين بيانات المستخدمين في قاعدة البيانات', 'score' => 0, 'icon' => 'fa-solid fa-database'],
                        ['text' => 'اختيار ألوان الصفحة فقط', 'score' => 1, 'icon' => 'fa-solid fa-palette'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
                [
                    'text' => 'إذا أردت إنشاء نموذج تسجيل دخول، أي وسم HTML هو الأنسب لتجميع حقول الإدخال وزر الإرسال؟',
                    'type' => 'سيناريو عملي',
                    'difficulty' => 'مبتدئ',
                    'answers' => [
                        ['text' => 'form', 'score' => 3, 'icon' => 'fa-solid fa-check'],
                        ['text' => 'section', 'score' => 1, 'icon' => 'fa-solid fa-layer-group'],
                        ['text' => 'img', 'score' => 0, 'icon' => 'fa-solid fa-image'],
                        ['text' => 'table', 'score' => 1, 'icon' => 'fa-solid fa-table'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
                [
                    'text' => 'ما الخاصية المناسبة في CSS لجعل العناصر تظهر بجانب بعضها داخل حاوية واحدة بسهولة؟',
                    'type' => 'اختيار من متعدد',
                    'difficulty' => 'مبتدئ',
                    'answers' => [
                        ['text' => 'display: flex', 'score' => 3, 'icon' => 'fa-solid fa-grip'],
                        ['text' => 'font-size', 'score' => 0, 'icon' => 'fa-solid fa-font'],
                        ['text' => 'color', 'score' => 0, 'icon' => 'fa-solid fa-droplet'],
                        ['text' => 'text-align', 'score' => 1, 'icon' => 'fa-solid fa-align-center'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
                [
                    'text' => 'إذا كانت الصفحة لا تتجاوب جيدًا على شاشة الهاتف، أي مفهوم في CSS يجب مراجعته أولًا؟',
                    'type' => 'سيناريو عملي',
                    'difficulty' => 'متوسط',
                    'answers' => [
                        ['text' => 'Responsive Design و media queries', 'score' => 3, 'icon' => 'fa-solid fa-mobile-screen'],
                        ['text' => 'تغيير اسم ملف HTML فقط', 'score' => 0, 'icon' => 'fa-solid fa-file'],
                        ['text' => 'زيادة عدد الصور في الصفحة', 'score' => 0, 'icon' => 'fa-solid fa-image'],
                        ['text' => 'حذف كل أكواد JavaScript', 'score' => 1, 'icon' => 'fa-solid fa-trash'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
                [
                    'text' => 'عند الضغط على زر في الصفحة ولا يحدث أي تفاعل، أي جزء من JavaScript تفحص أولًا؟',
                    'type' => 'سيناريو عملي',
                    'difficulty' => 'متوسط',
                    'answers' => [
                        ['text' => 'وجود event listener مربوط بالزر بشكل صحيح', 'score' => 3, 'icon' => 'fa-solid fa-computer-mouse'],
                        ['text' => 'لون خلفية الصفحة', 'score' => 0, 'icon' => 'fa-solid fa-palette'],
                        ['text' => 'حجم الصورة داخل الصفحة', 'score' => 0, 'icon' => 'fa-solid fa-image'],
                        ['text' => 'اسم الخط المستخدم فقط', 'score' => 0, 'icon' => 'fa-solid fa-font'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
                [
                    'text' => 'ما وظيفة DOM في تطوير الواجهات الأمامية؟',
                    'type' => 'اختيار من متعدد',
                    'difficulty' => 'متوسط',
                    'answers' => [
                        ['text' => 'يمثل عناصر الصفحة ويسمح لـ JavaScript بالتعامل معها وتعديلها', 'score' => 3, 'icon' => 'fa-solid fa-sitemap'],
                        ['text' => 'يحفظ كلمات المرور في السيرفر', 'score' => 0, 'icon' => 'fa-solid fa-lock'],
                        ['text' => 'يستبدل CSS بالكامل', 'score' => 0, 'icon' => 'fa-solid fa-brush'],
                        ['text' => 'يحدد سرعة الإنترنت للمستخدم', 'score' => 0, 'icon' => 'fa-solid fa-wifi'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
                [
                    'text' => 'إذا أردت تنسيق زر ليكون لونه بنفسجيًا وحوافه دائرية، أين تكتب هذا التنسيق غالبًا؟',
                    'type' => 'اختيار من متعدد',
                    'difficulty' => 'مبتدئ',
                    'answers' => [
                        ['text' => 'في CSS باستخدام خصائص مثل background و border-radius', 'score' => 3, 'icon' => 'fa-solid fa-paintbrush'],
                        ['text' => 'داخل قاعدة البيانات', 'score' => 0, 'icon' => 'fa-solid fa-database'],
                        ['text' => 'في عنوان الصفحة فقط', 'score' => 0, 'icon' => 'fa-solid fa-heading'],
                        ['text' => 'باستخدام وسم img', 'score' => 0, 'icon' => 'fa-solid fa-image'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
                [
                    'text' => 'ما الهدف من استخدام Bootstrap في مشروع واجهات أمامية بسيط؟',
                    'type' => 'اختيار من متعدد',
                    'difficulty' => 'متوسط',
                    'answers' => [
                        ['text' => 'تسريع بناء واجهات متجاوبة باستخدام مكونات وكلاسات جاهزة', 'score' => 3, 'icon' => 'fa-solid fa-layer-group'],
                        ['text' => 'تشغيل الخادم الخلفي بدل Laravel', 'score' => 0, 'icon' => 'fa-solid fa-server'],
                        ['text' => 'حفظ بيانات المستخدمين تلقائيًا', 'score' => 0, 'icon' => 'fa-solid fa-database'],
                        ['text' => 'منع استخدام HTML نهائيًا', 'score' => 0, 'icon' => 'fa-solid fa-ban'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
                [
                    'text' => 'في React، لماذا نقسم الواجهة إلى Components؟',
                    'type' => 'اختيار من متعدد',
                    'difficulty' => 'متقدم',
                    'answers' => [
                        ['text' => 'لتنظيم الواجهة إلى أجزاء قابلة لإعادة الاستخدام والصيانة', 'score' => 3, 'icon' => 'fa-solid fa-cubes'],
                        ['text' => 'لإلغاء الحاجة إلى JavaScript', 'score' => 0, 'icon' => 'fa-solid fa-xmark'],
                        ['text' => 'لتخزين كل الملفات داخل ملف واحد فقط', 'score' => 0, 'icon' => 'fa-solid fa-file'],
                        ['text' => 'لتغيير نوع قاعدة البيانات', 'score' => 0, 'icon' => 'fa-solid fa-database'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
            ];
        }

        if (
            str_contains($careerText, 'صيدلة') ||
            str_contains($careerText, 'صيدلي') ||
            str_contains($careerText, 'pharmacy')
        ) {
            return [
                [
                    'text' => 'عند صرف وصفة طبية، ما أول أمر يجب التأكد منه قبل تسليم الدواء للمريض؟',
                    'type' => 'سيناريو عملي',
                    'difficulty' => 'مبتدئ',
                    'answers' => [
                        ['text' => 'مطابقة اسم المريض والدواء والجرعة وطريقة الاستخدام مع الوصفة', 'score' => 3, 'icon' => 'fa-solid fa-prescription-bottle-medical'],
                        ['text' => 'اختيار الدواء الأرخص مباشرة دون مراجعة الوصفة', 'score' => 0, 'icon' => 'fa-solid fa-tag'],
                        ['text' => 'تسليم الدواء دون شرح طريقة الاستخدام', 'score' => 1, 'icon' => 'fa-solid fa-box'],
                        ['text' => 'تغيير الجرعة حسب توفر العبوة فقط', 'score' => 0, 'icon' => 'fa-solid fa-xmark'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
                [
                    'text' => 'إذا أخبرك المريض أنه يستخدم دواءً آخر، ما التصرف الأهم قبل صرف العلاج الجديد؟',
                    'type' => 'سيناريو عملي',
                    'difficulty' => 'متوسط',
                    'answers' => [
                        ['text' => 'مراجعة احتمال وجود تداخلات دوائية أو تحذيرات مرتبطة بالأدوية', 'score' => 3, 'icon' => 'fa-solid fa-triangle-exclamation'],
                        ['text' => 'تجاهل المعلومة إذا لم يسأل الطبيب عنها', 'score' => 0, 'icon' => 'fa-solid fa-ban'],
                        ['text' => 'إيقاف كل أدويته السابقة فورًا', 'score' => 0, 'icon' => 'fa-solid fa-stop'],
                        ['text' => 'صرف الدواء دون أي سؤال إضافي', 'score' => 0, 'icon' => 'fa-solid fa-pills'],
                        ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                    ],
                ],
            ];
        }

        return [
            [
                'text' => 'ما أول مفهوم عملي مرتبط مباشرة بمسار ' . $careerName . ' يجب فهمه قبل التطبيق؟',
                'type' => 'اختيار من متعدد',
                'difficulty' => 'مبتدئ',
                'answers' => [
                    ['text' => 'فهم المفاهيم الأساسية والمهام العملية الخاصة بهذا المسار', 'score' => 3, 'icon' => 'fa-solid fa-list-check'],
                    ['text' => 'البدء بمشروع كبير دون فهم الأساسيات', 'score' => 0, 'icon' => 'fa-solid fa-forward'],
                    ['text' => 'حفظ أسماء الأدوات فقط دون معرفة استخدامها', 'score' => 1, 'icon' => 'fa-solid fa-book'],
                    ['text' => 'تجاهل المهارات المطلوبة للمسار', 'score' => 0, 'icon' => 'fa-solid fa-ban'],
                    ['text' => 'لا أعرف الإجابة', 'score' => 0, 'icon' => 'fa-solid fa-circle-question'],
                ],
            ],
        ];
    }

    private function fallbackRoadmapTasksByPath($careerPath, $diagnosticResult)
    {
        $skills = is_array($careerPath->required_skills)
            ? implode(' ', $careerPath->required_skills)
            : ($careerPath->required_skills ?? '');

        $careerText = mb_strtolower(
            ($careerPath->name ?? '') . ' ' .
            ($careerPath->description ?? '') . ' ' .
            $skills
        );

        $isPharmacy = str_contains($careerText, 'صيدلة') ||
            str_contains($careerText, 'صيدلي') ||
            str_contains($careerText, 'pharmacy') ||
            str_contains($careerText, 'دواء') ||
            str_contains($careerText, 'أدوية');

        $isFrontEnd = str_contains($careerText, 'front') ||
            str_contains($careerText, 'frontend') ||
            str_contains($careerText, 'front-end') ||
            str_contains($careerText, 'واجهة') ||
            str_contains($careerText, 'واجهات') ||
            str_contains($careerText, 'html') ||
            str_contains($careerText, 'css') ||
            str_contains($careerText, 'javascript') ||
            str_contains($careerText, 'react') ||
            str_contains($careerText, 'ويب');

        if ($isPharmacy) {
            return [
                [
                    'month_number' => 1,
                    'week_number' => 1,
                    'day_number' => 1,
                    'title' => 'صيدلة: فهم دور الصيدلي عند استقبال وصفة طبية',
                    'description' => 'تعلّمي مسؤوليات الصيدلي عند استقبال وصفة، مثل التأكد من بيانات المريض، اسم الدواء، الجرعة، ووضوح التعليمات. طبقي تمرين استخراج بيانات وصفة بسيطة.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 1,
                    'day_number' => 2,
                    'title' => 'الوصفة الطبية: استخراج اسم الدواء والتركيز',
                    'description' => 'تدرّبي على قراءة اسم الدواء وتركيزه مثل 500mg أو 5mg/5ml، واكتبي جدولًا صغيرًا فيه اسم الدواء والتركيز والشكل الدوائي.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 1,
                    'day_number' => 3,
                    'title' => 'الوصفة الطبية: فهم الجرعة والتكرار ومدة العلاج',
                    'description' => 'افهمي الفرق بين dose و frequency و duration، وطبقي على وصفة فيها دواء يؤخذ مرتين يوميًا لمدة 5 أيام.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 1,
                    'day_number' => 4,
                    'title' => 'اختصارات الوصفات: فهم OD و BID و TID و PRN',
                    'description' => 'تعلّمي معنى أشهر اختصارات الوصفات الطبية، ثم حوّلي 5 اختصارات إلى تعليمات عربية واضحة للمريض.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 1,
                    'day_number' => 5,
                    'title' => 'Quiz: اختبار قصير على قراءة الوصفة الطبية',
                    'description' => 'راجعي عناصر الوصفة الأساسية ثم أجيبي على أسئلة قصيرة حول اسم الدواء، التركيز، الجرعة، التكرار، ومدة العلاج.',
                    'task_type' => 'quiz',
                    'duration' => '30 دقيقة',
                ],

                [
                    'month_number' => 1,
                    'week_number' => 2,
                    'day_number' => 1,
                    'title' => 'الأشكال الدوائية: الفرق بين tablets و capsules و syrups',
                    'description' => 'تعلّمي الفرق بين الأقراص والكبسولات والشراب، ومتى قد يكون كل شكل مناسبًا للمريض. اكتبي مثالين لكل شكل دوائي.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 2,
                    'day_number' => 2,
                    'title' => 'الأشكال الدوائية: القطرات والكريمات والحقن',
                    'description' => 'افهمي طريقة استخدام القطرات والكريمات والحقن بشكل عام، مع التركيز على إرشادات السلامة الأساسية لكل شكل.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 2,
                    'day_number' => 3,
                    'title' => 'إرشاد المريض: شرح طريقة استخدام الشراب الدوائي',
                    'description' => 'تدرّبي على شرح طريقة رج العبوة، قياس الجرعة بالمكيال، عدد مرات الاستخدام، وطريقة حفظ الشراب بعد الفتح.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 2,
                    'day_number' => 4,
                    'title' => 'إرشاد المريض: شرح طريقة استخدام القطرات',
                    'description' => 'اكتبي تعليمات واضحة لمريض يستخدم قطرة عين أو أذن، مع ذكر النظافة، عدد القطرات، وعدم لمس طرف العبوة.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 2,
                    'day_number' => 5,
                    'title' => 'Review: مراجعة الأشكال الدوائية وطريقة استخدامها',
                    'description' => 'راجعي الأشكال الدوائية التي تعلمتها، ثم اكتبي ملخصًا قصيرًا يربط كل شكل دوائي بطريقة استخدامه الصحيحة.',
                    'task_type' => 'review',
                    'duration' => '30 دقيقة',
                ],

                [
                    'month_number' => 1,
                    'week_number' => 3,
                    'day_number' => 1,
                    'title' => 'الجرعات: فهم معنى الجرعة الواحدة والجرعة اليومية',
                    'description' => 'تعلّمي الفرق بين جرعة واحدة وجرعة يومية كاملة، وطبقي على مثال دواء يؤخذ 3 مرات يوميًا.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 3,
                    'day_number' => 2,
                    'title' => 'حساب الجرعات: جرعة بسيطة حسب وزن المريض',
                    'description' => 'تدرّبي على حساب جرعة مبدئية باستخدام مثال mg/kg مع أرقام بسيطة، مع التأكيد أن القرار النهائي يكون حسب الطبيب أو المرجع الدوائي.',
                    'task_type' => 'practice',
                    'duration' => 'ساعة واحدة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 3,
                    'day_number' => 3,
                    'title' => 'الجرعات: تحويل تعليمات الوصفة إلى جدول استخدام',
                    'description' => 'حوّلي وصفة مكتوبة إلى جدول واضح للمريض: صباحًا، ظهرًا، مساءً، قبل أو بعد الطعام.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 3,
                    'day_number' => 4,
                    'title' => 'أخطاء الجرعات: اكتشاف تكرار جرعة غير منطقي',
                    'description' => 'تدرّبي على سيناريو وصفة فيها تكرار غير واضح أو جرعة مبالغ فيها، وحددي الأسئلة التي يجب طرحها قبل الصرف.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 3,
                    'day_number' => 5,
                    'title' => 'Quiz: اختبار قصير على الجرعات والتكرار',
                    'description' => 'اختبري فهمك للفرق بين dose و frequency و duration، وطبقي على 3 وصفات قصيرة.',
                    'task_type' => 'quiz',
                    'duration' => '30 دقيقة',
                ],

                [
                    'month_number' => 1,
                    'week_number' => 4,
                    'day_number' => 1,
                    'title' => 'السلامة الدوائية: مفهوم الحساسية الدوائية',
                    'description' => 'تعلّمي لماذا يجب سؤال المريض عن الحساسية قبل صرف الدواء، وطبقي على حالة مريض لديه حساسية من البنسلين.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 4,
                    'day_number' => 2,
                    'title' => 'السلامة الدوائية: متى يجب إيقاف الصرف وسؤال الطبيب',
                    'description' => 'تعرّفي على مواقف تستدعي مراجعة الطبيب مثل جرعة غير واضحة، تعارض دوائي محتمل، حمل، أو حساسية خطيرة.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 4,
                    'day_number' => 3,
                    'title' => 'أخطاء الصرف: تشابه أسماء الأدوية',
                    'description' => 'تدرّبي على مقارنة أسماء أدوية متشابهة شكلاً أو لفظًا، واكتبي خطوات لتقليل خطر صرف الدواء الخطأ.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 4,
                    'day_number' => 4,
                    'title' => 'أخطاء الصرف: التأكد من التركيز والشكل الدوائي',
                    'description' => 'طبقي تمرينًا فيه نفس الدواء بتركيزين أو شكلين مختلفين، وحددي كيف تتأكدين من الاختيار الصحيح.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 1,
                    'week_number' => 4,
                    'day_number' => 5,
                    'title' => 'Project: بطاقة فحص وصفة قبل الصرف',
                    'description' => 'صممي قائمة تحقق بسيطة تشمل: بيانات المريض، الحساسية، اسم الدواء، التركيز، الجرعة، التكرار، مدة العلاج، والتحذيرات.',
                    'task_type' => 'project',
                    'duration' => 'ساعتان',
                ],

                [
                    'month_number' => 2,
                    'week_number' => 1,
                    'day_number' => 1,
                    'title' => 'التداخلات الدوائية: فهم معنى Drug Interaction',
                    'description' => 'تعلّمي معنى التداخل الدوائي، ولماذا قد يزيد تأثير الدواء أو يقل أو يسبب ضررًا عند جمعه مع دواء آخر.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 1,
                    'day_number' => 2,
                    'title' => 'التداخلات الدوائية: أسئلة يجب طرحها على المريض',
                    'description' => 'اكتبي قائمة أسئلة للمريض قبل صرف دواء جديد: هل تستخدم أدوية أخرى؟ مكملات؟ أعشاب؟ هل لديك أمراض مزمنة؟',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 1,
                    'day_number' => 3,
                    'title' => 'التداخلات: أدوية الألم ومميعات الدم',
                    'description' => 'افهمي سيناريو مريض يستخدم مميع دم ويريد مسكن ألم، وحددي لماذا يجب الانتباه قبل صرف بعض المسكنات.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 1,
                    'day_number' => 4,
                    'title' => 'التداخلات: المضادات الحيوية وموانع الحمل',
                    'description' => 'تدرّبي على شرح فكرة وجود أدوية قد تؤثر على فعالية أدوية أخرى، مع صياغة نص إرشادي واضح للمريض.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 1,
                    'day_number' => 5,
                    'title' => 'Quiz: اكتشاف التداخلات من سيناريوهات قصيرة',
                    'description' => 'اقرئي 4 حالات صيدلية قصيرة، وحددي في كل حالة هل يوجد احتمال تداخل دوائي أو حاجة لسؤال إضافي.',
                    'task_type' => 'quiz',
                    'duration' => '30 دقيقة',
                ],

                [
                    'month_number' => 2,
                    'week_number' => 2,
                    'day_number' => 1,
                    'title' => 'إرشاد المريض: شرح الدواء بلغة بسيطة',
                    'description' => 'تعلّمي كيف تشرحين اسم الدواء، سبب استخدامه، وقت أخذه، ومدة العلاج دون مصطلحات معقدة.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 2,
                    'day_number' => 2,
                    'title' => 'إرشاد المريض: قبل الطعام أم بعد الطعام',
                    'description' => 'تدرّبي على توضيح تعليمات استخدام الدواء قبل أو بعد الطعام، واكتبي 3 أمثلة لجمل إرشادية واضحة.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 2,
                    'day_number' => 3,
                    'title' => 'إرشاد المريض: التعامل مع نسيان الجرعة',
                    'description' => 'تعلمي الطريقة العامة لتوجيه المريض عند نسيان جرعة، ومتى يجب عدم مضاعفة الجرعة دون استشارة.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 2,
                    'day_number' => 4,
                    'title' => 'إرشاد المريض: شرح الآثار الجانبية الشائعة',
                    'description' => 'تدرّبي على شرح آثار جانبية بسيطة بطريقة لا تخيف المريض، مع توضيح متى يجب مراجعة الطبيب.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 2,
                    'day_number' => 5,
                    'title' => 'Project: بطاقة إرشاد مريض لدواء شائع',
                    'description' => 'اكتبي بطاقة إرشاد مختصرة لدواء شائع تشمل: الاستخدام، طريقة الأخذ، التحذيرات، التخزين، ومتى يراجع الطبيب.',
                    'task_type' => 'project',
                    'duration' => 'ساعتان',
                ],

                [
                    'month_number' => 2,
                    'week_number' => 3,
                    'day_number' => 1,
                    'title' => 'OTC: التعامل مع حالة صداع بسيطة',
                    'description' => 'تعلّمي الأسئلة الأساسية قبل اقتراح علاج OTC للصداع: العمر، الحمل، أمراض مزمنة، أدوية أخرى، شدة الألم.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 3,
                    'day_number' => 2,
                    'title' => 'OTC: التعامل مع أعراض الزكام',
                    'description' => 'تدرّبي على تمييز أعراض الزكام البسيطة، والأسئلة التي تساعدك على اختيار نصيحة آمنة أو تحويل المريض للطبيب.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 3,
                    'day_number' => 3,
                    'title' => 'OTC: الحساسية الموسمية ومضادات الهيستامين',
                    'description' => 'افهمي بشكل عام متى تستخدم أدوية الحساسية، وما التحذيرات المهمة مثل النعاس أو قيادة السيارة.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 3,
                    'day_number' => 4,
                    'title' => 'OTC: الحموضة ومشاكل المعدة البسيطة',
                    'description' => 'تدرّبي على سؤال المريض عن مدة الأعراض، الأدوية المستخدمة، وجود ألم شديد، أو علامات تستدعي الطبيب.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 3,
                    'day_number' => 5,
                    'title' => 'Quiz: اختيار التصرف المناسب في حالات OTC',
                    'description' => 'أجيبي على حالات قصيرة: صداع، زكام، حساسية، حموضة، وحددي هل النصيحة الصيدلية كافية أم يجب تحويل المريض للطبيب.',
                    'task_type' => 'quiz',
                    'duration' => '30 دقيقة',
                ],

                [
                    'month_number' => 2,
                    'week_number' => 4,
                    'day_number' => 1,
                    'title' => 'تخزين الأدوية: درجة الحرارة والرطوبة والضوء',
                    'description' => 'تعلّمي أثر الحرارة والرطوبة والضوء على الدواء، واكتبي تعليمات تخزين واضحة للمريض.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 4,
                    'day_number' => 2,
                    'title' => 'تخزين الأدوية: أدوية تحتاج الثلاجة',
                    'description' => 'افهمي أهمية حفظ بعض الأدوية في درجة حرارة مناسبة، وطبقي على حالة مريض يسافر ومعه دواء يحتاج تبريد.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 4,
                    'day_number' => 3,
                    'title' => 'سلامة الدواء: تاريخ الانتهاء وتغير اللون أو الرائحة',
                    'description' => 'تعلّمي متى يجب عدم استخدام الدواء، مثل انتهاء الصلاحية أو تغير اللون أو الرائحة أو القوام.',
                    'task_type' => 'lesson',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 4,
                    'day_number' => 4,
                    'title' => 'سلامة الدواء: التخلص الآمن من الأدوية',
                    'description' => 'اكتبي إرشادات بسيطة حول عدم رمي بعض الأدوية عشوائيًا، وأهمية سؤال الصيدلية عن طريقة التخلص الآمنة.',
                    'task_type' => 'practice',
                    'duration' => '45 دقيقة',
                ],
                [
                    'month_number' => 2,
                    'week_number' => 4,
                    'day_number' => 5,
                    'title' => 'Project: ملف حالات صيدلية تدريبية',
                    'description' => 'اجمعي 6 حالات تدريبية: وصفة، جرعة، تداخل، OTC، تخزين، وإرشاد مريض، واكتبي لكل حالة التصرف الصحيح.',
                    'task_type' => 'project',
                    'duration' => 'ساعتان',
                ],
            ];
        }

        if ($isFrontEnd) {
            return $this->fallbackRoadmapTasks($careerPath, $diagnosticResult);
        }

        return [
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 1,
                'title' => 'تأسيس: فهم طبيعة المسار والمهام الأساسية',
                'description' => 'حددي أهم مسؤوليات هذا المسار، واكتبي قائمة بالمهارات التي تحتاجين تقويتها خلال الفترة القادمة.',
                'task_type' => 'lesson',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 2,
                'title' => 'تأسيس: تحديد المصطلحات الأساسية في المسار',
                'description' => 'اكتبي 10 مصطلحات مهمة مرتبطة بالمسار، وفسري كل مصطلح بجملة بسيطة مع مثال عملي.',
                'task_type' => 'practice',
                'duration' => '45 دقيقة',
            ],
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 3,
                'title' => 'تطبيق: تحليل حالة أو مهمة بسيطة من المجال',
                'description' => 'اختاري حالة تدريبية بسيطة مرتبطة بالمسار، وحددي المطلوب والمشكلة والخطوات المناسبة للتعامل معها.',
                'task_type' => 'practice',
                'duration' => 'ساعة واحدة',
            ],
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 4,
                'title' => 'مراجعة: تلخيص نقاط القوة والضعف',
                'description' => 'راجعي ما تعلمته هذا الأسبوع، واكتبي 3 نقاط قوة و3 نقاط تحتاج إلى تطوير.',
                'task_type' => 'review',
                'duration' => '30 دقيقة',
            ],
            [
                'month_number' => 1,
                'week_number' => 1,
                'day_number' => 5,
                'title' => 'Project: مخرج تدريبي صغير للمسار',
                'description' => 'أنشئي ملفًا بسيطًا يلخص فهمك لأول أسبوع، ويحتوي على مصطلحات، حالة قصيرة، وخطوات التعامل معها.',
                'task_type' => 'project',
                'duration' => 'ساعتان',
            ],
        ];
    }

}