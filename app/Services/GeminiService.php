<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = 'gemini-2.0-flash-lite';
    }

    /**
     * إرسال طلب إلى Gemini API
     */
    private function sendRequest($prompt)
    {
        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 2048,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            Log::error('Gemini API Error: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 1. محلل التقييم (Assessment Analyzer)
     * يحلل إجابات المستخدم ويستخرج نقاط القوة والضعف والتخصصات المناسبة
     */
    public function analyzeAssessment($answers)
    {
        $prompt = "أنت خبير في تحليل مهارات الطلاب وتوجيههم للمسار المهني المناسب.
        
        بناءً على إجابات المستخدم التالية، قم بتحليلها واستخرج:
        1. نقاط القوة (3 نقاط على الأقل)
        2. نقاط الضعف (3 نقاط على الأقل)
        3. أفضل 3 تخصصات تقنية تناسبه مع نسبة توافق لكل منها
        
        إجابات المستخدم:
        " . json_encode($answers, JSON_UNESCAPED_UNICODE) . "
        
        أجب باللغة العربية فقط، وكن محدداً ومفيداً.
        استخدم تنسيق JSON التالي:
        {
            \"strengths\": [\"نقطة قوة 1\", \"نقطة قوة 2\", \"نقطة قوة 3\"],
            \"weaknesses\": [\"نقطة ضعف 1\", \"نقطة ضعف 2\", \"نقطة ضعف 3\"],
            \"recommended_tracks\": [
                {\"name\": \"تخصص 1\", \"match_percentage\": 95},
                {\"name\": \"تخصص 2\", \"match_percentage\": 85},
                {\"name\": \"تخصص 3\", \"match_percentage\": 75}
            ],
            \"overall_level\": \"مبتدئ/متوسط/متقدم\",
            \"summary\": \"ملخص قصير عن تحليل المستخدم\"
        }";

        return $this->sendRequest($prompt);
    }

    /**
     * 2. صانع المسار الدراسي (Roadmap Generator)
     * يولد مساراً تعليمياً مخصصاً بناءً على مستوى المستخدم والتخصص المختار
     */
    public function generateRoadmap($level, $track, $userName = 'الطالب')
    {
        $prompt = "أنت خبير في تصميم الخطط الدراسية لتخصصات التقنية.
        
        قم بإنشاء خطة دراسية مخصصة لمستخدم مستواه \"{$level}\" ويريد التخصص في \"{$track}\".
        
        أجب باللغة العربية فقط.
        
        استخدم تنسيق JSON التالي:
        {
            \"title\": \"عنوان الخطة\",
            \"description\": \"وصف مختصر للخطة\",
            \"duration_months\": 6,
            \"steps\": [
                {
                    \"step_number\": 1,
                    \"title\": \"عنوان المرحلة\",
                    \"description\": \"وصف المرحلة\",
                    \"duration_weeks\": 4,
                    \"resources\": [
                        {\"name\": \"اسم المصدر\", \"type\": \"course/book/video\", \"url\": \"رابط اختياري\"}
                    ]
                }
            ],
            \"tips\": [\"نصيحة 1\", \"نصيحة 2\"]
        }";

        $result = $this->sendRequest($prompt);
        
        // محاولة استخراج JSON من النص
        if ($result) {
            $jsonStart = strpos($result, '{');
            $jsonEnd = strrpos($result, '}');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($result, $jsonStart, $jsonEnd - $jsonStart + 1);
                return json_decode($jsonString, true);
            }
        }
        
        return null;
    }

    /**
     * 3. مدرب التركيز (Focus Buddy)
     * يعطي نصائح ذكية للمستخدم بناءً على وقت الدراسة
     */
    public function getFocusTip($studyDurationMinutes, $completedSessions = 0, $energyLevel = 'medium')
    {
        $prompt = "أنت مدرب تركيز ذكي. بناءً على المعلومات التالية:
        - مدة الدراسة الحالية: {$studyDurationMinutes} دقيقة
        - عدد الجلسات المكتملة اليوم: {$completedSessions}
        - مستوى الطاقة: {$energyLevel}
        
        قدم نصيحة قصيرة ومفيدة (لا تتجاوز سطرين) باللغة العربية تشجعه على الاستمرار أو يأخذ استراحة أو يحسن تركيزه.
        
        فقط النصيحة، بدون مقدمات أو تحيات.";

        return $this->sendRequest($prompt);
    }

    /**
     * إنشاء مستخدم جديد بناءً على تحليل التقييم
     */
    public function generateUserFromAssessment($answers)
    {
        $prompt = "بناءً على إجابات المستخدم التالية، قم بإنشاء ملف شخصي له:
        
        الإجابات: " . json_encode($answers, JSON_UNESCAPED_UNICODE) . "
        
        استخرج أو أنشئ:
        1. مستوى مناسب (مبتدئ/متوسط/متقدم)
        2. وصف قصير للمستخدم (جملة واحدة)
        3. اقتراح لاسم مستخدم (username) من الإجابة أو عشوائي
        
        استخدم تنسيق JSON:
        {
            \"level\": \"مبتدئ/متوسط/متقدم\",
            \"bio\": \"وصف المستخدم\",
            \"suggested_username\": \"اسم_مقترح\"
        }";

        $result = $this->sendRequest($prompt);
        
        if ($result) {
            $jsonStart = strpos($result, '{');
            $jsonEnd = strrpos($result, '}');
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonString = substr($result, $jsonStart, $jsonEnd - $jsonStart + 1);
                return json_decode($jsonString, true);
            }
        }
        
        return [
            'level' => 'beginner',
            'bio' => 'مستخدم جديد في منصة أفق',
            'suggested_username' => 'user_' . rand(1000, 9999)
        ];
    }
}