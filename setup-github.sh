#!/bin/bash
# Script de Setup GitHub + Deploy Automático
# Senior Floors System

echo "🚀 Configurando GitHub + Deploy Automático para Hostinger"
echo ""

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar se está no diretório correto
if [ ! -f "system.php" ]; then
    echo -e "${RED}❌ Erro: Execute este script no diretório do projeto${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Diretório correto detectado${NC}"
echo ""

# Verificar se git está inicializado
if [ ! -d ".git" ]; then
    echo -e "${YELLOW}⚠️  Git não inicializado. Inicializando...${NC}"
    git init
    git branch -M main
fi

# Adicionar todos os arquivos
echo -e "${GREEN}📦 Adicionando arquivos ao Git...${NC}"
git add .

# Verificar se há mudanças
if git diff --staged --quiet; then
    echo -e "${YELLOW}⚠️  Nenhuma mudança para commitar${NC}"
else
    echo -e "${GREEN}💾 Criando commit inicial...${NC}"
    git commit -m "Initial commit: Senior Floors System - Complete setup with auto-deploy"
    echo -e "${GREEN}✅ Commit criado!${NC}"
fi

echo ""
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}📋 PRÓXIMOS PASSOS MANUAIS:${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo "1. Crie o repositório no GitHub:"
echo "   👉 Acesse: https://github.com/new"
echo "   👉 Nome: senior-floors-system"
echo "   👉 Visibilidade: Private"
echo "   👉 NÃO marque 'Add README'"
echo ""
echo "2. Depois de criar, execute:"
echo ""
echo -e "${GREEN}   git remote add origin https://github.com/SEU_USUARIO/senior-floors-system.git${NC}"
echo ""
echo "   (Substitua SEU_USUARIO pelo seu username do GitHub)"
echo ""
echo "3. Faça o primeiro push:"
echo ""
echo -e "${GREEN}   git push -u origin main${NC}"
echo ""
echo "4. Configure Secrets no GitHub:"
echo "   👉 Settings → Secrets and variables → Actions"
echo "   👉 Adicione as credenciais FTP ou SSH do Hostinger"
echo ""
echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${GREEN}✅ Configuração local completa!${NC}"
echo ""
echo "📚 Veja GITHUB_SETUP_QUICK.md para instruções detalhadas"
echo ""
