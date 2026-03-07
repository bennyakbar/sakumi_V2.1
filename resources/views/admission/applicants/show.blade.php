<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('app.nav.applicants') }} - {{ $applicant->registration_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Detail Info --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="text-gray-500">No. Registrasi</div>
                            <div class="font-medium">{{ $applicant->registration_number }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('app.label.name') }}</div>
                            <div class="font-medium">{{ $applicant->name }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('app.label.period') }}</div>
                            <div class="font-medium">{{ $applicant->admissionPeriod->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('app.label.class') }} (Target)</div>
                            <div class="font-medium">{{ $applicant->targetClass->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('app.label.category') }}</div>
                            <div class="font-medium">{{ $applicant->category->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('app.label.gender') }}</div>
                            <div class="font-medium">{{ $applicant->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Tempat, Tanggal Lahir</div>
                            <div class="font-medium">{{ $applicant->birth_place ?? '-' }}, {{ $applicant->birth_date?->format('d/m/Y') ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Asal Sekolah</div>
                            <div class="font-medium">{{ $applicant->previous_school ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('Parent Name') }}</div>
                            <div class="font-medium">{{ $applicant->parent_name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">{{ __('Parent Phone') }}</div>
                            <div class="font-medium">{{ $applicant->parent_phone ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">WhatsApp</div>
                            <div class="font-medium">{{ $applicant->parent_whatsapp ?? '-' }}</div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-gray-500">{{ __('Address') }}</div>
                            <div class="font-medium">{{ $applicant->address ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="text-gray-500">{{ __('app.label.status') }}</div>
                            <div class="font-medium">
                                @php $statusColor = match($applicant->status) {
                                    'registered'   => 'bg-gray-100 text-gray-800',
                                    'under_review' => 'bg-yellow-100 text-yellow-800',
                                    'accepted'     => 'bg-blue-100 text-blue-800',
                                    'rejected'     => 'bg-red-100 text-red-800',
                                    'enrolled'     => 'bg-green-100 text-green-800',
                                    default        => 'bg-gray-100 text-gray-800',
                                }; @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColor }}">
                                    {{ __('app.status.' . $applicant->status) }}
                                </span>
                            </div>
                        </div>

                        @if($applicant->rejection_reason)
                            <div class="md:col-span-2">
                                <div class="text-gray-500">Alasan Penolakan</div>
                                <div class="font-medium text-red-600">{{ $applicant->rejection_reason }}</div>
                            </div>
                        @endif

                        @if($applicant->status_changed_at)
                            <div>
                                <div class="text-gray-500">Status Diubah</div>
                                <div class="font-medium">{{ $applicant->status_changed_at?->format('d/m/Y') }} oleh {{ $applicant->statusChanger->name ?? '-' }}</div>
                            </div>
                        @endif

                        @if($applicant->student)
                            <div>
                                <div class="text-gray-500">Siswa Terdaftar</div>
                                <div class="font-medium">
                                    <a href="{{ route('master.students.show', $applicant->student) }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ $applicant->student->name }} ({{ $applicant->student->nis }})
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div>
                            <div class="text-gray-500">{{ __('app.label.created_by') }}</div>
                            <div class="font-medium">{{ $applicant->creator->name ?? '-' }}</div>
                        </div>

                        @if($applicant->notes)
                            <div class="md:col-span-2">
                                <div class="text-gray-500">{{ __('app.label.notes') }}</div>
                                <div class="font-medium">{{ $applicant->notes }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- Workflow Action Buttons --}}
                    <div class="mt-8 border-t pt-6">
                        <h3 class="text-base font-semibold text-gray-900 mb-4">Aksi</h3>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('admission.applicants.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                                {{ __('app.button.back') }}
                            </a>

                            @if($applicant->status === 'registered')
                                @can('admission.applicants.review')
                                    <form method="POST" action="{{ route('admission.applicants.review', $applicant) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600">
                                            Review
                                        </button>
                                    </form>
                                @endcan
                            @endif

                            @if($applicant->status === 'under_review')
                                @can('admission.applicants.accept')
                                    <form method="POST" action="{{ route('admission.applicants.accept', $applicant) }}" class="inline"
                                        onsubmit="return confirm('Terima calon siswa ini?');">
                                        @csrf
                                        <button type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                            Terima
                                        </button>
                                    </form>
                                @endcan
                            @endif

                            @if(in_array($applicant->status, ['registered', 'under_review']))
                                @can('admission.applicants.reject')
                                    <form method="POST" action="{{ route('admission.applicants.reject', $applicant) }}" class="inline"
                                        x-data="{ showReason: false }"
                                        @submit.prevent="if(!showReason) { showReason = true; } else { $el.submit(); }">
                                        @csrf
                                        <div class="flex items-center gap-2">
                                            <button type="submit"
                                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                                Tolak
                                            </button>
                                            <div x-show="showReason" x-cloak>
                                                <input type="text" name="rejection_reason" placeholder="Alasan penolakan (opsional)"
                                                    class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-red-500 focus:border-red-500" />
                                            </div>
                                        </div>
                                    </form>
                                @endcan
                            @endif

                            @if($applicant->status === 'accepted')
                                @can('admission.applicants.enroll')
                                    <form method="POST" action="{{ route('admission.applicants.enroll', $applicant) }}" class="inline"
                                        x-data="{ showNis: false }"
                                        onsubmit="return confirm('Daftarkan sebagai siswa? Ini akan membuat data siswa, fee mapping, dan invoice pendaftaran.');">
                                        @csrf
                                        <div class="flex items-center gap-2">
                                            <button type="submit"
                                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                                Daftarkan (Enroll)
                                            </button>
                                            <button type="button" @click="showNis = !showNis"
                                                class="text-xs text-gray-500 underline">NIS custom?</button>
                                            <div x-show="showNis" x-cloak>
                                                <input type="text" name="nis" placeholder="NIS (opsional, auto-generate jika kosong)"
                                                    class="border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500" />
                                            </div>
                                        </div>
                                    </form>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
