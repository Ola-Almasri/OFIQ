<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\AiAdvisory;
use Illuminate\Support\Facades\Auth;

class AiController extends Controller
{
    public function checkGroq()
    {
        return $this->askAi('groq');
    }

    public function checkGemini()
    {
        return $this->askAi('gemini');
    }

    public function showRoadmap()
    {
        return view('roadmap');
    }

    public function getOrGenerateRoadmap()
    {
        $user = Auth::user();

        $existingRoadmap = AiAdvisory::where('user_id', $user->id)
            ->where('type', 'roadmap')
            ->latest()
            ->first();

        if ($existingRoadmap) {
            return response()->json([
                'status' => 'Fetched from Database',
                'data' => json_decode($existingRoadmap->ai_response)
            ]);
        }

        return $this->generateRoadmap();
    }

    public function generateRoadmap()
    {
        $user = Auth::user();

        $assessment = \App\Models\Assessment::where('user_id', $user->id)->first();

        if (!$assessment) {
            return response()->json([
                'status' => 'error',
                'message' => 'يرجى إكمال التقييم أولاً لتوليد خارطة الطريق.'
            ], 404);
        }

        $userProfile = "
        المستخدم: {$user->name}
        التخصص: {$assessment->major}
        المرحلة الدراسية: {$assessment->education_level}
        المهارات الحالية: {$assessment->skills}
        الاهتمامات: {$assessment->interests}
        الهدف المهني: {$assessment->goal}
        مستوى الإنجليزية: {$assessment->english_level}
        المسار المختار: {$assessment->selected_career}
        ";

                $prompt = "أنت مستشار مهني ذكي في منصة أفق.

        بناءً على بيانات الطالب التالية:
        $userProfile

        المطلوب:
        أرجع JSON صالح فقط بدون Markdown وبدون شرح خارجي.

        صيغة JSON المطلوبة بالضبط:
        {
        \"welcome\": \"جملة ترحيبية ملهمة قصيرة\",
        \"career\": \"اسم المسار المهني الأنسب\",
        \"match_percentage\": 85,
        \"duration\": \"6 أشهر\",
        \"reason\": \"سبب اختيار هذا المسار بناءً على مهارات الطالب واهتماماته\",
        \"roadmap\": [
            {
            \"title\": \"المرحلة الأولى\",
            \"description\": \"شرح مختصر وواضح للمرحلة\",
            \"duration\": \"شهر\"
            },
            {
            \"title\": \"المرحلة الثانية\",
            \"description\": \"شرح مختصر وواضح للمرحلة\",
            \"duration\": \"شهرين\"
            },
            {
            \"title\": \"المرحلة الثالثة\",
            \"description\": \"شرح مختصر وواضح للمرحلة\",
            \"duration\": \"شهرين\"
            },
            {
            \"title\": \"المرحلة الرابعة\",
            \"description\": \"شرح مختصر وواضح للمرحلة\",
            \"duration\": \"شهر\"
            }
        ],
        \"required_skills\": [
            {
            \"name\": \"اسم المهارة\",
            \"progress\": 70
            }
        ],
        \"courses\": [
            {
            \"title\": \"اسم الكورس\",
            \"platform\": \"YouTube\",
            \"level\": \"Beginner\"
            }
        ],
        \"advice\": \"نصيحة عملية ومباشرة للطالب\"
        }

        الشروط:
        - اللغة العربية فقط.
        - لا تكتب أي نص خارج JSON.
        - لا تستخدم ```json.
        - نسبة التوافق رقم فقط بين 50 و 95.
        - roadmap يجب أن تحتوي 4 مراحل فقط.
        - required_skills يجب أن تحتوي 5 مهارات.
        - courses يجب أن تحتوي 3 كورسات.";

                return $this->askAiForRoadmap($prompt);
    }

    /*
    private function askAiForRoadmap($prompt)
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

            $result = $response->json();
            $aiContent = $result['choices'][0]['message']['content'] ?? '';

            if (empty($aiContent)) {
                throw new \Exception('استجابة فارغة من الذكاء الاصطناعي');
            }

            $aiContent = $this->cleanJsonText($aiContent);
            $decoded = json_decode($aiContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON غير صالح: ' . json_last_error_msg());
            }

            AiAdvisory::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'type'    => 'roadmap',
                ],
                [
                    'prompt_asked' => $prompt,
                    'ai_response'  => json_encode($decoded, JSON_UNESCAPED_UNICODE),
                ]
            );

            return response()->json([
                'status' => 'Success',
                'data' => $decoded
            ]);

        } catch (\Exception $e) {
            $fallbackRoadmap = $this->getFallbackRoadmap();

            return response()->json([
                'status' => 'Fallback',
                'data' => $fallbackRoadmap,
                'details' => $e->getMessage()
            ]);
        }
    }
    */

private function askAiForRoadmap($prompt)
{
    $response = $this->attemptRequest('openai', $prompt);
    
    if (!$response['success']) {
        $response = $this->attemptRequest('groq', $prompt);
    }

    if ($response['success']) {
        $decoded = $response['data'];
        
        AiAdvisory::updateOrCreate(
            ['user_id' => Auth::id(), 'type' => 'roadmap'],
            ['prompt_asked' => $prompt, 'ai_response' => json_encode($decoded, JSON_UNESCAPED_UNICODE)]
        );

        return response()->json(['status' => 'Success', 'data' => $decoded]);
    }

    return response()->json([
        'status' => 'Fallback', 
        'data' => $this->getFallbackRoadmap(),
        'details' => 'Both AI providers failed'
    ]);
}

private function attemptRequest($provider, $prompt)
{
    try {
        $config = ($provider === 'openai') ? [
            'url' => 'https://api.openai.com/v1/chat/completions',
            'key' => env('OPENAI_API_KEY'),
            'model' => 'gpt-4o'
        ] : [
            'url' => 'https://api.groq.com/openai/v1/chat/completions',
            'key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile')
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $config['key'],
            'Content-Type'  => 'application/json',
        ])->timeout(120)->post($config['url'], [ // زيادة الـ Timeout إلى 120 ثانية
            'model' => $config['model'],
            'messages' => [
                ['role' => 'system', 'content' => 'أنت مستشار مهني ذكي. أرجع JSON صالح فقط.'],
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if ($response->successful()) {
            $content = $response->json()['choices'][0]['message']['content'];
            return ['success' => true, 'data' => json_decode($this->cleanJsonText($content), true)];
        }
    } catch (\Exception $e) {
        return ['success' => false];
    }
    return ['success' => false];
}

    private function cleanJsonText($text)
    {
        $text = trim($text);
        $text = preg_replace('/```json|```/', '', $text);

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            throw new \Exception('لم يتم العثور على JSON في الرد');
        }

        return substr($text, $start, $end - $start + 1);
    }

    /**
     * خارطة طريق افتراضية في حال فشل الاتصال بـ API
     */
    private function getFallbackRoadmap()
    {
        return [
            'welcome' => 'مرحباً بك في رحلتك التعليمية! ابدأ خطوة بخطوة.',
            'career' => 'Software Developer',
            'match_percentage' => 75,
            'duration' => '6 أشهر',
            'reason' => 'تم اختيار هذا المسار بناءً على المهارات والاهتمامات المدخلة.',
            'roadmap' => [
                [
                    'title' => 'تقوية الأساسيات',
                    'description' => 'تعلم أساسيات البرمجة والمنطق وحل المشكلات.',
                    'duration' => 'شهر'
                ],
                [
                    'title' => 'تعلم أدوات تطوير الويب',
                    'description' => 'تعلم HTML و CSS و JavaScript وأساسيات بناء الواجهات.',
                    'duration' => 'شهرين'
                ],
                [
                    'title' => 'تعلم قواعد البيانات',
                    'description' => 'تعلم SQL وتصميم قواعد البيانات وربطها بالتطبيقات.',
                    'duration' => 'شهر'
                ],
                [
                    'title' => 'بناء مشاريع عملية',
                    'description' => 'أنشئ مشاريع حقيقية وضعها في معرض أعمالك.',
                    'duration' => 'شهرين'
                ],
            ],
            'required_skills' => [
                ['name' => 'حل المشكلات', 'progress' => 70],
                ['name' => 'أساسيات البرمجة', 'progress' => 65],
                ['name' => 'قواعد البيانات', 'progress' => 55],
                ['name' => 'بناء المشاريع', 'progress' => 50],
                ['name' => 'التعلم الذاتي', 'progress' => 80],
            ],
            'courses' => [
                [
                    'title' => 'Programming Fundamentals',
                    'platform' => 'YouTube',
                    'level' => 'Beginner'
                ],
                [
                    'title' => 'Web Development Basics',
                    'platform' => 'YouTube',
                    'level' => 'Beginner'
                ],
                [
                    'title' => 'Build Real Projects',
                    'platform' => 'Udemy',
                    'level' => 'Intermediate'
                ],
            ],
            'advice' => 'خصص وقتاً يومياً للتطبيق العملي، ولا تنتقل لمرحلة جديدة قبل بناء مشروع صغير.'
        ];
    }

    /**
     * دالة عامة للاستعلام عن Groq أو Gemini
     */
    private function askAi($provider)
    {
        $prompt = "أنا بيسان، مهندسة برمجيات أعمل على مشروع أفق. أعطني جملة واحدة ملهمة لمشروعي اليوم.";

        try {
            if ($provider == 'groq') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                    'Content-Type'  => 'application/json',
                ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                ]);

                if ($response->failed()) {
                    throw new \Exception($response->body());
                }

                $data = $response->json();
                $aiResponse = $data['choices'][0]['message']['content'] ?? 'No response';

            } else {
                $apiKey = env('GEMINI_API_KEY');

                if (!$apiKey) {
                    throw new \Exception('GEMINI_API_KEY is missing from .env');
                }

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])->timeout(30)->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
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

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $aiResponse = $data['candidates'][0]['content']['parts'][0]['text'];
                } else {
                    throw new \Exception(json_encode($data, JSON_UNESCAPED_UNICODE));
                }
            }

            AiAdvisory::updateOrCreate(
                [
                    'user_id' => Auth::id() ?? 1,
                    'type'    => $provider . '_message',
                ],
                [
                    'prompt_asked' => $prompt,
                    'ai_response'  => $aiResponse,
                ]
            );

            return response()->json([
                'status'  => 'Success from ' . $provider,
                'ai_says' => $aiResponse
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'   => 'Error',
                'provider' => $provider,
                'details'  => $e->getMessage()
            ], 500);
        }
    }
}