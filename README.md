# AppMax

Este é um projeto PHP. Siga as instruções abaixo para configurar e executar o ambiente de desenvolvimento com Docker.

## 📋 Pré-requisitos

Certifique-se de ter os seguintes softwares instalados:

- Docker; e
- Docker Compose;

## 🚀 Configuração do Ambiente

1. **Suba os Containers Docker:** Para construir e inicializar o ambiente, execute o comando abaixo na raiz do projeto:

    ```bash
    docker compose up --build -d
    ```

2. **Acesse o Container PHP:** Para acessar o terminal do container PHP como usuário root, execute o comando:

    ```bash
    docker compose exec --user root php bash
    ```

3. **Execute as Migrations:** Depois de acessar o container, aplique as migrations para configurar o banco de dados:

    ```bash
    php artisan migrate
    ```

## ⚠️ Atenção: Comandos Variam com a Versão do Docker

Dependendo da versão do Docker instalada em sua máquina, o comando para utilizar o Docker Compose pode variar:

**Docker Compose v1:** Use o comando docker-compose (com hífen).

```bash
docker-compose up -d
```

**Docker Compose v2+:** Use o comando docker compose (sem hífen), integrado ao Docker CLI.

```bash
docker compose up -d
```

## 🛠️ Tecnologias e Metodologias Utilizadas

- **PHP:** Linguagem principal utilizada para desenvolver a lógica de negócio.
- **Laravel:** Framework PHP escolhido por sua robustez, segurança e suporte a recursos modernos.
- **MySQL:** Banco de dados relacional utilizado para armazenar e gerenciar informações de forma eficiente.
- **Docker:** Ferramenta de virtualização que simplifica a criação, execução e gerenciamento do ambiente de desenvolvimento e produção.
- **Docker Compose:** Utilizado para orquestrar os containers necessários, como o servidor web, banco de dados e outros serviços.
- **Swagger:** Ferramenta utilizada para documentar e testar as APIs do projeto, garantindo clareza na comunicação entre equipes e simplicidade na integração com terceiros.
- **RESTful API:** Segue os princípios REST para criar APIs eficientes e escaláveis.

Desenvolvido com ❤️ por Henrique Cândido.
