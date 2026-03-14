<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Import Students') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Instructions --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Instructions') }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    {{-- Mandatory Fields --}}
                    <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                        <h4 class="text-sm font-bold text-red-800 uppercase tracking-wider mb-2">Kolom Wajib (REQUIRED)</h4>
                        <ul class="text-sm text-red-700 space-y-1">
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span><strong>name</strong> &mdash; Nama lengkap siswa</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span><strong>class_name</strong> &mdash; Nama kelas (harus sesuai data sistem)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span><strong>category_name</strong> &mdash; Kategori siswa (harus sesuai data sistem)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span><strong>gender</strong> &mdash; L (Laki-laki) atau P (Perempuan)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span><strong>enrollment_date</strong> &mdash; Tanggal masuk (YYYY-MM-DD)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                <span><strong>status</strong> &mdash; Aktif / Nonaktif / Lulus / Pindah / Keluar</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Optional Fields --}}
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <h4 class="text-sm font-bold text-gray-600 uppercase tracking-wider mb-2">Kolom Opsional (OPTIONAL)</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                <span><strong>nis</strong> &mdash; Nomor Induk Siswa</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                <span><strong>nisn</strong> &mdash; Nomor Induk Siswa Nasional</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                <span><strong>birth_place</strong> &mdash; Tempat lahir</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                <span><strong>birth_date</strong> &mdash; Tanggal lahir (YYYY-MM-DD)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                <span><strong>parent_name</strong> &mdash; Nama orang tua</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                <span><strong>parent_phone</strong> &mdash; No. telepon orang tua</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                <span><strong>parent_whatsapp</strong> &mdash; No. WhatsApp (format: 628xxx)</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 flex-shrink-0"></span>
                                <span><strong>address</strong> &mdash; Alamat</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-md p-3 mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Tips:</strong> Download template untuk memastikan format kolom yang benar.
                        Template berisi 2 contoh data &mdash; baris pertama dengan data lengkap, baris kedua hanya data wajib.
                        Kolom opsional yang kosong dapat dilengkapi nanti melalui halaman edit siswa.
                    </p>
                </div>

                <a href="{{ route('master.students.template') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ __('Download Template CSV') }}
                </a>
            </div>

            {{-- Upload Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Upload File') }}</h3>

                <form action="{{ route('master.students.processImport') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="file" :value="__('Choose Import File')" />
                        <input id="file" name="file" type="file" required accept=".csv,.txt,.xlsx,.xls"
                            class="mt-1 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-indigo-50 file:text-indigo-700
                                hover:file:bg-indigo-100" />
                        <p class="mt-1 text-xs text-gray-400">Format: CSV, TXT, XLSX, XLS (maks. 5MB)</p>
                        <x-input-error class="mt-2" :messages="$errors->get('file')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Import Students') }}</x-primary-button>
                        <a href="{{ route('master.students.index') }}"
                            class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
