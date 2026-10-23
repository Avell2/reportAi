# Verificação da beta — 22/10/2026

## Executado

- Leitura do PDF fornecido com pypdf e da aba OUR API com openpyxl.
- Extração de 14 métodos, campos exatos e linhas de origem para `config/endpoints.json`.
- Ambiente: PHP 8.4.8; extensões cURL, MySQLi, JSON e session disponíveis.
- Lint PHP nos arquivos da aplicação e testes: sem erros de sintaxe.
- 13 verificações automatizadas: zero com evidência, null, aliases/capitalização, escape JSON Pointer, períodos separados, aprovação válida, cutoff, preço histórico, data inválida, entidade, unidade, escala, rejeição e confirmação de revisão (algumas verificações agrupam condições).
- POST sem token de sessão: bloqueado com HTTP 403.
- Inspeção de tokens PHP: sem funções próprias, classes ou try/catch.
- Servidor HTTP local: página inicial respondeu 200; HTML contém as 16 seções, catálogo e aviso de configuração. CSP, cookies HttpOnly/SameSite e `Cache-Control: no-store` presentes.

## Não validado em integração

- MySQL local: conexão sem senha recusada com erro 1045. Não há credenciais existentes no projeto. Nenhum schema de produção foi inspecionado ou alterado.
- Tentativa de iniciar banco isolado de teste: o binário MySQL 26.10.0 falhou durante inicialização com SIGSEGV. O schema não foi executado; persistência, transações e locks precisam de teste com banco configurado.
- Stocks.News: nenhuma chamada real, pois não foram fornecidos contrato de autenticação ou configuração existente. Não há dados atuais nem cobertura de empresa confirmada.
- Inspeção visual por navegador automatizado não executada; verificação da interface limitada ao HTML/HTTP e CSS responsivo.
- Claude e publicação: desabilitadas por escopo, sem execução.

O arquivo de migração SQL citado no PDF e o PDF editorial da Vuzix não estavam entre os arquivos fornecidos. Não foram recriados por suposição.
