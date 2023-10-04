<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plantilla de correo</title>
</head>
<body>
    <h1>Extracción y Devolución / Notificacion de archivo procesado</h1>
    <p>SE TIENE EL SIGUIENTE TICKET PROCESADO, FAVOR DE REALIZAR LAS DEVOLUCIONES:</p>
    <table border="1" cellspacing="0" cellpadding="0" width="500px">
        <thead>
            <tr style="background-color:#dc3545;color:white;margin:0;">
                <th>Ticket</th>
                <th>Departamento</th>
                <th>Incidencia</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:0;border-bottom-width:1pt;">{{ $ticket }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $departamento }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $incidencia }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
