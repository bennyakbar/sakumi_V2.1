<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCategoryRequest;
use App\Http\Requests\Master\UpdateCategoryRequest;
use App\Models\StudentCategory;
use App\Services\PermanentDeleteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
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

    public function destroy(Request $request, StudentCategory $category): RedirectResponse
    {
        $permanentDelete = app(PermanentDeleteService::class);
        if ($permanentDelete->isRequested($request)) {
            $actor = $request->user();
            if (!$actor || !$permanentDelete->isAllowedFor($actor)) {
                return back()->withErrors(['delete' => __('message.permanent_delete_not_allowed')]);
            }
            if (!$permanentDelete->hasValidConfirmation($request)) {
                return back()->withErrors(['delete' => __('message.permanent_delete_confirmation_invalid')]);
            }

            $blocking = $permanentDelete->onlyBlockingDependencies(
                $permanentDelete->dependencyCounts(PermanentDeleteService::ENTITY_CATEGORY, (int) $category->id)
            );
            if (!empty($blocking)) {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_CATEGORY, $category, $blocking, 'blocked');
                return back()->withErrors([
                    'delete' => __('message.permanent_delete_blocked_dependencies', [
                        'details' => $permanentDelete->formatDependencies($blocking),
                    ]),
                ]);
            }

            try {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_CATEGORY, $category, [], 'attempt');
                $category->forceDelete();
            } catch (QueryException $e) {
                $permanentDelete->logSnapshot($actor, PermanentDeleteService::ENTITY_CATEGORY, $category, [], 'failed', $e->getMessage());
                return back()->withErrors(['delete' => __('message.permanent_delete_failed_fk')]);
            }

            return redirect()->route('master.categories.index')
                ->with('success', __('message.category_permanently_deleted'));
        }

        if ($category->students()->exists()) {
            return back()->with('error', __('message.category_has_students'));
        }

        $category->delete();

        return redirect()->route('master.categories.index')
            ->with('success', __('message.category_deleted'));
    }

}
