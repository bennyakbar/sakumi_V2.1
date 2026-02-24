<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreClassRequest;
use App\Http\Requests\Master\UpdateClassRequest;
use App\Models\SchoolClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $classes = SchoolClass::withCount('students')->latest()->paginate(15);

        return view('master.classes.index', compact('classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('master.classes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClassRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        SchoolClass::create($validated);

        return redirect()->route('master.classes.index')
            ->with('success', __('message.class_created'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SchoolClass $class): View
    {
        return view('master.classes.edit', compact('class'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClassRequest $request, SchoolClass $class): RedirectResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active');

        $class->update($validated);

        return redirect()->route('master.classes.index')
            ->with('success', __('message.class_updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SchoolClass $class): RedirectResponse
    {
        if ($class->students()->count() > 0) {
            return back()->with('error', __('message.class_has_students'));
        }

        $class->delete();

        return redirect()->route('master.classes.index')
            ->with('success', __('message.class_deleted'));
    }

}
