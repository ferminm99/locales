<?php
namespace App\Http\Controllers;

use App\Models\CompraCalendario;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ActualizaMetaTrait;

class CalendarioController extends Controller
{
    use ActualizaMetaTrait;

    // Listar sólo tus compras agendadas
    public function index()
    {
        $c = CompraCalendario::where('user_id', auth()->id())->get();
        return response()->json($c);
    }

    // Crear
    public function store(Request $request)
    {
        $request->validate([
            'nombre_persona'=>'required|string|max:100',
            'fecha'=>'required|date',
            'hora_inicio'=>'required|date_format:H:i',
            'hora_fin'=>'required|date_format:H:i',
            'descripcion'=>'nullable|string',
        ]);

        $compra = CompraCalendario::create([
            'user_id'       => auth()->id(),
            'nombre_persona'=> $request->nombre_persona,
            'fecha'         => $request->fecha,
            'hora_inicio'   => $request->hora_inicio,
            'hora_fin'      => $request->hora_fin,
            'descripcion'   => $request->descripcion,
        ]);

        return response()->json($compra,201);
    }

    // Actualizar
    public function update(Request $request, $id)
    {
        $c = CompraCalendario::where('user_id', auth()->id())->findOrFail($id);
        $c->update($request->only([
            'nombre_persona','fecha','hora_inicio','hora_fin','descripcion'
        ]));
        return response()->json($c);
    }

    // Eliminar
    public function destroy($id)
    {
        $c = CompraCalendario::where('user_id', auth()->id())->findOrFail($id);
        $c->delete();
        $last = CompraCalendario::where('user_id', auth()->id())->latest()->first();
        if ($last) $last->touch();
        return response()->json([
            'message'=>'Compra eliminada',
            'deleted_id'=>$id,
            'last_update'=>now()->timestamp*1000,
        ]);
    }

    // Actualizados desde
    public function calendarioActualizadosDesde(Request $request)
    {
        $ts = $request->query('timestamp');
        if (!$ts||!is_numeric($ts)) {
            return response()->json(['error'=>'Falta timestamp'],400);
        }
        $from = \Carbon\Carbon::createFromTimestamp(floor($ts/1000));
        $data = CompraCalendario::where('user_id', auth()->id())
            ->where('updated_at','>',$from)->get();
        $last = CompraCalendario::where('user_id', auth()->id())->max('updated_at') ?? now();
        return response()->json([
            'data'=>$data,
            'count'=>$data->count(),
            'last_update'=>strtotime($last),
        ]);
    }

    // Última actualización
    public function ultimaActualizacionCalendario()
    {
        $last = CompraCalendario::where('user_id', auth()->id())->max('updated_at');
        return response()->json(['last_update'=>strtotime($last)]);
    }
}