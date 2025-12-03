<div class="w-full">
    <iframe 
        src="data:application/pdf;base64,{{ $pdfBase64 }}" 
        class="w-full border-0 rounded-lg shadow-md"
        style="height: 600px;"
        type="application/pdf">
        <p>Su navegador no soporta la visualización de PDF. 
           <a href="data:application/pdf;base64,{{ $pdfBase64 }}" download>Haga clic aquí para descargar el PDF</a>
        </p>
    </iframe>
</div>
