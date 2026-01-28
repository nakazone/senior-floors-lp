# 📱 Guia de Instalação PWA - Senior Floors CRM

## 🎯 O Que Foi Implementado

✅ Logo Senior Floors no header do sistema admin  
✅ Favicon usando o logo  
✅ Manifest.json para PWA  
✅ Service Worker para funcionamento offline  
✅ Suporte para instalação como app no mobile  

---

## 📱 Como Instalar no Mobile

### iPhone (Safari)

1. **Acesse o sistema** no Safari:
   ```
   https://seudominio.com/system.php
   ```

2. **Toque no botão de compartilhar** (ícone de compartilhamento na parte inferior)

3. **Role para baixo** e toque em **"Adicionar à Tela de Início"**

4. **Personalize o nome** (opcional) e toque em **"Adicionar"**

5. **✅ Pronto!** O app aparecerá na sua tela inicial com o logo

### Android (Chrome)

1. **Acesse o sistema** no Chrome:
   ```
   https://seudominio.com/system.php
   ```

2. **Toque no menu** (3 pontos no canto superior direito)

3. **Toque em "Adicionar à tela inicial"** ou **"Instalar app"**

4. **Confirme** a instalação

5. **✅ Pronto!** O app aparecerá na sua tela inicial

---

## 🖥️ Como Instalar no Desktop

### Chrome/Edge (Windows/Mac/Linux)

1. **Acesse o sistema** no navegador

2. **Procure pelo ícone de instalação** na barra de endereços (geralmente um ícone de "+" ou "instalar")

3. **Clique em "Instalar"** ou **"Adicionar ao Chrome"**

4. **Confirme** a instalação

5. **✅ Pronto!** O app abrirá em uma janela separada

---

## ✨ Funcionalidades PWA

### ✅ O Que Funciona Offline

- Interface básica do sistema
- Navegação entre módulos já visitados
- Logo e recursos estáticos

### ⚠️ O Que Precisa de Internet

- Carregar dados do banco de dados
- Enviar formulários
- Atualizar informações
- APIs do sistema

---

## 🎨 Personalização

### Logo

O logo usado é: `assets/logoSeniorFloors.png`

### Cores do Tema

- Cor principal: `#1a2036` (azul escuro)
- Cor de fundo: `#1a2036`
- Definido no `manifest.json`

### Nome do App

- Nome completo: "Senior Floors CRM"
- Nome curto: "SF CRM"
- Definido no `manifest.json`

---

## 🔧 Arquivos Criados

### 1. `manifest.json`
Arquivo de configuração do PWA que define:
- Nome do app
- Ícones
- Cores do tema
- Modo de exibição
- Atalhos (shortcuts)

### 2. `sw.js` (Service Worker)
Script que permite:
- Cache de recursos
- Funcionamento offline básico
- Atualizações automáticas

### 3. Meta Tags no `system.php`
Tags HTML que habilitam:
- Instalação no iOS
- Instalação no Android
- Tema personalizado
- Modo standalone

---

## 🧪 Testar PWA

### Verificar se está funcionando:

1. **Acesse:** `https://seudominio.com/system.php`

2. **Abra o DevTools** (F12)

3. **Vá em "Application"** (Chrome) ou **"Application"** (Firefox)

4. **Verifique:**
   - ✅ Manifest está carregado
   - ✅ Service Worker está registrado e ativo
   - ✅ Cache está funcionando

### Testar Instalação:

1. **No mobile**, siga os passos acima
2. **Verifique** se o app aparece na tela inicial
3. **Abra o app** e verifique se funciona offline básico

---

## ⚠️ Requisitos

### Para PWA Funcionar:

- ✅ **HTTPS obrigatório** (já deve estar configurado)
- ✅ Service Worker deve estar acessível em `/sw.js`
- ✅ Manifest deve estar acessível em `/manifest.json`
- ✅ Logo deve estar em `assets/logoSeniorFloors.png`

---

## 🐛 Problemas Comuns

### App não aparece para instalar

**Causa:** Requisitos PWA não atendidos

**Solução:**
- Verifique se está usando HTTPS
- Verifique se `manifest.json` está acessível
- Verifique se `sw.js` está registrado
- Limpe o cache do navegador

### Logo não aparece

**Causa:** Caminho incorreto do logo

**Solução:**
- Verifique se `assets/logoSeniorFloors.png` existe
- Verifique permissões do arquivo
- Verifique caminho no código

### Service Worker não registra

**Causa:** Erro no script ou HTTPS não configurado

**Solução:**
- Verifique console do navegador para erros
- Certifique-se de que está usando HTTPS
- Verifique se `sw.js` está acessível

---

## 📋 Checklist

- [ ] Logo aparece no header do sistema
- [ ] Favicon aparece na aba do navegador
- [ ] Manifest.json está acessível
- [ ] Service Worker está registrado
- [ ] App pode ser instalado no mobile
- [ ] App funciona offline básico
- [ ] Logo aparece quando instalado

---

## 🎯 Próximos Passos (Opcional)

### Melhorias Futuras:

- [ ] Adicionar notificações push
- [ ] Melhorar cache offline
- [ ] Adicionar sincronização em background
- [ ] Criar splash screen personalizado
- [ ] Adicionar mais atalhos (shortcuts)

---

**Última atualização:** Janeiro 2025
