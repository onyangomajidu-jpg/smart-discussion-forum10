<?php

namespace App\Http\Controllers;

use App\Services\ContentManagementService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private ContentManagementService $cms) {}

    // editPost()
    public function update(Request $request, int $id)
    {
        $data = $request->validate(['body' => 'required|string']);

        try {
            $post = $this->cms->editPost($id, $data);
            return response()->json(['success' => true, 'post' => $post]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    // deletePost()
    public function destroy(int $id)
    {
        try {
            $this->cms->deletePost($id);
            return response()->json(['success' => true]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
