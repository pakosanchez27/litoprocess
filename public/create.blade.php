@extends('layouts.app')

@section('titulo')
    Documentos
@endsection

@section('contenido')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-3px);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        .animate-bounce {
            animation: bounce 1s infinite;
        }

        .animate-pulse {
            animation: pulse 1.5s infinite;
        }

        .delay-150 {
            animation-delay: 0.15s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }
    </style>

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y p-5">
            <h4 class="fw-bold">Crear Procedimiento</h4>
            <p>Llena los campos y Genera el procedimiento.</p>

            {{-- ===== FORM ===== --}}
            <form id="procedimientoForm" method="POST" action="{{ route('procedimientos.store') }}" novalidate>
                @csrf

                {{-- ========== METADATOS ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <h5>Metadatos</h5>
                            <div class="row">
                                <!-- Tipo de documento -->
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label for="tipo_documento" class="form-label">Tipo de documento</label>
                                        <select class="form-select" id="tipo_documento" name="tipo_documento">
                                            <option value="" {{ old('tipo_documento') == '' ? 'selected' : '' }}>
                                                Selecciona un tipo</option>
                                            <option value="PO" {{ old('tipo_documento') == 'PO' ? 'selected' : '' }}>PO
                                                (Procedimiento Operativo)</option>
                                            <option value="PG" {{ old('tipo_documento') == 'PG' ? 'selected' : '' }}>PG
                                                (Procedimiento General)</option>
                                        </select>
                                        @error('tipo_documento')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Área -->
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label for="area_codigo" class="form-label">Área (código)</label>
                                        <input class="form-control" list="datalist_areas" id="area_codigo"
                                            name="area_codigo" placeholder="Ej.: DG, DO, CA..."
                                            value="{{ old('area_codigo') }}">
                                        <datalist id="datalist_areas">
                                            <option value="DG">Dirección General</option>
                                            <option value="DO">Dirección Operativa</option>
                                            <option value="CA">Control de Calidad</option>
                                            <option value="CO">Contabilidad</option>
                                            <option value="FA">Facturación</option>
                                            <option value="SI">Sistemas IT</option>
                                            <option value="MT">Mantenimiento</option>
                                            <option value="VE">Ventas</option>
                                            <option value="OP">Operaciones</option>
                                            <option value="EN">Entregas / Envíos</option>
                                            <option value="LI">Acabado Litografía</option>
                                            <option value="MA">Acabado Manual</option>
                                            <option value="AL">Almacén</option>
                                            <option value="ID">Integración y Desarrollo</option>
                                            <option value="OF">Oficinas</option>
                                        </datalist>
                                        <div class="form-text">Se incluirá en el código.</div>
                                        @error('area_codigo')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Consecutivo -->
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label for="consecutivo" class="form-label">Consecutivo (3 dígitos)</label>
                                        <input type="text" class="form-control" id="consecutivo" name="consecutivo"
                                            maxlength="3" placeholder="Ej.: 001" value="{{ old('consecutivo') }}">
                                        <div class="form-text">Se autocompleta al elegir Tipo/Área y FSC.</div>
                                    </div>
                                    @error('consecutivo')
                                        <p class="text-danger small mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Fecha de emisión -->
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label for="fecha_emision" class="form-label">Fecha de Emisión</label>
                                        <input type="date" class="form-control" id="fecha_emision" name="fecha_emision"
                                            value="{{ old('fecha_emision', now()->timezone('America/Mexico_City')->format('Y-m-d')) }}" />
                                    </div>
                                </div>

                                <!-- Revisión -->
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label for="revision" class="form-label">Revisión</label>
                                        <input type="text" class="form-control" id="revision" name="revision"
                                            value="00" disabled>
                                        <div class="form-text">En automático será 00 por primera emisión.</div>
                                    </div>
                                </div>

                                <!-- Código generado -->
                                <div class="col-4">
                                    <div class="mb-3">
                                        <label for="codigo" class="form-label">Código</label>
                                        <input type="text" class="form-control" id="codigo" name="codigo"
                                            placeholder="L-PG-FSC-CA-001" readonly>

                                        <div class="flex flex-row align-items-center mt-2">
                                            <input type="checkbox" id="es_fsc" name="es_fsc"
                                                class="form-check-input me-2" {{ old('es_fsc') ? 'checked' : '' }} />
                                            <label for="es_fsc" class="form-label mb-0">Aplica FSC</label>
                                        </div>
                                    </div>
                                </div>

                                <input type="hidden" id="elaboro_area" name="elaboro_area"
                                    value="{{ old('elaboro_area') }}" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== REFERENCIAS NORMATIVAS (CHIPS) ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <h5>Referencias Normativas</h5>
                            <div class="row">
                                <div class="col-12">
                                    <div class="md-item col-12">
                                        <label for="ref_tag_input" class="form-label fw-semibold">Referencias
                                            Normativas</label>

                                        <div id="ref_tag_box"
                                            class="form-control d-flex flex-wrap gap-2 align-items-center" role="textbox"
                                            aria-describedby="referencias_normativas-help"
                                            aria-label="Referencias normativas (etiquetas)"
                                            style="min-height:2.5em; cursor:text;">
                                            <!-- chips se agregarán dinámicamente -->
                                            <input id="ref_tag_input" list="referencias_options"
                                                placeholder="Escribe y presiona Enter…"
                                                style="flex:1 1 120px;border:none;outline:0;background:transparent;height:1.9em;color:inherit;">
                                        </div>

                                        <datalist id="referencias_options">
                                            <option value="ISO 9001:2015 — Sistemas de gestión de la calidad"></option>
                                            <option value="ISO 14001:2015 — Sistemas de gestión ambiental"></option>
                                            <option value="ISO 45001:2018 — Seguridad y salud en el trabajo"></option>
                                            <option value="ISO 19011:2018 — Directrices para auditorías"></option>
                                            <option value="ISO 27001:2022 — Seguridad de la información"></option>
                                            <option value="FSC-STD-40-004 V3 — Cadena de custodia"></option>
                                            <option value="FSC-STD-40-005 V3 — Madera controlada"></option>
                                            <option value="FSC-STD-50-001 V2 — Requisitos de marcas FSC"></option>
                                            <option value="PEFC ST 2002:2020 — Cadena de custodia de productos forestales">
                                            </option>
                                            <option value="PEFC ST 2001:2020 — Reglas de uso de marcas PEFC"></option>
                                            <option value="NOM-035-STPS-2018 — Factores de riesgo psicosocial"></option>
                                            <option
                                                value="NOM-018-STPS-2015 — Sistema armonizado de clasificación y comunicación de peligros">
                                            </option>
                                            <option value="NOM-003-SEGOB-2011 — Señales y avisos de protección civil">
                                            </option>
                                            <option value="IATF 16949:2016 — Automotriz (calidad)"></option>
                                            <option value="BPM — Buenas Prácticas de Manufactura (GMP)"></option>
                                            <option value="ISO 31000:2018 — Gestión del riesgo"></option>
                                        </datalist>

                                        <input type="hidden" id="referencias_normativas" name="referencias_normativas"
                                            value="{{ old('referencias_normativas') }}">

                                        <small id="referencias_normativas-help" class="text-muted d-block mt-1">
                                            Escribe, selecciona y presiona Enter o coma. Backspace borra la última. Clic en
                                            ✖ para quitar.
                                            Si alguna contiene “FSC”, se marcará automáticamente.
                                        </small>

                                        @error('referencias_normativas')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== TÍTULO ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <h5>Titulo del Procedimiento</h5>
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="titulo_procedimiento" class="form-label">Título del
                                            Procedimiento</label>
                                        <input type="text" class="form-control" id="titulo_procedimiento"
                                            name="titulo" value="{{ old('titulo') }}"
                                            placeholder="Ej.: Procedimiento para el control de calidad de impresiones">
                                        @error('titulo')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== OBJETIVO ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <div class="col-12">
                                <label for="objetivo" class="form-label fw-semibold">Objetivo</label>
                                <textarea id="objetivo" name="objetivo" class="form-control" maxlength="1200" rows="4"
                                    placeholder="Describir el objetivo...">{{ old('objetivo') }}</textarea>

                                @error('objetivo')
                                    <p class="text-danger small mt-1">{{ $message }}</p>
                                @enderror

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <button class="btn btn-outline-primary btn-sm" type="button" data-target="objetivo"
                                        onclick="redactarObjetivoIA()">
                                        Mejorar redacción
                                    </button>

                                    <small class="text-muted">
                                        <span class="counter" data-counter="objetivo">0</span>/1200
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== ALCANCE ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="col-12">
                            <div class="card p-3">
                                <label for="alcance" class="form-label">Alcance</label>
                                <textarea class="form-control" id="alcance" name="alcance" rows="4" maxlength="1200"
                                    placeholder="Describir el alcance...">{{ old('alcance') }}</textarea>

                                @error('alcance')
                                    <p class="text-danger small mt-1">{{ $message }}</p>
                                @enderror

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="rewrite"
                                        data-target="alcance" onclick="redactarAlcanceIA()">
                                        Mejorar redacción
                                    </button>

                                    <span class="text-muted small">
                                        <span class="counter" id="alcance-counter">0</span>/1200
                                    </span>
                                </div>

                                <div class="form-text">
                                    Describe a qué procesos, áreas o actividades aplica este documento.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== REFERENCIAS Y FORMATOS (CHIPS) ========== --}}
                <div class="row mb-3">
                    <div class="col 12">
                        <div class="card p-3">
                            <!-- Referencias -->
                            <div class="col-12 mb-4">
                                <label for="ref_input" class="form-label fw-semibold">Referencias</label>
                                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                    <input type="text" class="form-control w-auto flex-fill" id="ref_input"
                                        list="referencias_options" placeholder="Escribe o selecciona y presiona Enter"
                                        autocomplete="off">
                                    <datalist id="referencias_options">
                                        <option value="ISO 9001:2015 — Sistemas de gestión de la calidad"></option>
                                        <option value="ISO 14001:2015 — Sistemas de gestión ambiental"></option>
                                        <option value="ISO 45001:2018 — Seguridad y salud en el trabajo"></option>
                                        <option value="ISO 19011:2018 — Directrices para auditorías"></option>
                                        <option value="ISO 27001:2022 — Seguridad de la información"></option>
                                        <option value="FSC-STD-40-004 — Cadena de custodia"></option>
                                        <option value="FSC-STD-40-005 — Madera controlada"></option>
                                        <option value="FSC-STD-50-001 — Requisitos de marcas FSC"></option>
                                        <option value="PEFC ST 2002:2020 — Cadena de custodia"></option>
                                        <option value="PEFC ST 2001:2020 — Reglas de uso de marcas PEFC"></option>
                                        <option value="NOM-035-STPS-2018 — Riesgo psicosocial"></option>
                                        <option value="NOM-018-STPS-2015 — Clasificación y comunicación de peligros">
                                        </option>
                                        <option value="NOM-003-SEGOB-2011 — Protección civil"></option>
                                        <option value="IATF 16949:2016 — Automotriz (calidad)"></option>
                                        <option value="BPM — Buenas Prácticas de Manufactura (GMP)"></option>
                                        <option value="ISO 31000:2018 — Gestión del riesgo"></option>
                                    </datalist>
                                </div>

                                <div id="chips_referencias" class="form-control d-flex flex-wrap gap-2 align-items-center"
                                    style="min-height:2.5em;"></div>
                                <input type="hidden" id="referencias_json" name="referencias_json"
                                    value="{{ old('referencias_json', '[]') }}">

                                @error('referencias_json')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Formatos -->
                            <div class="col-12">
                                <label for="fmt_input" class="form-label fw-semibold">Formatos</label>
                                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                    <input type="text" class="form-control w-auto flex-fill" id="fmt_input"
                                        list="formatos_options" placeholder="Escribe o selecciona y presiona Enter"
                                        autocomplete="off">
                                    <datalist id="formatos_options">
                                        <option value="FOR-001 — Solicitud de materiales"></option>
                                        <option value="FOR-002 — Reporte de no conformidad"></option>
                                        <option value="FOR-003 — Control de mantenimiento"></option>
                                        <option value="FOR-004 — Registro de capacitación"></option>
                                        <option value="FOR-005 — Control de documentos"></option>
                                        <option value="FOR-006 — Evaluación de proveedores"></option>
                                        <option value="FOR-007 — Revisión por la dirección"></option>
                                        <option value="FOR-008 — Inspección de producto terminado"></option>
                                        <option value="FOR-009 — Solicitud de compra"></option>
                                        <option value="FOR-010 — Verificación de proceso"></option>
                                    </datalist>
                                </div>

                                <div id="chips_formatos" class="form-control d-flex flex-wrap gap-2 align-items-center"
                                    style="min-height:2.5em;"></div>
                                <input type="hidden" id="formatos_json" name="formatos_json"
                                    value="{{ old('formatos_json', '[]') }}">

                                @error('formatos_json')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ========== DEFINICIONES (lista) ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <h5>Definiciones</h5>
                            <div class="row">
                                <div class="col-12">
                                    <label for="termino" class="form-label fw-semibold">Definiciones</label>

                                    <div class="row g-2 align-items-start mb-2">
                                        <div class="col-md-5">
                                            <input type="text" id="termino" class="form-control"
                                                placeholder="Término">
                                        </div>
                                        <div class="col-md-6 d-flex align-items-start gap-2">
                                            <textarea id="definicion" name="definicion" class="form-control flex-grow-1" rows="3" maxlength="600"
                                                placeholder="Escribe la definición...">{{ old('definicion') }}</textarea>

                                            <button type="button" class="btn btn-outline-primary btn-sm"
                                                data-bs-toggle="tooltip" data-bs-offset="0,4" data-bs-placement="top"
                                                data-bs-html="true" title="<span>Mejorar redacción</span>"
                                                onclick="mejorarDefinicionIA()">
                                                <i class='bx bx-pencil'></i>
                                            </button>
                                        </div>


                                        <div class="col-md-1 d-flex justify-content-end">
                                            <button class="btn btn-outline-success btn-sm w-100" id="addDefinicion"
                                                type="button">➕</button>
                                        </div>
                                    </div>

                                    <ul id="definiciones-list" class="list-group mb-2" aria-live="polite"></ul>

                                    <input type="hidden" name="definiciones" id="definiciones_json"
                                        value="{{ old('definiciones') }}">

                                    @error('definiciones')
                                        <p class="text-danger small mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== DESARROLLO (PASOS) ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <h5>Desarrollo del Procedimiento</h5>
                            <div id="pasosContainer" class="pasos mb-3"></div>

                            <div class="d-flex justify-content-end">
                                <button type="button" id="btnAgregarPaso" class="btn btn-outline-primary btn-sm">
                                    <i class="bx bx-plus"></i> Agregar paso
                                </button>
                            </div>

                            <input type="hidden" id="desarrollo_json" name="desarrollo_json"
                                value='{{ old('desarrollo_json') }}'>
                        </div>
                    </div>
                </div>

                {{-- ========== POLÍTICAS (dinámicas) ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <h5>Políticas</h5>

                            <div id="politicasContainer" class="mb-3"></div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <button type="button" id="btnAgregarPolitica" class="btn btn-outline-primary btn-sm">
                                    <i class="bx bx-plus"></i> Agregar política
                                </button>
                            </div>

                            <input type="hidden" id="politicas_json" name="politicas_json"
                                value='{{ old('politicas_json') }}'>
                        </div>
                    </div>
                </div>

                {{-- ========== INDICADORES (toggle) ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0">Indicadores</h5>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="toggleIndicadores">
                                    <label class="form-check-label" for="toggleIndicadores">Aplica Indicadores</label>
                                </div>
                            </div>

                            <div id="indicadoresSection" style="display: none;">
                                <div class="row g-2 align-items-end mb-3">
                                    <div class="col-md-3">
                                        <label for="indicador" class="form-label">Indicador</label>
                                        <input type="text" class="form-control" id="indicador"
                                            placeholder="Ej.: Porcentaje de cumplimiento">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="meta" class="form-label">Meta</label>
                                        <input type="text" class="form-control" id="meta"
                                            placeholder="Ej.: 95%">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="monitoreo" class="form-label">Monitoreo</label>
                                        <input type="text" class="form-control" id="monitoreo"
                                            placeholder="Ej.: Mensual">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="responsable" class="form-label">Responsable</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="responsable"
                                                placeholder="Ej.: Jefe de Calidad">
                                            <button class="btn btn-outline-primary" type="button" id="addIndicador">➕
                                                Agregar</button>
                                        </div>
                                    </div>
                                </div>

                                <ul id="indicadores-list" class="list-group mb-3" aria-live="polite"></ul>

                                <input type="hidden" name="indicadores" id="indicadores_json"
                                    value="{{ old('indicadores') }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========== FIRMAS ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <h5>Firmas</h5>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="elaboro_nombre" class="form-label fw-semibold">Elaboró
                                            (Nombre)</label>
                                        <input type="text" class="form-control" id="elaboro_nombre"
                                            name="elaboro_nombre" placeholder="Ej.: Juan Pérez"
                                            value="{{ old('elaboro_nombre') }}">
                                        @error('elaboro_nombre')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="reviso_nombre" class="form-label fw-semibold">Revisó (Nombre)</label>
                                        <input type="text" class="form-control" id="reviso_nombre"
                                            name="reviso_nombre" placeholder="Ej.: Laura Gómez"
                                            value="{{ old('reviso_nombre') }}">
                                        @error('reviso_nombre')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="autorizo_nombre" class="form-label fw-semibold">Autorizó
                                            (Nombre)</label>
                                        <input type="text" class="form-control" id="autorizo_nombre"
                                            name="autorizo_nombre" placeholder="Ej.: Roberto Hernández"
                                            value="{{ old('autorizo_nombre') }}">
                                        @error('autorizo_nombre')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="elaboro_cargo" class="form-label fw-semibold">Elaboró (Cargo)</label>
                                        <input type="text" class="form-control" id="elaboro_cargo"
                                            name="elaboro_cargo" placeholder="Ej.: Coordinador de Calidad"
                                            value="{{ old('elaboro_cargo') }}">
                                        @error('elaboro_cargo')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="reviso_cargo" class="form-label fw-semibold">Revisó (Cargo)</label>
                                        <input type="text" class="form-control" id="reviso_cargo" name="reviso_cargo"
                                            placeholder="Ej.: Supervisor de Producción"
                                            value="{{ old('reviso_cargo') }}">
                                        @error('reviso_cargo')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="mb-3">
                                       <label for="autorizo_cargo" class="form-label fw-semibold">Autorizó (Cargo)</label>
                                        <input type="text" class="form-control" id="autorizo_cargo"
                                            name="autorizo_cargo" placeholder="Ej.: Director General"
                                            value="{{ old('autorizo_cargo') }}">
                                        @error('autorizo_cargo')
                                            <p class="text-danger small mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ========== DIAGRAMA DE FLUJO (Mermaid) ========== --}}
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card p-3">
                            <h5>Diagrama de Flujo</h5>

                            <div class="row">
                                <div class="col-12">
                                    <!-- Barra de herramientas -->
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="btn-group" role="group">
                                            <button type="button" id="btnGenerarCodigo"
                                                class="btn btn-outline-primary btn-sm" onclick="renderDiagramaIA()">
                                                <i class="bx bx-code-curly"></i> Generar Código
                                            </button>

                                            <button type="button" id="btnFullscreen"
                                                class="btn btn-outline-secondary btn-sm">
                                                <i class="bx bx-fullscreen"></i> Pantalla completa
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Área del editor Mermaid -->
                                    <div class="mb-3">
                                        <label for="mermaidCode" class="form-label fw-semibold">Editor de Diagrama
                                            (Mermaid)</label>
                                        <textarea id="mermaidCode" name="mermaidCode" class="form-control" rows="8" spellcheck="false"
                                            placeholder="Escribe el código Mermaid aquí...">{{ old('mermaidCode', 'flowchart TD; A["Escribe Mermaid aquí"];') }}</textarea>
                                    </div>

                                    <!-- Vista previa del diagrama -->
                                    <div class="border rounded p-3 bg-light mb-3">
                                        <div id="mermaidPreview" class="mermaid text-center">Render pendiente…</div>
                                        <div id="mermaidError" class="text-danger small mt-2" style="display:none;">
                                        </div>
                                    </div>

                                    <!-- Campo oculto para el SVG -->
                                    <input type="hidden" name="diagrama_svg" id="diagrama_svg"
                                        value="{{ old('diagrama_svg') }}">
                                    <input type="hidden" name="diagrama_code" id="diagrama_code"
                                        value="{{ old('diagrama_code') }}">
                                    <input type="hidden" name="diagrama_png" id="diagrama_png" value="">
                                    @error('diagrama_svg')
                                        <p class="text-danger small mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUBMIT --}}
                <div class="flex justify-end align-center" style="margin-top: 20px; gap: 12px;">
                    <button class="btn btn-primary" id="btnGenerarPDF" type="submit" disabled>
                        📄 Generar Word
                    </button>
                </div>
              {{--  @if ($errors->any())
                    <div class="alert alert-danger">
                        <h6 class="mb-2">No se pudo generar el documento:</h6>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif --}

            </form>

            {{-- Pantalla exito --}}

            <!-- PANTALLA DE ÉXITO -->
            <div id="successScreen" class="text-center" style="display:none; padding: 80px;">
                <h2 class="fw-bold mb-3 text-success">✅ Procedimiento creado correctamente</h2>
                <p class="text-muted mb-4">El documento Word se ha generado y descargado exitosamente.</p>

                <div class="d-flex justify-content-center gap-3">
                    <button id="btnNuevo" type="button" class="btn btn-primary">
                        ➕ Crear nuevo procedimiento
                    </button>
                    <button id="btnVerDescarga" type="button" class="btn btn-outline-secondary">
                        📄 Ver archivo descargado
                    </button>
                </div>
            </div>

        </div>
    </div>
    <!-- 🌐 CHAT FLOTANTE -->
    <div id="chatbot" class="fixed bottom-6 right-6 z-50">
        <!-- Botón flotante -->
        <button id="chatToggle"
            class="bg-primary text-white rounded-full shadow-lg w-14 h-14 flex items-center justify-center text-2xl hover:scale-105 transition-transform rounded">
            💬
        </button>

        <!-- Ventana del chat -->
        <div id="chatWindow"
            class="hidden flex flex-col bg-white shadow-2xl rounded-2xl w-80 h-96 overflow-hidden border border-gray-200">
            <div class="bg-primary text-white px-4 py-2 flex justify-between items-center">
                <span class="font-semibold">Asistente IA</span>
                <button id="chatClose" class="text-white text-xl leading-none">×</button>
            </div>

            <div id="chatMessages" class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50">
                <div class="text-sm text-gray-500 text-center mt-2">
                    💡 Hola, soy tu asistente. ¿En qué puedo ayudarte con el procedimiento?
                </div>
            </div>

            <div class="border-t border-gray-200 p-2 flex items-center bg-white">
                <input id="chatInput" type="text" placeholder="Escribe tu mensaje..."
                    class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" />
                <button id="chatSend"
                    class="ml-2 bg-primary text-white px-3 py-2 rounded-lg text-sm hover:bg-primary/90 rounded mx-2">Enviar</button>
            </div>
        </div>
    </div>

   <script>
document.addEventListener('DOMContentLoaded', () => {
  const toggleBtn = document.getElementById('chatToggle');
  const chatWindow = document.getElementById('chatWindow');
  const closeBtn = document.getElementById('chatClose');
  const sendBtn = document.getElementById('chatSend');
  const input = document.getElementById('chatInput');
  const messages = document.getElementById('chatMessages');

  const WEBHOOK_URL = "https://n8n.srv914565.hstgr.cloud/webhook/chat-crear-procedimientos";

  // --- Toggle ventana ---
  toggleBtn.addEventListener('click', () => {
    chatWindow.classList.toggle('hidden');
    toggleBtn.style.display = chatWindow.classList.contains('hidden') ? 'flex' : 'none';
    input.focus();
  });
  closeBtn.addEventListener('click', () => {
    chatWindow.classList.add('hidden');
    toggleBtn.style.display = 'flex';
  });

  // --- Enviar mensaje ---
  async function sendMessage() {
    const text = input.value.trim();
    if (!text) return;

    appendMessage('user', text);
    input.value = '';
    input.focus();

    // Mostrar "bot escribiendo..."
    const typingId = appendTyping();

    try {
      const res = await fetch(WEBHOOK_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: text })
      });

      if (!res.ok) throw new Error('Error HTTP: ' + res.status);
      const data = await res.json();

      // Extraer texto de respuesta (más tolerante)
      let reply = extractTextFromResponse(data);

      removeTyping(typingId);
      appendMessage('bot', reply || '⚠️ No se recibió texto en la respuesta.');
    } catch (err) {
      console.error('❌ Error en conexión o parseo:', err);
      removeTyping(typingId);
      appendMessage('bot', '⚠️ Error al conectar con el asistente. Verifica el flujo en n8n.');
    }
  }

  // --- Función robusta para extraer texto de cualquier respuesta ---
  // --- Función ultra robusta para extraer texto de cualquier respuesta n8n ---
function extractTextFromResponse(data) {
  if (!data) return null;

  // Si es string, retornarlo directo
  if (typeof data === 'string') return data;

  // Si es objeto con campos comunes
  if (data.reply || data.response || data.message)
    return data.reply || data.response || data.message;

  // 🔍 Búsqueda recursiva: encuentra la primera cadena o propiedad "output"/"text"
  function deepSearch(obj) {
    if (obj == null) return null;

    if (typeof obj === 'string') return obj;
    if (typeof obj !== 'object') return null;

    // Si tiene campo "output" o "text", úsalo
    if (obj.output && typeof obj.output === 'string') return obj.output;
    if (obj.text && typeof obj.text === 'string') return obj.text;

    // Buscar dentro de propiedades hijas
    for (const key in obj) {
      const val = obj[key];
      const found = deepSearch(val);
      if (found) return found;
    }

    return null;
  }

  const found = deepSearch(data);
  if (found) return found;

  // Si es array, buscar en elementos
  if (Array.isArray(data)) {
    for (const item of data) {
      const found = extractTextFromResponse(item);
      if (found) return found;
    }
  }

  // Último recurso: mostrar el JSON completo
  return JSON.stringify(data);
}

  // --- Mostrar mensaje en el chat ---
  function appendMessage(sender, text) {
    const msg = document.createElement('div');
    msg.className = sender === 'user' ? 'text-right' : 'text-left';
    msg.innerHTML = `
      <div class="${sender === 'user'
        ? 'inline-block bg-primary text-white px-3 py-2 rounded-lg mb-1 max-w-[80%]'
        : 'inline-block bg-gray-200 text-gray-800 px-3 py-2 rounded-lg mb-1 max-w-[80%]'}">
        ${escapeHtml(text)}
      </div>`;
    messages.appendChild(msg);
    messages.scrollTop = messages.scrollHeight;
  }

  // --- Añadir indicador "bot escribiendo..." ---
  function appendTyping() {
    const id = 'typing-' + Date.now();
    const typing = document.createElement('div');
    typing.id = id;
    typing.className = 'text-left';
    typing.innerHTML = `
      <div class="inline-block bg-gray-200 text-gray-800 px-3 py-2 rounded-lg mb-1 max-w-[80%] flex items-center gap-1">
        <span class="animate-pulse">Escribiendo</span>
        <span class="dot1 animate-bounce">.</span>
        <span class="dot2 animate-bounce delay-150">.</span>
        <span class="dot3 animate-bounce delay-300">.</span>
      </div>`;
    messages.appendChild(typing);
    messages.scrollTop = messages.scrollHeight;
    return id;
  }

  function removeTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
  }

  // --- Escucha Enter o clic ---
  sendBtn.addEventListener('click', sendMessage);
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') sendMessage();
  });

  // --- Evita XSS ---
  function escapeHtml(unsafe) {
    return unsafe
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
});
</script>


    {{-- Idealmente carga mermaid y tus scripts via Vite --}}
    @vite(['resources/js/procedimientos/diagrama.js', 'resources/js/procedimientos/redactorIA.js', 'resources/js/procedimientos/formulario.js'])
@endsection
