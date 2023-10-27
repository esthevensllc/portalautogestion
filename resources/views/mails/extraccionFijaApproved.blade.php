<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plantilla de correo</title>
</head>
<body>
    <h1>Extracción y Devolución / Informe Aprobado</h1>
    <p>CONTROL REGULATORIO APROBO EL SIGUIENTE INFORME DE FALLAS CON EL SIGUIENTE TICKET:</p>
    <table border="1" cellspacing="0" cellpadding="0" width="500px">
        <thead>
            <tr style="background-color:#dc3545;color:white;margin:0;">
                <th>N° de Reporte</th>
                <th>Servicio Afectado</th>
                <th>Ticket</th>
                <th>Archivo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:0;border-bottom-width:1pt;">{{ $informeFallas->numero_reporte }}</td>
                <td style="padding:0;border-bottom-width:1pt;">{{ $servicioAfectado->label }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $informeFallas->ticket }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $informeFallas->name_file }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
