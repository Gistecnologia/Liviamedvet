<?php
// Configurações do SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);  // Porta padrão para TLS
define('SMTP_USERNAME', 'seu-email@gmail.com'); // Substitua pelo seu e-mail
define('SMTP_PASSWORD', 'sua-senha-de-app'); // Substitua pela sua senha de app do Gmail
define('SMTP_FROM', 'seu-email@gmail.com'); // E-mail que aparecerá como remetente
define('SMTP_FROM_NAME', 'Lívia.medvet'); // Nome que aparecerá como remetente

// Função para enviar e-mail via SMTP
function smtpMailer($to, $subject, $body) {
    $header = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
    $header .= "Reply-To: " . SMTP_FROM . "\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    $socket = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
    
    if (!$socket) {
        return false;
    }

    $res = fgets($socket);
    if (substr($res, 0, 3) !== '220') {
        return false;
    }

    // EHLO
    fputs($socket, "EHLO " . SMTP_HOST . "\r\n");
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '250') {
        return false;
    }
    while(substr($res, 3, 1) === '-') {
        $res = fgets($socket);
    }

    // STARTTLS
    fputs($socket, "STARTTLS\r\n");
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '220') {
        return false;
    }

    // Ativa criptografia TLS
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    // EHLO novamente após TLS
    fputs($socket, "EHLO " . SMTP_HOST . "\r\n");
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '250') {
        return false;
    }
    while(substr($res, 3, 1) === '-') {
        $res = fgets($socket);
    }

    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '334') {
        return false;
    }

    fputs($socket, base64_encode(SMTP_USERNAME) . "\r\n");
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '334') {
        return false;
    }

    fputs($socket, base64_encode(SMTP_PASSWORD) . "\r\n");
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '235') {
        return false;
    }

    // MAIL FROM
    fputs($socket, "MAIL FROM:<" . SMTP_FROM . ">\r\n");
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '250') {
        return false;
    }

    // RCPT TO
    fputs($socket, "RCPT TO:<" . $to . ">\r\n");
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '250') {
        return false;
    }

    // DATA
    fputs($socket, "DATA\r\n");
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '354') {
        return false;
    }

    // Envia o cabeçalho e o corpo do e-mail
    fputs($socket, "To: " . $to . "\r\n");
    fputs($socket, "Subject: " . $subject . "\r\n");
    fputs($socket, $header . "\r\n");
    fputs($socket, $body . "\r\n.\r\n");
    
    $res = fgets($socket);
    if (substr($res, 0, 3) !== '250') {
        return false;
    }

    // QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return true;
}

// Processar o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    // Construir o corpo do e-mail em HTML
    $emailBody = "
    <!DOCTYPE html>
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
            <h2>Nova mensagem do site Lívia.medvet</h2>
            <div class='info'><strong>Nome:</strong> {$name}</div>
            <div class='info'><strong>E-mail:</strong> {$email}</div>
            <div class='info'><strong>Telefone:</strong> {$phone}</div>
            <div class='info'><strong>Assunto:</strong> {$subject}</div>
            <div class='info'><strong>Mensagem:</strong><br>{$message}</div>
        </div>
    </body>
    </html>";

    // Enviar o e-mail
    $sent = smtpMailer(SMTP_FROM, "Contato do Site: " . $subject, $emailBody);
    
    // Redirecionar com status
    if ($sent) {
        header('Location: index.html?status=success#contato');
    } else {
        header('Location: index.html?status=error#contato');
    }
    exit;
}
?>
