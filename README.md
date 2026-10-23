# Gerador de relatórios — beta

**Símbolo → consultar dados → gerar com Claude → visualizar / salvar em PDF.**

PHP procedural, sem framework, classes ou funções PHP próprias. A interface não exibe a marca do fornecedor de dados. O relatório segue a organização do PDF de referência: capa com destaques e indicadores laterais, tese, negócio, setor, gestão, finanças, desempenho e riscos. A quantidade de páginas depende da informação disponível.

## Executar

```sh
cp config/config.example.php config/local.php
php -S 127.0.0.1:8085 -t public
```

Abra **http://localhost:8085**. Em `config/local.php`, configure:

- `api_headers`: autenticação confirmada da fonte de dados; depois marque `api_auth_confirmed` como `true`.
- `claude_api_key`: chave da Anthropic (ou variável `ANTHROPIC_API_KEY`).
- `claude_model`: identificador de um modelo Claude habilitado na conta (ou variável `ANTHROPIC_MODEL`).
- `claude_workspace`: somente se sua chave exigir identificação de workspace.

Não foi encontrado acesso configurado no projeto; nenhuma chamada real foi realizada. Requer PHP 8.2+ com cURL. Esta versão simplificada **não exige banco de dados**: salva relatórios e retornos em `var/reports/`, fora da pasta pública. A index lista todos os relatórios salvos, inclusive de sessões anteriores. Use localmente; não há login de usuários.

## Alterar a fonte posteriormente

`config/endpoints.json` contém os métodos e campos extraídos da aba **OUR API** do Excel. A consulta automática usa `overview`, `fundamentals`, `financial`, `earnings`, `executives`, `dividends` e `fullHistory`. A URL-base e os headers ficam em `config/local.php`; a montagem das chamadas fica em `app/data_collect.php`. Para uma API com outro contrato, ajuste esse adaptador. Não há scraping ou fontes externas de pesquisa.

## Geração e conteúdo

- Claude recebe somente os retornos utilizáveis e instruções editoriais em `app/prompt.json`.
- As 14 seções de conteúdo são geradas com referências; capa e informações finais completam a estrutura de 16 partes do PDF. O aplicativo exibe o conteúdo disponível, aceita referências vazias e omite campos inutilizáveis. Isso não comprova as afirmações: o resultado continua como beta gerada por IA.
- O modelo é instruído a não inventar dados nem calcular indicadores. Lacunas, inclusive balanço, caixa ou ownership sem cobertura, ficam indicadas. Não são copiados os fatos da Vuzix, logos, patrocínio ou valores do modelo.
- Fontes com erro, resposta vazia ou mais de 100 KB são excluídas do contexto; seus retornos ficam preservados e a cobertura parcial é indicada. Formatos, datas e unidades precisam de validação com a API real.
- A chamada Claude usa Messages API e uma ferramenta de saída estruturada. Não habilita busca na web nem ferramentas externas. Referência: [Messages e autenticação](https://platform.claude.com/docs/en/api/overview) e [definição de ferramentas](https://platform.claude.com/docs/en/agents-and-tools/tool-use/define-tools).
- Cada envio do símbolo cria um identificador no servidor e uma nova geração, inclusive após falha; pode consumir novos tokens. Abrir um relatório pela listagem reutiliza o arquivo salvo sem chamar APIs. A sessão é usada apenas para CSRF.
- Para PDF, use **Salvar em PDF / imprimir**. A paginação é feita pelo navegador.

## Verificação

```sh
php tests/run.php
php bin/check.php
```

Testes cobrem estrutura, fontes inexistentes, lacunas, remoção da marca do fornecedor e escape de HTML. Não substituem um teste real das duas APIs. O schema de saída está no próprio `app/claude_generate.php`. A exibição flexível é o padrão, sem uma etapa separada de aprovação ou prévia. A implementação anterior, com auditoria e MySQL, foi guardada em `legacy/` e não participa desta versão.

O título exibido é `AI Analysis SYMBOL report`, inclusive ao reabrir relatórios antigos. O prompt é um objeto em `app/prompt.json`, serializado para o campo `system`. Ao abrir um relatório, o console do navegador registra os retornos das APIs (JSON decodificado e resposta original), incluindo consultas que falharam, para conferência durante o desenvolvimento.
