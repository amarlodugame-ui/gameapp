<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use Illuminate\Http\Request;

class HomeBannerController extends Controller
{
    public function index()
    {
        $pageTitle = 'Home Banners';
        $banners = HomeBanner::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.home_banners.index', compact('pageTitle', 'banners'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        try {
            $imageUrl = $this->uploadBannerImage($request->file('image'));

            $nextSort = ((int) HomeBanner::max('sort_order')) + 1;

            HomeBanner::create([
                'image'      => $imageUrl,
                'route_name' => 'user.home',
                'title'      => null,
                'is_active'  => $request->has('is_active') ? 1 : 0,
                'sort_order' => $nextSort,
            ]);

           $notify[] = ['success', 'Banner uploaded successfully'];
           return redirect()->back()->withNotify($notify);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Banner upload failed');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $banner = HomeBanner::findOrFail($id);

        try {
            $data = [
                'is_active' => $request->has('is_active') ? 1 : 0,
            ];

            if ($request->hasFile('image')) {
                $this->deleteBannerImage($banner->image);
                $data['image'] = $this->uploadBannerImage($request->file('image'));
            }

            $banner->update($data);

              $notify[] = ['success', 'Banner updated successfully'];
                return redirect()->back()->withNotify($notify);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Banner update failed');
        }
    }

    public function destroy($id)
    {
        $banner = HomeBanner::findOrFail($id);

        try {
            $this->deleteBannerImage($banner->image);
            $banner->delete();

            $this->reorderSort();

           $notify[] = ['success', 'Banner deleted successfully'];
           return redirect()->back()->withNotify($notify);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Banner delete failed');
        }
    }

    private function uploadBannerImage($file)
    {
        $directory = public_path('assets/home-banners');

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fileName = 'banner_' . time() . '_' . uniqid() . '.' . $extension;

        $file->move($directory, $fileName);

        $relativePath = 'assets/home-banners/' . $fileName;

        return url($relativePath);
    }

    private function deleteBannerImage($imageUrl)
    {
        if (!$imageUrl) {
            return;
        }

        $path = parse_url($imageUrl, PHP_URL_PATH);

        if (!$path) {
            return;
        }

        $fullPath = public_path(ltrim($path, '/'));

        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    private function reorderSort()
    {
        $banners = HomeBanner::orderBy('sort_order')->orderBy('id')->get();

        foreach ($banners as $index => $banner) {
            $banner->update([
                'sort_order' => $index + 1,
            ]);
        }
    }
}