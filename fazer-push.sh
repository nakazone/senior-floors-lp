#!/bin/bash
# Script para fazer push no GitHub

echo "🚀 Configurando Push para GitHub"
echo ""

# Verificar se remote existe
if git remote -v | grep -q "origin"; then
    echo "✅ Remote já configurado:"
    git remote -v
    echo ""
    read -p "Deseja fazer push agora? (s/n) " resposta
    if [ "$resposta" = "s" ]; then
        echo ""
        echo "📤 Fazendo push..."
        git push -u origin main
    fi
else
    echo "⚠️  Remote não configurado ainda."
    echo ""
    echo "Primeiro, você precisa:"
    echo "1. Criar repositório no GitHub: https://github.com/new"
    echo "2. Depois execute:"
    echo ""
    echo "   git remote add origin https://github.com/SEU_USUARIO/senior-floors-system.git"
    echo ""
    echo "   (Substitua SEU_USUARIO pelo seu username)"
    echo ""
    read -p "Já criou o repo? Digite a URL completa: " url
    if [ ! -z "$url" ]; then
        git remote add origin "$url"
        echo "✅ Remote adicionado!"
        echo ""
        read -p "Deseja fazer push agora? (s/n) " resposta
        if [ "$resposta" = "s" ]; then
            echo ""
            echo "📤 Fazendo push..."
            git push -u origin main
        fi
    fi
fi
