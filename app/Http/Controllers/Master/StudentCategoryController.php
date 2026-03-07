<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\StudentCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $categories = StudentCategory::latest()->paginate(10);
        return view('master.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('master.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:student_categories',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        StudentCategory::create($validated);

        return redirect()->route('master.categories.index')
            ->with('success', 'Student Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(StudentCategory $studentCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StudentCategory $category): View // Using $category to match route model binding if not strictly typed in route, but let's stick to convention. 
    // Actually Route::resource uses {category} parameter by default for 'categories' resource.
    {
        return view('master.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, StudentCategory $category)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:student_categories,code,' . $category->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'discount_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $category->update($validated);

        return redirect()->route('master.categories.index')
            ->with('success', 'Student Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StudentCategory $category)
    {
        if ($category->students()->exists()) {
            return back()->with('error', 'Cannot delete category because it has associated students.');
        }

        $category->delete();

        return redirect()->route('master.categories.index')
            ->with('success', 'Student Category deleted successfully.');
    }
}
