<<<<<<< HEAD
# CloudiTag - Sistema de Gestão de Perfil Empresarial

Sistema completo desenvolvido em PHP + MySQL + HTML + CSS + JavaScript para gestão de perfis empresariais e profissionais com recursos avançados.

## 🚀 Recursos Principais

- **Múltiplos Perfis**: Crie perfis empresariais e profissionais ilimitados
- **Links Dinâmicos**: Adicione, remova e organize links personalizados
- **Campos Personalizados**: Crie campos específicos para suas necessidades
- **URLs Amigáveis**: Slugs personalizáveis para cada perfil
- **Design Responsivo**: Funciona perfeitamente em todos os dispositivos
- **Recursos Avançados**:
  - Integração com PIX
  - Conexão WiFi via QR Code
  - WhatsApp direto
  - Localização no Google Maps
  - Links para avaliação no Google
- **Gerenciamento de Usuários**: Sistema completo com diferentes níveis de acesso
- **Interface Moderna**: Design elegante e fácil de usar

## 📋 Requisitos do Sistema

- **Servidor Web**: Apache ou Nginx
- **PHP**: Versão 7.4 ou superior
- **MySQL**: Versão 5.7 ou superior
- **Extensões PHP**:
  - PDO
  - PDO_MySQL
  - GD (para upload de imagens)
  - JSON
  - MBString

## 🛠️ Instalação

### 1. Download e Configuração

1. Faça o download do sistema e extraia para o diretório do seu servidor web
2. Configure as permissões das pastas:
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/profiles/
   chmod 755 uploads/users/
   ```

### 2. Instalação Automática

1. Acesse `http://seudominio.com/clouditag/install.php`
2. Preencha os dados do administrador geral
3. Clique em "Instalar Sistema"
4. Aguarde a conclusão da instalação

### 3. Configuração Manual (Opcional)

Se preferir configurar manualmente:

1. Crie um banco MySQL chamado `clouditag`
2. Execute o script SQL localizado em `install.php` (seção de criação de tabelas)
3. Configure o arquivo `includes/config.php` com suas credenciais
4. Crie um usuário administrador diretamente no banco

## 👥 Tipos de Usuário

### Admin Geral
- Acesso completo ao sistema
- Pode criar outros administradores
- Gerenciar todos os usuários e perfis
- Acesso ao dashboard administrativo

### Admin de Perfis
- Pode gerenciar perfis de qualquer usuário
- Acesso limitado ao painel administrativo
- Não pode criar outros administradores

### Usuário Comum
- Pode criar e gerenciar seus próprios perfis
- Acesso ao dashboard pessoal
- Upload de fotos e logos

## 🎨 Estrutura do Sistema

```
clouditag/
├── admin/              # Área administrativa
│   ├── dashboard.php   # Dashboard administrativo
│   ├── users.php       # Gerenciar usuários
│   └── profiles.php    # Gerenciar perfis
├── assets/             # Recursos estáticos
│   ├── css/           # Estilos CSS
│   ├── js/            # Scripts JavaScript
│   └── icons/         # Ícones do sistema
├── includes/           # Arquivos de configuração
│   ├── config.php     # Configurações do banco
│   └── functions.php  # Funções auxiliares
├── profile/           # Páginas públicas dos perfis
├── uploads/           # Arquivos enviados
│   ├── profiles/      # Logos dos perfis
│   └── users/         # Fotos dos usuários
└── api/               # APIs do sistema
```

## 📱 Como Usar

### Para Usuários

1. **Cadastro**: Acesse `/register.php` e crie sua conta
2. **Login**: Entre com suas credenciais em `/login.php`
3. **Criar Perfil**: No dashboard, clique em "Criar Novo Perfil"
4. **Gerenciar Links**: Acesse "Gerenciar Links" para adicionar links personalizados
5. **Compartilhar**: Use a URL personalizada para compartilhar seu perfil

### Para Administradores

1. **Acesso Admin**: Login com conta de administrador
2. **Dashboard**: Visualize estatísticas e atividades
3. **Gerenciar Usuários**: Controle contas de usuários
4. **Criar Admins**: Crie novos administradores (apenas Admin Geral)

## 🔧 Personalização

### Alterando Cores e Estilos

Edite o arquivo `assets/css/style.css`:

```css
:root {
    --primary-color: #667eea;    /* Cor principal */
    --secondary-color: #764ba2;  /* Cor secundária */
    --success-color: #28a745;    /* Cor de sucesso */
    /* ... outras variáveis ... */
}
```

### Adicionando Novos Ícones

1. Acesse o gerenciamento de campos em `edit_profile.php`
2. Adicione novos ícones no array `$popular_icons`
3. Use classes do Font Awesome 6.0

### URLs Amigáveis

O sistema usa `.htaccess` para URLs amigáveis:
- `/profile/meu-slug` → `/profile/index.php?slug=meu-slug`

## 🔒 Segurança

- Senhas criptografadas com `password_hash()`
- Proteção contra SQL Injection com PDO
- Sanitização de entradas
- Validação de uploads de arquivos
- Proteção de diretórios sensíveis
- Headers de segurança configurados

## 📊 Analytics

O sistema inclui analytics básico:
- Contagem de visualizações de perfis
- Rastreamento de cliques em links
- Limitação por IP para evitar spam
- API REST para coleta de dados

## 🐛 Solução de Problemas

### Erro de Permissões
```bash
chmod -R 755 clouditag/
chmod -R 777 clouditag/uploads/
```

### Erro de Banco de Dados
- Verifique as credenciais em `includes/config.php`
- Certifique-se que o MySQL está rodando
- Verifique se o usuário tem permissões no banco

### Imagens não carregam
- Verifique permissões da pasta `uploads/`
- Confirme se a extensão GD está instalada
- Verifique o tamanho máximo de upload no PHP

### URLs não funcionam
- Certifique-se que mod_rewrite está habilitado
- Verifique se o arquivo `.htaccess` está presente
- Ajuste o caminho base no `.htaccess` se necessário

## 📈 Atualizações Futuras

Recursos planejados:
- Dashboard com gráficos de analytics
- Temas personalizáveis
- Integração com redes sociais
- QR Code para perfis
- Sistema de notificações
- App mobile

## 🤝 Suporte

Para suporte e dúvidas:
- Documente bugs encontrados
- Sugira melhorias
- Contribua com o desenvolvimento

## 📄 Licença

Este sistema foi desenvolvido para uso educacional e comercial. 

---

**CloudiTag** - Sistema de Gestão de Perfil Empresarial
Desenvolvido com ❤️ em PHP
=======
# clouditag
>>>>>>> f72ee6539a3420712369a4040153b163a99455d0
