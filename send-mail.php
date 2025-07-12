<?php
// Configurações do SMTP
$smtp_host = 'smtp.zoho.com';
$smtp_port = 587; // Porta padrão para TLS
$smtp_username = 'contato@liviamedvet.com.br'; // Substitua pelo seu email
$smtp_password = 'Lfmslmlb_2026!'; // Substitua pela sua senha de aplicativo do Gmail

// Dados do formulário
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$pet = filter_input(INPUT_POST, 'pet', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
$terms = isset($_POST['terms']) ? 'Sim' : 'Não';

// Validação básica
if (!$name || !$email || !$pet || !$message) {
    die('Por favor, preencha todos os campos obrigatórios.');
}

// Cabeçalhos do email
$to = 'contato@liviamedvet.com.br';
$subject = 'Nova mensagem do formulário de contato - Lívia.medvet';

// Corpo do email em HTML
$email_body = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>Nova mensagem do formulário de contato</h2>
        <div class='field'>
            <div class='label'>Nome:</div>
            <div>" . htmlspecialchars($name) . "</div>
        </div>
        <div class='field'>
            <div class='label'>Email:</div>
            <div>" . htmlspecialchars($email) . "</div>
        </div>
        <div class='field'>
            <div class='label'>Nome do Pet:</div>
            <div>" . htmlspecialchars($pet) . "</div>
        </div>
        <div class='field'>
            <div class='label'>Mensagem:</div>
            <div>" . nl2br(htmlspecialchars($message)) . "</div>
        </div>
        <div class='field'>
            <div class='label'>Aceitou receber comunicações:</div>
            <div>" . $terms . "</div>
        </div>
    </div>
</body>
</html>";

// Cabeçalhos adicionais
$headers = array(
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: ' . $smtp_username,
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion()
);

try {
    // Iniciar conexão SMTP
    $smtp = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
    if (!$smtp) {
        throw new Exception("Não foi possível conectar ao servidor SMTP: $errstr ($errno)");
    }

    // Função para ler resposta do servidor
    function getSmtpResponse($smtp) {
        $response = '';
        while ($str = fgets($smtp, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $response;
    }

    // Função para enviar comando SMTP
    function sendCommand($smtp, $command) {
        fwrite($smtp, $command . "\r\n");
        return getSmtpResponse($smtp);
    }

    // Iniciar comunicação SMTP
    getSmtpResponse($smtp);
    
    // EHLO
    sendCommand($smtp, "EHLO " . $_SERVER['SERVER_NAME']);
    
    // Iniciar TLS
    sendCommand($smtp, "STARTTLS");
    stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    
    // EHLO novamente após TLS
    sendCommand($smtp, "EHLO " . $_SERVER['SERVER_NAME']);
    
    // Autenticação
    sendCommand($smtp, "AUTH LOGIN");
    sendCommand($smtp, base64_encode($smtp_username));
    sendCommand($smtp, base64_encode($smtp_password));
    
    // Envio do email
    sendCommand($smtp, "MAIL FROM: <$smtp_username>");
    sendCommand($smtp, "RCPT TO: <$to>");
    sendCommand($smtp, "DATA");
    
    // Montagem do email completo
    $email_content = implode("\r\n", $headers) . "\r\n\r\n" . $email_body . "\r\n.";
    sendCommand($smtp, $email_content);
    
    // Finalizar conexão
    sendCommand($smtp, "QUIT");
    fclose($smtp);

    // Redirecionar com mensagem de sucesso
    header('Location: index.html?status=success#contato');
    exit;

} catch (Exception $e) {
    // Log do erro
    error_log("Erro no envio do email: " . $e->getMessage());
    
    // Redirecionar com mensagem de erro
    header('Location: index.html?status=error#contato');
    exit;
}
?>
