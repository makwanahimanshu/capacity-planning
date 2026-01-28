{{-- <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

 --}}

<x-app-layout>
    {{-- <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot> --}}

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Welcome Card -->
            <div class="main-dashboard-welcome-card bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    {{ __('Welcome back! Select an option below to continue.') }}
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="main-dashboard-grid">
                <!-- Card 1: Capacity -->
                <a href="{{ url('/capacity') }}" class="main-dashboard-card main-dashboard-card-primary">
                    <div class="main-dashboard-card-icon">
                        <svg class="main-dashboard-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <div class="main-dashboard-card-content">
                        <h3 class="main-dashboard-card-title">Capacity Dashboard</h3>
                        <p class="main-dashboard-card-description">View and manage current capacity metrics</p>
                    </div>
                    <div class="main-dashboard-card-arrow">
                        <svg class="main-dashboard-arrow-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </div>
                </a>

                <!-- Card 2: Capacity Planning -->
                <a href="{{ url('/capacity_planning') }}" class="main-dashboard-card main-dashboard-card-secondary">
                    <div class="main-dashboard-card-icon">
                        <svg class="main-dashboard-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="main-dashboard-card-content">
                        <h3 class="main-dashboard-card-title">Capacity Planning</h3>
                        <p class="main-dashboard-card-description">Plan and forecast future capacity needs</p>
                    </div>
                    <div class="main-dashboard-card-arrow">
                        <svg class="main-dashboard-arrow-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <style>
        /* Main Dashboard Styles */
        .main-dashboard-welcome-card {
            animation: main-dashboard-fade-in 0.5s ease-out;
        }

        .main-dashboard-grid {
            padding: 26px;
            display: flex;
            gap: 2rem;
            width: 100%;
            animation: main-dashboard-slide-up 0.6s ease-out 0.2s both;
        }

        .main-dashboard-card {
            flex: 1;
            /* Each card takes 50% */
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 3rem 2rem;
            min-height: 300px;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            cursor: pointer;
        }

        .main-dashboard-card-primary::before,
        .main-dashboard-card-secondary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            transition: height 0.3s ease;
        }

        .main-dashboard-card-primary::before {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .main-dashboard-card-secondary::before {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .main-dashboard-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .main-dashboard-card:hover::before {
            height: 100%;
            opacity: 0.05;
        }

        .main-dashboard-card:active {
            transform: translateY(-4px);
        }

        .main-dashboard-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .main-dashboard-card-primary .main-dashboard-card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .main-dashboard-card-secondary .main-dashboard-card-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .main-dashboard-card:hover .main-dashboard-card-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .main-dashboard-icon {
            width: 2rem;
            height: 2rem;
            color: white;
        }

        .main-dashboard-card-content {
            flex: 1;
            z-index: 1;
        }

        .main-dashboard-card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .main-dashboard-card-secondary:hover .main-dashboard-card-title {
            color: #f5576c;
        }

        .main-dashboard-card-arrow {
            align-self: flex-end;
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: #f3f4f6;
            transition: all 0.3s ease;
        }

        .main-dashboard-card:hover .main-dashboard-card-arrow {
            background: #667eea;
            transform: translateX(4px);
        }

        .main-dashboard-card-secondary:hover .main-dashboard-card-arrow {
            background: #f5576c;
        }

        .main-dashboard-arrow-icon {
            width: 1.25rem;
            height: 1.25rem;
            color: #6b7280;
            transition: color 0.3s ease;
        }

        .main-dashboard-card:hover .main-dashboard-arrow-icon {
            color: white;
        }

        @keyframes main-dashboard-fade-in {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes main-dashboard-slide-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 640px) {
            .main-dashboard-grid {
                flex-direction: column;
                gap: 1.5rem;
            }

            .main-dashboard-card {
                padding: 2rem;
            }

            .main-dashboard-card-title {
                font-size: 1.25rem;
            }
        }
    </style>
</x-app-layout>
