# Publicação editorial automática

Salvar/publicar no CMS registra a alteração. O worker consolida a projeção pública
e solicita o workflow existente do frontend em `main`. O GitHub Actions captura,
compila e transfere o export estático. O editor acompanha o resultado em
**Ferramentas → Publicação do frontend**. Uma aba aberta precisa ser recarregada.

## Configuração e ativação

Conferir primeiro `wp blooikos rebuild status --format=json`. É uma consulta local,
sem HTTP. Os campos `configuration` mostram somente booleanos. O heartbeat deve
avançar mesmo sem visitas. `tick`, `retry`, `reconcile` e `resume` alteram estado;
não são consultas de diagnóstico.

Configuração privada esperada no ambiente autorizado:

- `BLOOIKOS_REBUILD_ENABLED`: booleano `true`, somente na ativação.
- `BLOOIKOS_REBUILD_ENVIRONMENT`: `production`.
- `home_url()`: exatamente `https://cms.blooikos.com.br`.
- `BLOOIKOS_REBUILD_GITHUB_TOKEN`: credencial com Actions read/write somente no
  repositório frontend. Reusar a credencial adequada e documentar sua expiração
  fora do Git. Nunca colocar o valor em logs, navegador, payload editorial ou README.

O token do CMS é independente do `GITHUB_TOKEN` do Actions. Origem local não
dispara produção. O environment `production` precisa permitir a branch `main`
sem aprovação humana em cada edição, mantendo secrets protegidos de forks/PRs.

Agendar no servidor, com binário e diretório previamente conferidos, uma execução
por minuto de `wp --path=<CMS> blooikos rebuild tick`. Não instalar tarefa duplicada
nem desativar WP-Cron global. O lease/fencing existente serializa o cron nativo e
o agendamento externo. A fila espera 60 segundos sem alterações, com janela
máxima de consolidação de 300 segundos. Não há HTTP durante o salvamento.

## Contrato de confirmação

O marcador continua com schema 2 e os mesmos campos. O ZIP continua determinístico.
O workflow transfere com `rsync --checksum --no-times`, mantendo SSH, exclusões e
diretórios, inclusive o legado `blooikos-static/`. O timestamp fixo permanece no
ZIP; arquivos alterados recebem mtime atual no destino. Isso permite que o
servidor regenere variantes gzip antigas quando os bytes da publicação mudam.
`scripts/deploy/confirm.py` verifica hashes/proveniência do pacote antes de extrair,
e depois compara os bytes HTTP de `/` e `/blooikos-release.json` com esse artefato.
Não segue redirecionamentos. Faz no máximo três rodadas de duas requisições,
com timeout de dez segundos por requisição e duas pausas de dois segundos.

A etapa do job `publish` tem o nome legível por máquina:

```text
Confirm artifact over HTTP index=<SHA-256 de index.html> marker=<SHA-256 do marcador>
```

Os hashes são outputs da verificação do artefato. Não renomear essa etapa sem
atualizar o consumidor CMS. O CMS verifica repo, main, workflow, evento, SHA, run,
attempt, pedido e sucesso do job/etapa. Em outro tick, lê novamente HTML e marcador
e compara seus hashes. Nenhum token GitHub é enviado ao site público.
O estado confirmado registra `verification: artifact-http-v1`; confirmações
anteriores precisam ser verificadas novamente. Um workflow antigo sem essa etapa
não produz um aceite novo (`ARTIFACT_CONFIRMATION_MISSING`).

O HTML precisa de `no-store`, ou `no-cache` com `must-revalidate`. O marcador exige
`no-store` e `X-Robots-Tag: noindex`. Na hospedagem com timestamps determinísticos,
preferir `no-store` e eliminar validadores condicionais nessas duas rotas:
mtime/tamanho iguais não podem produzir um 304 incorreto. Conferir também o CDN.

Exemplo de **bloco a integrar**, após revisão da configuração existente, no
document root do frontend (requer `mod_setenvif` e `mod_headers`):

```apache
# BEGIN Blooikos publication freshness
SetEnvIf Request_URI "^/(index\.html|blooikos-release\.json)?$" BLOOIKOS_PUBLICATION=1
SetEnvIf Request_URI "^/blooikos-release\.json$" BLOOIKOS_MARKER=1
RequestHeader unset If-Modified-Since env=BLOOIKOS_PUBLICATION
RequestHeader unset If-None-Match env=BLOOIKOS_PUBLICATION
Header unset Cache-Control env=BLOOIKOS_PUBLICATION
Header always unset Cache-Control env=BLOOIKOS_PUBLICATION
Header always set Cache-Control "no-store, max-age=0" env=BLOOIKOS_PUBLICATION
Header unset ETag env=BLOOIKOS_PUBLICATION
Header always unset ETag env=BLOOIKOS_PUBLICATION
Header unset Last-Modified env=BLOOIKOS_PUBLICATION
Header always unset Last-Modified env=BLOOIKOS_PUBLICATION
Header unset X-Robots-Tag env=BLOOIKOS_MARKER
Header always set X-Robots-Tag "noindex" env=BLOOIKOS_MARKER
# END Blooikos publication freshness
```

Não substituir o `.htaccess` inteiro, mudar roteamento ou aplicar esse bloco ao CMS.
O rsync preserva o `.htaccess`. Headers/cron/token remotos exigem ativação autorizada.
Revalidar 200 fresco, inclusive com `If-Modified-Since`/`If-None-Match` antigos.

Mídias normalizadas no export recebem `blooikos_media=<epoch>.<mediaRevision>`,
inclusive em srcSet. A revisão vem da captura validada; não há valor aleatório nem
alteração dos dados brutos/digests. Mudanças de bytes detectadas pelo WordPress
produzem outra URL consumida pelo navegador. Substituição direta por FTP não é
coberta. Dev continua lendo o CMS local e não publica o site público.

## Falhas e recuperação

- `PUBLICATION_CONFIRMATION_EXHAUSTED`: dez tentativas ou dez minutos desde que o
  run foi observado concluído. Corrigir origem/cache/headers e usar recuperação
  explícita para verificar novamente o mesmo run, sem novo dispatch automático.
- `PUBLICATION_CONFIRMATION_FAILED`: o job falhou na prova HTTP. Não repetir builds
  para mascarar uma configuração incorreta. Corrigir a causa e republicar/verificar
  pelo fluxo autorizado. rsync não oferece atomicidade integral do site.
- `ARTIFACT_CONFIRMATION_MISSING`: publicar o workflow atual e uma captura nova;
  não adotar uma confirmação antiga como prova equivalente.
- `DISPATCH_LOOKUP_EXHAUSTED`: entrega incerta. A pesquisa pelo requestId termina
  após quinze minutos sem reenviar às cegas. Recuperar a pesquisa do mesmo pedido;
  verificar a execução existente antes de decidir qualquer novo disparo.
- `CONFIGURATION_BLOCKED`, `RATE_LIMITED` e pausa preservam seus diagnósticos e
  limites remotos. HTTP 429 pode ser repetido após o prazo; resposta aceita sem
  corpo ou perda de conexão entra em pesquisa, não em novo dispatch.

Uma edição durante o build permanece pendente. Somente epoch/versão/digest iguais,
projeção pronta, confirmação atual e ausência de alterações posteriores permitem
“Publicado”. Manter a fila em qualquer reversão. Para desativar a automação, pausar
dispatch ou desabilitar apenas a configuração própria; preservar o conteúdo salvo,
cron de terceiros e o último site saudável.

A prova operacional exige duas edições autorizadas chegando ao site sem comandos
intermediários e ausência de builds sem alterações. Medir a latência real. O alvo
inicial de dez minutos inclui debounce, cron, fila Actions, build e confirmação;
não é garantia nem atualização instantânea.
