<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\CareerPath;
use App\Models\RoadmapTask;
use App\Models\RecommendedCourse;
use App\Models\RoadmapOverview;

class RoadmapController extends Controller
{
   
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $careerPath = CareerPath::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$careerPath) {
            return redirect()->route('career.selection')
                ->with('error', 'يرجى اختيار مسار مهني أولاً.');
        }

        $tasksExist = RoadmapTask::where('user_id', $user->id)
            ->where('career_path_id', $careerPath->id)
            ->exists();

        if (!$tasksExist) {
            $this->generateMonthlyRoadmap($careerPath);
        }

        $coursesExist = RecommendedCourse::where('user_id', $user->id)
            ->where('career_path_id', $careerPath->id)
            ->exists();

        if (!$coursesExist) {
            $this->generateCourses($careerPath);
        }

        $overview = RoadmapOverview::where('user_id', $user->id)
            ->where('career_path_id', $careerPath->id)
            ->latest()
            ->first();

        $stages = [];
        if ($overview) {
            if (is_string($overview->stages)) {
                $stages = json_decode($overview->stages, true) ?? [];
            } elseif (is_array($overview->stages)) {
                $stages = $overview->stages;
            }
        }

        // إذا لم يكن هناك overview أو كانت المراحل فارغة، قم بحذفه وتوليد جديد
        if (!$overview || empty($stages)) {
            if ($overview) {
                $overview->delete();
            }
            $overview = $this->generateOverview($careerPath);
            
            // إعادة قراءة المراحل بعد التوليد الجديد
            if ($overview) {
                if (is_string($overview->stages)) {
                    $stages = json_decode($overview->stages, true) ?? [];
                } elseif (is_array($overview->stages)) {
                    $stages = $overview->stages;
                }
            }
        }

        $courses = RecommendedCourse::where('user_id', $user->id)
            ->where('career_path_id', $careerPath->id)
            ->get();

        $aiAdvice = $overview && $overview->summary
            ? $overview->summary
            : 'ابدئي بفهم المسارات العامة أولًا، ثم انتقلي للخطة الشهرية لتنفيذ المهام اليومية بالتدريج.';

        return view('roadmap', [
            'careerPath' => $careerPath,
            'overview' => $overview,
            'stages' => $stages,
            'courses' => $courses,
            'aiAdvice' => $aiAdvice,
        ]);
    }

    public function monthly()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $careerPath = \App\Models\CareerPath::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$careerPath) {
            return redirect()->route('career.selection')
                ->with('error', 'يرجى اختيار مسار مهني أولاً.');
        }

        $tasks = \App\Models\RoadmapTask::where('user_id', $user->id)
            ->where('career_path_id', $careerPath->id)
            ->orderBy('month_number')
            ->orderBy('week_number')
            ->orderBy('day_number')
            ->get();

        $months = $tasks
            ->groupBy(function ($task) {
                return (int) ($task->month_number ?? 1);
            })
            ->map(function ($monthTasks) {
                return $monthTasks->groupBy(function ($task) {
                    return (int) ($task->week_number ?? 1);
                });
            });

        $totalTasks = $tasks->count();

        $completedTasks = $tasks
            ->where('status', 'completed')
            ->count();

        $remainingTasks = max($totalTasks - $completedTasks, 0);

        $overallProgress = $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100)
            : 0;

        $skills = $careerPath->required_skills ?? [];

        if (is_string($skills)) {
            $decodedSkills = json_decode($skills, true);
            $skills = is_array($decodedSkills) ? $decodedSkills : [];
        }

        if (!is_array($skills)) {
            $skills = [];
        }

        return view('monthly-roadmap', [
            'careerPath' => $careerPath,
            'tasks' => $tasks,
            'months' => $months,
            'skills' => $skills,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'remainingTasks' => $remainingTasks,
            'overallProgress' => $overallProgress,
        ]);
    }
/*
    public function generateMonthlyRoadmap($careerPath)
    {
        $user = Auth::user();

        $skillNames = [];
        $skills = $careerPath->required_skills ?? [];

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

        $skillsText = implode(', ', $skillNames);

            $prompt = "
            You are a professional learning path designer for an Arabic student guidance platform called Ofuq.

            Your task is to create a strong, practical, one-month learning roadmap for the selected career path.

            Career path:
            {$careerPath->name}

            Career description:
            {$careerPath->description}

            Required skills:
            {$skillsText}

            Learning plan strategy:
            - Create exactly 4 weeks.
            - Each week must contain exactly 5 daily tasks.
            - Total tasks must be exactly 20.
            - The plan must be practical, progressive, and suitable for a beginner-to-intermediate student.
            - Week 1 must focus on understanding the field, key concepts, terminology, tools, and basic setup.
            - Week 2 must focus on learning and practicing the first core skill from the required skills.
            - Week 3 must focus on building a small practical project related to the career path.
            - Week 4 must focus on improving the project, reviewing progress, testing understanding, and preparing portfolio-ready outputs.

            Task quality rules:
            - Tasks must be clear, specific, practical, and executable.
            - Do not write vague tasks like only 'read', 'watch', or 'summarize'.
            - Every task must produce a clear output, such as:
            notes, solved exercise, checklist, code, design, report, analysis, documentation, mini project, presentation, or portfolio item.
            - Make every task directly related to the selected career path.
            - Task titles must be short and clear.
            - Task descriptions must explain exactly what the student should do.
            - Duration must be between 20 and 60 minutes.
            - task_type must be exactly one of these Arabic values:
            قراءة، فيديو، تطبيق، اختبار، مراجعة
            - Use Arabic for all text values.
            - Keep JSON keys in English exactly as shown.

            Output rules:
            - Return valid JSON only.
            - Do not use Markdown.
            - Do not wrap the JSON in code fences.
            - Do not add any explanation before or after the JSON.
            - Do not include comments.
            - The JSON must include exactly 4 weeks.
            - Each week must include exactly 5 tasks.
            - The day_number values inside each week must be 1, 2, 3, 4, and 5.

            Return exactly this JSON structure:
            {
            \"weeks\": [
                {
                \"week_number\": 1,
                \"title\": \"عنوان الأسبوع بالعربية\",
                \"tasks\": [
                    {
                    \"day_number\": 1,
                    \"title\": \"عنوان المهمة بالعربية\",
                    \"description\": \"وصف عملي واضح بالعربية لما يجب إنجازه\",
                    \"task_type\": \"تطبيق\",
                    \"duration\": \"30 دقيقة\"
                    }
                ]
                }
            ]
            }
            ";

            try {
                $data = $this->askGroq($prompt);
                $weeks = $data['weeks'] ?? [];

                if (count($weeks) < 4) {
                    $weeks = $this->fallbackWeeks($careerPath);
                }
            } catch (\Exception $e) {
                $weeks = $this->fallbackWeeks($careerPath);
            }

            foreach ($weeks as $week) {
                foreach (($week['tasks'] ?? []) as $task) {
                    RoadmapTask::create([
                        'user_id' => $user->id,
                        'career_path_id' => $careerPath->id,
                        'month_number' => 1,
                        'week_number' => $week['week_number'] ?? 1,
                        'day_number' => $task['day_number'] ?? null,
                        'title' => $task['title'] ?? 'مهمة تعليمية',
                        'description' => $task['description'] ?? null,
                        'task_type' => $task['task_type'] ?? 'قراءة',
                        'duration' => $task['duration'] ?? '30 دقيقة',
                        'status' => 'pending',
                    ]);
                }
            }
    }
*/
    public function generateMonthlyRoadmap($careerPath)
    {
        $user = Auth::user();

        $skillNames = [];
        $skills = $careerPath->required_skills ?? [];

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

        $skillsText = implode(', ', $skillNames);

        $prompt = "
        أنت خبير في تصميم خطط تعلم احترافية وتفصيلية لمنصة أفق.

        مهمتك: إنشاء خطة تعلم شهرية تفصيلية جداً (4 أسابيع، 5 مهام في كل أسبوع = 20 مهمة) لمسار مهني محدد.

        بيانات المسار:
        - اسم المسار: {$careerPath->name}
        - وصف المسار: {$careerPath->description}
        - المهارات المطلوبة: {$skillsText}

        تعليمات صارمة جداً:

        1. **التفصيل هو المطلوب الأساسي:**
        - لا تكتفي بعناوين عامة مثل 'تعلم HTML'.
        - كل مهمة يجب أن تركز على موضوع واحد محدد جداً.
        - مثلاً بدل 'تعلم HTML'، اكتب: 'إنشاء هيكل صفحة HTML باستخدام html و head و body'.
        - مثلاً بدل 'تعلم CSS'، اكتب: 'تطبيق ألوان الخلفية والنصوص باستخدام background-color و color'.
        - مثلاً بدل 'تعلم JavaScript'، اكتب: 'كتابة دالة باستخدام function و if-else لحساب الدرجات'.

        2. **التسلسل المنطقي:**
        - الأسبوع 1: الأساسيات (مفاهيم عامة، مصطلحات، أدوات، إعداد البيئة).
        - الأسبوع 2: المهارة الأولى من المهارات المطلوبة، مقسمة إلى 5 مهام فرعية مفصلة.
        - الأسبوع 3: المهارة الثانية أو مشروع مصغر مقسم إلى 5 مهام مفصلة.
        - الأسبوع 4: تحسين المشروع، مراجعة، اختبار، وتجهيز المخرجات.

        3. **قواعد المهام:**
        - كل مهمة يجب أن تكون محددة وقابلة للتنفيذ وتنتج مخرجات ملموسة.
        - المخرجات المقترحة: كود، تصميم، تقرير، تحليل، مشروع، تمرين محلول، قائمة مراجعة، مستند، إلخ.
        - لا تكرر المهام، وكل مهمة ترتبط مباشرة بالمهارات المطلوبة.
        - استخدم لغة عربية احترافية مع السماح بالمصطلحات التقنية الإنجليزية إذا لزم الأمر.

        4. **أنواع المهام (task_type) يجب أن تكون واحدة من:**
        قراءة، فيديو، تطبيق، اختبار، مراجعة

        5. **المدة (duration) يجب أن تكون واحدة من:**
        30 دقيقة، 45 دقيقة، ساعة واحدة، ساعتان

        6. **صيغة الإخراج: JSON فقط (بدون أي نص خارجي).**

        صيغة JSON المطلوبة:
        {
            \"weeks\": [
                {
                    \"week_number\": 1,
                    \"title\": \"عنوان الأسبوع بالعربية (تفصيلي وواضح)\",
                    \"tasks\": [
                        {
                            \"day_number\": 1,
                            \"title\": \"عنوان المهمة بالعربية (محدد جداً)\",
                            \"description\": \"وصف تفصيلي للمهمة يشرح بالضبط ما يجب إنجازه والمخرجات المتوقعة\",
                            \"task_type\": \"تطبيق\",
                            \"duration\": \"45 دقيقة\"
                        }
                    ]
                }
            ]
        }

        مثال على مستوى التفصيل المطلوب (إذا كان المسار Front-End):
        الأسبوع 1: أساسيات HTML
        - اليوم 1: إنشاء هيكل صفحة HTML باستخدام html, head, body
        - اليوم 2: استخدام العناوين (h1 إلى h6) والفقرات (p) لبناء محتوى نصي
        - اليوم 3: إنشاء قوائم مرتبة (ol) وغير مرتبة (ul) مع عناصر (li)
        - اليوم 4: إنشاء جداول باستخدام table, tr, th, td
        - اليوم 5: إنشاء نموذج بسيط باستخدام form, input, label, button

        الأسبوع 2: CSS الأساسي
        - اليوم 1: ربط ملف CSS خارجي بصفحة HTML
        - اليوم 2: استخدام المحددات (selectors): element, class, id
        - اليوم 3: تنسيق الألوان والخلفيات باستخدام color و background-color
        - اليوم 4: تنسيق الخطوط باستخدام font-family, font-size, font-weight
        - اليوم 5: فهم Box Model: margin, padding, border
        ";

        try {
            $data = $this->askGroq($prompt);
            $weeks = $data['weeks'] ?? [];

            if (count($weeks) < 4) {
                $weeks = $this->fallbackWeeks($careerPath);
            }
        } catch (\Exception $e) {
            \Log::error('Roadmap generation failed: ' . $e->getMessage());
            $weeks = $this->fallbackWeeks($careerPath);
        }

        // حفظ المهام في قاعدة البيانات
        foreach ($weeks as $week) {
            foreach (($week['tasks'] ?? []) as $task) {
                RoadmapTask::create([
                    'user_id' => $user->id,
                    'career_path_id' => $careerPath->id,
                    'month_number' => 1,
                    'week_number' => $week['week_number'] ?? 1,
                    'day_number' => $task['day_number'] ?? null,
                    'title' => $task['title'] ?? 'مهمة تعليمية',
                    'description' => $task['description'] ?? null,
                    'task_type' => $task['task_type'] ?? 'تطبيق',
                    'duration' => $task['duration'] ?? '45 دقيقة',
                    'status' => 'pending',
                ]);
            }
        }

        return true;
    }
    
    public function generateCourses($careerPath)
    {
        $user = Auth::user();

        $skillNames = $this->extractSkillNames($careerPath->required_skills ?? []);
        $skillsText = !empty($skillNames) ? implode(', ', $skillNames) : 'المهارات الأساسية';


        $prompt = "
        You are a professional course recommendation assistant for an Arabic student guidance platform called Ofuq.

        Your task is to recommend exactly 4 learning resource ideas for the selected career path.

        Career path:
        {$careerPath->name}

        Career description:
        {$careerPath->description}

        Required skills:
        {$skillsText}

        Important:
        - Do not generate URLs.
        - Only suggest clear Arabic titles, platform names, search keywords, and levels.
        - The resources must directly match the career path and required skills.
        - If the career path is pharmacy-related, all resources must be pharmacy-related.
        - Do not suggest programming, UX/UI, accounting, or technology courses unless the career path clearly requires them.
        - At least 1 resource must focus on foundations.
        - At least 1 resource must focus on practical application.
        - At least 1 resource must focus on one required skill.
        - At least 1 resource must focus on career readiness or professional practice.
        - Level must be exactly one of these Arabic values: مبتدئ، متوسط، متقدم.
        - Platform must be one of: YouTube, Coursera, edX, Google.
        - All text values must be Arabic except platform names.
        - JSON keys must stay in English exactly as shown.

        Output rules:
        - Return valid JSON only.
        - Do not use Markdown.
        - Do not wrap the JSON in code fences.
        - Do not write any text before or after JSON.
        - courses must contain exactly 4 items.

        Return exactly this JSON structure:
        {
        \"courses\": [
            {
            \"title\": \"عنوان المورد التعليمي بالعربية\",
            \"platform\": \"YouTube\",
            \"level\": \"مبتدئ\",
            \"search_query\": \"عبارة بحث واضحة بالعربية\"
            }
        ]
        }
        ";

            try {
                $data = $this->askGroq($prompt);
                $courses = $data['courses'] ?? [];

                if (count($courses) < 4) {
                    $courses = $this->fallbackCourses($careerPath);
                }
            } catch (\Exception $e) {
                $courses = $this->fallbackCourses($careerPath);
            }

            foreach ($courses as $course) {
                $platform = $course['platform'] ?? 'YouTube';
                $level = $course['level'] ?? 'مبتدئ';
                $title = $course['title'] ?? 'كورس مقترح';
                $searchQuery = $course['search_query'] ?? ($careerPath->name . ' ' . $title);

                $allowedLevels = ['مبتدئ', 'متوسط', 'متقدم'];
                if (!in_array($level, $allowedLevels)) {
                    $level = 'مبتدئ';
                }

                $allowedPlatforms = ['YouTube', 'Coursera', 'edX', 'Google'];
                if (!in_array($platform, $allowedPlatforms)) {
                    $platform = 'YouTube';
                }

                RecommendedCourse::create([
                    'user_id' => $user->id,
                    'career_path_id' => $careerPath->id,
                    'title' => $title,
                    'platform' => $platform,
                    'level' => $level,
                    'url' => $this->buildCourseSearchUrl($platform, $searchQuery),
                ]);
            }
    }    
        private function generateOverview($careerPath)
    {
        $user = Auth::user();

        // استخراج المهارات
        $skillNames = $this->extractSkillNames($careerPath->required_skills ?? []);
        $skillsText = !empty($skillNames) ? implode(', ', $skillNames) : 'المهارات الأساسية';

        $prompt = "
        You are a professional learning path designer for Ofuq, an Arabic AI student guidance platform.

        Create a clear, structured overview for the following career path:

        Career path: {$careerPath->name}
        Description: {$careerPath->description}
        Required skills: {$skillsText}

        Return valid JSON only with this exact structure:
        {
            \"title\": \"عنوان الخطة بالعربية\",
            \"summary\": \"ملخص مختصر للخطة بالعربية (3-4 جمل)\",
            \"estimated_duration\": \"المدة التقديرية (مثل: 3-6 أشهر)\",
            \"level\": \"مستوى الخطة (مبتدئ، متوسط، متقدم)\",
            \"stages\": [
                {
                    \"title\": \"اسم المسار 1\",
                    \"goal\": \"هدف المسار\",
                    \"estimated_duration\": \"المدة\",
                    \"nodes\": [
                        {\"title\": \"عنوان العقدة\", \"items\": [\"نقطة 1\", \"نقطة 2\"]}
                    ],
                    \"final_project\": \"المشروع النهائي\"
                }
            ],
            \"skills\": [\"مهارة 1\", \"مهارة 2\"],
            \"outcomes\": [\"مخرجات الخطة 1\", \"مخرجات الخطة 2\"]
        }

        Important:
        - All text must be in Arabic.
        - Include exactly 3-4 stages.
        - Each stage must have 2-3 nodes.
        - Be practical and realistic.
        - Return ONLY valid JSON, no Markdown.
        ";

        try {
            $data = $this->askGroq($prompt);
            
            // تأكد من وجود البيانات المطلوبة
            if (!isset($data['stages']) || count($data['stages']) < 3) {
                return $this->createFallbackOverview($careerPath);
            }
            
        } catch (\Exception $e) {
            \Log::error('فشل توليد الـ Overview من AI: ' . $e->getMessage());
            return $this->createFallbackOverview($careerPath);
        }

        // حفظ الـ Overview
        return RoadmapOverview::create([
            'user_id' => $user->id,
            'career_path_id' => $careerPath->id,
            'title' => $data['title'] ?? ('خطة ' . $careerPath->name),
            'summary' => $data['summary'] ?? 'خطة تعليمية شاملة للمسار المهني المختار.',
            'estimated_duration' => $data['estimated_duration'] ?? '3-6 أشهر',
            'level' => $data['level'] ?? 'مبتدئ إلى متوسط',
            'stages' => $data['stages'] ?? [],
            'skills' => $data['skills'] ?? [],
            'outcomes' => $data['outcomes'] ?? [],
        ]);
    }

    private function askGroq($prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
            'Content-Type' => 'application/json',
        ])->timeout(40)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'messages' => [
                ['role' => 'system', 'content' => 'أرجع JSON صالح فقط بدون Markdown.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            throw new \Exception($response->body());
        }

        $text = $response->json()['choices'][0]['message']['content'] ?? '{}';

        return json_decode($text, true);
    }

    private function getAdviceByProgress($progress)
    {
        if ($progress >= 100) {
            return 'رائع! أنهيت خطة الشهر. يمكنك الآن الانتقال لخطة أكثر تقدمًا أو فتح المسار التالي.';
        }

        if ($progress >= 60) {
            return 'أنتِ قريبة جدًا من إنهاء الخطة. ركزي على التطبيق العملي والمراجعة.';
        }

        if ($progress >= 30) {
            return 'تقدمك جيد. حافظي على الاستمرارية وأنجزي المهام خطوة بخطوة.';
        }

        return 'ابدئي بالأسبوع الأول ولا تستعجلي. المهم هو الالتزام اليومي البسيط.';
    }

    private function buildCourseSearchUrl($platform, $query)
    {
        $encodedQuery = urlencode($query);

        return match ($platform) {
            'Coursera' => "https://www.coursera.org/search?query={$encodedQuery}",
            'edX' => "https://www.edx.org/search?q={$encodedQuery}",
            'Google' => "https://www.google.com/search?q={$encodedQuery}",
            default => "https://www.youtube.com/results?search_query={$encodedQuery}",
        };
    }

    private function fallbackCourses($careerPath)
    {
        $careerName = $careerPath->name ?? 'المسار المهني';

        $skills = is_array($careerPath->required_skills)
            ? $careerPath->required_skills
            : [];

        $skillOne = $skills[0] ?? 'المهارات الأساسية';
        $skillTwo = $skills[1] ?? 'التطبيق العملي';

        $isPharmacy = str_contains($careerName, 'صيدلي')
            || str_contains($careerName, 'صيدلة')
            || str_contains($careerName, 'دوائي')
            || str_contains($careerName, 'الأدوية');

        if ($isPharmacy) {
            return [
                [
                    'title' => 'أساسيات الصيدلة والممارسة المهنية',
                    'platform' => 'YouTube',
                    'level' => 'مبتدئ',
                    'search_query' => 'أساسيات الصيدلة الممارسة المهنية للمبتدئين',
                ],
                [
                    'title' => 'مهارات التعامل مع الأدوية والوصفات',
                    'platform' => 'YouTube',
                    'level' => 'مبتدئ',
                    'search_query' => 'قراءة الوصفات الطبية وصرف الأدوية للصيادلة',
                ],
                [
                    'title' => 'التثقيف الدوائي والتواصل مع المرضى',
                    'platform' => 'Google',
                    'level' => 'متوسط',
                    'search_query' => 'مهارات التثقيف الدوائي والتواصل مع المرضى للصيادلة',
                ],
                [
                    'title' => 'مقدمة في التيقظ الدوائي وسلامة الدواء',
                    'platform' => 'YouTube',
                    'level' => 'متوسط',
                    'search_query' => 'التيقظ الدوائي وسلامة الدواء للمبتدئين',
                ],
            ];
        }

        return [
            [
                'title' => 'مدخل إلى ' . $careerName,
                'platform' => 'YouTube',
                'level' => 'مبتدئ',
                'search_query' => 'مقدمة في ' . $careerName,
            ],
            [
                'title' => 'أساسيات ' . $skillOne,
                'platform' => 'YouTube',
                'level' => 'مبتدئ',
                'search_query' => 'تعلم ' . $skillOne . ' للمبتدئين',
            ],
            [
                'title' => 'تطبيق عملي في ' . $careerName,
                'platform' => 'Coursera',
                'level' => 'متوسط',
                'search_query' => $careerName . ' practical project',
            ],
            [
                'title' => 'تطوير مهارة ' . $skillTwo,
                'platform' => 'edX',
                'level' => 'متوسط',
                'search_query' => 'تعلم ' . $skillTwo,
            ],
        ];
    }

    private function fallbackWeeks($careerPath)
    {
        $careerName = $careerPath->name ?? 'المسار المهني';

        $skills = is_array($careerPath->required_skills)
            ? $careerPath->required_skills
            : [];

        $skillOne = $skills[0] ?? 'المهارة الأساسية الأولى';
        $skillTwo = $skills[1] ?? 'المهارة الأساسية الثانية';
        $skillThree = $skills[2] ?? 'مهارة التطبيق العملي';

        return [
            [
                'week_number' => 1,
                'title' => 'فهم المجال وتجهيز الأساسيات',
                'tasks' => [
                    [
                        'day_number' => 1,
                        'title' => 'بناء خريطة فهم لمسار ' . $careerName,
                        'description' => 'اكتب صفحة واحدة توضّح طبيعة المسار، أهم مجالات العمل، وأمثلة على وظائف مرتبطة به.',
                        'task_type' => 'تطبيق',
                        'duration' => '35 دقيقة',
                    ],
                    [
                        'day_number' => 2,
                        'title' => 'إنشاء قاموس مصطلحات أساسية',
                        'description' => 'اجمع 10 مصطلحات مهمة في المسار، واكتب معنى كل مصطلح ومثال عملي عليه.',
                        'task_type' => 'قراءة',
                        'duration' => '30 دقيقة',
                    ],
                    [
                        'day_number' => 3,
                        'title' => 'تحليل 3 نماذج عمل حقيقية',
                        'description' => 'ابحث عن 3 أمثلة أو مشاريع في هذا المجال، واكتب ماذا يميز كل مثال.',
                        'task_type' => 'مراجعة',
                        'duration' => '40 دقيقة',
                    ],
                    [
                        'day_number' => 4,
                        'title' => 'تحديد الأدوات المطلوبة للمسار',
                        'description' => 'اكتب قائمة بالأدوات أو البرامج أو المنصات التي ستحتاجها، مع سبب استخدام كل أداة.',
                        'task_type' => 'تطبيق',
                        'duration' => '30 دقيقة',
                    ],
                    [
                        'day_number' => 5,
                        'title' => 'اختبار فهم الأسبوع الأول',
                        'description' => 'اكتب 10 أسئلة قصيرة عن المجال وأجب عنها بدون الرجوع للملاحظات.',
                        'task_type' => 'اختبار',
                        'duration' => '25 دقيقة',
                    ],
                ],
            ],
            [
                'week_number' => 2,
                'title' => 'تعلّم المهارة الأولى وتطبيقها',
                'tasks' => [
                    [
                        'day_number' => 1,
                        'title' => 'فهم أساسيات ' . $skillOne,
                        'description' => 'اكتب ملخصًا عمليًا يشرح أهم 5 مفاهيم في هذه المهارة مع مثال لكل مفهوم.',
                        'task_type' => 'قراءة',
                        'duration' => '35 دقيقة',
                    ],
                    [
                        'day_number' => 2,
                        'title' => 'تطبيق تمرين صغير على ' . $skillOne,
                        'description' => 'نفّذ تمرينًا بسيطًا باستخدام المهارة، ثم اكتب ما تعلمته من التجربة.',
                        'task_type' => 'تطبيق',
                        'duration' => '45 دقيقة',
                    ],
                    [
                        'day_number' => 3,
                        'title' => 'مشاهدة شرح تطبيقي وتوثيق الخطوات',
                        'description' => 'شاهد شرحًا عمليًا، ثم اكتب الخطوات التي تم تنفيذها وكأنك تشرحها لطالب آخر.',
                        'task_type' => 'فيديو',
                        'duration' => '40 دقيقة',
                    ],
                    [
                        'day_number' => 4,
                        'title' => 'حل مشكلة صغيرة باستخدام ' . $skillOne,
                        'description' => 'اختر مشكلة بسيطة مرتبطة بالمسار وحاول حلها باستخدام المهارة الأساسية.',
                        'task_type' => 'تطبيق',
                        'duration' => '50 دقيقة',
                    ],
                    [
                        'day_number' => 5,
                        'title' => 'مراجعة نقاط الضعف',
                        'description' => 'حدد 3 نقاط لم تفهمها جيدًا هذا الأسبوع، واكتب خطة قصيرة لتحسينها.',
                        'task_type' => 'مراجعة',
                        'duration' => '25 دقيقة',
                    ],
                ],
            ],
            [
                'week_number' => 3,
                'title' => 'بناء مشروع مصغر',
                'tasks' => [
                    [
                        'day_number' => 1,
                        'title' => 'اختيار فكرة مشروع مرتبطة بالمسار',
                        'description' => 'اختر فكرة مشروع صغيرة تخدم مسار ' . $careerName . ' واكتب هدف المشروع والمشكلة التي يحلها.',
                        'task_type' => 'تطبيق',
                        'duration' => '35 دقيقة',
                    ],
                    [
                        'day_number' => 2,
                        'title' => 'تقسيم المشروع إلى مهام صغيرة',
                        'description' => 'قسّم المشروع إلى 5 أجزاء: الفكرة، المتطلبات، التنفيذ، الاختبار، التحسين.',
                        'task_type' => 'تطبيق',
                        'duration' => '30 دقيقة',
                    ],
                    [
                        'day_number' => 3,
                        'title' => 'تنفيذ الجزء الأول من المشروع',
                        'description' => 'ابدأ بتنفيذ أول جزء عملي من المشروع، واحفظ النتيجة كمخرج واضح.',
                        'task_type' => 'تطبيق',
                        'duration' => '60 دقيقة',
                    ],
                    [
                        'day_number' => 4,
                        'title' => 'إضافة تحسين باستخدام ' . $skillTwo,
                        'description' => 'أضف تحسينًا واضحًا للمشروع يعتمد على مهارة ثانية من المهارات المطلوبة.',
                        'task_type' => 'تطبيق',
                        'duration' => '50 دقيقة',
                    ],
                    [
                        'day_number' => 5,
                        'title' => 'توثيق المشروع',
                        'description' => 'اكتب وصفًا للمشروع: الهدف، الأدوات المستخدمة، خطوات التنفيذ، وما تعلمته.',
                        'task_type' => 'مراجعة',
                        'duration' => '35 دقيقة',
                    ],
                ],
            ],
            [
                'week_number' => 4,
                'title' => 'تقييم وتحسين وتجهيز المخرجات',
                'tasks' => [
                    [
                        'day_number' => 1,
                        'title' => 'اختبار المشروع أو المخرج النهائي',
                        'description' => 'اختبر المشروع أو العمل الذي أنجزته، واكتب 5 ملاحظات تحسين.',
                        'task_type' => 'اختبار',
                        'duration' => '35 دقيقة',
                    ],
                    [
                        'day_number' => 2,
                        'title' => 'تحسين جودة المشروع',
                        'description' => 'طبّق تحسينين على المشروع بناءً على ملاحظات الاختبار.',
                        'task_type' => 'تطبيق',
                        'duration' => '50 دقيقة',
                    ],
                    [
                        'day_number' => 3,
                        'title' => 'إنشاء ملخص Portfolio',
                        'description' => 'اكتب فقرة احترافية قصيرة تشرح المشروع أو الإنجاز لإضافته في ملفك الشخصي.',
                        'task_type' => 'تطبيق',
                        'duration' => '30 دقيقة',
                    ],
                    [
                        'day_number' => 4,
                        'title' => 'تقييم ذاتي للمهارات',
                        'description' => 'قيّم نفسك من 1 إلى 5 في: ' . $skillOne . '، ' . $skillTwo . '، ' . $skillThree . '، واكتب سبب التقييم.',
                        'task_type' => 'مراجعة',
                        'duration' => '30 دقيقة',
                    ],
                    [
                        'day_number' => 5,
                        'title' => 'خطة الشهر القادم',
                        'description' => 'حدد 3 مهارات أو مواضيع تريد تطويرها الشهر القادم بناءً على نتائج هذا الشهر.',
                        'task_type' => 'تطبيق',
                        'duration' => '30 دقيقة',
                    ],
                ],
            ],
        ];
    }

    public function toggleTask(RoadmapTask $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        if ($task->status === 'completed') {
            $task->update([
                'status' => 'pending',
                'completed_at' => null,
            ]);
        } else {
            $task->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return back();
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
}
