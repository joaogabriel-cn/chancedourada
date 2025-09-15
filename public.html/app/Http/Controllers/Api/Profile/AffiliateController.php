<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Models\AffiliateHistory;
use App\Models\AffiliateWithdraw;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AffiliateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $indications    = User::where('inviter', auth('api')->id())->count();
        $walletDefault  = Wallet::where('user_id', auth('api')->id())->first();

        return response()->json([
            'status'        => true,
            'code'          => auth('api')->user()->inviter_code,
            'url'           => config('app.url') . '/register?code='.auth('api')->user()->inviter_code,
            'indications'   => $indications,
            'wallet'        => $walletDefault
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function generateCode()
    {
        $code = $this->gencode();
        $setting = \Helper::getSetting();

        if(!empty($code)) {
            $user = auth('api')->user();
            \DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => 2,
                    'model_type' => 'App\Models\User',
                    'model_id' => $user->id,
                ],
            );

            if(auth('api')->user()->update(['inviter_code' => $code, 'affiliate_revenue_share' => $setting->revshare_percentage])) {
                return response()->json(['status' => true, 'message' => trans('Successfully generated code')]);
            }

            return response()->json(['error' => ''], 400);
        }

        return response()->json(['error' => ''], 400);
    }

    /**
     * @return null
     */
    private function gencode() {
        $code = \Helper::generateCode(10);

        $checkCode = User::where('inviter_code', $code)->first();
        if(empty($checkCode)) {
            return $code;
        }

        return $this->gencode();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function makeRequest(Request $request)
    {
        // Obtendo as configurações de saque do usuário
        $settings = Setting::where('id', 1)->first();

        // Verificando se as configurações foram encontradas e se os limites de saque foram definidos
        if ($settings) {
            $withdrawalLimit = $settings->withdrawal_limit;
            $withdrawalPeriod = $settings->withdrawal_period;
        } else {
            // Caso as configurações não tenham sido encontradas, defina os valores padrão ou trate conforme necessário
            $withdrawalLimit = null;
            $withdrawalPeriod = null;
        }

        if ($withdrawalLimit !== null && $withdrawalPeriod !== null) {
            $startDate = now()->startOfDay();
            $endDate = now()->endOfDay();

            switch ($withdrawalPeriod) {
                case 'daily':
                    break;

                case 'weekly':
                    $startDate = now()->startOfWeek();
                    $endDate = now()->endOfWeek();
                    break;
                case 'monthly':
                    $startDate = now()->startOfMonth();
                    $endDate = now()->endOfMonth();
                    break;
                case 'yearly':
                    $startDate = now()->startOfYear();
                    $endDate = now()->endOfYear();
                    break;
            }

            $withdrawalCount = AffiliateWithdraw::where('user_id', auth('api')->user()->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            if ($withdrawalCount >= $withdrawalLimit) {
                return response()->json(['message' => 'Você atingiu o limite de saques para este período'], 400);
            }
        }

        // \Log::info('PayLoss withdrawalCount '.$withdrawalCount);
        // \Log::info('PayLoss withdrawalLimit '.$withdrawalLimit);

        $rules = [
            'amount' => ['required', 'numeric', 'min:'.$settings->min_withdrawal, 'max:'.$settings->max_withdrawal],
            'pix_type' => 'required',
        ];

        switch ($request->pix_type) {
            case 'document':
                $rules['pix_key'] = 'required|cpf_ou_cnpj';
                break;
            case 'email':
                $rules['pix_key'] = 'required|email';
                break;
            default:
                $rules['pix_key'] = 'required';
                break;
        }


        $validator = Validator::make($request->all(), $rules);


        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }


        // Verificando se o usuário tem saldo suficiente para o saque
        $comission = auth('api')->user()->wallet->refer_rewards;

        if (floatval($comission) >= floatval($request->amount) && floatval($request->amount) > 0) {
            // Criando o registro de saque
            AffiliateWithdraw::create([
                'user_id'   => auth('api')->id(),
                'amount'    => $request->amount,
                'pix_key'   => $request->pix_key,
                'pix_type'  => $request->pix_type,
                'currency'  => 'BRL',
                'symbol'    => 'R$',
            ]);

            // Decrementando o saldo do usuário
            auth('api')->user()->wallet->decrement('refer_rewards', $request->amount);

            // Retornando mensagem de sucesso
            return response()->json(['message' => trans('Commission withdrawal successfully carried out')], 200);
        }

        // Retornando mensagem de erro se não houver saldo suficiente
        return response()->json(['message' => trans('Commission withdrawal error')], 400);
    }

    /**
     * Buscar estatísticas detalhadas do afiliado
     */
    public function getStatistics()
    {
        try {
            \Log::info('getStatistics iniciado', ['user_id' => auth('api')->id()]);
            
            $userId = auth('api')->id();
            
            // Estatísticas básicas
            $totalIndications = User::where('inviter', $userId)->count();
            \Log::info('Total indications calculado', ['total' => $totalIndications]);
            
            $wallet = Wallet::where('user_id', $userId)->first();
            \Log::info('Wallet encontrada', ['wallet_id' => $wallet->id ?? 'null']);
            
            // Histórico de comissões
            $commissionHistory = AffiliateHistory::where('inviter', $userId)
                ->with(['user:id,name,email,created_at'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'user_name' => $item->user->name ?? 'Usuário',
                        'user_email' => $item->user->email ?? '',
                        'commission_type' => $item->commission_type,
                        'commission_paid' => $item->commission_paid,
                        'deposited' => $item->deposited,
                        'losses' => $item->losses,
                        'status' => $item->status,
                        'created_at' => $item->created_at->format('d/m/Y H:i'),
                        'created_at_raw' => $item->created_at
                    ];
                });
            \Log::info('Commission history processado', ['count' => $commissionHistory->count()]);

            // Estatísticas por tipo de comissão
            $totalRevshare = AffiliateHistory::where('inviter', $userId)
                ->where('commission_type', 'revshare')
                ->sum('commission_paid');
                
            $totalCpa = AffiliateHistory::where('inviter', $userId)
                ->where('commission_type', 'cpa')
                ->sum('commission_paid');
            \Log::info('Totais calculados', ['revshare' => $totalRevshare, 'cpa' => $totalCpa]);

            // Histórico de saques
            $withdrawals = AffiliateWithdraw::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'amount' => $item->amount,
                        'pix_key' => $item->pix_key,
                        'pix_type' => $item->pix_type,
                        'status' => $item->status,
                        'created_at' => $item->created_at->format('d/m/Y H:i'),
                        'created_at_raw' => $item->created_at
                    ];
                });
            \Log::info('Withdrawals processado', ['count' => $withdrawals->count()]);

            // Indicações recentes
            $recentIndications = User::where('inviter', $userId)
                ->select('id', 'name', 'email', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'email' => $item->email,
                        'created_at' => $item->created_at->format('d/m/Y H:i'),
                        'created_at_raw' => $item->created_at
                    ];
                });
            \Log::info('Recent indications processado', ['count' => $recentIndications->count()]);

            // Estatísticas mensais (últimos 6 meses)
            $monthlyStats = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthStart = $date->copy()->startOfMonth();
                $monthEnd = $date->copy()->endOfMonth();
                
                $monthlyStats[] = [
                    'month' => $date->format('M/Y'),
                    'month_raw' => $date->format('Y-m'),
                    'revshare' => AffiliateHistory::where('inviter', $userId)
                        ->where('commission_type', 'revshare')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('commission_paid'),
                    'cpa' => AffiliateHistory::where('inviter', $userId)
                        ->where('commission_type', 'cpa')
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('commission_paid'),
                    'indications' => User::where('inviter', $userId)
                        ->whereBetween('created_at', [$monthStart, $monthEnd])
                        ->count()
                ];
            }
            \Log::info('Monthly stats processado', ['count' => count($monthlyStats)]);

            $response = [
                'status' => true,
                'data' => [
                    'overview' => [
                        'total_indications' => $totalIndications,
                        'available_balance' => $wallet->refer_rewards ?? 0,
                        'total_revshare' => $totalRevshare,
                        'total_cpa' => $totalCpa,
                        'total_earnings' => $totalRevshare + $totalCpa,
                        'commission_rate' => auth('api')->user()->affiliate_revenue_share ?? 0,
                        'cpa_value' => auth('api')->user()->affiliate_cpa ?? 0
                    ],
                    'commission_history' => $commissionHistory,
                    'withdrawals' => $withdrawals,
                    'recent_indications' => $recentIndications,
                    'monthly_stats' => $monthlyStats
                ]
            ];
            
            \Log::info('getStatistics finalizado com sucesso', ['response_keys' => array_keys($response['data'])]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            \Log::error('Erro em getStatistics', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'error' => 'Erro interno do servidor',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Teste simples para verificar autenticação
     */
    public function testAuth()
    {
        try {
            $user = auth('api')->user();
            $userId = auth('api')->id();
            
            \Log::info('Teste de autenticação', [
                'user_id' => $userId,
                'user_name' => $user->name ?? 'null',
                'user_email' => $user->email ?? 'null',
                'has_inviter_code' => !empty($user->inviter_code),
                'inviter_code' => $user->inviter_code ?? 'null'
            ]);
            
            return response()->json([
                'status' => true,
                'message' => 'Autenticação funcionando',
                'user_id' => $userId,
                'user_name' => $user->name ?? 'null',
                'user_email' => $user->email ?? 'null',
                'inviter_code' => $user->inviter_code ?? 'null'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erro no teste de autenticação', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'status' => false,
                'error' => 'Erro de autenticação',
                'debug' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Teste simples para verificar consultas básicas
     */
    public function testQueries()
    {
        try {
            $userId = auth('api')->id();
            
            // Teste 1: Contar indicações
            $totalIndications = User::where('inviter', $userId)->count();
            
            // Teste 2: Verificar se tem wallet
            $wallet = Wallet::where('user_id', $userId)->first();
            
            // Teste 3: Contar histórico de comissões
            $totalCommissions = AffiliateHistory::where('inviter', $userId)->count();
            
            \Log::info('Teste de consultas', [
                'user_id' => $userId,
                'total_indications' => $totalIndications,
                'has_wallet' => !empty($wallet),
                'wallet_id' => $wallet->id ?? 'null',
                'total_commissions' => $totalCommissions
            ]);
            
            return response()->json([
                'status' => true,
                'data' => [
                    'user_id' => $userId,
                    'total_indications' => $totalIndications,
                    'has_wallet' => !empty($wallet),
                    'wallet_id' => $wallet->id ?? null,
                    'total_commissions' => $totalCommissions
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Erro no teste de consultas', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'status' => false,
                'error' => 'Erro nas consultas',
                'debug' => $e->getMessage()
            ], 500);
        }
    }
}
