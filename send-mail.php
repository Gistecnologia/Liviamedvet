<?php
// Configurações do SMTP
$smtp_host = 'smtp.zoho.com';
$smtp_username = 'contato@liviamedvet.com.br'; // Substitua pelo seu email do Zoho
$smtp_password = 'Lfmslmlb_2026!'; // Substitua pela sua senha do Zoho
$smtp_port = 587; // Porta padrão para TLS
$smtp_secure = false;

// Recupera os dados do formulário
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$pet = filter_input(INPUT_POST, 'pet', FILTER_SANITIZE_STRING);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

// Verifica se os campos obrigatórios foram preenchidos
if (!$name || !$email || !$pet || !$message) {
    header('Location: index.html?status=error');
    exit;
}

// Monta o cabeçalho do email
$to = 'contato@liviamedvet.com.br'; // Email que receberá as mensagens
$subject = "Novo contato do site - " . $name;

// Monta o corpo do email
$email_body = "Nome: " . $name . "\n";
$email_body .= "Email: " . $email . "\n";
$email_body .= "Nome do Pet: " . $pet . "\n";
$email_body .= "Mensagem: " . $message . "\n";

// Cabeçalhos adicionais
$headers = array(
    'From' => $smtp_username,
    'Reply-To' => $email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8'
);

try {
    // Inicia a conexão SMTP
    $smtp = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
    if (!$smtp) {
        throw new Exception("Não foi possível conectar ao servidor SMTP: $errstr ($errno)");
    }

    // Função para enviar comando e verificar resposta
    function send_command($smtp, $command, $expected_code = '') {
        fwrite($smtp, $command . "\r\n");
        $response = fgets($smtp, 515);
        if ($expected_code && strpos($response, $expected_code) !== 0) {
            throw new Exception("Erro SMTP: " . $response);
        }
        return $response;
    }

    // Inicia a comunicação SMTP
    send_command($smtp, "", "220");
    send_command($smtp, "EHLO " . $_SERVER['SERVER_NAME'], "250");

    // Inicia TLS se necessário
    if (!$smtp_secure) {
        send_command($smtp, "STARTTLS", "220");
        stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        send_command($smtp, "EHLO " . $_SERVER['SERVER_NAME'], "250");
    }

    // Autenticação
    send_command($smtp, "AUTH LOGIN", "334");
    send_command($smtp, base64_encode($smtp_username), "334");
    send_command($smtp, base64_encode($smtp_password), "235");

    // Envia o email
    send_command($smtp, "MAIL FROM:<" . $smtp_username . ">", "250");
    send_command($smtp, "RCPT TO:<" . $to . ">", "250");
    send_command($smtp, "DATA", "354");

    // Monta o cabeçalho completo
    $header = "";
    foreach ($headers as $key => $value) {
        $header .= $key . ": " . $value . "\r\n";
    }

    // Envia o conteúdo do email
    fwrite($smtp, "Subject: " . $subject . "\r\n");
    fwrite($smtp, $header . "\r\n");
    fwrite($smtp, $email_body . "\r\n.\r\n");
    send_command($smtp, "", "250");

    // Finaliza a conexão
    send_command($smtp, "QUIT", "221");
    fclose($smtp);

    // Redireciona com sucesso
    header('Location: index.html?status=success');
} catch (Exception $e) {
    // Log do erro (você pode personalizar isso)
    error_log("Erro ao enviar email: " . $e->getMessage());
    
    // Redireciona com erro
    header('Location: index.html?status=error');
}
?>
