<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $pageTitle = 'Announcements';
        $announcements = Announcement::latest()->paginate(10);

        return view('admin.announcements.index', compact('pageTitle', 'announcements'));
    }

    public function create()
    {
        $pageTitle = 'Create Announcement';
        return view('admin.announcements.create', compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'content'     => 'required|string',
            'button_text' => 'nullable|string|max:255',
        ]);

        Announcement::create([
            'title'       => $request->title,
            'content'     => $request->content,
            'button_text' => $request->button_text,
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ]);
        $notify[] = ['success', 'Announcement created successfully'];
        return redirect()->route('admin.announcements.index')->withNotify($notify);
    }

    public function edit($id)
    {
        $pageTitle = 'Edit Announcement';
        $announcement = Announcement::findOrFail($id);

        return view('admin.announcements.edit', compact('pageTitle', 'announcement'));
    }

    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'content'     => 'required|string',
            'button_text' => 'nullable|string|max:255',
        ]);

        $announcement->update([
            'title'       => $request->title,
            'content'     => $request->content,
            'button_text' => $request->button_text,
            'is_active'   => $request->has('is_active') ? 1 : 0,
        ]);
        $notify[] = ['success', 'Announcement updated successfully'];
        return redirect()->route('admin.announcements.index')->withNotify($notify);
    }

    public function status($id)
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->is_active = !$announcement->is_active;
        $announcement->save();
        $notify[] = ['success', 'Announcement status updated successfully'];
        return redirect()->route('admin.announcements.index')->withNotify($notify);
    }
}