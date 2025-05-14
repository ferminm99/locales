<?php
namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ActualizaMetaTrait;

class ClienteController extends Controller
{
    use ActualizaMetaTrait;

    // Listar sólo tus clientes
    public function index()
    {
        $clientes = Cliente::where('user_id', auth()->id())->get();
        return response()->json($clientes);
    }

    // Crear cliente
    public function store(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'cuit'     => 'nullable|string|max:20',
            'cbu'      => 'nullable|string|max:30',
        ]);

        $cliente = Cliente::create([
            'user_id'  => auth()->id(),
            'nombre'   => $request->nombre,
            'apellido' => $request->apellido,
            'cuit'     => $request->cuit,
            'cbu'      => $request->cbu,
        ]);

        return response()->json($cliente,201);
    }

    // Actualizar cliente
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'cuit'     => 'nullable|string|max:20',
            'cbu'      => 'nullable|string|max:30',
        ]);

        $cliente = Cliente::where('user_id', auth()->id())->findOrFail($id);
        $cliente->update($request->only(['nombre','apellido','cuit','cbu']));

        return response()->json($cliente);
    }

    // Eliminar cliente
    public function destroy($id)
    {
        $cliente = Cliente::where('user_id', auth()->id())->findOrFail($id);
        $cliente->delete();

        $ultimo = Cliente::where('user_id', auth()->id())->latest()->first();
        if ($ultimo) $ultimo->touch();

        return response()->json([
            'message'     => 'Cliente eliminado',
            'deleted_id'  => $id,
            'last_update' => now()->timestamp * 1000,
        ]);
    }

    // Clientes actualizados desde timestamp
    public function clientesActualizadosDesde(Request $request)
    {
        $ts = $request->query('timestamp');
        if (!$ts || !is_numeric($ts)) {
            return response()->json(['error'=>'Falta timestamp'],400);
        }
        $from = \Carbon\Carbon::createFromTimestamp(floor($ts/1000));
        $data = Cliente::where('user_id', auth()->id())
            ->where('updated_at','>',$from)->get();
        $last = Cliente::where('user_id', auth()->id())->max('updated_at') ?? now();
        return response()->json([
            'data'        => $data,
            'count'       => $data->count(),
            'last_update' => strtotime($last),
        ]);
    }

    // Última actualización global de clientes
    public function ultimaActualizacionClientes()
    {
        $last = Cliente::where('user_id', auth()->id())->max('updated_at');
        return response()->json(['last_update'=> strtotime($last)]);
    }
}