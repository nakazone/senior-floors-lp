# Mudar para Formspree - Solução Mais Simples

## ✅ Por que Formspree?

A App Password do Google não está funcionando. Formspree é:
- ✅ **Muito mais simples** - só copiar e colar
- ✅ **Mais confiável** - emails sempre chegam
- ✅ **Zero configuração** - não precisa SMTP
- ✅ **Gratuito** - até 50 envios/mês
- ✅ **Funciona imediatamente**

## 📋 Passo a Passo (5 minutos)

### 1. Criar Conta no Formspree

1. Acesse: **https://formspree.io**
2. Clique em **Sign Up** (criar conta)
3. Use seu email (pode ser `contact@senior-floors.com`)
4. Confirme o email

### 2. Criar Formulário

1. Depois de fazer login, clique em **New Form**
2. Nome: "Senior Floors Contact Form"
3. Configure:
   - **Email notifications to**: `leads@senior-floors.com`
   - **Subject**: `New Lead from Senior Floors Website`
4. Clique em **Create Form**

### 3. Copiar Endpoint

1. Você verá um **Endpoint URL**
2. Será algo como: `https://formspree.io/f/abc123xyz`
3. **COPIE ESSE URL COMPLETO**

### 4. Atualizar JavaScript

**Opção A: Usar o arquivo pronto**

1. Abra o arquivo `script-formspree.js`
2. Encontre a linha 8:
   ```javascript
   const FORMSPREE_ENDPOINT = 'YOUR_FORMSPREE_ENDPOINT_HERE';
   ```
3. Substitua por:
   ```javascript
   const FORMSPREE_ENDPOINT = 'https://formspree.io/f/SEU_ID_AQUI';
   ```
4. Renomeie `script-formspree.js` para `script.js` (faça backup do antigo primeiro)

**Opção B: Atualizar manualmente**

1. Abra `script.js`
2. Encontre (linha ~199):
   ```javascript
   fetch('contact-form-handler.php', {
   ```
3. Substitua por:
   ```javascript
   fetch('https://formspree.io/f/SEU_ID_AQUI', {
   ```
4. Faça isso para **ambos os formulários** (hero e contact)
5. Também precisa mudar o formato dos dados (veja `script-formspree.js` como exemplo)

### 5. Testar

1. Preencha o formulário
2. Envie
3. Verifique se o email chegou em `leads@senior-floors.com`
4. Pronto! 🎉

## ✅ Vantagens

- ✅ **Funciona imediatamente** - sem configuração complexa
- ✅ **Emails sempre chegam** - muito mais confiável
- ✅ **Dashboard** - veja todos os envios na conta do Formspree
- ✅ **Gratuito** - 50 envios/mês (suficiente para começar)

## 📊 Comparação

| | SMTP Google | Formspree |
|---|---|---|
| Configuração | Complexa (App Password) | Simples (copiar URL) |
| Confiabilidade | Média | Alta |
| Tempo de setup | 30+ minutos | 5 minutos |
| Funciona sempre? | Às vezes | Sim |

## 🆘 Se Precisar de Mais de 50 Envios/Mês

- **Formspree Pro**: $10/mês (ilimitado)
- **EmailJS**: 200 envios/mês grátis
- Ou configure SMTP depois (quando tiver tempo)

## 💡 Dica

Você pode manter o `contact-form-handler.php` para salvar no CSV também, mas com Formspree você não precisa porque todos os envios ficam salvos na conta do Formspree!

---

**Recomendação**: Use Formspree agora para ter emails funcionando imediatamente. Depois, se quiser, pode tentar configurar SMTP novamente.
