<?php

namespace App\Http\Controllers;

use App\Models\ModelOption;
use Illuminate\Http\Request;

class ModelOptionController extends Controller
{
    public function index()
    {
        $modelOptions = ModelOption::where('active', true)
            ->with('apiProvider')
            ->get();

        return response()->json($modelOptions);
    }
}
