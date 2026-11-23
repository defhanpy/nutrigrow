<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\ChatMessage;
use App\Services\NutritionChatService;
use Illuminate\Http\Request;

class NutritionChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil daftar balita yang bisa diakses user untuk dropdown konteks
        $children = match ($user->role->value) {
            'kader' => Child::where('facility_id', $user->facility_id)->orderBy('full_name')->get(),
            'ortu' => $user->guardianships()->with('child')->get()->pluck('child')->filter(),
            'nakes', 'admin_puskesmas' => Child::whereHas('facility', function ($q) use ($user) {
                $q->where('id', $user->facility_id)->orWhere('parent_id', $user->facility_id);
            })->orderBy('full_name')->take(20)->get(),
            default => collect(),
        };

        $selectedChildId = $request->query('child');
        $selectedChild = $children->firstWhere('id', (int) $selectedChildId) ?? $children->first();

        // Ambil riwayat pesan user
        $messages = ChatMessage::where('user_id', $user->id)
            ->latest('id')
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        return view('shared.nutrition.chat', compact('user', 'children', 'selectedChild', 'messages'));
    }

    public function send(Request $request, NutritionChatService $chatService)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'child_id' => 'nullable|exists:children,id',
        ]);

        $user = $request->user();
        $child = !empty($validated['child_id']) ? Child::find($validated['child_id']) : null;

        $reply = $chatService->reply($user, $validated['message'], $child);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'user_message' => $validated['message'],
                'reply' => $reply,
                'created_at' => now()->translatedFormat('H:i'),
            ]);
        }

        return redirect()->route('nutrition.chat', ['child' => $child?->id]);
    }

    public function reset(Request $request)
    {
        ChatMessage::where('user_id', $request->user()->id)->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('nutrition.chat');
    }
}
