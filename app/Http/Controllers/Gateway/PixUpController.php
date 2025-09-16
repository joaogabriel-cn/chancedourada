<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PixUpController extends Controller
{
    private $apiUrl;

    public function __construct()
    {
        // Usa URL configurada no .env ou o padrão
        $this->apiUrl = env('PIXUP_URL', 'https://api.pixup.com.br/v2');
    }

    /**
     * Gera o token OAuth
     */
    private function getToken()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('PIXUP_CLIENT_ID') . ':' . env('PIXUP_CLIENT_SECRET')),
            'Accept'        => 'application/json',
        ])->post($this->apiUrl . '/oauth/token', [
            'grant_type' => 'client_credentials'
        ]);

        return $response->json()['access_token'] ?? null;
    }

    /**
     * Criar cobrança PIX
     */
    public function createCharge(Request $request)
    {
        $token = $this->getToken();

        if (!$token) {
            return response()->json(['error' => 'Erro ao gerar token'], 500);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ])->post($this->apiUrl . '/pix/create-charge', [
            'value'        => $request->value,
            'description'  => $request->description ?? 'Pagamento via PixUp',
            'external_id'  => uniqid()
        ]);

        return $response->json();
    }

    /**
     * Callback (PixUp envia quando pagamento é concluído)
     */
    public function callback(Request $request)
    {
        // Aqui você pode salvar no banco de dados o pagamento confirmado
        // Exemplo: Transaction::create($request->all());

        return response()->json([
            'status' => 'received',
            'data'   => $request->all()
        ]);
    }

    /**
     * Consultar status de uma transação
     */
    public function consultStatus(Request $request)
    {
        $token = $this->getToken();

        if (!$token) {
            return response()->json(['error' => 'Erro ao gerar token'], 500);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ])->get($this->apiUrl . '/pix/consult-status/' . $request->transaction_id);

        return $response->json();
    }
}
