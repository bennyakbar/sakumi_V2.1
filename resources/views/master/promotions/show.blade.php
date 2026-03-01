<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Promotion Batch #{{ $promotion->id }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div><span class="font-semibold">From:</span> {{ $promotion->fromAcademicYear?->code }}</div>
                        <div><span class="font-semibold">To:</span> {{ $promotion->toAcademicYear?->code }}</div>
                        <div><span class="font-semibold">Status:</span> {{ $promotion->status }}</div>
                        <div><span class="font-semibold">Effective:</span> {{ optional($promotion->effective_date)->format('Y-m-d') }}</div>
                        <div><span class="font-semibold">Created by:</span> {{ $promotion->creator?->name }}</div>
                        <div><span class="font-semibold">Approved by:</span> {{ $promotion->approver?->name ?? '-' }}</div>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        @if ($promotion->status === 'draft')
                            <form method="POST" action="{{ route('master.promotions.approve', $promotion) }}">
                                @csrf
                                <x-primary-button>Approve</x-primary-button>
                            </form>
                        @endif
                        @if ($promotion->status === 'approved')
                            <form method="POST" action="{{ route('master.promotions.apply', $promotion) }}"
                                onsubmit="return confirm('Apply this promotion batch?');">
                                @csrf
                                <x-primary-button>Apply Batch</x-primary-button>
                            </form>
                        @endif
                        <a href="{{ route('master.promotions.index') }}" class="text-sm text-gray-600">Back</a>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Batch Items</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">From Enrollment</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Target Class</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Applied</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($promotion->items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $item->student?->nis }} - {{ $item->student?->name }}</td>
                                        <td class="px-4 py-2 text-sm">#{{ $item->from_enrollment_id }} ({{ $item->fromEnrollment?->schoolClass?->name }})</td>
                                        <td class="px-4 py-2 text-sm">{{ $item->action }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $item->toClass?->name ?? '-' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $item->is_applied ? 'yes' : 'no' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
