<?php namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Event;
use App\Models\News;

class HomeController extends Controller
{
    public function index()
    {
        $events = Event::where('event_date', '>=', now()->toDateString())
            ->orderBy('event_date', 'asc')
            ->limit(2)
            ->get();

        $news = News::where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        return view('index', compact('events', 'news'));
    }
}
