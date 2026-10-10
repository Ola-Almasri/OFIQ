<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Assessment;
use App\Models\CareerPath;
use App\Models\AiAdvisory;
use App\Models\RoadmapTask;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $assessment = Assessment::where('user_id', $user->id)->first();

        if (!$assessment) {
            return redirect()->route('assessment');
        }

        $selectedCareerPath = CareerPath::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$selectedCareerPath && $assessment->selected_career) {
            $selectedCareerPath = CareerPath::where('user_id', $user->id)
                ->where('name', $assessment->selected_career)
                ->first();

            if ($selectedCareerPath) {
                CareerPath::where('user_id', $user->id)->update([
                    'status' => 'locked'
                ]);

                $selectedCareerPath->update([
                    'status' => 'active'
                ]);
            }
        }

        $aiRoadmap = AiAdvisory::where('user_id', $user->id)
            ->where('type', 'roadmap')
            ->latest()
            ->first();

        $aiData = $aiRoadmap ? json_decode($aiRoadmap->ai_response, true) : null;

        $careerName = $selectedCareerPath->name ?? ($assessment->selected_career ?? 'لم يتم اختيار مسار بعد');
        $matchPercentage = $selectedCareerPath->match_percentage ?? 75;
        $matchReason = $selectedCareerPath->reason ?? 'مبني على مهاراتك واهتماماتك الحالية.';
        $requiredSkills = $selectedCareerPath->required_skills ?? ['التواصل', 'حل المشكلات', 'التعلم المستمر'];
        $salaryRange = 'غير محدد حالياً';
        $aiAdvice = $aiData['advice'] ?? 'استمري في تطوير مهاراتك خطوة بخطوة حسب المسار المختار.';

        $tasks = collect();
        $todayTasks = collect();
        $totalTasks = 0;
        $completedTasks = 0;
        $remainingTasks = 0;
        $overallProgress = 0;

        if ($selectedCareerPath) {
            $tasks = RoadmapTask::where('user_id', $user->id)
                ->where('career_path_id', $selectedCareerPath->id)
                ->orderBy('week_number')
                ->orderBy('day_number')
                ->get();

            $totalTasks = $tasks->count();
            $completedTasks = $tasks->where('status', 'completed')->count();
            $remainingTasks = $totalTasks - $completedTasks;

            $overallProgress = $totalTasks > 0
                ? round(($completedTasks / $totalTasks) * 100)
                : 0;

            $todayTasks = RoadmapTask::where('user_id', $user->id)
                ->where('career_path_id', $selectedCareerPath->id)
                ->where('status', 'pending')
                ->orderBy('week_number')
                ->orderBy('day_number')
                ->limit(4)
                ->get();
        }

        return view('dashboard', compact(
            'user',
            'assessment',
            'selectedCareerPath',
            'aiData',
            'careerName',
            'matchPercentage',
            'matchReason',
            'requiredSkills',
            'salaryRange',
            'aiAdvice',
            'tasks',
            'todayTasks',
            'totalTasks',
            'completedTasks',
            'remainingTasks',
            'overallProgress'
        ));
    }
}