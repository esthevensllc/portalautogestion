<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plantilla de correo</title>
</head>
<body>
    <h1>Informe de ENVIO DE TIPIFICACION</h1>
    <table border="1" cellspacing="0" cellpadding="0" width="500px">
        <thead>
            <tr style="background-color:#dc3545;color:white;margin:0;">
                <th>Tipo Operación</th>
                <th>Tipo Documento</th>
                <th>Archivo</th>
                <th>Imei</th>
                <th>Tipificación</th>
                <th>Tickler</th>
                <th>Istantaneo</th>
                <th>Notas</th>                
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $tipoOperacionId }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $tipoDocumentoId }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $documento }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $imei }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $tipificacionId }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $ticklerId }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $instantaneo }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $notas }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>