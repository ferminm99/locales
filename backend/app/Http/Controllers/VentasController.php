<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Variante;
use App\Models\Cliente;
use App\Models\Facturacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Traits\ActualizaMetaTrait;
use Carbon\Carbon;

class VentasController extends Controller
{
    use ActualizaMetaTrait;

    // Registrar una o varias ventas (uno por variante solicitada)
    public function registrarVenta(Request $request)
    {
        $request->validate([
            'cliente.nombre'            => 'required|string|max:100',
            'cliente.apellido'          => 'required|string|max:100',
            'cliente.cuit'              => 'nullable|string|max:20',
            'cliente.cbu'               => 'nullable|string|max:30',
            'fecha'                     => 'required|date',
            'forma_pago'                => 'required|in:efectivo,transferencia',
            'productos'                 => 'required|array|min:1',
            'productos.*.variante_id'   => 'required|integer|exists:variantes,id',
            'productos.*.precio'        => 'required|numeric|min:0',
            'productos.*.costo_original'=> 'required|numeric|min:0',
        ]);

        // 1) Manejo del cliente (find o create)
        $c = $request->input('cliente');
        $cliente = Cliente::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'cuit'    => $c['cuit'] ?? null,
                'cbu'     => $c['cbu']  ?? null,
            ],
            [
                'user_id' => auth()->id(),
                'nombre'  => $c['nombre'],
                'apellido'=> $c['apellido'],
                'cuit'    => $c['cuit'] ?? null,
                'cbu'     => $c['cbu']  ?? null,
            ]
        );

        $ventasRegistradas = [];

        // 2) Crear una venta por cada variante pedida
        foreach ($request->productos as $prod) {
            $variante = Variante::findOrFail($prod['variante_id']);

            // Descontar stock (1 unidad por venta)
            if ($variante->stock > 0) {
                $variante->decrement('stock');
            }

            $venta = Venta::create([
                'user_id'        => auth()->id(),
                'cliente_id'     => $cliente->id,
                'variante_id'    => $variante->id,
                'precio'         => $prod['precio'],
                'costo_original' => $prod['costo_original'],
                'fecha'          => $request->fecha,
                'forma_pago'     => $request->forma_pago,
            ]);

            // Cargar relaciones para el frontend
            $venta->load('variante.articulo', 'cliente');
            $ventasRegistradas[] = $venta;
        }

        return response()->json([
            'ventas'      => $ventasRegistradas,
            'last_update' => now()->timestamp * 1000,
        ], 201);
    }

    // Obtener todas las ventas del usuario
    public function obtenerVentas()
    {
        $ventas = Venta::with('variante.articulo', 'cliente')
            ->where('user_id', auth()->id())
            ->get();

        return response()->json($ventas);
    }

    // Actualizar una venta existente
    public function update(Request $request, $id)
    {
        $request->validate([
            'precio'         => 'required|numeric|min:0',
            'costo_original' => 'required|numeric|min:0',
            'fecha'          => 'required|date',
            'forma_pago'     => 'required|in:efectivo,transferencia',
        ]);

        $venta = Venta::where('user_id', auth()->id())->findOrFail($id);

        $venta->update([
            'precio'         => $request->precio,
            'costo_original' => $request->costo_original,
            'fecha'          => $request->fecha,
            'forma_pago'     => $request->forma_pago,
        ]);

        $venta->touch();
        $venta->load('variante.articulo', 'cliente');

        return response()->json([
            'venta'       => $venta,
            'last_update' => now()->timestamp * 1000,
        ]);
    }

    // Eliminar una venta y reponer stock
    public function destroy($id)
    {
        $venta = Venta::where('user_id', auth()->id())->findOrFail($id);

        // Reponer 1 unidad al stock de la variante
        $venta->variante->increment('stock');

        $venta->delete();

        return response()->json([
            'message'     => 'Venta eliminada',
            'deleted_id'  => $id,
            'last_update' => now()->timestamp * 1000,
        ]);
    }

    // Cambiar de variante en una venta
    public function cambiarBombacha(Request $request)
    {
        $request->validate([
            'venta_id'           => 'required|integer|exists:ventas,id',
            'original_variante_id' => 'required|integer|exists:variantes,id',
            'nueva_variante_id'    => 'required|integer|exists:variantes,id',
            'precio'             => 'required|numeric|min:0',
            'costo_original'     => 'required|numeric|min:0',
            'fecha'              => 'required|date',
            'forma_pago'         => 'required|in:efectivo,transferencia',
        ]);

        $venta = Venta::where('user_id', auth()->id())->findOrFail($request->venta_id);

        // Reponer stock de la variante original
        Variante::findOrFail($request->original_variante_id)->increment('stock');

        // Descontar stock de la variante nueva
        Variante::findOrFail($request->nueva_variante_id)->decrement('stock');

        // Actualizar la venta
        $venta->update([
            'variante_id'    => $request->nueva_variante_id,
            'precio'         => $request->precio,
            'costo_original' => $request->costo_original,
            'fecha'          => $request->fecha,
            'forma_pago'     => $request->forma_pago,
        ]);

        $venta->touch();
        $venta->load('variante.articulo', 'cliente');

        return response()->json([
            'venta'       => $venta,
            'last_update' => now()->timestamp * 1000,
        ]);
    }

    // Última factura creada
    public function obtenerUltimaFacturacion()
    {
        $ultima = Facturacion::latest('fecha_facturacion')->first();
        return response()->json($ultima);
    }

    // Guardar facturaciones asociadas a ventas
    public function guardarFacturaciones(Request $request)
    {
        $request->validate([
            'ventas'   => 'required|array',
            'ventas.*.id' => 'required|integer|exists:ventas,id',
        ]);

        $ultimaVentaId = null;
        foreach ($request->ventas as $v) {
            $fact = Facturacion::create([
                'venta_id'         => $v['id'],
                'fecha_facturacion'=> now(),
            ]);
            $ultimaVentaId = $fact->venta_id;
        }

        $lastUpdate = Venta::where('user_id', auth()->id())->latest('updated_at')->first()?->updated_at->timestamp * 1000;

        return response()->json([
            'message'         => 'Facturaciones guardadas',
            'ultima_venta_id' => $ultimaVentaId,
            'last_update'     => $lastUpdate,
        ]);
    }

    // Última actualización de ventas
    public function ultimaActualizacionVentas()
    {
        $last = Venta::where('user_id', auth()->id())->max('updated_at');
        return response()->json(['last_update' => strtotime($last)]);
    }

    // Obtener ventas actualizadas desde un timestamp
    public function ventasActualizadasDesde(Request $request)
    {
        $ts = $request->query('timestamp');
        if (!$ts || !is_numeric($ts)) {
            return response()->json(['error' => 'Timestamp inválido'], 400);
        }

        $from = Carbon::createFromTimestamp(floor($ts / 1000));
        $ventas = Venta::with('variante.articulo', 'cliente')
            ->where('user_id', auth()->id())
            ->where('updated_at', '>', $from)
            ->get();

        $lastUpdate = Venta::where('user_id', auth()->id())->max('updated_at') ?? now();

        return response()->json([
            'data'        => $ventas,
            'count'       => $ventas->count(),
            'last_update' => strtotime($lastUpdate),
        ]);
    }
}