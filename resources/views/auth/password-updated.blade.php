<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Optimanage">
    <meta name="author" content="Optimanage">
    <meta name="keywords" content="OptiManage">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="{{ asset('img/icons/icon-48x48.png') }}" />
    <title>{{env('APP_NAME')}} - Password Updated</title>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-blue: #2196f3;
            --primary-gradient: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%);
            --text-primary: #333333;
            --text-secondary: #666666;
            --background-gray: #f5f5f5;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--primary-gradient);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            display: flex;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            width: 900px;
            max-width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .form-container {
            flex: 1;
            padding: 48px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }

        .welcome-container {
            flex: 1;
            background: var(--primary-gradient);
            padding: 48px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .header h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 24px;
        }

        .success-icon {
            font-size: 48px;
            color: #4caf50;
            margin-bottom: 24px;
        }

        .message {
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 32px;
        }

        .btn-login {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background: #1976d2;
            transform: translateY(-1px);
            color: white;
            text-decoration: none;
        }

        .brand-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e0e0e0;
        }

        .brand-name {
            font-size: 24px;
            font-weight: 600;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .welcome-container h2 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .welcome-container p {
            font-size: 16px;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column-reverse;
            }

            .form-container,
            .welcome-container {
                padding: 32px;
            }
        }
    </style>
</head>

<body>
<div class="container">
    <div class="form-container">
        <div class="header">
            <h1>Password Updated</h1>
        </div>

        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>

        <div class="message">
            Your password has been successfully updated. You can now log in with your new password.
        </div>
        <div class="brand-footer">
            <div class="brand-name">{{env('APP_NAME')}}</div>
        </div>
    </div>

    <div class="welcome-container">
        <h2>Success!</h2>
        <p>Your password has been reset successfully.</p>
    </div>
</div>

<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>

