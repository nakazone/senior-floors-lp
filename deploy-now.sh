#!/bin/bash

# Script de Deploy - CRM v3.0 Completo
# Este script prepara e faz push das mudanças para GitHub
# O GitHub Actions fará o deploy automático para Hostinger

set -e

echo "🚀 Iniciando Deploy do CRM v3.0..."
echo ""

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar se estamos no diretório correto
if [ ! -f "system.php" ]; then
    echo -e "${RED}❌ Erro: Execute este script a partir do diretório raiz do projeto${NC}"
    exit 1
fi

# Verificar se git está configurado
if ! git remote -v | grep -q "origin"; then
    echo -e "${RED}❌ Erro: Git remote não configurado${NC}"
    echo "Configure o remote com: git remote add origin <URL>"
    exit 1
fi

echo -e "${YELLOW}📋 Verificando mudanças...${NC}"
git status --short

echo ""
echo -e "${YELLOW}📦 Adicionando arquivos ao staging...${NC}"

# Adicionar todos os arquivos novos e modificados
git add .

echo ""
echo -e "${YELLOW}💾 Criando commit...${NC}"

# Criar commit com mensagem descritiva
COMMIT_MSG="Deploy CRM v3.0: Customers, Projects, Coupons, Activities e Assignment

- ✅ Módulo de Customers completo
- ✅ Módulo de Projects com Pós-Atendimento
- ✅ Módulo de Coupons
- ✅ Sistema de Activities
- ✅ Sistema de Assignment
- ✅ 21 novos endpoints de API
- ✅ 5 novos módulos admin
- ✅ Migration v2→v3 do banco de dados"

git commit -m "$COMMIT_MSG"

echo ""
echo -e "${GREEN}✅ Commit criado com sucesso!${NC}"
echo ""

# Mostrar resumo do commit
echo -e "${YELLOW}📊 Resumo do commit:${NC}"
git log -1 --stat --oneline

echo ""
echo -e "${YELLOW}🚀 Fazendo push para GitHub...${NC}"
echo "Isso vai disparar o deploy automático via GitHub Actions"
echo ""

# Fazer push
git push origin main

echo ""
echo -e "${GREEN}✅ Push realizado com sucesso!${NC}"
echo ""
echo -e "${GREEN}🎉 Deploy iniciado!${NC}"
echo ""
echo "📝 Próximos passos:"
echo "1. Acesse: https://github.com/nakazone/senior-floors-system/actions"
echo "2. Verifique o workflow 'Deploy to Hostinger (SSH) - Fixed'"
echo "3. Aguarde a conclusão do deploy (geralmente 2-5 minutos)"
echo "4. Execute a migration do banco: database/migration-v2-to-v3.sql"
echo "5. Teste os novos módulos no sistema admin"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANTE:${NC}"
echo "- Execute a migration do banco de dados após o deploy"
echo "- Verifique se config/database.php está configurado no servidor"
echo "- Teste os módulos: customers, projects, coupons"
echo ""
