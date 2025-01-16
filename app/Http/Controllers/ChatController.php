<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\ChatContext;
use DB;

class ChatController extends Controller
{
    protected $context = [];

    public function chat(Request $request)
    {
        $aux = DB::select('select * from leads where reason_id = 1');
        dd($aux);

        $sessionId = $request->session()->getId();
        $question = $request->input('message');

        $this->context = $this->loadContext($sessionId);

        $queryMessages = $this->buildQueryMessages($question);
        dump($queryMessages);

        $queryResponse = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => $queryMessages,
            'max_tokens' => 150,
            'temperature' => 0.1,
        ]);

        $aiQueryResponse = $queryResponse['choices'][0]['message']['content'];

        try {
            $query = $this->extractQuery($aiQueryResponse);
            $results = $this->executeQuery($query);
            dump($query);

            $this->updateContext($question, json_encode($results));
            $this->saveContext($sessionId, $this->context);

            // Build a human-friendly response using GPT-3.5 for cost optimization
            $responseMessages = $this->buildResponseMessages($question, $results);
            $friendlyResponse = OpenAI::chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $responseMessages,
                'max_tokens' => 100,
                'temperature' => 0.4,
            ]);

            $aiFriendlyResponse = $friendlyResponse['choices'][0]['message']['content'];

            return response()->json(['response' => $aiFriendlyResponse]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    protected function loadContext($sessionId)
    {
        $chatContext = ChatContext::firstOrCreate(
            ['session_id' => $sessionId],
            ['context' => '']
        );

        return explode("\n", $chatContext->context);
    }

    protected function buildQueryMessages($question)
    {
        $compactContext = $this->compactContext($this->context);

        return [
            ['role' => 'system', 'content' => 'Eres un asistente experto en construir consultas SQL para una base de datos relacional.usa LIKE para coincidencias aproximadas en nombres de todas las tablas excepto leads. Traduce términos como "sin gestionar, venta..." a "reasons.name LIKE \"%...%\", hay varios reasons en reasons.name similares, para esto tener en cuenta el funnel_id", siempre busca informacion del año actual a menos que especifiquen otro y asegúrate de devolver consultas solo de lectura.'],
            ['role' => 'user', 'content' => "Esquema de la base de datos:\n- leads(id,name,brand_id,distributor_id,model_id,reason_id,created_at)\n- distributors(id,name)\n- brands(id,name)\n- models(id,name)\n- reasons(id,name,funnel_id), estados de funnel_id(1 primera etapa del lead, 2 estados de contactado,3 estados de agendamiento, 4 estados de inicio de proceso de venta, 5 ventas)\nLa conversación anterior:\n$compactContext\nNueva pregunta: $question\nDevuelve únicamente el query SQL de lectura."],
        ];
    }

    protected function buildResponseMessages($question, $results)
    {
        $compactContext = $this->compactContext($this->context);

        return [
            ['role' => 'system', 'content' => 'Eres un asesor asistente para una plataforma de leads que proporciona respuestas profesionales basadas en resultados de consultas de leads y ventas.'],
            ['role' => 'user', 'content' => "Pregunta original: $question\nResultados: " . json_encode($results) . "\nPor favor, responde de manera profesional y concisa"],
        ];
    }

    protected function updateContext($question, $response)
    {
        $this->context[] = "Pregunta: $question\nRespuesta: $response";
        if (count($this->context) > 5) {
            array_shift($this->context);
        }
    }

    protected function extractQuery($aiResponse)
    {
        if (preg_match('/\bSELECT\b.*?\bFROM\b.*?(;|$)/is', $aiResponse, $matches)) {
            return trim($matches[0]);
        }

        throw new \Exception("No se encontró un query SQL válido.");
    }

    protected function executeQuery($query)
    {
        $answer_query = DB::select($query);
        return $answer_query;
    }

    protected function compactContext($context)
    {
        $recentContext = array_slice($context, -5);

        if (count($context) > 5) {
            $recentContext[] = "Resumido: Consultas previas sobre leads(id,name,brand_id,distributor_id,model_id,reason_id,created_at)";
        }

        return implode("\n", $recentContext);
    }

    protected function saveContext($sessionId, $context)
    {
        $chatContext = ChatContext::firstOrCreate(['session_id' => $sessionId]);
        $chatContext->context = implode("\n", $context);
        $chatContext->save();
    }
}
