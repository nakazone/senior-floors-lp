# 📝 Comandos Git - Guia Rápido

## 🎯 Comandos Essenciais

### Ver Status
```bash
git status
```
Mostra o que mudou

### Adicionar Arquivos
```bash
git add .
```
Adiciona todos os arquivos modificados

### Fazer Commit
```bash
git commit -m "Descrição das mudanças"
```
Salva as mudanças localmente

### Verificar Remote
```bash
git remote -v
```
Mostra se está conectado ao GitHub

### Adicionar Remote (Primeira Vez)
```bash
git remote add origin https://github.com/SEU_USUARIO/senior-floors-system.git
```
Conecta ao GitHub (substitua SEU_USUARIO)

### Fazer Push (Enviar para GitHub)
```bash
git push -u origin main
```
Primeira vez (depois é só `git push`)

### Ver Histórico
```bash
git log --oneline
```
Mostra commits anteriores

## 🔄 Fluxo Completo (Depois do Setup)

```bash
# 1. Ver o que mudou
git status

# 2. Adicionar mudanças
git add .

# 3. Fazer commit
git commit -m "Descrição clara das mudanças"

# 4. Enviar para GitHub
git push
```

## 🆘 Comandos de Emergência

### Desfazer mudanças não commitadas
```bash
git restore .
```

### Ver diferenças
```bash
git diff
```

### Remover remote e adicionar de novo
```bash
git remote remove origin
git remote add origin https://github.com/SEU_USUARIO/senior-floors-system.git
```

## 📍 Onde Executar?

**No Terminal do Mac:**
1. Pressione `Cmd + Espaço`
2. Digite `Terminal`
3. Navegue até o projeto:
   ```bash
   cd /Users/naka/senior-floors-landing
   ```
4. Execute os comandos acima

## ✅ Checklist Rápido

- [ ] Repositório criado no GitHub?
- [ ] Remote adicionado? (`git remote -v`)
- [ ] Commit feito? (`git log`)
- [ ] Push feito? (`git push`)
