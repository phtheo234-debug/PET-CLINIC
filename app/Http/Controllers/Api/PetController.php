<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use Illuminate\Http\Request;

class PetController extends Controller
{
    public function index()
    {
        return response()->json(Pet::with('owner')->get(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => 'required|exists:owners,id',
            'name' => 'required|string|max:255',
            'species' => 'required|string',
            'weight' => 'required|numeric|min:0.1',
        ]);

        $pet = Pet::create($validated);
        return response()->json(['message' => 'Thêm hồ sơ thú cưng thành công', 'data' => $pet], 201);
    }

    public function show($id)
    {
        return response()->json(Pet::with('owner')->findOrFail($id), 200);
    }

    public function update(Request $request, $id)
    {
        $pet = Pet::findOrFail($id);
        $pet->update($request->all());
        return response()->json(['message' => 'Cập nhật thành công', 'data' => $pet], 200);
    }

    public function destroy($id)
    {
        Pet::destroy($id);
        return response()->json(['message' => 'Đã xóa hồ sơ thú cưng'], 200);
    }
}
