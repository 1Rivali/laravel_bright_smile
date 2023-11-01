<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function createComment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => ['required', 'max:255'],
            'evaluation' => ['required']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->messages(),
            ], 400);
        }

        try {
            $validated = $validator->validated();
            $user = Auth::guard('sanctum')->user();
            $patient = Patient::where('user_id', $user->id)->first();
            $comment = new Comment([
                'content' => $validated['content'],
                'evaluation' => (float)$validated['evaluation']
            ]);
            $comment->patient()->associate($patient);
            $comment->save();
            return response()->json([
                'success' => true,
                'message' => 'Comment created successfully',
                'data' => $comment,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    public function getAllComments()
    {
        try {
            $comments = Comment::with('patient.user')->get();
            if (sizeof($comments) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No comments found',
                ], 204);
            }
            return response()->json([
                'success' => true,
                'message' => 'Comments fetched successfully',
                'data' => $comments,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
