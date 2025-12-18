<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ 'Capacity Tracker' }}</title>
    
    <!-- Fonts -->
    {{-- <link rel="preconnect" href="https://fonts.bunny.net"> --}}
    {{-- <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" /> --}}
    
    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Instrument Sans', sans-serif;
            }
        </style>
    @endif
    
    <style>
        .dashboard-main-body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-main-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .dashboard-main-header {
            position: relative;
            z-index: 10;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dashboard-main-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .dashboard-main-logo-icon {
            width: 2rem;
            height: 2rem;
            background: white;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-weight: 700;
        }
        
        .dashboard-main-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .dashboard-main-btn {
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .dashboard-main-btn-primary {
            background: white;
            color: #667eea;
            border: 2px solid white;
        }
        
        .dashboard-main-btn-primary:hover {
            background: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .dashboard-main-btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .dashboard-main-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: white;
            transform: translateY(-2px);
        }
        
        .dashboard-main-btn-ghost {
            background: transparent;
            color: white;
            border: 2px solid transparent;
        }
        
        .dashboard-main-btn-ghost:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .dashboard-main-container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 4rem 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 100px);
        }
        
        .dashboard-main-hero {
            text-align: center;
            color: white;
            /* animation: dashboard-main-fade-in-up 0.8s ease-out; */
        }
        
        .dashboard-main-hero-badge {
            display: inline-block;
            padding: 0.5rem 1.25rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .dashboard-main-hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #ffffff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .dashboard-main-hero-subtitle {
            font-size: 1.25rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 3rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .dashboard-main-hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .dashboard-main-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 5rem;
            width: 100%;
            /* animation: dashboard-main-fade-in-up 1s ease-out 0.3s both; */
        }
        
        .dashboard-main-feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .dashboard-main-feature-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }
        
        .dashboard-main-feature-icon {
            width: 3rem;
            height: 3rem;
            background: white;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: #667eea;
        }
        
        .dashboard-main-feature-icon svg {
            width: 1.75rem;
            height: 1.75rem;
        }
        
        .dashboard-main-feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: white;
            margin-bottom: 0.75rem;
        }
        
        .dashboard-main-feature-description {
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        /* @keyframes dashboard-main-fade-in-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
         */
        @media (max-width: 768px) {
            .dashboard-main-header {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .dashboard-main-nav {
                width: 100%;
                justify-content: center;
            }
            
            .dashboard-main-hero-title {
                font-size: 2.5rem;
            }
            
            .dashboard-main-hero-subtitle {
                font-size: 1.1rem;
            }
            
            .dashboard-main-features {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .dashboard-main-btn {
                font-size: 0.875rem;
                padding: 0.5rem 1.25rem;
            }
        }
    </style>
</head>
<body class="dashboard-main-body">
    <!-- Header -->
    <header class="dashboard-main-header">
        <a href="{{ url('/') }}" class="dashboard-main-logo">
            <div class="dashboard-main-logo-icon">C</div>
            <span>{{ 'Capacity Tracker' }}</span>
        </a>
        
        @if (Route::has('login'))
            <nav class="dashboard-main-nav">
                @auth
                    <a href="{{ url('/dashboard') }}" class="dashboard-main-btn dashboard-main-btn-primary">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="dashboard-main-btn dashboard-main-btn-ghost">
                        Log in
                    </a>
                    {{-- @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="dashboard-main-btn dashboard-main-btn-primary">
                            Register
                        </a>
                    @endif --}}
                @endauth
            </nav>
        @endif
    </header>

    <!-- Main Content -->
    <div class="dashboard-main-container">
        <div class="dashboard-main-hero">
            <div class="dashboard-main-hero-badge">
                📊 Welcome to Capacity Planner
            </div>
            
            <h1 class="dashboard-main-hero-title">
                Optimize Your Team & Projects
            </h1>
            
            <p class="dashboard-main-hero-subtitle">
                Monitor resource availability, allocate tasks efficiently, track project progress, and make data-driven decisions—all from a single intuitive dashboard.
            </p>
            
            <div class="dashboard-main-hero-actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="dashboard-main-btn dashboard-main-btn-primary">
                        Go to Dashboard
                    </a>
                @else
                    <a href="{{ route('register') }}" class="dashboard-main-btn dashboard-main-btn-primary">
                        Get Started
                    </a>
                    <a href="{{ route('login') }}" class="dashboard-main-btn dashboard-main-btn-secondary">
                        Sign In
                    </a>
                @endauth
            </div>
        </div>
    </div>


        <!-- Features -->
        {{-- <div class="dashboard-main-features">
            <div class="dashboard-main-feature-card">
                <div class="dashboard-main-feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="dashboard-main-feature-title">Lightning Fast</h3>
                <p class="dashboard-main-feature-description">
                    Built with modern technology to ensure blazing fast performance and seamless user experience.
                </p>
            </div>

            <div class="dashboard-main-feature-card">
                <div class="dashboard-main-feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h3 class="dashboard-main-feature-title">Secure by Default</h3>
                <p class="dashboard-main-feature-description">
                    Your data security is our priority. Built with industry-leading security practices and protocols.
                </p>
            </div>

            <div class="dashboard-main-feature-card">
                <div class="dashboard-main-feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="dashboard-main-feature-title">Powerful Analytics</h3>
                <p class="dashboard-main-feature-description">
                    Get insights into your capacity and planning with comprehensive analytics and reporting tools.
                </p>
            </div>
        </div> --}}
    </div>
</body>
</html>