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
                    'content' => 'Eres un creador profesional de diagramas en Mermaid. Tu función es recibir del usuario una serie de pasos o instrucciones para crear un diagrama y generar exclusivamente el código Mermaid correspondiente.

                    REGLAS ESTRICTAS:
                    1. Analiza cuidadosamente todos los pasos proporcionados por el usuario
                    2. No omitas ningún paso o elemento mencionado
                    3. Tu respuesta debe contener ÚNICAMENTE el código Mermaid, sin explicaciones, comentarios, texto adicional o formato markdown
                    4. No agregues elementos que no fueron solicitados
                    5. El código debe ser funcional y seguir la sintaxis correcta de Mermaid
                    6. Si hay ambigüedad en los pasos, usa tu criterio profesional para crear un diagrama coherente pero siempre basado en lo solicitado

                    Ejemplo de respuesta esperada:
                    graph TD
                        A[Inicio] --> B[Proceso]
                        B --> C[Fin]

                    No incluyas ```mermaid ni ningún otro marcado. Solo el código puro.',
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
