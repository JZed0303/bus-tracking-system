<?php
namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show()
    {
        return view('company.profile.show');
    }

    public function update(Request $request)
    {
        return back()->with('success', 'Profile updated.');
    }
}
