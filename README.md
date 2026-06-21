# -Sistema-em-PHP-com-SQLite

# Sistema de Autenticação & Bilheteria de Shows em PHP

> **Projeto Didático** — Aula de Desenvolvimento Web com PHP (Professora Maristela)  
> **Instituição:** Faculdade de Tecnologia e Inovação Senac DF  
> **Desenvolvedor(a):** Lorrane (ADS)

---

## Sobre o Projeto

Seja bem-vindo(a) ao repositório do **Sistema de Autenticação e Gestão de Eventos**!
Este é um projeto prático estruturado para consolidar os conceitos de backend em PHP utilizando persistência leve com o banco de dados relacional **SQLite3**.

A aplicação foi projetada para ir além dos sistemas acadêmicos tradicionais, contando com uma **identidade visual temática premium inspirada na natureza (folhas e pétalas flutuantes animadas via HTML5 Canvas)** e um **Módulo Completo de Emissão de Ingressos (Vouchers)** para shows musicais.

> *Se algo der erro, respire fundo, tome água e lembre-se: até o ponto e vírgula tem seus dias de protagonismo no ecossistema do software!*

---

## Identidade Visual & UX

A interface gráfica do sistema foi desenvolvida com foco na harmonia visual e na experiência do usuário:

* **Fundo Dinâmico:** Um motor de partículas nativo programado em JavaScript (`Canvas`) renderiza folhas, flores e pétalas flutuantes com opacidades, rotações e velocidades orgânicas.
* **Paleta de Cores Premium:** Tons de verde escuro floresta (`#0d3318`), amarelo ouro para realces e detalhes, e uma base creme aconchegante (`#fdf6e3`) para a leitura dos cartões de dados.
* **Tipografia Elegante:** Combinação das fontes *Playfair Display* (para títulos clássicos marcantes) e *DM Sans* (legibilidade impecável em inputs, botões e tabelas).
* **Vouchers Estilizados:** Os ingressos emitidos possuem um design que simula bilhetes destacados/picotados tradicionais de shows.

---

## Funcionalidades Atuais

1. **Gestão de Usuários:**
   * Cadastro completo com validações em tempo de execução.
   * Autenticação segura com proteção ativa contra ataques de fixação de sessão.
2. **Módulo de Eventos (Shows):**
   * Cadastro administrativo de novas atrações musicais (Artista, Data, Local e Preço do ingresso).
   * Listagem responsiva e dinâmica dos próximos shows inseridos no banco.
   * **Resgate de Vouchers:** O usuário logado pode emitir e garantir o seu ingresso com apenas um clique.
   * **Painel de Bilhetes:** Exibição imediata dos ingressos vinculados exclusivamente ao perfil conectado.

---

## Estrutura de Diretórios do Projeto

Todos os arquivos principais estão localizados de forma direta e acessível na raiz do projeto, facilitando a execução imediata:

```text
 Cadastro-php-main/
├── 📄 db.php           # Fábrica de conexões PDO (Cria o banco e tabelas automaticamente)
├── 📄 db_mysql.php     # Script opcional configurado para futura portabilidade para MySQL
├── 📄 database.db      # Arquivo físico do banco SQLite (gerado automaticamente)
├── 📄 index.php        # Ponto de entrada (Controlador inicial de rotas/redirecionamento)
├── 📄 cadastro.php     # Interface e processamento do registro de novos usuários
├── 📄 login.php        # Validação de credenciais e abertura da sessão segura
├── 📄 dashboard.php    # Painel interno principal do usuário (Home logada)
├── 📄 shows.php        # Módulo de cadastro de shows e retirada de ingressos
├── 📄 logout.php       # Encerramento e destruição controlada da sessão
├── 📄 dump_users.php   # Script auxiliar para depuração rápida de usuários em JSON
├── 📄 test_db.php      # Script de teste rápido de conectividade do banco
└── 📄 README.md        # Documentação completa do ecossistema (Este arquivo)
