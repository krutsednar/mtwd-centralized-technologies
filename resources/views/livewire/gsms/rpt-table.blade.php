<div>
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-2 py-3">
                    OR No.
                </th>
                <th scope="col" class="px-2 py-3">
                    Date Issued
                </th>
                <th scope="col" class="px-2 py-3">
                    Attachment
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($taxes as $tax)
                <tr class="bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <td class="px-2 py-3 font-medium text-gray-900 dark:text-white">
                        {{ $tax->or_no }}
                    </td>
                    <td class="px-2 py-3 text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($tax->date_issued)->format('F d, Y') }}
                    </td>
                    <td class="px-2 py-3 text-blue-600">
                        @if($tax->attachment)
                            <a
                                href="{{ Storage::url($tax->attachment) }}"
                                target="_blank"
                                class="flex items-center gap-1 text-blue-600 hover:underline dark:text-info"
                            >
                                <x-filament::icon
                                    icon="heroicon-c-eye"
                                    class="h-5 w-5 text-blue-500 dark:text-blue-400"
                                    style="color:#278bda;"
                                />
                                <span class="font-bold text-blue-400" style="color: #278bda;">View</span>
                            </a>
                        @else
                            N/A
                        @endif
                    </td>

                </tr>
            @endforeach
        </tbody>
    </table>
</div>
