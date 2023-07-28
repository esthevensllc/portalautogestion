<!DOCTYPE html>

<html>
<head>
    <meta charset="utf-8">
    <title>Plantilla de correo</title>
</head>
<body>
    <h1>Extracción y Devolución / Informe Aprobado</h1>
    <p>TIENES EL SIGUIENTE INFORME APROBADO:</p>
    <table border="1" cellspacing="0" cellpadding="0" width="500px">
        <thead>
            <tr style="background-color:#dc3545;color:white;margin:0;">
                <th>N° de reporte</th>
                <th>Ticket</th>
                <th>Archivo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportes as $reporte)
                <tr>
                    <td style="padding:0;border-bottom-width:1pt;">{{ $reporte->numero_de_reporte }}</td>
                    <td style="padding:0;border-bottom-width:1pt;">{{ $reporte->ticket }}</td>
                    <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->name_file }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>