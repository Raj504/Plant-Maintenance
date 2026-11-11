<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\History;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $history = History::with(['user', 'model'])
            ->when($request->user_id, fn ($q, $v) => $q->where('user_id', $v))
            ->when($request->model_type, fn ($q, $v) => $q->where('model_type', 'like', '%' . $v))
            ->when($request->action, fn ($q, $v) => $q->where('action', $v))
            ->when($request->from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')->paginate(50)->withQueryString();

        return view('admin.audit', [
            'history' => $history,
            'users'   => User::orderBy('name')->get(),
            'types'   => History::select('model_type')->distinct()->pluck('model_type')->map(fn ($t) => class_basename($t))->sort()->values(),
            'actions' => History::select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
