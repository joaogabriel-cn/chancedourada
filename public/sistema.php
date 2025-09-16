<?php
// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carregar configurações do Laravel
require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (Exception $e) {
    // Se não conseguir carregar o .env, usar configurações padrão
    $_ENV['DB_HOST'] = '127.0.0.1';
    $_ENV['DB_DATABASE'] = 'u228447457_raspavue';
    $_ENV['DB_USERNAME'] = 'root';
    $_ENV['DB_PASSWORD'] = '';
}

// Configuração do banco de dados do .env
$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbname = $_ENV['DB_DATABASE'] ?? 'u228447457_raspavue';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

// Debug - mostrar configurações (remover depois)
echo "<!-- Debug: Host=$host, DB=$dbname, User=$username -->";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Função para executar queries
function executeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        echo "<!-- Erro SQL: " . $e->getMessage() . " -->";
        return false;
    }
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_gateway':
                $sql = "UPDATE gateways SET 
                        bspay_uri = ?, bspay_cliente_id = ?, bspay_cliente_secret = ?
                        WHERE id = 1";
                executeQuery($pdo, $sql, [
                    $_POST['bspay_uri'], $_POST['bspay_cliente_id'], $_POST['bspay_cliente_secret']
                ]);
                break;
                

                
            case 'delete_deposit':
                $sql = "DELETE FROM deposits WHERE id = ?";
                executeQuery($pdo, $sql, [$_POST['deposit_id']]);
                break;
                
            case 'add_deposit':
                $sql = "INSERT INTO deposits (payment_id, user_id, amount, type, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
                executeQuery($pdo, $sql, [
                    uniqid(), $_POST['user_id'], $_POST['amount'], $_POST['type'], $_POST['status']
                ]);
                break;
        }
    }
}

// Buscar dados
$gateways_stmt = executeQuery($pdo, "SELECT * FROM gateways WHERE id = 1");
$gateways = $gateways_stmt ? $gateways_stmt->fetch(PDO::FETCH_ASSOC) : [];



$deposits_stmt = executeQuery($pdo, "SELECT d.*, u.name as user_name FROM deposits d LEFT JOIN users u ON d.user_id = u.id ORDER BY d.created_at DESC LIMIT 50");
$deposits = $deposits_stmt ? $deposits_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

$users_stmt = executeQuery($pdo, "SELECT id, name, email, balance, created_at FROM users ORDER BY created_at DESC LIMIT 20");
$users = $users_stmt ? $users_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Administração</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1a;
            color: #fff;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .section {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .section h2 {
            color: #00ff88;
            margin-bottom: 15px;
            border-bottom: 2px solid #00ff88;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        
        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 5px;
            background: #1a1a1a;
            color: #fff;
            font-size: 14px;
        }
        
        input[type="text"]:focus, input[type="number"]:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #00ff88;
        }
        
        .btn {
            background: #00ff88;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .btn:hover {
            background: #00cc6a;
        }
        
        .btn-danger {
            background: #ff4444;
            color: #fff;
        }
        
        .btn-danger:hover {
            background: #cc3333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        
        th {
            background: #333;
            color: #00ff88;
            font-weight: bold;
        }
        
        tr:hover {
            background: #333;
        }
        
        .status-0 { color: #ffaa00; }
        .status-1 { color: #00ff88; }
        .status-2 { color: #ff4444; }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Sistema de Administração</h1>
            <p>Painel de controle para gerenciar configurações e dados do sistema</p>
        </div>

        <!-- Configurações de Gateways -->
        <div class="section">
            <h2>💰 Configurações de Gateways</h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_gateway">
                <div>
                    <h3>BSPay</h3>
                    <div class="form-group">
                        <label>URI:</label>
                        <input type="text" name="bspay_uri" value="<?= htmlspecialchars($gateways['bspay_uri'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Client ID:</label>
                        <input type="text" name="bspay_cliente_id" value="<?= htmlspecialchars($gateways['bspay_cliente_id'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Client Secret:</label>
                        <input type="text" name="bspay_cliente_secret" value="<?= htmlspecialchars($gateways['bspay_cliente_secret'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" class="btn">Salvar Configurações</button>
            </form>
        </div>



        <!-- Gerenciar Depósitos -->
        <div class="section">
            <h2>💳 Gerenciar Depósitos</h2>
            
            <!-- Adicionar Depósito -->
            <form method="POST" style="margin-bottom: 20px;">
                <input type="hidden" name="action" value="add_deposit">
                <div class="grid">
                    <div class="form-group">
                        <label>Usuário ID:</label>
                        <input type="number" name="user_id" required>
                    </div>
                    <div class="form-group">
                        <label>Valor:</label>
                        <input type="number" name="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Tipo:</label>
                        <select name="type" required>
                            <option value="pix">PIX</option>
                            <option value="card">Cartão</option>
                            <option value="transfer">Transferência</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" required>
                            <option value="0">Pendente</option>
                            <option value="1">Aprovado</option>
                            <option value="2">Rejeitado</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn">Adicionar Depósito</button>
            </form>

            <!-- Lista de Depósitos -->
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Valor</th>
                        <th>Tipo</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deposits as $deposit): ?>
                    <tr>
                        <td><?= $deposit['id'] ?></td>
                        <td><?= htmlspecialchars($deposit['user_name'] ?? 'N/A') ?></td>
                        <td>R$ <?= number_format($deposit['amount'], 2, ',', '.') ?></td>
                        <td><?= strtoupper($deposit['type']) ?></td>
                        <td class="status-<?= $deposit['status'] ?>">
                            <?= $deposit['status'] == 0 ? 'Pendente' : ($deposit['status'] == 1 ? 'Aprovado' : 'Rejeitado') ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($deposit['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_deposit">
                                <input type="hidden" name="deposit_id" value="<?= $deposit['id'] ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Tem certeza?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Lista de Usuários -->
        <div class="section">
            <h2>👥 Usuários Recentes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Saldo</th>
                        <th>Data Cadastro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>R$ <?= number_format($user['balance'], 2, ',', '.') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Estatísticas Rápidas -->
        <div class="section">
            <h2>📊 Estatísticas</h2>
            <?php
            $total_users_stmt = executeQuery($pdo, "SELECT COUNT(*) as total FROM users");
            $total_users = $total_users_stmt ? $total_users_stmt->fetch(PDO::FETCH_ASSOC)['total'] : 0;
            
            $total_deposits_stmt = executeQuery($pdo, "SELECT COUNT(*) as total FROM deposits");
            $total_deposits = $total_deposits_stmt ? $total_deposits_stmt->fetch(PDO::FETCH_ASSOC)['total'] : 0;
            
            $total_deposits_value_stmt = executeQuery($pdo, "SELECT SUM(amount) as total FROM deposits WHERE status = 1");
            $total_deposits_value = $total_deposits_value_stmt ? ($total_deposits_value_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0) : 0;
            
            $pending_deposits_stmt = executeQuery($pdo, "SELECT COUNT(*) as total FROM deposits WHERE status = 0");
            $pending_deposits = $pending_deposits_stmt ? $pending_deposits_stmt->fetch(PDO::FETCH_ASSOC)['total'] : 0;
            ?>
            <div class="grid">
                <div style="text-align: center; padding: 20px; background: #333; border-radius: 5px;">
                    <h3>👥 Total de Usuários</h3>
                    <p style="font-size: 2em; color: #00ff88;"><?= number_format($total_users) ?></p>
                </div>
                <div style="text-align: center; padding: 20px; background: #333; border-radius: 5px;">
                    <h3>💳 Total de Depósitos</h3>
                    <p style="font-size: 2em; color: #00ff88;"><?= number_format($total_deposits) ?></p>
                </div>
                <div style="text-align: center; padding: 20px; background: #333; border-radius: 5px;">
                    <h3>💰 Valor Total Aprovado</h3>
                    <p style="font-size: 2em; color: #00ff88;">R$ <?= number_format($total_deposits_value, 2, ',', '.') ?></p>
                </div>
                <div style="text-align: center; padding: 20px; background: #333; border-radius: 5px;">
                    <h3>⏳ Depósitos Pendentes</h3>
                    <p style="font-size: 2em; color: #ffaa00;"><?= number_format($pending_deposits) ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh a cada 30 segundos
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Notificação de sucesso
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        alert('Operação realizada com sucesso!');
        <?php endif; ?>
    </script>
</body>
</html>
