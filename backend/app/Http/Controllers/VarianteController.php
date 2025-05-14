<?php

namespace App\Http\Controllers;

use App\Models\Variante;
use Illuminate\Http\Request;

class VarianteController extends Controller
{
    // Actualizar datos de una variante
    public function update(Request $request, $id)
    {
        $request->validate([
            'precio'    => 'nullable|numeric|min:0',
            'stock'     => 'nullable|integer|min:0',
            'atributos' => 'nullable|array',
        ]);

        $variante = Variante::findOrFail($id);
        $variante->update($request->only(['precio','stock','atributos']));

        return response()->json([
            'message'  => 'Variante actualizada',
            'variante' => $variante,
        ]);
    }

    // Eliminar una variante
    public function destroy($id)
    {
        $variante = Variante::findOrFail($id);
        $variante->delete();

        return response()->json([
            'message'    => 'Variante eliminada',
            'deleted_id' => $id,
        ]);
    }

    // Ajustar stock (sumar o restar)
    public function ajustarStock(Request $request, $id)
    {
        $request->validate([
            'cantidad' => 'required|integer',
        ]);

        $variante = Variante::findOrFail($id);
        $variante->stock += $request->cantidad;
        $variante->save();

        return response()->json([
            'message' => 'Stock ajustado',
            'stock'   => $variante->stock,
        ]);
    }
}