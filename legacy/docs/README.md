# ReportStock — beta procedural

Aplicação local em **PHP estruturado/procedural**, MySQL/MariaDB e HTML/CSS. Sem framework, classes, funções PHP próprias, Composer ou `try/catch`. Usa somente os métodos Stocks.News documentados na aba **OUR API** da planilha fornecida. Não faz scraping, consultas diretas à SEC ou pesquisa externa.

## Abrir

Na pasta do projeto:

```sh
php -S 127.0.0.1:8085 -t public
```

Acesse **http://127.0.0.1:8085**. A interface funciona sem configuração, mostrando lacunas reais e os métodos disponíveis. Coleta e persistência exigem configurar o banco e a API. Não há dados financeiros de demonstração na interface.

Requisitos: PHP 8.2+ com `mysqli`, `curl`, `json` e `session`; MySQL 8+ ou MariaDB 10.6+. O ambiente encontrado usa PHP 8.4.8. A compatibilidade do SQL com banco real ainda precisa de validação de integração.

## Configurar banco e API

1. Crie **um banco vazio dedicado** chamado `reportStock_beta`, com charset `utf8mb4`. Use um usuário autorizado do seu MySQL.
2. Importe o schema:

   ```sh
   mysql -u SEU_USUARIO -p reportStock_beta < database/schema.sql
   ```

3. Copie a configuração:

   ```sh
   cp config/config.example.php config/local.php
   ```

4. Preencha `db_host`, `db_port`, `db_name`, `db_user` e `db_password` no arquivo local.
5. Confirme o contrato real de autenticação Stocks.News; preencha `api_headers` com os headers exigidos e só então marque `api_auth_confirmed` como `true`. Não foi presumido Bearer, API key, login ou endpoint de autenticação. Se o contrato exigir algo diferente de headers, o transporte deverá ser adaptado antes da integração.
6. Informe editor, revisor e patrocínio quando fornecidos pela empresa.

**Não aplique o schema em um banco existente.** Ele é uma instalação nova, deliberadamente sem `IF NOT EXISTS`, para não mascarar incompatibilidades. O arquivo `atualizar_reportStock_longId.sql` não foi disponibilizado. Nenhuma migração do banco `reportStock` foi executada.

A configuração fica fora do diretório público e é ignorada pelo Git. Sirva exclusivamente `public/`. Esta beta não contém autenticação de usuários: execute em localhost ou ambiente restrito, sem exposição pública.

## Fluxo implementado

1. Criar relatório por símbolo, data do preço e cutoff em UTC.
2. Coletar um método documentado, começando por `overview`. Métodos adicionais são escolhidos explicitamente, evitando chamadas automáticas redundantes.
3. Salvar corpo original, hash SHA-256, parâmetros sem headers de autenticação, HTTP, horários e status da tentativa.
4. Localizar chaves exatas da planilha, preservar seus valores e registrar evidência usando JSON Pointer. Nenhum alias é aplicado: `marketcap` e `marketCap`, por exemplo, continuam distintos.
5. Revisar cada campo e confirmar entidade, contexto, período, data, disponibilidade pública, unidade, escala e moeda. A aprovação é humana e deve se apoiar na resposta original; metadados não são preenchidos por suposição.
6. Exibir somente fatos aprovados nas seções relacionadas. As outras seções mostram lacunas. Valores compostos aparecem como JSON, sem achatamento que possa misturar períodos ou entidades.
7. Imprimir ou salvar como PDF pelo navegador, com folha de estilos própria.

A data de disponibilidade pública é essencial: o momento da coleta não prova que o dado já era público no cutoff. Aprovar preços exige referência na data escolhida. Para datas passadas, consulte `fullHistory`; não há substituição automática por preço atual, nem ajuste automático para dias sem pregão.

Retornos bem-sucedidos são reutilizados pelo conjunto relatório + método + parâmetros. Um lock MySQL impede coleta simultânea da mesma combinação. Respostas salvas antes de uma interrupção são normalizadas na retomada. Falhas e tentativas interrompidas permanecem no histórico. Um novo relatório representa uma nova coleta; não há botão de sobrescrever a resposta existente.

## Cobertura e limites desta entrega

- As **16 seções editoriais** estão presentes; dados disponíveis formam tabelas parciais, não um relatório textual completo.
- `config/endpoints.json` contém os **14 métodos** com campos e linhas de origem da aba OUR API. Doze métodos individuais têm coleta; `summary` e `AllSymbolsByExchange` ficam catalogados para futura seleção de peers e descoberta de instrumentos.
- Todos os requests usam os campos documentados. `charts` usa o exemplo apenas com `fields`, sem presumir unidade de intervalo ou seleção histórica.
- Retornos e formatos reais, paginação, cobertura temporal, ajustes de preços e unidades ainda não foram confirmados. Um HTTP 200 não aprova fatos nem comprova cobertura completa.
- O normalizador é conservador: procura nomes exatos em envelopes desconhecidos e deixa tudo pendente. Chaves não reconhecidas permanecem no retorno original. Erros em JSON com HTTP 200 também exigem revisão; não são tratados como fatos aprovados.
- Balanço, fluxo de caixa, ownership, notícias, patentes e conteúdo descritivo não têm cobertura comprovada na planilha. Isso não significa que sejam indisponíveis no fornecedor.
- Cálculos financeiros derivados, seleção automática de períodos, preenchimento das sete tabelas de apresentação legadas, catálogo completo de requisitos e migração dos 19 objetos propostos ficam para integração com o schema e contratos reais. Esta beta usa dez tabelas, incluindo controle de versão, e renderiza diretamente fatos aprovados.
- Geração/extração Claude, prompts, tese e destaques automáticos estão **desativados**, respeitando a revisão pendente descrita no PDF. Não há chamadas à Anthropic nem prompts apresentados como aprovados.
- Aprovação de fatos não é aprovação editorial. Publicação e versionamento de versões publicadas não foram habilitados; todo relatório continua como rascunho.
- Editor, revisor e patrocínio só aparecem quando configurados. O PDF editorial da Vuzix não foi fornecido; não houve reprodução de seu layout ou conteúdo.

## Estrutura

```text
public/                 Entrada web, CSS e JavaScript
app/                    Includes procedurais: coleta, validação e persistência
config/endpoints.json   Catálogo extraído da aba OUR API, sem valores de exemplo
config/config.example.php
database/schema.sql    Instalação em banco vazio (sem FKs)
bin/check.php           Diagnóstico local sem exibir segredos
tests/run.php           Testes locais de extração e validação
docs/VALIDACAO.md        Resultado das verificações e bloqueios
```

Cada tabela usa `id` numérico, `longId` textual e índices. As relações usam o `longId` da tabela de destino; não há foreign keys, referências numéricas ou cascatas. Os IDs de dados são gerados pela aplicação com `random_bytes`.

## Verificar

```sh
php bin/check.php
php tests/run.php
find app public config bin tests -name '*.php' -exec php -l {} \;
```

Os testes usam fixtures sintéticas exclusivamente em memória, separadas da aplicação. Não chamam a API nem escrevem no banco. Testes de integração reais devem criar um relatório, coletar `overview`, revisar evidências, aprovar/rejeitar fatos e repetir a coleta para confirmar a reutilização. Confira também falha HTTP e retomada após interrupção antes de usar a beta com pesquisa real.
