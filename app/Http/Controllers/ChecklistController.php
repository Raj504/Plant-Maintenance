<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    public function index()
    {
        return view('pm.checklists.index', ['checklists' => Checklist::withCount('items')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('pm.checklists.form', ['checklist' => new Checklist(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $checklist = Checklist::create(['name' => $data['name'], 'description' => $data['description'] ?? null, 'is_active' => $request->boolean('is_active', true)]);
        $this->syncItems($checklist, $data['items']);
        flash_toast('success', 'Checklist created.');
        return redirect()->route('pm.checklists.index');
    }

    public function edit(Checklist $checklist)
    {
        $checklist->load('items');
        return view('pm.checklists.form', compact('checklist'));
    }

    public function update(Request $request, Checklist $checklist)
    {
        $data = $this->validated($request, $checklist);
        $checklist->update(['name' => $data['name'], 'description' => $data['description'] ?? null, 'is_active' => $request->boolean('is_active', true)]);
        $this->syncItems($checklist, $data['items']);
        flash_toast('success', 'Checklist updated.');
        return redirect()->route('pm.checklists.index');
    }

    public function destroy(Checklist $checklist)
    {
        if (\App\Models\PmPlan::where('checklist_id', $checklist->id)->exists()) {
            flash_toast('error', 'Checklist is used by PM plans. Deactivate it instead.');
            return back();
        }
        $checklist->delete();
        flash_toast('success', 'Checklist deleted.');
        return back();
    }

    protected function validated(Request $request, ?Checklist $checklist = null): array
    {
        return $request->validate([
            'name'        => 'required|string|max:150|unique:pm_checklists,name,' . ($checklist?->id ?? 'NULL'),
            'description' => 'nullable|string|max:255',
            'items'       => 'required|array|min:1',
            'items.*'     => 'nullable|string|max:255',
        ]);
    }

    /** Replace the items with the submitted list (blank rows ignored). */
    protected function syncItems(Checklist $checklist, array $items): void
    {
        $checklist->items()->delete();
        $order = 1;
        foreach ($items as $text) {
            if (trim((string) $text) !== '') {
                $checklist->items()->create(['item_text' => trim($text), 'sort_order' => $order++]);
            }
        }
    }
}
