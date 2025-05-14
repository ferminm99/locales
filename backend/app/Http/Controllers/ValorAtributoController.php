<?php

namespace App\Http\Controllers;

use App\Models\AtributoDefinido;
use App\Models\ValorAtributo;
use Illuminate\Http\Request;

class ValorAtributoController extends Controller
{
    // Lista todos los valores de un atributo
    public function index($atributoId)
    {
        $atributo = AtributoDefinido::where('user_id', auth()->id())
            ->findOrFail($atributoId);

        return response()->json($atributo->valores);
    }

    // Crea un nuevo valor para un atributo
    public function store(Request $request, $atributoId)
    {
        $request->validate([
            'valor' => 'required|string|max:100'
        ]);

        $atributo = AtributoDefinido::where('user_id', auth()->id())
            ->findOrFail($atributoId);

        $valor = $atributo->valores()->create([
            'valor' => $request->valor
        ]);

        return response()->json([
            'message' => 'Valor creado',
            'valor'   => $valor
        ], 201);
    }

    // Actualiza un valor existente
    public function update(Request $request, $id)
    {
        $request->validate([
            'valor' => 'required|string|max:100'
        ]);

        $valor = ValorAtributo::findOrFail($id);

        // Verificamos que el atributo pertenezca al usuario
        if ($valor->atributo->user_id !== auth()->id()) {
            abort(403);
        }

        $valor->update(['valor' => $request->valor]);

        return response()->json([
            'message' => 'Valor actualizado',
            'valor'   => $valor
        ]);
    }

    // Elimina un valor
    public function destroy($id)
    {
        $valor = ValorAtributo::findOrFail($id);

        if ($valor->atributo->user_id !== auth()->id()) {
            abort(403);
        }

        $valor->delete();

        return response()->json([
            'message' => 'Valor eliminado'
        ]);
    }
}