# Encontrar Emails que Não Chegaram na Caixa de Entrada

## ✅ Confirmado: Email está sendo enviado!

Os logs mostram: `Email sent successfully to leads@senior-floors.com`

O código está funcionando perfeitamente. O problema é que o Gmail não está mostrando o email na caixa de entrada.

## 🔍 Onde o Email Pode Estar

### 1. Pasta de Spam (Mais Comum)

1. Acesse o Gmail de `leads@senior-floors.com`
2. Clique em **Spam** ou **Lixo eletrônico** (no menu lateral esquerdo)
3. Procure por emails de `contact@senior-floors.com`
4. Se encontrar:
   - Marque como "Não é spam"
   - Selecione o email e clique em "Não é spam"

### 2. Pasta "Todos os emails"

1. No Gmail, no menu lateral esquerdo
2. Role para baixo e clique em **Todos os emails**
3. Procure por emails de `contact@senior-floors.com`
4. Ou use a busca: `from:contact@senior-floors.com`

### 3. Pesquisar no Gmail

Use estas buscas no Gmail:

```
from:contact@senior-floors.com
```

```
subject:"New Lead from Senior Floors"
```

```
"Senior Floors Website"
```

```
leads@senior-floors.com
```

### 4. Verificar Filtros

1. No Gmail, clique no ícone de engrenagem ⚙️
2. Vá em **Ver todas as configurações**
3. Clique na aba **Filtros e endereços bloqueados**
4. Verifique se há filtros que podem estar:
   - Escondendo emails
   - Movendo para outras pastas
   - Deletando automaticamente

### 5. Verificar Endereços Bloqueados

1. No Gmail, vá em **Configurações** > **Filtros e endereços bloqueados**
2. Role até **Endereços bloqueados**
3. Verifique se `contact@senior-floors.com` está bloqueado
4. Se estiver, remova da lista

### 6. Verificar Lixeira

1. No Gmail, clique em **Lixeira** (no menu lateral)
2. Procure por emails recentes
3. Se encontrar, restaure o email

## ✅ Solução Definitiva: Criar Filtro no Gmail

Para garantir que os emails SEMPRE cheguem na caixa de entrada:

### Passo a Passo:

1. **Acesse o Gmail de `leads@senior-floors.com`**

2. **Clique no ícone de engrenagem ⚙️** (canto superior direito)

3. **Vá em "Ver todas as configurações"**

4. **Clique na aba "Filtros e endereços bloqueados"**

5. **Clique em "Criar um novo filtro"**

6. **No campo "De", digite:**
   ```
   contact@senior-floors.com
   ```

7. **Clique em "Criar filtro"**

8. **Marque estas opções:**
   - ✅ **Nunca enviar para Spam**
   - ✅ **Sempre marcá-lo como importante**
   - ✅ **Aplicar o rótulo:** (crie um rótulo "Leads" se quiser)
   - ✅ **Também aplicar filtro a X conversas correspondentes** (se aparecer)

9. **Clique em "Criar filtro"**

10. **Pronto!** Agora todos os emails de `contact@senior-floors.com` vão:
    - Sempre chegar na caixa de entrada
    - Nunca ir para spam
    - Ser marcados como importantes

## 🧪 Teste Manual

Para confirmar que tudo está funcionando:

1. **Envie um email manualmente:**
   - Abra o Gmail de `contact@senior-floors.com`
   - Envie um email para `leads@senior-floors.com`
   - Assunto: "Test - Senior Floors"
   - Verifique se chegou

2. **Se o email manual chegou:**
   - O problema era apenas spam/filtros
   - O filtro que você criou vai resolver

3. **Se o email manual NÃO chegou:**
   - Há um problema com a conta `leads@senior-floors.com`
   - Verifique se a conta existe e está ativa
   - Entre em contato com o suporte do Google Workspace

## 📊 Verificar Logs

Você pode verificar quando os emails foram enviados:

1. Acesse `check-email-status.php`
2. Veja os logs de envio
3. Compare com a hora que você enviou o formulário

## ✅ Checklist

- [ ] Verificou pasta de spam?
- [ ] Verificou "Todos os emails"?
- [ ] Usou a busca `from:contact@senior-floors.com`?
- [ ] Verificou filtros do Gmail?
- [ ] Verificou endereços bloqueados?
- [ ] Criou filtro para garantir entrega?
- [ ] Testou enviar email manualmente?

## 🎯 Próximos Passos

1. **Imediato**: Verifique a pasta de spam
2. **Curto prazo**: Crie o filtro no Gmail
3. **Teste**: Envie o formulário novamente e verifique se chegou

## 💡 Dica Extra

Você também pode configurar um **encaminhamento automático** no Gmail:

1. Vá em **Configurações** > **Encaminhamento e POP/IMAP**
2. Adicione um endereço de email para encaminhar
3. Todos os emails de `contact@senior-floors.com` serão encaminhados automaticamente

---

**Lembre-se**: O código está funcionando perfeitamente! O email está sendo enviado. O problema é apenas de organização/filtros do Gmail. O filtro que você criar vai resolver isso definitivamente.
