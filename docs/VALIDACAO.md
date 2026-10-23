# Validação da versão simplificada

- Inspecionado o PDF de 19 páginas fornecido, incluindo imagens da capa e das tabelas financeiras.
- Mantido o catálogo extraído da aba OUR API da planilha.
- PHP procedural: lint sem erros.
- 11 casos automatizados de validação de documento, mais renderização com escape de HTML, preservação de zero e ausência de marca do fornecedor.
- Não há dados de demonstração na interface. Fixtures são usadas exclusivamente nos testes.
- Geração Claude implementada, mas ainda não executada contra a API real: faltam chave/modelo e autenticação da fonte.
- O banco de dados não é requisito desta versão. Código anterior preservado em legacy/.

- Página inicial conferida no Chrome headless (1440 × 1000), sem identidade do fornecedor.
- Fluxo HTTP: formulário válido sem credenciais retorna 503 com orientação; envio sem CSRF retorna 403.
