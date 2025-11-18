<p align="center">
<svg xmlns="http://www.w3.org/2000/svg" width="180" height="70" viewBox="0 0 180 70">
  <!-- User circle -->
  <circle cx="30" cy="35" r="18" fill="#0ea5a4"/>

  <!-- Lines to gateways -->
  <line x1="48" y1="35" x2="85" y2="20" stroke="#475569" stroke-width="3"/>
  <line x1="48" y1="35" x2="85" y2="50" stroke="#475569" stroke-width="3"/>

  <!-- Gateway boxes -->
  <rect x="85" y="10" width="70" height="20" rx="4" fill="#334155"/>
  <rect x="85" y="40" width="70" height="20" rx="4" fill="#334155"/>

  <!-- Final flow indicator -->
  <line x1="155" y1="35" x2="170" y2="35" stroke="#0f172a" stroke-width="3"/>
  <polygon points="170,35 164,31 164,39" fill="#0f172a"/>
</svg>

</p>


## Overview

Pag super é um módulo de integração com subadquirentes de pagamento.

Tecnologias usadas:

* PHP
* Laravel
* MySQL
* Nginx
* Docker + Docker Compose

## Setup

> [!IMPORTANT]  
> É necessário possuir Docker e Docker compose na sua máquina

* Clone o repositório:
```sh
git clone https://github.com/benjamimWalker/pag-super.git
```

* Navegue até o diretório do projeto:
```sh
cd pag-super
```

* Prepare a env:
```sh
cp .env.example .env
```

* Suba os containers:
```sh
docker compose up -d
```

* Instale as dependências do composer:
```sh
docker compose exec app composer install
```

* Execute as migrations:
```sh
docker compose exec app php artisan migrate
```

* Execute o seeder:
```sh
docker compose exec app php artisan db:seed
```

* Execute a fila
```sh
docker compose exec app php artisan queue:listen
```

* Acesse a documentação em:
```sh
http://localhost/docs/api
```

## Como usar

### 1 - Faça um pix

Na página da documentação, em pix.store, altere o body trocando o parâmetro user_id para 1 (criado pelo seeder); também altere amount assim como outros parâmetros como desejar. O botão "Send API Request" faz uma requisição real

![Content creation image](https://raw.githubusercontent.com/benjamimWalker/pag-super/master/assets/pix.png)

### 2 - Faça um saque

Na página da documentação, em withdraw.store, altere o body trocando o parâmetro user_id para 1 (criado pelo seeder); também altere amount assim como outros parâmetros como desejar. O botão "Send API Request" faz uma requisição real

![Content creation image](https://raw.githubusercontent.com/benjamimWalker/pag-super/master/assets/withdraw.png)

## Estrutura e decisões técnicas adotadas

O projeto foi organizado para permitir integrar várias subadquirentes (SubadqA, SubadqB) de forma padronizada.
Cada subadquirente tem seu próprio adaptador, mas todos seguem a mesma interface. Assim, a aplicação escolhe automaticamente o provedor certo para cada usuário.

As operações de PIX e saque seguem o mesmo fluxo:

A API cria o registro local da transação.

O adaptador envia a requisição para a subadquirente (mock).

A aplicação recebe o retorno e salva o external_id.

Jobs são usados para simular webhooks do provedor e atualizar o status no banco.

As principais decisões do projeto foram:

Padronizar integrações usando interface + adaptadors.

Separar responsabilidades: controllers cuidam da entrada, adaptadores falam com o provedor, jobs tratam webhooks.

Simular callbacks para reproduzir o comportamento real de uma subadquirente.

Armazenar payloads para facilitar rastreabilidade.

Essa estrutura deixa fácil adicionar novos provedores, testar fluxos reais de callback e manter o código organizado.

[Benjamim] - [benjamim.sousamelo@gmail.com]<br>
Github: <a href="https://github.com/benjamimWalker">@benjamimWalker</a>
