# Sistema Gestor – Gráfica Escolar

## Instituição
**ETEC Vasco Antônio Venchiarutti**

## Curso
**Informática para Internet**

## Turma
**2°D**

## Autores
- **Arthur Alexandre Dias Silva**
- **Helena Bianquini Carriço**

---

## 1. Apresentação

O **Sistema Gestor – Gráfica Escolar** é uma aplicação web desenvolvida em PHP e integrada a um banco de dados. Seu objetivo é auxiliar na organização e no gerenciamento das atividades de uma gráfica escolar, reunindo diferentes áreas administrativas em um único sistema.

A aplicação possui acesso por login e módulos destinados ao gerenciamento de estoque, funcionários, gastos, impressões, vendas e relatórios.

## 2. Objetivos

- Centralizar informações e rotinas administrativas da gráfica.
- Organizar o controle e a consulta dos itens do estoque.
- Reunir informações sobre funcionários, gastos, impressões e vendas.
- Disponibilizar relatórios para acompanhamento das informações.
- Controlar o acesso às áreas administrativas por meio de autenticação.
- Armazenar e consultar os dados por meio de um banco de dados.

## 3. Funcionamento e funcionalidades

### 3.1. Login e controle de acesso
O sistema possui uma tela de login para permitir o acesso à área administrativa. Os arquivos `login.php`, `logout.php` e `includes/auth.php` estão relacionados à autenticação, ao encerramento da sessão e ao controle de acesso.

### 3.2. Página inicial
Após realizar o acesso, o usuário pode entrar na área administrativa, que reúne a navegação para os módulos disponíveis.

### 3.3. Estoque
O módulo de estoque permite acessar e gerenciar informações dos itens utilizados ou armazenados pela gráfica escolar.

### 3.4. Funcionários
O módulo reúne a área destinada ao gerenciamento das informações relacionadas aos funcionários.

### 3.5. Gastos
O módulo disponibiliza uma área para registrar e consultar informações referentes às despesas da gráfica.

### 3.6. Impressões
O módulo concentra as funcionalidades relacionadas ao gerenciamento dos serviços de impressão.

### 3.7. Vendas
O módulo reúne as operações e informações referentes às vendas realizadas pela gráfica.

### 3.8. Relatórios
O sistema possui uma área de relatórios para reunir informações administrativas. Também foi desenvolvido um recurso de relatório com envio por e-mail, relacionado aos arquivos `admin/relatorio.php` e `includes/relatorio_funcoes.php`.

## 4. Tecnologias e recursos

- **PHP:** desenvolvimento da lógica e processamento das funcionalidades.
- **Banco de dados e SQL:** armazenamento e consulta das informações.
- **HTML:** estruturação das páginas.
- **CSS:** estilização e organização visual.
- **JavaScript:** comportamentos e interações no lado do cliente.

## 5. Organização dos arquivos

```text
grafica-escolar/
├── admin/
│   ├── estoque.php
│   ├── funcionarios.php
│   ├── gastos.php
│   ├── impressao.php
│   ├── inicio.php
│   ├── relatorio.php
│   └── vendas.php
├── assets/
│   ├── css/
│   │   └── estilo.css
│   └── js/
│       └── script.js
├── database/
│   └── grafica.sql
├── imagens/
├── includes/
│   ├── auth.php
│   ├── conexao.php
│   ├── config.php
│   ├── footer.php
│   ├── funcoes.php
│   ├── header.php
│   └── relatorio_funcoes.php
├── logo/
├── planejamento/
├── referencias/
├── index.php
├── login.php
├── logout.php
└── README.md
```

### Descrição da estrutura

| Diretório/arquivo | Responsabilidade |
|---|---|
| `admin/` | Reúne as páginas dos módulos administrativos. |
| `estoque.php` | Página do módulo de estoque. |
| `funcionarios.php` | Página do módulo de funcionários. |
| `gastos.php` | Página do módulo de gastos. |
| `impressao.php` | Página do módulo de impressões. |
| `inicio.php` | Página inicial da área administrativa. |
| `relatorio.php` | Página do módulo de relatórios. |
| `vendas.php` | Página do módulo de vendas. |
| `assets/css/` | Armazena os arquivos de estilização. |
| `assets/js/` | Armazena os scripts JavaScript. |
| `database/grafica.sql` | Arquivo SQL relacionado à estrutura do banco de dados. |
| `imagens/` e `logo/` | Armazenam imagens e elementos de identidade visual. |
| `includes/` | Reúne arquivos compartilhados, configurações, conexão, autenticação e funções auxiliares. |
| `conexao.php` | Arquivo responsável pela conexão com o banco de dados. |
| `config.php` | Reúne configurações utilizadas pelo sistema. |
| `funcoes.php` | Reúne funções auxiliares da aplicação. |
| `header.php` e `footer.php` | Componentes compartilhados da estrutura das páginas. |
| `relatorio_funcoes.php` | Reúne funções relacionadas aos relatórios. |
| `planejamento/` | Diretório destinado aos materiais de planejamento. |
| `referencias/` | Diretório destinado aos materiais de referência. |
| `index.php` | Arquivo de entrada do projeto. |
| `login.php` | Página de acesso ao sistema. |
| `logout.php` | Arquivo responsável pelo encerramento da sessão. |

## 6. Banco de dados

O projeto possui o diretório `database/`, que contém o arquivo `grafica.sql`, relacionado à estrutura do banco de dados. O arquivo `includes/conexao.php` concentra a conexão utilizada pela aplicação, enquanto `includes/config.php` reúne configurações gerais.

Para executar o projeto em outro ambiente, é necessário configurar os dados de conexão de acordo com o servidor de banco de dados utilizado e importar o arquivo SQL no ambiente correspondente.

## 7. Considerações finais

O desenvolvimento do Sistema Gestor – Gráfica Escolar permite aplicar conhecimentos de desenvolvimento web, programação em PHP, integração com banco de dados, autenticação de usuários, organização de arquivos e construção de interfaces.

A separação dos módulos e dos arquivos compartilhados busca facilitar a manutenção e a evolução do sistema. O projeto também proporciona a aplicação prática de conceitos de programação e gerenciamento de informações em um contexto escolar.

---

## DECLARAÇÃO DE USO DE INTELIGÊNCIA ARTIFICIAL

Durante o desenvolvimento do Sistema Gestor – Gráfica Escolar, foram utilizadas ferramentas de Inteligência Artificial como apoio à programação, à organização visual e à implementação de funcionalidades específicas.

| Ferramenta | Etapa | Finalidade | Validação |
|---|---|---|---|
| Claude | Desenvolvimento | Auxílio na compreensão e elaboração dos códigos relacionados aos processos de login e logout. | Os códigos foram analisados no contexto do projeto; os testes de autenticação e encerramento de sessão devem confirmar seu funcionamento. |
| ChatGPT | Desenvolvimento visual | Auxílio na escrita e no ajuste de trechos de CSS e JavaScript utilizados na interface e nas interações do sistema. | Os trechos foram avaliados no contexto do projeto; a apresentação e as interações devem ser conferidas no navegador. |
| Claude | Desenvolvimento | Auxílio na implementação do sistema de relatórios com envio por e-mail. | A funcionalidade deve ser verificada por testes de geração do relatório e de envio e recebimento do e-mail. |
| Gemini | Desenvolvimento | Auxílio na compreensão, elaboração e correção de códigos gerais em PHP utilizados no sistema. | As sugestões foram analisadas no contexto do projeto; as funcionalidades correspondentes devem ser verificadas por testes. |

As ferramentas de Inteligência Artificial foram utilizadas como recursos de apoio, sem substituir a participação dos autores nas decisões e na construção do sistema. Os autores permanecem responsáveis pelo código, pelas informações armazenadas, pelo funcionamento das funcionalidades e pela verificação dos resultados.

**Atenção:** a coluna de validação deve refletir os testes que realmente foram realizados. Antes da entrega, ajuste cada descrição para registrar somente verificações que você efetivamente executou.

---

*Projeto desenvolvido para fins educacionais no curso de Informática para Internet.*
