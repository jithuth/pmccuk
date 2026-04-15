<?php namespace App\Http\Controllers;

use App\Models\TeamMember;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('cat', 'current');
        $members = TeamMember::where('category', $category)->orderBy('order_no')->get();
        return view('team', compact('members', 'category'));
    }
}
