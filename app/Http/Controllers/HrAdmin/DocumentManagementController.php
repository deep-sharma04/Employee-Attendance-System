<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DocumentManagementController extends Controller
{
    public function index(): View
    {
        return view('hr-admin.documents.index', ['documents' => collect([])]);
    }
}
