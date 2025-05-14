<?php

namespace App\Http\Controllers;

use App\Models\AtributoDefinido;
use Illuminate\Http\Request;

class AtributoDefinidoController extends Controller
{
    // Lista todos los atributos definidos del usuario
    public function index()
    {
        $userId = auth()->id();
        $atributos = AtributoDefinido::where('user_id', $userId)->get();
        return response()->json($atributos);
    }

    // Crea un nuevo atributo
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50'
        ]);

        $atributo = AtributoDefinido::create([
            'user_id' => auth()->id(),
            'nombre'  => $request->nombre,
        ]);

        return response()->json([
            'message'  => 'Atributo creado',
            'atributo' => $atributo
        ], 201);
    }

    // Actualiza el nombre de un atributo
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:50'
        ]);

        $atributo = AtributoDefinido::where('user_id', auth()->id())
            ->findOrFail($id);

        $atributo->update(['nombre' => $request->nombre]);

        return response()->json([
            'message'  => 'Atributo actualizado',
            'atributo' => $atributo
        ]);
    }

    // Elimina un atributo y todos sus valores
    public function destroy($id)
    {
        $atributo = AtributoDefinido::where('user_id', auth()->id())
            ->findOrFail($id);

        // Si tenés FK ON DELETE CASCADE en la migración no hace falta borrarlos manualmente,
        // pero por las dudas:
        $atributo->valores()->delete();

        $atributo->delete();

        return response()->json([
            'message' => 'Atributo eliminado'
        ]);
    }
}