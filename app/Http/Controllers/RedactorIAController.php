<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RedactorIAController extends Controller
{

    public function stream(Request $request)
    {


        // dd($request->all());
        $introduccion = "Eres un redactor profesional en español (MX): mejora claridad, Ortografía y tono profesional sin inventar datos. Devuelve solo el texto mejorado minimo 250 caracteres. trabajas para Litoprocess.";
        $objetivoEjemplo = "
                    1. Establecer las actividades y responsabilidades en la solicitud de maquilas de acuerdo a las 
                        necesidades de los procesos de producción en Litoprocess. 
                    2. Establecer y estandarizar el método para la elaboración, revisión y autorización de cotizaciones para 
                        los presupuestos que se envían a los clientes de la organización. 
                    3. Atraer e identificar a los candidatos, por medio del reclutamiento, a fin de contar con las mejores 
                        opciones para seleccionar y escoger al colaborador idóneo, con base y acorde a la Descripción de 
                        Puesto de la posición, enfocado siempre al cumplimiento de nuestros objetivos organizacionales. 
                        Concluyendo el proceso con el estableciendo de acuerdos de voluntades con claridad y precisión 
                        para que las partes tengan certeza del objeto de la contratación, de sus términos y de sus efectos, 
                        pues es el marco dentro del cual las partes ejecutarán lo convenido.
                    4.Asegurar la confiabilidad en la fabricación de los Pantones para el proceso de impresión Offset y 
                          cumplir con los estándares colorimétricos especificados en la norma ISO 12647-2.
                    5. Establecer mecanismos en la empresa para una urgenica médica: asistencia inmediata, temporal y necesaria que 
                        se le brindará a una persona o personas que hayan sufrido un accidente, enfermedad súbita o enfermedad crónica 
                        agudizada, utilizando los materiales que se tienen a la mano, hasta la llegada de los servicios de atención médica 
                        prehospitalaria, que se encargarán de la atención en el sitio de la urgencia o del trasladado a una unidad hospitalaria 
                        para su tratamiento integral. 
        ";

        $alcanceEjemplo = "
                    1. Aplica para todas las áreas de la empresa.  
                    2. Este Procedimiento aplica desde que se recibe el sobre viajero hasta su resguardo final. 
                    3. Este procedimiento aplica desde el primer contacto con el cliente y recopilación de su información 
                      hasta la autorización del presupuesto por parte del cliente.
                    4. Aplica para el área de Recursos Humanos y todos los colaboradores de Litoprocess involucrados en el proceso de reclutamiento y selección.
                    5. Aplica a todos los procesos de Litoprocess que intervienen en la manufactura de un producto 
                      que requiere un servicio de tercerización. 
        ";
        $politicasEjemplo = "
                    1. La Inducción a la Empresa se le dará a todos los colaboradores de nuevo ingreso inmediato en un lapso 
                       no mayor a 7 días. 
                    2. Toda solicitud de cotización debe ser atendida entregando presupuesto al cliente de acuerdo a los 
                        siguientes tiempos: 
                        Mismo día para solicitudes recibidas hasta las 13:00hrs. 
                        Día siguiente para solicitudes recibidas de 13:01 hrs en adelante.
                    3. Todos los arrastres deben elaborarse utilizando el Formato de Colorimetría y contar con la 
                        firma de autorización del Gerente de Producción, Jefe de Offset, Supervisor de Offset, 
                        Cliente o Vendedor. Sin esta firma, no se podrá iniciar la producción. 
                         Las solicitudes de igualaciones por parte del área de Ventas deben realizarse a través del 
                        formato 'Solicitud de Elaboración de Igualaciones de Pantone'. 
                    4. El proceso de reclutamiento y selección debe cumplir con las leyes laborales vigentes en México.
                    5. El Pantone debe cumplir con los estándares colorimétricos especificados en la norma ISO 12647-2.
                    6. El personal designado para brindar primeros auxilios debe contar con la capacitación adecuada y vigente en técnicas de atención prehospitalaria.
                    ";
        $actividadEjemplo = "
                    1.Recibe por parte del área solicitante Requisición de Personal formato 
                        L-FO-RH-007, la cual puede ser un puesto nuevo o sustitución de 
                        personal.
                    2. Difunde la vacante en las distintas fuentes de reclutamiento Digital, 
                        Portales virtuales de empleo privados y de gobierno, juntas de 
                        Recursos Humanos, posteo, bolsas de trabajo de Universidades, Ferias 
                        de empleo, entre otros. 
                         Dentro del proceso no existe discriminación por origen étnico o nacional, 
                        género, edad, discapacidad, condición social, condiciones de salud, 
                        religión, opiniones, preferencias sexuales o estado civil; acorde con el 
                        Artículo 2º de la Ley Federal del Trabajo. 
                         Sólo nos reservamos el derecho de admisión a aquellas personas 
                        menores de 18 años.
                    3.  Autoriza el formato L-FO-VE-005 “Presupuesto” y firma el formato L
                        FO-VE-008 “Pedido de ventas” de aceptado. 
                         Sí este formato no fuera firmado, es necesario contar con la orden de 
                        compra firmada por el cliente o en su defecto contar con firma de 
                        autorización de la Dirección  de Operaciones para continuar con el 
                        proceso. 
                        
                         El cliente puede autorizar el trabajo vía correo electrónico. 
                    4. Elabora el Pantone conforme a las especificaciones del cliente y 
                        realiza las pruebas de impresión necesarias para asegurar la 
                        conformidad del color.
                    5. Realiza la atención inmediata en el lugar del incidente, utilizando los 
                        materiales disponibles en el botiquín de primeros auxilios, para
                        estabilizar al paciente hasta la llegada de los servicios médicos. 

        ";

        // dd($request->type);
        switch ($request->type) {
            case 'objetivo':
                $system = " $introduccion Recibiras un texto que describe el objetivo de un procedimiento. Tu tarea es mejorar la claridad, fluidez y profesionalismo del texto sin alterar su significado original, si el texto es muy corto amuenta sin alterar o inventar minimo 250 caracteres devuelve solo el texto mejorado. basado en los sigiuientes ejemplos: {$objetivoEjemplo}";
                $user   = "Texto original:\n\n{$request->text}\n\nReescribe mejorando claridad y fluidez.";
                break;
            case 'alcance':
                $system = " $introduccion Recibiras un texto que describe el alcance de un procedimiento. Tu tarea es mejorar la claridad, fluidez y profesionalismo del texto sin alterar su significado original. Devuelve solo el texto mejorado. basado en los sigiuientes ejemplos: {$alcanceEjemplo}";
                $user   = "Texto original:\n\n{$request->text}\n\nReescribe mejorando claridad y fluidez.";
                break;
            case 'politicas':
                $system = " $introduccion Recibiras un texto que describe las políticas de un procedimiento. Tu tarea es mejorar la claridad, fluidez y profesionalismo del texto sin alterar su significado original. Devuelve solo el texto mejorado. basado en los sigiuientes ejemplos: {$politicasEjemplo}";
                $user   = "Texto original:\n\n{$request->text}\n\nReescribe mejorando claridad y fluidez.";
                break;
            case 'actividad':
                $system = " $introduccion Recibiras un texto que describe las actividades de un procedimiento. Tu tarea es mejorar la claridad, fluidez y profesionalismo del texto sin alterar su significado original. Devuelve solo el texto mejorado. basado en los sigiuientes ejemplos: {$actividadEjemplo}";
                $user   = "Texto original:\n\n{$request->text}\n\nReescribe mejorando claridad y fluidez.";
                break;
            case 'definicion':
                $system = " $introduccion Recibiras un texto que describe la definición de un procedimiento. Tu tarea es mejorar la claridad, fluidez y profesionalismo del texto sin alterar su significado original. Devuelve solo el texto mejorado. basado en los sigiuientes ejemplos: ";
                $user   = "Texto original:\n\n{$request->text}\n\nReescribe mejorando claridad y fluidez.";
                break;

            default:
                $system = " $introduccion Recibiras un texto. Tu tarea es mejorar la claridad, fluidez y profesionalismo del texto sin alterar su significado original. Devuelve solo el texto mejorado.";
                break;
        }



        $model = env('AI_MODEL', 'gpt-4o-mini'); // ajusta el que uses

        $payload = [
            'model' => $model,
            'input' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user',   'content' => $user],
            ],
            'temperature'       => 0.3,
            'max_output_tokens' => 600,
            'stream'            => true, // <- ACTIVAMOS STREAM
        ];

        $stream = function () use ($payload) {
            $res = Http::withToken(env('OPENAI_API_KEY'))
                ->withHeaders([
                    'Accept' => 'text/event-stream',
                ])
                ->withOptions(['stream' => true])
                ->post('https://api.openai.com/v1/responses', $payload);

            $body = $res->toPsrResponse()->getBody();


            while (!$body->eof()) {
                // lee trozos del SSE y reenvía tal cual
                echo $body->read(1024);
                @ob_flush();
                @flush();
            }
        };
        return new StreamedResponse($stream, 200, [
            'Content-Type'  => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no', // Nginx: desactiva buffering
            'Connection'    => 'keep-alive',
        ]);
    }

    public function diagrama(Request $request)
    {

        $pasos = $request->input('pasos');

        $resp = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '
                    [SISTEMA] Rol:
                    Eres un creador profesional de diagramas Mermaid. Tu tarea es transformar pasos/instrucciones del usuario en DIAGRAMAS Mermaid válidos y listos para renderizar.

                    [OBJETIVO]
                    Generar EXCLUSIVAMENTE código Mermaid funcional, sin textos adicionales, que represente fielmente todos los elementos e interacciones descritas por el usuario, con especial atención a NO OMITIR NINGUNA DECISIÓN NI SUS RAMAS.

                    [ENTRADA ESPERADA]
                    - El usuario puede dar instrucciones libres (texto) o estructura (lista, tabla, JSON/YAML).
                    - Si el usuario especifica tipo de diagrama, dirección, estilos, subgráficos, notas, actores, etiquetas o nombres de nodos, respétalos.
                    - Si entrega varios conjuntos de pasos, entiende que solicita varios diagramas.

                    [FORMATO DE SALIDA — MUY IMPORTANTE]
                    - Responde ÚNICAMENTE con código Mermaid válido.
                    - No uses backticks, no uses ```mermaid, no agregues comentarios, explicaciones, ni texto extra.
                    - Si el usuario solicita varios diagramas, colócalos en el orden dado y sepáralos por UNA línea en blanco (sin texto intermedio).

                    [REGLAS ESTRICTAS]
                    1) Analiza cuidadosamente todos los pasos, entidades y relaciones. No omitas ningún elemento, relación, condición o DECISIÓN.
                    2) No agregues nada que el usuario no haya pedido. Mantén nombres, etiquetas y orden cuando aplique.
                    3) El código debe compilar en Mermaid y seguir la sintaxis correcta.
                    4) Si hay ambigüedad:
                    - Si es AMBIGÜEDAD CRÍTICA que impide representar las decisiones (p. ej., se describe una condición pero no se indican sus salidas), realiza UNA sola pregunta clara.
                    - Si no es crítica, usa tu criterio profesional para producir la versión más coherente, mínima y fiel a lo solicitado, manteniendo todas las decisiones mencionadas.
                    5) Escapa caracteres especiales cuando sea necesario para preservar el texto del usuario.
                    6) No apliques estilos, init, clases o temas a menos que el usuario lo pida.
                    7) Mantén consistencia visual (dirección, nodos, flechas) según lo solicitado.

                    [ENFASIS EN DECISIONES — OBLIGATORIO]
                    A) Detección:
                    - Identifica explícitamente cada decisión en el texto: palabras como “si…”, “en caso de…”, “cuando…”, “según…”, “condición”, “validación”, “aprobado/rechazado”, etc.
                    - Considera decisiones implícitas (p. ej., “si falla X, entonces Y”).
                    B) Representación:
                    - En flowchart: usa nodos de decisión con llaves `{}` y crea una arista por cada rama mencionada (p. ej., `|Sí|`, `|No|`, `|Error|`, `|>5|`, `|<=5|`).
                    - En sequenceDiagram: usa bloques `alt`, `else`, `opt`, `par` según corresponda, replicando TODAS las ramas descritas.
                    - En stateDiagram-v2: usa transiciones guardadas y estados para cada condición y salida.
                    - En journey/gantt/erDiagram/classDiagram/mindmap/pie: si el usuario describe decisiones, transpórtalas al tipo lo más fiel posible (p. ej., subnodos o secciones), sin inventar ramas.
                    C) Exhaustividad:
                    - Crea una salida por CADA rama explicitamente mencionada en el texto.
                    - Si el usuario menciona “en caso contrario”, incluye una rama “Else/Default” exactamente con el texto dado por el usuario.
                    - NO inventes ramas no mencionadas. Si el texto insinúa varias pero no las nombra, pregunta solo si es crítico (ver 4).
                    D) Claridad de etiquetas:
                    - Etiqueta cada arista/segmento de decisión con el texto de la condición tal como lo dio el usuario (o su versión mínimamente normalizada).
                    E) Bucles y salidas:
                    - Si una rama regresa a un paso previo (reintento/loop), dibuja la arista de regreso explícitamente.
                    - Asegura que todas las ramas lleven a un estado/acción o terminación definida, si así lo indica el usuario.

                    [TIPOS DE DIAGRAMA SOPORTADOS]
                    - flowchart (flowchart TD/LR/RL/BT)
                    - sequenceDiagram
                    - classDiagram
                    - stateDiagram-v2
                    - erDiagram
                    - gantt
                    - journey
                    - mindmap
                    - pie
                    Si no se especifica el tipo y no es inferible por la estructura, usa `flowchart TD`.

                    [MAPEO Y CONVENCIONES]
                    - Pasos → nodos; relaciones → aristas con etiquetas de acción.
                    - Decisiones condicionales → nodos `{}` (flowchart), `alt/else/opt/par` (sequence), transiciones guardadas (state).
                    - Paralelismo → ramas concurrentes o `par`/subgraphs si el usuario lo indica.
                    - Agrupaciones/etapas → `subgraph` cuando el usuario lo pida explícitamente.
                    - Fechas/hitos (Gantt) y actores (sequence/journey) se toman literalmente del usuario.

                    [VALIDACIÓN ANTES DE RESPONDER]
                    - Verifica: tipo correcto, dirección definida, sintaxis válida, nodos referenciados existentes, etiquetas exactas del usuario y ausencia total de texto no-Mermaid.
                    - CHEQUEO DE DECISIONES: confirma que cada decisión identificada tiene TODAS sus ramas mencionadas y correctamente conectadas; sin ramas huérfanas ni faltantes.

                    [EJEMPLO DE RESPUESTA (NO LO IMPRIMAS)]
                    flowchart TD
                    A[Inicio] --> B{¿Condición X?}
                    B -->|Sí| C[Tarea 1]
                    B -->|No| D[Tarea 2]
                    C --> E{¿Pasa validación Y?}
                    E -->|Aprobado| F[Registrar]
                    E -->|Rechazado| G[Notificar]
                    D --> H[Fin]
                    F --> H[Fin]
                    G --> H[Fin]

                    ',
                ],
                [
                    'role' => 'user',
                    'content' => "Crea un diagrama Mermaid con los siguientes pasos: $pasos Recuerda: Solo devuelve el código Mermaid, sin explicaciones ni texto adicional.",
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => 1000,
        ]);


        return response()->json(['response' => $resp->choices[0]->message->content,]);
    }
}
