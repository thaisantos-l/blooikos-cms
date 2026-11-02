# Conteúdo público em runtime

`GET /wp-json/blooikos/v1/site-content` fornece somente a operação anônima
versionada existente, em `{schemaVersion: 1, version, data}`. HEAD e OPTIONS não
alteram conteúdo. Parâmetros de consulta (exceto o roteamento `rest_route`), corpo,
POST e consultas arbitrárias são rejeitados. Mesmo um administrador recebe a
projeção anônima. Nenhuma mensagem do formulário integra esta API.

O estado persistido é conferido antes/depois da leitura, inclusive no cache e no
304. Uma escrita ou revisão pendente retorna 409 `CONTENT_NOT_READY` e
`Retry-After: 1` em runtime (`5` no modo legado); dependência/armazenamento/projeção indisponível retorna 503
`CONTENT_UNAVAILABLE`. Falhas não retornam payload antigo. O cache técnico usa
uma única transient, até 2 MiB e 300 segundos, sem payload na fila. Não executa
worker nem GitHub no GET. Em runtime, o fim do salvamento consolida imediatamente
a revisão, depois de fechar o escritor. Uma leitura pode recuperar uma revisão
pendente com uma única consolidação local sob lease, sem esperar outro escritor
ou disparar publicação. Escritas concorrentes são descartadas pelo controle de
sequência; o próximo acesso tenta novamente. Nenhum conteúdo editorial é escrito
pela API. OPTIONS não consolida.

Consultas com ETag vigente retornam 304 após conferir apenas o estado, sem ler
o payload, executar GraphQL ou calcular hashes de mídia. A consulta do frontend
ocorre a cada 5 segundos de aba visível. O objetivo de 5–10 segundos pressupõe
CMS/rede disponíveis e salvamento concluído; falhas mantêm a última versão
validada e usam retentativas limitadas, sem prometer esse prazo durante falhas.

Respostas válidas usam ETag, `public, max-age=0, must-revalidate` e noindex;
erros usam no-store. CORS permite por padrão apenas `https://blooikos.com.br`.
`BLOOIKOS_CONTENT_ALLOWED_ORIGINS` aceita um array de origens exatas; HTTP
loopback requer também `WP_ENVIRONMENT_TYPE=local`. Consumidores sem Origin
podem ler dados públicos. Não há credenciais CORS. A política de Contato é
independente e permanece inalterada.

## Corte operacional

1. Instalar este endpoint compatível antes do novo frontend.
2. Conferir `wp blooikos rebuild status --format=json` e resolver qualquer
   pedido legado em voo antes da mudança. Não apagar pedido/histórico.
3. Suspender dispatch antigo (`BLOOIKOS_REBUILD_ENABLED=false`) antes de integrar
   o workflow de deploy incompatível. Manter o cron de um minuto.
4. Validar um novo build e publicar explicitamente seu ID de artefato.
5. Configurar `BLOOIKOS_CONTENT_DELIVERY=runtime`, mantendo dispatch antigo
   desabilitado. O modo runtime tem precedência mesmo se enabled/token antigos
   estiverem presentes. Nenhum acesso GitHub é permitido pelo worker nesse modo.
6. Provar duas alterações autorizadas via API/DOM sem novos runs ou arquivos.

O cron continua como recuperação, sem token. O debounce de 60 segundos/janela
máxima de 300 permanece apenas no caminho legado/agendado; a entrega runtime
não depende dele. A consolidação rápida não altera o heartbeat do cron.
Status informa **Conteúdo pronto na API**, revisão e heartbeat;
release anterior é histórico do código/fallback, não confirmação de abas.
Retry/reconcile/adopt/pause/resume legados são rejeitados no modo runtime. Um
pedido legado inesperado é preservado e sinalizado para resolução operacional.

A primeira instalação e a configuração remota exigem autorização. Não usar
produção para testes. A reversão usa um artefato anterior validado e autorização
explícita, preservando conteúdo, API e histórico. Não reativar dispatch editorial
silenciosamente. Se CDN ignorar revalidação, usar bypass restrito à rota (ou
no-store documentado), sem cache-busting aleatório.
