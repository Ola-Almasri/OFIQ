<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserTask;
use Illuminate\Support\Facades\Auth;

class UserTaskController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $tasks = UserTask::where('user_id', $user->id)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('user-tasks', compact('tasks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        UserTask::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'تم إضافة المهمة بنجاح');
    }

    public function toggle(UserTask $task)
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

        return redirect()->back();
    }

    public function destroy(UserTask $task)
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }

        $task->delete();

        return redirect()->back()->with('success', 'تم حذف المهمة بنجاح');
    }
}