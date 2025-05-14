<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Variante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticuloController extends Controller
{
    // Listar todos los artículos con sus variantes
    public function index()
    {
        $articulos = Articulo::with('variantes')
            ->where('user_id', auth()->id())
            ->orderBy('nombre')
            ->get();

        return response()->json($articulos);
    }

    // Ver un solo artículo (con variantes)
    public function show($id)
    {
        $articulo = Articulo::with('variantes')
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        return response()->json($articulo);
    }

    // Crear artículo (opcionalmente con variantes)
    public function store(Request $request)
    {
        $request->validate([
            'nombre'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'categoria'    => 'nullable|string|max:100',
            'precio_base'  => 'nullable|numeric|min:0',
            'variantes'    => 'nullable|array',
            'variantes.*.sku'        => 'nullable|string|max:100',
            'variantes.*.stock'      => 'nullable|integer|min:0',
            'variantes.*.precio'     => 'nullable|numeric|min:0',
            'variantes.*.atributos'  => 'nullable|array',
        ]);

        $articulo = Articulo::create([
            'user_id'      => auth()->id(),
            'nombre'       => $request->nombre,
            'descripcion'  => $request->descripcion,
            'categoria'    => $request->categoria,
            'precio_base'  => $request->precio_base,
        ]);

        if ($request->filled('variantes')) {
            foreach ($request->variantes as $v) {
                $articulo->variantes()->create($v);
            }
        }

        return response()->json([
            'message'  => 'Artículo creado correctamente',
            'articulo' => $articulo->load('variantes'),
        ], 201);
    }

    // Actualizar datos del artículo (sin tocar variantes)
    public function update(Request $request, $id)
    {
        $articulo = Articulo::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'nombre'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'categoria'    => 'nullable|string|max:100',
            'precio_base'  => 'nullable|numeric|min:0',
        ]);

        $articulo->update($request->only(['nombre','descripcion','categoria','precio_base']));

        return response()->json([
            'message'  => 'Artículo actualizado correctamente',
            'articulo' => $articulo,
        ]);
    }

    // Eliminar artículo y todas sus variantes
    public function destroy($id)
    {
        $articulo = Articulo::where('user_id', auth()->id())->findOrFail($id);
        $articulo->variantes()->delete();
        $articulo->delete();

        return response()->json([
            'message'     => 'Artículo y variantes eliminados',
            'deleted_id'  => $id,
            'last_update' => now()->timestamp * 1000,
        ]);
    }

    //
    //  — MÉTODOS AUXILIARES DE PRECIO —
    //

    private function redondearPrecio($valor)
    {
        // redondeo a múltiplo de 500
        return round($valor / 500) * 500;
    }

    private function calcularPrecios($costo)
    {
        if ($costo >= 25000) {
            $efectivo    = $costo * 1.74;
            $transferencia = $costo * 1.89;
        } elseif ($costo < 15750) {
            $efectivo    = $costo * 1.8;
            $transferencia = $costo * 1.95;
        } else {
            $efectivo    = $costo * 1.74;
            $transferencia = $costo * 1.89;
        }

        return [
            'precio_efectivo'     => $this->redondearPrecio($efectivo),
            'precio_transferencia'=> $this->redondearPrecio($transferencia),
        ];
    }

    // Recalcular precios masivamente para todos los artículos
    public function recalcularPreciosMasivamente()
    {
        $articulos = Articulo::where('user_id', auth()->id())->get();

        foreach ($articulos as $art) {
            $precios = $this->calcularPrecios($art->precio_base ?? 0);
            $art->update([
                'precio_base'           => $art->precio_base,
                'precio_efectivo'       => $precios['precio_efectivo'],
                'precio_transferencia'  => $precios['precio_transferencia'],
            ]);
        }

        return response()->json([
            'message'  => 'Precios recalculados correctamente',
            'articulos'=> Articulo::with('variantes')->where('user_id',auth()->id())->get(),
        ]);
    }

    // Aumentar costo_base un % y recalcular precios
    public function aumentarCostoOriginal(Request $request)
    {
        $request->validate(['porcentaje'=>'required|numeric']);
        $pct = $request->porcentaje / 100;

        $articulos = Articulo::where('user_id', auth()->id())->get();
        foreach ($articulos as $art) {
            $nuevo = round(($art->precio_base ?? 0) * (1 + $pct));
            $precios = $this->calcularPrecios($nuevo);
            $art->update([
                'precio_base'          => $nuevo,
                'precio_efectivo'      => $precios['precio_efectivo'],
                'precio_transferencia' => $precios['precio_transferencia'],
            ]);
        }

        return response()->json([
            'message'  => "Costos incrementados un {$request->porcentaje}%",
            'articulos'=> Articulo::with('variantes')->where('user_id',auth()->id())->get(),
        ]);
    }

    //
    //  — ENDPOINTS DE SINCRONIZACIÓN / “ÚLTIMA ACTUALIZACIÓN” —
    //

    // Última actualización de cualquier artículo
    public function ultimaActualizacionArticulos()
    {
        $last = Articulo::where('user_id',auth()->id())->max('updated_at');
        return response()->json(['last_update'=>strtotime($last)]);
    }

    // Artículos cambiados desde un timestamp (ms)
    public function articulosActualizadosDesde(Request $request)
    {
        $ts = $request->query('timestamp');
        if (!$ts || !is_numeric($ts)) {
            return response()->json(['error'=>'Timestamp inválido'],400);
        }
        $from = \Carbon\Carbon::createFromTimestampMs($ts);
        $data = Articulo::with('variantes')
            ->where('user_id',auth()->id())
            ->where('updated_at','>',$from)
            ->get();
        $last = Articulo::where('user_id',auth()->id())->max('updated_at') ?? now();
        return response()->json([
            'data'        => $data,
            'last_update' => strtotime($last),
        ]);
    }

    // Última actualización de VARIANTES
    public function ultimaActualizacionVariantes()
    {
        $last = Variante::whereHas('articulo', fn($q)=>$q->where('user_id',auth()->id()))
            ->max('updated_at');
        return response()->json(['last_update'=>strtotime($last)]);
    }

    // Variantes cambiadas desde un timestamp (ms)
    public function variantesActualizadasDesde(Request $request)
    {
        $ts = $request->query('timestamp');
        if (!$ts || !is_numeric($ts)) {
            return response()->json(['error'=>'Timestamp inválido'],400);
        }
        $from = \Carbon\Carbon::createFromTimestampMs($ts);
        $data = Variante::with('articulo')
            ->whereHas('articulo', fn($q)=>$q->where('user_id',auth()->id()))
            ->where('updated_at','>',$from)
            ->get();
        $last = Variante::whereHas('articulo', fn($q)=>$q->where('user_id',auth()->id()))
            ->max('updated_at') ?? now();
        return response()->json([
            'data'        => $data,
            'last_update' => strtotime($last),
        ]);
    }
}