<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $offices = Office::query()
            ->when($q, fn($qr) => $qr->where('name', 'like', "%{$q}%")
                ->orWhere('office_code', 'like', "%{$q}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('super.offices.index', compact('offices', 'q'));
    }

    public function create()
    {
        return view('super.offices.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'office_code' => 'required|string|max:50|unique:offices,office_code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        Office::create($data);

        return redirect()->route('super.offices.index')->with('status', 'Office created.');
    }

    public function edit(Office $office)
    {
        return view('super.offices.edit', compact('office'));
    }

    public function update(Request $request, Office $office)
    {
        $data = $request->validate([
            'office_code' => 'required|string|max:50|unique:offices,office_code,' . $office->id,
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        $office->update($data);

        return redirect()->route('super.offices.index')->with('status', 'Office updated.');
    }
}
