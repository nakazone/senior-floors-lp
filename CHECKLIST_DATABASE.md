# ✅ Checklist: Configurar Banco de Dados

## 📋 Siga Esta Lista na Ordem

### 1. Criar Banco de Dados
- [ ] Acessar cPanel do Hostinger
- [ ] Ir em **MySQL Databases**
- [ ] Criar banco: `senior_floors_db`
- [ ] **Anotar nome completo:** `SEU_USUARIO_senior_floors_db`

### 2. Criar Usuário MySQL
- [ ] Na mesma página, criar usuário
- [ ] Nome: `senior_floors_user`
- [ ] **Anotar nome completo:** `SEU_USUARIO_senior_floors_user`
- [ ] Criar senha forte
- [ ] **Anotar senha**

### 3. Conectar Usuário ao Banco
- [ ] Adicionar usuário ao banco
- [ ] Marcar **ALL PRIVILEGES**
- [ ] Confirmar

### 4. Executar SQL
- [ ] Abrir **phpMyAdmin**
- [ ] Selecionar seu banco
- [ ] Abrir aba **SQL**
- [ ] Copiar conteúdo de `database/schema.sql`
- [ ] Colar e executar
- [ ] Verificar se 3 tabelas foram criadas

### 5. Configurar Arquivo
- [ ] Abrir `public_html/config/database.php` no File Manager
- [ ] Atualizar `DB_NAME` com nome completo
- [ ] Atualizar `DB_USER` com nome completo
- [ ] Atualizar `DB_PASS` com senha
- [ ] Salvar

### 6. Testar
- [ ] Acessar: `https://seudominio.com/test-db.php`
- [ ] Verificar se mostra ✅ em todos os testes
- [ ] Se houver ❌, corrigir e testar novamente

### 7. Testar Formulário
- [ ] Acessar landing page
- [ ] Preencher e enviar formulário
- [ ] Verificar no phpMyAdmin → tabela `leads`
- [ ] Deve aparecer o lead enviado!

### 8. Limpar
- [ ] Deletar `test-db.php` (por segurança)

---

## 📝 Informações para Anotar

**Nome completo do banco:**
```
_________________________________
```

**Nome completo do usuário:**
```
_________________________________
```

**Senha do usuário:**
```
_________________________________
```

---

## 🆘 Se Algo Der Errado

1. Verifique se anotou os nomes COMPLETOS (com prefixo)
2. Verifique se a senha está correta
3. Verifique se o usuário tem ALL PRIVILEGES
4. Verifique se executou o schema.sql
5. Veja `CONFIGURAR_DATABASE_PASSO_A_PASSO.md` para detalhes

---

**Boa sorte!** 🚀
