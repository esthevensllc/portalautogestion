<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Plantilla de correo</title>
</head>
<body>
    <h1>Informe de EJECUCION BLOQ/DESQ</h1>
    <table border="1" cellspacing="0" cellpadding="0" width="500px">
        <thead>
            <tr style="background-color:#dc3545;color:white;margin:0;">
                <th>Id</th>
                <th>Fecha</th>
                <th>Tipo Operación</th>
                <th>Tipo Documento</th>
                <th>Documento</th>
                <th>Peso</th>
                <th>EIR</th>
                <th>Cantidad Registros</th>
                <th>Imeis Unicos</th>
                <th>Ejecuciones Exitosas</th>
                <th>Ejecuciones Fallidas</th>
                <th>Procesado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->id }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->fecha }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->tipo_operacion_id }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->tipo_documento_id }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->filename }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->size_bytes }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->eir_filename }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->cant_registros }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->cant_unicos }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->exec_ok }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->exec_fail }}</td>
                <td style="padding:0;border-bottom-width:1pt;text-align:right;">{{ $reporte->processed }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
