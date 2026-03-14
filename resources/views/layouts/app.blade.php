<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100"
             x-data="{
                 sidebarCollapsed: localStorage.getItem('sidebar_collapsed') === 'true',
                 mobileOpen: false,
                 toggleCollapse() {
                     this.sidebarCollapsed = !this.sidebarCollapsed;
                     localStorage.setItem('sidebar_collapsed', this.sidebarCollapsed);
                 },
                 toggleMobile() {
                     this.mobileOpen = !this.mobileOpen;
                 },
                 closeMobile() {
                     this.mobileOpen = false;
                 }
             }"
             x-on:keydown.escape.window="closeMobile()"
        >
            {{-- Sidebar --}}
            @include('layouts.sidebar')

            {{-- Main content wrapper --}}
            <div class="transition-all duration-300 ease-in-out lg:pl-64"
                 :class="{ 'lg:pl-16': sidebarCollapsed, 'lg:pl-64': !sidebarCollapsed }">

                {{-- Top bar --}}
                @include('layouts.topbar')

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white shadow">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    @if (session('success') || session('status') || $errors->any() || session('temporary_password'))
                        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 space-y-2">
                            @if (session('success'))
                                <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if (session('status'))
                                <div class="rounded-md bg-blue-50 border border-blue-200 p-3 text-sm text-blue-800">
                                    {{ session('status') }}
                                </div>
                            @endif

                            @if (session('temporary_password'))
                                <div class="rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-900">
                                    <p class="font-semibold">Temporary Password</p>
                                    <p class="mt-1 font-mono">{{ session('temporary_password') }}</p>
                                    <p class="mt-1 text-xs">Share securely and require immediate password change.</p>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">
                                    <ul class="list-disc ms-5">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <script>
            (function () {
                const forms = document.querySelectorAll('.js-permanent-delete-form');
                if (!forms.length) {
                    return;
                }

                forms.forEach((form) => {
                    form.addEventListener('submit', async (event) => {
                        if (form.dataset.skipSubmit === '1') {
                            return;
                        }

                        event.preventDefault();

                        const previewUrl = form.dataset.previewUrl;
                        const entity = form.dataset.entity;
                        const entityId = form.dataset.entityId;
                        const confirmInput = form.querySelector('input[name="confirm_text"]');
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        if (!previewUrl || !entity || !entityId || !confirmInput || !token) {
                            alert('Permanent delete pre-check tidak tersedia.');
                            return;
                        }

                        const typed = prompt('Ketik HAPUS PERMANEN untuk melanjutkan penghapusan permanen');
                        if (typed === null) {
                            return;
                        }
                        confirmInput.value = typed;

                        try {
                            const response = await fetch(previewUrl, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': token,
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    entity: entity,
                                    id: Number(entityId),
                                }),
                            });

                            const payload = await response.json();
                            if (!response.ok || !payload.ok) {
                                alert(payload.reason || 'Pre-check permanent delete gagal.');
                                return;
                            }

                            if (payload.blocked) {
                                alert('Permanent delete diblokir karena dependensi:\n' + (payload.dependency_text || '-'));
                                return;
                            }

                            if (!confirm('Data tidak memiliki dependensi. Lanjutkan permanent delete?')) {
                                return;
                            }

                            form.dataset.skipSubmit = '1';
                            form.submit();
                        } catch (error) {
                            alert('Gagal melakukan pre-check permanent delete.');
                        }
                    });
                });
            })();
        </script>
        @stack('scripts')
    </body>
</html>
