#!/bin/bash
# Script de Teste - Verifica se tudo está configurado corretamente

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}🧪 TESTE DE CONFIGURAÇÃO - Senior Floors System${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# 1. Verificar diretório
echo -e "${YELLOW}1. Verificando diretório...${NC}"
if [ -f "system.php" ] && [ -f "send-lead.php" ]; then
    echo -e "${GREEN}   ✅ Diretório correto${NC}"
else
    echo -e "${RED}   ❌ Diretório incorreto${NC}"
    exit 1
fi

# 2. Verificar Git
echo -e "${YELLOW}2. Verificando Git...${NC}"
if [ -d ".git" ]; then
    echo -e "${GREEN}   ✅ Repositório Git inicializado${NC}"
    BRANCH=$(git branch --show-current)
    echo -e "   📍 Branch: ${BLUE}$BRANCH${NC}"
else
    echo -e "${RED}   ❌ Git não inicializado${NC}"
    exit 1
fi

# 3. Verificar Remote
echo -e "${YELLOW}3. Verificando conexão com GitHub...${NC}"
REMOTE=$(git remote get-url origin 2>/dev/null)
if [ ! -z "$REMOTE" ]; then
    echo -e "${GREEN}   ✅ Remote configurado${NC}"
    echo -e "   🔗 URL: ${BLUE}$REMOTE${NC}"
    
    # Testar conexão
    echo -e "   🔍 Testando conexão..."
    if git ls-remote --heads origin main &>/dev/null; then
        echo -e "${GREEN}   ✅ Conexão com GitHub OK${NC}"
    else
        echo -e "${YELLOW}   ⚠️  Não conseguiu conectar (pode ser normal se repo é novo)${NC}"
    fi
else
    echo -e "${RED}   ❌ Remote não configurado${NC}"
    echo -e "   💡 Execute: git remote add origin https://github.com/SEU_USUARIO/senior-floors-system.git"
fi

# 4. Verificar Commits
echo -e "${YELLOW}4. Verificando commits...${NC}"
COMMIT_COUNT=$(git rev-list --count HEAD 2>/dev/null || echo "0")
if [ "$COMMIT_COUNT" -gt "0" ]; then
    echo -e "${GREEN}   ✅ $COMMIT_COUNT commit(s) encontrado(s)${NC}"
    echo -e "   📝 Último commit:"
    git log -1 --oneline --no-decorate 2>/dev/null | sed 's/^/      /'
else
    echo -e "${RED}   ❌ Nenhum commit encontrado${NC}"
fi

# 5. Verificar Status
echo -e "${YELLOW}5. Verificando status do repositório...${NC}"
if git diff --quiet && git diff --cached --quiet; then
    echo -e "${GREEN}   ✅ Working tree limpo (sem mudanças pendentes)${NC}"
else
    echo -e "${YELLOW}   ⚠️  Há mudanças não commitadas${NC}"
    git status --short | head -5 | sed 's/^/      /'
fi

# 6. Verificar GitHub Actions
echo -e "${YELLOW}6. Verificando GitHub Actions...${NC}"
if [ -d ".github/workflows" ]; then
    WORKFLOW_COUNT=$(ls -1 .github/workflows/*.yml 2>/dev/null | wc -l | tr -d ' ')
    if [ "$WORKFLOW_COUNT" -gt "0" ]; then
        echo -e "${GREEN}   ✅ $WORKFLOW_COUNT workflow(s) configurado(s)${NC}"
        ls -1 .github/workflows/*.yml 2>/dev/null | sed 's/^/      /' | sed 's/\.github\/workflows\///'
    else
        echo -e "${RED}   ❌ Nenhum workflow encontrado${NC}"
    fi
else
    echo -e "${RED}   ❌ Pasta .github/workflows não existe${NC}"
fi

# 7. Verificar .gitignore
echo -e "${YELLOW}7. Verificando .gitignore...${NC}"
if [ -f ".gitignore" ]; then
    echo -e "${GREEN}   ✅ .gitignore existe${NC}"
    IGNORE_COUNT=$(grep -v '^#' .gitignore | grep -v '^$' | wc -l | tr -d ' ')
    echo -e "   📋 $IGNORE_COUNT regra(s) de exclusão"
else
    echo -e "${RED}   ❌ .gitignore não encontrado${NC}"
fi

# 8. Verificar arquivos importantes
echo -e "${YELLOW}8. Verificando arquivos importantes...${NC}"
FILES_OK=0
FILES_TOTAL=0

check_file() {
    FILES_TOTAL=$((FILES_TOTAL + 1))
    if [ -f "$1" ]; then
        echo -e "${GREEN}   ✅ $1${NC}"
        FILES_OK=$((FILES_OK + 1))
    else
        echo -e "${YELLOW}   ⚠️  $1 (não encontrado)${NC}"
    fi
}

check_file "send-lead.php"
check_file "system.php"
check_file "config/database.php"
check_file "database/schema.sql"
check_file "README.md"

echo -e "   📊 $FILES_OK/$FILES_TOTAL arquivos encontrados"

# 9. Resumo
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}📊 RESUMO${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"

ALL_OK=true

if [ -z "$REMOTE" ]; then
    echo -e "${YELLOW}⚠️  Remote não configurado${NC}"
    echo -e "   Execute: git remote add origin https://github.com/SEU_USUARIO/senior-floors-system.git"
    ALL_OK=false
fi

if [ "$COMMIT_COUNT" -eq "0" ]; then
    echo -e "${YELLOW}⚠️  Nenhum commit encontrado${NC}"
    ALL_OK=false
fi

if [ ! -d ".github/workflows" ]; then
    echo -e "${YELLOW}⚠️  GitHub Actions não configurado${NC}"
    ALL_OK=false
fi

if [ "$ALL_OK" = true ] && [ ! -z "$REMOTE" ]; then
    echo -e "${GREEN}✅ Tudo parece estar configurado!${NC}"
    echo ""
    echo -e "${BLUE}Próximo passo:${NC}"
    echo -e "   git push -u origin main"
    echo ""
    echo -e "${YELLOW}Nota:${NC} Se pedir credenciais, use Personal Access Token"
    echo -e "   (não sua senha normal do GitHub)"
else
    echo -e "${YELLOW}⚠️  Algumas configurações estão faltando${NC}"
    echo -e "   Veja os avisos acima"
fi

echo ""
