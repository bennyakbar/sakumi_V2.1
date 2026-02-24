<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCategoryRequest;
use App\Http\Requests\Master\UpdateCategoryRequest;
use App\Models\StudentCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = StudentCategory::latest()->paginate(15);

        return view('master.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('master.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        StudentCategory::create($request->validated());

        return redirect()->route('master.categories.index')
            ->with('success', __('message.category_created'));
    }

    public function edit(StudentCategory $category): View
    {
        return view('master.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, StudentCategory $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('master.categories.index')
            ->with('success', __('message.category_updated'));
    }

    public function destroy(StudentCategory $category): RedirectResponse
    {
        if ($category->students()->exists()) {
            return back()->with('error', __('message.category_has_students'));
        }

        $category->delete();

        return redirect()->route('master.categories.index')
            ->with('success', __('message.category_deleted'));
    }

}
