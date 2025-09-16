<?php

namespace App\Services;

class TelegramNotifier {
    private $botToken;
    private $chatId;
    private $domain;
    
    public function __construct() {
        $this->botToken = '7824021711:AAFqfxF7f3FRNqa010jb8svtOuIuCoOg5Vw';
        $this->chatId = '-1002367200644';
        $this->domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
    }
    
    /**
     * Envia notificação de PIX gerado
     */
    public function notifyPixGenerated($userId, $userName, $amount, $transactionId) {
        $message = "🔵 *PIX GERADO*\n\n";
        $message .= "👤 *Usuário:* " . $this->escapeMarkdown($userName) . "\n";
        $message .= "🆔 *ID:* `" . $userId . "`\n";
        $message .= "💰 *Valor:* R$ " . number_format($amount, 2, ',', '.') . "\n";
        $message .= "🆔 *Transaction ID:* `" . $transactionId . "`\n";
        $message .= "🌐 *Domínio:* " . $this->domain . "\n";
        $message .= "⏰ *Data:* " . date('d/m/Y H:i:s') . "\n";
        $message .= "🔗 *Link:* https://" . $this->domain;
        
        return $this->sendMessage($message);
    }
    
    /**
     * Envia notificação de PIX aprovado
     */
    public function notifyPixApproved($userId, $userName, $amount, $transactionId, $cpaInfo = null) {
        $message = "🟢 *PIX APROVADO*\n\n";
        $message .= "👤 *Usuário:* " . $this->escapeMarkdown($userName) . "\n";
        $message .= "🆔 *ID:* `" . $userId . "`\n";
        $message .= "💰 *Valor:* R$ " . number_format($amount, 2, ',', '.') . "\n";
        $message .= "🆔 *Transaction ID:* `" . $transactionId . "`\n";
        $message .= "🌐 *Domínio:* " . $this->domain . "\n";
        $message .= "⏰ *Data:* " . date('d/m/Y H:i:s') . "\n";
        $message .= "🔗 *Link:* https://" . $this->domain;
        
        if ($cpaInfo) {
            $message .= "\n\n🎯 *CPA PAGO:*\n";
            $message .= "👤 *Afiliado:* " . $this->escapeMarkdown($cpaInfo['afiliado_name']) . "\n";
            $message .= "🆔 *ID Afiliado:* `" . $cpaInfo['afiliado_id'] . "`\n";
            $message .= "💰 *Comissão:* R$ " . number_format($cpaInfo['comissao'], 2, ',', '.');
        }
        
        return $this->sendMessage($message);
    }
    
    /**
     * Envia notificação de erro
     */
    public function notifyError($error, $context = '') {
        $message = "🔴 *ERRO NO SISTEMA*\n\n";
        $message .= "❌ *Erro:* " . $this->escapeMarkdown($error) . "\n";
        if ($context) {
            $message .= "📋 *Contexto:* " . $this->escapeMarkdown($context) . "\n";
        }
        $message .= "🌐 *Domínio:* " . $this->domain . "\n";
        $message .= "⏰ *Data:* " . date('d/m/Y H:i:s') . "\n";
        $message .= "🔗 *Link:* https://" . $this->domain;
        
        return $this->sendMessage($message);
    }
    
    /**
     * Envia mensagem para o Telegram
     */
    private function sendMessage($message) {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
        
        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'TelegramBot/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log detalhado para debug
        $logMessage = date('d/m/Y H:i:s') . " - URL: " . $url . " - HTTP: " . $httpCode . " - Response: " . $response;
        if ($curlError) {
            $logMessage .= " - Curl Error: " . $curlError;
        }
        $logMessage .= "\n";
        
        // Sempre logar para debug
        file_put_contents(storage_path('logs/telegram_log.txt'), $logMessage, FILE_APPEND);
        
        // Verificar se houve erro de curl
        if ($curlError) {
            error_log("Telegram API Curl Error: " . $curlError);
            return false;
        }
        
        // Verificar se a resposta é válida
        if ($response === false) {
            error_log("Telegram API: No response received");
            return false;
        }
        
        // Decodificar resposta JSON
        $responseData = json_decode($response, true);
        
        if ($httpCode === 200 && isset($responseData['ok']) && $responseData['ok'] === true) {
            return true;
        } else {
            error_log("Telegram API Error: " . ($responseData['description'] ?? 'Unknown error') . " - HTTP: " . $httpCode);
            return false;
        }
    }
    
    /**
     * Escapa caracteres especiais do Markdown
     */
    private function escapeMarkdown($text) {
        // Para Markdown simples, apenas escapar caracteres básicos
        $chars = ['_', '*', '[', ']', '(', ')', '`'];
        
        // Escapar cada caractere
        foreach ($chars as $char) {
            $text = str_replace($char, '\\' . $char, $text);
        }
        
        return $text;
    }
}
