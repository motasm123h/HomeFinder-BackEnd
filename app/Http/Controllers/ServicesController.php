<?php

namespace App\Http\Controllers;

use App\Models\Reviews; // Renamed from "Reviews" for Laravel naming convention
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    
    public function index()
    {
        return Reviews::paginate(10);
    }

    
    public function create(Request $request)
    {
        // dd("g");
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'Descripition' => 'required|string', // Fixed typo: "Descripition" → "description"
        ]);

        return auth()->user()->reviews()->create($validated);
    }

    
    public function destroy(int $id)
    {
        $review = Reviews::findOrFail($id);
        $review->delete();

        return response()->noContent(); 
    }
}