<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Base Pterodactyl Language
    |--------------------------------------------------------------------------
    |
    | These base strings are used throughout the front-end of Pterodactyl but
    | not on pages that are used when viewing a server. Those keys are in server.php
    |
    */
    'validation_error' => 'Um erro ocorreu durante a validação de suas informações:',
    'confirm' => 'Você tem certeza?',
    'failed' => 'Essas credenciais não estão nos nossos registros.',
    'throttle' => 'Muitas tentativas de login. Por favor tente novamente em :seconds segundos.',
    'view_as_admin' => 'Você está vendo a lista de servidores como administrador. Assim, todos os servidores no sistema são mostrados. Qualquer servidor em que você esteja marcado como dono será mostrado com um ponto azul à esquerda de seu nome.',
    'server_name' => 'Nome do Servidor',
    'no_servers' => 'Você não tem nenhum servidor na sua conta atualmente.',
    'form_error' => 'Os seguintes erros ocorreram durante o processo do seu pedido.',
    'password_req' => 'Senhas devem cumprir o seguinte requiriso: pelo menos uma letra maiúscula, um minúscula, um dígito, e ter 8 caracteres no total.',
    'root_administrator' => 'Mudar isso para "Sim" dará ao usuário permissões completas administrativas ao PufferPanel.',
    'account' => [
        'totp_header' => 'Autenticação em Duas Etapas',
        'totp_qr' => 'QR Code TOTP',
        'totp_enable_help' => 'Você não parece ter a autenticação em duas etapas ativada. Esse método de autenticação adiciona uma barreira adicional prevenindo acesso não autorizado à sua conta. Se você ativar, será necessário fornecer um código gerado no seu celular ou outro dispositivo com suporte a TOTP antes de terminar o login.',
        'totp_apps' => 'Você precisa ter uma aplicação com suporte a TOTP (exemplo: Google Authenticator, DUO Mobile, Authy) para usar essa opção.',
        'totp_enable' => 'Ativar Autenticação em Duas Etapas',
        'totp_disable' => 'Desativar Autenticação em Duas Etapas',
        'totp_token' => 'Token TOTP',
        'totp_disable_help' => 'Para desativar o TOTP nesta conta será necessário fornecer um código TOTP válido. Uma vez validado, a autenticação em duas etapas nesta conta será desativada.',
        'totp_checkpoint_help' => 'Por favor verifique suas configurações de TOTP escanenando o QR Code à direita com o seu aplicativo de TOTP, e então forneça o código de 6 digitos dado pleo aplicativo na caixa abaixo. Aperte a tecla Enter quando tiver acabado.',
        'totp_enabled' => 'Sua conta foi ativada com autenticação TOTP. Por favor clique no botão de fechar desta caixa para finalizar.',
        'totp_enabled_error' => 'O código TOTP fornecido não foi autenticado. Por favor tente novamente.',
        'email_password' => 'Senha do Email',
        'update_user' => 'Atualizar Usuário',
        'delete_user' => 'Deletar Usuário',
        'update_email' => 'Atualizar Email',
        'new_email' => 'Novo Email',
        'new_password' => 'Nova Senha',
        'update_pass' => 'Atualizar Senha'
    ]
];
