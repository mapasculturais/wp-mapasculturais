# Requisitos
Para utilizar o plugin é necessário ter uma conta em alguma instalação do Mapas Culturais que esteja rodando com a versão 4.6.0 ou superior. O número da versão pode ser obtido pelo endereço da **/api/site/version** da instalação do Mapas Culturais.


# Instalação e ativação
1. No painel de seu site, navegue até **Plugins** –> **Adicionar Novo**.
2. Aperte o botão **Enviar plugin** e em seguida o botão para escolher o arquivo para enviar.
3. Quando o popup aparecer selecione o arquivo wp-mapasculturais-x.x.x.zip para fazer upload, onde x.x.x é a versão do plugin.
4. Aperte o botão **Instalar agora**.
5. Quando o processo de instalação tiver sido concluído, aperte o botão **Ativar plugin**.
6. Navegue até **Configurações** -> **Links Permanentes** e clique no botão **Salvar Alterações**.


# Configuração
O plugin se utiliza da plataforma Mapas Culturais para ler e escrever as informações de agentes, espaços e eventos. Para tanto é necessário informar o endereço da plataforma e um par de chaves que devem ser obtidas no painel do usuário da plataforma. 

## Obtenção das chaves
1. Faça login na plataforma Mapas Culturais.
2. No painel do usuário, clique no menu **Meus Apps**.
3. Clique no botão **Adicionar novo app**.
4. Dê um nome como `Meu site`.
5. Após a criação clique no link com o nome do app criado.
6. Clique no pondo de exclamação ao lado do campo `Chave Privada` para que o conteúdo seja exibido.

## Configuração do endereço e das chaves
1. No painel de seu site navegue até **Mapas Culturais** -> **Configurações**.
2. Informe o endereço da plataforma no campo **URL da instalação do Mapas Culturais**.
3. Copie as chaves pública e privada da plataforma e cole nos campos **Chave Pública** e **Chave Privada**.
4. Clique em **Salvar alterações**

## Configuração de sincronização e filtro das entidades
Nos menus **Mapas Culturais** -> **Agentes**, **Mapas Culturais** -> **Espaços** e **Mapas Culturais** -> **Eventos** estão disponíveis as configurações de sincronização e de filtros das entidades, ou seja, quais informações serão trazidas da plataforma Mapas Culturais para o seu site.

A sincronização no sentido _WordPress_ -> _Mapas Culturais_ ocorrerá sempre que um agente, espaço ou evento for salvo. Já no sentido _Mapas Culturais_ -> _WordPress_ ocorrerá no intervalo mínimo de 1 minuto para agentes, espaços e eventos que estejam com a configuração _Importar automaticamente_ habilitadas.

# Utilização
## Shortcodes
Estão disponíveis os seguintes shortcodes para utilização nos conteúdos dos posts e páginas:
- `events-agenda` - exibe uma agenda de eventos.
- `events-calendar` - exibe um calendário com os eventos.
- `events-day` - exibe os eventos do dia.
- `events-list` - exibe uma lista com os eventos do mês.
- `events-now` - exibe uma lista com os eventos acontecendo agora.

### Opções dos shortcodes
Todos os shortcodes aceitam as seguintes opções:
- `filters` - Indica se os filtros devem ser exibidos. O padrão é `true`. Se informado `false`, `no` ou 0 os filtros não serão exibidos.
- `spaces` - Exibir somente os eventos que ocorrem nos espaços com os ids informados. 
- `agents` - Exibir somente os eventos publicados pelos agentes com os ids informados.

**exemplos de shortcodes**
- `[events-calendar spaces=1,2]` - Exibe um calendário com os eventos que ocorrem nos espaços de ids 1 e 2.
- `[events-now spaces=1 filters=no]` - Exibe uma lista dos exendos que estão acontecendo agora no espaço de id 1 sem exibir os filtros.
- `[events-day spaces=2 agents=1,2 filters=no]` - Exibe os eventos que ocorrem hoje no espaço de id 2 e que tenhan sido publicados pelos agentes de id 1 e 2. Não exibe os filtros.
