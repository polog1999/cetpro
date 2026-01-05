<div class="w-full" style="height: 80vh; min-height: 500px;">
    @if($url)
        <iframe 
            src="{{ $url }}#toolbar=1&navpanes=0&scrollbar=1&view=FitH" 
            class="w-full h-full rounded-lg border border-gray-200 dark:border-gray-700"
            title="Documento de {{ $nombre }}"
            style="min-height: 500px;"
        ></iframe>
    @else
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="mt-4 text-gray-500 dark:text-gray-400">No se encontró el documento.</p>
            </div>
        </div>
    @endif
</div>

