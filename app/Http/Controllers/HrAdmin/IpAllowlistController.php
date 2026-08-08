<?php

namespace App\Http\Controllers\HrAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class IpAllowlistController extends Controller
{
    public function index(): View
    {
        return view('hr-admin.ip-allowlists.index', ['ipAllowlists' => collect([])]);
    }
}
