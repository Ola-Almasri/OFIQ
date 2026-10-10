<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;  // ✅ أضف هذا السطر
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }


    public function updateAvatar(Request $request)
    {
        try {
            $request->validate([
                'avatar' => 'required|string'
            ]);
            
            $user = Auth::user();
            
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            $avatarPath = $this->saveBase64Image($request->avatar);
            
            if (!$avatarPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ في حفظ الصورة'
                ], 400);
            }
            
            $user->avatar = $avatarPath;
            $user->updated_at = now(); // ✅ مهم جداً
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الصورة بنجاح',
                'avatar' => asset('storage/' . $avatarPath . '?v=' . time())
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $user->name = $request->name ?? $user->name;
        $user->save();
        
        if ($user->assessment) {
            $user->assessment->update([
                'university' => $request->university ?? $user->assessment->university,
                'major' => $request->major ?? $user->assessment->major,
                'gpa' => $request->gpa ?? $user->assessment->gpa,
            ]);
        }
        
        return response()->json(['success' => true]);
    }

    private function saveBase64Image($base64)
    {
        $imageData = explode(',', $base64);
        if (count($imageData) < 2) {
            return null;
        }
        
        $imageData = base64_decode($imageData[1]);
        $imageName = time() . '_' . uniqid() . '.png';
        $path = 'uploads/avatars/' . $imageName;
        
        Storage::disk('public')->put($path, $imageData);
        
        return $path;
    }
}