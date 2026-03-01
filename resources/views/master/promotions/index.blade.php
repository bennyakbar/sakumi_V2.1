<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Promotion Batches</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Promotion Batches</h2>
                        <a href="{{ route('master.promotions.create') }}"
                            class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            New Batch
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Effective Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($batches as $batch)
                                    <tr>
                                        <td class="px-4 py-3 text-sm">{{ $batch->id }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $batch->fromAcademicYear?->code }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $batch->toAcademicYear?->code }}</td>
                                        <td class="px-4 py-3 text-sm">{{ optional($batch->effective_date)->format('Y-m-d') }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $batch->items_count }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $batch->status }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <a class="text-indigo-600 hover:text-indigo-900"
                                                href="{{ route('master.promotions.show', $batch) }}">Detail</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-4 text-sm text-center text-gray-500">No promotion batches found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $batches->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
