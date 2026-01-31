#!/bin/bash

# Deploy completo pelo Git
# Faz add, commit e push para main; o GitHub Actions (FTP) envia os arquivos para o Hostinger.

set -e

echo "🚀 Deploy completo pelo Git..."
echo ""

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar se estamos no diretório correto
if [ ! -f "system.php" ] && [ ! -f "send-lead.php" ]; then
    echo -e "${RED}❌ Erro: Execute este script a partir do diretório raiz do projeto (senior-floors-landing)${NC}"
    exit 1
fi

# Verificar se git está configurado
if ! git remote -v | grep -q "origin"; then
    echo -e "${RED}❌ Erro: Git remote não configurado${NC}"
    echo "Configure o remote com: git remote add origin <URL_DO_REPOSITORIO>"
    exit 1
fi

echo -e "${YELLOW}📋 Verificando mudanças...${NC}"
git status --short

echo ""
echo -e "${YELLOW}📦 Adicionando arquivos ao staging...${NC}"
git add .

# Verificar se há algo para commitar
if git diff --cached --quiet 2>/dev/null && git diff --quiet 2>/dev/null; then
    echo -e "${YELLOW}Nenhuma alteração para commitar. (Já está tudo em dia ou nada foi adicionado.)${NC}"
    echo "Para forçar um deploy, faça uma pequena alteração e rode o script de novo."
    exit 0
fi

echo ""
echo -e "${YELLOW}💾 Criando commit...${NC}"
COMMIT_MSG="Deploy completo: LP, send-lead, CORS, form para banco

- LP (index.html, script.js) envia para senior-floors.com/send-lead.php
- send-lead.php: CORS (OPTIONS), application/x-www-form-urlencoded
- Form hero/contact salvando no banco (receive-lead)"

git commit -m "$COMMIT_MSG"

echo ""
echo -e "${GREEN}✅ Commit criado.${NC}"
echo -e "${YELLOW}📊 Resumo:${NC}"
git log -1 --oneline

echo ""
echo -e "${YELLOW}🚀 Fazendo push para origin main...${NC}"
echo "Isso dispara o workflow 'Deploy to Hostinger' (FTP) no GitHub Actions."
echo ""

git push origin main

echo ""
echo -e "${GREEN}✅ Push concluído.${NC}"
echo -e "${GREEN}🎉 Deploy iniciado no GitHub Actions.${NC}"
echo ""
echo "Próximos passos:"
echo "1. Abra o repositório no GitHub → aba Actions"
echo "2. Confira o workflow 'Deploy to Hostinger' (deve estar rodando ou já verde)"
echo "3. Aguarde o fim do deploy (alguns minutos)"
echo "4. Teste o site e o formulário (LP → senior-floors.com/send-lead.php → banco)"
echo ""
echo -e "${YELLOW}Se o deploy falhar:${NC} confira os secrets HOSTINGER_FTP_HOST, HOSTINGER_FTP_USER, HOSTINGER_FTP_PASSWORD em Settings → Secrets and variables → Actions."
echo ""
