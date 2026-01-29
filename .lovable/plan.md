
# Plano: Corrigir Envio de Email (Método Simples)

## Diagnóstico

O email não está sendo enviado porque os headers têm um problema: o formato `From: Nome <email>` pode ser rejeitado por alguns servidores. Vou simplificar para o formato que funcionou antes.

---

## Correção Proposta

Modificar os headers do email em `docs/php/master/restaurants.php` (linhas 329-339):

### De:
```php
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Cardápio Floripa <noreply@cardapiofloripa.com.br>\r\n";

if (mail($restaurant['email'], $subject, $body, $headers)) {
```

### Para:
```php
$to = $restaurant['email'];
$headers = [];
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: text/html; charset=UTF-8";
$headers[] = "From: noreply@cardapiofloripa.com.br";
$headers[] = "Reply-To: noreply@cardapiofloripa.com.br";
$headers[] = "X-Mailer: PHP/" . phpversion();

$headerString = implode("\r\n", $headers);

$emailSent = @mail($to, $subject, $body, $headerString);

if ($emailSent) {
    $message = 'Email enviado com sucesso para ' . $restaurant['email'] . '!';
} else {
    $lastError = error_get_last();
    $errorMsg = $lastError ? $lastError['message'] : 'Função mail() indisponível';
    $message = 'Falha ao enviar email: ' . $errorMsg;
}
```

---

## Alterações Principais

1. **Remover nome do From** - Usar apenas o email sem "Cardápio Floripa <...>"
2. **Adicionar Reply-To** - Header importante para entregabilidade
3. **Adicionar X-Mailer** - Identificador padrão do PHP
4. **Melhorar tratamento de erro** - Capturar mensagem de erro real se falhar

---

## Arquivo Modificado

```text
docs/php/master/restaurants.php  ← Ajustar headers da função mail()
```

---

## Seção Técnica

O problema está no formato do header `From:`. Alguns servidores de email rejeitam o formato `Nome <email>` quando há caracteres especiais (como "á" em "Cardápio"). Usando apenas o email puro `noreply@cardapiofloripa.com.br` resolve esse problema de compatibilidade.
