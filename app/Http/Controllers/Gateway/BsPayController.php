<?php

namespace App\Http\Controllers\Gateway;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Traits\Affiliates\AffiliateHistoryTrait;
use App\Traits\Gateways\BsPayTrait;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BsPayController extends Controller
{
    use BsPayTrait, AffiliateHistoryTrait;

    public function getQRCodePix(Request $request)
    {
        // chama o método correto do trait
        return self::requestQrcodeBsPay($request);
    }

    public function callbackMethod(Request $request)
    {
        // Log para auditoria/debug
        Log::info('BSPay webhook recebido', ['raw' => $request->all()]);

        // A doc mostra que eles podem enviar dentro de "requestBody"
        $data = $request->input('requestBody', $request->all());

        $transactionType = $data['transactionType'] ?? null;
        $transactionId   = $data['transactionId']  ?? null;
        $status          = $data['status']         ?? null;

        if ($transactionType === 'RECEIVEPIX' && $transactionId && $status) {
            // BSPay usa "PAID" como confirmado
            if (in_array($status, ['PAID','CONFIRMED','APPROVED'], true)) {
                $ok = self::finalizePayment($transactionId);
                Log::info('Finalização de pagamento via webhook', [
                    'transactionId' => $transactionId,
                    'ok' => $ok
                ]);
            } else {
                Log::info('Webhook BSPay com status não-pago', [
                    'transactionId' => $transactionId,
                    'status' => $status
                ]);
            }
        } else {
            Log::warning('Webhook BSPay inválido', compact('transactionType','transactionId','status'));
        }

        // Sempre responder 200 para evitar redelivery agressivo
        return response()->json(['ok' => true], 200);
    }

    public function consultStatusTransactionPix(Request $request)
    {
        return self::consultStatusTransaction($request);
    }

    public function withdrawalFromModal($id)
    {
        $withdrawal = Withdrawal::find($id);

        Log::debug('Withdrawal details:', ['withdrawal' => $withdrawal]);

        if (!empty($withdrawal)) {
            $parm = [
                'pix_key'    => $withdrawal->pix_key,
                'pix_type'   => $withdrawal->pix_type,
                'amount'     => $withdrawal->amount,
                'document'   => $withdrawal->bank_info,
                'payment_id' => $withdrawal->id
            ];

            $resp = self::MakePayment($parm);

            if ($resp) {
                $withdrawal->update(['status' => 1]);

                Notification::make()
                    ->title('Saque solicitado')
                    ->body('Saque solicitado com sucesso')
                    ->success()
                    ->send();

                return back();
            } else {
                Notification::make()
                    ->title('Erro no saque')
                    ->body('Erro ao solicitar o saque')
                    ->danger()
                    ->send();

                return back();
            }
        }

        return back();
    }

    public function cancelWithdrawalFromModal($id)
    {
        $withdrawal = Withdrawal::find($id);

        if (!$withdrawal) {
            return back()->with('error', 'Saque não encontrado.');
        }

        if ($withdrawal->status != 0) {
            return back()->with('error', 'Saque já processado e não pode ser cancelado.');
        }

        // Atualiza o status do saque para cancelado
        $withdrawal->update(['status' => 3]);

        // Estorna o valor para a carteira do usuário
        $wallet = Wallet::where('user_id', $withdrawal->user_id)->first();

        if ($wallet) {
            $wallet->balance += $withdrawal->amount;
            $wallet->save();
        }

        Notification::make()
            ->title('Saque cancelado')
            ->body('Saque cancelado com sucesso e valor estornado.')
            ->success()
            ->send();

        return back();
    }
}
