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
    <link rel="canonical" href="https://demo-basic.adminkit.io/pages-sign-in.html" />
    <title>{{env('APP_NAME')}}</title>

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

        .login-container {
            display: flex;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            width: 900px;
            max-width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .login-form-container {
            flex: 1;
            padding: 48px;
            background: white;
            display: flex;
            flex-direction: column;
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

        .login-header {
            margin-bottom: 32px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 24px;
        }

        .social-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 24px;
        }

        .social-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .social-button:hover {
            background: #f5f5f5;
            color: var(--primary-blue);
        }

        .divider {
            text-align: center;
            color: var(--text-secondary);
            font-size: 14px;
            margin: 16px 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: none;
            background: var(--background-gray);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--primary-blue);
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 24px;
        }

        .forgot-password a {
            color: var(--text-secondary);
            font-size: 14px;
            text-decoration: none;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            background: #1976d2;
            transform: translateY(-1px);
        }

        .welcome-container h2 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .welcome-container p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 24px;
        }

        .sign-up-link {
            color: white;
            text-decoration: underline;
        }

        /* New styles for the brand footer */
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

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column-reverse;
            }

            .login-form-container,
            .welcome-container {
                padding: 32px;
            }

            .welcome-container {
                padding-bottom: 48px;
            }
        }

        .invalid-feedback {
            color: #dc3545;
            font-size: 12px;
            margin-top: 4px;
        }

        .is-invalid {
            box-shadow: 0 0 0 2px #dc3545;
        }
    </style>
</head>

<body>
<div class="login-container">
    <div class="login-form-container">
        <div class="login-header">
            <h1>Log In</h1>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <input
                    type="email"
                    class="form-control @error('email') is-invalid @enderror"
                    name="email"
                    placeholder="Email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                >
                @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="form-group">
                <input
                    type="password"
                    class="form-control @error('password') is-invalid @enderror"
                    name="password"
                    placeholder="Password"
                    required
                >
                @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" value="remember-me" name="remember" checked>
                    <span class="form-check-label">Remember me</span>
                </label>
            </div>

            <div class="forgot-password">
                <a href="{{ route('password.request') }}">Forgot your password?</a>
            </div>

            <button type="submit" class="btn-login">LOG IN</button>
        </form>

        <!-- Added brand footer -->
        <div class="brand-footer">
            <div class="brand-name">{{env('APP_NAME')}}</div>
        </div>
    </div>

    <div class="welcome-container">
        <h2>Welcome Back!</h2>
        <p>Enter your credentials to login.</p>
    </div>
</div>

<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
