<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página não encontrada - CloudiTag</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .error-page {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 60px 40px;
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 20px;
            line-height: 1;
        }
        
        .error-title {
            font-size: 2rem;
            color: var(--dark-color);
            margin-bottom: 20px;
        }
        
        .error-description {
            font-size: 1.1rem;
            color: var(--gray-600);
            margin-bottom: 40px;
        }
        
        .error-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .error-container {
                padding: 40px 20px;
            }
            
            .error-code {
                font-size: 6rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="error-page">
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Página não encontrada</h1>
        <p class="error-description">
            Oops! A página que você está procurando não existe ou foi movida. 
            Verifique o endereço digitado ou use os links abaixo para navegar.
        </p>
        
        <div class="error-actions">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Página Inicial
            </a>
            <a href="login.php" class="btn btn-light">
                <i class="fas fa-sign-in-alt"></i> Fazer Login
            </a>
            <a href="register.php" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Cadastrar
            </a>
        </div>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--gray-200);">
            <p style="color: var(--gray-500); font-size: 14px;">
                <i class="fas fa-cloud"></i> 
                <strong>CloudiTag</strong> - Sistema de Gestão de Perfil Empresarial
            </p>
        </div>
    </div>
</body>
</html>