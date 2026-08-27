<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Office;
use Illuminate\Http\Request;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $divisions = Division::query()
            ->with('office')
            ->when($q, fn($qr) => $qr->where('name', 'like', "%{$q}%")
                ->orWhereHas('office', fn($oq) => $oq->where('name', 'like', "%{$q}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $offices = Office::orderBy('name')->get();

        return view('super.divisions.index', compact('divisions', 'q', 'offices'));
    }

    public function create()
    {
        $offices = Office::orderBy('name')->get();
        return view('super.divisions.create', compact('offices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'name' => 'required|string|max:255',
        ]);

        Division::create($data);

        return redirect()->route('super.divisions.index')->with('status', 'Division created.');
    }

    public function edit(Division $division)
    {
        $offices = Office::orderBy('name')->get();
        return view('super.divisions.edit', compact('division', 'offices'));
    }

    public function update(Request $request, Division $division)
    {
        $data = $request->validate([
            'office_id' => 'required|exists:offices,id',
            'name' => 'required|string|max:255',
        ]);

        $division->update($data);

        return redirect()->route('super.divisions.index')->with('status', 'Division updated.');
    }

    public function destroy(Division $division)
    {
        $division->delete();

        return redirect()->route('super.divisions.index')->with('status', 'Division deleted.');
    }
}
