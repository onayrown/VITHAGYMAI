# 🏋️‍♂️ SMARTBIOFIT

**Aplicativo Web de Avaliação Física Profissional**

Sistema desenvolvido para educadores físicos gerenciarem alunos, realizarem avaliações físicas completas e prescreverem treinos personalizados.

## 🎯 Status do Projeto

✅ **Milestone 1**: Setup Inicial e Estrutura Base - **CONCLUÍDO**  
✅ **Milestone 2**: Cadastro e Gerenciamento de Alunos - **CONCLUÍDO** 🎉  
🔄 **Milestone 3**: Módulo de Avaliação Física - *Próximo*  
⏳ **Milestone 4**: Módulo de Prescrição de Treinos  
⏳ **Milestone 5**: Acesso e Painel do Aluno  
⏳ **Milestone 6**: Admin, Segurança e Finalização  

## 🚀 Características Implementadas

### 🎉 Milestone 2 - Sistema de Gerenciamento de Alunos (COMPLETO)
- **✅ CRUD Completo**: Criar, visualizar, editar e desativar alunos
- **✅ Busca e Filtros**: Por nome, email, telefone, sexo e status
- **✅ Interface Responsiva**: Design mobile-first com logo integrada
- **✅ Validação de Dados**: Client-side e server-side
- **✅ Modal de Detalhes**: Visualização completa do perfil do aluno
- **✅ API Endpoint**: Suporte para integração futura
- **✅ Logo e Branding**: Navegação profissional implementada
- **✅ Menu Mobile**: Funcionalidade responsiva completa

### Funcionalidades de Segurança
- **✅ Autenticação robusta** com hash de senhas
- **✅ Controle de acesso** baseado em roles (admin/professor/aluno)
- **✅ Proteção SQL Injection** via prepared statements
- **✅ Validação de dados** em múltiplas camadas
- **✅ Log de atividades** para auditoria

## 🎨 Paleta de Cores

| Cor | Hex | Uso |
|-----|-----|-----|
| Azul Cobalto | `#2F80ED` | Cor principal (botões, links, destaques) |
| Verde Saúde | `#27AE60` | Indicadores de sucesso e progresso |
| Vermelho Suave | `#EB5757` | Erros, alertas e remoção |
| Cinza Neutro | `#4F4F4F` | Textos principais |
| Cinza Claro | `#F2F2F2` | Fundo de telas, áreas neutras |
| Amarelo Claro | `#F2C94C` | Avisos e destaques secundários |

## 🎯 Logo e Branding

### Integração da Logo SMARTBIOFIT
- **✅ Logo Principal**: Localizada em `/assets/images/logo-smartbiofit.png`
- **✅ Navegação**: Logo + texto "SMARTBIOFIT" no header
- **✅ Responsividade**: 40px (desktop) / 32px (mobile)
- **✅ Favicon**: Integrado em todas as páginas
- **✅ Footer**: Logo reduzida no rodapé
- **✅ Menu Mobile**: Funcional com animação hambúrguer

### Arquivos da Logo
```
assets/images/logo-smartbiofit.png  # Logo principal (364KB, PNG)
```

## 📦 Instalação

### Pré-requisitos

- XAMPP (Apache + MySQL + PHP)
- Navegador web moderno
- Acesso à internet (para CDNs)

### Passos de Instalação

1. **Clone/Baixe o projeto** para `c:\xampp\htdocs\smartbiofit`

2. **Configure o banco de dados**:
   - Abra o phpMyAdmin (http://localhost/phpmyadmin)
   - Crie um banco chamado `smartbiofit`
   - Configure a codificação como `utf8mb4_unicode_ci`

3. **Configure o ambiente**:
   - Edite o arquivo `.env` com suas configurações
   - Ajuste o `APP_URL` para seu IP local ou domínio

4. **Execute o setup**:
   - Acesse `http://SEU_IP/smartbiofit/setup.php`
   - Execute a inicialização do banco de dados

5. **Primeiro acesso**:
   - Acesse `http://SEU_IP/smartbiofit/login.php`
   - Use as credenciais: `admin@smartbiofit.com` / `admin123`

## 🏗️ Estrutura do Projeto

```
smartbiofit/
├── 📁 assets/           # Recursos estáticos
│   ├── 📁 css/         # Estilos CSS
│   ├── 📁 js/          # Scripts JavaScript
│   └── 📁 images/      # Imagens do sistema
├── 📁 includes/        # Arquivos PHP incluídos
├── 📁 pages/           # Páginas do sistema
├── 📁 uploads/         # Arquivos enviados
│   ├── 📁 fotos/       # Fotos dos usuários
│   ├── 📁 videos/      # Vídeos dos treinos
│   └── 📁 documentos/  # Documentos e relatórios
├── 📁 logs/            # Logs do sistema
├── 📄 config.php       # Configurações principais
├── 📄 database.php     # Classe de banco de dados
├── 📄 login.php        # Página de login
├── 📄 index.php        # Dashboard principal
└── 📄 setup.php        # Inicialização do sistema
```

## 👥 Tipos de Usuário

### 🔧 Administrador
- Gerenciamento completo do sistema
- Controle de usuários e permissões
- Acesso a relatórios e logs
- Configurações do sistema

### 👨‍🏫 Professor
- Gerenciamento de alunos
- Realização de avaliações físicas
- Criação de treinos personalizados
- Geração de relatórios

### 👤 Aluno
- Visualização de avaliações
- Acesso aos treinos
- Acompanhamento do progresso
- Histórico de evolução

## 📊 Módulos do Sistema

### ✅ Milestone 1 - Setup Inicial (CONCLUÍDO)
- [x] Estrutura base do projeto
- [x] Sistema de autenticação
- [x] Interface responsiva
- [x] Configuração do banco de dados

### 🔄 Milestone 2 - Gestão de Alunos (EM DESENVOLVIMENTO)
- [ ] CRUD completo de alunos
- [ ] Sistema de busca e filtros
- [ ] Upload de fotos
- [ ] Dados pessoais e contato

### 🔄 Milestone 3 - Avaliações Físicas
- [ ] Anamnese
- [ ] Composição corporal (Pollock, Guedes)
- [ ] Perimetria
- [ ] Avaliação postural
- [ ] Testes de VO2Max

### 🔄 Milestone 4 - Prescrição de Treinos
- [ ] Criação de treinos
- [ ] Upload de vídeos
- [ ] Compartilhamento via QR code
- [ ] Exportação para PDF

### 🔄 Milestone 5 - Painel do Aluno
- [ ] Dashboard personalizado
- [ ] Histórico de progresso
- [ ] Visualização de treinos
- [ ] Gráficos de evolução

### 🔄 Milestone 6 - Administração e Segurança
- [ ] Painel administrativo
- [ ] Sistema de permissões
- [ ] Backup automatizado
- [ ] Testes automatizados

## 🔧 Configuração para Acesso Externo

### Configuração do XAMPP

1. **Edite o arquivo `httpd.conf`**:
   ```apache
   Listen 80
   Listen [SEU_IP]:80
   ```

2. **Configure o Virtual Host** (opcional):
   ```apache
   <VirtualHost *:80>
       DocumentRoot "c:/xampp/htdocs/smartbiofit"
       ServerName smartbiofit.local
   </VirtualHost>
   ```

3. **Ajuste o firewall** para permitir conexões na porta 80

### Configuração de Rede

- **IP Fixo**: Configure um IP fixo no roteador
- **Port Forwarding**: Redirecione a porta 80 para sua máquina
- **DDNS**: Use um serviço de DNS dinâmico se necessário

## 🔒 Segurança

### Implementações de Segurança

- ✅ Prepared Statements (SQL Injection Prevention)
- ✅ Input Sanitization (XSS Prevention)
- ✅ CSRF Protection
- ✅ Session Management
- ✅ Password Hashing (bcrypt)
- ✅ Headers de Segurança
- ✅ Logs de Auditoria

### Para Produção

1. **Altere credenciais padrão**
2. **Configure HTTPS** (SSL/TLS)
3. **Desabilite debug** (APP_DEBUG=false)
4. **Configure backup** automatizado
5. **Monitore logs** regularmente

## 📱 PWA (Progressive Web App)

O sistema está preparado para ser uma PWA:

- ✅ Design responsivo
- ✅ Service Worker (base implementada)
- ✅ Manifest (será criado no Milestone 6)
- ✅ Funcionalidade offline básica

## 🐛 Troubleshooting

### Problemas Comuns

**Erro de Conexão com Banco**:
- Verifique se o MySQL está rodando
- Confirme as credenciais no `.env`
- Teste a conexão via phpMyAdmin

**Página não carrega**:
- Verifique se o Apache está ativo
- Confirme o IP no arquivo `.env`
- Teste a conectividade de rede

**Erro de Permissão**:
- Verifique as permissões das pastas `uploads/` e `logs/`
- Execute `chmod 755` nas pastas necessárias (Linux/Mac)

## 📞 Suporte

Para suporte e dúvidas:

- **Email**: suporte@smartbiofit.com
- **Documentação**: Consulte os comentários no código
- **Logs**: Verifique `logs/app.log` para erros detalhados

## 📄 Licença

Este projeto é propriedade privada. Todos os direitos reservados.

---

**SMARTBIOFIT v1.0** - Desenvolvido com ❤️ para profissionais de educação física
