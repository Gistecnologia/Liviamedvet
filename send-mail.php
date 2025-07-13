<?php

// Configurações do SMTP
$smtp = array(
    'host' => 'smtp.zoho.com',
    'port' => 587,
    'username' => 'contato@liviamedvet.com.br', // Substitua pelo seu email Zoho
    'password' => 'Lfmslmlb_2026!', // Substitua pela sua senha
    'from_email' => 'contato@liviamedvet.com.br', // Substitua pelo seu email Zoho
    'from_name' => 'Lívia.medvet'
);

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    // Validação básica
    if (empty($name) || empty($email) || empty($message)) {
        header('Location: index.html?status=error#contato');
        exit;
    }

    // Cabeçalhos do e-mail
    $headers = array(
        'From: ' . $smtp['from_name'] . ' <' . $smtp['from_email'] . '>',
        'Reply-To: ' . $email,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    );

    // Conteúdo do e-mail
    $subject = "Nova mensagem do site - " . $name;
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .info { margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Nova mensagem recebida do site</h2>
            <div class='info'><strong>Nome:</strong> {$name}</div>
            <div class='info'><strong>E-mail:</strong> {$email}</div>
            <div class='info'><strong>Telefone:</strong> {$phone}</div>
            <div class='info'><strong>Mensagem:</strong><br>{$message}</div>
        </div>
    </body>
    </html>";

    // Configuração da conexão SMTP
    $errno = 0;
    $errstr = '';
    $timeout = 30;

    // Estabelece conexão com o servidor SMTP
    $fp = fsockopen($smtp['host'], $smtp['port'], $errno, $errstr, $timeout);

    if (!$fp) {
        header('Location: index.html?status=error#contato');
        exit;
    }

    // Lê a resposta inicial do servidor
    $response = fgets($fp);
    
    // Inicia a conversação SMTP
    fputs($fp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = fgets($fp);

    // Inicia STARTTLS
    fputs($fp, "STARTTLS\r\n");
    $response = fgets($fp);

    // Ativa criptografia TLS
    stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    // Re-envia EHLO após TLS
    fputs($fp, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    $response = fgets($fp);

    // Autenticação
    fputs($fp, "AUTH LOGIN\r\n");
    $response = fgets($fp);
    
    fputs($fp, base64_encode($smtp['username']) . "\r\n");
    $response = fgets($fp);
    
    fputs($fp, base64_encode($smtp['password']) . "\r\n");
    $response = fgets($fp);

    // Envia o e-mail
    fputs($fp, "MAIL FROM: <" . $smtp['from_email'] . ">\r\n");
    $response = fgets($fp);

    fputs($fp, "RCPT TO: <" . $smtp['from_email'] . ">\r\n");
    $response = fgets($fp);

    fputs($fp, "DATA\r\n");
    $response = fgets($fp);

    // Monta o cabeçalho completo do e-mail
    $email_headers = implode("\r\n", $headers) . "\r\n";
    $email_headers .= "Subject: " . $subject . "\r\n\r\n";

    // Envia o conteúdo do e-mail
    fputs($fp, $email_headers . $body . "\r\n.\r\n");
    $response = fgets($fp);

    // Encerra a conexão
    fputs($fp, "QUIT\r\n");
    fclose($fp);

    // Redireciona com status de sucesso
    header('Location: index.html?status=success#contato');
    exit;
} else {
    // Se alguém tentar acessar o arquivo diretamente, redireciona para a página inicial
    header('Location: index.html');
    exit;
}
?>
