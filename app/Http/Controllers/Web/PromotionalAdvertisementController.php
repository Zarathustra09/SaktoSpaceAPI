<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PromotionalAdvertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PromotionalAdvertisementController extends Controller
{
    public function index()
    {
        $ads = PromotionalAdvertisement::orderBy('start_date')->orderBy('end_date')->orderByDesc('created_at')->paginate(10);
        return view('promotional_advertisements.index', compact('ads'));
    }

    public function create()
    {
        return view('promotional_advertisements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,jpg,png,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('promotional-ads', 'public');
        }

        PromotionalAdvertisement::create($data);

        return redirect()->route('promotional-advertisements.index')->with('success', 'Created.');
    }

    public function show(PromotionalAdvertisement $promotionalAdvertisement)
    {
        return view('promotional_advertisements.show', compact('promotionalAdvertisement'));
    }

    public function edit(PromotionalAdvertisement $promotionalAdvertisement)
    {
        return view('promotional_advertisements.edit', compact('promotionalAdvertisement'));
    }

    public function update(Request $request, PromotionalAdvertisement $promotionalAdvertisement)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->hasFile('image')) {
            if ($promotionalAdvertisement->image) {
                Storage::disk('public')->delete($promotionalAdvertisement->image);
            }
            $data['image'] = $request->file('image')->store('promotional-ads', 'public');
        }

        $promotionalAdvertisement->update($data);

        return redirect()->route('promotional-advertisements.index')->with('success', 'Updated.');
    }

    public function destroy(PromotionalAdvertisement $promotionalAdvertisement)
    {
        if ($promotionalAdvertisement->image) {
            Storage::disk('public')->delete($promotionalAdvertisement->image);
        }
        $promotionalAdvertisement->delete();
        return redirect()->route('promotional-advertisements.index')->with('success', 'Deleted.');
    }
}
