

# Correção do .htaccess - Acesso ao /master

## Problema

A regra de exclusão no `.htaccess` usa `!^/master/` (com barra final obrigatória). Ao acessar `cardapiofloripa.com.br/master` (sem barra), a URL não é excluída e cai na regra genérica de slug, que tenta buscar um restaurante com slug "master" e exibe a página 404.

## Solução

Alterar as condições de exclusão no `.htaccess` para aceitar os diretórios com ou sem barra final, usando `/?` (barra opcional) no regex.

## Arquivo Modificado

| Arquivo | Mudança |
|---------|---------|
| `docs/php/.htaccess` | Trocar `!^/admin/` por `!^/admin` e `!^/master/` por `!^/master` nas condições de rewrite |

## Detalhes Técnicos

As linhas atuais:

```text
RewriteCond %{REQUEST_URI} !^/admin/
RewriteCond %{REQUEST_URI} !^/master/
```

Serão alteradas para:

```text
RewriteCond %{REQUEST_URI} !^/admin
RewriteCond %{REQUEST_URI} !^/master
```

Remover a barra final do padrão faz com que tanto `/master` quanto `/master/` e `/master/qualquer-coisa` sejam excluídos da regra de slug. O mesmo se aplica a `/admin`.

