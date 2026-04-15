<?php namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::orderBy('event_date', 'desc')->paginate(12);
        return view('events', compact('events'));
    }

    public function show($id)
    {
        $event = Event::findOrFail($id);
        return view('event_details', compact('event'));
    }
}
