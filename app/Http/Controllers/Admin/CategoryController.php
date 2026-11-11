<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        return $this->page(new Category(['type' => $request->type ?? 'asset']));
    }

    public function create()
    {
        return redirect()->route('admin.categories.index');
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));
        flash_toast('success', 'Category added.');
        return redirect()->route('admin.categories.index', ['type' => $request->type]);
    }

    public function edit(Category $category)
    {
        return $this->page($category);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));
        flash_toast('success', 'Category updated.');
        return redirect()->route('admin.categories.index', ['type' => $category->type]);
    }

    public function destroy(Category $category)
    {
        $category->update(['is_active' => false]);
        flash_toast('success', 'Category deactivated.');
        return back();
    }

    protected function page(Category $category)
    {
        return view('admin.masters.categories', [
            'categories' => Category::where('type', $category->type)->orderBy('name')->get(),
            'category'   => $category,
            'type'       => $category->type,
        ]);
    }

    protected function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'type'        => ['required', Rule::in(array_keys(Category::TYPES))],
            'name'        => ['required', 'string', 'max:150', Rule::unique('pm_categories')->where('type', $request->type)->ignore($category?->id)],
            'description' => 'nullable|string|max:255',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        return $data;
    }
}
