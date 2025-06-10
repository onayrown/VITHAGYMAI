# Documento de Planejamento por Milestones

## Projeto: SMARTBIOFIT - Aplicativo Web de Avaliação Física Profissional

**Tecnologias**: PHP, MySQL, JavaScript 
**Foco**: Aplicativo responsivo com ênfase em uso mobile
**Objetivo**: Criar uma ferramenta para educadores físicos gerenciarem alunos, realizarem avaliações e prescreverem treinos personalizados

---

## Milestone 1: Setup Inicial e Estrutura Base

**Duração estimada**: 3 dias
**Objetivos:**

* Estrutura inicial do projeto PHP
* Configuração do banco de dados MySQL
* Ambiente responsivo inicial (HTML + CSS base com media queries)
* Conexão PHP com MySQL

**Entregas:**

* Estrutura de pastas organizada
* Arquivo `.env` para credenciais e configurações
* Tabela `usuarios` criada no banco de dados
* Sistema de login básico funcional

---

## Milestone 2: Cadastro e Gerenciamento de Alunos

**Duração estimada**: 4 dias
**Objetivos:**

* Interface para cadastro de alunos
* Tela de listagem com filtros e busca
* CRUD completo de alunos (criar, visualizar, editar, excluir)

**Entregas:**

* Interface responsiva para gestão de alunos
* Funções PHP com SQL preparado para segurança (SQL injection prevention)

---

## Milestone 3: Módulo de Avaliação Física

**Duração estimada**: 6 dias
**Objetivos:**

* Formulários para coleta de dados:

  * Anamnese
  * Composição corporal (Pollock, Guedes, etc)
  * Perimetria
  * Avaliação postural
  * Testes de VO2Max
* Cálculos automáticos com base nos dados inseridos

**Entregas:**

* Tabelas específicas no banco de dados para armazenar as avaliações
* Interface intuitiva para preenchimento e visualização dos resultados
* Gráficos comparativos simples com JavaScript (Chart.js)

---

## Milestone 4: Módulo de Prescrição de Treinos

**Duração estimada**: 5 dias
**Objetivos:**

* Cadastro e edição de treinos
* Associação de treinos a alunos
* Upload de vídeos explicativos
* Compartilhamento de planilha por link direto/QR code

**Entregas:**

* Interface para montagem dos treinos
* Armazenamento de links e vídeos
* Exportação para PDF ou compartilhamento via WhatsApp

---

## Milestone 5: Acesso e Painel do Aluno

**Duração estimada**: 3 dias
**Objetivos:**

* Tela de login para aluno
* Visualização dos treinos e avaliações vinculadas
* Histórico de progresso com gráficos

**Entregas:**

* Tela responsiva com menu simplificado para aluno
* Consulta às informações do banco por ID
* Visualização de vídeos e planilhas

---

## Milestone 6: Admin, Segurança e Finalização

**Duração estimada**: 3 dias
**Objetivos:**

* Criação de painel administrativo para controle de contas e planos
* Sistema de permissões (admin, professor, aluno)
* Proteção de rotas e dados sensíveis
* Testes finais automatizados com logs

**Entregas:**

* Painel de controle
* Validações de sessão e segurança
* Backup e exportação do banco de dados

---

## Total Estimado de Tempo: 24 dias de trabalho

**Observação:** Todas as milestones serão implementadas utilizando o VSCode com GitHub Copilot, com foco em testes locais automáticos ao final de cada etapa. A responsividade mobile é prioridade para todas as interfaces.

---

## 🎨 Sugestão de Paleta de Cores: "Energia & Confiança"

| Cor            | Hex     | Uso sugerido                                          |
| -------------- | ------- | ----------------------------------------------------- |
| Azul Cobalto   | #2F80ED | Cor principal (botões, links, destaques)              |
| Cinza Neutro   | #4F4F4F | Textos principais                                     |
| Cinza Claro    | #F2F2F2 | Fundo de telas, áreas neutras                         |
| Verde Saúde    | #27AE60 | Indicadores de progresso, sucesso (ex: avaliações OK) |
| Vermelho Suave | #EB5757 | Erros, alertas ou remoção                             |
| Branco Puro    | #FFFFFF | Fundo principal ou seções destacadas                  |
| Amarelo Claro  | #F2C94C | Avisos, tooltips e destaques secundários              |
