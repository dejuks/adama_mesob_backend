<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Woreda;
use Illuminate\Http\Request;

class WoredaController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => Woreda::with('subcity')->latest()->paginate(10)
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'subcity_id' => 'required|exists:subcities,id'
        ]);

        $woreda = Woreda::create($data);

        return response()->json([
            'success' => true,
            'data' => $woreda
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'success' => true,
            'data' => Woreda::with('subcity')->findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $woreda = Woreda::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string',
            'subcity_id' => 'required|exists:subcities,id'
        ]);

        $woreda->update($data);

        return response()->json([
            'success' => true,
            'data' => $woreda
        ]);
    }

    public function destroy($id)
    {
        Woreda::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}
