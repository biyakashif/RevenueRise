<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SliderImage;
use App\Events\SliderUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SliderController extends Controller
{
    public function index()
    {
        $desktopImages = SliderImage::desktop()->ordered()->get();
        $mobileImages = SliderImage::mobile()->ordered()->get();

        return Inertia::render('Admin/SliderManager', [
            'desktopImages' => $desktopImages,
            'mobileImages' => $mobileImages
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'type' => 'required|in:desktop,mobile',
            'sort_order' => 'nullable|integer|min:0'
        ]);

        $imagePath = $request->file('image')->store('sliders', 'public');

        SliderImage::create([
            'title' => $request->title,
            'image_path' => $imagePath,
            'image_path_mobile' => null,
            'type' => $request->type,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true
        ]);

        // Broadcast slider update to all users
        broadcast(new SliderUpdated())->toOthers();

        return redirect()->back()->with('success', 'Slider image uploaded successfully.');
    }

    public function update(Request $request, SliderImage $sliderImage)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean'
        ]);

        $data = [
            'title' => $request->title,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => $request->is_active ?? true
        ];

        if ($request->hasFile('image')) {
            // Delete old image
            if ($sliderImage->image_path) {
                Storage::disk('public')->delete($sliderImage->image_path);
            }
            $data['image_path'] = $request->file('image')->store('sliders', 'public');
        }

        $sliderImage->update($data);

        // Broadcast slider update to all users
        broadcast(new SliderUpdated())->toOthers();

        return redirect()->back()->with('success', 'Slider image updated successfully.');
    }

    public function destroy(SliderImage $sliderImage)
    {
        // Delete image from storage
        if ($sliderImage->image_path) {
            Storage::disk('public')->delete($sliderImage->image_path);
        }

        $sliderImage->delete();

        // Broadcast slider update to all users
        broadcast(new SliderUpdated())->toOthers();

        return redirect()->back()->with('success', 'Slider image deleted successfully.');
    }
}
