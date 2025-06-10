# VithaGymAI

Sistema de gestão inteligente para academias e centros de fitness com IA.

## 🚀 Tecnologias

- **Backend**: PHP 8.1
- **Banco de Dados**: MySQL 8.0
- **Frontend**: JavaScript, HTML5, CSS3
- **Infraestrutura**: Docker & Docker Compose
- **Autenticação**: JWT
- **Cache**: Sistema próprio de cache

## 📋 Pré-requisitos

- Docker Desktop
- Docker Compose
- Git

## 🛠️ Instalação e Execução

## 🛠️ Instalação e Execução

### 1. Clone o repositório
```bash
git clone https://github.com/onayrown/VithaGymAI.git
cd VithaGymAI
```

### 2. Configure o ambiente para Docker
```bash
# Copie o arquivo de configuração Docker
cp .env.docker .env

# OU mantenha seu .env atual e apenas altere as configurações de banco:
# DB_HOST=db (em vez de 127.0.0.1)
# APP_URL=http://localhost:8080
```

### 3. Execute com Docker
```bash
# Construir e executar os containers
docker-compose up -d --build

# Aguardar inicialização (1-2 minutos na primeira vez)
docker-compose logs -f web

# Verificar se os serviços estão rodando
docker-compose ps
```

### 4. Acesse a aplicação
- **Aplicação**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081 (root/root123)
- **MySQL**: localhost:3306

### 5. Login padrão
- **Email**: admin@vithagymai.com  
- **Senha**: admin123

> 💡 **Primeira execução**: O sistema criará automaticamente todas as tabelas e o usuário admin na primeira inicialização.

## 📝 Configurações de Ambiente

O projeto suporta múltiplos ambientes através de diferentes arquivos `.env`:

| Arquivo | Propósito | Banco | URL |
|---------|-----------|-------|-----|
| `.env.docker` | Desenvolvimento Docker | `vithagymai` | http://localhost:8080 |
| `.env.development` | Desenvolvimento XAMPP | `vithagymai_dev` | http://localhost/VithaGymAI |
| `.env.production` | Produção (backup) | `smartbiofit` | IP de produção |
| `.env.example` | Template para novos devs | `vithagymai` | localhost |

### 🔄 Trocar entre ambientes
```bash
# Para Docker
cp .env.docker .env && docker-compose up -d

# Para XAMPP local  
cp .env.development .env

# Voltar para produção (se aplicável)
cp .env.production .env
```

### 3. Execute com Docker
```bash
# Construir e executar os containers
docker-compose up -d --build

# Verificar se os serviços estão rodando
docker-compose ps
```

### 4. Acesse a aplicação
- **Aplicação**: http://localhost:8080
- **phpMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3306

## 📊 Estrutura do Projeto

```
VithaGymAI/
├── api/              # APIs e endpoints
├── assets/           # CSS, JS, imagens
├── includes/         # Arquivos incluídos (headers, etc.)
├── logs/             # Logs da aplicação
├── pages/            # Páginas da aplicação
├── uploads/          # Uploads de usuários
├── config.php        # Configurações do banco
├── index.php         # Página inicial
├── login.php         # Sistema de autenticação
└── docker-compose.yml # Configuração Docker
```

## 🐳 Comandos Docker Úteis

```bash
# Ver logs dos containers
docker-compose logs -f web    # Logs do Apache/PHP
docker-compose logs -f db     # Logs do MySQL

# Parar os containers
docker-compose down

# Rebuild após mudanças no código
docker-compose up -d --build

# Reinicializar apenas o banco (apaga dados!)
docker-compose down -v && docker-compose up -d

# Acessar container PHP
docker exec -it vithagymai_web bash

# Acessar MySQL diretamente
docker exec -it vithagymai_db mysql -u root -p

# Executar comandos PHP no container
docker exec vithagymai_web php /var/www/html/init-database.php

# Ver status dos containers
docker-compose ps

# Ver uso de recursos
docker stats
```

## 🔧 Troubleshooting

### Problema: "Connection refused" ao acessar localhost:8080
```bash
# Verificar se containers estão rodando
docker-compose ps

# Ver logs para identificar erros
docker-compose logs web
```

### Problema: Erro de conexão com banco de dados
```bash
# Aguardar MySQL inicializar completamente (pode demorar 1-2 min)
docker-compose logs db

# Reinicializar tabelas manualmente
docker exec smartbiofit_web php /var/www/html/init-database.php
```

### Problema: Permissões de arquivo
```bash
# Corrigir permissões manualmente
docker exec vithagymai_web chown -R www-data:www-data /var/www/html/
docker exec vithagymai_web chmod -R 777 /var/www/html/logs /var/www/html/uploads
```

## 🔄 Migração de Projeto Existente

Se você já possui um projeto **SMARTBIOFIT** funcionando e quer migrar para **VithaGymAI**:

### 📋 Passo a Passo Seguro

1. **Backup da configuração atual**:
```bash
cp .env .env.production.backup
```

2. **Configure desenvolvimento Docker**:
```bash
cp .env.docker .env
```

3. **Teste o Docker** (não afeta produção):
```bash
docker-compose up -d --build
# Acesse: http://localhost:8080
```

4. **Para voltar à produção** (quando necessário):
```bash
cp .env.production.backup .env
```

### ⚠️ Importante
- O ambiente Docker cria um banco **separado** (`vithagymai`)
- Sua produção (`smartbiofit`) **não é afetada**
- Você pode desenvolver e testar sem riscos
- Migração completa é **opcional** e pode ser feita quando estiver pronto

## 🚀 Deploy

### Para VPS/Produção
1. **Configure variáveis de ambiente para produção**:
```bash
cp .env.docker .env.production
# Edite .env.production com:
# - DB_HOST do seu servidor MySQL  
# - APP_URL do seu domínio
# - APP_ENV=production
# - APP_DEBUG=false
```

2. **Use Docker Compose em produção**:
```bash
cp .env.production .env
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

3. **Configure SSL/proxy reverso** (Traefik, Nginx, etc.)

### Compatibilidade
- ✅ **VPS** com Docker
- ✅ **Docker Swarm** 
- ✅ **Portainer**
- ✅ **Shared Hosting** (modo XAMPP/tradicional)

## 📝 Licença

Proprietary - Todos os direitos reservados

## 👨‍💻 Desenvolvedor

Desenvolvido por [onayrown e Pedro Bastos](https://github.com/onayrown)

---

⚠️ **Nota**: Certifique-se de configurar adequadamente as variáveis de ambiente antes de executar em produção.