
# Plano: Corrigir Envio de Email com PHPMailer

## Diagnóstico do Problema

O sistema atual usa a função nativa `mail()` do PHP, que é **pouco confiável** e depende totalmente da configuração do servidor. Problemas comuns:

- Servidores compartilhados bloqueiam `mail()`
- Sem autenticação SMTP, emails caem no spam
- Falhas silenciosas sem log de erro
- Cabeçalhos podem ser rejeitados

## Solução Proposta

Implementar **PHPMailer** com configuração SMTP profissional, garantindo entrega confiável.

---

## Etapa 1: Adicionar PHPMailer ao Projeto

Criar arquivo `docs/php/includes/mailer.php` com classe PHPMailer simplificada ou incluir via Composer.

**Configurações SMTP necessárias no `config/database.php`:**
```php
// Configurações de Email
define('SMTP_HOST', 'smtp.seuservidor.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@cardapiofloripa.com.br');
define('SMTP_PASS', 'sua_senha_aqui');
define('SMTP_FROM_NAME', 'Cardápio Floripa');
define('SMTP_FROM_EMAIL', 'noreply@cardapiofloripa.com.br');
```

---

## Etapa 2: Criar Função de Envio Robusta

Adicionar em `docs/php/includes/functions.php`:

```php
function sendEmail(string $to, string $subject, string $htmlBody): bool {
    // Usar PHPMailer com SMTP autenticado
    // Fallback para mail() se PHPMailer não disponível
    // Log de erros para diagnóstico
}
```

---

## Etapa 3: Atualizar restaurants.php

Substituir chamada `mail()` por função robusta:

```php
// Antes:
if (mail($restaurant['email'], $subject, $body, $headers)) { ... }

// Depois:
if (sendEmail($restaurant['email'], $subject, $body)) {
    $message = 'Email enviado com sucesso!';
} else {
    $error = 'Falha ao enviar email. Verifique configurações SMTP.';
}
```

---

## Etapa 4: Adicionar Log de Email

Criar tabela `email_logs` para rastrear tentativas:

```sql
CREATE TABLE email_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restaurant_id INT,
    email_to VARCHAR(255),
    subject VARCHAR(255),
    status ENUM('sent', 'failed'),
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Alternativa Simples (sem PHPMailer)

Se preferir não instalar PHPMailer, podemos:

1. **Melhorar headers do mail()** - Adicionar cabeçalhos mais completos
2. **Verificar função mail()** - Adicionar diagnóstico para identificar erro
3. **Usar serviço externo** - Integrar com SendGrid, Mailgun ou Resend via API HTTP

---

## Informações Necessárias

Para implementar, preciso saber:

1. **Qual provedor de email você usa?** (Gmail, Hostinger, Locaweb, etc.)
2. **Tem acesso às credenciais SMTP do servidor?**
3. **Prefere PHPMailer ou integração com serviço como SendGrid?**

---

## Seção Técnica

### Estrutura de Arquivos Modificados

```text
docs/php/
├── config/
│   └── database.php          ← Adicionar configurações SMTP
├── includes/
│   ├── functions.php         ← Adicionar função sendEmail()
│   └── mailer.php            ← Nova classe de email (opcional)
└── master/
    └── restaurants.php       ← Substituir mail() por sendEmail()
```

### Dependências

Se usar PHPMailer:
```bash
composer require phpmailer/phpmailer
```

Ou download manual: https://github.com/PHPMailer/PHPMailer
