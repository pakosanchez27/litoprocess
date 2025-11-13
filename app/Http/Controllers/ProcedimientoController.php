<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;



class ProcedimientoController extends Controller
{
    public function create()
    {
        return view('procedimientos.create');
    }

    public function store(Request $request)
    {

        // dd($request->all());    
        // 1) Validación base (campos de texto principales)
        //  $request->validate([
        //      'tipo_documento'         => 'required',
        //      'area_codigo'            => 'required',
        //      'consecutivo'             => 'required',
        //      'fecha_emision'          => 'required|date|date_format:Y-m-d',
        //      'elaboro_area'           => 'required|string|max:255',
        //      // OJO: este es el campo de "chips" de la parte superior (CSV).
        //      // Si no quieres que sea obligatorio, déjalo nullable:
        //      'referencias_normativas' => 'nullable|string|max:2000',
        //      'referencias_internas'   => 'nullable|string|max:2000',

        //      'codigo'                 => 'required',
        //      'titulo'                 => 'required',

        //      'elaboro_nombre'         => 'required|string|max:255',
        //      'reviso_nombre'          => 'required|string|max:255',
        //      'autorizo_nombre'        => 'required|string|max:255',
        //      'elaboro_cargo'          => 'required|string|max:255',
        //      'reviso_cargo'           => 'required|string|max:255',
        //      'autorizo_cargo'         => 'required|string|max:255',

        //      'objetivo'               => 'required|string|max:1200',
        //      'alcance'                => 'required|string|max:1200',
        //      // "Políticas" ahora via JSON hidden politicas_json, así que este puede ser nullable.
        //      // 'politicas'              => 'nullable|string|max:2000',


        //  // Listas varias:
        //     'definiciones'           => 'nullable|string', // JSON
        //     'desarrollo_json'        => 'nullable|string', // JSON pasos


        //      // Diagrama
        //      'diagrama_png'           => 'nullable|string',

        //  ]);

        // dd($request->all());

        // Helpers
        $decodeJson = static function (?string $json) {
            if ($json === null || trim($json) === '') return [];
            try {
                $arr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                return is_array($arr) ? $arr : [];
            } catch (\Throwable $e) {
                return [];
            }
        };
        $clean = static function (?string $txt): string {
            $txt = (string)($txt ?? '');
            $txt = str_replace(["\xC2\xA0"], ' ', $txt); // nbsp
            $txt = preg_replace('/\s+/', ' ', $txt);
            return trim($txt);
        };
        $splitCodigoNombre = static function (string $raw): array {
            // Acepta "CODIGO — NOMBRE" (emdash) o "CODIGO - NOMBRE" / "CODIGO – NOMBRE"
            $parts = preg_split('/\s*(?:—|–|-)\s*/u', $raw, 2);
            if (count($parts) >= 2) {
                return ['codigo' => trim($parts[0]), 'nombre' => trim($parts[1])];
            }
            return ['codigo' => '', 'nombre' => trim($raw)];
        };

        // 2) Decodificar y normalizar los chips multi (REFERENCIAS, FORMATOS, y REFERENCIAS INTERNAS/NORMATIVAS)
        $referenciasRaw   = $decodeJson($request->input('referencias_json'));        // array
        $formatosRaw      = $decodeJson($request->input('formatos_json'));           // array
        $refNormativasRaw = $decodeJson($request->input('referencias_normativas'));  // array
        $refInternasRaw   = $decodeJson($request->input('referencias_internas'));    // array



        // Normaliza a [{codigo, nombre}]
        $normList = static function (array $raw) use ($splitCodigoNombre): array {
            $out = [];
            foreach ($raw as $item) {
                if (is_array($item)) {
                    $codigo = isset($item['codigo']) ? trim((string)$item['codigo']) : '';
                    $nombre = isset($item['nombre']) ? trim((string)$item['nombre']) : '';
                    if ($codigo === '' && $nombre === '' && isset($item['label'])) {
                        // si vino como {label: "CODIGO — NOMBRE"}
                        $pair = $splitCodigoNombre((string)$item['label']);
                        $codigo = $codigo ?: $pair['codigo'];
                        $nombre = $nombre ?: $pair['nombre'];
                    }
                    if ($codigo !== '' || $nombre !== '') {
                        $out[] = compact('codigo', 'nombre');
                    }
                } elseif (is_string($item)) {
                    $pair = $splitCodigoNombre($item);
                    if ($pair['codigo'] !== '' || $pair['nombre'] !== '') {
                        $out[] = $pair;
                    }
                }
            }
            return $out;
        };

        $referencias       = $normList($referenciasRaw);
        $formatos          = $normList($formatosRaw);
        $refNormativas     = $normList($refNormativasRaw);
        $refInternas       = $normList($refInternasRaw);

        // 2b) Reglas de “no vacío” (por si enviaron JSON válido pero vacío)
        // 2b) Reglas de “no vacío” (solo para formatos)
        $customErrors = [];
        if (count($formatos) === 0) {
            $customErrors['formatos_json'] = 'Agrega al menos un formato.';
        }
        if ($customErrors) {
            return back()->withErrors($customErrors)->withInput();
        }

        // 3) Otras listas
        $definiciones = $decodeJson($request->input('definiciones'));     // [{termino, definicion}]
        $desarrollo   = $decodeJson($request->input('desarrollo_json'));  // [{responsable, actividad}]
        $politicasArr = $decodeJson($request->input('politicas_json'));   // [ "texto", ... ]
        $indicadores  = $decodeJson($request->input('indicadores'));      // [{indicador, meta, monitoreo, responsable}]

        try {
            // 4) Fecha formateada
            Carbon::setLocale('es_MX');
            $formattedDate = '';
            if ($request->filled('fecha_emision')) {
                $fecha = Carbon::parse($request->input('fecha_emision'));
                $formattedDate = mb_convert_case($fecha->isoFormat('DD / MMM / YYYY'), MB_CASE_TITLE, 'UTF-8');
            }

            // 5) Cargar plantilla
            $templatePath = storage_path('app/plantillas/plantilla-procedimiento.docx');
            if (!file_exists($templatePath)) {
                return back()
                    ->withErrors(['plantilla' => 'No se encontró la plantilla de Word.'])
                    ->withInput();
            }
            $template = new TemplateProcessor($templatePath);

            // Helper para valores seguros
            $val = function (string $key, string $default = '') use ($request) {
                $v = $request->input($key);
                $v = is_scalar($v) ? trim((string)$v) : '';
                return ($v !== '') ? $v : $default;
            };
            $refNormativasRaw = $val('referencias_normativas', '');

            // Intenta decodificar (si es JSON válido)
            $refNormativas = json_decode($refNormativasRaw, true);

            // Si es un array válido, extrae solo los códigos
            if (is_array($refNormativas)) {
                $soloCodigos = array_column($refNormativas, 'codigo');

                // Convierte a CSV y limpia valores
                $referenciasNormativasCSV = implode(', ', array_map('trim', $soloCodigos));
            } else {
                // Si no es JSON válido, úsalo como string simple
                $referenciasNormativasCSV = $refNormativasRaw;
            }
            // 6) Set de valores simples
            $template->setValues([
                'fecha_emision'          => $formattedDate,
                'elaboro_area'           => $val('elaboro_area', ''),
                'codigo'                 => $val('codigo', ''),
                'revision'               => $val('revision', '00'),
                'referencias_normativas' => $referenciasNormativasCSV,
                'titulo'                 => $val('titulo', ''),

                'elaboro_nombre'         => $val('elaboro_nombre', ''),
                'reviso_nombre'          => $val('reviso_nombre', ''),
                'autorizo_nombre'        => $val('autorizo_nombre', ''),
                'elaboro_cargo'          => $val('elaboro_cargo', ''),
                'reviso_cargo'           => $val('reviso_cargo', ''),
                'autorizo_cargo'         => $val('autorizo_cargo', ''),

                'objetivo'               => $val('objetivo', ''),
                'alcance'                => $val('alcance', ''),
                // Si tu plantilla tiene un placeholder plano para "políticas" concatenadas:
                'politicas'              => implode("\n", array_map($clean, $politicasArr)),
            ]);

            // 7) Referencias (texto jerárquico 3.x)
            $refLines = [];

            //
            // 🔹 3.1 Referencias Normativas
            //
            if ($refNormativas && count($refNormativas)) {
                $refLines[] = "3.1 Referencias Normativas";
                foreach ($refNormativas as $i => $r) {
                    $codigo = $clean($r['codigo'] ?? '');
                    $nombre = $clean($r['nombre'] ?? '');
                    $num = '3.1.' . ($i + 1);
                    $refLines[] = "{$num} " . ($codigo !== '' ? "[$codigo] " : '') . $nombre;
                }
            } else {
                $refLines[] = "3.1 Referencias Normativas";
                $refLines[] = "3.1.1 (Sin referencias normativas registradas)";
            }

            //
            // 🔹 3.2 Referencias Internas
            //
            if ($refInternas && count($refInternas)) {
                $refLines[] = "3.2 Referencias Internas";
                foreach ($refInternas as $i => $r) {
                    $codigo = $clean($r['codigo'] ?? '');
                    $nombre = $clean($r['nombre'] ?? '');
                    $num = '3.2.' . ($i + 1);
                    $refLines[] = "{$num} " . ($codigo !== '' ? "[$codigo] " : '') . $nombre;
                }
            } else {
                $refLines[] = "3.2 Referencias Internas";
                $refLines[] = "3.2.1 (Sin referencias internas registradas)";
            }

            // 🔸 Colocar todo el bloque en la plantilla
            $template->setValue('referencias_lista', implode("\n", $refLines));



            // 8) Formatos (texto numerado 4.x)
            if ($formatos) {
                $lineas = [];
                foreach ($formatos as $i => $f) {
                    $codigo = $clean($f['codigo'] ?? '');
                    $nombre = $clean($f['nombre'] ?? '');
                    $lineas[] = '4.' . ($i + 1) . ' ' . ($codigo !== '' ? "[$codigo] " : '') . $nombre;
                }
                $template->setValue('formatos_lista', implode("\n", $lineas));
            } else {
                $template->setValue('formatos_lista', '');
            }

            // 9) Definiciones (texto numerado 5.x)
            if ($definiciones) {
                $lineas = [];
                foreach ($definiciones as $i => $d) {
                    $termino = $clean($d['termino'] ?? '');
                    $defn    = $clean($d['definicion'] ?? '');
                    $lineas[] = '5.' . ($i + 1) . ' ' . ($termino !== '' ? "$termino: " : '') . $defn;
                }
                $template->setValue('definiciones_lista', implode("\n", $lineas));
            } else {
                $template->setValue('definiciones_lista', '');
            }

            // 10) Desarrollo (tabla 6.x): cloneRow
            $rows = [];
            foreach (($desarrollo ?? []) as $i => $item) {
                $rows[] = [
                    'desarrollo_numero'  => '6.' . ($i + 1),
                    'desarrollo_titulo'  => $clean($item['responsable'] ?? ''),
                    'desarrollo_detalle' => $clean($item['actividad']   ?? ''),
                ];
            }
            if ($rows) {
                $template->cloneRowAndSetValues('desarrollo_numero', $rows);
            } else {
                // deja una fila vacía
                $template->cloneRow('desarrollo_numero', 1);
                $template->setValues([
                    'desarrollo_numero'  => '',
                    'desarrollo_titulo'  => '',
                    'desarrollo_detalle' => '',
                ]);
            }

            // 11) Indicadores (tabla o texto "No aplica")
            $inds = array_values(array_filter(($indicadores ?? []), 'is_array'));

            if (!empty($inds)) {
                // Hay indicadores → genera filas normalmente
                $rows = [];
                foreach ($inds as $it) {
                    $rows[] = [
                        'indicadores_nombre'      => $clean($it['indicador']   ?? ''),
                        'indicadores_meta'        => $clean($it['meta']        ?? ''),
                        'indicadores_frecuencia'  => $clean($it['monitoreo']   ?? ''),
                        'indicadores_responsable' => $clean($it['responsable'] ?? ''),
                    ];
                }
                $template->cloneRowAndSetValues('indicadores_nombre', $rows);
            } else {
                // No hay indicadores → clona una sola fila y pon "No aplica"
                $template->cloneRow('indicadores_nombre', 1);
                $template->setValue('indicadores_nombre#1', '-');
                $template->setValue('indicadores_meta#1', '—');
                $template->setValue('indicadores_frecuencia#1', '—');
                $template->setValue('indicadores_responsable#1', '—');
            }



            // 12) Diagrama PNG (opcional)
            $pngEncoded = $request->input('diagrama_png');
            if ($pngEncoded) {
                $pngEncoded = preg_replace('#^data:image/\w+;base64,#', '', $pngEncoded);
                $pngContent = base64_decode($pngEncoded);
                Storage::makeDirectory('tmp');
                $pngPath = storage_path('app/tmp/diagrama.png');
                file_put_contents($pngPath, $pngContent);

                $template->setImageValue('diagrama', [
                    'path'   => $pngPath,
                    'width'  => 700,
                    'height' => 450,
                    'ratio'  => true,
                ]);
            } else {
                $template->setValue('diagrama', '');
            }

            // 13) Guardar y descargar
            Storage::makeDirectory('tmp');
            $fileName = 'PO_' . preg_replace('/[^\w\-]+/', '_', $request->input('codigo')) . '_' . now()->format('Ymd_His') . '.docx';
            $outPath  = storage_path('app/tmp/' . $fileName);
            $template->saveAs($outPath);

            return response()->download($outPath, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['general' => 'No se pudo generar el documento: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function uploadform()
    {
        return view('procedimientos.upload');
    }
}
