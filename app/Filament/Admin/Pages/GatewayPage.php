<?php

namespace App\Filament\Admin\Pages;

use App\Models\Gateway;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

class GatewayPage extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.gateway-page';

    public ?array $data = [];
    public Gateway $setting;

    /**
     * @dev @venixplataformas
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * @return void
     */
    public function mount(): void
    {
        $gateway = Gateway::first();
        if(!empty($gateway)) {
            $this->setting = $gateway;
            $this->form->fill($this->maskSensitiveData($this->setting->toArray()));
        }else{
            $this->form->fill();
        }
    }

    /**
     * Mascara dados sensíveis com asteriscos
     * @param array $data
     * @return array
     */
    private function maskSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'pradapay_apikey',
            'bspay_cliente_id',
            'bspay_cliente_secret',
            'nomad_secret',
            'shark_public_key',
            'shark_private_key',
            'digitopay_cliente_id',
            'digitopay_cliente_secret'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                // Ocultar completamente com asteriscos
                $data[$field] = str_repeat('*', 20);
            }
        }

        return $data;
    }

    /**
     * @param Form $form
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('PradaPay')
                    ->description('Ajustes de credenciais para a PradaPay')
                    ->schema([
                        TextInput::make('pradapay_uri')
                            ->label('Client URI')
                            ->placeholder('Digite a url da api')
                            ->maxLength(191),
                        TextInput::make('pradapay_apikey')
                            ->label('API KEY')
                            ->placeholder('Digite a apikey')
                            ->maxLength(191)
                    ])->columns(2),
                    
                Section::make('BSPay')
                    ->description('Ajustes de credenciais para a BSPay')
                    ->schema([
                        TextInput::make('bspay_uri')
                            ->label('Client URI')
                            ->placeholder('Digite a url da api')
                            ->default('https://api.bspay.co/')
                            ->maxLength(191),
                            
                        TextInput::make('bspay_cliente_id')
                            ->label('Client ID')
                            ->placeholder('Digite o client ID')
                            ->maxLength(191)
                            ->type('text'),
                        TextInput::make('bspay_cliente_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o client secret')
                            ->maxLength(191)
                            ->type('text'),
                    ])->columns(3),
                Section::make('Nomad')
                    ->description('Ajustes de credenciais para a Nomad')
                    ->schema([
                        TextInput::make('nomad_uri')
                            ->label('Client URI')
                            ->placeholder('Digite a url da api')
                            ->default('https://api.nomadfy.app/v1/')
                            ->maxLength(191),
                        TextInput::make('nomad_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o client secret')
                            ->maxLength(191)
                            ->type('text'),
                    ])->columns(3),
                Section::make('Sharkpay')
                    ->description('Ajustes de credenciais para a Sharkpay: https://www.sharkpay.com.br')
                    ->schema([
                        TextInput::make('shark_public_key')
                            ->label('Public Key')
                            ->placeholder('Digite o Client ID')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('shark_private_key')
                            ->label('Private Key')
                            ->placeholder('Digite a Private Key')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ]),
                Section::make('DigitoPay')
                    ->description('Ajustes de credenciais para a DigitoPay')
                    ->schema([
                        TextInput::make('digitopay_uri')
                            ->label('Client URI')
                            ->placeholder('Digite a url da api')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('digitopay_cliente_id')
                            ->label('Client ID')
                            ->placeholder('Digite o client ID')
                            ->maxLength(191)
                            ->columnSpanFull(),
                        TextInput::make('digitopay_cliente_secret')
                            ->label('Client Secret')
                            ->placeholder('Digite o client secret')
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
            ])
            ->statePath('data');
    }


    /**
     * @return void
     */
    public function submit(): void
    {
        try {
            if(env('APP_DEMO')) {
                Notification::make()
                    ->title('Atenção')
                    ->body('Você não pode realizar está alteração na versão demo')
                    ->danger()
                    ->send();
                return;
            }

            $setting = Gateway::first();
            if(!empty($setting)) {
                // Preservar dados originais se não foram alterados
                $dataToUpdate = $this->preserveOriginalData($this->data, $setting);
                
                if($setting->update($dataToUpdate)) {
                    Notification::make()
                        ->title('Chaves Alteradas')
                        ->body('Suas chaves foram alteradas com sucesso!')
                        ->success()
                        ->send();
                }
            }else{
                if(Gateway::create($this->data)) {
                    Notification::make()
                        ->title('Chaves Criadas')
                        ->body('Suas chaves foram criadas com sucesso!')
                        ->success()
                        ->send();
                }
            }


        } catch (Halt $exception) {
            Notification::make()
                ->title('Erro ao alterar dados!')
                ->body('Erro ao alterar dados!')
                ->danger()
                ->send();
        }
    }

    /**
     * Preserva dados originais se não foram alterados
     * @param array $newData
     * @param Gateway $original
     * @return array
     */
    private function preserveOriginalData(array $newData, Gateway $original): array
    {
        $sensitiveFields = [
            'pradapay_apikey',
            'bspay_cliente_id',
            'bspay_cliente_secret',
            'nomad_secret',
            'shark_public_key',
            'shark_private_key',
            'digitopay_cliente_id',
            'digitopay_cliente_secret'
        ];

        foreach ($sensitiveFields as $field) {
            // Se o campo está vazio ou contém apenas asteriscos, preservar o valor original
            if (isset($newData[$field]) && 
                (empty($newData[$field]) || $this->isMaskedValue($newData[$field]))) {
                $newData[$field] = $original->$field;
            }
        }

        return $newData;
    }

    /**
     * Verifica se o valor está mascarado (contém asteriscos)
     * @param string $value
     * @return bool
     */
    private function isMaskedValue(string $value): bool
    {
        return strpos($value, '*') !== false;
    }
}
