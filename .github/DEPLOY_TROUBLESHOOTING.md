# Troubleshooting: Deploy

## Erro: SSH Connection timed out

```
ssh: connect to host *** port ***: Connection timed out
❌ SSH connection test failed
```

### Causa

O runner do GitHub Actions não consegue conectar ao servidor SSH da Hostinger. Em geral:

1. **Firewall da Hostinger** bloqueia conexões de IPs externos (os IPs do GitHub mudam).
2. **SSH desativado** ou porta diferente no seu plano.
3. **Host/porta** incorretos nos secrets (`HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_PORT`).

### O que fazer

#### Opção 1: Usar apenas deploy por FTP (recomendado)

O workflow **Deploy to Hostinger** (FTP) costuma funcionar sem esse problema.

1. No repositório: **Settings** → **Secrets and variables** → **Actions**
2. Confira os secrets de FTP:
   - `HOSTINGER_FTP_HOST`
   - `HOSTINGER_FTP_USER`
   - `HOSTINGER_FTP_PASSWORD`
3. Desative o deploy por SSH para não falhar no push:
   - Renomeie `.github/workflows/deploy-hostinger-ssh-fixed.yml` para `deploy-hostinger-ssh-fixed.yml.disabled`
   - Ou apague o arquivo se não for usar SSH

Assim, só o workflow FTP roda no push e o deploy segue funcionando.

#### Opção 2: Corrigir SSH (se quiser usar SSH)

1. **Painel Hostinger**  
   Confirme se SSH está ativo e qual é o host/porta (ex.: `ssh.u123456789.hostinger.com`, porta 22 ou outra).

2. **Secrets no GitHub**  
   - `HOSTINGER_SSH_HOST`: host exato que a Hostinger mostra (não o domínio do site).  
   - `HOSTINGER_SSH_PORT`: porta SSH (22 ou a que a Hostinger indicar).  
   - `HOSTINGER_SSH_USER` e `HOSTINGER_SSH_KEY`: usuário e chave privada corretos.

3. **Firewall / IP**  
   A Hostinger pode exigir liberar IPs para SSH. Os IPs do GitHub Actions variam; muitas vezes o suporte da Hostinger precisa confirmar se é possível liberar acesso SSH para “qualquer IP” ou para uma faixa de IPs do GitHub.

4. **Testar SSH no seu PC**  
   No terminal:
   ```bash
   ssh -p PORTA USUARIO@HOST
   ```
   Se não conectar da sua rede, também não vai conectar do GitHub.

### Resumo

- **Deploy falhando por causa desse erro de SSH:** use a **Opção 1** (só FTP e desative o workflow SSH).  
- **Quer continuar usando SSH:** siga a **Opção 2** e, se precisar, abra um ticket na Hostinger sobre liberação de SSH para o GitHub Actions.
